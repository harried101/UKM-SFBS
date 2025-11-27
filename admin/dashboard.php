<?php 
// PHP placeholder to ensure the file runs correctly on the server
// This is the file for the Admin Role (K012033)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - UKM SFBS</title>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #ffcccc; color: #b30e22; text-align: center; padding-top: 50px; }
        h1 { border-bottom: 2px solid #b30e22; padding-bottom: 10px; margin-bottom: 40px;}
        .button-group { display: flex; justify-content: center; gap: 20px; margin-top: 20px; }
        .dashboard-btn {
            padding: 15px 30px; 
            background-color: #b30e22; 
            color: white; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .dashboard-btn:hover { background-color: #8a0d19; }
    </style>
</head>
<body>
    <h1>✅ ADMIN DASHBOARD LOADED SUCCESSFULLY!</h1>
    <p>Authentication and redirect successful for Admin ID: K012033.</p>
    <p>Your next task is adding a new facility via the link below:</p>

    <div class="button-group">
        <!-- Link to the Add Facility Form -->
        <a href="addfacilities.php" class="dashboard-btn">➕ Add New Facility</a>
        
        <!-- Button to return to the login screen -->
        <!-- Button to log out -->
        <a href="../logout.php" class="dashboard-btn">🚪 Log Out</a>
    </div>
</body>
</html>