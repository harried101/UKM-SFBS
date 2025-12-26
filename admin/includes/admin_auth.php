<?php
session_start();
$timeout_limit = 10; 

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
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/db_connect.php';

// 2. Fetch Admin Details
$adminIdentifier = $_SESSION['user_id'] ?? '';
$adminName = 'Administrator';
$adminID = $adminIdentifier;

if ($adminIdentifier) {
    $stmtAuth = $conn->prepare("SELECT FirstName, LastName, UserIdentifier FROM users WHERE UserIdentifier = ?");
    $stmtAuth->bind_param("s", $adminIdentifier);
    $stmtAuth->execute();
    $resAuth = $stmtAuth->get_result();
    if ($rowAuth = $resAuth->fetch_assoc()) {
        $adminName = $rowAuth['FirstName'] . ' ' . $rowAuth['LastName'];
        $adminID = $rowAuth['UserIdentifier'];
    }
    $stmtAuth->close();
}
?>
