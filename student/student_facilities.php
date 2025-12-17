<?php
session_start();

// SECURITY CHECK: Redirect if not logged in or role is not Student
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Student') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/db_connect.php';

// 1. Fetch Student Details (For Navbar)
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

// 2. Fetch Filter Options
$typesResult = $conn->query("SELECT DISTINCT Type FROM facilities WHERE Status IN ('Active', 'Maintenance')");
$types = [];
while($t = $typesResult->fetch_assoc()) {
    $types[] = $t['Type'];
}

if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}
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
:root {
    --primary: #8a0d19; /* UKM Red */
    --secondary: #006400; /* Dashboard Green */
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
h1, h2, h3 {
    font-family: 'Playfair Display', serif;
}
/* Hero Section */
.hero-section {
    background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('../court.jpg');
    background-size: cover;
    background-position: center;
    color: white;
    padding: 80px 0;
    margin-bottom: 40px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border-radius: 0 0 30px 30px;
}
/* Card Styling */
.facility-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05),0 8px 10px -6px rgba(0,0,0,0.01);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    border: 1px solid rgba(0,0,0,0.05);
}
.facility-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1),0 10px 10px -5px rgba(0,0,0,0.04);
    border-color: rgba(138,13,25,0.2);
}
.card-img-container {
    position: relative;
    height: 220px;
    overflow: hidden;
}
.card-img-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}
.facility-card:hover .card-img-container img {
    transform: scale(1.05);
}
.facility-type-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(255,255,255,0.95);
    color: var(--primary);
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}
/* Filter Bar */
.filter-bar {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 20px -1px rgba(0,0,0,0.08);
    margin-top: -60px;
    margin-bottom: 40px;
    border: 1px solid #eee;
    position: relative;
    z-index: 10;
}
/* Fade Animation */
.fade-in { animation: fadeIn 0.4s ease-in-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>
</head>
<body>

<!-- NAVBAR (Matches Dashboard Theme) -->
<nav class="bg-white/95 backdrop-blur-sm border-b border-gray-200 sticky top-0 z-50 shadow-md">
    <div class="container mx-auto px-6 py-3 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <img src="../assets/img/ukm.png" alt="UKM Logo" class="h-12 w-auto">
            <div class="h-8 w-px bg-gray-300 hidden sm:block"></div>
            <img src="../assets/img/pusatsukanlogo.png" alt="Pusat Sukan Logo" class="h-12 w-auto hidden sm:block">
        </div>
        <div class="flex items-center gap-6">
            <a href="dashboard.php" class="text-gray-600 hover:text-[#8a0d19] font-medium transition flex items-center gap-2 group">
                <span class="p-2 rounded-full bg-gray-100 group-hover:bg-[#8a0d19] group-hover:text-white transition shadow-sm">
                    <i class="fa-solid fa-house"></i>
                </span>
                <span class="hidden md:inline">Home</span>
            </a>
            <!-- Active State for Facilities -->
            <a href="student_facilities.php" class="text-[#8a0d19] font-medium transition">Facilities</a>
            <a href="dashboard.php" class="text-gray-600 hover:text-[#8a0d19] font-medium transition">History</a>

            <div class="flex items-center gap-3 pl-6 border-l border-gray-200">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($studentName); ?></p>
                    <p class="text-xs text-gray-500 uppercase tracking-wider"><?php echo htmlspecialchars($studentID); ?></p>
                </div>
                <div class="relative group">
                    <img src="../assets/img/user.png" alt="Profile" class="w-10 h-10 rounded-full border-2 border-white shadow-md object-cover cursor-pointer hover:scale-105 transition">
                    <!-- Dropdown -->
                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-100 hidden group-hover:block z-50">
                        <a href="../logout.php" onclick="return confirm('Are you sure you want to logout?');" class="block px-4 py-3 text-sm text-red-600 hover:bg-red-50 rounded-lg m-1">
                            <i class="fa-solid fa-right-from-bracket mr-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<div class="hero-section text-center">
    <div class="container mx-auto px-6">
        <h1 class="text-4xl md:text-6xl font-bold mb-4 tracking-tight drop-shadow-lg">UKM Sport Facility Booking</h1>
        <p class="text-lg md:text-xl opacity-90 max-w-2xl mx-auto font-light leading-relaxed">
            Seamless booking. Active living.
        </p>
    </div>
</div>

<main class="container mx-auto px-6 pb-20 flex-grow">
    
    <div class="filter-bar max-w-5xl mx-auto">
        <form id="searchForm" class="flex flex-col md:flex-row gap-4 items-center" onsubmit="return false;">
            <div class="flex-grow w-full relative">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input type="text" id="searchInput" name="search" placeholder="Search facilities..." 
                        class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-[#8a0d19] focus:ring-1 focus:ring-[#8a0d19] transition">
            </div>
            <div class="w-full md:w-56 relative">
                <i class="fa-solid fa-layer-group absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <select id="typeSelect" name="type" class="w-full pl-10 pr-8 py-3 bg-gray-50 border border-gray-200 rounded-lg appearance-none focus:outline-none focus:border-[#8a0d19] cursor-pointer">
                    <option value="">All Categories</option>
                    <?php foreach($types as $t): ?>
                        <option value="<?= $t ?>"><?= $t ?></