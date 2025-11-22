<?php
// index.php — UKM Sport Facilities Booking System Login UI
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UKM Sport Facilities Booking System</title>

    <style>
        body {
            margin: 0;
            font-family: "Poppins", sans-serif;
            background: url("img/sport-bg.jpg") no-repeat center center/cover;
            height: 100vh;
            display: flex;
            justify-content: space-between;
            padding: 40px;
        }

        /* LEFT SIDE TEXT */
        .left-section {
            color: white;
            max-width: 40%;
            margin-top: 80px;
        }
        .left-section h1 {
            font-size: 50px;
            font-weight: bold;
            line-height: 1.2;
        }
        .left-section h2 {
            margin-top: 40px;
            font-size: 32px;
            font-weight: 600;
        }

        /* LOGIN CONTAINER */
        .login-box {
            width: 400px;
            background: rgba(230, 244, 246, 0.95);
            padding: 50px;
            border-radius: 40px;
            text-align: center;
            margin-right: 40px;
        }

        .login-box h2 {
            font-size: 48px;
            color: #9c0025;
            margin-bottom: 40px;
        }

        .input-field {
            width: 100%;
            padding: 14px;
            margin: 15px 0;
            border: none;
            border-radius: 30px;
            background: white;
            font-size: 16px;
            padding-left: 20px;
        }

        .login-btn {
            width: 100%;
            padding: 16px;
            background: #b2092e;
            border: none;
            color: white;
            font-size: 20px;
            border-radius: 30px;
            margin-top: 20px;
            cursor: pointer;
        }

        .login-btn:hover {
            background: #920826;
        }

        .forgot {
            text-align: right;
            font-size: 14px;
            color: #666;
            margin-top: -10px;
        }

        /* Silhouette Images */
        .silhouettes {
            margin-top: 20px;
            width: 100%;
        }

        /* Logos */
        .logo-row img {
            height: 60px;
            margin-right: 20px;
        }
    </style>
</head>

<body>

    <!-- LEFT SIDE -->
    <div class="left-section">
        <div class="logo-row">
            <img src="img/ukm-logo.png">
            <img src="img/pusat-sukan.png">
        </div>

        <h1>UKM Sport<br>Facilities Booking<br>System</h1>
        <h2>Welcome Back!</h2>
    </div>

    <!-- LOGIN FORM -->
    <div class="login-box">
        <h2>Log in</h2>

        <form action="validate.php" method="POST">

            <input type="text" name="username" class="input-field" placeholder="Username" required>

            <input type="password" name="password" class="input-field" placeholder="Password" required>

            <div class="forgot">
                <a href="#" style="color:#777; text-decoration:none;">Forgot Password?</a>
            </div>

            <button type="submit" class="login-btn">Log in</button>

        </form>

        <img src="img/silhouettes.png" class="silhouettes">

    </div>

</body>
</html>
