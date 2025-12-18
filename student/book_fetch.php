<?php
session_start();
require_once '../includes/db_connect.php';

// Set Timezone to ensure day calculations match Malaysia time
date_default_timezone_set('Asia/Kuala_Lumpur');

// --- 1. GET REQUEST: Fetch Dynamic Slots ---
if (isset($_GET['get_slots'])) {
    header('Content-Type: application/json');
    
    $date = $_GET['date'] ?? '';
    $facilityID = $_GET['facility_id'] ?? '';

    if (!$date || !$facilityID) {
        echo json_encode(['success' => false, 'message' => 'Missing date or ID']);
        exit;
    }

    $selectedDate = date('Y-m-d', strtotime($date));

    // A. CHECK CLOSURES (Maintenance/Events from scheduleoverrides)
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

    // B. FETCH WEEKLY SCHEDULE (facilityschedules)
    // 0 = Sunday, 1 = Monday, ..., 6 = Saturday
    $dayIndex = date('w', strtotime($selectedDate)); 
    $dayName = date('l', strtotime($selectedDate)); // e.g., "Saturday"
    
    $sched_sql = "SELECT OpenTime, CloseTime, SlotDuration 
                  FROM facilityschedules 
                  WHERE FacilityID = ? AND DayOfWeek = ?";
    $stmt = $conn->prepare($sched_sql);
    $stmt->bind_param("ii", $facilityID, $dayIndex);
    $stmt->execute();
    $sched_res = $stmt->get_result();
    
    // --- CRITICAL FIX: IF NO ROW FOUND, RETURN CLOSED ---
    if ($sched_res->num_rows === 0) {
        echo json_encode([
            'success' => true, 
            'slots' => [], 
            'message' => "Facility is closed on {$dayName}s.", // Message shown to student
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
        // Store just the HH:MM (e.g. "09:00")
        $booked_times[] = substr($row['booked_time'], 0, 5); 
    }
    $stmt->close();

    // D. GENERATE TIME SLOTS
    $slots = [];
    $current = strtotime($selectedDate . ' ' . $openTime);
    $end = strtotime($selectedDate . ' ' . $closeTime);

    while ($current < $end) {
        $startTimeStr = date('H:i:s', $current);
        $compareTime = date('H:i', $current); // 09:00
        $labelTime = date('h:i A', $current); // 09:00 AM
        
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
    $startTime = $_POST['start_time']; // "YYYY-MM-DD HH:MM:SS"
    
    // 1. Get Slot Duration from DB to calculate End Time
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
        echo json_encode(['success' => false, 'message' => 'Sorry, this slot was just taken.']);
        exit;
    }
    $check->close();

    // 5. Insert Booking
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