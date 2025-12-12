<?php
session_start();

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

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    body {
        margin: 0;
        background-color: #f5f6fa;
        font-family: 'Poppins', sans-serif;
    }

    /* ======= TOP BAR NAVIGATION ======= */
    .top-nav {
        background: #003b75;
        color: white;
        display: flex;
        justify-content: center;
        gap: 40px;
        padding: 18px 0;
        font-weight: 500;
        font-size: 1.1rem;
    }

    .top-nav a {
        color: white;
        text-decoration: none;
        transition: 0.3s;
    }

    .top-nav a:hover {
        text-decoration: underline;
    }

    /* ======= HERO BANNER ======= */
    .hero {
        width: 100%;
        height: 260px;
        background: url('../court.jpg') center/cover;
        position: relative;
    }

    .hero::after {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.55);
    }

    .hero-text {
        position: absolute;
        bottom: 20px;
        right: 30px;
        color: white;
        font-size: 2rem;
        font-weight: 700;
        text-shadow: 2px 2px 5px rgba(0,0,0,0.7);
    }

    /* ======= DASHBOARD CONTENT ======= */
    .container {
        max-width: 1100px;
        margin: 30px auto;
        background: white;
        padding: 30px 40px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    h2 {
        text-align: center;
        font-size: 2.3rem;
        color: #003b75;
        margin-top: 0;
        letter-spacing: 1px;
        font-weight: 700;
    }

    .welcome {
        text-align: center;
        margin-top: -10px;
        color: #777;
        font-size: 1rem;
        margin-bottom: 30px;
    }

    /* ======= ACTION CARDS LIKE PUSAT SUKAN UI ======= */
    .card-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 25px;
        margin-top: 20px;
    }

    .dashboard-card {
        background: #e8f4ff;
        border: 2px solid #003b75;
        border-radius: 10px;
        text-align: center;
        padding: 25px;
        transition: 0.3s;
        color: #003b75;
        text-decoration: none;
        font-weight: 600;
    }

    .dashboard-card:hover {
        background: #d4eaff;
        transform: translateY(-5px);
    }

    .dashboard-card i {
        font-size: 2.5rem;
        margin-bottom: 10px;
        color: #003b75;
    }

    /* ======= LOGOUT ======= */
    .logout-wrap {
        text-align: center;
        margin-top: 40px;
    }
    .logout-btn {
        color: #cc0000;
        text-decoration: none;
        font-weight: 600;
        font-size: 1.1rem;
    }
    .logout-btn:hover {
        text-decoration: underline;
    }

</style>
</head>
<body>

<!-- NAV BAR -->
<div class="top-nav">
    <a href="#">HOME</a>
    <a href="student_facilities.php">BOOK FACILITY</a>
    <a href="#">MY BOOKINGS</a>
    <a href="../logout.php">LOGOUT</a>
</div>

<!-- HERO IMAGE -->
<div class="hero">
    <div class="hero-text">STUDENT DASHBOARD</div>
</div>

<!-- MAIN CONTENT -->
<div class="container">
    <h2>Welcome, Student</h2>
    <p class="welcome">Logged in as <strong><?php echo htmlspecialchars($_SESSION['userIdentifier']); ?></strong></p>

    <div class="card-grid">

        <a href="student_facilities.php" class="dashboard-card">
            <i class="fa-solid fa-dumbbell"></i>
            <div>Browse Facilities</div>
        </a>

        <a href="#" class="dashboard-card" onclick="alert('My Bookings page coming soon!'); return false;">
            <i class="fa-solid fa-calendar-check"></i>
            <div>My Bookings</div>
        </a>

    </div>

    <div class="logout-wrap">
        <a href="../logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
</div>

</body>
</html>
