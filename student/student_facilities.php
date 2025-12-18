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
:root { --primary:#0b4d9d; --bg-light:#f8fafc; }
body {
    font-family:'Inter',sans-serif;
    background-color:var(--bg-light);
    color: #1e293b;
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
/* Ensure red buttons from fetch file work if needed */
.bg-\[\#8a0d19\] { background-color: #8a0d19 !important; }
.text-\[\#8a0d19\] { color: #8a0d19 !important; }
.hover\:bg-\[\#6d0a13\]:hover { background-color: #6d0a13 !important; }
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
            <a href="dashboard.php" class="text-slate-500 hover:text-[#8a0d19] font-medium transition hover:scale-105">
                Home
            </a>
            <!-- Active Facilities -->
            <a href="student_facilities.php" class="text-[#8a0d19] font-semibold transition flex items-center gap-2 group relative">
                <span>Facilities</span>
                <span class="absolute -bottom-1 left-0 w-full h-0.5 bg-[#8a0d19] rounded-full"></span>
            </a>
            <a href="booking_history.php" class="text-slate-500 hover:text-[#8a0d19] font-medium transition hover:scale-105">
                History
            </a>

            <div class="flex items-center gap-4 pl-6 border-l border-slate-200">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($studentName) ?></p>
                    <p class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold"><?= htmlspecialchars($studentID) ?></p>
                </div>

                <!-- LOGOUT DROPDOWN CLICK -->
                <div class="relative">
                    <img id="userAvatar" src="../assets/img/user.png" class="w-10 h-10 rounded-full border-2 border-white ring-2 ring-slate-100 object-cover cursor-pointer transition transform hover:scale-105">

                    <div id="logoutDropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-slate-100 hidden z-50 overflow-hidden">
                        <a href="../logout.php" onclick="return confirm('Are you sure you want to logout?');" class="block px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition flex items-center gap-2">
                            <i class="fa-solid fa-right-from-bracket"></i> Logout
                        </a>
                    </div>
                </div>
                <!-- END LOGOUT DROPDOWN -->
            </div>
        </div>
    </div>
</nav>

<!-- HERO -->
<div class="w-full h-64 md:h-80 relative overflow-hidden shadow-md">
    <img src="../court.jpg" class="w-full h-full object-cover">
    <div class="absolute inset-0 bg-black/50"></div>
    <div class="absolute inset-0 flex flex-col items-center justify-center text-white text-center">
        <h1 class="text-6xl font-bold mb-3">Browse Facilities</h1>
        <p class="opacity-90">Find and book sports facilities at UKM</p>
    </div>
</div>

<main class="container mx-auto px-6 py-12 flex-grow max-w-7xl">

    <!-- FILTER -->
    <div class="filter-bar mb-8 mt-[-60px] relative z-10">
        <form class="flex flex-col md:flex-row gap-4" onsubmit="return false;">
            <div class="flex-grow relative">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input id="searchInput" placeholder="Search facilities..."
                    class="w-full p-3 pl-10 rounded-lg border border-gray-200 bg-gray-50 focus:outline-none focus:border-[#8a0d19] focus:ring-1 focus:ring-[#8a0d19] transition">
            </div>
            <div class="relative w-full md:w-56">
                 <i class="fa-solid fa-layer-group absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <select id="typeSelect" class="w-full p-3 pl-10 rounded-lg border border-gray-200 bg-gray-50 focus:outline-none focus:border-[#8a0d19] appearance-none cursor-pointer">
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
            <i class="fa-solid fa-circle-notch fa-spin text-3xl text-[#8a0d19]"></i>
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
            <i class="fa-solid fa-circle-notch fa-spin text-4xl text-[#8a0d19] mb-3"></i>
            <p class="text-gray-500 font-medium">Loading booking system...</p>
        </div>

        <iframe id="calendarFrame" class="w-full h-full border-none opacity-0 transition-opacity duration-300" src=""></iframe>
    </div>
</div>

<!-- FOOTER -->
<footer class="bg-white border-t border-slate-200 py-12 mt-auto">
    <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
            <!-- Brand -->
            <div class="space-y-4">
                <img src="../assets/img/pusatsukanlogo.png" alt="Pusat Sukan" class="h-14">
                <p class="text-xs text-slate-500 leading-relaxed max-w-xs">
                    Empowering students through sports excellence and state-of-the-art facilities management.
                </p>
            </div>
            
            <!-- Quick Links -->
            <div>
                <h4 class="text-xs font-bold text-slate-900 uppercase tracking-widest mb-4">Quick Access</h4>
                <ul class="space-y-2 text-sm text-slate-600">
                    <li><a href="dashboard.php" class="hover:text-[#8a0d19] transition">Dashboard</a></li>
                    <li><a href="student_facilities.php" class="hover:text-[#8a0d19] transition">Browse Facilities</a></li>
                    <li><a href="dashboard.php?tab=history" class="hover:text-[#8a0d19] transition">My Bookings</a></li>
                </ul>
            </div>

            <!-- Contact -->
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

    // --- LOGOUT DROPDOWN CLICK ---
    const userAvatar = document.getElementById('userAvatar');
    const logoutDropdown = document.getElementById('logoutDropdown');

    userAvatar.addEventListener('click', () => {
        logoutDropdown.classList.toggle('hidden');
    });

    document.addEventListener('click', (e) => {
        if (!userAvatar.contains(e.target) && !logoutDropdown.contains(e.target)) {
            logoutDropdown.classList.add('hidden');
        }
    });
</script>

</body>
</html>
