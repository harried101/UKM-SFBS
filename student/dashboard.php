<?php
session_start();

// Redirect if not logged in or not a student
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Student') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/db_connect.php';

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

// 2. Fetch Active Bookings (Future + Pending/Approved)
$activeBookings = [];
if ($db_numeric_id > 0) {
    $sql = "SELECT b.BookingID, b.StartTime, b.EndTime, b.Status, f.Name as FacilityName
            FROM bookings b
            JOIN facilities f ON b.FacilityID = f.FacilityID
            WHERE b.UserID = ? 
            AND b.StartTime > NOW() 
            AND b.Status IN ('Pending', 'Approved')
            ORDER BY b.StartTime ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $db_numeric_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $activeBookings[] = $row;
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
    --primary: #8a0d19; /* UKM Red matching facilities theme */
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

/* Custom Scrollbar for tables if needed */
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
</style>
</head>
<body>

<!-- NAVBAR (Matches student_facilities.php) -->
<nav class="bg-white/90 backdrop-blur-md border-b border-gray-200 sticky top-0 z-50">
    <div class="container mx-auto px-6 py-3 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <img src="../assets/img/ukm.png" alt="UKM Logo" class="h-14 w-auto">
            <div class="h-8 w-px bg-gray-300 hidden sm:block"></div>
            <img src="../assets/img/pusatsukanlogo.png" alt="Pusat Sukan Logo" class="h-14 w-auto hidden sm:block">
        </div>
        <div class="flex items-center gap-6">
            <a href="dashboard.php" class="text-[#8a0d19] font-medium transition flex items-center gap-2 group">
                <span class="p-2 rounded-full bg-[#8a0d19] text-white transition">
                    <i class="fa-solid fa-house"></i>
                </span>
                <span class="hidden md:inline">Home</span>
            </a>
            <a href="student_facilities.php" class="text-gray-600 hover:text-[#8a0d19] font-medium transition">Facilities</a>
            <!-- Using simple link for history to keep UI clean, can also be a tab -->
            <a href="#" class="text-gray-600 hover:text-[#8a0d19] font-medium transition">History</a>

            <div class="flex items-center gap-3 pl-6 border-l border-gray-200">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($studentName); ?></p>
                    <p class="text-xs text-gray-500 uppercase tracking-wider"><?php echo htmlspecialchars($studentID); ?></p>
                </div>
                <div class="relative group">
                    <img src="../assets/img/user.png" alt="Profile" class="w-10 h-10 rounded-full border-2 border-white shadow-md object-cover cursor-pointer">
                    <!-- Simple Dropdown -->
                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-100 hidden group-hover:block">
                        <a href="../logout.php" onclick="return confirm('Are you sure you want to logout?');" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg m-1">
                            <i class="fa-solid fa-right-from-bracket mr-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- BANNER -->
<div class="w-full h-64 md:h-80 overflow-hidden relative shadow-md">
    <div class="absolute inset-0 bg-black/30 z-10"></div>
    <img src="../assets/img/psukan.jpg" alt="Pusat Sukan" class="w-full h-full object-cover">
    <div class="absolute inset-0 z-20 flex flex-col items-center justify-center text-white text-center px-4">
        <h1 class="text-4xl md:text-5xl font-bold mb-2 drop-shadow-lg">Welcome back, <?php echo htmlspecialchars($studentName); ?>!</h1>
        <p class="text-lg opacity-90 font-light">Manage your sports activities efficiently.</p>
    </div>
</div>

<main class="container mx-auto px-6 py-12 flex-grow max-w-6xl">

    <!-- ACTIVE BOOKINGS SECTION -->
    <div class="mb-16">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl md:text-3xl font-bold text-[#8a0d19] flex items-center gap-3">
                <i class="fa-solid fa-calendar-check"></i> Active Bookings
            </h2>
            <a href="student_facilities.php" class="text-sm font-semibold text-[#8a0d19] hover:underline flex items-center gap-1">
                Book New <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#8a0d19] text-white text-sm uppercase tracking-wider">
                            <th class="p-4 font-semibold">Facility</th>
                            <th class="p-4 font-semibold">Date & Time</th>
                            <th class="p-4 font-semibold text-center">Status</th>
                            <th class="p-4 font-semibold text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700">
                        <?php if (empty($activeBookings)): ?>
                            <tr>
                                <td colspan="4" class="p-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <i class="fa-regular fa-calendar-xmark text-4xl mb-3 text-gray-300"></i>
                                        <p>No active bookings found.</p>
                                        <a href="student_facilities.php" class="mt-2 text-[#8a0d19] font-semibold hover:underline">Book a facility now</a>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($activeBookings as $bk): 
                                $startObj = new DateTime($bk['StartTime']);
                                $endObj = new DateTime($bk['EndTime']);
                                $statusClass = ($bk['Status'] === 'Approved') 
                                    ? 'bg-green-100 text-green-700 border-green-200' 
                                    : 'bg-yellow-100 text-yellow-700 border-yellow-200';
                            ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                                <td class="p-4 font-bold text-gray-800">
                                    <?php echo htmlspecialchars($bk['FacilityName']); ?>
                                </td>
                                <td class="p-4">
                                    <div class="font-semibold text-gray-900"><?php echo $startObj->format('d M Y'); ?></div>
                                    <div class="text-sm text-gray-500 flex items-center gap-1">
                                        <i class="fa-regular fa-clock"></i>
                                        <?php echo $startObj->format('h:i A') . ' - ' . $endObj->format('h:i A'); ?>
                                    </div>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold border <?php echo $statusClass; ?>">
                                        <?php echo $bk['Status']; ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button onclick="cancelBooking(<?php echo $bk['BookingID']; ?>)" 
                                                class="px-4 py-2 bg-white border border-red-200 text-red-600 rounded-lg text-sm font-semibold hover:bg-red-50 hover:border-red-300 transition shadow-sm">
                                            Cancel
                                        </button>
                                        <!-- Review Button (Visual only for now) -->
                                        <button class="px-4 py-2 bg-gray-100 text-gray-400 border border-gray-200 rounded-lg text-sm font-semibold cursor-not-allowed" title="Available after session">
                                            Review
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- COUNTERS SECTION -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-16">
        <div class="bg-white p-8 rounded-xl shadow-md border border-gray-100 text-center hover:shadow-xl transition transform hover:-translate-y-1">
            <div class="w-16 h-16 mx-auto bg-red-50 rounded-full flex items-center justify-center text-[#8a0d19] text-2xl mb-4">
                <i class="fa-solid fa-dumbbell"></i>
            </div>
            <div class="text-5xl font-bold text-[#8a0d19] mb-2 font-serif" id="facility-counter">0</div>
            <div class="text-gray-500 font-medium uppercase tracking-wide">Facilities Available</div>
        </div>
        <div class="bg-white p-8 rounded-xl shadow-md border border-gray-100 text-center hover:shadow-xl transition transform hover:-translate-y-1">
            <div class="w-16 h-16 mx-auto bg-blue-50 rounded-full flex items-center justify-center text-[#0b4d9d] text-2xl mb-4">
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="text-5xl font-bold text-[#0b4d9d] mb-2 font-serif" id="staff-counter">0</div>
            <div class="text-gray-500 font-medium uppercase tracking-wide">Dedicated Staff</div>
        </div>
    </div>

    <!-- INFO CARD -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8 md:p-10 text-center relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-[#8a0d19] to-[#0b4d9d]"></div>
        <h2 class="text-3xl font-bold text-[#8a0d19] mb-4 font-serif">About UKM Sports Center</h2>
        <div class="w-24 h-1 bg-gray-200 mx-auto mb-6"></div>
        <p class="text-gray-600 leading-relaxed max-w-4xl mx-auto">
            The UKM Sports Center started on 1 November 1974 with a Sports Officer from the Ministry of Education who managed sports activities for students and staff. In 1981 and 1982, UKM participated in the ASEAN University Games. In 2008, the Sports Unit was upgraded to the Sports Center, and in 2010, a director was appointed. Today, the center continues to serve as the hub for sporting excellence with 47 staff members.
        </p>
    </div>

</main>

<!-- FOOTER (Matches student_facilities.php) -->
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

<!-- SCRIPTS -->
<script>
// 1. Counter Logic
let facilityCount = 0;
let staffCount = 0;
const facilityTarget = 15;
const staffTarget = 47;
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
        setTimeout(incrementCounters, 40); // Slightly faster animation
    }
}
// Start animation when page loads
window.onload = incrementCounters;

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