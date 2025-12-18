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

<style>
:root {
    --primary: #8a0d19; /* UKM Red */
    --secondary: #006400;
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

/* Custom Scrollbar */
.scrollbar-hide::-webkit-scrollbar { display: none; }

/* Tabs Animation */
.tab-btn { position: relative; color: #6b7280; transition: all 0.3s; }
.tab-btn::after {
    content: ''; position: absolute; bottom: -2px; left: 0; width: 0%; height: 3px;
    background-color: var(--primary); transition: width 0.3s;
}
.tab-btn.active { color: var(--primary); font-weight: 700; }
.tab-btn.active::after { width: 100%; }

/* Fade Animation */
.fade-in { animation: fadeIn 0.4s ease-in-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="bg-white/95 backdrop-blur-sm border-b border-gray-200 sticky top-0 z-50 shadow-md">
    <div class="container mx-auto px-6 py-3 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <img src="../assets/img/ukm.png" alt="UKM Logo" class="h-12 w-auto">
            <div class="h-8 w-px bg-gray-300 hidden sm:block"></div>
            <img src="../assets/img/pusatsukanlogo.png" alt="Pusat Sukan Logo" class="h-12 w-auto hidden sm:block">
        </div>
        <div class="flex items-center gap-6">
            <!-- Active Home (No Icon) -->
            <a href="dashboard.php" class="text-[#8a0d19] font-bold transition flex items-center gap-2 group">
                Home
            </a>
            <a href="student_facilities.php" class="text-gray-600 hover:text-[#8a0d19] font-medium transition">Facilities</a>
            <!-- Link triggers history tab -->
            <button onclick="switchTab('history')" class="text-gray-600 hover:text-[#8a0d19] font-medium transition">History</button>

            <div class="flex items-center gap-3 pl-6 border-l border-gray-200">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($studentName); ?></p>
                    <p class="text-xs text-gray-500 uppercase tracking-wider"><?php echo htmlspecialchars($studentID); ?></p>
                </div>
                <div class="relative group">
                    <img src="../assets/img/user.png" alt="Profile" class="w-10 h-10 rounded-full border-2 border-white shadow-md object-cover cursor-pointer hover:scale-105 transition">
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

<!-- HERO BANNER (Image Only) -->
<div class="w-full h-64 md:h-72 overflow-hidden relative shadow-md group">
    <img src="../assets/img/psukan.jpg" alt="Pusat Sukan" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
    <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
</div>

<!-- WELCOME SECTION (Overlapping Card) -->
<div class="container mx-auto px-6 -mt-16 relative z-20 mb-12">
    <div class="bg-white rounded-2xl shadow-xl p-8 flex flex-col md:flex-row items-center justify-between gap-6 border border-gray-100 overflow-hidden relative">
        <!-- Decorative bg pattern -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-red-50 rounded-full mix-blend-multiply filter blur-3xl opacity-50 -translate-y-1/2 translate-x-1/2"></div>
        <div class="relative z-10">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800 font-serif mb-2">
                Welcome back, <span class="text-[#8a0d19]"><?php echo htmlspecialchars($studentName); ?></span>
            </h1>
            <p class="text-gray-500 text-lg">Ready to stay active? Check your schedule below or book a new facility.</p>
        </div>
        <a href="student_facilities.php" class="relative z-10 bg-[#8a0d19] text-white px-8 py-3.5 rounded-xl shadow-lg hover:bg-[#6d0a13] transition font-medium whitespace-nowrap flex items-center gap-2 transform hover:-translate-y-0.5 active:scale-95 group">
            <i class="fa-solid fa-plus-circle group-hover:rotate-90 transition-transform"></i> Book New Facility
        </a>
    </div>
</div>

<main class="container mx-auto px-6 pb-12 flex-grow max-w-6xl relative z-30">

    <!-- STATS / COUNTERS -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
        <!-- Stat 1 -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-5 hover:shadow-lg transition-all duration-300 group hover:-translate-y-1 relative overflow-hidden">
            <div class="absolute right-0 top-0 w-24 h-24 bg-red-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="w-14 h-14 rounded-xl bg-red-100 flex items-center justify-center text-[#8a0d19] text-2xl relative z-10 group-hover:bg-[#8a0d19] group-hover:text-white transition-colors">
                <i class="fa-solid fa-dumbbell"></i>
            </div>
            <div class="relative z-10">
                <div class="text-3xl font-bold text-gray-800 font-serif" id="facility-counter">0</div>
                <div class="text-xs text-gray-500 uppercase font-bold tracking-wider">Active Facilities</div>
            </div>
        </div>
        <!-- Stat 2 -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-5 hover:shadow-lg transition-all duration-300 group hover:-translate-y-1 relative overflow-hidden">
             <div class="absolute right-0 top-0 w-24 h-24 bg-blue-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="w-14 h-14 rounded-xl bg-blue-100 flex items-center justify-center text-blue-700 text-2xl relative z-10 group-hover:bg-blue-700 group-hover:text-white transition-colors">
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="relative z-10">
                <div class="text-3xl font-bold text-gray-800 font-serif" id="staff-counter">0</div>
                <div class="text-xs text-gray-500 uppercase font-bold tracking-wider">Staff Members</div>
            </div>
        </div>
        <!-- Stat 3 -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-5 hover:shadow-lg transition-all duration-300 group hover:-translate-y-1 relative overflow-hidden">
            <div class="absolute right-0 top-0 w-24 h-24 bg-green-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="w-14 h-14 rounded-xl bg-green-100 flex items-center justify-center text-green-700 text-2xl relative z-10 group-hover:bg-green-700 group-hover:text-white transition-colors">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <div class="relative z-10">
                <div class="text-3xl font-bold text-gray-800 font-serif"><?php echo count($upcoming) + count($history); ?></div>
                <div class="text-xs text-gray-500 uppercase font-bold tracking-wider">Total Bookings</div>
            </div>
        </div>
    </div>

    <!-- DASHBOARD CARD -->
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden min-h-[500px]">
        
        <!-- Tabs Header -->
        <div class="flex items-center gap-8 px-8 pt-6 border-b border-gray-100 bg-white">
            <button onclick="switchTab('upcoming')" id="tab-upcoming" class="tab-btn active pb-4 px-2 text-sm font-bold uppercase tracking-wide flex items-center gap-2">
                Upcoming <span class="bg-[#8a0d19] text-white px-2 py-0.5 rounded-full text-xs font-bold"><?php echo count($upcoming); ?></span>
            </button>
            <button onclick="switchTab('history')" id="tab-history" class="tab-btn pb-4 px-2 text-sm font-bold uppercase tracking-wide flex items-center gap-2">
                History <span class="bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full text-xs font-bold"><?php echo count($history); ?></span>
            </button>
        </div>

        <!-- TAB CONTENT: UPCOMING -->
        <div id="view-upcoming" class="p-0 fade-in">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider font-semibold border-b border-gray-100">
                        <tr>
                            <th class="p-6">Facility Details</th>
                            <th class="p-6">Schedule</th>
                            <th class="p-6 text-center">Status</th>
                            <th class="p-6 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-gray-700">
                        <?php if (empty($upcoming)): ?>
                            <tr>
                                <td colspan="4" class="p-16 text-center">
                                    <div class="flex flex-col items-center justify-center opacity-60">
                                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                            <i class="fa-regular fa-calendar-plus text-3xl text-gray-400"></i>
                                        </div>
                                        <h3 class="text-xl font-bold text-gray-800">No Upcoming Bookings</h3>
                                        <p class="text-gray-500 mb-6">You have no active sessions scheduled.</p>
                                        <a href="student_facilities.php" class="text-[#8a0d19] font-bold hover:underline flex items-center gap-2">Browse Facilities <i class="fa-solid fa-arrow-right"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($upcoming as $bk): 
                                $startObj = new DateTime($bk['StartTime']);
                                $endObj = new DateTime($bk['EndTime']);
                                $duration = $startObj->diff($endObj);
                                $hours = $duration->h + ($duration->i / 60);
                                
                                $statusClass = ($bk['Status'] === 'Approved') 
                                    ? 'bg-green-100 text-green-700 border-green-200' 
                                    : 'bg-yellow-100 text-yellow-700 border-yellow-200';
                            ?>
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="p-6">
                                    <div class="font-bold text-gray-800 text-lg mb-1"><?php echo htmlspecialchars($bk['FacilityName']); ?></div>
                                    <div class="text-xs text-gray-500 flex items-center gap-2">
                                        <span class="bg-gray-100 px-2 py-0.5 rounded text-gray-600 font-medium"><?php echo htmlspecialchars($bk['Type']); ?></span>
                                        <span class="flex items-center gap-1"><i class="fa-solid fa-location-dot text-[#8a0d19]"></i> <?php echo htmlspecialchars($bk['Location']); ?></span>
                                    </div>
                                </td>
                                <td class="p-6">
                                    <div class="flex items-center gap-4">
                                        <div class="bg-red-50 text-[#8a0d19] rounded-xl p-3 text-center min-w-[60px] border border-red-100 shadow-sm">
                                            <span class="block text-xl font-bold leading-none font-serif"><?php echo $startObj->format('d'); ?></span>
                                            <span class="block text-[10px] uppercase font-bold tracking-wider"><?php echo $startObj->format('M'); ?></span>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-900 text-base"><?php echo $startObj->format('l'); ?></div>
                                            <div class="text-sm text-gray-500 font-medium">
                                                <?php echo $startObj->format('h:i A') . ' - ' . $endObj->format('h:i A'); ?>
                                                <span class="text-xs opacity-75 bg-gray-100 px-1.5 py-0.5 rounded ml-2"><?php echo $hours; ?> hrs</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-6 text-center">
                                    <span class="px-3 py-1.5 rounded-full text-xs font-bold border <?php echo $statusClass; ?> inline-flex items-center gap-1.5 shadow-sm">
                                        <?php if($bk['Status']=='Approved'): ?><i class="fa-solid fa-check"></i><?php else: ?><i class="fa-regular fa-clock"></i><?php endif; ?>
                                        <?php echo $bk['Status']; ?>
                                    </span>
                                </td>
                                <td class="p-6 text-center">
                                    <button onclick="cancelBooking(<?php echo $bk['BookingID']; ?>)" 
                                            class="inline-flex items-center justify-center w-full md:w-auto px-5 py-2.5 border border-red-200 text-red-600 rounded-lg text-sm font-bold hover:bg-red-50 hover:border-red-300 transition shadow-sm gap-2">
                                        <i class="fa-solid fa-ban"></i> Cancel
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB CONTENT: HISTORY -->
        <div id="view-history" class="p-0 hidden fade-in">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider font-semibold border-b border-gray-100">
                        <tr>
                            <th class="p-6">Facility</th>
                            <th class="p-6">Date</th>
                            <th class="p-6">Time</th>
                            <th class="p-6 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-gray-600">
                        <?php if (empty($history)): ?>
                            <tr>
                                <td colspan="4" class="p-16 text-center text-gray-400">No booking history available.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($history as $bk): 
                                $startObj = new DateTime($bk['StartTime']);
                                $endObj = new DateTime($bk['EndTime']);
                                
                                $statusStyle = 'bg-gray-100 text-gray-500 border-gray-200'; // Default/Cancelled
                                if ($bk['Status'] == 'Approved') $statusStyle = 'bg-green-50 text-green-700 border-green-100'; // Completed success
                                if ($bk['Status'] == 'Rejected') $statusStyle = 'bg-red-50 text-red-700 border-red-100';
                            ?>
                            <tr class="hover:bg-gray-50 transition opacity-90">
                                <td class="p-6 font-bold text-gray-700"><?php echo htmlspecialchars($bk['FacilityName']); ?></td>
                                <td class="p-6 text-sm font-medium"><?php echo $startObj->format('d M Y'); ?></td>
                                <td class="p-6 text-sm font-medium"><?php echo $startObj->format('h:i A') . ' - ' . $endObj->format('h:i A'); ?></td>
                                <td class="p-6 text-center">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold border uppercase <?php echo $statusStyle; ?>">
                                        <?php echo $bk['Status']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- INFO TEXT -->
    <div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-2xl shadow-xl p-10 mt-12 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -mr-16 -mt-16 blur-3xl"></div>
        <div class="relative z-10 flex flex-col md:flex-row gap-8 items-center">
            <div class="flex-1 text-center md:text-left">
                <h2 class="text-3xl font-bold font-serif mb-4 text-white">About UKM Sports Center</h2>
                <div class="w-16 h-1 bg-[#8a0d19] mb-4 mx-auto md:mx-0"></div>
                <p class="text-gray-300 text-base leading-relaxed max-w-2xl">
                    Established on 1 November 1974, the UKM Sports Center began with a single Sports Officer. Today, it has evolved into a fully equipped center managing sports activities for students and staff, participating in major events like the ASEAN University Games.
                </p>
            </div>
            <div class="flex-shrink-0 opacity-80">
                <i class="fa-solid fa-medal text-7xl text-white/20"></i>
            </div>
        </div>
    </div>

</main>

<!-- FOOTER -->
<footer class="bg-white border-t border-gray-200 py-10 mt-auto">
    <div class="container mx-auto px-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-8">
            <div class="flex items-start gap-4 max-w-md">
                <img src="../assets/img/pusatsukanlogo.png" alt="Pusat Sukan Logo" class="h-16 w-auto">
                <div class="text-sm text-gray-600 leading-snug">
                    <strong class="block text-gray-900 text-base mb-1">PEJABAT PENGARAH PUSAT SUKAN</strong>
                    Stadium Universiti, Universiti Kebangsaan Malaysia<br>
                    43600 Bangi, Selangor Darul Ehsan<br>
                    <span class="mt-1 block"><i class="fa-solid fa-phone mr-1"></i> 03-8921-5306</span>
                </div>
            </div>
            <div>
                <img src="../assets/img/sdg.png" alt="SDG Logo" class="h-20 w-auto opacity-90">
            </div>
        </div>
        <div class="border-t border-gray-100 mt-8 pt-8 text-center text-sm text-gray-500">
            &copy; 2025 Universiti Kebangsaan Malaysia. All rights reserved.
        </div>
    </div>
</footer>

<!-- SCRIPTS -->
<script>
// 1. Tab Logic
function switchTab(tab) {
    // Buttons
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    
    // Views
    document.getElementById('view-upcoming').classList.add('hidden');
    document.getElementById('view-history').classList.add('hidden');
    
    const view = document.getElementById('view-' + tab);
    view.classList.remove('hidden');
}

// Check URL param on load
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('tab') === 'history') {
    switchTab('history');
}

// 2. Counter Logic (Dynamic + Animated)
const facilityTarget = <?php echo $realFacilityCount > 0 ? $realFacilityCount : 15; ?>;
const staffTarget = 47; // Hardcoded per requirement or fetch from DB if table exists
let facilityCount = 0;
let staffCount = 0;

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
        setTimeout(incrementCounters, 40);
    }
}
window.onload = incrementCounters;

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

</body>
</html>