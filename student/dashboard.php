<?php
session_start();

// Redirect if not logged in or not a student
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Student') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/db_connect.php';

// Timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

// -----------------------------
// 1. FETCH STUDENT DETAILS
// -----------------------------
$studentIdentifier = $_SESSION['user_id'] ?? '';
$studentName = 'Student';
$studentID = '';
$db_numeric_id = 0;

if ($studentIdentifier) {
    $stmtStudent = $conn->prepare("
        SELECT UserID, FirstName, LastName, UserIdentifier 
        FROM users 
        WHERE UserIdentifier = ?
    ");
    $stmtStudent->bind_param("s", $studentIdentifier);
    $stmtStudent->execute();
    $resStudent = $stmtStudent->get_result();

    if ($rowStudent = $resStudent->fetch_assoc()) {
        $studentName   = $rowStudent['FirstName'] . ' ' . $rowStudent['LastName'];
        $studentID     = $rowStudent['UserIdentifier'];
        $db_numeric_id = (int)$rowStudent['UserID'];
    }
    $stmtStudent->close();
}

// -----------------------------
// 2. FETCH BOOKINGS
// -----------------------------
$upcoming = [];
$history  = [];
$now = new DateTime(); // current KL time

if ($db_numeric_id > 0) {

    $sql = "
        SELECT 
            b.BookingID,
            b.StartTime,
            b.EndTime,
            b.Status,
            f.Name AS FacilityName,
            f.Type,
            f.Location
        FROM bookings b
        JOIN facilities f ON b.FacilityID = f.FacilityID
        WHERE b.UserID = ?
        ORDER BY b.StartTime DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $db_numeric_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        // StartTime is FULL datetime
        $start = new DateTime($row['StartTime']);

        // EndTime is TIME only → combine with StartTime date
        $end = new DateTime($row['StartTime']);
        $end->setTime(
            (int)date('H', strtotime($row['EndTime'])),
            (int)date('i', strtotime($row['EndTime'])),
            (int)date('s', strtotime($row['EndTime']))
        );

        // Classification
        if (
            $end > $now &&
            in_array($row['Status'], ['Pending', 'Approved', 'Confirmed'])
        ) {
            $upcoming[] = $row;
        } else {
            $history[] = $row;
        }
    }

    $stmt->close();
}

if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}
?>
