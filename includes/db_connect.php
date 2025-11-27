<?php
// DB Configuration
// NOTE: Ensure these credentials match your XAMPP/MySQL setup exactly.
$host = 'localhost';
$user = 'root'; // Default XAMPP/WAMP user
$pass = ''; // Default XAMPP/WAMP password (often blank)
$dbname = 'ukm-sfbs'; 

// Establish the connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Note: Connection error handling is performed in the calling script (e.g., login_processor.php)
// to ensure a clean JSON error response is sent back to the frontend.
?>