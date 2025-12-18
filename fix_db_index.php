<?php
require_once 'includes/db_connect.php';

try {
    $conn->query("ALTER TABLE bookings DROP INDEX unique_booking_slot");
    echo "Index 'unique_booking_slot' dropped successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
