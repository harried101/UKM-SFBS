<?php
// 1. START BUFFERING: Catches any unwanted whitespace or errors
ob_start();

// 2. DISABLE ERROR DISPLAY: Prevents PHP warnings from breaking JSON
ini_set('display_errors', 0); 
error_reporting(E_ALL);

session_start();
require_once '../includes/db_connect.php';

// Set Timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

header('Content-Type: application/json');

// HELPER: Returns JSON and stops script
function jsonResponse($success, $message, $data = []) {
    // Clear any previous output (warnings, whitespace)
    ob_clean(); 
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

try {
    // --- 1. GET REQUEST: Fetch Dynamic Slots ---
    if (isset($_GET['get_slots'])) {
        
        $date = $_GET['date'] ?? '';
        $facilityID = $_GET['facility_id'] ?? '';

        if (!$date || !$facilityID) {
            jsonResponse(false, 'Missing date or ID');
        }

        $selectedDate = date('Y-m-d', strtotime($date));

        // A. CHECK CLOSURES (Maintenance/Events)
        $closure_sql = "SELECT Reason FROM scheduleoverrides 
                        WHERE FacilityID = ? 
                        AND ? BETWEEN DATE(StartTime) AND DATE(EndTime) 
                        LIMIT 1";
        $stmt = $conn->prepare($closure_sql);
        if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
        
        $stmt->bind_param("is", $facilityID, $selectedDate);
        $stmt->execute();
        $closure_res = $stmt->get_result();
        
        if ($closure_row = $closure_res->fetch_assoc()) {
            jsonResponse(true, 'Facility Closed: ' . $closure_row['Reason'], ['slots' => [], 'is_closed' => true]);
        }
        $stmt->close();

        // B. FETCH WEEKLY SCHEDULE
        $dayIndex = date('w', strtotime($selectedDate)); 
        $dayName = date('l', strtotime($selectedDate)); 
        
        $sched_sql = "SELECT OpenTime, CloseTime, SlotDuration 
                      FROM facilityschedules 
                      WHERE FacilityID = ? AND DayOfWeek = ?";
        $stmt = $conn->prepare($sched_sql);
        $stmt->bind_param("ii", $facilityID, $dayIndex);
        $stmt->execute();
        $sched_res = $stmt->get_result();
        
        // IF NO ROW FOUND, RETURN CLOSED
        if ($sched_res->num_rows === 0) {
            jsonResponse(true, "Facility is closed on {$dayName}s.", ['slots' => [], 'is_closed' => true]);
        }

        $schedule = $sched_res->fetch_assoc();
        $openTime = $schedule['OpenTime'];
        $closeTime = $schedule['CloseTime'];
        $slotDuration = intval($schedule['SlotDuration']);
        $stmt->close();

        // C. FETCH EXISTING BOOKINGS
        $bk_sql = "SELECT TIME(StartTime) as booked_time 
                   FROM bookings 
                   WHERE FacilityID = ? 
                   AND DATE(StartTime) = ? 
                   AND Status IN ('Approved', 'Pending', 'Confirmed')";
        $stmt = $conn->prepare($bk_sql);
        $stmt->bind_param("is", $facilityID, $selectedDate);
        $stmt->execute();
        $bk_res = $stmt->get_result();
        
        $booked_times = [];
        while ($row = $bk_res->fetch_assoc()) {
            $booked_times[] = substr($row['booked_time'], 0, 5); 
        }
        $stmt->close();

        // D. GENERATE TIME SLOTS
        $slots = [];
        $current = strtotime($selectedDate . ' ' . $openTime);
        $end = strtotime($selectedDate . ' ' . $closeTime);

        while ($current < $end) {
            $startTimeStr = date('H:i:s', $current);
            $compareTime = date('H:i', $current); 
            $labelTime = date('h:i A', $current); 
            
            $slotEndTime = $current + ($slotDuration * 60);
            
            if ($slotEndTime > $end) break;

            $isBooked = in_array($compareTime, $booked_times);

            $slots[] = [
                'start' => $startTimeStr,
                'label' => $labelTime,
                'status' => $isBooked ? 'booked' : 'available'
            ];

            $current = $slotEndTime;
        }

        jsonResponse(true, '', ['slots' => $slots, 'is_closed' => false]);
    }

    // --- 2. POST REQUEST: Submit Booking ---
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        
        if (!isset($_SESSION['user_id'])) {
            jsonResponse(false, 'Session expired.');
        }

        $userIdentifier = $_SESSION['user_id'];
        $facilityID = $_POST['facility_id'];
        $startTime = $_POST['start_time']; 
        
        // 1. Get Slot Duration
        $dayIndex = date('w', strtotime($startTime));
        $dur_sql = "SELECT SlotDuration FROM facilityschedules WHERE FacilityID = ? AND DayOfWeek = ?";
        $d_stmt = $conn->prepare($dur_sql);
        $d_stmt->bind_param("ii", $facilityID, $dayIndex);
        $d_stmt->execute();
        $dur_res = $d_stmt->get_result();
        
        $durationMinutes = 60; 
        if ($dur_row = $dur_res->fetch_assoc()) {
            $durationMinutes = intval($dur_row['SlotDuration']);
        }
        $d_stmt->close();

        // 2. Calculate End Time
        $endTime = date('Y-m-d H:i:s', strtotime($startTime . " +$durationMinutes minutes"));

        // 3. Get User ID
        $u_stmt = $conn->prepare("SELECT UserID FROM users WHERE UserIdentifier = ?");
        $u_stmt->bind_param("s", $userIdentifier);
        $u_stmt->execute();
        $res = $u_stmt->get_result();
        $userID = ($row = $res->fetch_assoc()) ? $row['UserID'] : 0;
        $u_stmt->close();

        if ($userID === 0) {
            jsonResponse(false, 'User profile not found.');
        }

        // 4. Double Booking Check
        $check = $conn->prepare("SELECT BookingID FROM bookings WHERE FacilityID = ? AND StartTime = ? AND Status IN ('Approved', 'Pending', 'Confirmed')");
        $check->bind_param("is", $facilityID, $startTime);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            jsonResponse(false, 'Sorry, this slot was just taken.');
        }
        $check->close();

        // 5. Insert Booking
        $status = 'Pending';
        $ins = $conn->prepare("INSERT INTO bookings (UserID, FacilityID, StartTime, EndTime, Status, BookedAt) VALUES (?, ?, ?, ?, ?, NOW())");
        $ins->bind_param("iisss", $userID, $facilityID, $startTime, $endTime, $status);

        if ($ins->execute()) {
            jsonResponse(true, 'Booking successful!', ['booking_id' => $ins->insert_id]);
        } else {
            jsonResponse(false, 'DB Error: ' . $conn->error);
        }

        $ins->close();
    }

} catch (Exception $e) {
    jsonResponse(false, 'Server Error: ' . $e->getMessage());
}
?>