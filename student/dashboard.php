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

    /* ===== LOGO HEADER ===== */
    .top-header {
        width: 100%;
        background: #0b4d9d; /* DARK BLUE */
        text-align: center;
        padding: 10px 0;
        border-bottom: 6px solid #6badce; /* LIGHT BLUE STRIPE */
    }

    .top-header img {
        height: 90px;
    }

    /* ===== TEXT BELOW LOGO ===== */
    .branding-text {
        text-align: center;
        font-size: 18px;
        margin-top: 10px;
        color: #003b75;
        font-weight: bold;
    }

    /* Only UKM italic */
    .branding-text span.ukm {
        font-style: italic;
        font-weight: normal;
    }

    /* LIGHT BLUE LINE UNDER TEXT */
    .blue-line {
        width: 100%;
        height: 6px;
        background: #6badce;
        margin-top: 6px;
    }

    /* ===== NAV BAR FULL WIDTH ===== */
    .nav-bar {
        width: 100%;
        background: #3aa1e0; 
        display: flex;
        padding: 0;
    }

    .nav-item {
        flex: 1;
        text-align: center;
        padding: 16px 0;
        font-weight: bold;
        font-size: 18px;
        color: white;
        cursor: pointer;
        text-decoration: none;
        border-right: 1px solid rgba(255,255,255,0.3);
    }

    .nav-item:last-child {
        border-right: none;
    }

    .nav-item:hover {
        background: #2d8ac3;
    }

    /* DROPDOWN */
    .dropdown {
        position: relative;
        flex: 1;
    }

    .dropdown-btn {
        padding: 16px 0;
        font-weight: bold;
        font-size: 18px;
        color: white;
        cursor: pointer;
        text-align: center;
        border-right: 1px solid rgba(255,255,255,0.3);
    }

    .dropdown-content {
        display: none;
        position: absolute;
        background: #3aa1e0;
        width: 100%;
        top: 52px;
        z-index: 1000;
    }

    .dropdown-content a {
        display: block;
        padding: 12px;
        color: white;
        text-decoration: none;
        font-size: 16px;
    }

    .dropdown-content a:hover {
        background: #2d8ac3;
    }

    .dropdown:hover .dropdown-content {
        display: block;
    }

    /* ===== IMAGE AREA ===== */
    .banner {
        width: 100%;
        height: 260px;
        background: #ddd;
        display: flex;
        justify-content: center;
        align-items: center;
        color: #555;
        font-size: 22px;
        margin-top: 10px;
    }

    /* ===== BOOKING HISTORY ===== */
    .section-title {
        text-align: center;
        font-size: 30px;
        font-weight: bold;
        color: #003b75;
        margin-top: 35px;
    }

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

    .cancel-btn {
        background: #c91818;
        padding: 10px 25px;
        color: white;
        border-radius: 5px;
        font-weight: bold;
        cursor: pointer;
    }

    .review-btn {
        background: #21b32d;
        padding: 10px 25px;
        color: white;
        border-radius: 5px;
        font-weight: bold;
        cursor: pointer;
    }
</style>
</head>

<body>

<!-- LOGO HEADER -->
<div class="top-header">
    <img src="your-logo-here.png" alt="Pusat Sukan Logo">
</div>

<!-- TEXT BELOW LOGO: ONLY UKM ITALIC -->
<div class="branding-text">
    Pusat Sukan / <span class="ukm">Universiti Kebangsaan Malaysia</span>
</div>

<!-- BLUE LINE -->
<div class="blue-line"></div>

<!-- NAVIGATION -->
<div class="nav-bar">
    <a class="nav-item" href="#">HOME</a>

    <div class="dropdown">
        <div class="dropdown-btn">INFO ▼</div>
        <div class="dropdown-content">
            <a href="#">About</a>
            <a href="#">Rules & Regulations</a>
            <a href="#">Operating Hours</a>
        </div>
    </div>

    <a class="nav-item" href="#">BOOK FACILITY</a>
    <a class="nav-item" href="#">CANCEL BOOKING</a>
    <a class="nav-item" href="#">FEEDBACK / REVIEW</a>
</div>

<!-- IMAGE PLACEHOLDER -->
<div class="banner">
    INSERT YOUR PUSAT SUKAN IMAGE HERE
</div>

<!-- BOOKING HISTORY -->
<div class="section-title">BOOKING HISTORY</div>

<table>
    <tr>
        <th>FACILITIES</th>
        <th>DATE / TIME</th>
        <th>STATUS</th>
    </tr>

    <tr>
        <td>Padang D</td>
        <td>9:00 AM – 11:00 AM<br>23/11/2025</td>
        <td>
            <span class="cancel-btn">CANCEL</span>
            &nbsp;&nbsp;
            <span class="review-btn">REVIEW</span>
        </td>
    </tr>

    <tr>
        <td>Gelanggang Skuasy</td>
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
