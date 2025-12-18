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
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
:root {
    --primary: #8a0d19; /* UKM Red */
    --primary-hover: #6d0a13;
    --bg-light: #f9fafb; /* Lighter grey for modern feel */
}
body {
    font-family: 'Inter', sans-serif;
    background-color: var(--bg-light);
    color: #1f2937; /* Gray-900 for text */
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* Custom Scrollbar */
.scrollbar-hide::-webkit-scrollbar { display: none; }

/* Tabs Animation */
.tab-btn {
    border-bottom: 2px solid transparent;
    color: #6b7280; /* Gray-500 */
    font-weight: 500;
    transition: all 0.2s;
}
.tab-btn:hover {
    color: #374151; /* Gray-700 */
}
.tab-btn.active {
    border-color: var(--primary);
    color: var(--primary);
    font-weight: 600;
}

/* Fade Animation */
.fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
    <div class="container mx-auto px-6 py-3 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <img src="../assets/img/ukm.png" alt="UKM Logo" class="h-12 w-auto">
            <div class="h-8 w-px bg-gray-300 hidden sm:block"></div>
            <img src="../assets/img/pusatsukanlogo.png" alt="Pusat Sukan Logo" class="h-12 w-auto hidden sm:block">
        </div>
        <div class="flex items-center gap-6">
            <!-- Active Home -->
            <a href="dashboard.php" class="text-[#8a0d19] font-semibold transition flex items-center gap-2 group">
                Home
            </a>
            <a href="student_facilities.php" class="text-gray-600 hover:text-[#8a0d19] font-medium transition">Facilities</a>
            <!-- Link triggers history tab -->
            <button onclick="switchTab('history')" class="text-gray-600 hover:text-[#8a0d19] font-medium transition">History</button>

            <div class="flex items-center gap-3 pl-6 border-l border-gray-200">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($studentName); ?></p>
                    <p class="text-xs text-gray-500 uppercase tracking-wider"><?php echo htmlspecialchars($studentID); ?></p>
                </div>
                <div class="relative group">
                    <img src="../assets/img/user.png" alt="Profile" class="w-10 h-10 rounded-full border-2 border-white shadow-sm object-cover cursor-pointer hover:scale-105 transition">
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

<!-- MAIN CONTENT -->
<main class="container mx-auto px-6 py-8 flex-grow max-w-7xl">

    <!-- PAGE HEADER -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <p class="text-gray-500 mt-1">Welcome back, <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($studentName); ?></span></p>
        </div>
        <a href="student_facilities.php" class="bg-[#8a0d19] hover:bg-[#6d0a13] text-white px-5 py-2.5 rounded-lg shadow-sm text-sm font-medium transition flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> New Booking
        </a>
    </div>

    <!-- STATS OVERVIEW -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Stat 1 -->
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Active Facilities</p>
                <p class="text-2xl font-bold text-gray-900 mt-1" id="facility-counter">0</p>
            </div>
            <div class="p-3 bg-red-50 rounded-lg text-[#8a0d19]">
                <i class="fa-solid fa-dumbbell text-xl"></i>
            </div>
        </div>
        <!-- Stat 2 -->
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
             <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Staff Members</p>
                <p class="text-2xl font-bold text-gray-900 mt-1" id="staff-counter">0</p>
            </div>
            <div class="p-3 bg-blue-50 rounded-lg text-blue-700">
                <i class="fa-solid fa-users text-xl"></i>
            </div>
        </div>
        <!-- Stat 3 -->
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
             <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Bookings</p>
                <p class="text-2xl font-bold text-gray-900 mt-1"><?php echo count($upcoming) + count($history); ?></p>
            </div>
            <div class="p-3 bg-green-50 rounded-lg text-green-700">
                <i class="fa-solid fa-calendar-check text-xl"></i>
            </div>
        </div>
    </div>

    <!-- BOOKINGS CARD -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm min-h-[500px] flex flex-col">
        
        <!-- Tabs -->
        <div class="flex border-b border-gray-200 px-6 pt-2">
            <button onclick="switchTab('upcoming')" id="tab-upcoming" class="tab-btn active py-4 px-4 mr-4 flex items-center gap-2">
                Upcoming <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full text-xs font-semibold"><?php echo count($upcoming); ?></span>
            </button>
            <button onclick="switchTab('history')" id="tab-history" class="tab-btn py-4 px-4 flex items-center gap-2">
                History <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full text-xs font-semibold"><?php echo count($history); ?></span>
            </button>
        </div>

        <!-- TAB CONTENT -->
        <div class="p-6 flex-grow">
            
            <!-- VIEW: UPCOMING -->
            <div id="view-upcoming" class="fade-in h-full">
                <?php if (empty($upcoming)): ?>
                    <div class="flex flex-col items-center justify-center h-64 text-center">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4 text-gray-300">
                            <i class="fa-regular fa-calendar text-2xl"></i>
                        </div>
                        <h3 class="text-base font-semibold text-gray-900">No upcoming bookings</h3>
                        <p class="text-sm text-gray-500 mt-1 mb-4">You have no active sessions scheduled.</p>
                        <a href="student_facilities.php" class="text-[#8a0d19] text-sm font-medium hover:underline">Browse Facilities &rarr;</a>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-xs text-gray-500 border-b border-gray-100 uppercase tracking-wider">
                                    <th class="pb-3 pl-2 font-medium">Facility</th>
                                    <th class="pb-3 font-medium">Date & Time</th>
                                    <th class="pb-3 text-center font-medium">Status</th>
                                    <th class="pb-3 text-right font-medium pr-2">Action</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm text-gray-700">
                                <?php foreach ($upcoming as $bk): 
                                    $startObj = new DateTime($bk['StartTime']);
                                    $endObj = new DateTime($bk['EndTime']);
                                    $statusClass = ($bk['Status'] === 'Approved') 
                                        ? 'bg-green-50 text-green-700 border border-green-200' 
                                        : 'bg-yellow-50 text-yellow-700 border border-yellow-200';
                                ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                                    <td class="py-4 pl-2">
                                        <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($bk['FacilityName']); ?></div>
                                        <div class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($bk['Type']); ?> • <?php echo htmlspecialchars($bk['Location']); ?></div>
                                    </td>
                                    <td class="py-4">
                                        <div class="font-medium"><?php echo $startObj->format('d M Y'); ?></div>
                                        <div class="text-xs text-gray-500 mt-1"><?php echo $startObj->format('h:i A') . ' - ' . $endObj->format('h:i A'); ?></div>
                                    </td>
                                    <td class="py-4 text-center">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                            <?php echo $bk['Status']; ?>
                                        </span>
                                    </td>
                                    <td class="py-4 text-right pr-2">
                                        <button onclick="cancelBooking(<?php echo $bk['BookingID']; ?>)" 
                                                class="text-red-600 hover:text-red-800 text-xs font-medium border border-red-200 hover:bg-red-50 px-3 py-1.5 rounded transition">
                                            Cancel
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- VIEW: HISTORY -->
            <div id="view-history" class="hidden fade-in h-full">
                <?php if (empty($history)): ?>
                    <div class="flex flex-col items-center justify-center h-64 text-center text-gray-400">
                        <p>No past booking history found.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-xs text-gray-500 border-b border-gray-100 uppercase tracking-wider">
                                    <th class="pb-3 pl-2 font-medium">Facility</th>
                                    <th class="pb-3 font-medium">Date</th>
                                    <th class="pb-3 font-medium">Time</th>
                                    <th class="pb-3 text-center font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm text-gray-600">
                                <?php foreach ($history as $bk): 
                                    $startObj = new DateTime($bk['StartTime']);
                                    $endObj = new DateTime($bk['EndTime']);
                                    
                                    $statusStyle = 'bg-gray-100 text-gray-500 border-gray-200'; // Default/Cancelled
                                    if ($bk['Status'] == 'Approved') $statusStyle = 'bg-green-50 text-green-700 border-green-200'; 
                                    if ($bk['Status'] == 'Rejected') $statusStyle = 'bg-red-50 text-red-700 border-red-200';
                                ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                                    <td class="py-4 pl-2 font-medium text-gray-900"><?php echo htmlspecialchars($bk['FacilityName']); ?></td>
                                    <td class="py-4"><?php echo $startObj->format('d M Y'); ?></td>
                                    <td class="py-4"><?php echo $startObj->format('h:i A') . ' - ' . $endObj->format('h:i A'); ?></td>
                                    <td class="py-4 text-center">
                                        <span class="px-2.5 py-0.5 rounded text-xs font-medium border uppercase <?php echo $statusStyle; ?>">
                                            <?php echo $bk['Status']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- ABOUT SECTION -->
    <div class="mt-8 bg-white rounded-xl border border-gray-200 p-6 shadow-sm flex items-start gap-4">
         <div class="p-3 bg-gray-50 rounded-full text-gray-400 hidden sm:block">
            <i class="fa-solid fa-info-circle text-xl"></i>
        </div>
        <div>
            <h3 class="text-base font-bold text-gray-900 mb-1">About UKM Sports Center</h3>
            <p class="text-sm text-gray-600 leading-relaxed max-w-4xl">
                Established on 1 November 1974, the UKM Sports Center began with a single Sports Officer. Today, it has evolved into a fully equipped center managing sports activities for students and staff, participating in major events like the ASEAN University Games.
            </p>
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

// Check URL param on load (e.g. from navbar history link)
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