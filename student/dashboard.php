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

    /* ===== TOP HEADER ===== */
    .top-header {
        width: 100%;
        background: #0c4da2;
        text-align: center;
        padding: 5px 0;
    }

    .top-header img {
        height: 80px;
    }

    /* ===== NAVIGATION BAR ===== */
    .nav-bar {
        width: 100%;
        background: #3aa1e0;
        display: flex;
        justify-content: center;
        gap: 60px;
        padding: 14px 0;
        font-size: 18px;
        font-weight: bold;
    }

    .nav-bar a {
        color: white;
        text-decoration: none;
    }

    .nav-bar a:hover {
        text-decoration: underline;
    }

    /* ===== BANNER SECTION (YOU WILL INSERT IMAGE HERE) ===== */
    .banner {
        width: 100%;
        height: 270px;
        background: #ddd; /* Placeholder — you will replace */
        position: relative;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 22px;
        color: #555;
    }

    /* ===== BOOKING HISTORY HEADER ===== */
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

    /* CANCEL & REVIEW BUTTONS */
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

<!-- TOP LOGO -->
<div class="top-header">
    <img src="your-logo-here.png" alt="Pusat Sukan Logo">
</div>

<!-- NAVIGATION BAR -->
<div class="nav-bar">
    <a href="#">HOME</a>
    <a href="#">INFO</a>
    <a href="#">BOOK FACILITY</a>
    <a href="#">CANCEL BOOKING</a>
</div>

<!-- INSERT YOUR BANNER IMAGE HERE -->
<div class="banner">
    INSERT YOUR PUSAT SUKAN IMAGE HERE
</div>

<!-- BOOKING HISTORY TITLE -->
<div class="section-title">BOOKING HISTORY</div>

<!-- BOOKING TABLE -->
<table>
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
