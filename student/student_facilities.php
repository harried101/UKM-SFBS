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

// Fetch Types
$typesResult = $conn->query("SELECT DISTINCT Type FROM facilities WHERE Status IN ('Active','Maintenance')");
$types = [];
while($t = $typesResult->fetch_assoc()) $types[] = $t['Type'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Browse Facilities - UKM Sports Center</title>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
:root { --primary:#0b4d9d; --bg-light:#f8f9fa; }
body {
    font-family:'Inter',sans-serif;
    background-color:var(--bg-light);
    min-height:100vh;
    display:flex;
    flex-direction:column;
}
h1,h2,h3{font-family:'Playfair Display',serif;}
.filter-bar{
    background:white;padding:20px;border-radius:12px;
    box-shadow:0 4px 20px -1px rgba(0,0,0,0.08);
    margin-top:-60px;margin-bottom:40px;border:1px solid #eee;
}
</style>
</head>

<body>

<!-- NAVBAR -->
<nav class="bg-white/95 backdrop-blur-sm border-b border-gray-200 sticky top-0 z-50 shadow-md">
    <div class="container mx-auto px-6 py-3 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <img src="../assets/img/ukm.png" class="h-12">
            <div class="h-8 w-px bg-gray-300 hidden sm:block"></div>
            <img src="../assets/img/pusatsukanlogo.png" class="h-12 hidden sm:block">
        </div>
        <div class="flex items-center gap-6">
            <a href="dashboard.php" class="text-gray-600 hover:text-[#0b4d9d] font-medium flex items-center gap-2">
                <span class="p-2 rounded-full bg-gray-100"><i class="fa-solid fa-house"></i></span>
                <span class="hidden md:inline">Home</span>
            </a>
            <a href="student_facilities.php" class="text-[#0b4d9d] font-bold flex items-center gap-2">
                <span class="p-2 rounded-full bg-[#0b4d9d] text-white">
                    <i class="fa-solid fa-dumbbell"></i>
                </span> Facilities
            </a>
            <a href="dashboard.php?tab=history" class="text-gray-600 hover:text-[#0b4d9d]">History</a>

            <div class="flex items-center gap-3 pl-6 border-l">
                <div class="hidden sm:block text-right">
                    <p class="text-sm font-bold"><?= htmlspecialchars($studentName) ?></p>
                    <p class="text-xs text-gray-500"><?= htmlspecialchars($studentID) ?></p>
                </div>
                <img src="../assets/img/user.png" class="w-10 h-10 rounded-full border shadow">
            </div>
        </div>
    </div>
</nav>

<!-- HERO -->
<div class="w-full h-64 md:h-80 relative overflow-hidden shadow-md">
    <img src="../court.jpg" class="w-full h-full object-cover">
    <div class="absolute inset-0 bg-black/50"></div>
    <div class="absolute inset-0 flex flex-col items-center justify-center text-white text-center">
        <h1 class="text-5xl font-bold mb-3">Browse Facilities</h1>
        <p class="opacity-90">Find and book world-class sports facilities at UKM</p>
    </div>
</div>

<main class="container mx-auto px-6 pb-20 flex-grow">

    <!-- FILTER -->
    <div class="filter-bar max-w-5xl mx-auto">
        <form class="flex flex-col md:flex-row gap-4">
            <input id="searchInput" placeholder="Search facilities..."
                class="flex-grow p-3 rounded-lg border bg-gray-50">
            <select id="typeSelect" class="p-3 rounded-lg border bg-gray-50">
                <option value="">All Categories</option>
                <?php foreach($types as $t): ?>
                    <option value="<?= $t ?>"><?= $t ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- GRID -->
    <div id="facilitiesContainer" class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div class="col-span-full text-center py-12 text-gray-400">
            <i class="fa-solid fa-circle-notch fa-spin text-3xl text-[#0b4d9d]"></i>
            <p>Loading facilities...</p>
        </div>
    </div>
</main>

<!-- ✅ EXTENDED FOOTER -->
<footer class="bg-white border-t border-gray-200 mt-auto">
    <div class="container mx-auto px-6 py-12">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-10 mb-10">

            <!-- About -->
            <div>
                <img src="../assets/img/pusatsukanlogo.png" class="h-14 mb-4">
                <p class="text-sm text-gray-600 leading-relaxed">
                    Pusat Sukan Universiti Kebangsaan Malaysia manages all university
                    sports facilities, bookings, and athletic development programs.
                </p>
            </div>

            <!-- Links -->
            <div>
                <h4 class="text-sm font-bold uppercase mb-4">Quick Access</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="dashboard.php" class="hover:text-[#0b4d9d]">Dashboard</a></li>
                    <li><a href="student_facilities.php" class="hover:text-[#0b4d9d]">Facilities</a></li>
                    <li><a href="dashboard.php?tab=history" class="hover:text-[#0b4d9d]">Booking History</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div>
                <h4 class="text-sm font-bold uppercase mb-4">Contact</h4>
                <p class="text-sm text-gray-600">
                    Stadium Universiti, UKM<br>
                    43600 Bangi, Selangor<br>
                    <span class="text-[#0b4d9d] font-semibold">
                        <i class="fa-solid fa-phone mr-1"></i> 03-8921 5306
                    </span>
                </p>
            </div>
        </div>

        <div class="border-t pt-6 flex justify-between items-center">
            <img src="../assets/img/sdg.png" class="h-14 opacity-90">
            <p class="text-xs text-gray-400 text-right">
                © 2025 Universiti Kebangsaan Malaysia<br>All rights reserved
            </p>
        </div>
    </div>
</footer>

<script>
const searchInput=document.getElementById('searchInput');
const typeSelect=document.getElementById('typeSelect');
const container=document.getElementById('facilitiesContainer');

function fetchFacilities(){
    fetch(`student_facilities_fetch.php?search=${searchInput.value}&type=${typeSelect.value}`)
    .then(r=>r.text()).then(html=>container.innerHTML=html);
}
searchInput.addEventListener('input',fetchFacilities);
typeSelect.addEventListener('change',fetchFacilities);
fetchFacilities();
</script>

</body>
</html>
