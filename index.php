<?php
session_start();
// If already logged in, redirect immediately based on role
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if ($_SESSION['role'] === 'Admin') {
        header("Location: admin/dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'Student') {
        header("Location: student/dashboard.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UKM Sports Booking Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; font-family: 'Inter', sans-serif; background-color: #f4f4f4; }
        .container { display: flex; height: 100vh; }
        @media (max-width: 900px) {
            .container { flex-direction: column; height: auto; min-height: 100vh; }
            .left-panel { display: none; }
            .right-panel { width: 100%; border-radius: 0; padding: 40px 20px; }
            .login-box { width: 90%; }
        }
        .left-panel {
            width: 50%;
            background-image: url('court.jpg'); 
            background-size: cover;
            background-position: center;
            color: white;
            padding: 50px; 
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.6);
        }
        .left-panel .logos { display: flex; flex-direction: row; justify-content: flex-start; align-items: center; gap: 15px; width: 100%; }
        .left-panel img { background-color: rgba(255, 255, 255, 0.2); padding: 5px; border-radius: 5px; }
        .left-panel h1 { font-family: "Playfair Display", serif; font-size: 45px; font-weight: 700; margin-top: 100px; line-height: 1.2; }
        .right-panel { width: 50%; background: #dce7ea; border-radius: 0 50px 50px 0; display: flex; justify-content: center; align-items: center; }
        .login-box { width: 75%; text-align: center; }
        .login-title { font-family: "Playfair Display", serif; font-size: 50px; color: #8a0d19; margin-bottom: 30px; }
        .message-box { background: #ffebeb; color: #b30e22; border: 1px solid #b30e22; padding: 10px; border-radius: 10px; margin-bottom: 20px; text-align: left; display: none; }
        .loading-spinner { border: 4px solid #f3f3f3; border-top: 4px solid #b30e22; border-radius: 50%; width: 20px; height: 20px; animation: spin 1s linear infinite; display: none; margin: 0 auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .input-group { background: white; border-radius: 30px; display: flex; align-items: center; padding: 10px 20px; margin: 15px 0; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .input-group input { border: none; outline: none; width: 100%; padding-left: 10px; font-size: 16px; }
        .forgot { display: block; margin-top: 5px; margin-bottom: 25px; text-align: right; font-size: 14px; color: #555; cursor: pointer; }
        .login-btn { background: #b30e22; color: white; border: none; width: 100%; padding: 14px; border-radius: 30px; font-size: 20px; cursor: pointer; transition: background 0.3s ease; margin-top: 30px; }
        .login-btn:hover { background: #8a0d19; }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <div class="logos">
                <img src="../assets/img/logo.png" width="120" onerror="this.src='https://placehold.co/120x120/8a0d19/FFFFFF?text=UKM'">
                <img src="../assets/img/pisatsukanlogo.png" width="120" onerror="this.src='https://placehold.co/120x120/004c99/FFFFFF?text=Pusat+Sukan'">
            </div>
            <h1><b>UKM Sport<br>Facilities Booking<br>System</b></h1>
        </div>
        <div class="right-panel">
            <div class="login-box">
                <div class="login-title">LOG IN</div>
                <div id="messageBox" class="message-box"></div>
                <form id="loginForm"> 
                    <div class="input-group">
                        <i>👤</i>
                        <input type="text" id="userIdentifier" name="userIdentifier" placeholder="Matric/Staff Number" required>
                    </div>
                    <div class="input-group">
                        <i>🔒</i>
                        <input type="password" id="password" name="password" placeholder="Password" required>
                        <i id="togglePassword" style="margin-left:10px; cursor: pointer;">👁️</i>
                    </div>
                    <button type="submit" id="loginBtn" class="login-btn">
                        <span id="loginText">Log in</span>
                        <div id="spinner" class="loading-spinner"></div>
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('loginForm');
            const identifierInput = document.getElementById('userIdentifier');
            const passwordInput = document.getElementById('password');
            const messageBox = document.getElementById('messageBox');
            const loginBtn = document.getElementById('loginBtn');
            const loginText = document.getElementById('loginText');
            const spinner = document.getElementById('spinner');
            const togglePassword = document.getElementById('togglePassword');
            const LOGIN_ENDPOINT = 'login_processor.php'; 

            const showMessage = (message, isError = true) => {
                messageBox.textContent = message;
                messageBox.style.display = 'block';
                messageBox.style.backgroundColor = isError ? '#ffebeb' : '#e6ffe6';
                messageBox.style.color = isError ? '#b30e22' : '#006400';
                messageBox.style.borderColor = isError ? '#b30e22' : '#006400';
            };

            form.addEventListener('submit', async (e) => {
                e.preventDefault(); 
                loginText.style.display = 'none';
                spinner.style.display = 'block';
                loginBtn.disabled = true;
                messageBox.style.display = 'none';

                const credentials = {
                    userIdentifier: identifierInput.value,
                    password: passwordInput.value
                };
                
                try {
                    const response = await fetch(LOGIN_ENDPOINT, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(credentials)
                    });

                    if (!response.ok) { throw new Error('Server error'); }
                    const result = await response.json();

                    loginText.style.display = 'inline';
                    spinner.style.display = 'none';
                    loginBtn.disabled = false;
                    
                    if (result.status === 'success') {
                        showMessage(result.message, false); 
                        setTimeout(() => {
                            if (result.role === 'Admin') {
                                window.location.href = 'admin/dashboard.php'; 
                            } else if (result.role === 'Student') {
                                window.location.href = 'student/dashboard.php'; 
                            }
                        }, 1000); 
                    } else {
                        showMessage(result.message, true);
                    }
                } catch (error) {
                    console.error(error);
                    loginText.style.display = 'inline';
                    spinner.style.display = 'none';
                    loginBtn.disabled = false;
                    showMessage('An unexpected server or network error occurred.', true);
                }
            });
            
            togglePassword.addEventListener('click', () => {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    togglePassword.textContent = '🙈';
                } else {
                    passwordInput.type = 'password';
                    togglePassword.textContent = '👁️';
                }
            });
        });
    </script>
</body>
</html>