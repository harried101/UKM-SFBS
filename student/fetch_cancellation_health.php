<?php
// ==========================================
// FETCH CANCELLATION HEALTH (WEEKLY)
// ==========================================

ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once '../includes/db_connect.php';
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
    // ---------- Get User ----------
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

    // ---------- Calculations ----------
    // Cancellation Rate (LOW is good)
    $cancelRate = ($totalBookings > 0)
        ? round(($totalCanceled / $totalBookings) * 100)
        : 0;

    // Health Score (HIGH is good)
    $healthScore = 100 - $cancelRate;

    // ---------- Health Status ----------
    $status = 'Good';
    $color = 'green';
    $message = 'Your cancellation health is excellent.';

    if ($totalBookings >= 3) {
        if ($healthScore < 67) {              // >33% canceled
            $status = 'Risk';
            $color = 'red';
            $message = 'Health critical: High cancellation rate. Bookings may be restricted.';
        } elseif ($healthScore < 85) {         // >15% canceled
            $status = 'Fair';
            $color = 'amber';
            $message = 'Your cancellation rate is increasing. Avoid frequent cancellations.';
        }
    } else {
        $message = 'Not enough data yet. Your account is in good standing.';
    }

    // ---------- Response ----------
    jsonResponse(true, [
        'total_weekly'     => $totalBookings,
        'canceled_weekly'  => $totalCanceled,
        'cancel_rate'      => $cancelRate,    // bad metric
        'health_score'     => $healthScore,   // good metric
        'health_status'    => $status,
        'status_color'     => $color,
        'message'          => $message
    ]);

} catch (Exception $e) {
    jsonResponse(false, [
        'message' => 'Server error',
        'error' => $e->getMessage()
    ]);
}
?>
