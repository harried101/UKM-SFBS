<?php
// DB Configuration
// NOTE: Ensure these credentials match your XAMPP/MySQL setup exactly.
$host = 'localhost';
$user = 'root'; // Default XAMPP/WAMP user
$pass = ''; // Default XAMPP/WAMP password (often blank)
$dbname = 'ukm-sfbs'; 

// Establish the connection
// Establish the connection
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    // Log error and allow calling script to handle response
    error_log("DB Connection Error: " . $e->getMessage());
    // We don't exit here to allow login_processor.php to return JSON
}

// Note: Connection error handling is performed in the calling script (e.g., login_processor.php)
// to ensure a clean JSON error response is sent back to the frontend.
?>