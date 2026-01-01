<?php
// 1. Setup
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once '../includes/db_connect.php';
date_default_timezone_set('Asia/Kuala_Lumpur');

header('Content-Type: application/json');

function jsonResponse($success, $data) {
    ob_clean();
    echo json_encode(['success' => $success, 'data' => $data]);
    exit;
}

// 2. Auth
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'Student') {
    jsonResponse(false, ['message' => 'Access Denied']);
}

try {
    $studentIdentifier = $_SESSION['user_id'];

    // Get ID
    $uStmt = $conn->prepare("SELECT UserID FROM users WHERE UserIdentifier = ?");
    $uStmt->bind_param("s", $studentIdentifier);
    $uStmt->execute();
    $uid = $uStmt->get_result()->fetch_assoc()['UserID'] ?? 0;
    $uStmt->close();

    if (!$uid) jsonResponse(false, ['message' => 'User not found']);

    // 3. Calculate Weekly Stats
    $weekMode = 1; // Week starts Monday
    $sql = "SELECT 
                COUNT(*) as total_bookings,
                SUM(CASE WHEN Status = 'Canceled' THEN 1 ELSE 0 END) as total_canceled
            FROM bookings 
            WHERE UserID = ? 
            AND YEARWEEK(StartTime, ?) = YEARWEEK(NOW(), ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $uid, $weekMode, $weekMode);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $total = intval($data['total_bookings']);
    $canceled = intval($data['total_canceled']);
    
    // Avoid division by zero
    $rate = ($total > 0) ? round(($canceled / $total) * 100) : 0;
    
    // 4. Determine Health Status (With Threshold Logic)
    $status = 'Good';
    $color = 'green';
    $message = 'Your account is in good standing.';

    // Only flag 'Risk' or 'Fair' if user has made at least 3 bookings to avoid penalizing small sample sizes
    if ($total >= 3) {
        if ($rate >= 33) {
            $status = 'Risk';
            $color = 'red';
            $message = 'High cancellation rate (>33%). Future bookings blocked.';
        } elseif ($rate > 15) {
            $status = 'Fair';
            $color = 'yellow';
            $message = 'Your cancellation rate is rising. Please be careful.';
        }
    } else {
        // If total bookings < 3, keep status Good regardless of rate
        $message = 'Your account is in good standing.';
    }

    // 5. Return Data
    jsonResponse(true, [
        'total_weekly' => $total,
        'canceled_weekly' => $canceled,
        'cancellation_rate' => $rate,
        'health_status' => $status,
        'status_color' => $color,
        'message' => $message
    ]);

} catch (Exception $e) {
    jsonResponse(false, ['message' => $e->getMessage()]);
}
?>