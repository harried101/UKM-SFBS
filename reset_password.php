<?php
// reset_password.php
require_once 'includes/db_connect.php';

$message = '';
$token = $_GET['token'] ?? '';
$validToken = false;

if (empty($token)) {
    $message = '<div class="alert alert-danger">Invalid request. Token missing.</div>';
} else {
    // Validate Token and Expiry
    $sql = "SELECT UserID FROM users WHERE ResetToken = ? AND ResetTokenExpiry > NOW()";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows == 1) {
            $validToken = true;
        } else {
            $message = '<div class="alert alert-danger">Invalid or expired token.</div>';
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $pass = $_POST['password'];
    $confirmPass = $_POST['confirm_password'];

    if (empty($pass) || empty($confirmPass)) {
        $message = '<div class="alert alert-danger">Please fill in all fields.</div>';
    } elseif ($pass !== $confirmPass) {
        $message = '<div class="alert alert-danger">Passwords do not match.</div>';
    } else {
        // Update Password
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        
        $updateSql = "UPDATE users SET PasswordHash = ?, ResetToken = NULL, ResetTokenExpiry = NULL WHERE ResetToken = ?";
        if ($updateStmt = $conn->prepare($updateSql)) {
            $updateStmt->bind_param("ss", $hash, $token);
            if ($updateStmt->execute()) {
                $message = '<div class="alert alert-success">Password reset successfully! <a href="index.php">Login now</a>.</div>';
                $validToken = false; // Hide form
            } else {
                $message = '<div class="alert alert-danger">Error updating password: ' . $conn->error . '</div>';
            }
            $updateStmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - UKM SFBS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .card { width: 400px; padding: 20px; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="card">
    <h3 class="text-center mb-4">Reset Password</h3>
    
    <?php echo $message; ?>

    <?php if ($validToken): ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">New Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Reset Password</button>
    </form>
    <?php endif; ?>
    
    <div class="text-center mt-3">
        <a href="index.php">Back to Login</a>
    </div>
</div>

</body>
</html>
