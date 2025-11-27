<?php
// --- PHP BACKEND LOGIC ---
// Handles secure login authentication via AJAX POST request.

// Report all errors for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set header for JSON response
header('Content-Type: application/json');

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit();
}

// --- 1. DATABASE CONNECTION ---
// CRITICAL: Path correction for includes folder (assuming it's in 'includes/db_connect.php' or similar)
// If db_connect.php is directly in the root, change this to: require_once 'db_connect.php';
require_once __DIR__ . '/includes/db_connect.php'; 

// Check if the connection variable was successfully created and is valid.
if (!isset($conn) || $conn->connect_error) {
    $error_msg = isset($conn) ? $conn->connect_error : "Connection object not created in db_connect.php.";
    error_log("FATAL DB ERROR: " . $error_msg); 
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: Database is unavailable.']);
    exit();
}

// --- 2. RECEIVE AND VALIDATE INPUT ---
$json_input = file_get_contents('php://input');
$data = json_decode($json_input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("LOGIN DEBUG: Failed to decode JSON input: " . json_last_error_msg() . " Raw input: " . $json_input);
    http_response_code(400); 
    echo json_encode(['status' => 'error', 'message' => 'Invalid data format sent to server.']);
    $conn->close();
    exit();
}

$identifier = trim($data['userIdentifier'] ?? '');
$password = $data['password'] ?? '';

$generic_failure_response = ['status' => 'error', 'message' => 'Invalid ID or password.'];

if (empty($identifier) || empty($password)) {
    http_response_code(400); 
    echo json_encode($generic_failure_response);
    $conn->close();
    exit();
}

// --- 3. DATABASE QUERY (Secure Prepared Statements) ---
// This prevents SQL Injection.
$sql = "SELECT PasswordHash, Role FROM users WHERE UserIdentifier = ?";
$response = $generic_failure_response;

if ($stmt = $conn->prepare($sql)) {
    // Bind 's' for string parameter ($identifier)
    $stmt->bind_param("s", $identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $stored_hash = $user['PasswordHash'];

        // --- 4. PASSWORD HASH VERIFICATION ---
        // Compares plaintext password against stored hash securely.
        if (password_verify($password, $stored_hash)) {
            
            $role = $user['Role'];
            
            // --- START SESSION AND STORE USER DATA ---
            session_start();
            $_SESSION['user_id'] = $identifier; 
            $_SESSION['role'] = $role;
            $_SESSION['logged_in'] = true;

            $response = [
                'status' => 'success',
                'message' => 'Login successful.',
                'role' => $role // Key for role-based redirect
            ];
            
        } 
    } 

    $stmt->close();
} else {
    error_log("FATAL SQL PREPARE ERROR: " . $conn->error); 
    http_response_code(500);
    $response = ['status' => 'error', 'message' => 'Server error during login.'];
}

// --- 5. SEND RESPONSE AND CLEAN UP ---
echo json_encode($response);
$conn->close();
exit();
?>