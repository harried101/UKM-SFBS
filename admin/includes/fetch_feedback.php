<?php
// 1. Prevent unwanted output (HTML/Whitespace)
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// Helper to return clean JSON
function jsonResponse($success, $data = [], $message = '') {
    ob_clean(); // Discard any prior output/warnings
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

// 2. Auth Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'Admin') {
    jsonResponse(false, [], 'Access Denied');
}

try {
    // 3. Database Query
    // Joins feedback with users and facilities to get names instead of IDs
    // Uses 'SubmittedAt' as per your database schema
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
    
    $feedbacks = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Format data for frontend
            $row['FormattedDate'] = date('d M Y, h:i A', strtotime($row['SubmittedAt']));
            $row['StudentName'] = trim(($row['FirstName'] ?? '') . ' ' . ($row['LastName'] ?? ''));
            
            // Fallback if name is empty
            if (empty($row['StudentName'])) {
                $row['StudentName'] = $row['UserIdentifier'];
            }
            
            // Clean nulls
            if (empty($row['FacilityName'])) $row['FacilityName'] = 'Unknown Facility';
            if (empty($row['Comment'])) $row['Comment'] = '-';

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