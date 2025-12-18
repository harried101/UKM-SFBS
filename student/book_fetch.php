<?php
// 1. Clean Output Buffer (Prevents whitespace errors)
ob_start();

// 2. Disable Error Display (Prevents HTML appearing in JSON)
ini_set('display_errors', 0); 
error_reporting(E_ALL);

session_start();
require_once '../includes/db_connect.php';

// CRITICAL: Set Timezone to Malaysia
date_default_timezone_set('Asia/Kuala_Lumpur');

header('Content-Type: application/json');

function jsonResponse($success, $message, $data = []) {
    ob_clean(); // Clear any previous output
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

try {
    // --- 1. GET SLOTS (For Calendar) ---
    if (isset($_GET['get_slots'])) {
        
        $date = $_GET['date'] ?? '';
        $facilityID = $_GET['facility_id'] ?? '';

        if (!$date || !$facilityID) jsonResponse(false, 'Missing data');

        $selectedDate = date('Y-m-d', strtotime($date));

        // A. CHECK OVERRIDES (Maintenance)
        $closure_sql = "SELECT Reason FROM scheduleoverrides 
                        WHERE FacilityID = ? 
                        AND ? BETWEEN DATE(StartTime) AND DATE(EndTime) 
                        LIMIT 1";
        $stmt = $conn->prepare($closure_sql);
        $stmt->bind_param("is", $facilityID, $selectedDate);
        $stmt->execute();
        
        if ($row = $stmt->get_result()->fetch_assoc()) {
            jsonResponse(true, '', [
                'is_closed' => true, 
                'message' => "Closed: " . $row['Reason']
            ]);
        }
        $stmt->close();

        // B. CHECK WEEKLY SCHEDULE
        $dayIndex = date('w', strtotime($selectedDate)); // 0=Sun, 6=Sat
        $dayName = date('l', strtotime($selectedDate));
        
        $sched_sql = "SELECT OpenTime, CloseTime, SlotDuration 
                      FROM facilityschedules 
                      WHERE FacilityID = ? AND DayOfWeek = ? 
                      LIMIT 1";
        $stmt = $conn->prepare($sched_sql);
        $stmt->bind_param("ii", $facilityID, $dayIndex);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) {
            jsonResponse(true, '', [
                'is_closed' => true, 
                'message' => "Facility is closed on {$dayName}s."
            ]);
        }
        
        $sched = $res->fetch_assoc();
        $stmt->close();

        // C. GET EXISTING BOOKINGS
        $bk_sql = "SELECT StartTime FROM bookings 
                   WHERE FacilityID = ? 
                   AND DATE(StartTime) = ? 
                   AND Status IN ('Approved', 'Pending', 'Confirmed')";
        $stmt = $conn->prepare($bk_sql);
        $stmt->bind_param("is", $facilityID, $selectedDate);
        $stmt->execute();
        $bk_res = $stmt->get_result();
        
        $booked_times = [];
        while ($row = $bk_res->fetch_assoc()) {
            // Store full datetime string: "2025-12-19 09:00:00"
            $booked_times[] = $row['StartTime'];
        }
        $stmt->close();

        // D. GENERATE SLOTS
        $slots = [];
        $curr = strtotime($selectedDate . ' ' . $sched['OpenTime']);
        $end = strtotime($selectedDate . ' ' . $sched['CloseTime']);
        $dur = intval($sched['SlotDuration']);

        while ($curr < $end) {
            // FIX: This creates the full datetime string for the DB
            $slotStartFull = date('Y-m-d H:i:s', $curr);
            
            // NEW: Skip Past Times
            // If the slot start time is in the past (compared to now), we skip it.
            // This prevents users from booking 9:00 AM at 10:00 AM on the same day.
            if ($curr < time()) {
                $curr += ($dur * 60);
                continue;
            }

            // Check if this slot exceeds closing time
            if (($curr + ($dur * 60)) > $end) break;

            $status = in_array($slotStartFull, $booked_times) ? 'booked' : 'available';

            $slots[] = [
                'start' => $slotStartFull, // Value for DB
                'label' => date('h:i A', $curr), // Value for Display
                'status' => $status
            ];

            $curr += ($dur * 60);
        }

        jsonResponse(true, 'Slots fetched', ['slots' => $slots, 'is_closed' => false]);
    }

    // --- 2. SUBMIT BOOKING ---
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        
        if (!isset($_SESSION['user_id'])) jsonResponse(false, 'Session expired');

        $userIdentifier = $_SESSION['user_id'];
        $facilityID = $_POST['facility_id'];
        $startTime = $_POST['start_time']; // Expecting "2025-12-19 09:00:00"

        if (empty($startTime)) jsonResponse(false, 'Invalid time selection');

        // 1. Get User ID
        $stmt = $conn->prepare("SELECT UserID FROM users WHERE UserIdentifier = ?");
        $stmt->bind_param("s", $userIdentifier);
        $stmt->execute();
        $uid = $stmt->get_result()->fetch_assoc()['UserID'] ?? 0;
        $stmt->close();

        if (!$uid) jsonResponse(false, 'User ID not found');

        // 2. Get Duration for End Time
        $dayIndex = date('w', strtotime($startTime));
        $stmt = $conn->prepare("SELECT SlotDuration FROM facilityschedules WHERE FacilityID=? AND DayOfWeek=?");
        $stmt->bind_param("ii", $facilityID, $dayIndex);
        $stmt->execute();
        $dur = $stmt->get_result()->fetch_assoc()['SlotDuration'] ?? 60;
        $stmt->close();

        $endTime = date('Y-m-d H:i:s', strtotime($startTime) + ($dur * 60));

        // 3. Double Check Availability
        $check = $conn->prepare("SELECT BookingID FROM bookings WHERE FacilityID=? AND StartTime=? AND Status IN ('Approved','Pending','Confirmed')");
        $check->bind_param("is", $facilityID, $startTime);
        $check->execute();
        if ($check->get_result()->num_rows > 0) jsonResponse(false, 'Slot taken');
        $check->close();

        // 4. Insert
        $stmt = $conn->prepare("INSERT INTO bookings (UserID, FacilityID, StartTime, EndTime, Status, BookedAt) VALUES (?, ?, ?, ?, 'Pending', NOW())");
        $stmt->bind_param("isss", $uid, $facilityID, $startTime, $endTime);

        if ($stmt->execute()) {
            jsonResponse(true, 'Booking successful!');
        } else {
            jsonResponse(false, 'DB Error: ' . $conn->error);
        }
    }

} catch (Exception $e) {
    jsonResponse(false, 'Server Error: ' . $e->getMessage());
}
?>