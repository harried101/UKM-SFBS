<?php
// ==========================================
// FETCH CANCELLATION RATE (WEEKLY) API
// ==========================================

ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once '../../includes/db_connect.php';
date_default_timezone_set('Asia/Kuala_Lumpur');

header('Content-Type: application/json');

// ---------- Helper ----------
function jsonResponse($success, $data) {
    ob_clean();
    echo json_encode([
        'success' => $success,
        'data' => $data
    ]);
    exit;
}

// ---------- Auth ----------
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'Student') {
    jsonResponse(false, ['message' => 'Access denied']);
}

try {
    // ---------- Get User ID ----------
    $studentIdentifier = $_SESSION['user_id'];

    $uStmt = $conn->prepare(
        "SELECT UserID FROM users WHERE UserIdentifier = ? LIMIT 1"
    );
    $uStmt->bind_param("s", $studentIdentifier);
    $uStmt->execute();
    $userRow = $uStmt->get_result()->fetch_assoc();
    $uStmt->close();

    if (!$userRow) {
        jsonResponse(false, ['message' => 'User not found']);
    }

    $uid = (int)$userRow['UserID'];

    // ---------- Weekly Booking Stats ----------
    // ISO week (Monday start)
    $weekMode = 1;

    $sql = "
        SELECT 
            COUNT(*) AS total_bookings,
            SUM(CASE WHEN Status = 'Canceled' THEN 1 ELSE 0 END) AS total_canceled
        FROM bookings
        WHERE UserID = ?
        AND YEARWEEK(StartTime, ?) = YEARWEEK(NOW(), ?)
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $uid, $weekMode, $weekMode);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $totalBookings = (int)($stats['total_bookings'] ?? 0);
    $totalCanceled = (int)($stats['total_canceled'] ?? 0);

    // ---------- Calculation ----------
    // Cancellation Rate (LOW is good, HIGH is bad)
    $cancelRate = ($totalBookings > 0)
        ? round(($totalCanceled / $totalBookings) * 100)
        : 0;

    // ---------- Status Logic (Based on Cancellation Rate) ----------
    $status = 'Low'; // Low Cancellation Rate
    $color = 'green';
    $message = 'Your cancellation rate is excellent (Low).';

    if ($totalBookings >= 3) {
        // High Risk (33% or more canceled)
        if ($cancelRate >= 33) { 
            $status = 'High (Risk)';
            $color = 'red';
            $message = 'Cancellation Rate is high (≥33%). Your future bookings may be restricted.';
        
        // Moderate Risk (15% to 32% canceled)
        } elseif ($cancelRate >= 15) { 
            $status = 'Moderate';
            $color = 'amber';
            $message = 'Your cancellation rate is moderate (≥15%). Avoid frequent cancellations.';
        
        // Low Risk (Below 15%)
        } else {
            // Keep status 'Low' (green)
        }
    } else {
        $message = 'Not enough data yet. Your account is in good standing.';
    }

    // ---------- Response ----------
    jsonResponse(true, [
        'total_weekly'     => $totalBookings,
        'canceled_weekly'  => $totalCanceled,
        'rate_value'       => $cancelRate,   // The percentage value to display (e.g., 20)
        'rate_status'      => $status,      // The text status (e.g., 'Moderate')
        'status_color'     => $color,       // The color key for CSS (e.g., 'amber')
        'message'          => $message      // The explanatory message
    ]);

} catch (Exception $e) {
    jsonResponse(false, [
        'message' => 'Server error',
        'error' => $e->getMessage()
    ]);
}
?>