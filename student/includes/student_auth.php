<?php
require_once '../includes/config.php';
session_start();
$timeout_limit = SESSION_TIMEOUT_SECONDS;

// 2. Check if the 'last_activity' timestamp exists
if (isset($_SESSION['last_activity'])) {
    $seconds_inactive = time() - $_SESSION['last_activity'];
    
    // 3. If inactive for too long, redirect to logout
    if ($seconds_inactive >= $timeout_limit) {
        header("Location: ../logout.php");
        exit;
    }
}

// 4. Update the timestamp to 'now' because they just loaded the page
$_SESSION['last_activity'] = time();

// 1. Auth Check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Student') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/db_connect.php';

// 2. Fetch Student Details
$studentIdentifier = $_SESSION['user_id'] ?? '';
$studentName = 'Student';
$studentID = '';
$db_numeric_id = 0;

if ($studentIdentifier) {
    $stmtAuth = $conn->prepare("SELECT UserID, FirstName, LastName, UserIdentifier FROM users WHERE UserIdentifier = ?");
    $stmtAuth->bind_param("s", $studentIdentifier);
    $stmtAuth->execute();
    $resAuth = $stmtAuth->get_result();
    if ($rowAuth = $resAuth->fetch_assoc()) {
        $studentName = $rowAuth['FirstName'] . ' ' . $rowAuth['LastName'];
        $studentID = $rowAuth['UserIdentifier'];
        $db_numeric_id = (int)$rowAuth['UserID'];
    }
    $stmtAuth->close();
}
?>
