<?php
require_once __DIR__ . '/includes/db_connect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - USM Sports Facilities Booking System</title>
    <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>

    <div class="login-container">
        <h1>Login</h1>

        <form action="login.php" method="post">
            <label for="userIdentifier">Matric / Staff Number:</label>
            <input type="text" id="userIdentifier" name="userIdentifier" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Log In</button>
        </form>
    </div>

</body>
</html>

