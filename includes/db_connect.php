<?php
$host = 'localhost';
$user = 'root';             // default for XAMPP
$pass = '';                 // default is empty
$dbname = 'ukm-sfbs';   // database name

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Uncomment this line to test connection:
// echo "Database connected successfully!";
?>
