<?php
session_start();

$timeout_limit = 1800; 

// 2. Check if the 'last_activity' timestamp exists
if (isset($_SESSION['last_activity'])) {
    $seconds_inactive = time() - $_SESSION['last_activity'];
    
    // 3. If inactive for too long, redirect to logout
    if ($seconds_inactive >= $timeout_limit) {
        header("Location: ../logout.php");
        exit;
    }
}

// 4. Update the timestamp to 'now' because they just loaded the page
$_SESSION['last_activity'] = time();
date_default_timezone_set('Asia/Kuala_Lumpur');

/* ================= AUTH ================= */
// Redirect if not logged in or not a student
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Student') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/db_connect.php';


// -----------------------------
// 1. FETCH STUDENT DETAILS
// -----------------------------
$studentIdentifier = $_SESSION['user_id'] ?? '';
$studentName = 'Student';
$studentID = '';
$db_numeric_id = 0;

if ($studentIdentifier) {
    $stmtStudent = $conn->prepare("
        SELECT UserID, FirstName, LastName, UserIdentifier 
        FROM users 
        WHERE UserIdentifier = ?
    ");
    $stmtStudent->bind_param("s", $studentIdentifier);
    $stmtStudent->execute();
    $resStudent = $stmtStudent->get_result();

    if ($rowStudent = $resStudent->fetch_assoc()) {
        $studentName   = $rowStudent['FirstName'] . ' ' . $rowStudent['LastName'];
        $studentID     = $rowStudent['UserIdentifier'];
        $db_numeric_id = (int)$rowStudent['UserID'];
    }
    $stmtStudent->close();
}

// -----------------------------
// 2. FETCH ALL BOOKINGS (FIXED LOGIC)
// -----------------------------
$all_bookings = [];
$now = new DateTime(); // current KL time

if ($db_numeric_id > 0) {
    // Fetch bookings that are not marked as 'Completed' or permanently 'Rejected/Canceled' 
    // to keep the dashboard clean, but fetch all for history.
    $sql = "
        SELECT 
            b.BookingID,
            b.StartTime,
            b.EndTime,
            b.Status,
            f.Name AS FacilityName,
            f.Location
        FROM bookings b
        JOIN facilities f ON b.FacilityID = f.FacilityID
        WHERE b.UserID = ?
        ORDER BY b.StartTime DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $db_numeric_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        
        // Use full database datetime strings to create DateTime objects
        $startTimeStr = ($row['StartTime'] === '0000-00-00 00:00:00' || empty($row['StartTime'])) 
            ? $row['EndTime'] 
            : $row['StartTime'];

        $start = new DateTime($startTimeStr);
        $end = new DateTime($row['EndTime']);
        
        // Check if the booking has passed
        $is_passed = ($end < $now);

        $all_bookings[] = array_merge($row, [
            'is_passed'       => $is_passed,
            'formatted_start' => $start->format('d M Y'),
            'formatted_time'  => $start->format('h:i A') . ' - ' . $end->format('h:i A'),
            'day'             => $start->format('d'),
            'month'           => $start->format('M')
        ]);
    }
    $stmt->close();
}

if ($conn->connect_error) {
    // Better way to handle connection error in production
    // die("DB Connection failed: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard – UKM Sports Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #8a0d19; 
            --primary-hover: #6d0a13;
            --bg-light: #f8fafc;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: #1e293b;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        h1, h2, h3, .font-serif { font-family: 'Playfair Display', serif; }
        
        /* Animations */
        .fade-in { animation: fadeIn 0.5s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); }
    </style>
</head>
<body>

<nav class="bg-white/95 backdrop-blur-sm border-b border-slate-200 sticky top-0 z-40 transition-all duration-300 shadow-sm">
    <div class="container mx-auto px-6 py-3 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <img src="../assets/img/ukm.png" alt="UKM Logo" class="h-10 md:h-12 w-auto transition-transform hover:scale-105">
            <div class="h-8 w-px bg-slate-200 hidden sm:block"></div>
            <img src="../assets/img/pusatsukanlogo.png" alt="Pusat Sukan Logo" class="h-10 md:h-12 w-auto hidden sm:block transition-transform hover:scale-105">
        </div>
        
        <div class="flex items-center gap-8">
            <div class="hidden md:flex items-center gap-6">
                <a href="dashboard.php" class="text-[#8a0d19] font-bold transition flex items-center gap-2 relative text-decoration-none">
                    <span>Home</span>
                    <span class="absolute -bottom-1.5 left-0 w-full h-0.5 bg-[#8a0d19] rounded-full"></span>
                </a>
                <a href="student_facilities.php" class="text-slate-600 hover:text-[#8a0d19] font-medium transition hover:-translate-y-0.5 text-decoration-none">Facilities</a>
                <a href="booking_history.php" class="text-slate-600 hover:text-[#8a0d19] font-medium transition hover:-translate-y-0.5 text-decoration-none">History</a>
            </div>

            <div class="flex items-center gap-4 pl-4 md:pl-6 md:border-l border-slate-200 relative">
                <a href="notification.php" class="text-slate-400 hover:text-[#8a0d19] transition-colors relative p-1">
                    <i class="fa-regular fa-bell text-xl"></i>
                    </a>

                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-slate-800 leading-tight"><?php echo htmlspecialchars($studentName); ?></p>
                    <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold"><?php echo htmlspecialchars($studentID); ?></p>
                </div>
                <div class="relative" id="profileDropdownContainer">
                    <button id="profileBtn" class="focus:outline-none focus:ring-2 focus:ring-[#8a0d19] rounded-full transition-transform active:scale-95">
                        <img src="../assets/img/user.png" alt="Profile" class="w-10 h-10 rounded-full border-2 border-white ring-2 ring-slate-100 object-cover shadow-sm hover:ring-[#8a0d19]/20">
                    </button>
                    <div id="dropdownMenu" class="absolute right-0 mt-3 w-56 bg-white rounded-xl shadow-xl border border-slate-100 hidden z-50 overflow-hidden transform origin-top-right transition-all duration-200">
                        <div class="px-4 py-3 bg-slate-50 border-b border-slate-100 md:hidden">
                             <p class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($studentName); ?></p>
                             <p class="text-xs text-slate-500"><?php echo htmlspecialchars($studentID); ?></p>
                        </div>
                        <a href="student_facilities.php" class="block md:hidden px-4 py-3 text-sm text-slate-600 hover:bg-slate-50 hover:text-[#8a0d19] text-decoration-none">New Booking</a>
                        <a href="booking_history.php" class="block md:hidden px-4 py-3 text-sm text-slate-600 hover:bg-slate-50 hover:text-[#8a0d19] text-decoration-none">History</a>
                        <a href="notification.php" class="block md:hidden px-4 py-3 text-sm text-slate-600 hover:bg-slate-50 hover:text-[#8a0d19] text-decoration-none">Notifications</a>
                        <div class="h-px bg-slate-100 md:hidden"></div>
                        <a href="../logout.php" id="logoutLink" class="block px-4 py-3 text-sm text-red-600 hover:bg-red-50 hover:pl-6 transition-all font-medium flex items-center gap-2 text-decoration-none">
                            <i class="fa-solid fa-right-from-bracket"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<main class="container mx-auto px-4 md:px-6 py-8 md:py-12 flex-grow max-w-7xl relative z-10 space-y-8">

    <div class="fade-in space-y-2">
        <p class="text-slate-500 font-medium text-sm uppercase tracking-wide">Dashboard</p>
        <h1 class="text-3xl md:text-4xl font-bold text-[#8a0d19] font-serif">Welcome back, <?php echo explode(' ', trim($studentName))[0]; ?> 👋</h1>
        <p class="text-slate-600 max-w-xl text-lg">Here's an overview of your sports activities and upcoming sessions.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 fade-in">
        <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center justify-between gap-4 card-hover">
            <div>
                <p class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Active Bookings</p>
                <div class="text-3xl font-bold text-slate-800 font-serif">
                    <?php 
                    $active_count = 0;
                    foreach($all_bookings as $b) {
                        if(!$b['is_passed'] && in_array($b['Status'], ['Pending','Approved','Confirmed'])) $active_count++;
                    }
                    echo $active_count;
                    ?>
                </div>
            </div>
            <div class="h-12 w-12 rounded-full bg-red-50 flex items-center justify-center text-[#8a0d19]">
                <i class="fa-regular fa-calendar-check text-xl"></i>
            </div>
        </div>

     <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm card-hover flex flex-col gap-4">
    <h5 class="text-slate-500 font-bold uppercase text-xs tracking-wider">Cancelation Health (Weekly)</h5>
    
    <div class="flex items-center justify-between">
        <h3 id="health-score-percent" class="text-4xl font-extrabold text-[#d9464a] drop-shadow-lg">--%</h3>
        
        <span id="health-status-tag" class="px-2 py-1 text-xs font-bold rounded-full bg-amber-100 text-amber-700">Loading...</span>
    </div>
    
    <p id="health-status-message" class="text-slate-400 text-sm">Fetching weekly rate data...</p>
    
    <div class="w-full h-3 bg-amber-100 rounded-full overflow-hidden">
        <div id="health-progress-bar" class="h-3 bg-amber-400 rounded-full" style="width:0%"></div>
    </div>
</div>


    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-100 pb-4">
        <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-3">
            <span class="w-2 h-8 bg-[#8a0d19] rounded-full"></span>
            My Bookings
        </h2>
        <a href="student_facilities.php" class="bg-[#8a0d19] hover:bg-[#6d0a13] text-white px-6 py-2.5 rounded-full font-bold shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5 flex items-center gap-2 group text-sm text-decoration-none">
            <i class="fa-solid fa-plus transition-transform group-hover:rotate-90"></i> New Booking
        </a>
    </div>

    <div class="space-y-6 fade-in" style="animation-delay: 0.1s;">
        <?php 
        // Filter out completed/rejected bookings from the dashboard display for cleaner UX
        $display_bookings = array_filter($all_bookings, function($b) {
            return !in_array($b['Status'], ['Completed', 'Rejected', 'Canceled']);
        });

        if (empty($display_bookings)): ?>
            <div class="bg-white rounded-3xl border-2 border-dashed border-slate-200 p-12 text-center flex flex-col items-center justify-center hover:border-slate-300 transition-colors">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 text-slate-300">
                    <i class="fa-solid fa-person-running text-3xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-1">No Relevant Activities</h3>
                <p class="text-slate-500 mb-6 max-w-sm mx-auto text-sm">You have no pending, approved, or recently passed bookings here. Check History for past bookings.</p>
                <a href="student_facilities.php" class="text-[#8a0d19] font-bold text-sm hover:underline flex items-center gap-2 transition group text-decoration-none">
                    Browse Facilities <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="divide-y divide-slate-100">
                <?php foreach ($display_bookings as $bk): 
                    $statusColor = 'bg-slate-100 text-slate-600';
                    $statusDot = 'bg-slate-400';
                    
                    if (in_array($bk['Status'], ['Approved', 'Confirmed'])) {
                        $statusColor = 'bg-emerald-50 text-emerald-700';
                        $statusDot = 'bg-emerald-500';
                    } elseif ($bk['Status'] === 'Pending') {
                        $statusColor = 'bg-amber-50 text-amber-700';
                        $statusDot = 'bg-amber-500';
                    } elseif (in_array($bk['Status'], ['Canceled', 'Rejected'])) {
                        $statusColor = 'bg-red-50 text-red-700';
                        $statusDot = 'bg-red-500';
                    }
                    
                    $opacityClass = $bk['is_passed'] ? 'opacity-60 bg-slate-50/50' : 'hover:bg-slate-50';
                ?>
                <div class="p-4 flex flex-col sm:flex-row sm:items-center gap-4 transition-colors <?php echo $opacityClass; ?>">
                    
                    <div class="flex-shrink-0 flex sm:flex-col items-center sm:justify-center gap-2 sm:gap-0 min-w-[60px] text-center">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider"><?php echo $bk['month']; ?></span>
                        <span class="text-xl font-bold text-[#8a0d19] font-serif leading-none"><?php echo $bk['day']; ?></span>
                    </div>
                    
                    <div class="flex-grow min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <h4 class="text-sm font-bold text-slate-800 truncate"><?php echo htmlspecialchars($bk['FacilityName']); ?></h4>
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold <?php echo $statusColor; ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?php echo $statusDot; ?>"></span>
                                <?php echo $bk['Status']; ?>
                            </span>
                        </div>
                        <div class="flex items-center gap-4 text-xs text-slate-500">
                            <span class="flex items-center gap-1.5 truncate">
                                <i class="fa-regular fa-clock text-slate-400"></i> <?php echo $bk['formatted_time']; ?>
                            </span>
                            <span class="flex items-center gap-1.5 truncate">
                                <i class="fa-solid fa-location-dot text-slate-400"></i> <?php echo htmlspecialchars($bk['Location']); ?>
                            </span>
                        </div>
                    </div>

                    <div class="flex-shrink-0 flex items-center gap-2 mt-2 sm:mt-0">
                             <?php if (!$bk['is_passed'] && in_array($bk['Status'], ['Pending', 'Approved', 'Confirmed'])): ?>
                                <button onclick="showCancelModal(<?php echo $bk['BookingID']; ?>)" 
                                    title="Cancel Booking"
                                    class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-red-50 hover:text-red-600 transition flex items-center justify-center">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            <?php elseif ($bk['is_passed'] && in_array($bk['Status'], ['Approved', 'Confirmed'])): ?>
                                <button onclick="openFeedback(this, <?php echo $bk['BookingID']; ?>)" 
                                    data-facility="<?php echo htmlspecialchars($bk['FacilityName']); ?>"
                                    data-date="<?php echo htmlspecialchars($bk['formatted_start']); ?>"
                                    class="px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-600 text-xs font-bold hover:bg-indigo-100 transition">
                                    Feedback
                                </button>
                            <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="mt-8 bg-slate-900 rounded-2xl p-8 text-white relative overflow-hidden shadow-2xl group">
        <div class="absolute right-0 top-0 h-full w-1/3 bg-white/5 skew-x-12 transform origin-top-right group-hover:scale-110 transition-transform duration-700"></div>
        <div class="relative z-10 flex flex-col md:flex-row items-center gap-6">
            <div class="p-4 bg-white/10 rounded-2xl backdrop-blur-sm">
                <i class="fa-solid fa-medal text-4xl text-yellow-400"></i>
            </div>
            <div>
                 <h3 class="text-xl font-bold font-serif mb-2">UKM Sports Center Excellence</h3>
                 <p class="text-slate-300 text-sm leading-relaxed max-w-2xl">
                     Since 1974, we've been dedicated to fostering athletic talent and student well-being.
                     Enjoy world-class facilities right here at the Bangi campus.
                 </p>
            </div>
        </div>
    </div>

</main>


<div id="cancelModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl p-6 md:p-8 w-full max-w-md mx-4 transform scale-95 transition-transform duration-300" id="cancelModalContent">
        <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4 text-red-500">
            <i class="fa-solid fa-triangle-exclamation text-3xl"></i>
        </div>
        <h3 class="text-xl font-bold text-center text-slate-800 mb-2 font-serif">Cancel Booking?</h3>
        <p class="text-center text-slate-500 text-sm mb-8">Are you sure you want to cancel? This action cannot be undone.</p>
        
        <div class="flex gap-3">
            <button onclick="closeCancelModal()" class="flex-1 px-4 py-3 bg-slate-100 text-slate-700 font-bold rounded-xl hover:bg-slate-200 transition">
                No, Keep it
            </button>
            <button id="confirmCancelBtn" class="flex-1 px-4 py-3 bg-red-600 text-white font-bold rounded-xl hover:bg-red-700 shadow-lg shadow-red-200 transition">
                Yes, Cancel
            </button>
        </div>
    </div>
</div>

<script>
let currentBookingId = null;

// ===========================================
// START: FETCH CANCELLATION HEALTH INTEGRATION
// ===========================================

// Target elements for the Cancellation Card
const CARD_ELEMENTS = {
    rateValue: document.getElementById('health-score-percent'),
    tag: document.getElementById('health-status-tag'),
    message: document.getElementById('health-status-message'),
    bar: document.getElementById('health-progress-bar'),
};

// Tailwind Class Mapping based on status_color from PHP API
// This is used to override the hardcoded colors (red/amber) with the correct status colors
const STATUS_CLASSES = {
    // Low Rate (Good)
    'green': { tagBg: 'bg-green-100', tagText: 'text-green-700', barBg: 'bg-green-500', scoreText: 'text-green-600' },
    // Moderate Rate (Warning)
    'amber': { tagBg: 'bg-amber-100', tagText: 'text-amber-700', barBg: 'bg-amber-500', scoreText: 'text-amber-600' },
    // High Rate (Risk)
    'red': { tagBg: 'bg-red-100', tagText: 'text-red-700', barBg: 'bg-red-600', scoreText: 'text-red-600' }
};

// Helper to clear old CSS classes (e.g., removes the default red color before adding green)
function resetClasses(element, prefix) {
    const classes = Array.from(element.classList);
    classes.forEach(cls => {
        // Remove all classes that start with the prefix (e.g., 'text-', 'bg-')
        if (cls.startsWith(prefix)) {
            element.classList.remove(cls);
        }
    });
}

async function fetchCancellationHealth() {
    if (!CARD_ELEMENTS.rateValue) return; 

    try {
        const response = await fetch('/UKM-SFBS/student/API/fetch_cancellation_health.php'); 
        const result = await response.json();

        if (result.success && result.data) {
            const data = result.data;
            const color = data.status_color;
            const classes = STATUS_CLASSES[color] || STATUS_CLASSES['green']; 

            // --- 1. Update Rate Percentage ---
            CARD_ELEMENTS.rateValue.textContent = `${data.rate_value}%`; 
            // Reset and apply new text color
            resetClasses(CARD_ELEMENTS.rateValue, 'text-');
            CARD_ELEMENTS.rateValue.classList.add(classes.scoreText, 'drop-shadow-lg'); 

            // --- 2. Update Status Tag ---
            CARD_ELEMENTS.tag.textContent = data.rate_status;
            // Reset and apply new background and text color
            resetClasses(CARD_ELEMENTS.tag, 'bg-');
            resetClasses(CARD_ELEMENTS.tag, 'text-');
            CARD_ELEMENTS.tag.classList.add(classes.tagBg, classes.tagText);

            // --- 3. Update Status Message ---
            CARD_ELEMENTS.message.textContent = data.message;

            // --- 4. Update Progress Bar ---
            CARD_ELEMENTS.bar.style.width = `${data.rate_value}%`; 
            // Reset and apply new bar color
            resetClasses(CARD_ELEMENTS.bar, 'bg-');
            CARD_ELEMENTS.bar.classList.add(classes.barBg);

        } else {
            CARD_ELEMENTS.message.textContent = `Error: ${result.data ? result.data.message : 'Failed to fetch rate data.'}`;
            CARD_ELEMENTS.tag.textContent = 'Error';
            console.error('API Error:', result);
        }

    } catch (error) {
        CARD_ELEMENTS.message.textContent = 'Network or Server connection error.';
        CARD_ELEMENTS.tag.textContent = 'Network Error';
        console.error('Fetch Error:', error);
    }
}

// NOTE: You would place fetchActiveBookings() here when ready.

// ===========================================
// END: FETCH CANCELLATION HEALTH INTEGRATION
// ===========================================


// ===========================================
// START: EXISTING MODAL & DROPDOWN LOGIC
// (Your original code, unchanged)
// ===========================================

// Modal Logic
function showCancelModal(id) {
    currentBookingId = id;
    const modal = document.getElementById('cancelModal');
    const content = document.getElementById('cancelModalContent');
    
    modal.classList.remove('hidden');
    // small delay to allow display:block to apply before opacity transition
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        content.classList.remove('scale-95');
        content.classList.add('scale-100');
    }, 10);
    
    document.getElementById('confirmCancelBtn').onclick = function() {
        processCancellation(currentBookingId);
    };
}

function closeCancelModal() {
    const modal = document.getElementById('cancelModal');
    const content = document.getElementById('cancelModalContent');
    
    modal.classList.add('opacity-0');
    content.classList.remove('scale-100');
    content.classList.add('scale-95');
    
    setTimeout(() => {
        modal.classList.add('hidden');
        currentBookingId = null;
    }, 300);
}

// Cancel Booking Fetch (Assume cancel_booking.php exists)
function processCancellation(id) {
    const btn = document.getElementById('confirmCancelBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('booking_id', id);

    fetch('cancel_booking.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            // Optional: Success visual before reload
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Canceled!';
            btn.classList.remove('bg-red-600');
            btn.classList.add('bg-green-600');
            setTimeout(() => {
                location.reload(); 
            }, 800);
        } else {
            alert("Error: " + (data.message || "Unknown error"));
            closeCancelModal();
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(err => {
        console.error(err);
        alert("Network error. Please try again.");
        closeCancelModal();
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// FIX: openFeedback function now reads from data attributes for cleaner data handling
function openFeedback(buttonElement, bookingId) {
    const facilityName = buttonElement.getAttribute('data-facility');
    const date = buttonElement.getAttribute('data-date');
    
    // Encode values to safely include them in the URL
    const url = `feedback.php?booking_id=${bookingId}&facility_name=${encodeURIComponent(facilityName)}&date=${encodeURIComponent(date)}`;
    
    window.location.href = url;
}


// Dropdown Interactions
const profileBtn = document.getElementById('profileBtn');
const dropdownMenu = document.getElementById('dropdownMenu');
const profileContainer = document.getElementById('profileDropdownContainer');
let isDropdownOpen = false;

profileBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    isDropdownOpen = !isDropdownOpen;
    toggleDropdown();
});

function toggleDropdown() {
    if (isDropdownOpen) {
        dropdownMenu.classList.remove('hidden');
        // Animate open
        requestAnimationFrame(() => {
            dropdownMenu.classList.remove('opacity-0', 'scale-95');
            dropdownMenu.classList.add('opacity-100', 'scale-100');
        });
    } else {
        // Animate close
        dropdownMenu.classList.remove('opacity-100', 'scale-100');
        dropdownMenu.classList.add('opacity-0', 'scale-95');
        setTimeout(() => {
            dropdownMenu.classList.add('hidden');
        }, 150);
    }
}

document.addEventListener('click', (e) => {
    if(isDropdownOpen && !profileContainer.contains(e.target)) {
        isDropdownOpen = false;
        toggleDropdown();
    }
});
// ===========================================
// END: EXISTING MODAL & DROPDOWN LOGIC
// ===========================================


// EXECUTE FUNCTIONS ON PAGE LOAD
document.addEventListener('DOMContentLoaded', function() {
    // Call function to load Cancellation Health data
    fetchCancellationHealth();
    // fetchActiveBookings(); // Uncomment this when the corresponding API is ready
});
</script>

<?php include './includes/footer.php'; ?>
<script src="../assets/js/idle_timer.js"></script>
</body>
</html>