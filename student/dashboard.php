<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Dashboard - Pusat Sukan</title>

<style>
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background: #ffffff;
    }

    /* ===== TOP HEADER (LOGO AREA) ===== */
    .top-header {
        width: 100%;
        background: #0b4d9d; /* SAME DARK BLUE AS UI */
        text-align: center;
        padding: 5px 0;
    }

    .top-header img {
        height: 90px;
    }

    /* ===== BLUE SUBLINE UNDER LOGO ===== */
    .subline {
        width: 100%;
        background: #0b4d9d; /* Same dark blue */
        color: white;
        text-align: center;
        padding: 6px 0;
        font-weight: bold;
        font-size: 17px;
        letter-spacing: 1px;
    }

    /* ===== NAVIGATION BAR ===== */
    .nav-bar {
        width: 100%;
        background: #3aa1e0; /* SAME LIGHT BLUE */
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 50px;
        padding: 14px 0;
        font-size: 18px;
        font-weight: bold;
        position: relative;
    }

    .nav-bar a {
        color: white;
        text-decoration: none;
        cursor: pointer;
    }

    .nav-bar a:hover {
        text-decoration: underline;
    }

    /* ===== INFO DROPDOWN ===== */
    .dropdown {
        position: relative;
        display: inline-block;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        background: #3aa1e0;
        min-width: 180px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        z-index: 10;
        top: 40px;
        border-radius: 0 0 6px 6px;
    }

    .dropdown-content a {
        color: white;
        padding: 10px 15px;
        text-decoration: none;
        display: block;
        font-size: 16px;
    }

    .dropdown-content a:hover {
        background: #2d8ac3;
    }

    .dropdown:hover .dropdown-content {
        display: block;
    }

    /* ===== BANNER/IMAGE INSERT SPACE ===== */
    .banner {
        width: 100%;
        height: 260px;
        background: #ddd; /* Placeholder */
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 22px;
        color: #555;
    }

    /* ===== BOOKING HISTORY TITLE ===== */
    .section-title {
        text-align: center;
        font-size: 30px;
        font-weight: bold;
        color: #003b75;
        margin-top: 35px;
    }

    /* ===== BOOKING HISTORY TABLE ===== */
    table {
        width: 85%;
        margin: 25px auto;
        border-collapse: collapse;
        font-size: 18px;
    }

    th {
        background: #003b75;
        color: white;
        padding: 15px;
        font-size: 20px;
    }

    td {
        background: #d7ecff;
        padding: 18px;
        text-align: center;
        border-bottom: 2px solid white;
    }

    /* ===== CANCEL & REVIEW BUTTONS ===== */
    .cancel-btn {
        background: #c91818;
        padding: 10px 25px;
        border-radius: 5px;
        color: white;
        font-weight: bold;
        display: inline-block;
    }

    .review-btn {
        background: #21b32d;
        padding: 10px 25px;
        border-radius: 5px;
        color: white;
        font-weight: bold;
        display: inline-block;
    }
</style>
</head>

<body>

<!-- LOGO HEADER -->
<div class="top-header">
    <img src="your-logo-here.png" alt="Pusat Sukan Logo">
</div>

<!-- BLUE SUBLINE -->
<div class="subline">
    PUSAT SUKAN / UNIVERSITI KEBANGSAAN MALAYSIA
</div>

<!-- NAV BAR -->
<div class="nav-bar">

    <a href="#">HOME</a>

    <div class="dropdown">
        <a>INFO ▼</a>
        <div class="dropdown-content">
            <a href="#">About</a>
            <a href="#">Rules & Regulations</a>
            <a href="#">Operating Hours</a>
        </div>
    </div>

    <a href="#">BOOK FACILITY</a>
    <a href="#">CANCEL BOOKING</a>
    <a href="#">FEEDBACK/REVIEW</a>

</div>

<!-- IMAGE INSERT SECTION -->
<div class="banner">
    INSERT YOUR PUSAT SUKAN IMAGE HERE
</div>

<!-- BOOKING HISTORY -->
<div class="section-title">BOOKING HISTORY</div>

<table>
    <tr>
        <th>FACILITIES</th>
        <th>DATE/TIME</th>
        <th>STATUS</th>
    </tr>

    <tr>
        <td>PADANG D</td>
        <td>9:00 AM – 11:00 AM<br>23/11/2025</td>
        <td>
            <span class="cancel-btn">CANCEL</span>
            &nbsp;&nbsp;
            <span class="review-btn">REVIEW</span>
        </td>
    </tr>

    <tr>
        <td>GELANGGANG SKUASY</td>
        <td>9:00 AM – 11:00 AM<br>23/11/2025</td>
        <td>
            <span class="cancel-btn">CANCEL</span>
            &nbsp;&nbsp;
            <span class="review-btn">REVIEW</span>
        </td>
    </tr>

    <tr>
        <td>GELANGGANG BOLA JARING</td>
        <td>9:00 AM – 11:00 AM<br>23/11/2025</td>
        <td>
            <span class="cancel-btn">CANCEL</span>
            &nbsp;&nbsp;
            <span class="review-btn">REVIEW</span>
        </td>
    </tr>

    <tr>
        <td>GELANGGANG BOLA SEPAK</td>
        <td>9:00 AM – 11:00 AM<br>23/11/2025</td>
        <td>
            <span class="cancel-btn">CANCEL</span>
            &nbsp;&nbsp;
            <span class="review-btn">REVIEW</span>
        </td>
    </tr>

</table>

</body>
</html>
