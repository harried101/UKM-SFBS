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

    /* ===== TOP HEADER WITH TWO LOGOS ===== */
    .top-header {
        width: 100%;
        background: #0b4d9d;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 20px;
        padding: 5px 0;
    }

    .top-header img {
        height: 60px;
    }

    .logo-ukm {
        height: 60px;
    }

    .logo-sukan {
        height: 60px;
    }

    /* ===== BLUE SUBLINE ===== */
    .subline {
        width: 100%;
        background: #0b4d9d;
        color: white;
        text-align: center;
        padding: 6px 0;
        font-weight: bold;
        font-size: 17px;
        letter-spacing: 1px;
    }

    .subline .ukm {
        font-weight: bold;
        font-style: italic;
    }

    /* ===== FULL WIDTH NAVIGATION BAR ===== */
    .nav-bar {
        width: 100%;
        display: flex;
        padding: 0;
        margin: 0;
    }

    .nav-item, .dropdown {
        flex: 1;
        text-align: center;
        background: #3aa1e0;
        color: white;
        padding: 16px 0;
        font-weight: bold;
        font-size: 18px;
        cursor: pointer;
        text-decoration: none;
        border-right: 1px solid rgba(255,255,255,0.3);
        position: relative;
        transition: 0.3s;
    }

    .nav-item:last-child, .dropdown:last-child {
        border-right: none;
    }

    .nav-item:hover, .dropdown:hover .dropdown-btn {
        background: #2d8ac3;
        color: white;
    }

    .dropdown-btn {
        display: block;
        width: 100%;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        top: 52px;
        left: 0;
        width: 100%;
        background: #3aa1e0;
        z-index: 10;
    }

    .dropdown-content a {
        display: block;
        padding: 12px 0;
        text-decoration: none;
        color: white;
        font-size: 16px;
    }

    .dropdown-content a:hover {
        background: #2d8ac3;
    }

    .dropdown:hover .dropdown-content {
        display: block;
    }

    /* ===== BANNER ===== */
    .banner {
        width: 100%;
        height: 260px;
        background: url('assets/img/pusatsukan.jpeg') no-repeat center center;
        background-size: cover;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 22px;
        color: #555;
    }

    /* ===== LONG BANNER IMAGE ABOVE BOOKING HISTORY ===== */
    .long-banner img {
        width: 100%;
        max-height: 400px;
        object-fit: cover;
        display: block;
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
        cursor: pointer;
    }

    .review-btn {
        background: #21b32d;
        padding: 10px 25px;
        border-radius: 5px;
        color: white;
        font-weight: bold;
        display: inline-block;
        cursor: pointer;
    }

    /* ===== FOOTER ===== */
    .footer {
        background: #0b4d9d;
        color: white;
        text-align: center;
        padding: 25px 15px;
        font-size: 16px;
        line-height: 1.6;
        margin-top: 50px;
    }

    .footer a {
        color: #ffffff;
        text-decoration: underline;
        margin: 0 5px;
    }

    .footer .contact {
        font-weight: bold;
        margin-bottom: 10px;
        display: block;
    }
</style>
</head>

<body>

<!-- TOP HEADER WITH TWO LOGOS -->
<div class="top-header">
    <img src="../assets/img/logo.png" alt="UKM Logo" class="logo-ukm">
    <img src="../assets/img/pusatsukanlogo.png" alt="Pusat Sukan Logo" class="logo-sukan">
</div>

<!-- BLUE SUBLINE -->
<div class="subline">
    PUSAT SUKAN / <span class="ukm">UNIVERSITI KEBANGSAAN MALAYSIA</span>
</div>

<!-- FULL WIDTH NAVIGATION BAR -->
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

<!-- LONG BANNER ABOVE BOOKING HISTORY -->
<div class="long-banner">
    <img src="../assets/img/psukan.jpg" alt="Pusat Sukan">
</div>

<!-- MAIN BUTTON: Browse Facilities -->
<div style="display: flex; justify-content: center; margin: 50px 0;">
    <a href="student_facilities.php" class="browse-btn">
        BROWSE FACILITIES
    </a>
</div>

<style>
.browse-btn {
    display: inline-block;
    background: linear-gradient(135deg, #1e3c72, #2a5298); /* Nice blue gradient */
    color: white;
    font-family: 'Times New Roman', serif;
    font-weight: bold;
    font-size: 2rem; /* Bigger size */
    text-transform: uppercase; /* Caps lock */
    padding: 20px 60px; /* Bigger padding */
    border-radius: 15px;
    box-shadow: 0 8px 20px rgba(30,60,114,0.5);
    text-decoration: none;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
}

.browse-btn::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    width: 100%;
    height: 0%;
    background: rgba(255,255,255,0.15);
    border-radius: 15px;
    transition: all 0.3s ease;
}

.browse-btn:hover::after {
    height: 100%;
}

.browse-btn:hover {
    transform: translateY(-5px) scale(1.08);
    box-shadow: 0 12px 30px rgba(30,60,114,0.6);
}
</style>

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

<!-- FOOTER -->
<div class="footer">
    <!-- Top part: logos + contact info -->
    <div class="footer-top">
        <div class="footer-container">
            <!-- Left: Pusat Sukan Logo -->
            <div class="footer-logo">
                <img src="../assets/img/pusatsukanlogo.png" alt="Pusat Sukan Logo">
            </div>

            <!-- Center: Contact Info -->
            <div class="footer-info">
                <strong>PEJABAT PENGARAH PUSAT SUKAN</strong><br>
                Stadium Universiti<br>
                Universiti Kebangsaan Malaysia<br>
                43600 Bangi, Selangor Darul Ehsan<br>
                No. Telefon Tempahan: 03-8921-5306
            </div>

            <!-- Right: SDG Logo -->
            <div class="footer-sdg">
                <img src="../assets/img/sdg.png" alt="SDG Logo">
            </div>
        </div>
    </div>

    <!-- Bottom part: copyright / links -->
    <div class="footer-bottom">
        Hakcipta © 2022 Universiti Kebangsaan Malaysia<br>
        Penafian | Hakcipta | Dasar Privasi | Dasar Keselamatan | Hubungi Kami
    </div>
</div>

<style>
/* ===== FOOTER ===== */

/* Top part: logos + contact info */
.footer-top {
    background: #6badce; /* Top blue shade */
    color: white;
    padding: 40px 15px 20px;
}

.footer-container {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 50px;
    flex-wrap: wrap;
    text-align: center;
    margin-bottom: 20px;
}

.footer-logo img {
    height: 120px;
    display: block;
}

.footer-sdg img {
    height: 150px;
    display: block;
}

.footer-info {
    line-height: 1.6;
}

/* Bottom part: copyright / links */
.footer-bottom {
    background: #0b4d9d; /* Different blue shade */
    color: white;
    text-align: center;
    padding: 20px 10px;
    font-size: 16px;
    line-height: 1.6;
}

.footer-bottom a {
    color: #ffffff;
    text-decoration: underline;
    margin: 0 5px;
}

@media screen and (max-width: 768px) {
    .footer-container {
        flex-direction: column;
    }
    .footer-logo img {
        height: 90px;
    }
    .footer-sdg img {
        height: 120px;
    }
}
</style>

</body>
</html>
