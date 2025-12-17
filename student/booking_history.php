<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Student') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/db_connect.php';

// Fetch student info
$studentIdentifier = $_SESSION['user_id'] ?? '';
$studentName = 'Student';
$studentID = $studentIdentifier;

if ($studentIdentifier) {
    $stmtStudent = $conn->prepare("SELECT FirstName, LastName, UserIdentifier, UserID FROM users WHERE UserIdentifier = ?");
    $stmtStudent->bind_param("s", $studentIdentifier);
    $stmtStudent->execute();
    $resStudent = $stmtStudent->get_result();
    if ($row = $resStudent->fetch_assoc()) {
        $studentName = $row['FirstName'] . ' ' . $row['LastName'];
        $studentID = $row['UserIdentifier'];
        $userID = $row['UserID'];
    }
    $stmtStudent->close();
}

// Fetch booking history
$bookings = [];
$stmtBooking = $conn->prepare("
    SELECT f.Name AS FacilityName, b.StartTime, b.EndTime, b.Status
    FROM bookings b
    JOIN facilities f ON b.FacilityID = f.FacilityID
    WHERE b.UserID = ?
    ORDER BY b.StartTime DESC
");
$stmtBooking->bind_param("i", $userID);
$stmtBooking->execute();
$resBooking = $stmtBooking->get_result();
while ($row = $resBooking->fetch_assoc()) {
    $bookings[] = $row;
}
$stmtBooking->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking History – UKM Sports Center</title>
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
}
.nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.5); }
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

/* PAGE TITLE */
.section-title {
    text-align: center;
    margin: 30px 0;
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
.status-completed { color: green; font-weight: 700; }
.status-canceled { color: red; font-weight: 700; }
.status-pending { color: orange; font-weight: 700; }
.status-confirmed { color: blue; font-weight: 700; }

/* FOOTER */
.footer { background: white; border-top: 1px solid #eee; margin-top: auto; }
.footer-top { display: flex; justify-content: space-between; align-items: center; padding: 30px 15px; flex-wrap: wrap; }
.footer-logo img { height: 100px; }
.footer-info { text-align: center; flex: 1; line-height: 1.6; }
.footer-title { font-size: 22px; margin-bottom: 10px; display: block; }
.footer-sdg img { height: 180px; }
.footer-bottom { background: var(--primary); color: white; text-align: center; padding: 12px 15px; font-size: 13px; }

@media screen and (max-width:768px) {
    .footer-top { flex-direction: column; gap: 20px; }
    .footer-logo img, .footer-sdg img { height: 100px; }
}
</style>
</head>
<body>

<!-- NAVBAR (EXACTLY LIKE DASHBOARD) -->
<nav class="d-flex justify-content-between align-items-center px-4 py-2">
    <div class="nav-logo d-flex align-items-center gap-3">
        <img src="../assets/img/ukm.png" alt="UKM Logo" height="45">
        <img src="../assets/img/pusatsukanlogo.png" alt="Pusat Sukan Logo" height="45">
    </div>

    <div class="d-flex align-items-center gap-4">
        <a class="nav-link" href="dashboard.php">Home</a>
        <a class="nav-link" href="student_facilities.php">Facilities</a>
        <a class="nav-link active" href="booking_history.php">Booking History</a>

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



<!-- PAGE TITLE -->
<h1 class="section-title">Booking History</h1>

<!-- BOOKING TABLE -->
<div class="table-wrapper">
<table>
<thead>
<tr>
<th>Facility</th>
<th>Start Time</th>
<th>End Time</th>
<th>Status</th>
</tr>
</thead>
<tbody>
<?php if(count($bookings) > 0): ?>
    <?php foreach($bookings as $b): 
        $statusClass = 'status-' . strtolower($b['Status']); ?>
    <tr>
        <td><?php echo htmlspecialchars($b['FacilityName']); ?></td>
        <td><?php echo date("d M Y, H:i", strtotime($b['StartTime'])); ?></td>
        <td><?php echo date("d M Y, H:i", strtotime($b['EndTime'])); ?></td>
        <td class="<?php echo $statusClass; ?>"><?php echo htmlspecialchars($b['Status']); ?></td>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
<tr><td colspan="4" class="text-center py-6 text-gray-500">No booking history found.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

<!-- FOOTER -->
<div class="footer">
    <div class="footer-bottom">
        Hakcipta © 2025 Universiti Kebangsaan Malaysia
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
