<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'Student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['booking_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing booking ID']);
    exit();
}

$booking_id = intval($_POST['booking_id']);
$userIdentifier = $_SESSION['user_id'];

// Get numeric UserID
$stmt = $conn->prepare("SELECT UserID FROM users WHERE UserIdentifier = ?");
$stmt->bind_param("s", $userIdentifier);
$stmt->execute();
$res = $stmt->get_result();

if (!$row = $res->fetch_assoc()) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

$userID = $row['UserID'];
$stmt->close();

// DELETE booking
$stmt = $conn->prepare("DELETE FROM bookings WHERE BookingID = ? AND UserID = ?");
$stmt->bind_param("ii", $booking_id, $userID);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Delete failed']);
}

$stmt->close();
$conn->close();
