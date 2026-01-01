<?php
// 1. START BUFFERING & ERROR HANDLING
ob_start(); // Capture all output
ini_set('display_errors', 0); // Hide errors from output
error_reporting(E_ALL); // Report all errors internally

header('Content-Type: application/json');
date_default_timezone_set('Asia/Kuala_Lumpur');

// 2. INCLUDE DATABASE
$db_path = '../includes/db_connect.php';

if (!file_exists($db_path)) {
    ob_clean();
    echo json_encode(['success' => false, 'data' => [], 'message' => 'DB file missing']);
    exit;
}

require_once $db_path;

// CRITICAL: Clean the buffer immediately after include to remove any whitespace from db_connect.php
if (ob_get_length()) ob_clean(); 

if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['success' => false, 'data' => [], 'message' => 'DB connection failed']);
    exit;
}

// 3. AUTH CHECK
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'data' => [], 'message' => 'Access Denied']);
    exit;
}

try {
    // 4. QUERY
    // Matching your provided table structure: FeedbackID, UserID, FacilityID, BookingID, Rating, Comment, SubmittedAt
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
        throw new Exception("SQL Error: " . $conn->error);
    }
    
    $feedbacks = [];
    while ($row = $result->fetch_assoc()) {
        
        // Date Formatting
        $dateStr = $row['SubmittedAt'];
        $formattedDate = '-';
        if (!empty($dateStr) && $dateStr !== '0000-00-00 00:00:00') {
            $dt = date_create($dateStr);
            if ($dt) {
                $formattedDate = date_format($dt, 'd M Y, h:i A');
            }
        }

        // Name Formatting
        $studentName = trim(($row['FirstName'] ?? '') . ' ' . ($row['LastName'] ?? ''));
        if (empty($studentName)) {
            $studentName = $row['UserIdentifier'] ?? 'Unknown ID';
        }
        
        // Null checks
        $facilityName = $row['FacilityName'] ?? 'Unknown Facility';
        $comment = $row['Comment'] ?? '-';
        $userIdLabel = $row['UserIdentifier'] ?? '';

        $feedbacks[] = [
            'FeedbackID'     => $row['FeedbackID'],
            'Rating'         => (int)$row['Rating'],
            'Comment'        => htmlspecialchars($comment),
            'FormattedDate'  => $formattedDate,
            'StudentName'    => htmlspecialchars($studentName),
            'UserIdentifier' => htmlspecialchars($userIdLabel),
            'FacilityName'   => htmlspecialchars($facilityName)
        ];
    }

    // 5. OUTPUT JSON
    // Use flags to handle invalid UTF-8 characters gracefully
    echo json_encode(
        ['success' => true, 'data' => $feedbacks, 'message' => 'Fetched'], 
        JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR
    );

} catch (Exception $e) {
    // 6. CATCH ERRORS
    ob_clean(); // Clean buffer before sending error JSON
    echo json_encode(['success' => false, 'data' => [], 'message' => 'Server Error: ' . $e->getMessage()]);
}

// Final flush/exit
exit;
?>