<?php
session_start();
require_once '../includes/db_connect.php';

// --- 1. GET REQUEST: Fetch Dynamic Slots (Synced with DB) ---
if (isset($_GET['get_slots'])) {
    header('Content-Type: application/json');
    
    $date = $_GET['date'] ?? '';
    $facilityID = $_GET['facility_id'] ?? '';

    if (!$date || !$facilityID) {
        echo json_encode(['success' => false, 'message' => 'Missing date or ID']);
        exit;
    }

    // A. CHECK CLOSURES (scheduleoverrides)
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
            'success' => false, 
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
    
    // --- UPDATED LOGIC: Default to 9AM - 10PM if no schedule found ---
    if ($sched_res->num_rows === 0) {
        $openTime = '09:00:00';
        $closeTime = '22:00:00'; // 10 PM
        $slotDuration = 60;      // 1 Hour default
    } else {
        $schedule = $sched_res->fetch_assoc();
        $openTime = $schedule['OpenTime'];
        $closeTime = $schedule['CloseTime'];
        $slotDuration = intval($schedule['SlotDuration']);
    }

    // C. FETCH EXISTING BOOKINGS
    // We select TIME(StartTime) to compare easily
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
        // Take only HH:MM to ensure match even if seconds differ
        $booked_times[] = substr($row['booked_time'], 0, 5); 
    }
    $stmt->close();

    // D. GENERATE TIME SLOTS
    $slots = [];
    $current = strtotime($date . ' ' . $openTime);
    $end = strtotime($date . ' ' . $closeTime);

    while ($current < $end) {
        // Formats: "09:00:00" for DB, "09:00" for comparison, "09:00 AM" for display
        $startTimeStr = date('H:i:s', $current);
        $compareTime = date('H:i', $current); 
        $labelTime = date('h:i A', $current); 
        
        $slotEndTime = $current + ($slotDuration * 60);
        
        // Stop if the slot exceeds closing time
        if ($slotEndTime > $end) break;

        // Check if this time exists in booked_times array
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
    
    // 1. Get Slot Duration (or default to 60)
    $dayOfWeek = date('l', strtotime($startTime));
    $dur_sql = "SELECT SlotDuration FROM facilityschedules WHERE FacilityID = ? AND DayOfWeek = ?";
    $d_stmt = $conn->prepare($dur_sql);
    $d_stmt->bind_param("ss", $facilityID, $dayOfWeek);
    $d_stmt->execute();
    $dur_res = $d_stmt->get_result();
    
    if ($dur_row = $dur_res->fetch_assoc()) {
        $durationMinutes = intval($dur_row['SlotDuration']);
    } else {
        $durationMinutes = 60;
    }
    $d_stmt->close();

    // 2. Calculate End Time
    $endTime = date('Y-m-d H:i:s', strtotime($startTime . " +$durationMinutes minutes"));

    // 3. Get User ID
    $u_stmt = $conn->prepare("SELECT UserID FROM users WHERE UserIdentifier = ?");
    $u_stmt->bind_param("s", $userIdentifier);
    $u_stmt->execute();
    $res = $u_stmt->get_result();
    
    if ($row = $res->fetch_assoc()) {
        $userID = $row['UserID'];
    } else {
        $userID = $userIdentifier; 
    }
    $u_stmt->close();

    // 4. Double Booking Check
    $check = $conn->prepare("SELECT BookingID FROM bookings WHERE FacilityID = ? AND StartTime = ? AND Status IN ('Approved', 'Pending')");
    $check->bind_param("ss", $facilityID, $startTime);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        echo "<script>alert('Sorry! This slot was just taken.'); window.parent.location.reload();</script>";
        exit;
    }
    $check->close();

    // 5. Insert
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