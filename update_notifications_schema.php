<?php
require_once 'includes/db_connect.php';

try {
    $result = $conn->query("SHOW COLUMNS FROM notifications LIKE 'BookingID'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE notifications ADD COLUMN BookingID INT NULL");
        echo "Column BookingID added successfully.";
    } else {
        echo "Column BookingID already exists.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
