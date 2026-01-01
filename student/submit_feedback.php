<?php
session_start();
require_once '../includes/db_connect.php'; // Assumed path to your DB connection

// 1. Basic Security & Authentication Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'Student') {
    // Redirect to login page if session is invalid
    header("Location: ../index.php");
    exit();
}

// 2. Input Validation and Sanitization
$bookingId = $_POST['booking_id'] ?? null;
$rating = $_POST['rating'] ?? 0;
$comment = $_POST['comment'] ?? '';

// Check required fields (Booking ID must be present and Rating must be 1-5)
if (empty($bookingId) || !is_numeric($bookingId) || $rating == 0) {
    // Redirect back to dashboard with an error parameter
    header("Location: dashboard.php?feedback_status=error&message=Missing required rating or booking ID.");
    exit();
}

// Ensure rating is within 1-5
$rating = (int)$rating;
if ($rating < 1 || $rating > 5) {
    header("Location: dashboard.php?feedback_status=error&message=Invalid rating value.");
    exit();
}

// 3. Get UserID (numeric) and FacilityID from the booking record
$userId = $_SESSION['user_id'] ?? ''; // UserIdentifier (like matric ID)
$numericUserId = 0;
$facilityId = '';
$canSubmit = false; 

try {
    // We join 'users' and 'bookings' to get the numeric UserID (FK), FacilityID (FK), and verify ownership
    $sql_fetch = "
        SELECT 
            u.UserID, 
            b.FacilityID
        FROM users u
        JOIN bookings b ON u.UserID = b.UserID
        WHERE u.UserIdentifier = ? AND b.BookingID = ?
    ";
    
    $stmt_fetch = $conn->prepare($sql_fetch);
    $stmt_fetch->bind_param("si", $userId, $bookingId);
    $stmt_fetch->execute();
    $res_fetch = $stmt_fetch->get_result();

    if ($row_fetch = $res_fetch->fetch_assoc()) {
        $numericUserId = (int)$row_fetch['UserID'];
        $facilityId = $row_fetch['FacilityID'];
        $canSubmit = true;
    }
    $stmt_fetch->close();

    if (!$canSubmit) {
        throw new Exception("Booking verification failed or record not found for this user.");
    }
    
    // 4. Submission Logic (Insert into feedback table)
    
    // Schema: FeedbackID, UserID, FacilityID, BookingID, Rating, Comment, SubmittedAt (default NOW())
    $sql_insert = "
        INSERT INTO feedback (UserID, FacilityID, BookingID, Rating, Comment)
        VALUES (?, ?, ?, ?, ?)
    ";
    $stmt_insert = $conn->prepare($sql_insert);
    
    if ($stmt_insert === false) {
        throw new Exception("Database prepare error: " . $conn->error);
    }
    
    // Parameters: i (int), s (string), i (int), i (int), s (string)
    $stmt_insert->bind_param("isiis", $numericUserId, $facilityId, $bookingId, $rating, $comment);
    $stmt_insert->execute();
    $stmt_insert->close();


    // 5. Update the Booking Status to 'Completed'
    $sql_update_booking = "
        UPDATE bookings 
        SET Status = 'Completed' 
        WHERE BookingID = ? 
          AND UserID = ? 
          AND Status IN ('Approved', 'Confirmed')
    ";
    $stmt_update = $conn->prepare($sql_update_booking);
    
    if ($stmt_update === false) {
        throw new Exception("Database prepare error (Booking Status Update): " . $conn->error);
    }
    
    // Parameters: i (int), i (int)
    $stmt_update->bind_param("ii", $bookingId, $numericUserId);
    $stmt_update->execute();
    $stmt_update->close();
    
    
    // 6. Success Redirection
    header("Location: dashboard.php?feedback_status=success");
    exit();

} catch (Exception $e) {
    // 7. Error Handling
    error_log("Feedback submission failed for Booking ID: $bookingId. Error: " . $e->getMessage());
    // Redirect with a friendly error message
    header("Location: dashboard.php?feedback_status=error&message=" . urlencode("Submission failed."));
    exit();
}

$conn->close();
?>
