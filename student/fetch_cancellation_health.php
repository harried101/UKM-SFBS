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
    // We look at bookings scheduled for the current week
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
    
    // Calculate Rates
    // cancelRate: Percentage of bookings cancelled (Low is good)
    $cancelRate = ($total > 0) ? round(($canceled / $total) * 100) : 0;
    
    // healthScore: 100% means Perfect (0 cancellations). Goes down as you cancel.
    $healthScore = 100 - $cancelRate;
    
    // 4. Determine Health Status
    // We base status on the health score (High is good)
    // < 67% Health means > 33% Cancellation Rate (Risk)
    $status = 'Good';
    $color = 'green';
    $message = 'Your cancellation health is excellent.';

    if ($total >= 3) {
        if ($healthScore < 67) { // Equivalent to > 33% cancellation
            $status = 'Risk';
            $color = 'red';
            $message = 'Health Critical: High cancellation rate. Bookings blocked.';
        } elseif ($healthScore < 85) { // Equivalent to > 15% cancellation
            $status = 'Fair';
            $color = 'yellow';
            $message = 'Your health score is dropping. Avoid frequent cancellations.';
        }
    } else {
        $message = 'Your account is in good standing.';
    }

    // 5. Return Data
    // We send 'healthScore' as 'cancellation_rate' so the frontend displays the high number (100%)
    jsonResponse(true, [
        'total_weekly' => $total,
        'canceled_weekly' => $canceled,
        'cancellation_rate' => $healthScore, // Sending Health Score (100%) for display
        'health_status' => $status,
        'status_color' => $color,
        'message' => $message
    ]);

} catch (Exception $e) {
    jsonResponse(false, ['message' => $e->getMessage()]);
}
?>