<?php
// 1. SILENCE ERRORS & BUFFER OUTPUT
// This prevents PHP warnings/notices from corrupting the JSON response
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Helper function defined EARLY to handle errors even before DB loads
function jsonResponse($success, $data = [], $message = '') {
    // Clear buffer of any warnings/notices/whitespace
    if (ob_get_length()) ob_clean(); 
    echo json_encode([
        'success' => $success, 
        'data' => $data, // DataTables expects this key
        'message' => $message
    ]);
    exit;
}

// Catch Fatal Errors (e.g. if db_connect fails severely)
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR)) {
        // We can't use jsonResponse here easily if headers sent, but we try
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
    // We explicitly select columns to avoid ambiguity and ensure we get UserIdentifier
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
                $formattedDate = $dt->format('Y-m-d'); // Match the format shown in your UI example
            } catch (Exception $e) {
                $formattedDate = $dateStr; 
            }
        }

        // Format Student Name
        $fname = $row['FirstName'] ?? '';
        $lname = $row['LastName'] ?? '';
        $studentName = trim("$fname $lname");
        
        // Fallback if name is missing (e.g. user deleted)
        if (empty($studentName)) {
            $studentName = 'Unknown Student';
        }
        
        // Ensure Facility Name exists
        $facilityName = $row['FacilityName'] ?? 'Unknown Facility';
        
        // Ensure Comment isn't null
        $comment = $row['Comment'] ?? '-';
        
        // User Identifier (Matric No)
        $userIdentifier = $row['UserIdentifier'] ?? '-';

        // 6. BUILD ROW OBJECT
        // Keys must match what the DataTables 'columns' config expects
        $feedbacks[] = [
            'FeedbackID'     => $row['FeedbackID'],
            'Rating'         => (int)$row['Rating'],
            'Comment'        => htmlspecialchars($comment),
            'FormattedDate'  => $formattedDate,
            'StudentName'    => htmlspecialchars($studentName),
            'UserIdentifier' => htmlspecialchars($userIdentifier), // Crucial for the UI render function
            'FacilityName'   => htmlspecialchars($facilityName)
        ];
    }

    // 7. RETURN SUCCESS
    jsonResponse(true, $feedbacks, 'Data fetched successfully');

} catch (Exception $e) {
    // 8. HANDLE ERRORS GRACEFULLY
    // Return empty data array so DataTables doesn't crash, just shows empty table
    jsonResponse(false, [], 'Server Error: ' . $e->getMessage());
}
?>