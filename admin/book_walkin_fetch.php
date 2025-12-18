<?php
session_start();
require_once '../includes/db_connect.php';
header("Content-Type: application/json; charset=utf-8");

// SECURITY: Only Admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(["success" => false, "message" => "Access Denied"]);
    exit;
}

function normalizeDateTime($input) {
    if (!$input) return false;
    $ts = strtotime(trim($input));
    if ($ts !== false) return date('Y-m-d H:i:s', $ts);
    return false;
}

function getUserIDByIdentifier($conn, $identifier) {
    if (!$identifier) return 0;
    $stmt = $conn->prepare("SELECT UserID FROM users WHERE UserIdentifier = ? AND Role = 'Student' LIMIT 1");
    if (!$stmt) return 0;
    $stmt->bind_param("s", $identifier);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) return intval($row['UserID']);
    return 0;
}

function jres($arr) {
    echo json_encode($arr);
    exit;
}

// --- GET SLOTS (Same logic as student, but accessible by admin) ---
if (isset($_GET['get_slots'])) {
    $facilityID = $_GET['facility_id'] ?? '';
    $dateInput = $_GET['date'] ?? '';

    if (!$facilityID || !$dateInput) jres(["success"=>false,"message"=>"Missing facility_id or date"]);

    $dateOnly = date('Y-m-d', strtotime($dateInput));

    // Check overrides
    $stmt = $conn->prepare("SELECT Reason FROM scheduleoverrides WHERE FacilityID=? AND DATE(?) BETWEEN DATE(StartTime) AND DATE(EndTime) LIMIT 1");
    $stmt->bind_param("ss",$facilityID,$dateOnly);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) jres(["success"=>true,"is_closed"=>true,"message"=>"Facility Closed: ".$row['Reason'], "slots"=>[]]);

    // Weekly schedule
    $dayIndex = intval(date('w', strtotime($dateOnly)));
    $stmt = $conn->prepare("SELECT OpenTime, CloseTime, SlotDuration FROM facilityschedules WHERE FacilityID=? AND DayOfWeek=? LIMIT 1");
    $stmt->bind_param("si",$facilityID,$dayIndex);
    $stmt->execute();
    $schedule = $stmt->get_result()->fetch_assoc();
    if (!$schedule) jres(["success"=>true,"is_closed"=>true,"message"=>"Facility closed on this day","slots"=>[]]);

    $open = strtotime($dateOnly.' '.$schedule['OpenTime']);
    $close = strtotime($dateOnly.' '.$schedule['CloseTime']);
    $duration = intval($schedule['SlotDuration']);

    // fetch booked slots
    $stmt = $conn->prepare("SELECT StartTime FROM bookings WHERE FacilityID=? AND DATE(StartTime)=? AND Status IN ('Approved','Pending','Confirmed')");
    $stmt->bind_param("ss",$facilityID,$dateOnly);
    $stmt->execute();
    $res = $stmt->get_result();
    $booked = [];
    while($r = $res->fetch_assoc()) $booked[] = $r['StartTime'];

    // build slots
    $slots=[];
    $current = $open;
    // For admin, we might allow booking past slots if needed, but let's keep it future-only for now or allow all for flexibility?
    // Let's stick to future only to avoid confusion, or maybe allow current day past slots? 
    // Standard logic:
    $now = time();
    
    while($current+($duration*60) <= $close){
        // Optional: Allow admins to backdate? For now, let's enforce same rules as students for simplicity, 
        // but maybe relax the "now" check if requested. The prompt implies "walk-ins" which are usually "now" or "future".
        if($dateOnly===date('Y-m-d') && ($current+($duration*60))<= $now){ 
             // Uncomment to hide past slots even for admin
             $current+=$duration*60; continue; 
        }
        
        $slotStart = date('Y-m-d H:i:s', $current);
        $slots[] = ["start"=>$slotStart,"label"=>date('h:i A',$current),"status"=>in_array($slotStart,$booked)?'booked':'available'];
        $current+=$duration*60;
    }

    jres(["success"=>true,"is_closed"=>false,"slots"=>$slots]);
}

// --- PROCESS BOOKING ---
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['facility_id'], $_POST['start_time'], $_POST['student_id'])){
    $facilityID = trim($_POST['facility_id']); // TRIM ADDED
    $rawStart = $_POST['start_time'];
    $studentIdentifier = trim($_POST['student_id']);

    $startTimeDT = normalizeDateTime($rawStart);
    if(!$startTimeDT) jres(["success"=>false,"message"=>"Invalid start_time"]);

    // Validate Student
    $userID = getUserIDByIdentifier($conn, $studentIdentifier);
    if(!$userID) jres(["success"=>false,"message"=>"Student ID not found in system."]);

    // Validate Schedule
    $dayIndex = intval(date('w',strtotime($startTimeDT)));
    $stmt = $conn->prepare("SELECT SlotDuration, OpenTime, CloseTime FROM facilityschedules WHERE FacilityID=? AND DayOfWeek=? LIMIT 1");
    $stmt->bind_param("si",$facilityID,$dayIndex);
    $stmt->execute();
    $sched = $stmt->get_result()->fetch_assoc();
    if(!$sched) jres(["success"=>false,"message"=>"No schedule found"]);

    $duration = intval($sched['SlotDuration']);
    $endTimeDT = date('Y-m-d H:i:s', strtotime($startTimeDT)+$duration*60);
    $openT = strtotime(substr($startTimeDT,0,10).' '.$sched['OpenTime']);
    $closeT = strtotime(substr($startTimeDT,0,10).' '.$sched['CloseTime']);
    $slotTS = strtotime($startTimeDT);
    
    if($slotTS<$openT || ($slotTS+$duration*60)>$closeT) jres(["success"=>false,"message"=>"Slot outside operating hours"]);

    // Check availability
    // Note: We should double check if it's already booked to avoid double booking race conditions
    $stmtCheck = $conn->prepare("SELECT BookingID FROM bookings WHERE FacilityID=? AND StartTime=? AND Status IN ('Approved','Pending','Confirmed')");
    $stmtCheck->bind_param("ss", $facilityID, $startTimeDT);
    $stmtCheck->execute();
    if($stmtCheck->get_result()->num_rows > 0) {
        jres(["success"=>false,"message"=>"Slot already booked"]);
    }

    $adminIdentifier = $_SESSION['user_id']; // The admin creating this (String Identifier)
    
    // Get Admin's actual UserID (Integer)
    $stmtAdmin = $conn->prepare("SELECT UserID FROM users WHERE UserIdentifier = ? LIMIT 1");
    $stmtAdmin->bind_param("s", $adminIdentifier);
    $stmtAdmin->execute();
    $resAdmin = $stmtAdmin->get_result();
    $adminRow = $resAdmin->fetch_assoc();
    
    if (!$adminRow) {
        jres(["success"=>false,"message"=>"Admin user not found in database."]);
    }
    $adminUserID = $adminRow['UserID'];

    $conn->begin_transaction();
    // Status is 'Confirmed' for walk-ins
    $stmt = $conn->prepare("INSERT INTO bookings (UserID, FacilityID, StartTime, EndTime, Status, CreatedByAdminID, BookedAt, UpdatedAt) VALUES (?, ?, ?, ?, 'Confirmed', ?, NOW(), NOW())");
    $stmt->bind_param("isssi", $userID, $facilityID, $startTimeDT, $endTimeDT, $adminUserID);

    try{
        if(!$stmt->execute()){
            $errno=$stmt->errno;
            $error=$conn->error;
            $conn->rollback();
            // DEBUG LOGGING
            file_put_contents("debug_log.txt", date('Y-m-d H:i:s')." - Error: $error | FacilityID: '$facilityID'\n", FILE_APPEND);
            
            if($errno==1062) jres(["success"=>false,"message"=>"Slot already booked"]);
            else jres(["success"=>false,"message"=>"Database error: " . $error]);
        }else{
            $bookingID = $conn->insert_id;
            $conn->commit();
            jres(["success"=>true,"message"=>"Walk-in booking confirmed!","booking_id"=>$bookingID]);
        }
    }catch(Exception $e){
        $conn->rollback();
        // DEBUG LOGGING
        file_put_contents("debug_log.txt", date('Y-m-d H:i:s')." - Exception: ".$e->getMessage()." | FacilityID: '$facilityID' | UserID: $userID\n", FILE_APPEND);
        jres(["success"=>false,"message"=>"Server error: " . $e->getMessage()]);
    }
}

jres(["success"=>false,"message"=>"Invalid API endpoint"]);
?>
