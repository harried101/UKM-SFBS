<?php
session_start();

require_once '../includes/db_connect.php'; 

header('Content-Type: application/json');

if ($conn->connect_error) {
    if ($_SERVER["REQUEST_METHOD"] === "GET" || strpos($_SERVER['REQUEST_URI'], '?') !== false) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    } else {
        die("Database connection failed.");
    }
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    header('Content-Type: text/html');

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Student') {
        die("Access denied. Please log in as a Student.");
    }
    
    $userIdentifier = $_SESSION['user_id'] ?? null; 
    $facilityID = $_POST['facility_id'] ?? null;
    $startTimeStr = $_POST['start_time'] ?? null; 
    $endTimeStr = $_POST['end_time'] ?? null; 
    $status = 'Pending';
    $userID = null; 

    if (!$userIdentifier || !$facilityID || !$startTimeStr || !$endTimeStr) {
        $missing_field = 'booking data';
        if (!$userIdentifier) $missing_field = 'User Identifier (Session)';
        
        echo "<script>alert('Error: Required data missing: {$missing_field}.'); window.parent.closeCalendar();</script>";
        $conn->close();
        exit;
    }

    $sql_lookup = "SELECT UserID FROM users WHERE UserIdentifier = ?";
    if ($stmt_lookup = $conn->prepare($sql_lookup)) {
        $stmt_lookup->bind_param("s", $userIdentifier);
        $stmt_lookup->execute();
        $result_lookup = $stmt_lookup->get_result();
        
        if ($row_lookup = $result_lookup->fetch_assoc()) {
            $userID = $row_lookup['UserID']; 
        }
        $stmt_lookup->close();
    } else {
        $error_message = "Booking failed! Lookup SQL error: " . $conn->error;
        echo "<script>alert('{$error_message}'); window.parent.closeCalendar();</script>";
        $conn->close();
        exit;
    }

    if (!$userID) {
        $error_message = "Booking failed! User Identifier ('{$userIdentifier}') not found in users table. Foreign key constraint will fail.";
        echo "<script>alert('{$error_message}'); window.parent.closeCalendar();</script>";
        $conn->close();
        exit;
    }

    $sql = "INSERT INTO bookings (UserID, FacilityID, StartTime, EndTime, Status) 
            VALUES (?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("issss", $userID, $facilityID, $startTimeStr, $endTimeStr, $status);

        if ($stmt->execute()) {
            $new_booking_id = $stmt->insert_id;
            
            $display_booking_id = str_pad($new_booking_id, 1, '0', STR_PAD_LEFT); 

            $message = 'Booking for ' . $userIdentifier . ' successful! Your Booking ID is "' . $display_booking_id . '".';

            echo "<script>
                if (window.parent) {
                    alert('{$message}');
                    window.parent.closeCalendar(); 
                    window.parent.location.reload(); 
                }
            </script>";
            
        } else {
            $error_message = "Booking failed! Database error: " . $stmt->error;
            if (strpos($stmt->error, 'foreign key constraint fails') !== false) {
                 $error_message .= " (Foreign Key Error: UserID ({$userID}) lookup failed.)";
            }
            echo "<script>alert('{$error_message}'); window.parent.closeCalendar();</script>";
        }

        $stmt->close();
    } else {
        $error_message = "Booking failed! Failed to prepare SQL statement: " . $conn->error;
        echo "<script>alert('{$error_message}'); window.parent.closeCalendar();</script>";
    }

    $conn->close();
    exit;
}

if (isset($_GET['get_slots']) && isset($_GET['date']) && isset($_GET['facility_id'])) {
    
    $date = $_GET['date'];
    $facilityID = $_GET['facility_id'];

    $sql_slots = "SELECT TIME_FORMAT(StartTime, '%H:%i:%s') as booked_time 
                  FROM bookings 
                  WHERE FacilityID = ? 
                  AND DATE(StartTime) = ? 
                  AND (Status = 'Pending' OR Status = 'Confirmed')";

    $booked_slots = [];
    if ($stmt_s = $conn->prepare($sql_slots)) {
        $stmt_s->bind_param("ss", $facilityID, $date);
        $stmt_s->execute();
        $result_s = $stmt_s->get_result();
        
        while ($row = $result_s->fetch_assoc()) {
            $booked_slots[] = $row['booked_time'];
        }
        $stmt_s->close();

        echo json_encode(['success' => true, 'booked_slots' => $booked_slots]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare slot query.']);
    }

    $conn->close();
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request to fetch slots. Check URL parameters.']);
exit;
?>