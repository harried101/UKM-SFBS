<?php
// 1. SILENCE ERRORS & BUFFER OUTPUT
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
date_default_timezone_set('Asia/Kuala_Lumpur');

// Helper to sanitize data for JSON
function utf8ize($d) {
    if (is_array($d)) {
        foreach ($d as $k => $v) {
            $d[$k] = utf8ize($v);
        }
    } else if (is_string($d)) {
        return mb_convert_encoding($d, 'UTF-8', 'UTF-8');
    }
    return $d;
}

// Helper function defined EARLY to handle errors
function jsonResponse($success, $data = [], $message = '') {
    // Clear buffer of any warnings/notices/whitespace
    if (ob_get_length()) ob_clean(); 
    
    $response = [
        'success' => $success, 
        'data' => utf8ize($data), // Ensure UTF-8
        'message' => $message
    ];

    $json = json_encode($response);
    
    if ($json === false) {
        // JSON Encode failed (usually special chars)
        echo json_encode(['success' => false, 'data' => [], 'message' => 'JSON Encode Error: ' . json_last_error_msg()]);
    } else {
        echo $json;
    }
    exit;
}

// Catch Fatal Errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR)) {
        @ob_clean();
        echo json_encode(['success' => false, 'data' => [], 'message' => 'Fatal Error: ' . $error['message']]);
    }
});

session_start();

// 2. INCLUDE DB
$db_path = '../includes/db_connect.php';
if (!file_exists($db_path)) {
    jsonResponse(false, [], 'Database connection file not found.');
}
require_once $db_path;

if (!isset($conn) || $conn->connect_error) {
    jsonResponse(false, [], 'Database connection failed.');
}

// 3. AUTHENTICATION CHECK
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'Admin') {
    jsonResponse(false, [], 'Access Denied');
}

try {
    // 4. QUERY DATABASE
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
        throw new Exception("Database Query Failed: " . $conn->error);
    }
    
    $feedbacks = [];
    while ($row = $result->fetch_assoc()) {
        
        // 5. DATA FORMATTING
        
        // Format Date
        $dateStr = $row['SubmittedAt'];
        $formattedDate = '-';
        if ($dateStr && $dateStr !== '0000-00-00 00:00:00') {
            try {
                $dt = new DateTime($dateStr);
                $formattedDate = $dt->format('Y-m-d'); 
            } catch (Exception $e) {
                $formattedDate = $dateStr; 
            }
        }

        // Format Student Name
        $fname = $row['FirstName'] ?? '';
        $lname = $row['LastName'] ?? '';
        $studentName = trim("$fname $lname");
        
        if (empty($studentName)) {
            $studentName = 'Unknown Student';
        }
        
        // Ensure values aren't null
        $facilityName = $row['FacilityName'] ?? 'Unknown Facility';
        $comment = $row['Comment'] ?? '-';
        $userIdentifier = $row['UserIdentifier'] ?? '-';

        // 6. BUILD ROW OBJECT
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

    // 7. RETURN SUCCESS
    jsonResponse(true, $feedbacks, 'Data fetched successfully');

} catch (Exception $e) {
    // 8. HANDLE ERRORS GRACEFULLY
    jsonResponse(false, [], 'Server Error: ' . $e->getMessage());
}
?>