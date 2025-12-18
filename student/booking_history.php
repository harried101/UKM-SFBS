<?php
session_start();

// SECURITY CHECK
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Student') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/db_connect.php';

// Student Details
$studentIdentifier = $_SESSION['user_id'] ?? '';
$studentName = 'Student';
$studentID = $studentIdentifier;

if ($studentIdentifier) {
    $stmtStudent = $conn->prepare("SELECT FirstName, LastName, UserIdentifier FROM users WHERE UserIdentifier = ?");
    $stmtStudent->bind_param("s", $studentIdentifier);
    $stmtStudent->execute();
    $resStudent = $stmtStudent->get_result();
    if ($rowStudent = $resStudent->fetch_assoc()) {
        $studentName = $rowStudent['FirstName'] . ' ' . $rowStudent['LastName'];
        $studentID = $rowStudent['UserIdentifier'];
    }
    $stmtStudent->close();
}

// Fetch Bookings
$userID = 0;
if ($studentIdentifier) {
    $stmtUser = $conn->prepare("SELECT UserID FROM users WHERE UserIdentifier=?");
    $stmtUser->bind_param("s", $studentIdentifier);
    $stmtUser->execute();
    $resUser = $stmtUser->get_result();
    if ($rowUser = $resUser->fetch_assoc()) $userID = $rowUser['UserID'];
    $stmtUser->close();
}

$bookings = [];
if ($userID > 0) {
    $stmt = $conn->prepare("
        SELECT f.Name AS FacilityName, b.StartTime, b.EndTime, b.Status AS BookingStatus
        FROM bookings b
        JOIN facilities f ON b.FacilityID = f.FacilityID
        WHERE b.UserID = ?
        ORDER BY b.StartTime DESC
    ");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()) $bookings[] = $row;
    $stmt->close();
}

// Determine current page for active navbar
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking History - UKM Sports Center</title>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
:root { --primary:#8a0d19; --bg-light:#f8fafc; }
body { font-family:'Inter',sans-serif; background-color:var(--bg-light); color: #1e293b; min-height:100vh; display:flex; flex-direction:column; }
h1,h2,h3{font-family:'Playfair Display',serif;}

/* ===== TABLE STYLES ===== */
.table-wrapper { width: 92%; margin: 40px auto 70px; background:white; border-radius:24px; border:1px solid #e5e7eb; box-shadow:0 20px 40px rgba(0,0,0,0.06); overflow:hidden; }
table { width:100%; border-collapse:collapse; }
th { background:var(--primary); color:white; padding:16px; font-size:13px; letter-spacing:1px; text-transform:uppercase; }
td { padding:16px; border-bottom:1px solid #f1f5f9; font-weight:500; }
tr:hover { background:#f8fafc; }

/* STATUS COLORS RED */
.status-confirmed, .status-pending, .status-cancelled, .status-rejected { color:var(--primary); font-weight:700; }
</style>
</head>

<body>

<!-- NAVBAR -->
<nav class="bg-white/90 backdrop-blur-md border-b border-slate-200 sticky top-0 z-50 transition-all duration-300">
    <div class="container mx-auto px-6 py-3 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <img src="../assets/img/ukm.png" alt="UKM Logo" class="h-12 w-auto">
            <div class="h-8 w-px bg-slate-200 hidden sm:block"></div>
            <img src="../assets/img/pusatsukanlogo.png" alt="Pusat Sukan Logo" class="h-12 w-auto hidden sm:block">
        </div>
        <div class="flex items-center gap-8">
            <a href="dashboard.php" class="<?= $currentPage=='dashboard.php' ? 'text-[#8a0d19] font-semibold relative' : 'text-slate-500 hover:text-[#8a0d19]' ?> flex items-center gap-2">
                <span>Home</span>
                <?php if($currentPage=='dashboard.php'): ?><span class="absolute -bottom-1 left-0 w-full h-0.5 bg-[#8a0d19] rounded-full"></span><?php endif; ?>
            </a>
            <a href="student_facilities.php" class="<?= $currentPage=='student_facilities.php' ? 'text-[#8a0d19] font-semibold relative' : 'text-slate-500 hover:text-[#8a0d19]' ?> flex items-center gap-2">
                <span>Facilities</span>
                <?php if($currentPage=='student_facilities.php'): ?><span class="absolute -bottom-1 left-0 w-full h-0.5 bg-[#8a0d19] rounded-full"></span><?php endif; ?>
            </a>
            <a href="booking_history.php" class="<?= $currentPage=='booking_history.php' ? 'text-[#8a0d19] font-semibold relative' : 'text-slate-500 hover:text-[#8a0d19]' ?> flex items-center gap-2">
                <span>History</span>
                <?php if($currentPage=='booking_history.php'): ?><span class="absolute -bottom-1 left-0 w-full h-0.5 bg-[#8a0d19] rounded-full"></span><?php endif; ?>
            </a>

            <div class="flex items-center gap-4 pl-6 border-l border-slate-200">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($studentName) ?></p>
                    <p class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold"><?= htmlspecialchars($studentID) ?></p>
                </div>
                <!-- PROFILE CLICK DROPDOWN -->
                <div class="relative" id="profileDropdown">
                    <img src="../assets/img/user.png" id="profileBtn" class="w-10 h-10 rounded-full border-2 border-white ring-2 ring-slate-100 object-cover cursor-pointer transition transform hover:scale-105">
                    <div id="dropdownMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-slate-100 hidden z-50 overflow-hidden">
                        <a href="../logout.php" onclick="return confirm('Are you sure you want to logout?');" class="block px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition flex items-center gap-2">
                            <i class="fa-solid fa-right-from-bracket"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- TITLE -->
<h1 class="text-center text-4xl font-bold mt-12 mb-8 text-[#8a0d19]">Booking History</h1>

<!-- TABLE -->
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
<?php if(!empty($bookings)): ?>
    <?php foreach($bookings as $b):
        $statusClass = 'status-' . strtolower($b['BookingStatus']);
    ?>
    <tr>
        <td><?= htmlspecialchars($b['FacilityName']) ?></td>
        <td><?= date("d M Y, h:i A", strtotime($b['StartTime'])) ?></td>
        <td><?= date("d M Y, h:i A", strtotime($b['EndTime'])) ?></td>
        <td class="<?= $statusClass ?>"><?= htmlspecialchars($b['BookingStatus']) ?></td>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
<tr>
    <td colspan="4" class="text-center text-gray-400 py-6">No booking history found.</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>

<!-- FOOTER -->
<footer class="bg-white border-t border-slate-200 py-12 mt-auto">
    <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
            <div class="space-y-4">
                <img src="../assets/img/pusatsukanlogo.png" alt="Pusat Sukan" class="h-14">
                <p class="text-xs text-slate-500 leading-relaxed max-w-xs">
                    Empowering students through sports excellence and state-of-the-art facilities management.
                </p>
            </div>
            <div>
                <h4 class="text-xs font-bold text-slate-900 uppercase tracking-widest mb-4">Quick Access</h4>
                <ul class="space-y-2 text-sm text-slate-600">
                    <li><a href="dashboard.php" class="hover:text-[#8a0d19] transition">Dashboard</a></li>
                    <li><a href="student_facilities.php" class="hover:text-[#8a0d19] transition">Browse Facilities</a></li>
                    <li><a href="booking_history.php" class="hover:text-[#8a0d19] transition">My Bookings</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-xs font-bold text-slate-900 uppercase tracking-widest mb-4">Contact Us</h4>
                <div class="text-sm text-slate-600 space-y-2">
                    <p class="font-medium">Stadium Universiti, UKM</p>
                    <p>43600 Bangi, Selangor</p>
                    <p class="text-[#8a0d19] font-bold mt-2"><i class="fa-solid fa-phone mr-2"></i> 03-8921 5306</p>
                </div>
            </div>
        </div>
        <div class="border-t border-slate-100 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-[10px] text-slate-400">© 2025 Universiti Kebangsaan Malaysia. All rights reserved.</p>
        </div>
    </div>
</footer>

<script>
const profileBtn = document.getElementById('profileBtn');
const dropdownMenu = document.getElementById('dropdownMenu');

profileBtn.addEventListener('click', function() {
    dropdownMenu.classList.toggle('hidden');
});

document.addEventListener('click', function(event) {
    if (!profileBtn.contains(event.target) && !dropdownMenu.contains(event.target)) {
        dropdownMenu.classList.add('hidden');
    }
});
</script>

</body>
</html>
