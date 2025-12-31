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
    // We select specific columns to avoid ambiguity
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
        throw new Exception("Query Failed: " . $conn->error);
    }
    
    $feedbacks = [];
    while ($row = $result->fetch_assoc()) {
        // Safe Date Formatting
        $dateStr = $row['SubmittedAt'];
        $formattedDate = '-';
        if ($dateStr && $dateStr !== '0000-00-00 00:00:00') {
            try {
                $dt = new DateTime($dateStr);
                $formattedDate = $dt->format('d M Y, h:i A');
            } catch (Exception $e) {
                $formattedDate = $dateStr; // Fallback
            }
        }

        // Safe Name Formatting
        $fname = $row['FirstName'] ?? '';
        $lname = $row['LastName'] ?? '';
        $studentName = trim("$fname $lname");
        
        // Fallback if name is empty (e.g. deleted user)
        if (empty($studentName)) {
            $studentName = $row['UserIdentifier'] ?? 'Unknown User';
        }
        
        // Safe Facility Name
        $facilityName = $row['FacilityName'] ?? 'Unknown Facility';
        
        // Safe Comment
        $comment = $row['Comment'] ?? '-';

        $feedbacks[] = [
            'FeedbackID' => $row['FeedbackID'],
            'Rating' => (int)$row['Rating'],
            'Comment' => htmlspecialchars($comment),
            'FormattedDate' => $formattedDate,
            'StudentName' => htmlspecialchars($studentName),
            'UserIdentifier' => htmlspecialchars($row['UserIdentifier'] ?? ''),
            'FacilityName' => htmlspecialchars($facilityName)
        ];
    }

    jsonResponse(true, $feedbacks, 'Data fetched successfully');

} catch (Exception $e) {
    jsonResponse(false, [], 'Server Error: ' . $e->getMessage());
}
?>