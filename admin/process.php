<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'Admin') {
    die("Access Denied");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingId = $_POST['booking_id'];
    $action = $_POST['action'];
    $notes = $_POST['admin_notes'] ?? '';

    $status = ($action === 'approve') ? 'Approved' : 'Cancelled';

    $stmt = $conn->prepare("UPDATE bookings SET Status = ? WHERE BookingID = ?");
    $stmt->bind_param("si", $status, $bookingId);
    
    if ($stmt->execute()) {
        header("Location: manage_bookings.php");
    } else {
        echo "Error: " . $conn->error;
    }
    $stmt->close();
}
?>