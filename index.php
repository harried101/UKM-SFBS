<?php
session_start();

// PREVENT CACHING (Fixes "Too Many Redirects" loop issues)
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

// If already logged in, redirect immediately
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
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { 
            margin: 0; 
            font-family: 'Inter', sans-serif; 
            background-color: #f4f4f4;
            /* Optional: Subtle background image or color */
            background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('court.jpg');
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-card {
            background: white;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            text-align: center;
            position: relative;
            z-index: 10;
        }

        .logos {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 25px;
        }
        .logos img { height: 50px; width: auto; }

        h1 {
            font-family: "Playfair Display", serif;
            font-size: 28px;
            color: #8a0d19;
            margin: 0 0 5px 0;
            font-weight: 700;
        }
        
        p.subtitle {
            color: #666;
            margin: 0 0 30px 0;
            font-size: 14px;
        }

        .input-group {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            transition: all 0.2s;
        }
        .input-group:focus-within {
            border-color: #8a0d19;
            box-shadow: 0 0 0 3px rgba(138, 13, 25, 0.1);
        }
        .input-group input {
            border: none;
            background: transparent;
            width: 100%;
            margin-left: 10px;
            outline: none;
            font-size: 15px;
            color: #333;
        }
        .icon { opacity: 0.5; font-size: 18px; }

        .login-btn {
            background: #8a0d19;
            color: white;
            border: none;
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 10px;
        }
        .login-btn:hover { background: #6d0a13; }
        .login-btn:disabled { opacity: 0.7; cursor: not-allowed; }

        .message-box {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            padding: 10px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: left;
            display: none;
        }

        /* Spinner */
        .loading-spinner {
            border: 2px solid rgba(255,255,255,0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            animation: spin 1s linear infinite;
            display: none;
            margin: 0 auto;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="logos">
            <img src="assets/img/ukm.png" alt="UKM">
            <img src="assets/img/pusatsukanlogo.png" alt="Pusat Sukan">
        </div>

        <h1>Welcome Back</h1>
        <p class="subtitle">UKM Sports Facility Booking System</p>

        <div id="messageBox" class="message-box"></div>

        <form id="loginForm">
            <div class="input-group">
                <span class="icon">👤</span>
                <input type="text" id="userIdentifier" name="userIdentifier" placeholder="Matric / Staff ID" required>
            </div>

            <div class="input-group">
                <span class="icon">🔒</span>
                <input type="password" id="password" name="password" placeholder="Password" required>
                <span class="icon" id="togglePassword" style="cursor: pointer;">👁️</span>
            </div>

            <button type="submit" id="loginBtn" class="login-btn">
                <span id="loginText">Log In</span>
                <div id="spinner" class="loading-spinner"></div>
            </button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('loginForm');
            const messageBox = document.getElementById('messageBox');
            const loginBtn = document.getElementById('loginBtn');
            const loginText = document.getElementById('loginText');
            const spinner = document.getElementById('spinner');
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');

            // Toggle Password
            togglePassword.addEventListener('click', () => {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                togglePassword.textContent = type === 'password' ? '👁️' : '🙈';
            });

            // Login Logic
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                // UI Loading State
                loginText.style.display = 'none';
                spinner.style.display = 'block';
                loginBtn.disabled = true;
                messageBox.style.display = 'none';

                const formData = {
                    userIdentifier: document.getElementById('userIdentifier').value,
                    password: passwordInput.value
                };

                try {
                    const response = await fetch('login_processor.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(formData)
                    });

                    const result = await response.json();

                    if (result.status === 'success') {
                        // Success Color
                        messageBox.style.backgroundColor = '#f0fdf4';
                        messageBox.style.color = '#166534';
                        messageBox.style.borderColor = '#bbf7d0';
                        messageBox.textContent = "Login successful! Redirecting...";
                        messageBox.style.display = 'block';
                        
                        setTimeout(() => {
                            if (result.role === 'Admin') window.location.href = 'admin/dashboard.php';
                            else window.location.href = 'student/dashboard.php';
                        }, 1000);
                    } else {
                        throw new Error(result.message);
                    }

                } catch (error) {
                    // Reset UI
                    loginText.style.display = 'inline';
                    spinner.style.display = 'none';
                    loginBtn.disabled = false;
                    
                    // Show Error
                    messageBox.style.backgroundColor = '#fef2f2';
                    messageBox.style.color = '#991b1b';
                    messageBox.style.borderColor = '#fecaca';
                    messageBox.textContent = error.message || 'Login failed. Please check your credentials.';
                    messageBox.style.display = 'block';
                }
            });
        });
    </script>

</body>
</html>