<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
// Absolute session expiration (30 mins)
if (isset($_SESSION['created_at']) && (time() - $_SESSION['created_at']) > 1800) {
    session_unset();
    session_destroy();
    header("Location: ../index.php?expired=1");
    exit();
}

// Idle timeout (server-side backup)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 1800) {
    session_unset();
    session_destroy();
    header("Location: ../index.php?idle=1");
    exit();
}

// Update activity time
$_SESSION['last_activity'] = time();

require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// Security check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Student') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

// Validate booking ID
if (!isset($_POST['booking_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing booking ID'
    ]);
    exit();
}

$booking_id = (int) $_POST['booking_id'];

// Correct ENUM value
$sql = "UPDATE bookings SET Status = 'Canceled' WHERE BookingID = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'SQL prepare error: ' . $conn->error
    ]);
    exit();
}

$stmt->bind_param("i", $booking_id);
$stmt->execute();

// Check result
if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    // Check if the booking exists and what the current status is
    $check = $conn->prepare("SELECT Status FROM bookings WHERE BookingID = ?");
    $check->bind_param("i", $booking_id);
    $check->execute();
    $res = $check->get_result();
    if ($row = $res->fetch_assoc()) {
        echo json_encode([
            'success' => false,
            'message' => 'Booking already Canceled or cannot update. Current status: ' . $row['Status']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Booking not found'
        ]);
    }
    $check->close();
}

$stmt->close();
$conn->close();
