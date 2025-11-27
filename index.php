<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UKM Sports Booking Login</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">

    <style>
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background-color: #f4f4f4;
        }

        .container {
            display: flex;
            height: 100vh;
        }

        /* Left side background */
        .left-panel {
            width: 50%;
            background-image: url('court.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 20px 50px 50px 50px;
        }

        /* Move logos up */
        .left-panel img {
            display: block;
            margin-bottom: 10px;
        }

        .left-panel h1 {
            font-size: 45px;
            font-weight: 700;
            margin-top: 100px; 
            line-height: 1.2;
        }

        /* Right login box */
        .right-panel {
            width: 50%;
            background: #dce7ea;
            border-radius: 0 50px 50px 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-box {
            width: 75%;
            text-align: center;
        }

        .login-title {
            font-family: "Playfair Display", serif;
            font-size: 50px;
            color: #8a0d19;
            margin-bottom: 30px;
        }

        .input-group {
            background: white;
            border-radius: 30px;
            display: flex;
            align-items: center;
            padding: 10px 20px;
            margin: 15px 0;
        }

        .input-group input {
            border: none;
            outline: none;
            width: 100%;
            padding-left: 10px;
            font-size: 16px;
        }

        .forgot {
            display: block;
            margin-top: 5px;
            margin-bottom: 25px;
            text-align: right;
            font-size: 14px;
            color: #555;
            cursor: pointer;
        }

        .login-btn {
            background: #b30e22;
            color: white;
            border: none;
            width: 100%;
            padding: 14px;
            border-radius: 30px;
            font-size: 20px;
            cursor: pointer;
        }

        /* Error Message */
        .error-box {
            margin-top: 15px;
            color: #b30000;
            font-size: 16px;
            font-weight: 500;
        }

        .logos {
            display: flex !important;
            flex-direction: row !important;
            justify-content: center;
            align-items: center;
            gap: 15px;
            width: 100%;
        }
    </style>
</head>

<body>

<?php 
session_start(); 
?>

<div class="container">

    <!-- LEFT SIDE -->
    <div class="left-panel">
        <div class="logos">
            <img src="logo.png" width="120">
            <img src="pusatsukan.png" width="120">
        </div>

        <h1><b>UKM Sport<br>Facilities Booking<br>System</b></h1>
    </div>


    <!-- RIGHT SIDE -->
    <div class="right-panel">
        <div class="login-box">

            <div class="login-title">LOG IN</div>

            <!-- FORM START -->
            <form action="auth.php" method="POST">

                <!-- Username -->
                <div class="input-group">
                    <i>👤</i>
                    <input type="text" name="matric" placeholder="Matric Number" required>
                </div>

                <!-- Password -->
                <div class="input-group">
                    <i>🔒</i>
                    <input type="password" name="password" placeholder="Password" required>
                    <i style="margin-left:10px;">👁️</i>
                </div>

                <div class="forgot">Forgot Password?</div>

                <button class="login-btn" type="submit">Log in</button>

                <!-- Error message -->
                <?php 
                if (isset($_SESSION['error'])) {
                    echo "<div class='error-box'>".$_SESSION['error']."</div>";
                    unset($_SESSION['error']);
                }
                ?>

            </form>
            <!-- FORM END -->

        </div>
    </div>

</div>

</body>
</html>
