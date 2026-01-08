<?php
// signup_temp.php
// TEMPORARY FILE: Use this to create initial users. Delete after use.

require_once 'includes/db_connect.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim($_POST['userIdentifier']);
    $email = trim($_POST['email']);
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $pass = $_POST['password'];

    if (empty($id) || empty($email) || empty($pass)) {
        $message = '<div class="alert alert-danger">Please fill in all required fields.</div>';
    } else {
        // Determine Role
        $firstLetter = strtoupper(substr($id, 0, 1));
        $role = '';
        if ($firstLetter === 'A') {
            $role = 'Student';
        } elseif ($firstLetter === 'K') {
            $role = 'Admin';
        } else {
            $message = '<div class="alert alert-warning">Unknown ID format. Must start with A (Student) or K (Admin).</div>';
        }

        if ($role) {
            // Hash Password
            $hash = password_hash($pass, PASSWORD_DEFAULT);

            // Insert
            $sql = "INSERT INTO users (UserIdentifier, Email, PasswordHash, FirstName, LastName, Role) VALUES (?, ?, ?, ?, ?, ?)";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ssssss", $id, $email, $hash, $firstName, $lastName, $role);
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success">User <strong>' . htmlspecialchars($id) . '</strong> (' . $role . ') created successfully!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
                }
                $stmt->close();
            } else {
                $message = '<div class="alert alert-danger">Prepare Error: ' . $conn->error . '</div>';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Temp Sign Up</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .card { width: 400px; padding: 20px; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="card">
    <h3 class="text-center mb-4">Create User</h3>
    
    <?php echo $message; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">User ID (e.g., A204024, K204024) *</label>
            <input type="text" name="userIdentifier" class="form-control" required placeholder="A... or K...">
        </div>
        <div class="mb-3">
            <label class="form-label">Email *</label>
            <input type="email" name="email" class="form-control" required placeholder="name@example.com">
        </div>
        <div class="mb-3">
            <label class="form-label">First Name</label>
            <input type="text" name="firstName" class="form-control" placeholder="First Name">
        </div>
        <div class="mb-3">
            <label class="form-label">Last Name</label>
            <input type="text" name="lastName" class="form-control" placeholder="Last Name">
        </div>
        <div class="mb-3">
            <label class="form-label">Password *</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Create User</button>
    </form>
    <div class="text-center mt-3">
        <a href="index.php">Go to Login</a>
    </div>
</div>

</body>
</html>
