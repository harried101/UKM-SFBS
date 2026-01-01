<?php
// 1. Setup & Security
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once '../includes/db_connect.php';
date_default_timezone_set('Asia/Kuala_Lumpur');

header('Content-Type: application/json');

// Helper
function jsonResponse($success, $message, $data = []) {
    ob_clean();
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

// 2. Auth Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'Student') {
    jsonResponse(false, 'Access Denied');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingId = $_POST['booking_id'] ?? null;
    $studentIdentifier = $_SESSION['user_id'];

    if (!$bookingId) jsonResponse(false, 'Missing Booking ID');

    try {
        // 3. Get User ID (INT)
        $userStmt = $conn->prepare("SELECT UserID FROM users WHERE UserIdentifier = ?");
        $userStmt->bind_param("s", $studentIdentifier);
        $userStmt->execute();
        $userId = $userStmt->get_result()->fetch_assoc()['UserID'] ?? 0;
        $userStmt->close();

        if (!$userId) jsonResponse(false, 'User profile not found.');

        // 4. Fetch Booking
        $stmt = $conn->prepare("SELECT StartTime, EndTime, Status FROM bookings WHERE BookingID = ? AND UserID = ?");
        $stmt->bind_param("ii", $bookingId, $userId);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$booking) jsonResponse(false, 'Booking not found or unauthorized.');

        // 5. Validation: Time & Status
        $now = new DateTime();
        $start = new DateTime($booking['StartTime']);
        // Handle 0000-00-00 case
        if ($booking['StartTime'] === '0000-00-00 00:00:00') {
             $start = new DateTime($booking['EndTime']);
        }

        if ($start < $now) {
            jsonResponse(false, 'Cannot cancel past bookings.');
        }

        if ($booking['Status'] === 'Canceled') {
            jsonResponse(false, 'Booking is already canceled.');
        }

        // 6. Execute Cancellation
        // NOTE: using 'Canceled' (one L) based on your DB ENUM
        $updateStmt = $conn->prepare("UPDATE bookings SET Status = 'Canceled', UpdatedAt = NOW() WHERE BookingID = ?");
        $updateStmt->bind_param("i", $bookingId);
        
        if ($updateStmt->execute()) {
            
            // 7. NEW: Calculate Weekly Cancellation Rate immediately
            // This helps the frontend warn the user if they crossed the 33% threshold
            $weekMode = 1; // Starts Monday
            $statsSql = "SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN Status = 'Canceled' THEN 1 ELSE 0 END) as canceled
                         FROM bookings 
                         WHERE UserID = ? 
                         AND YEARWEEK(StartTime, ?) = YEARWEEK(NOW(), ?)";
            
            $statsStmt = $conn->prepare($statsSql);
            $statsStmt->bind_param("iii", $userId, $weekMode, $weekMode);
            $statsStmt->execute();
            $stats = $statsStmt->get_result()->fetch_assoc();
            
            $rate = ($stats['total'] > 0) ? round(($stats['canceled'] / $stats['total']) * 100) : 0;
            $warning = ($rate > 33) ? "Warning: Your weekly cancellation rate is {$rate}%. You may be blocked from booking." : "";

            jsonResponse(true, 'Booking cancelled successfully.', ['new_rate' => $rate, 'warning' => $warning]);
        
        } else {
            jsonResponse(false, 'Database update failed.');
        }
        $updateStmt->close();

    } catch (Exception $e) {
        jsonResponse(false, 'Server Error: ' . $e->getMessage());
    }
}
?>