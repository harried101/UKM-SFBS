<?php
session_start();
require_once '../includes/db_connect.php';

// --- 1. GET REQUEST: Fetch Dynamic Slots ---
if (isset($_GET['get_slots'])) {
    header('Content-Type: application/json');
    
    $date = $_GET['date'] ?? '';
    $facilityID = $_GET['facility_id'] ?? '';

    if (!$date || !$facilityID) {
        echo json_encode(['success' => false, 'message' => 'Missing date or ID']);
        exit;
    }

    // A. CHECK CLOSURES (scheduleoverrides)
    // Check if the requested date is inside a maintenance range
    $closure_sql = "SELECT Reason FROM scheduleoverrides 
                    WHERE FacilityID = ? 
                    AND ? BETWEEN DATE(StartTime) AND DATE(EndTime) 
                    LIMIT 1";
    $stmt = $conn->prepare($closure_sql);
    $stmt->bind_param("ss", $facilityID, $date);
    $stmt->execute();
    $closure_res = $stmt->get_result();
    
    if ($closure_row = $closure_res->fetch_assoc()) {
        echo json_encode([
            'success' => true, 
            'slots' => [], 
            'message' => 'Facility Closed: ' . $closure_row['Reason'],
            'is_closed' => true
        ]);
        exit;
    }
    $stmt->close();

    // B. FETCH WEEKLY SCHEDULE (facilityschedules)
    $dayOfWeek = date('l', strtotime($date)); // e.g., "Monday"
    
    $sched_sql = "SELECT OpenTime, CloseTime, SlotDuration 
                  FROM facilityschedules 
                  WHERE FacilityID = ? AND DayOfWeek = ?";
    $stmt = $conn->prepare($sched_sql);
    $stmt->bind_param("ss", $facilityID, $dayOfWeek);
    $stmt->execute();
    $sched_res = $stmt->get_result();
    
    // --- DEFAULT LOGIC IF NO SCHEDULE ---
    if ($sched_res->num_rows === 0) {
        // Default: 9 AM to 10 PM
        $openTime = '09:00:00';
        $closeTime = '22:00:00';
        $slotDuration = 60;
    } else {
        $schedule = $sched_res->fetch_assoc();
        $openTime = $schedule['OpenTime'];
        $closeTime = $schedule['CloseTime'];
        $slotDuration = intval($schedule['SlotDuration']);
    }

    // C. FETCH EXISTING BOOKINGS
    $bk_sql = "SELECT TIME(StartTime) as booked_time 
               FROM bookings 
               WHERE FacilityID = ? 
               AND DATE(StartTime) = ? 
               AND Status IN ('Approved', 'Pending')";
    $stmt = $conn->prepare($bk_sql);
    $stmt->bind_param("ss", $facilityID, $date);
    $stmt->execute();
    $bk_res = $stmt->get_result();
    
    $booked_times = [];
    while ($row = $bk_res->fetch_assoc()) {
        $booked_times[] = substr($row['booked_time'], 0, 5); // Store "09:00"
    }
    $stmt->close();

    // D. GENERATE TIME SLOTS
    $slots = [];
    $current = strtotime($date . ' ' . $openTime);
    $end = strtotime($date . ' ' . $closeTime);

    while ($current < $end) {
        $startTimeStr = date('H:i:s', $current); // "09:00:00"
        $compareTime = date('H:i', $current);    // "09:00"
        $labelTime = date('h:i A', $current);    // "09:00 AM"
        
        $slotEndTime = $current + ($slotDuration * 60);
        
        // Stop if slot goes past closing
        if ($slotEndTime > $end) break;

        $isBooked = in_array($compareTime, $booked_times);

        $slots[] = [
            'start' => $startTimeStr,
            'label' => $labelTime,
            'status' => $isBooked ? 'booked' : 'available'
        ];

        $current = $slotEndTime;
    }

    echo json_encode(['success' => true, 'slots' => $slots, 'is_closed' => false]);
    $conn->close();
    exit;
}

// --- 2. POST REQUEST: Submit Booking ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    if (!isset($_SESSION['user_id'])) {
        die("<script>alert('Session expired. Please log in.'); window.parent.location.reload();</script>");
    }

    $userIdentifier = $_SESSION['user_id'];
    $facilityID = $_POST['facility_id'];
    $startTime = $_POST['start_time']; 
    
    // Calculate End Time based on duration
    $dayOfWeek = date('l', strtotime($startTime));
    $dur_sql = "SELECT SlotDuration FROM facilityschedules WHERE FacilityID = ? AND DayOfWeek = ?";
    $d_stmt = $conn->prepare($dur_sql);
    $d_stmt->bind_param("ss", $facilityID, $dayOfWeek);
    $d_stmt->execute();
    $dur_res = $d_stmt->get_result();
    
    $durationMinutes = ($dur_row = $dur_res->fetch_assoc()) ? intval($dur_row['SlotDuration']) : 60;
    $d_stmt->close();

    $endTime = date('Y-m-d H:i:s', strtotime($startTime . " +$durationMinutes minutes"));

    // Get User ID
    $u_stmt = $conn->prepare("SELECT UserID FROM users WHERE UserIdentifier = ?");
    $u_stmt->bind_param("s", $userIdentifier);
    $u_stmt->execute();
    $res = $u_stmt->get_result();
    $userID = ($row = $res->fetch_assoc()) ? $row['UserID'] : $userIdentifier;
    $u_stmt->close();

    // Double Booking Check
    $check = $conn->prepare("SELECT BookingID FROM bookings WHERE FacilityID = ? AND StartTime = ? AND Status IN ('Approved', 'Pending')");
    $check->bind_param("ss", $facilityID, $startTime);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        echo "<script>alert('Sorry! This slot was just taken.'); window.parent.location.reload();</script>";
        exit;
    }
    $check->close();

    // Insert
    $status = 'Pending';
    $ins = $conn->prepare("INSERT INTO bookings (UserID, FacilityID, StartTime, EndTime, Status) VALUES (?, ?, ?, ?, ?)");
    $ins->bind_param("issss", $userID, $facilityID, $startTime, $endTime, $status);

    if ($ins->execute()) {
        echo "<script>
            alert('Booking Submitted Successfully!');
            window.parent.location.reload(); 
        </script>";
    } else {
        echo "<script>alert('Database Error: " . addslashes($conn->error) . "');</script>";
    }

    $ins->close();
    $conn->close();
    exit;
}
?>