<?php
require_once 'includes/db_connect.php';

// Check if column exists
$check = $conn->query("SHOW COLUMNS FROM feedback LIKE 'Category'");
if($check->num_rows == 0) {
    // Add column
    $sql = "ALTER TABLE feedback ADD COLUMN Category VARCHAR(100) NULL AFTER Comment";
    if($conn->query($sql)) {
        echo "Column Category added successfully.";
    } else {
        echo "Error adding column: " . $conn->error;
    }
} else {
    echo "Column Category already exists.";
}
?>
