<?php
session_start();

// Redirect if not logged in or not a student
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Student') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/db_connect.php';

// Timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

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
        $studentID      = $rowStudent['UserIdentifier'];
        $db_numeric_id = (int)$rowStudent['UserID'];
    }
    $stmtStudent->close();
}

// -----------------------------
// 2. FETCH ALL BOOKINGS
// -----------------------------
$all_bookings = [];
$now = new DateTime(); // current KL time

if ($db_numeric_id > 0) {
    $sql = "
        SELECT 
            b.BookingID,
            b.StartTime,
            b.EndTime,
            b.Status,
            f.Name AS FacilityName,
            f.Type,
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
        // StartTime logic
        if ($row['StartTime'] === '0000-00-00 00:00:00' || empty($row['StartTime'])) {
            $start = new DateTime($row['EndTime']);
        } else {
            $start = new DateTime($row['StartTime']);
        }

        // EndTime logic: combine StartTime date with EndTime clock components
        $end = new DateTime($row['StartTime']);
        $end->setTime(
            (int)date('H', strtotime($row['EndTime'])),
            (int)date('i', strtotime($row['EndTime'])),
            (int)date('s', strtotime($row['EndTime']))
        );

        // Determine if the booking session has ended
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
    die("DB Connection failed: " . $conn->connect_error);
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
:root {
    --primary: #8a0d19; /* UKM Red */
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

h1, h2, h3 { font-family: 'Playfair Display', serif; }

.fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

/* Tab link styles */
.tab-link {
    position: relative;
    padding-bottom: 0.5rem;
    font-weight: 600;
    transition: all 0.3s;
}
.tab-link.active {
    color: var(--primary);
}
.tab-link.active::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 100%;
    height: 2px;
    background-color: var(--primary);
}
.tab-link:not(.active) {
    color: #64748b;
}
.tab-link:not(.active):hover {
    color: var(--primary);
}
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
            <a href="dashboard.php" class="text-[#8a0d19] font-semibold transition flex items-center gap-2 group relative text-decoration-none">
                <span>Home</span>
                <span class="absolute -bottom-1 left-0 w-full h-0.5 bg-[#8a0d19] rounded-full"></span>
            </a>
            <a href="student_facilities.php" class="text-slate-500 hover:text-[#8a0d19] font-medium transition hover:scale-105 text-decoration-none">Facilities</a>

            <div class="flex items-center gap-4 pl-6 border-l border-slate-200">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($studentName); ?></p>
                    <p class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold"><?php echo htmlspecialchars($studentID); ?></p>
                </div>
                <div class="relative group">
                    <img src="../assets/img/user.png" alt="Profile" class="w-10 h-10 rounded-full border-2 border-white ring-2 ring-slate-100 object-cover cursor-pointer transition transform group-hover:scale-105">
                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-slate-100 hidden group-hover:block z-50 overflow-hidden">
                        <a href="../logout.php" onclick="return confirm('Are you sure you want to logout?');" class="block px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition flex items-center gap-2 text-decoration-none">
                            <i class="fa-solid fa-right-from-bracket"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- MAIN CONTENT -->
<main class="container mx-auto px-6 py-12 flex-grow max-w-7xl relative z-20">

    <!-- WELCOME GREETING -->
    <div class="mb-12 fade-in">
        <h1 class="text-3xl md:text-4xl font-bold text-[#8a0d19] mb-2 font-serif">Welcome back, <?php echo htmlspecialchars($studentName); ?>!</h1>
        <div class="w-20 h-1 bg-[#8a0d19] rounded-full opacity-50"></div>
        <p class="text-slate-500 mt-4 max-w-2xl">Manage your sports activities and track your bookings below.</p>
    </div>

    <!-- PAGE HEADER & ACTION -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-6">
        <div class="flex items-center gap-8 border-b border-slate-200 w-full md:w-auto">
            <a href="dashboard.php" class="tab-link active text-decoration-none text-sm uppercase tracking-wider">Active Bookings</a>
            <a href="booking_history.php" class="tab-link text-decoration-none text-sm uppercase tracking-wider">Booking History</a>
        </div>
        <a href="student_facilities.php" class="bg-[#8a0d19] hover:bg-[#6d0a13] text-white px-6 py-2.5 rounded-full font-bold shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5 flex items-center gap-2 text-decoration-none">
            <i class="fa-solid fa-plus-circle"></i> New Booking
        </a>
    </div>

    <!-- BOOKINGS CONTAINER -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-xl overflow-hidden min-h-[400px] flex flex-col fade-in">
        <div class="p-8 flex-grow bg-slate-50/50">
            
            <?php if (empty($all_bookings)): ?>
                <div class="flex flex-col items-center justify-center h-80 text-center">
                    <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mb-6 shadow-sm text-slate-300">
                        <i class="fa-regular fa-calendar-xmark text-3xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-2">No Bookings Found</h3>
                    <p class="text-slate-500 mb-6 max-w-xs text-sm">You haven't made any bookings yet.</p>
                    <a href="student_facilities.php" class="text-[#8a0d19] text-sm font-bold hover:underline flex items-center gap-2 group text-decoration-none">
                        Browse Facilities <i class="fa-solid fa-arrow-right transition-transform group-hover:translate-x-1"></i>
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($all_bookings as $bk): 
                        // Determine badge color
                        $statusClass = 'bg-slate-100 text-slate-500 border-slate-200';
                        if (in_array($bk['Status'], ['Approved', 'Confirmed'])) {
                            $statusClass = 'bg-green-100 text-green-700 border-green-200';
                        } elseif ($bk['Status'] === 'Pending') {
                            $statusClass = 'bg-yellow-50 text-yellow-700 border-yellow-200';
                        } elseif (in_array($bk['Status'], ['Cancelled', 'Rejected'])) {
                            $statusClass = 'bg-red-50 text-red-700 border-red-200';
                        }
                    ?>
                    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all flex flex-col md:flex-row items-center justify-between gap-6 group">
                        <div class="flex items-center gap-6 w-full">
                            <!-- Date Badge -->
                            <div class="bg-slate-50 rounded-xl p-3 min-w-[70px] text-center border border-slate-100 <?php echo $bk['is_passed'] ? 'opacity-50' : ''; ?>">
                                <span class="block text-xl font-bold text-[#8a0d19] font-serif"><?php echo $bk['day']; ?></span>
                                <span class="block text-[10px] uppercase font-bold text-slate-400 tracking-wider"><?php echo $bk['month']; ?></span>
                            </div>
                            <!-- Info -->
                            <div class="<?php echo $bk['is_passed'] ? 'opacity-60' : ''; ?>">
                                <h4 class="font-bold text-slate-800 text-lg mb-1 group-hover:text-[#8a0d19] transition-colors"><?php echo htmlspecialchars($bk['FacilityName']); ?></h4>
                                <div class="flex flex-wrap gap-4 text-sm text-slate-500 font-medium">
                                    <span class="flex items-center gap-1.5"><i class="fa-regular fa-clock text-slate-400"></i> <?php echo $bk['formatted_time']; ?></span>
                                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-location-dot text-slate-400"></i> <?php echo htmlspecialchars($bk['Location']); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Status & Action Buttons -->
                        <div class="flex items-center gap-3 w-full md:w-auto justify-between md:justify-end border-t md:border-t-0 border-slate-100 pt-4 md:pt-0">
                            <span class="px-3 py-1 rounded-full text-xs font-bold border <?php echo $statusClass; ?>">
                                <?php echo $bk['Status']; ?>
                            </span>
                            
                            <div class="flex gap-2">
                                <?php if (!$bk['is_passed'] && in_array($bk['Status'], ['Pending', 'Approved', 'Confirmed'])): ?>
                                    <!-- CANCEL Button: Shown for active upcoming bookings -->
                                    <button onclick="cancelBooking(<?php echo $bk['BookingID']; ?>)" 
                                            class="text-red-500 hover:text-white border border-red-200 hover:bg-red-500 hover:border-red-500 px-4 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-2">
                                        Cancel
                                    </button>
                                <?php elseif ($bk['is_passed'] && in_array($bk['Status'], ['Approved', 'Confirmed'])): ?>
                                    <!-- FEEDBACK Button: Shown for completed approved bookings -->
                                    <button onclick="openFeedback(<?php echo $bk['BookingID']; ?>)" 
                                            class="text-blue-600 hover:text-white border border-blue-200 hover:bg-blue-600 hover:border-blue-600 px-4 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-2">
                                        Feedback
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ABOUT SECTION -->
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
            <div class="space-y-4">
                <img src="../assets/img/pusatsukanlogo.png" alt="Pusat Sukan" class="h-14">
                <p class="text-xs text-slate-500 leading-relaxed max-w-xs">
                    Empowering students through sports excellence and state-of-the-art facilities management.
                </p>
            </div>
            <div>
                <h4 class="text-xs font-bold text-slate-900 uppercase tracking-widest mb-4">Quick Access</h4>
                <ul class="space-y-2 text-sm text-slate-600">
                    <li><a href="dashboard.php" class="hover:text-[#8a0d19] transition text-decoration-none">Dashboard</a></li>
                    <li><a href="student_facilities.php" class="hover:text-[#8a0d19] transition text-decoration-none">Browse Facilities</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-xs font-bold text-slate-900 uppercase tracking-widest mb-4">Contact Us</h4>
                <div class="text-sm text-slate-600 space-y-2">
                    <p class="font-medium">Stadium Universiti, UKM</p>
                    <p>43600 Bangi, Selangor</p>
                    <p class="text-[#8a0d19] font-bold mt-2"><i class="fa-solid fa-phone mr-2"></i> 03-8921 5306</p>
                </div>
            </div>
        </div>
        <div class="border-t border-slate-100 mt-12 pt-8">
            <p class="text-[10px] text-slate-400">© 2025 Universiti Kebangsaan Malaysia. All rights reserved.</p>
        </div>
    </div>
</footer>

<script>
function cancelBooking(id) {
    if(!confirm("Are you sure you want to cancel this booking?")) return;

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

function openFeedback(id) {
    window.location.href = "feedback.php?booking_id=" + id;
}
</script>

</body>
</html>