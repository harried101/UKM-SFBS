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
    border:1px solid #eee;
}
/* Ensure blue buttons from fetch file work */
.bg-\[\#8a0d19\] { background-color: #0b4d9d !important; }
.text-\[\#8a0d19\] { color: #0b4d9d !important; }
.hover\:bg-\[\#6d0a13\]:hover { background-color: #083a75 !important; }
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
            <a href="dashboard.php" class="text-gray-600 hover:text-[#0b4d9d] font-medium transition">
                Home
            </a>
            <a href="student_facilities.php" class="text-[#0b4d9d] font-bold transition">
                Facilities
            </a>
            <a href="dashboard.php?tab=history" class="text-gray-600 hover:text-[#0b4d9d] font-medium transition">
                History
            </a>

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

<!-- MAIN CONTENT (No Banner) -->
<main class="container mx-auto px-6 py-10 flex-grow">

    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-[#0b4d9d] mb-1 font-serif">Browse Facilities</h1>
            <p class="text-gray-500">Find and book world-class sports facilities at UKM.</p>
        </div>
    </div>

    <!-- FILTER -->
    <div class="filter-bar mb-8">
        <form class="flex flex-col md:flex-row gap-4" onsubmit="return false;">
            <div class="flex-grow relative">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input id="searchInput" placeholder="Search facilities..."
                    class="w-full p-3 pl-10 rounded-lg border border-gray-200 bg-gray-50 focus:outline-none focus:border-[#0b4d9d] focus:ring-1 focus:ring-[#0b4d9d] transition">
            </div>
            <div class="relative w-full md:w-56">
                 <i class="fa-solid fa-layer-group absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <select id="typeSelect" class="w-full p-3 pl-10 rounded-lg border border-gray-200 bg-gray-50 focus:outline-none focus:border-[#0b4d9d] appearance-none cursor-pointer">
                    <option value="">All Categories</option>
                    <?php foreach($types as $t): ?>
                        <option value="<?= $t ?>"><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
                <i class="fa-solid fa-chevron-down absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
            </div>
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

<!-- MODAL (POPUP) CODE -->
<div id="calendarModal" class="fixed inset-0 bg-black/50 hidden z-[9999] flex items-center justify-center backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-[95%] max-w-4xl h-[90vh] md:h-[600px] relative flex flex-col overflow-hidden animate-fade-in">
        
        <button onclick="closeCalendar()" class="absolute top-4 right-4 z-10 bg-gray-100 hover:bg-red-100 text-gray-600 hover:text-red-600 rounded-full w-8 h-8 flex items-center justify-center transition-colors">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div id="calendarLoader" class="absolute inset-0 flex flex-col items-center justify-center bg-white z-0">
            <i class="fa-solid fa-circle-notch fa-spin text-4xl text-[#0b4d9d] mb-3"></i>
            <p class="text-gray-500 font-medium">Loading booking system...</p>
        </div>

        <iframe id="calendarFrame" class="w-full h-full border-none opacity-0 transition-opacity duration-300" src=""></iframe>
    </div>
</div>

<!-- FOOTER -->
<footer class="bg-white border-t border-gray-200 mt-auto">
    <div class="container mx-auto px-6 py-12">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-10 mb-8">

            <!-- About -->
            <div>
                <img src="../assets/img/pusatsukanlogo.png" class="h-14 mb-4">
                <p class="text-sm text-gray-600 leading-relaxed">
                    Pusat Sukan Universiti Kebangsaan Malaysia manages all university
                    sports facilities, bookings, and athletic development programs.
                </p>
            </div>

            <!-- Contact -->
            <div class="md:text-right">
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

        <div class="border-t pt-6 flex flex-col md:flex-row justify-between items-center gap-4">
            <img src="../assets/img/sdg.png" class="h-12 opacity-90">
            <p class="text-xs text-gray-400 text-right">
                © 2025 Universiti Kebangsaan Malaysia<br>All rights reserved
            </p>
        </div>
    </div>
</footer>

<script>
    const searchInput = document.getElementById('searchInput');
    const typeSelect = document.getElementById('typeSelect');
    const facilitiesContainer = document.getElementById('facilitiesContainer');

    function fetchFacilities() {
        const search = encodeURIComponent(searchInput.value);
        const type = encodeURIComponent(typeSelect.value);

        fetch(`student_facilities_fetch.php?search=${search}&type=${type}`)
            .then(res => res.text())
            .then(html => facilitiesContainer.innerHTML = html)
            .catch(err => console.error(err));
    }

    searchInput.addEventListener('input', () => fetchFacilities());
    typeSelect.addEventListener('change', () => fetchFacilities());

    // Initial Load
    fetchFacilities();

    // --- MODAL FUNCTIONS ---
    function openCalendar(facilityID) {
        const modal = document.getElementById("calendarModal");
        const loader = document.getElementById("calendarLoader");
        const frame = document.getElementById("calendarFrame");

        if(!modal || !frame) return;

        modal.classList.remove("hidden");
        loader.classList.remove("hidden");
        frame.classList.add("opacity-0");
        
        // Loads book.php
        frame.src = "book.php?facility_id=" + encodeURIComponent(facilityID);

        frame.onload = () => {
            loader.classList.add("hidden");
            frame.classList.remove("opacity-0");
        };
    }

    function closeCalendar() {
        const modal = document.getElementById("calendarModal");
        const frame = document.getElementById("calendarFrame");
        if(modal) {
            modal.classList.add("hidden");
            if(frame) frame.src = "";
        }
    }

    document.getElementById("calendarModal").addEventListener("click", function(e) {
        if (e.target === this) {
            closeCalendar();
        }
    });
</script>

</body>
</html>