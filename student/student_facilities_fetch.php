<?php
session_start();
require_once '../includes/db_connect.php';

// --- 1. GET REQUEST: Fetch Dynamic Slots (Synced with Admin Data) ---
if (isset($_GET['get_slots'])) {
    header('Content-Type: application/json');
    
    $date = $_GET['date'] ?? '';
    $facilityID = $_GET['facility_id'] ?? '';

    if (!$date || !$facilityID) {
        echo json_encode(['success' => false, 'message' => 'Missing date or ID']);
        exit;
    }

    $selectedDate = date('Y-m-d', strtotime($date));

    // A. CHECK CLOSURES (Maintenance/Events)
    // Checks if the selected date falls inside any blocked range set by Admin
    $closure_sql = "SELECT Reason FROM scheduleoverrides 
                    WHERE FacilityID = ? 
                    AND ? BETWEEN DATE(StartTime) AND DATE(EndTime) 
                    LIMIT 1";
    $stmt = $conn->prepare($closure_sql);
    $stmt->bind_param("is", $facilityID, $selectedDate);
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

    // B. FETCH WEEKLY SCHEDULE
    // Convert date to Day Index (0=Sunday, 1=Monday... matches Admin setup)
    $dayIndex = date('w', strtotime($selectedDate)); 
    
    $sched_sql = "SELECT OpenTime, CloseTime, SlotDuration 
                  FROM facilityschedules 
                  WHERE FacilityID = ? AND DayOfWeek = ?";
    $stmt = $conn->prepare($sched_sql);
    $stmt->bind_param("ii", $facilityID, $dayIndex);
    $stmt->execute();
    $sched_res = $stmt->get_result();
    
    // If no schedule exists for this specific day (e.g. Sat/Sun unchecked in Admin), it is CLOSED.
    if ($sched_res->num_rows === 0) {
        echo json_encode([
            'success' => true, 
            'slots' => [], 
            'message' => 'Facility is closed on this day.', 
            'is_closed' => true
        ]);
        exit;
    }

    $schedule = $sched_res->fetch_assoc();
    $openTime = $schedule['OpenTime'];
    $closeTime = $schedule['CloseTime'];
    $slotDuration = intval($schedule['SlotDuration']);
    $stmt->close();

    // C. FETCH EXISTING BOOKINGS (To gray out taken slots)
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
        $booked_times[] = substr($row['booked_time'], 0, 5); // "09:00"
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
        
        // Stop if the slot exceeds closing time
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
        echo json_encode(['success' => false, 'message' => 'Session expired.']);
        exit;
    }

    $userIdentifier = $_SESSION['user_id'];
    $facilityID = $_POST['facility_id'];
    $startTime = $_POST['start_time']; // Full datetime string from JS
    
    // 1. Get Slot Duration
    $dayIndex = date('w', strtotime($startTime));
    $dur_sql = "SELECT SlotDuration FROM facilityschedules WHERE FacilityID = ? AND DayOfWeek = ?";
    $d_stmt = $conn->prepare($dur_sql);
    $d_stmt->bind_param("ii", $facilityID, $dayIndex);
    $d_stmt->execute();
    $dur_res = $d_stmt->get_result();
    
    $durationMinutes = 60; // Default
    if ($dur_row = $dur_res->fetch_assoc()) {
        $durationMinutes = intval($dur_row['SlotDuration']);
    }
    $d_stmt->close();

    // 2. Calculate End Time
    $endTime = date('Y-m-d H:i:s', strtotime($startTime . " +$durationMinutes minutes"));

    // 3. Get User ID (Integer)
    $u_stmt = $conn->prepare("SELECT UserID FROM users WHERE UserIdentifier = ?");
    $u_stmt->bind_param("s", $userIdentifier);
    $u_stmt->execute();
    $res = $u_stmt->get_result();
    $userID = ($row = $res->fetch_assoc()) ? $row['UserID'] : 0;
    $u_stmt->close();

    if ($userID === 0) {
        echo json_encode(['success' => false, 'message' => 'User profile not found.']);
        exit;
    }

    // 4. Double Booking Check
    $check = $conn->prepare("SELECT BookingID FROM bookings WHERE FacilityID = ? AND StartTime = ? AND Status IN ('Approved', 'Pending', 'Confirmed')");
    $check->bind_param("is", $facilityID, $startTime);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Slot already taken.']);
        exit;
    }
    $check->close();

    // 5. Insert
    $status = 'Pending';
    $ins = $conn->prepare("INSERT INTO bookings (UserID, FacilityID, StartTime, EndTime, Status, BookedAt) VALUES (?, ?, ?, ?, ?, NOW())");
    $ins->bind_param("iisss", $userID, $facilityID, $startTime, $endTime, $status);

    if ($ins->execute()) {
        echo json_encode(['success' => true, 'booking_id' => $ins->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'DB Error: ' . $conn->error]);
    }

    $ins->close();
    $conn->close();
    exit;
}
?>