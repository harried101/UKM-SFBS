<?php
session_start();

// 1. Auth Check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/db_connect.php';

// 2. Fetch Admin Details
$adminIdentifier = $_SESSION['user_id'] ?? '';
$adminName = 'Administrator';

if ($adminIdentifier) {
    $stmt = $conn->prepare("SELECT FirstName, LastName FROM users WHERE UserIdentifier = ?");
    $stmt->bind_param("s", $adminIdentifier);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($r = $res->fetch_assoc()) {
        $adminName = $r['FirstName'] . ' ' . $r['LastName'];
    }
    $stmt->close();
}

// 3. Fetch Statistics
// Pending Bookings
$sqlPending = "SELECT COUNT(*) FROM bookings WHERE Status = 'Pending'";
$countPending = $conn->query($sqlPending)->fetch_row()[0] ?? 0;

// Today's Bookings
$sqlToday = "SELECT COUNT(*) FROM bookings WHERE DATE(StartTime) = CURDATE() AND Status IN ('Approved', 'Confirmed')";
$countToday = $conn->query($sqlToday)->fetch_row()[0] ?? 0;

// Active Facilities
$sqlFacilities = "SELECT COUNT(*) FROM facilities WHERE Status = 'Active'";
$countFacilities = $conn->query($sqlFacilities)->fetch_row()[0] ?? 0;

// Recent Bookings (Limit 5)
$sqlRecent = "SELECT b.BookingID, b.Status, b.StartTime, f.Name as FacilityName, u.UserIdentifier 
              FROM bookings b
              JOIN facilities f ON b.FacilityID = f.FacilityID
              JOIN users u ON b.UserID = u.UserID
              ORDER BY b.BookedAt DESC LIMIT 5";
$resRecent = $conn->query($sqlRecent);

if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard – UKM Sports Center</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
:root {
    --primary: #0b4d9d;
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
            <!-- Active Home -->
            <a href="dashboard.php" class="text-[#0b4d9d] font-bold transition flex items-center gap-2 group">
                <span class="p-2 rounded-full bg-[#0b4d9d] text-white transition shadow-sm">
                    <i class="fa-solid fa-house"></i>
                </span>
                <span class="hidden md:inline">Dashboard</span>
            </a>
            
            <a href="addfacilities.php" class="text-gray-600 hover:text-[#0b4d9d] font-medium transition">Facilities</a>
            <a href="manage_bookings.php" class="text-gray-600 hover:text-[#0b4d9d] font-medium transition">Bookings</a>

            <div class="flex items-center gap-3 pl-6 border-l border-gray-200">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($adminName); ?></p>
                    <p class="text-xs text-gray-500 uppercase tracking-wider">Administrator</p>
                </div>
                <div class="relative group">
                    <img src="../assets/img/user.png" alt="Profile" class="w-10 h-10 rounded-full border-2 border-white shadow-md object-cover cursor-pointer hover:scale-105 transition">
                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-100 hidden group-hover:block z-50">
                        <a href="../logout.php" onclick="return confirm('Logout?');" class="block px-4 py-3 text-sm text-red-600 hover:bg-red-50 rounded-lg m-1">
                            <i class="fa-solid fa-right-from-bracket mr-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- HERO BANNER -->
<div class="w-full h-64 md:h-72 overflow-hidden relative shadow-md group">
    <img src="../assets/img/psukan.jpg" alt="Pusat Sukan" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
</div>

<!-- WELCOME CARD -->
<div class="container mx-auto px-6 -mt-12 relative z-20 mb-10">
    <div class="bg-white rounded-xl shadow-lg p-6 md:p-8 flex flex-col md:flex-row items-center justify-between gap-6 border border-gray-100">
        <div>
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800 font-serif mb-2">
                Hello, <span class="text-[#0b4d9d]"><?php echo htmlspecialchars($adminName); ?></span>
            </h1>
            <p class="text-gray-500">Here's what's happening at the Sports Center today.</p>
        </div>
        <div class="flex gap-3">
             <a href="book_walkin.php" class="bg-white border border-gray-300 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-50 transition font-medium shadow-sm flex items-center gap-2">
                <i class="fa-solid fa-user-pen"></i> Walk-in
            </a>
            <a href="manage_bookings.php" class="bg-[#0b4d9d] text-white px-6 py-3 rounded-lg shadow-md hover:bg-[#083a75] transition font-medium flex items-center gap-2">
                <i class="fa-solid fa-list-check"></i> Manage Requests
            </a>
        </div>
    </div>
</div>

<main class="container mx-auto px-6 pb-12 flex-grow max-w-6xl relative z-30 fade-in">

    <!-- STATS GRID -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <!-- Stat 1: Pending -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition cursor-pointer" onclick="window.location.href='manage_bookings.php?status=Pending'">
            <div>
                <div class="text-gray-500 text-xs uppercase font-bold tracking-wider mb-1">Pending Approvals</div>
                <div class="text-4xl font-bold text-yellow-600 font-serif"><?php echo $countPending; ?></div>
            </div>
            <div class="w-12 h-12 rounded-full bg-yellow-50 flex items-center justify-center text-yellow-600 text-xl">
                <i class="fa-solid fa-clock"></i>
            </div>
        </div>

        <!-- Stat 2: Today -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition">
            <div>
                <div class="text-gray-500 text-xs uppercase font-bold tracking-wider mb-1">Today's Bookings</div>
                <div class="text-4xl font-bold text-[#0b4d9d] font-serif"><?php echo $countToday; ?></div>
            </div>
            <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center text-[#0b4d9d] text-xl">
                <i class="fa-regular fa-calendar-check"></i>
            </div>
        </div>

        <!-- Stat 3: Facilities -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition cursor-pointer" onclick="window.location.href='addfacilities.php'">
            <div>
                <div class="text-gray-500 text-xs uppercase font-bold tracking-wider mb-1">Active Facilities</div>
                <div class="text-4xl font-bold text-green-700 font-serif"><?php echo $countFacilities; ?></div>
            </div>
            <div class="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center text-green-700 text-xl">
                <i class="fa-solid fa-building-circle-check"></i>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- RECENT ACTIVITY (Takes up 2 cols) -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-gray-800">Recent Bookings</h3>
                <a href="manage_bookings.php" class="text-xs font-bold text-[#0b4d9d] hover:underline">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-white text-gray-500 border-b border-gray-100">
                        <tr>
                            <th class="p-4 font-semibold">Student</th>
                            <th class="p-4 font-semibold">Facility</th>
                            <th class="p-4 font-semibold">Date</th>
                            <th class="p-4 font-semibold text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if ($resRecent && $resRecent->num_rows > 0): ?>
                            <?php while ($row = $resRecent->fetch_assoc()): 
                                $statusClass = match($row['Status']) {
                                    'Pending' => 'bg-yellow-100 text-yellow-800',
                                    'Approved', 'Confirmed' => 'bg-green-100 text-green-800',
                                    'Cancelled', 'Rejected' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                            ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-4 font-medium text-gray-900"><?php echo htmlspecialchars($row['UserIdentifier']); ?></td>
                                <td class="p-4 text-gray-600"><?php echo htmlspecialchars($row['FacilityName']); ?></td>
                                <td class="p-4 text-gray-500"><?php echo date('d M, h:i A', strtotime($row['StartTime'])); ?></td>
                                <td class="p-4 text-center">
                                    <span class="px-2 py-1 rounded text-xs font-bold <?php echo $statusClass; ?>">
                                        <?php echo $row['Status']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="p-6 text-center text-gray-400">No recent activity.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- QUICK LINKS (Takes up 1 col) -->
        <div class="flex flex-col gap-6">
            <!-- Card 1 -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition group cursor-pointer" onclick="window.location.href='addfacilities.php'">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-[#0b4d9d] group-hover:bg-[#0b4d9d] group-hover:text-white transition">
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800">Add Facility</h4>
                        <p class="text-xs text-gray-500">Create new courts or update details.</p>
                    </div>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition group cursor-pointer" onclick="window.location.href='manage_bookings.php'">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-purple-50 flex items-center justify-center text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition">
                        <i class="fa-solid fa-filter"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800">Filter Bookings</h4>
                        <p class="text-xs text-gray-500">View by date, status, or student.</p>
                    </div>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition group cursor-pointer" onclick="window.location.href='addfacilities.php?tab=closures'">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-600 group-hover:bg-red-600 group-hover:text-white transition">
                        <i class="fa-solid fa-ban"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800">Block Dates</h4>
                        <p class="text-xs text-gray-500">Close facilities for maintenance.</p>
                    </div>
                </div>
            </div>
        </div>

    </div>

</main>

<!-- FOOTER -->
<footer class="bg-white border-t border-gray-200 py-6 mt-auto">
    <div class="container mx-auto px-6">
        <div class="flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-4">
                <img src="../assets/img/pusatsukanlogo.png" alt="Logo" class="h-12 w-auto">
                <div class="text-xs text-gray-600 leading-snug">
                    <strong class="block text-gray-800 text-sm mb-0.5">PEJABAT PENGARAH PUSAT SUKAN</strong>
                    Stadium Universiti, UKM, 43600 Bangi<br>
                    <span class="mt-0.5 block text-[#0b4d9d] font-semibold"><i class="fa-solid fa-phone mr-1"></i> 03-8921-5306</span>
                </div>
            </div>
            <div class="flex items-center gap-6">
                <img src="../assets/img/sdg.png" alt="SDG" class="h-14 w-auto opacity-90">
                <p class="text-[10px] text-gray-400 text-right">&copy; 2025 Universiti Kebangsaan Malaysia.<br>All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>

</body>
</html>