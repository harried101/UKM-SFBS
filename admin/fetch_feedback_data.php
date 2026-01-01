<?php
// 1. SILENCE ERRORS & BUFFER OUTPUT
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
date_default_timezone_set('Asia/Kuala_Lumpur');

// Helper for JSON response
function jsonResponse($success, $data = [], $message = '') {
    if (ob_get_length()) ob_clean(); 
    echo json_encode([
        'success' => $success, 
        'data' => $data, 
        'message' => $message
    ]);
    exit;
}

session_start();
require_once '../includes/db_connect.php';

// 2. AUTH CHECK
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'Admin') {
    jsonResponse(false, [], 'Access Denied');
}

try {
    // 3. QUERY DATABASE
    // We use LEFT JOIN so that even if a user or facility is deleted, the feedback remains visible.
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
        LEFT JOIN users u ON fb.UserID = u.UserID
        LEFT JOIN facilities f ON fb.FacilityID = f.FacilityID
        ORDER BY fb.SubmittedAt DESC
    ";

    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Database Error: " . $conn->error);
    }
    
    $feedbacks = [];
    while ($row = $result->fetch_assoc()) {
        
        // Format Date
        $formattedDate = $row['SubmittedAt'];
        if ($formattedDate && $formattedDate !== '0000-00-00 00:00:00') {
            $formattedDate = date('d M Y, h:i A', strtotime($formattedDate));
        }

        // Format Name
        $studentName = trim(($row['FirstName'] ?? '') . ' ' . ($row['LastName'] ?? ''));
        if (empty($studentName)) {
            $studentName = 'Unknown Student';
        }
        
        // Handle Nulls
        $userIdentifier = $row['UserIdentifier'] ?? '-';
        $facilityName = $row['FacilityName'] ?? 'Unknown Facility (Deleted)';
        $comment = $row['Comment'] ?? '';

        // Add to array
        $feedbacks[] = [
            'FeedbackID'     => $row['FeedbackID'],
            'Rating'         => (int)$row['Rating'],
            'Comment'        => htmlspecialchars($comment),
            'FormattedDate'  => $formattedDate,
            'StudentName'    => htmlspecialchars($studentName),
            'UserIdentifier' => htmlspecialchars($userIdentifier),
            'FacilityName'   => htmlspecialchars($facilityName)
        ];
    }

    // 4. RETURN DATA
    jsonResponse(true, $feedbacks, 'Loaded ' . count($feedbacks) . ' records');

} catch (Exception $e) {
    jsonResponse(false, [], 'Error: ' . $e->getMessage());
}
?>