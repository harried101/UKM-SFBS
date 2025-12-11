<?php

session_start();
require_once '../includes/db_connect.php';
header("Content-Type: application/json; charset=utf-8");

function normalizeDateTime($input) {
    if (!$input) return false;
    $ts = strtotime(trim($input));
    if ($ts !== false) return date('Y-m-d H:i:s', $ts);
    return false;
}

// guna UserIdentifier untuk cari UserID sebab guna untuk login
function getActualUserID($conn, $identifier) {
    if (!$identifier) return 0;
    $stmt = $conn->prepare("SELECT UserID FROM users WHERE UserIdentifier = ? LIMIT 1");
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

// get slot yang available
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
    $now = time();
    while($current+($duration*60) <= $close){
        if($dateOnly===date('Y-m-d') && ($current+($duration*60))<= $now){ $current+=$duration*60; continue; }
        $slotStart = date('Y-m-d H:i:s', $current);
        $slots[] = ["start"=>$slotStart,"label"=>date('h:i A',$current),"status"=>in_array($slotStart,$booked)?'booked':'available'];
        $current+=$duration*60;
    }

    jres(["success"=>true,"is_closed"=>false,"slots"=>$slots]);
}

// masuk table bookings
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['facility_id'], $_POST['start_time'])){
    $facilityID = $_POST['facility_id'];
    $rawStart = $_POST['start_time'];

    $startTimeDT = normalizeDateTime($rawStart);
    if(!$startTimeDT) jres(["success"=>false,"message"=>"Invalid start_time"]);

    $sessionIdentifier = $_SESSION['user_id'] ?? null;
    $userID = getActualUserID($conn,$sessionIdentifier);
    if(!$userID) jres(["success"=>false,"message"=>"Invalid user session"]);

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

    $conn->begin_transaction();
    $stmt = $conn->prepare("INSERT INTO bookings (UserID,FacilityID,StartTime,EndTime,Status,CreatedByAdminID,BookedAt,UpdatedAt) VALUES (?,?,?,?, 'Pending', NULL, NOW(), NOW())");
    $stmt->bind_param("isss",$userID,$facilityID,$startTimeDT,$endTimeDT);

    try{
    if(!$stmt->execute()){
        $errno=$stmt->errno;
        $conn->rollback();
        if($errno==1062) jres(["success"=>false,"message"=>"Slot already booked"]);
        else jres(["success"=>false,"message"=>"Database error"]);
    }else{
        $bookingID = $conn->insert_id; // Get ID immediately after execute
        $conn->commit();
        jres(["success"=>true,"message"=>"Booking submitted","booking_id"=>$bookingID]);
    }
}catch(Exception $e){
    $conn->rollback();
    jres(["success"=>false,"message"=>"Server error"]);
}

}

jres(["success"=>false,"message"=>"Invalid API endpoint"]);
