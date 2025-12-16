<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    die("Access Denied");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['booking_id'])) {
    $action = $_POST['action'];
    $bookingID = intval($_POST['booking_id']);
    $adminNotes = trim($_POST['admin_notes'] ?? '');
    $adminID = $_SESSION['user_id'];

    $status = '';
    $notificationMessage = '';

    if ($action === 'approve') {
        $status = 'Confirmed';
        $notificationMessage = "Good news, Your Booking has been approved. Enjoy!!";
        if (!empty($adminNotes)) {
            $notificationMessage .= "\n\nNote: " . $adminNotes;
        }
    } elseif ($action === 'reject') {
        $status = 'Canceled'; // Or 'Rejected'
        if (empty($adminNotes)) {
            header("Location: bookinglist.php?err=Reason required for rejection");
            exit();
        }
        $notificationMessage = "Your booking has been rejected.\n\nReason: " . $adminNotes;
    } elseif ($action === 'cancel') {
        $status = 'Canceled';
        $notificationMessage = "Your booking has been canceled by admin.";
        if (!empty($adminNotes)) {
            $notificationMessage .= "\n\nReason: " . $adminNotes;
        }
    } else {
        die("Invalid Action");
    }

    if ($status) {
        // 1. Update Booking (Without AdminRemarks column)
        $stmt = $conn->prepare("UPDATE bookings SET Status = ?, UpdatedAt = NOW() WHERE BookingID = ?");
        $stmt->bind_param("si", $status, $bookingID);
        
        if ($stmt->execute()) {
            // 2. Get UserID for Notification
            $userQuery = $conn->query("SELECT UserID FROM bookings WHERE BookingID = $bookingID");
            $userRow = $userQuery->fetch_assoc();
            
            if ($userRow) {
                $userID = $userRow['UserID'];
                
                // 3. Insert Notification
                $notifStmt = $conn->prepare("INSERT INTO notifications (UserID, Message, IsRead, CreatedAt) VALUES (?, ?, 0, NOW())");
                $notifStmt->bind_param("is", $userID, $notificationMessage);
                $notifStmt->execute();
                $notifStmt->close();
            }

            header("Location: bookinglist.php?msg=Booking updated successfully");
        } else {
            header("Location: bookinglist.php?err=Failed to update booking");
        }
        $stmt->close();
    }
} else {
    // Fallback for GET requests (legacy support or direct link)
    if (isset($_GET['action']) && isset($_GET['id'])) {
         // Redirect to modal view instead of processing directly, 
         // as we now require POST for notes. 
         // For now, just redirect back to list.
         header("Location: bookinglist.php");
    } else {
        header("Location: bookinglist.php");
    }
}
?>
