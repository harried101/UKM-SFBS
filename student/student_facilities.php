<?php
require_once 'includes/student_auth.php';




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
<!-- NAVBAR -->
<?php 
$nav_active = 'facilities';
include 'includes/navbar.php'; 
?>

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
<?php include 'includes/footer.php'; ?>

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

// Dropdown logic is now handled in includes/navbar.php
</script>
<script src="../assets/js/idle_timer.js.php"></script>
</body>
</html>
