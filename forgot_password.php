<?php
// forgot_password.php
require_once 'includes/db_connect.php';

$message = '';
$link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $message = '<div class="alert alert-danger">Please enter your email address.</div>';
    } else {
        // Check if email exists
        $sql = "SELECT UserID FROM users WHERE Email = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows == 1) {
                // Generate Token
                $token = bin2hex(random_bytes(32));
                // Set Expiry (1 hour from now)
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Update User
                $updateSql = "UPDATE users SET ResetToken = ?, ResetTokenExpiry = ? WHERE Email = ?";
                if ($updateStmt = $conn->prepare($updateSql)) {
                    $updateStmt->bind_param("sss", $token, $expiry, $email);
                    if ($updateStmt->execute()) {
                        // SIMULATE EMAIL SENDING
                        $resetLink = "http://localhost/UKM-SFBS/reset_password.php?token=" . $token;
                        $message = '<div class="alert alert-success">A reset link has been sent to your email (Simulated).</div>';
                        $link = '<div class="alert alert-info"><strong>SIMULATION:</strong><br>Click here to reset: <a href="' . $resetLink . '">' . $resetLink . '</a></div>';
                    } else {
                        $message = '<div class="alert alert-danger">Error updating token: ' . $conn->error . '</div>';
                    }
                    $updateStmt->close();
                }
            } else {
                // Security: Don't reveal if email exists or not, but for this assignment we might want to be helpful or standard generic message
                $message = '<div class="alert alert-success">If an account exists with that email, a reset link has been sent.</div>';
            }
            $stmt->close();
        } else {
            $message = '<div class="alert alert-danger">Database error: ' . $conn->error . '</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - UKM SFBS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .card { width: 400px; padding: 20px; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="card">
    <h3 class="text-center mb-4">Forgot Password</h3>
    
    <?php echo $message; ?>
    <?php echo $link; ?>

    <p class="text-muted text-center">Enter your email address and we'll send you a link to reset your password.</p>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" required placeholder="name@example.com">
        </div>
        <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
    </form>
    <div class="text-center mt-3">
        <a href="index.php">Back to Login</a>
    </div>
</div>

</body>
</html>
