<?php
session_start();

// Redirect if not logged in or not a student
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Student') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/db_connect.php';

// FIX: Set Timezone to match Database (Malaysia Time)
date_default_timezone_set('Asia/Kuala_Lumpur');

// 1. Fetch Student Details
$studentIdentifier = $_SESSION['user_id'] ?? '';
$studentName = 'Student';
$studentID = $studentIdentifier;
$db_numeric_id = 0;

if ($studentIdentifier) {
    $stmtStudent = $conn->prepare("SELECT UserID, FirstName, LastName, UserIdentifier FROM users WHERE UserIdentifier = ?");
    $stmtStudent->bind_param("s", $studentIdentifier);
    $stmtStudent->execute();
    $resStudent = $stmtStudent->get_result();
    if ($rowStudent = $resStudent->fetch_assoc()) {
        $studentName = $rowStudent['FirstName'] . ' ' . $rowStudent['LastName'];
        $studentID = $rowStudent['UserIdentifier'];
        $db_numeric_id = $rowStudent['UserID']; // Needed for bookings query
    }
    $stmtStudent->close();
}

// 2. Fetch All Bookings (Separated into Upcoming & History)
$upcoming = [];
$history = [];
$now = new DateTime(); // Current time in KL (due to timezone set above)

if ($db_numeric_id > 0) {
    $sql = "SELECT b.BookingID, b.StartTime, b.EndTime, b.Status, f.Name as FacilityName, f.Type, f.Location
            FROM bookings b
            JOIN facilities f ON b.FacilityID = f.FacilityID
            WHERE b.UserID = ? 
            ORDER BY b.StartTime DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $db_numeric_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $start = new DateTime($row['StartTime']);
        
        // Logic: Upcoming if future date AND Status is active
        if ($start > $now && in_array($row['Status'], ['Pending', 'Approved'])) {
            $upcoming[] = $row;
        } else {
            // Everything else (Past, Cancelled, Rejected) goes to history
            $history[] = $row;
        }
    }
    $stmt->close();
}

// 3. Fetch Real Facility Count
$facCountResult = $conn->query("SELECT COUNT(*) FROM facilities WHERE Status='Active'");
$realFacilityCount = $facCountResult ? $facCountResult->fetch_row()[0] : 0;

if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard – UKM Sports Center</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
:root {
    --primary: #0b4d9d;
    --bg-light: #f8f9fa;
}
body {
    font-family: 'Inter', sans-serif;
    background-color: var(--bg-light);
    color: #333;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}
h1,h2,h3 { font-family: 'Playfair Display', serif; }

/* NAVBAR */
nav {
    background: #bfd9dc;
    padding: 10px 40px;
    border-bottom-left-radius: 25px;
    border-bottom-right-radius: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 4px 6px rgba(0,0,0,0.3);
}
.nav-logo img { height: 65px; }
.nav-link {
    color: #071239ff;
    font-weight: 600;
    padding: 8px 18px;
    border-radius: 12px;
    transition: 0.3s ease;
    text-decoration: none;
}
.nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.5); color: #071239ff; }
.dropdown-menu { border-radius: 12px; background: #bfd9dc; box-shadow: 0 4px 8px rgba(0,0,0,0.2); padding: 5px; }
.dropdown-item { color: #071239ff; font-weight: 600; padding: 8px 18px; border-radius: 10px; transition: 0.3s ease; }
.dropdown-item:hover { background: rgba(255,255,255,0.5); color: #071239ff; }

/* BANNER IMAGE */
.banner img {
    width: 100%;
    max-height: 250px;
    object-fit: cover;
    display: block;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

/* WELCOME TEXT */
.welcome-text {
    text-align: center;
    margin: 30px auto 50px auto;
    font-size: 36px;
    font-weight: 900;
    color: var(--primary);
}

/* TABLE */
.table-wrapper {
    width: 90%;
    margin: 0 auto 50px;
    overflow: hidden;
    border-radius: 16px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
    background: white;
}
table { width: 100%; border-collapse: collapse; }
th, td { padding: 14px; text-align: center; border-bottom: 1px solid #eee; }
th { background: var(--primary); color: white; text-transform: uppercase; }
button.cancel-btn { background: #dc3545; color: white; padding: 6px 14px; font-weight: 600; border-radius: 6px; border:none; transition: 0.3s; }
button.review-btn { background: #21b32d; color: white; padding: 6px 14px; font-weight: 600; border-radius: 6px; border:none; transition: 0.3s; }
button.cancel-btn:hover { background: #bb2d3b; }
button.review-btn:hover { background: #1a8b23; }

/* COUNTERS */
.counters { display: flex; justify-content: center; gap: 80px; margin: 30px auto 50px; flex-wrap: wrap; text-align: center; }
.counter-number { font-size: 48px; font-weight: 700; color: var(--primary); }
.counter-label { font-size: 18px; margin-top: 5px; color: #004d7a; }

/* INFO BOX */
.info-text {
    width: 85%;
    margin: 40px auto;
    background: white;
    padding: 25px 30px;
    border-radius: 16px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
    font-size: 16px;
    line-height: 1.7;
    text-align: center;
    color: #004d7a;
}
.info-text h2 { font-size: 28px; margin-bottom: 15px; font-weight: bold; color: var(--primary); }

/* FOOTER */
.footer { background: white; border-top: 1px solid #eee; margin-top: auto; }
.footer-top { display: flex; justify-content: space-between; align-items: center; padding: 30px 15px; flex-wrap: wrap; }
.footer-logo img { height: 100px; }
.footer-info { text-align: center; flex: 1; line-height: 1.6; }
.footer-title { font-size: 22px; margin-bottom: 10px; display: block; }
.footer-sdg img { height: 180px; }
.footer-bottom { background: var(--primary); color: white; text-align: center; padding: 12px 15px; font-size: 13px; }

@media screen and (max-width:768px) {
    .counters { flex-direction: column; gap: 30px; }
    .footer-top { flex-direction: column; gap: 20px; }
    .footer-logo img, .footer-sdg img { height: 100px; }
}

/* Tabs for History/Upcoming */
.tab-btn {
    border: none;
    background: none;
    padding: 10px 20px;
    font-weight: 600;
    color: #6c757d;
    cursor: pointer;
    border-bottom: 3px solid transparent;
}
.tab-btn.active {
    color: var(--primary);
    border-bottom: 3px solid var(--primary);
}
</style>
</head>
<body>

<!-- NAVBAR (Admin-style) -->
<nav class="d-flex justify-content-between align-items-center px-4 py-2">
    <div class="nav-logo d-flex align-items-center gap-3">
        <img src="../assets/img/ukm.png" alt="UKM Logo" height="45">
        <img src="../assets/img/pusatsukanlogo.png" alt="Pusat Sukan Logo" height="45">
    </div>

    <div class="d-flex align-items-center gap-4">
        <a class="nav-link active" href="#">Home</a>
        <a class="nav-link" href="student_facilities.php">Facilities</a>
        <!-- Booking History link triggers tab switch via JS if on same page -->
        <button onclick="switchTab('history')" class="nav-link bg-transparent border-0">Booking History</button>

        <div class="dropdown">
            <a href="#" class="d-flex align-items-center gap-2 text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="../assets/img/user.png" class="rounded-circle" style="width:45px; height:45px;">
                <div style="line-height:1.2; text-align: left;">
                    <div class="fw-bold" style="color:#071239ff;"><?php echo htmlspecialchars($studentName); ?></div>
                    <small class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars($studentID); ?></small>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item text-danger" href="../logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- BANNER -->
<div class="banner container mt-4">
    <img src="../assets/img/psukan.jpg" alt="Pusat Sukan">
</div>

<!-- WELCOME TEXT BELOW BANNER -->
<h1 class="welcome-text">Welcome, <?php echo htmlspecialchars($studentName); ?>!</h1>

<!-- BOOKINGS SECTION -->
<div class="container mb-5">
    <div class="d-flex justify-content-center mb-4 border-bottom">
        <button onclick="switchTab('upcoming')" id="tab-upcoming" class="tab-btn active">Upcoming Bookings</button>
        <button onclick="switchTab('history')" id="tab-history" class="tab-btn">History</button>
    </div>

    <!-- UPCOMING TABLE -->
    <div id="view-upcoming" class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Facility</th>
                    <th>Date / Time</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($upcoming)): ?>
                    <tr>
                        <td colspan="4" class="py-5 text-muted">
                            No upcoming bookings found. <br>
                            <a href="student_facilities.php" class="text-decoration-underline fw-bold" style="color:var(--primary);">Book a facility now</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($upcoming as $bk): 
                        $startObj = new DateTime($bk['StartTime']);
                        $endObj = new DateTime($bk['EndTime']);
                    ?>
                    <tr>
                        <td class="fw-bold" style="color:var(--primary);"><?php echo htmlspecialchars($bk['FacilityName']); ?></td>
                        <td>
                            <div class="fw-semibold"><?php echo $startObj->format('d M Y'); ?></div>
                            <small class="text-muted"><?php echo $startObj->format('h:i A') . ' - ' . $endObj->format('h:i A'); ?></small>
                        </td>
                        <td>
                            <span class="badge <?php echo ($bk['Status']=='Approved') ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                <?php echo $bk['Status']; ?>
                            </span>
                        </td>
                        <td>
                            <button class="cancel-btn" onclick="cancelBooking(<?php echo $bk['BookingID']; ?>)">Cancel</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- HISTORY TABLE -->
    <div id="view-history" class="table-wrapper" style="display:none;">
        <table>
            <thead>
                <tr>
                    <th>Facility</th>
                    <th>Date / Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($history)): ?>
                    <tr>
                        <td colspan="3" class="py-5 text-muted">No booking history available.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($history as $bk): 
                        $startObj = new DateTime($bk['StartTime']);
                        $endObj = new DateTime($bk['EndTime']);
                        $statusColor = 'bg-secondary';
                        if($bk['Status']=='Approved') $statusColor='bg-success';
                        if($bk['Status']=='Rejected') $statusColor='bg-danger';
                    ?>
                    <tr>
                        <td class="fw-bold text-muted"><?php echo htmlspecialchars($bk['FacilityName']); ?></td>
                        <td class="text-muted">
                            <?php echo $startObj->format('d M Y'); ?><br>
                            <small><?php echo $startObj->format('h:i A'); ?></small>
                        </td>
                        <td>
                            <span class="badge <?php echo $statusColor; ?>"><?php echo $bk['Status']; ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
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

<!-- INFO TEXT -->
<div class="info-text">
<h2>Let’s Get to Know UKM Sports Center</h2>
<p>The UKM Sports Center started on 1 November 1974 with a Sports Officer from the Ministry of Education who managed sports activities for students and staff. In 1981 and 1982, UKM participated in the ASEAN University Games. In 2008, the Sports Unit was upgraded to the Sports Center, and in 2010, a director was appointed. Today, the center has 47 staff members.</p>
</div>

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
        Hakcipta © 2025 Universiti Kebangsaan Malaysia
    </div>
</div>

<!-- LOGIC SCRIPTS -->
<script>
// 1. Counter Logic
let facilityCount = 0;
let staffCount = 0;
const facilityTarget = <?php echo $realFacilityCount > 0 ? $realFacilityCount : 15; ?>;
const staffTarget = 47;
const facilityElem = document.getElementById('facility-counter');
const staffElem = document.getElementById('staff-counter');

function incrementCounters() {
    let done = true;
    if(facilityCount < facilityTarget){ 
        facilityCount++; 
        facilityElem.textContent = facilityCount; 
        done = false; 
    }
    if(staffCount < staffTarget){ 
        staffCount++; 
        staffElem.textContent = staffCount; 
        done = false; 
    }
    if(!done){
        setTimeout(incrementCounters, 50);
    }
}
window.onload = incrementCounters;

// 2. Tab Logic
function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    
    document.getElementById('view-upcoming').style.display = 'none';
    document.getElementById('view-history').style.display = 'none';
    document.getElementById('view-' + tab).style.display = 'block';
}

// Check URL param
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('tab') === 'history') {
    switchTab('history');
}

// 3. Cancel Booking Logic
function cancelBooking(id) {
    if(!confirm("Are you sure you want to cancel this booking? This action cannot be undone.")) return;

    const formData = new FormData();
    formData.append('booking_id', id);

    fetch('cancel_booking.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            alert("Booking cancelled successfully.");
            location.reload(); 
        } else {
            alert("Error: " + (data.message || "Unknown error"));
        }
    })
    .catch(err => {
        console.error(err);
        alert("Network error. Please try again.");
    });
}
</script>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>