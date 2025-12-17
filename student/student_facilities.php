<?php
session_start();

// SECURITY CHECK: Redirect if not logged in or role is not Student
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Student') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/db_connect.php';

// 1. Fetch Student Details (For Navbar consistency)
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

/* Custom Scrollbar */
.scrollbar-hide::-webkit-scrollbar { display: none; }

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
            <a href="student_facilities.php" class="text-[#8a0d19] font-bold transition flex items-center gap-2">
                <span class="p-2 rounded-full bg-[#8a0d19] text-white shadow-sm">
                    <i class="fa-solid fa-dumbbell"></i>
                </span>
                Facilities
            </a>
            
            <!-- Link triggers history tab on dashboard -->
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

<!-- HERO BANNER -->
<div class="w-full h-64 md:h-80 overflow-hidden relative shadow-md group">
    <img src="../court.jpg" alt="Sports Court" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-black/20"></div>
    <div class="absolute inset-0 flex flex-col items-center justify-center text-white text-center px-4 z-10">
        <h1 class="text-4xl md:text-6xl font-bold mb-4 tracking-tight drop-shadow-lg font-serif">Browse Facilities</h1>
        <p class="text-lg md:text-xl opacity-90 max-w-2xl font-light leading-relaxed">
            Find and book world-class sports facilities at UKM.
        </p>
    </div>
</div>

<main class="container mx-auto px-6 pb-20 flex-grow relative z-20">
    
    <!-- Filter Section -->
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
                        <option value="<?= $t ?>"><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
                <i class="fa-solid fa-chevron-down absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
            </div>
        </form>
    </div>

    <!-- Facilities Grid -->
    <div id="facilitiesContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 fade-in">
        <!-- Facilities will be loaded here via AJAX -->
        <div class="col-span-full text-center py-12 text-gray-400">
            <i class="fa-solid fa-circle-notch fa-spin text-3xl text-[#8a0d19] mb-3"></i>
            <p>Loading facilities...</p>
        </div>
    </div>

</main>

<!-- FOOTER (Matches Dashboard Theme) -->
<footer class="bg-white border-t border-gray-200 py-10 mt-auto">
    <div class="container mx-auto px-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-8">
            <!-- Logo & Address -->
            <div class="flex items-start gap-4 max-w-md">
                <img src="../assets/img/pusatsukanlogo.png" alt="Pusat Sukan Logo" class="h-16 w-auto">
                <div class="text-sm text-gray-600 leading-relaxed">
                    <strong class="block text-gray-900 text-base mb-1">PEJABAT PENGARAH PUSAT SUKAN</strong>
                    Stadium Universiti, Universiti Kebangsaan Malaysia<br>
                    43600 Bangi, Selangor Darul Ehsan<br>
                    <span class="mt-1 block"><i class="fa-solid fa-phone mr-1"></i> 03-8921-5306</span>
                </div>
            </div>
            <!-- SDG Logo -->
            <div>
                <img src="../assets/img/sdg.png" alt="SDG Logo" class="h-20 w-auto opacity-90">
            </div>
        </div>
        <div class="border-t border-gray-100 mt-8 pt-8 text-center text-sm text-gray-500">
            &copy; 2025 Universiti Kebangsaan Malaysia. All rights reserved.
        </div>
    </div>
</footer>

<!-- MODAL (POPUP) CODE - CRITICAL FOR FUNCTIONALITY -->
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