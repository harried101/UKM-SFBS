<?php
require_once __DIR__ . '/includes/db_connect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - UKM Sports Facilities Booking System</title>
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

    <h1>UKM Sport<br>Facilities Booking<br>System</h1>

    <div class="welcome">Welcome Back!</div>
</div>

        <!-- RIGHT SIDE -->
        <div class="right-panel">
            <div class="login-box">

                <div class="login-title">LOG IN</div>

                <!-- Username -->
                <div class="input-group">
                    <i>👤</i>
                    <input type="text" placeholder="Username">
                </div>

                <!-- Password -->
                <div class="input-group">
                    <i>🔒</i>
                    <input type="password" placeholder="Password">
                    <i style="margin-left:10px;">👁️</i>
                </div>

                <div class="forgot">Forgot Password?</div>

                <button class="login-btn">Log in</button>

              
        </div>

    </div>
</body>
</html>
