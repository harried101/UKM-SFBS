<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Dashboard – Pusat Sukan</title>

<style>
    body {
        margin: 0;
        font-family: "Inter", sans-serif;
        background: #f5f7fa;
        color: #333;
    }

    /* ===== HEADER ===== */
    .top-header {
        width: 100%;
        background: #0b4d9d;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 20px;
        padding: 8px 0;
    }

    .top-header img {
        height: 48px;
    }

    /* ===== NAVIGATION ===== */
    .nav-bar {
        width: 100%;
        display: flex;
        background: #6badce;
        border-bottom: 1px solid rgba(0,0,0,0.1);
    }

    .nav-bar a {
        flex: 1;
        font-size: 14px;
        padding: 10px 0;
        text-align: center;
        text-decoration: none;
        color: #fff;
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        transition: 0.3s;
    }

    .nav-bar a:hover {
        background: #559fc4;
    }

    /* ===== BANNER IMAGE ===== */
    .long-banner img {
        width: 100%;
        max-height: 400px;
        object-fit: cover;
        display: block;
    }

    /* ===== INFO TEXT ===== */
    .info-text {
        width: 80%;
        margin: 40px auto;
        font-size: 16px;
        line-height: 1.7;
        color: #004d7a;
        text-align: center;
    }

    .info-text h2 {
        font-size: 32px;
        font-weight: bold;
        margin-bottom: 20px;
        color: #003b75;
    }

    /* ===== COUNTERS ===== */
    .counters {
        display: flex;
        justify-content: center;
        gap: 100px;
        margin: 20px auto 30px auto;
        text-align: center;
    }

    .counter {
        font-size: 24px;
        color: #004d7a;
    }

    .counter-number {
        font-size: 48px;
        font-weight: 700;
        color: #0b4d9d;
    }

    .counter-label {
        font-size: 18px;
        margin-top: 5px;
    }

    /* ===== BROWSE FACILITIES BUTTON ===== */
    .browse-btn {
        display: inline-block;
        background: linear-gradient(135deg, #1e3c72, #2a5298);
        color: white;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        font-weight: bold;
        font-size: 2rem;
        text-transform: uppercase;
        padding: 20px 60px;
        border-radius: 15px;
        text-decoration: none;
        transition: all 0.3s ease;
        margin: 20px auto 50px auto;
        display: block;
        width: max-content;
        text-align: center;
    }

    .browse-btn:hover {
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 8px 20px rgba(30,60,114,0.5);
    }

    /* ===== BOOKING HISTORY ===== */
    .section-title {
        text-align: center;
        font-size: 40px;
        color: #0b4d9d;
        font-weight: 900;
        text-transform: uppercase;
        margin-top: 35px;
    }

    table {
        width: 70%;
        margin: 20px auto;
        border-collapse: collapse;
        background: white;
        font-size: 14px;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    th {
        background: #003b75;
        color: white;
        padding: 12px;
    }

    td {
        padding: 14px;
        text-align: center;
        border-bottom: 1px solid #eee;
    }

    .cancel-btn {
        background: #c91818;
        padding: 6px 14px;
        font-size: 12px;
        border-radius: 4px;
        color: white;
        font-weight: 600;
        cursor: pointer;
    }

    .review-btn {
        background: #21b32d;
        padding: 6px 14px;
        font-size: 12px;
        border-radius: 4px;
        color: white;
        font-weight: 600;
        cursor: pointer;
    }

    /* ===== FOOTER ===== */
    .footer {
        background: #0b4d9d;
        color: white;
        font-size: 14px;
        line-height: 1.6;
    }

    .footer-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 30px 15px;
    }

    .footer-logo img {
        height: 100px; /* slightly bigger left logo */
    }

    .footer-info {
        text-align: center;
        flex: 1;
        line-height: 1.6;
    }

    .footer-title {
        font-size: 22px;
        display: block;
        margin-bottom: 10px; /* space after title */
    }

    .footer-sdg img {
        height: 180px;
    }

    .footer-bottom {
        background: #003b75;
        padding: 12px 15px;
        font-size: 13px;
        text-align: center;
    }

    @media screen and (max-width: 768px) {
        .footer-top {
            flex-direction: column;
            gap: 20px;
        }

        .footer-logo img,
        .footer-sdg img {
            height: 100px;
        }
    }
</style>
</head>
<body>

<!-- HEADER -->
<div class="top-header">
    <img src="../assets/img/logo.png" alt="UKM Logo">
    <img src="../assets/img/pusatsukanlogo.png" alt="Pusat Sukan Logo">
</div>

<!-- NAVIGATION -->
<div class="nav-bar">
    <a href="#">Home</a>
    <a href="#">Info</a>
    <a href="#">Book Facility</a>
    <a href="#">Cancel Booking</a>
    <a href="#">Review</a>
</div>

<!-- BANNER IMAGE -->
<div class="long-banner">
    <img src="../assets/img/psukan.jpg" alt="Pusat Sukan">
</div>

<!-- INFO TEXT -->
<div class="info-text">
    <h2>Let’s Get to Know UKM Sports Center</h2>
    <p>The UKM Sports Center started on 1 November 1974 with a Sports Officer from the Ministry of Education who managed sports activities for students and staff. In 1981 and 1982, UKM participated in the ASEAN University Games. In 2008, the Sports Unit was upgraded to the Sports Center, and in 2010, a director was appointed. Today, the center has 47 staff members.</p>
</div>

<!-- COUNTERS -->
<div class="counters">
    <div class="counter">
        <div class="counter-number" id="facility-counter">0</div>
        <div class="counter-label">Facilities</div>
    </div>
    <div class="counter">
        <div class="counter-number" id="staff-counter">0</div>
        <div class="counter-label">Staffs</div>
    </div>
</div>

<!-- BROWSE FACILITIES BUTTON -->
<a href="student_facilities.php" class="browse-btn">BROWSE FACILITIES</a>

<!-- BOOKING HISTORY -->
<div class="section-title">BOOKING HISTORY</div>

<table>
    <tr>
        <th>FACILITY</th>
        <th>DATE / TIME</th>
        <th>ACTION</th>
    </tr>
    <tr>
        <td>Field D</td>
        <td>23 Nov 2025<br>09:00 – 11:00</td>
        <td>
            <span class="cancel-btn">Cancel</span>
            &nbsp;
            <span class="review-btn">Review</span>
        </td>
    </tr>
    <tr>
        <td>Squash Court</td>
        <td>23 Nov 2025<br>09:00 – 11:00</td>
        <td>
            <span class="cancel-btn">Cancel</span>
            &nbsp;
            <span class="review-btn">Review</span>
        </td>
    </tr>
    <tr>
        <td>Netball Court</td>
        <td>23 Nov 2025<br>09:00 – 11:00</td>
        <td>
            <span class="cancel-btn">Cancel</span>
            &nbsp;
            <span class="review-btn">Review</span>
        </td>
    </tr>
</table>

<!-- FOOTER -->
<div class="footer">
    <div class="footer-top">
        <div class="footer-logo">
            <img src="../assets/img/pusatsukanlogo.png" alt="Pusat Sukan Logo">
        </div>

        <div class="footer-info">
            <strong class="footer-title">PEJABAT PENGARAH PUSAT SUKAN</strong>
            Stadium Universiti<br>
            Universiti Kebangsaan Malaysia<br>
            43600 Bangi, Selangor Darul Ehsan<br>
            No. Telefon : 03-8921-5306
        </div>

        <div class="footer-sdg">
            <img src="../assets/img/sdg.png" alt="SDG Logo">
        </div>
    </div>

    <div class="footer-bottom">
        Hakcipta © 2022 Universiti Kebangsaan Malaysia
    </div>
</div>

<script>
    // Animate counters
    let facilityCount = 0;
    let staffCount = 0;
    const facilityTarget = 15;
    const staffTarget = 47;
    const facilityElem = document.getElementById('facility-counter');
    const staffElem = document.getElementById('staff-counter');

    const increment = () => {
        if(facilityCount <= facilityTarget){
            facilityElem.textContent = facilityCount;
            facilityCount++;
        }
        if(staffCount <= staffTarget){
            staffElem.textContent = staffCount;
            staffCount++;
        }
        if(facilityCount <= facilityTarget || staffCount <= staffTarget){
            setTimeout(increment, 50);
        }
    };
    increment();
</script>

</body>
</html>
