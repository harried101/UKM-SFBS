<?php
session_start();

// SECURITY CHECK: Redirect if not logged in or role is not Student
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Student') {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - UKM SFBS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #e6ffe6; color: #006400; text-align: center; padding-top: 50px; }
        h1 { border-bottom: 2px solid #006400; padding-bottom: 10px; margin-bottom: 40px; display: inline-block;}
        .dashboard-btn {
            padding: 15px 30px; 
            background-color: #006400; 
            color: white; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            transition: background-color 0.3s;
            margin: 0 10px;
            display: inline-block;
        }
        .dashboard-btn:hover { background-color: #004c00; }
        .welcome-text { font-size: 1.2rem; margin-bottom: 30px; }
    </style>
</head>
<body>
    <h1>✅ STUDENT DASHBOARD LOADED SUCCESSFULLY!</h1>
    
    <p class="welcome-text">
        Welcome, <strong><?php echo htmlspecialchars($_SESSION['userIdentifier'] ?? 'Student'); ?></strong>!
    </p>
    
    <div style="margin-top: 20px;">
        <!-- Link to the Facility List page -->
        <a href="student_facilities.php" class="dashboard-btn">TODO: Browse Facilities</a>
        
        <!-- Link to Logout -->
        <a href="../logout.php" class="dashboard-btn">🚪 Log Out</a>
    </div>
</body>
</html>