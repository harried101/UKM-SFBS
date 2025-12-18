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
    --primary-hover: #6d0a13;
    --bg-light: #f8fafc; /* Very light cool grey */
}
body {
    font-family: 'Inter', sans-serif;
    background-color: var(--bg-light);
    color: #1e293b; /* Slate-800 */
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

h1, h2, h3 { font-family: 'Playfair Display', serif; }

/* Custom Scrollbar */
.scrollbar-hide::-webkit-scrollbar { display: none; }

/* Tabs Animation - Modern Pill Style */
.tab-btn {
    position: relative;
    color: #64748b; /* Slate-500 */
    font-weight: 500;
    transition: all 0.3s ease;
    border-radius: 9999px; /* Full rounded pill */
}
.tab-btn:hover {
    color: #334155; /* Slate-700 */
    background-color: #f1f5f9;
}
.tab-btn.active {
    background-color: var(--primary);
    color: white;
    box-shadow: 0 4px 6px -1px rgba(138, 13, 25, 0.2);
}

/* Fade Animation */
.fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

/* Card Hover Effect */
.hover-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.hover-card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.01); }
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
            <!-- Active Home -->
            <a href="dashboard.php" class="text-[#8a0d19] font-semibold transition flex items-center gap-2 group relative">
                <span>Home</span>
                <span class="absolute -bottom-1 left-0 w-full h-0.5 bg-[#8a0d19] rounded-full"></span>
            </a>
            <a href="student_facilities.php" class="text-slate-500 hover:text-[#8a0d19] font-medium transition hover:scale-105">Facilities</a>
            <!-- Link triggers history tab -->
            <button onclick="switchTab('history')" class="text-slate-500 hover:text-[#8a0d19] font-medium transition hover:scale-105">History</button>

            <div class="flex items-center gap-4 pl-6 border-l border-slate-200">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($studentName); ?></p>
                    <p class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold"><?php echo htmlspecialchars($studentID); ?></p>
                </div>
                <div class="relative group">
                    <img src="../assets/img/user.png" alt="Profile" class="w-10 h-10 rounded-full border-2 border-white ring-2 ring-slate-100 object-cover cursor-pointer transition transform group-hover:scale-105">
                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-slate-100 hidden group-hover:block z-50 overflow-hidden">
                        <a href="../logout.php" onclick="return confirm('Are you sure you want to logout?');" class="block px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition flex items-center gap-2">
                            <i class="fa-solid fa-right-from-bracket"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- HERO BANNER & HEADER -->
<div class="w-full h-64 overflow-hidden relative shadow-lg group">
    <img src="../assets/img/psukan.jpg" alt="Pusat Sukan" class="w-full h-full object-cover object-center transition-transform duration-1000 group-hover:scale-105">
    <div class="absolute inset-0 bg-gradient-to-t from-[#8a0d19]/90 via-[#8a0d19]/40 to-transparent"></div>
    
    <div class="absolute inset-0 flex flex-col items-center justify-center text-white text-center px-4 z-10 translate-y-2">
        <h1 class="text-3xl md:text-5xl font-bold mb-3 tracking-tight font-serif drop-shadow-sm">Welcome Back, <?php echo htmlspecialchars($studentName); ?></h1>
        <div class="w-24 h-1 bg-white/50 rounded-full mb-6"></div>
        <p class="text-lg text-white/90 font-light max-w-2xl mb-6">
            Ready to get moving? Check availability and book your next session.
        </p>
        
        <a href="student_facilities.php" class="bg-white text-[#8a0d19] px-8 py-3 rounded-full font-bold shadow-xl hover:shadow-2xl hover:bg-slate-50 transition-all transform hover:-translate-y-1 flex items-center gap-2 group-hover:gap-3">
            <span>New Booking</span>
            <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>
</div>

<!-- MAIN CONTENT -->
<main class="container mx-auto px-6 py-12 flex-grow max-w-7xl relative z-20">

    <!-- BOOKINGS CARD -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-xl overflow-hidden min-h-[500px] flex flex-col mt-4">
        
        <!-- Tabs Header -->
        <div class="flex items-center gap-4 px-8 pt-8 pb-4 bg-white border-b border-slate-100">
            <button onclick="switchTab('upcoming')" id="tab-upcoming" class="tab-btn active py-2.5 px-6 flex items-center gap-2">
                Upcoming 
                <span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full text-[10px] font-bold"><?php echo count($upcoming); ?></span>
            </button>
            <button onclick="switchTab('history')" id="tab-history" class="tab-btn py-2.5 px-6 flex items-center gap-2">
                History 
                <span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full text-[10px] font-bold"><?php echo count($history); ?></span>
            </button>
        </div>

        <!-- TAB CONTENT -->
        <div class="p-8 flex-grow bg-slate-50/50">
            
            <!-- VIEW: UPCOMING -->
            <div id="view-upcoming" class="fade-in h-full">
                <?php if (empty($upcoming)): ?>
                    <div class="flex flex-col items-center justify-center h-80 text-center">
                        <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mb-6 shadow-sm text-slate-300">
                            <i class="fa-regular fa-calendar-xmark text-3xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 mb-2">No Upcoming Bookings</h3>
                        <p class="text-slate-500 mb-6 max-w-xs text-sm leading-relaxed">Your schedule is clear.</p>
                        <a href="student_facilities.php" class="text-[#8a0d19] text-sm font-bold hover:underline flex items-center gap-2 group">
                            Browse Facilities <i class="fa-solid fa-arrow-right transition-transform group-hover:translate-x-1"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($upcoming as $bk): 
                            $startObj = new DateTime($bk['StartTime']);
                            $endObj = new DateTime($bk['EndTime']);
                            $statusClass = ($bk['Status'] === 'Approved') 
                                ? 'bg-green-100 text-green-700 border-green-200' 
                                : 'bg-yellow-50 text-yellow-700 border-yellow-200';
                        ?>
                        <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all flex flex-col md:flex-row items-center justify-between gap-6 group">
                            <div class="flex items-center gap-6 w-full">
                                <!-- Date Badge -->
                                <div class="bg-slate-50 rounded-xl p-3 min-w-[70px] text-center border border-slate-100">
                                    <span class="block text-xl font-bold text-[#8a0d19] font-serif"><?php echo $startObj->format('d'); ?></span>
                                    <span class="block text-[10px] uppercase font-bold text-slate-400 tracking-wider"><?php echo $startObj->format('M'); ?></span>
                                </div>
                                <!-- Info -->
                                <div>
                                    <h4 class="font-bold text-slate-800 text-lg mb-1 group-hover:text-[#8a0d19] transition-colors"><?php echo htmlspecialchars($bk['FacilityName']); ?></h4>
                                    <div class="flex flex-wrap gap-4 text-sm text-slate-500 font-medium">
                                        <span class="flex items-center gap-1.5"><i class="fa-regular fa-clock text-slate-400"></i> <?php echo $startObj->format('h:i A') . ' - ' . $endObj->format('h:i A'); ?></span>
                                        <span class="flex items-center gap-1.5"><i class="fa-solid fa-location-dot text-slate-400"></i> <?php echo htmlspecialchars($bk['Location']); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Status & Action -->
                            <div class="flex items-center gap-4 w-full md:w-auto justify-between md:justify-end border-t md:border-t-0 border-slate-100 pt-4 md:pt-0">
                                <span class="px-3 py-1 rounded-full text-xs font-bold border <?php echo $statusClass; ?>">
                                    <?php echo $bk['Status']; ?>
                                </span>
                                <button onclick="cancelBooking(<?php echo $bk['BookingID']; ?>)" 
                                        class="text-red-500 hover:text-white border border-red-200 hover:bg-red-500 hover:border-red-500 px-4 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-2">
                                    Cancel
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- VIEW: HISTORY -->
            <div id="view-history" class="hidden fade-in h-full">
                <?php if (empty($history)): ?>
                    <div class="flex flex-col items-center justify-center h-80 text-center text-slate-400">
                        <i class="fa-solid fa-clock-rotate-left text-4xl mb-4 opacity-20"></i>
                        <p class="text-sm">No past booking history found.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($history as $bk): 
                            $startObj = new DateTime($bk['StartTime']);
                            $endObj = new DateTime($bk['EndTime']);
                            
                            $statusStyle = 'bg-slate-100 text-slate-500'; // Default/Cancelled
                            $statusIcon = 'fa-ban';
                            if ($bk['Status'] == 'Approved') { $statusStyle = 'bg-green-50 text-green-700'; $statusIcon='fa-check'; }
                            if ($bk['Status'] == 'Rejected') { $statusStyle = 'bg-red-50 text-red-700'; $statusIcon='fa-xmark'; }
                        ?>
                        <div class="flex items-center justify-between p-4 bg-white rounded-xl border border-slate-100 hover:bg-white hover:shadow-sm transition opacity-80 hover:opacity-100">
                            <div class="flex items-center gap-4">
                                <div class="h-10 w-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400">
                                    <i class="fa-solid fa-history text-sm"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-700 text-sm"><?php echo htmlspecialchars($bk['FacilityName']); ?></p>
                                    <p class="text-xs text-slate-400 font-medium"><?php echo $startObj->format('d M Y, h:i A'); ?></p>
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide flex items-center gap-1.5 <?php echo $statusStyle; ?>">
                                <i class="fa-solid <?php echo $statusIcon; ?>"></i> <?php echo $bk['Status']; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- ABOUT SECTION (Minimalist) -->
    <div class="mt-12 bg-white rounded-2xl border border-slate-100 p-8 shadow-lg flex flex-col md:flex-row items-center gap-8 relative overflow-hidden group">
        <div class="absolute left-0 top-0 bottom-0 w-1 bg-[#8a0d19]"></div>
        <div class="flex-1 relative z-10">
            <h2 class="text-2xl font-bold font-serif mb-3 text-slate-800">About UKM Sports Center</h2>
            <p class="text-slate-600 text-sm leading-relaxed max-w-3xl">
                Established on 1 November 1974, the UKM Sports Center began with a single Sports Officer. Today, it has evolved into a fully equipped center managing sports activities for students and staff, participating in major events like the ASEAN University Games. We are committed to fostering athletic excellence and student well-being.
            </p>
        </div>
        <div class="flex-shrink-0 opacity-10 group-hover:opacity-20 transition-opacity">
            <i class="fa-solid fa-medal text-8xl text-slate-800"></i>
        </div>
    </div>

</main>

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
            <img src="../assets/img/sdg.png" alt="SDG" class="h-10 opacity-70 grayscale hover:grayscale-0 transition">
            <p class="text-[10px] text-slate-400">© 2025 Universiti Kebangsaan Malaysia. All rights reserved.</p>
        </div>
    </div>
</footer>

<!-- SCRIPTS -->
<script>
// 1. Tab Logic
function switchTab(tab) {
    // Reset Buttons
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('active', 'bg-slate-50');
        b.classList.add('text-slate-500');
    });

    // Active Button
    const activeBtn = document.getElementById('tab-' + tab);
    activeBtn.classList.add('active');
    activeBtn.classList.remove('text-slate-500');
    
    // Switch Views
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

// 2. Cancel Booking Logic
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
            // Optional: Show a nice toast instead of alert
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