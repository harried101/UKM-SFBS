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
    
    <!-- Fonts from index.php (Playfair Display & Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #8a0d19; /* UKM Red/Maroon */
            --accent-color: #f4f4f4;
            --text-dark: #333;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            /* Using the same background aesthetic as login page */
            background: url('../court.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Dark overlay for better contrast */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.65); /* Darken the background image */
            z-index: -1;
        }

        .dashboard-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            width: 90%;
            max-width: 900px;
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        /* Decorative top bar */
        .dashboard-container::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, var(--primary-color), #b30e22);
        }

        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            color: var(--primary-color);
            margin: 0 0 10px 0;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        .welcome-sub {
            font-size: 1.2rem;
            color: #555;
            margin-bottom: 40px;
            font-weight: 400;
        }

        .user-badge {
            background-color: var(--primary-color);
            color: white;
            padding: 6px 18px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 10px rgba(138, 13, 25, 0.3);
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 40px;
        }

        .action-card {
            background: white;
            border: 1px solid #eee;
            border-radius: 16px;
            padding: 35px 25px;
            text-decoration: none;
            color: var(--text-dark);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            position: relative;
            overflow: hidden;
        }

        .action-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
            border-color: var(--primary-color);
        }

        .action-card i {
            font-size: 3rem;
            color: var(--primary-color);
            transition: transform 0.3s ease;
        }

        .action-card:hover i {
            transform: scale(1.1) rotate(-5deg);
        }

        .action-card h3 {
            margin: 0;
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .action-card p {
            font-size: 0.95rem;
            color: #777;
            margin: 0;
            line-height: 1.5;
        }

        .logout-link {
            margin-top: 50px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #777;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
            font-size: 1rem;
        }

        .logout-link:hover {
            color: var(--primary-color);
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            h1 { font-size: 2.5rem; }
            .dashboard-container { padding: 30px 20px; width: 95%; }
            .action-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="dashboard-container">
        <h1>Welcome Back</h1>
        
        <div class="welcome-sub">
            Logged in as <span class="user-badge"><?php echo htmlspecialchars($_SESSION['userIdentifier'] ?? 'Student'); ?></span>
        </div>

        <p style="color: #666; max-width: 600px; margin: 0 auto 40px auto; line-height: 1.6;">
            Access the UKM Sports Facilities Booking System to manage your activities. Select an option below to get started.
        </p>

        <div class="action-grid">
            <!-- Browse Facilities Card -->
            <a href="student_facilities.php" class="action-card">
                <i class="fa-solid fa-dumbbell"></i>
                <h3>Browse Facilities</h3>
                <p>Explore available courts, fields, and halls. Check availability and make new bookings.</p>
            </a>

            <!-- My Bookings Card (Placeholder) -->
            <a href="#" class="action-card" onclick="alert('My Bookings feature is coming soon!'); return false;">
                <i class="fa-solid fa-calendar-check"></i>
                <h3>My Bookings</h3>
                <p>View your active reservations, check status, and manage past booking history.</p>
            </a>
        </div>

        <a href="../logout.php" class="logout-link">
            <i class="fa-solid fa-right-from-bracket"></i> Sign Out
        </a>
    </div>

</body>
</html>