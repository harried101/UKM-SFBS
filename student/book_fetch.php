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

        // C. FETCH EXISTING BOOKINGS (Active ones only)
        $bk_sql = "SELECT StartTime 
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
            // Store full datetime to match exact slots
            $booked_times[] = $row['StartTime']; 
        }
        $stmt->close();

        // D. GENERATE TIME SLOTS
        $slots = [];
        $current = strtotime($selectedDate . ' ' . $openTime);
        $end = strtotime($selectedDate . ' ' . $closeTime);

        while ($current < $end) {
            // FIX: Use full Y-m-d H:i:s format so DB gets correct date
            $startTimeStr = date('Y-m-d H:i:s', $current);
            $labelTime = date('h:i A', $current); 
            
            $slotEndTime = $current + ($slotDuration * 60);
            
            if ($slotEndTime > $end) break;

            $isBooked = in_array($startTimeStr, $booked_times);

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
        $facilityID = $_POST['facility_id']; // Allow Alphanumeric IDs
        $startTime = $_POST['start_time']; 
        
        // Debug Log
        file_put_contents('booking_debug.log', date('Y-m-d H:i:s') . " - Attempting booking. FacID: $facilityID, UserID: $userIdentifier\n", FILE_APPEND);

        if (empty($facilityID)) {
             jsonResponse(false, 'Invalid Facility ID.');
        }

        // 1. Get Slot Duration AND Verify Facility Exists
        $dayIndex = date('w', strtotime($startTime));
        $dur_sql = "SELECT SlotDuration FROM facilityschedules WHERE FacilityID = ? AND DayOfWeek = ?";
        $d_stmt = $conn->prepare($dur_sql);
        $d_stmt->bind_param("si", $facilityID, $dayIndex); // 's' for string ID
        $d_stmt->execute();
        $dur_res = $d_stmt->get_result();
        
        $durationMinutes = 60; 
        if ($dur_row = $dur_res->fetch_assoc()) {
            $durationMinutes = intval($dur_row['SlotDuration']);
        } else {
             // If no schedule found, verify if facility actually exists
             $fac_check = $conn->prepare("SELECT FacilityID FROM facilities WHERE FacilityID = ?");
             $fac_check->bind_param("s", $facilityID); // 's' for string ID
             $fac_check->execute();
             if ($fac_check->get_result()->num_rows === 0) {
                 jsonResponse(false, 'Facility does not exist.');
             }
             $fac_check->close();
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

        // ==========================================
        // CHECK CANCELLATION RATE (MONTHLY)
        // ==========================================
        // $weekMode = 1; // Removed
        $statsSql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN Status = 'Canceled' THEN 1 ELSE 0 END) as canceled
                     FROM bookings 
                     WHERE UserID = ? 
                     AND MONTH(StartTime) = MONTH(NOW())
                     AND YEAR(StartTime) = YEAR(NOW())";
        
        $statsStmt = $conn->prepare($statsSql);
        $statsStmt->bind_param("i", $userID);
        $statsStmt->execute();
        $stats = $statsStmt->get_result()->fetch_assoc();
        $statsStmt->close();

        if ($stats['total'] >= 3) {
            $cancelRate = ($stats['canceled'] / $stats['total']) * 100;
            if ($cancelRate > 33) {
                // Friendly Message
                jsonResponse(false, "To ensure fair access for everyone, your booking privileges are paused for the rest of this month due to a high cancellation rate. You can book again next month!");
            }
        }

        // ==========================================
        // 4. SMART BOOKING (Handle Duplicates/Re-booking)
        // ==========================================
        // Check if a record already exists for this slot (Active OR Cancelled)
        $check = $conn->prepare("SELECT BookingID, Status FROM bookings WHERE FacilityID = ? AND StartTime = ?");
        $check->bind_param("ss", $facilityID, $startTime); // 's' for string ID
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();
        $check->close();

        $status = 'Pending';

        if ($existing) {
            // If slot is taken by an active booking
            if (in_array($existing['Status'], ['Approved', 'Pending', 'Confirmed'])) {
                jsonResponse(false, 'Sorry, this slot was just taken.');
            } 
            // If slot exists but was Cancelled/Rejected -> REUSE IT (Update)
            else {
                $upd = $conn->prepare("UPDATE bookings SET UserID=?, EndTime=?, Status=?, BookedAt=NOW(), CreatedByAdminID=NULL WHERE BookingID=?");
                $upd->bind_param("issi", $userID, $endTime, $status, $existing['BookingID']);
                
                if ($upd->execute()) {
                    jsonResponse(true, 'Booking successful!', ['booking_id' => $existing['BookingID']]);
                } else {
                    file_put_contents('booking_debug.log', "Update Failed: " . $conn->error . "\n", FILE_APPEND);
                    jsonResponse(false, 'Update Error: ' . $conn->error);
                }
                $upd->close();
            }
        } else {
            // No existing record -> INSERT NEW
            
            // Double check facility exists right before insert just in case
            $fac_final_check = $conn->prepare("SELECT COUNT(*) FROM facilities WHERE FacilityID = ?");
            $fac_final_check->bind_param("s", $facilityID); // 's' for string ID
            $fac_final_check->execute();
            if ($fac_final_check->get_result()->fetch_row()[0] == 0) {
                 file_put_contents('booking_debug.log', "Final Check Failed: Facility $facilityID not found.\n", FILE_APPEND);
                 jsonResponse(false, 'Facility not found (Final safety check).');
            }
            $fac_final_check->close();

            $ins = $conn->prepare("INSERT INTO bookings (UserID, FacilityID, StartTime, EndTime, Status, BookedAt) VALUES (?, ?, ?, ?, ?, NOW())");
            $ins->bind_param("issss", $userID, $facilityID, $startTime, $endTime, $status); // 's' for FacilityID

            if ($ins->execute()) {
                jsonResponse(true, 'Booking successful!', ['booking_id' => $ins->insert_id]);
            } else {
                file_put_contents('booking_debug.log', "Insert Failed: " . $conn->error . " | FacID: $facilityID\n", FILE_APPEND);
                // Throw to catch block or return error
                throw new Exception("Insert Error: " . $conn->error);
            }
            $ins->close();
        }

    }

} catch (Exception $e) {
    jsonResponse(false, 'Server Error: ' . $e->getMessage());
}
?>
