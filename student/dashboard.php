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
<title>Student Dashboard - UKM Pusat Sukan</title>

<style>
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background-color: #ffffff;
    }

    /* ===== TOP BAR ===== */
    .top-header {
        width: 100%;
        background: #0c4da2; /* SAME BLUE BAR */
        padding: 5px 0;
        text-align: center;
    }

    .top-header img {
        height: 70px;
    }

    /* ===== NAVIGATION ===== */
    .nav-bar {
        background: #3b9ae1;   /* SAME LIGHT BLUE */
        display: flex;
        justify-content: center;
        gap: 60px;
        padding: 14px 0;
        font-weight: bold;
    }

    .nav-bar a {
        color: white;
        text-decoration: none;
        font-size: 18px;
    }

    .nav-bar a:hover {
        text-decoration: underline;
    }

    /* ===== HERO IMAGE ===== */
    .hero {
        width: 100%;
        height: 250px;
        background: url('../img/header-sports.jpg') center/cover no-repeat;
        position: relative;
    }

    .hero-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.3);
    }

    .hero-text {
        position: absolute;
        bottom: 25px;
        left: 40px;
        color: white;
        font-size: 36px;
        font-weight: bold;
        text-shadow: 2px 2px 4px black;
    }

    /* ===== SECTION TITLE ===== */
    .section-title {
        text-align: center;
        font-size: 32px;
        font-weight: bold;
        color: #003366;
        margin-top: 40px;
    }

    /* ===== TABLE ===== */
    .history-table {
        width: 85%;
        margin: 25px auto;
        border-collapse: collapse;
        font-size: 18px;
    }

    .history-table th {
        background: #003366;
        color: white;
        padding: 15px;
        font-size: 20px;
    }

    .history-table td {
        background: #e5f3ff;
        padding: 18px;
        text-align: center;
        border-bottom: 2px solid white;
    }

    /* ===== BUTTONS ===== */
    .cancel-btn {
        background: #c11a1a;
        color: white;
        padding: 10px 25px;
        border-radius: 5px;
        font-weight: bold;
    }

    .review-btn {
        background: #2bb33c;
        color: white;
        padding: 10px 25px;
        border-radius: 5px;
        font-weight: bold;
    }

</style>
</head>

<body>

<!-- TOP HEADER IMAGE -->
<div class="top-header">
    <img src="../img/ukm-pusat-sukan-logo.png">
</div>

<!-- NAV BAR -->
<div class="nav-bar">
    <a href="#">HOME</a>
    <a href="#">INFO</a>
    <a href="student_facilities.php">BOOK FACILITY</a>
    <a href="#">FEEDBACK/REVIEW</a>
    <a href="#">CANCEL BOOKING</a>
</div>

<!-- HERO IMAGE -->
<div class="hero">
    <div class="hero-overlay"></div>
    <div class="hero-text">PUSAT SUKAN UNIVERSITI</div>
</div>

<!-- SECTION TITLE -->
<div class="section-title">BOOKING HISTORY</div>

<!-- BOOKING TABLE -->
<table class="history-table">
    <tr>
        <th>FACILITIES</th>
        <th>DATE/TIME</th>
        <th>STATUS</th>
    </tr>

    <tr>
        <td>PADANG D</td>
        <td>9:00AM - 11:00AM<br>23/11/2025</td>
        <td>
            <span class="cancel-btn">CANCEL</span>
            &nbsp;&nbsp;
            <span class="review-btn">REVIEW</span>
        </td>
    </tr>

    <tr>
        <td>GELANGGANG SKUASY</td>
        <td>9:00AM - 11:00AM<br>23/11/2025</td>
        <td>
            <span class="cancel-btn">CANCEL</span>
            &nbsp;&nbsp;
            <span class="review-btn">REVIEW</span>
        </td>
    </tr>

    <tr>
        <td>GELANGGANG BOLA JARING</td>
        <td>9:00AM - 11:00AM<br>23/11/2025</td>
        <td>
            <span class="cancel-btn">CANCEL</span>
            &nbsp;&nbsp;
            <span class="review-btn">REVIEW</span>
        </td>
    </tr>

    <tr>
        <td>GELANGGANG BOLA SEPAK</td>
        <td>9:00AM - 11:00AM<br>23/11/2025</td>
        <td>
            <span class="cancel-btn">CANCEL</span>
            &nbsp;&nbsp;
            <span class="review-btn">REVIEW</span>
        </td>
    </tr>

</table>

</body>
</html>
