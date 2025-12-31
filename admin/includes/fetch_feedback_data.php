<?php
// 1. SILENCE ERRORS & BUFFER OUTPUT
// This prevents PHP warnings/notices from corrupting the JSON response
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// Helper function to send JSON and stop execution
function jsonResponse($success, $data = [], $message = '') {
    ob_clean(); // Discard any partial output or warnings
    echo json_encode([
        'success' => $success, 
        'data' => $data, // DataTables expects this key
        'message' => $message
    ]);
    exit;
}

// 2. AUTHENTICATION CHECK
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'Admin') {
    jsonResponse(false, [], 'Access Denied');
}

try {
    // 3. QUERY DATABASE
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
        
        // 4. DATA FORMATTING
        
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

        // 5. BUILD ROW OBJECT
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

    // 6. RETURN SUCCESS
    jsonResponse(true, $feedbacks, 'Data fetched successfully');

} catch (Exception $e) {
    // 7. HANDLE ERRORS GRACEFULLY
    // Return empty data array so DataTables doesn't crash, just shows empty table
    jsonResponse(false, [], 'Server Error: ' . $e->getMessage());
}
?>