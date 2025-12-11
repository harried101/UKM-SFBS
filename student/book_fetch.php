<?php
session_start();
require_once '../includes/db_connect.php';

// 1. GET REQUEST: Fetch Slots (Called via AJAX by book.php)
if (isset($_GET['get_slots'])) {
    header('Content-Type: application/json');
    
    $date = $_GET['date'] ?? '';
    $facilityID = $_GET['facility_id'] ?? '';

    if (!$date || !$facilityID) {
        echo json_encode(['success' => false, 'message' => 'Missing date or ID']);
        exit;
    }

    // Fetch time portions (HH:MM:SS) of booked slots
    // We filter for Confirmed OR Pending to prevent double booking
    // Using TIME() function to extract just the time part for easy JS comparison
    $sql = "SELECT TIME(StartTime) as booked_time 
            FROM bookings 
            WHERE FacilityID = ? 
            AND DATE(StartTime) = ? 
            AND Status IN ('Approved', 'Pending')";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $facilityID, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $booked_slots = [];
    while ($row = $result->fetch_assoc()) {
        $booked_slots[] = $row['booked_time'];
    }

    echo json_encode(['success' => true, 'booked_slots' => $booked_slots]);
    $stmt->close();
    $conn->close();
    exit;
}

// 2. POST REQUEST: Submit Booking (Called by Form Submit)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // Auth Check
    if (!isset($_SESSION['user_id'])) {
        die("<script>alert('Session expired. Please log in.'); window.parent.location.reload();</script>");
    }

    $userIdentifier = $_SESSION['user_id']; // This is usually the Metric Number/Username
    $facilityID = $_POST['facility_id'];
    $startTime = $_POST['start_time'];
    $endTime = $_POST['end_time'];

    // Retrieve User's Numeric ID (Foreign Key)
    // NOTE: Adjust 'UserIdentifier' to match your actual column name (e.g., 'username', 'metric_id')
    $u_stmt = $conn->prepare("SELECT UserID FROM users WHERE UserIdentifier = ?");
    $u_stmt->bind_param("s", $userIdentifier);
    $u_stmt->execute();
    $res = $u_stmt->get_result();
    
    if ($row = $res->fetch_assoc()) {
        $userID = $row['UserID'];
    } else {
        // Fallback: If session user_id IS the numeric ID, use it directly
        // Remove this else block if your session definitely stores a string username
        $userID = $userIdentifier; 
    }
    $u_stmt->close();

    // Final Double Booking Check (Race Condition Prevention)
    $check = $conn->prepare("SELECT BookingID FROM bookings WHERE FacilityID = ? AND StartTime = ? AND Status IN ('Approved', 'Pending')");
    $check->bind_param("ss", $facilityID, $startTime);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        die("<script>alert('Sorry! This slot was just taken by someone else.'); window.location.href='book.php?facility_id=$facilityID';</script>");
    }
    $check->close();

    // Insert Booking
    $status = 'Pending';
    $ins = $conn->prepare("INSERT INTO bookings (UserID, FacilityID, StartTime, EndTime, Status) VALUES (?, ?, ?, ?, ?)");
    $ins->bind_param("issss", $userID, $facilityID, $startTime, $endTime, $status);

    if ($ins->execute()) {
        // Success: Alert and reload parent page (Dashboard)
        echo "<script>
            alert('Booking Submitted Successfully! Status: Pending.');
            window.parent.location.reload(); 
        </script>";
    } else {
        echo "<script>
            alert('Database Error: " . addslashes($conn->error) . "');
            window.parent.closeCalendar();
        </script>";
    }

    $ins->close();
    $conn->close();
    exit;
}
?>