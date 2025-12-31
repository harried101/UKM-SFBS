<?php
// 1. Setup & Security
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

function jsonResponse($success, $data = [], $message = '') {
    ob_clean();
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

// Ensure only Admin can access
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'Admin') {
    jsonResponse(false, [], 'Access Denied');
}

try {
    // 2. Fetch Logic
    $sql = "
        SELECT 
            fb.FeedbackID,
            fb.Rating,
            fb.Comment,
            fb.SubmittedAt, 
            u.FirstName,
            u.LastName,
            u.UserIdentifier,
            f.Name AS FacilityName
        FROM feedback fb
        JOIN users u ON fb.UserID = u.UserID
        JOIN facilities f ON fb.FacilityID = f.FacilityID
        ORDER BY fb.SubmittedAt DESC
    ";

    $result = $conn->query($sql);
    
    $feedbacks = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Format date for display
            $row['FormattedDate'] = date('Y-m-d', strtotime($row['SubmittedAt']));
            // Combine names
            $row['StudentName'] = $row['FirstName'] . ' ' . $row['LastName'];
            $feedbacks[] = $row;
        }
        jsonResponse(true, $feedbacks, 'Data fetched successfully');
    } else {
        throw new Exception($conn->error);
    }

} catch (Exception $e) {
    jsonResponse(false, [], 'Server Error: ' . $e->getMessage());
}
?>