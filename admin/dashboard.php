<?php
require_once 'includes/admin_auth.php'; // Standardized Auth & User Fetch

// ============================================================================
// ANALYTICS DATA QUERIES
// ============================================================================

// 1. ENHANCED STATS CARDS
// Pending Bookings
$sqlPending = "SELECT COUNT(*) FROM bookings WHERE Status = 'Pending'";
$countPending = $conn->query($sqlPending)->fetch_row()[0] ?? 0;

// Today's Bookings
$sqlToday = "SELECT COUNT(*) FROM bookings WHERE DATE(StartTime) = CURDATE() AND Status IN ('Confirmed', 'Pending')";
$countToday = $conn->query($sqlToday)->fetch_row()[0] ?? 0;

// Active Facilities
$sqlFacilities = "SELECT COUNT(*) FROM facilities WHERE Status = 'Active'";
$countFacilities = $conn->query($sqlFacilities)->fetch_row()[0] ?? 0;

// This Month's Bookings
$sqlThisMonth = "SELECT COUNT(*) FROM bookings WHERE MONTH(BookedAt) = MONTH(CURDATE()) AND YEAR(BookedAt) = YEAR(CURDATE())";
$countThisMonth = $conn->query($sqlThisMonth)->fetch_row()[0] ?? 0;

// Last Month's Bookings (for comparison)
$sqlLastMonth = "SELECT COUNT(*) FROM bookings WHERE MONTH(BookedAt) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(BookedAt) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
$countLastMonth = $conn->query($sqlLastMonth)->fetch_row()[0] ?? 1; // Avoid division by zero

// Calculate percentage change
$monthlyChange = $countLastMonth > 0 ? (($countThisMonth - $countLastMonth) / $countLastMonth) * 100 : 0;

// 2. BOOKING TRENDS (Last 30 Days)
$sqlTrends = "SELECT 
    DATE(BookedAt) as BookingDate,
    COUNT(*) as TotalBookings,
    SUM(CASE WHEN Status = 'Confirmed' THEN 1 ELSE 0 END) as Confirmed,
    SUM(CASE WHEN Status = 'Pending' THEN 1 ELSE 0 END) as Pending,
    SUM(CASE WHEN Status = 'Canceled' THEN 1 ELSE 0 END) as Canceled
FROM bookings 
WHERE BookedAt >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DATE(BookedAt)
ORDER BY BookingDate ASC";
$resTrends = $conn->query($sqlTrends);
$trendDates = [];
$trendTotal = [];
$trendConfirmed = [];
$trendPending = [];
$trendCanceled = [];
while ($row = $resTrends->fetch_assoc()) {
    $trendDates[] = date('M d', strtotime($row['BookingDate']));
    $trendTotal[] = $row['TotalBookings'];
    $trendConfirmed[] = $row['Confirmed'];
    $trendPending[] = $row['Pending'];
    $trendCanceled[] = $row['Canceled'];
}

// 3. FACILITY UTILIZATION (Bookings per Facility)
$sqlFacilityUtil = "SELECT f.Name, f.Type, COUNT(b.BookingID) as BookingCount
FROM facilities f
LEFT JOIN bookings b ON f.FacilityID = b.FacilityID
WHERE f.Status = 'Active'
GROUP BY f.FacilityID, f.Name, f.Type
ORDER BY BookingCount DESC
LIMIT 10";
$resFacilityUtil = $conn->query($sqlFacilityUtil);
$facilityNames = [];
$facilityBookings = [];
$facilityTypes = [];
while ($row = $resFacilityUtil->fetch_assoc()) {
    $facilityNames[] = $row['Name'];
    $facilityBookings[] = $row['BookingCount'];
    $facilityTypes[] = $row['Type'];
}

// 4. STATUS DISTRIBUTION
$sqlStatus = "SELECT Status, COUNT(*) as Count FROM bookings GROUP BY Status";
$resStatus = $conn->query($sqlStatus);
$statusLabels = [];
$statusCounts = [];
while ($row = $resStatus->fetch_assoc()) {
    $statusLabels[] = $row['Status'];
    $statusCounts[] = $row['Count'];
}

// 5. PEAK HOURS ANALYSIS
$sqlPeakHours = "SELECT 
    DAYOFWEEK(StartTime) as DayOfWeek,
    HOUR(StartTime) as Hour,
    COUNT(*) as BookingCount
FROM bookings
WHERE StartTime >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DayOfWeek, Hour
ORDER BY BookingCount DESC
LIMIT 50";
$resPeakHours = $conn->query($sqlPeakHours);
$peakHoursData = [];
while ($row = $resPeakHours->fetch_assoc()) {
    $peakHoursData[] = [
        'day' => $row['DayOfWeek'],
        'hour' => $row['Hour'],
        'count' => $row['BookingCount']
    ];
}

// 6. POPULAR FACILITIES (Top 5)
$sqlPopular = "SELECT f.Name, f.Type, COUNT(b.BookingID) as BookingCount
FROM facilities f
LEFT JOIN bookings b ON f.FacilityID = b.FacilityID
WHERE f.Status = 'Active'
GROUP BY f.FacilityID, f.Name, f.Type
ORDER BY BookingCount DESC
LIMIT 5";
$resPopular = $conn->query($sqlPopular);
$popularFacilities = [];
$maxPopularCount = 1;
while ($row = $resPopular->fetch_assoc()) {
    $popularFacilities[] = $row;
    if ($row['BookingCount'] > $maxPopularCount) {
        $maxPopularCount = $row['BookingCount'];
    }
}

// 7. USER STATISTICS
$sqlTotalUsers = "SELECT COUNT(*) FROM users WHERE Role = 'Student'";
$countTotalUsers = $conn->query($sqlTotalUsers)->fetch_row()[0] ?? 0;

$sqlActiveUsersMonth = "SELECT COUNT(DISTINCT UserID) FROM bookings WHERE MONTH(BookedAt) = MONTH(CURDATE()) AND YEAR(BookedAt) = YEAR(CURDATE())";
$countActiveUsersMonth = $conn->query($sqlActiveUsersMonth)->fetch_row()[0] ?? 0;

$sqlNewUsersMonth = "SELECT COUNT(*) FROM users WHERE Role = 'Student' AND MONTH(CreatedAt) = MONTH(CURDATE()) AND YEAR(CreatedAt) = YEAR(CURDATE())";
$countNewUsersMonth = $conn->query($sqlNewUsersMonth)->fetch_row()[0] ?? 0;

// 8. TOP ACTIVE USERS (This Month)
$sqlTopUsers = "SELECT u.UserIdentifier, u.FirstName, u.LastName, COUNT(b.BookingID) as BookingCount
FROM users u
JOIN bookings b ON u.UserID = b.UserID
WHERE MONTH(b.BookedAt) = MONTH(CURDATE()) AND YEAR(b.BookedAt) = YEAR(CURDATE())
GROUP BY u.UserID, u.UserIdentifier, u.FirstName, u.LastName
ORDER BY BookingCount DESC
LIMIT 5";
$resTopUsers = $conn->query($sqlTopUsers);
$topUsers = [];
while ($row = $resTopUsers->fetch_assoc()) {
    $topUsers[] = $row;
}

// 9. RECENT BOOKINGS (Last 10)
$sqlRecent = "SELECT b.BookingID, b.Status, b.StartTime, b.BookedAt, f.Name as FacilityName, u.UserIdentifier, u.FirstName, u.LastName
FROM bookings b
JOIN facilities f ON b.FacilityID = f.FacilityID
JOIN users u ON b.UserID = u.UserID
ORDER BY b.BookedAt DESC 
LIMIT 10";
$resRecent = $conn->query($sqlRecent);

// 10. UPCOMING BOOKINGS TODAY
$sqlUpcomingToday = "SELECT COUNT(*) FROM bookings WHERE DATE(StartTime) = CURDATE() AND StartTime > NOW() AND Status IN ('Confirmed', 'Pending')";
$countUpcomingToday = $conn->query($sqlUpcomingToday)->fetch_row()[0] ?? 0;

if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Analytics Dashboard – UKM Sports Center</title>
<!-- Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
    :root {
        --primary: #0b4d9d;
        --primary-hover: #063a75;
        --bg-light: #f8fafc;
    }
    body {
        font-family: 'Inter', sans-serif;
        background-color: var(--bg-light);
        color: #1e293b;
    }
    h1, h2, h3, .font-serif {
        font-family: 'Playfair Display', serif;
    }

    /* Animations */
    .fade-in { animation: fadeIn 0.6s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    
    .slide-in { animation: slideIn 0.5s ease-out; }
    @keyframes slideIn { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }

    /* Card Hover */
    .card-hover { transition: all 0.3s ease; }
    .card-hover:hover { transform: translateY(-4px); box-shadow: 0 12px 28px -5px rgba(0,0,0,0.15); }
    
    /* Chart Container */
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }
    
    /* Progress Bar */
    .progress-bar {
        height: 8px;
        border-radius: 999px;
        background: linear-gradient(90deg, #0b4d9d, #1e88e5);
        transition: width 0.6s ease;
    }
    
    /* Badge Styles */
    .badge-up { background: linear-gradient(135deg, #10b981, #059669); }
    .badge-down { background: linear-gradient(135deg, #ef4444, #dc2626); }
    
    /* Timeline */
    .timeline-item {
        position: relative;
        padding-left: 2rem;
        border-left: 2px solid #e2e8f0;
    }
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -6px;
        top: 0.5rem;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #0b4d9d;
    }
    
    /* Heatmap Cell */
    .heatmap-cell {
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .heatmap-cell:hover {
        transform: scale(1.1);
        z-index: 10;
    }
</style>
</head>

<body class="bg-[#f8fafc] text-slate-800 font-sans min-h-screen">

<!-- NAVBAR -->
<?php 
$nav_active = 'home';
include 'includes/navbar.php'; 
?>

<!-- WELCOME HEADER -->
<div class="bg-gradient-to-r from-[#0b4d9d] to-[#1e88e5] text-white py-8 shadow-lg">
    <div class="container mx-auto px-6 max-w-7xl">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold mb-2">
                    <i class="fas fa-chart-line mr-3"></i>Analytics Dashboard
                </h1>
                <p class="text-blue-100 text-sm">Welcome back, <?php echo htmlspecialchars($adminName); ?> - Real-time insights and statistics</p>
            </div>
            <div class="flex gap-3">
                <a href="book_walkin.php" class="bg-white/10 backdrop-blur-sm border border-white/20 text-white px-5 py-2.5 rounded-lg hover:bg-white/20 transition font-medium shadow-sm flex items-center gap-2 text-sm">
                    <i class="fa-solid fa-user-pen"></i> Walk-in
                </a>
                <a href="bookinglist.php" class="bg-white text-[#0b4d9d] px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg transition font-medium flex items-center gap-2 text-sm">
                    <i class="fa-solid fa-list-check"></i> Manage Requests
                </a>
            </div>
        </div>
    </div>
</div>

<main class="container mx-auto px-6 py-8 max-w-7xl">

    <!-- ENHANCED STATS CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 fade-in">
        <!-- Card 1: This Month -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 card-hover">
            <div class="flex justify-between items-start mb-3">
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white text-xl shadow-lg">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
                <?php if ($monthlyChange > 0): ?>
                    <span class="badge-up text-white text-xs px-2 py-1 rounded-full font-bold">
                        <i class="fas fa-arrow-up"></i> <?php echo number_format(abs($monthlyChange), 1); ?>%
                    </span>
                <?php elseif ($monthlyChange < 0): ?>
                    <span class="badge-down text-white text-xs px-2 py-1 rounded-full font-bold">
                        <i class="fas fa-arrow-down"></i> <?php echo number_format(abs($monthlyChange), 1); ?>%
                    </span>
                <?php endif; ?>
            </div>
            <div class="text-gray-500 text-xs uppercase font-bold tracking-wider mb-1">This Month</div>
            <div class="text-4xl font-bold text-gray-900 font-serif"><?php echo $countThisMonth; ?></div>
            <div class="text-xs text-gray-400 mt-2">Total bookings this month</div>
        </div>

        <!-- Card 2: Pending -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 card-hover cursor-pointer" onclick="window.location.href='bookinglist.php?status=Pending'">
            <div class="flex justify-between items-start mb-3">
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-yellow-500 to-yellow-600 flex items-center justify-center text-white text-xl shadow-lg">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <?php if ($countPending > 0): ?>
                    <span class="bg-red-500 text-white text-xs w-6 h-6 rounded-full flex items-center justify-center font-bold animate-pulse">
                        <?php echo min($countPending, 9); ?><?php echo $countPending > 9 ? '+' : ''; ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="text-gray-500 text-xs uppercase font-bold tracking-wider mb-1">Pending Approvals</div>
            <div class="text-4xl font-bold text-yellow-600 font-serif"><?php echo $countPending; ?></div>
            <div class="text-xs text-gray-400 mt-2">Awaiting action</div>
        </div>

        <!-- Card 3: Today -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 card-hover">
            <div class="flex justify-between items-start mb-3">
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center text-white text-xl shadow-lg">
                    <i class="fa-regular fa-calendar-check"></i>
                </div>
            </div>
            <div class="text-gray-500 text-xs uppercase font-bold tracking-wider mb-1">Today's Bookings</div>
            <div class="text-4xl font-bold text-green-600 font-serif"><?php echo $countToday; ?></div>
            <div class="text-xs text-gray-400 mt-2"><?php echo $countUpcomingToday; ?> upcoming</div>
        </div>

        <!-- Card 4: Active Facilities -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 card-hover cursor-pointer" onclick="window.location.href='addfacilities.php'">
            <div class="flex justify-between items-start mb-3">
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center text-white text-xl shadow-lg">
                    <i class="fa-solid fa-building-circle-check"></i>
                </div>
            </div>
            <div class="text-gray-500 text-xs uppercase font-bold tracking-wider mb-1">Active Facilities</div>
            <div class="text-4xl font-bold text-purple-600 font-serif"><?php echo $countFacilities; ?></div>
            <div class="text-xs text-gray-400 mt-2">Available in system</div>
        </div>
    </div>

    <!-- CHARTS ROW 1: Booking Trends & Status Distribution -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        
        <!-- Booking Trends Chart -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6 slide-in">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="font-bold text-gray-800 text-lg flex items-center gap-2">
                        <i class="fas fa-chart-line text-[#0b4d9d]"></i>
                        Booking Trends
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">Last 30 days activity</p>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        <!-- Status Distribution Pie Chart -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 slide-in">
            <div class="mb-6">
                <h3 class="font-bold text-gray-800 text-lg flex items-center gap-2">
                    <i class="fas fa-chart-pie text-[#0b4d9d]"></i>
                    Status Distribution
                </h3>
                <p class="text-xs text-gray-500 mt-1">All bookings breakdown</p>
            </div>
            <div class="chart-container">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

    <!-- CHARTS ROW 2: Facility Utilization -->
    <div class="grid grid-cols-1 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 fade-in">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="font-bold text-gray-800 text-lg flex items-center gap-2">
                        <i class="fas fa-building text-[#0b4d9d]"></i>
                        Facility Utilization
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">Booking counts per facility</p>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="facilityChart"></canvas>
            </div>
        </div>
    </div>

    <!-- DATA WIDGETS ROW -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        
        <!-- Popular Facilities -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 slide-in">
            <div class="mb-5">
                <h3 class="font-bold text-gray-800 text-lg flex items-center gap-2">
                    <i class="fas fa-fire text-orange-500"></i>
                    Popular Facilities
                </h3>
                <p class="text-xs text-gray-500 mt-1">Top 5 most booked</p>
            </div>
            <div class="space-y-4">
                <?php if (!empty($popularFacilities)): ?>
                    <?php foreach ($popularFacilities as $idx => $fac): 
                        $percentage = $maxPopularCount > 0 ? ($fac['BookingCount'] / $maxPopularCount) * 100 : 0;
                        $colors = ['from-blue-500 to-blue-600', 'from-green-500 to-green-600', 'from-purple-500 to-purple-600', 'from-orange-500 to-orange-600', 'from-pink-500 to-pink-600'];
                        $color = $colors[$idx % 5];
                    ?>
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-gradient-to-br <?php echo $color; ?> text-white text-xs flex items-center justify-center font-bold"><?php echo $idx + 1; ?></span>
                                <span class="font-medium text-sm text-gray-800"><?php echo htmlspecialchars($fac['Name']); ?></span>
                            </div>
                            <span class="text-sm font-bold text-gray-600"><?php echo $fac['BookingCount']; ?></span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                            <div class="progress-bar bg-gradient-to-r <?php echo $color; ?> h-full" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        <div class="text-xs text-gray-400 mt-1"><?php echo htmlspecialchars($fac['Type']); ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center text-gray-400 italic py-4">No data available</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- User Statistics -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 slide-in">
            <div class="mb-5">
                <h3 class="font-bold text-gray-800 text-lg flex items-center gap-2">
                    <i class="fas fa-users text-[#0b4d9d]"></i>
                    User Statistics
                </h3>
                <p class="text-xs text-gray-500 mt-1">This month overview</p>
            </div>
            <div class="space-y-5">
                <div class="flex items-center justify-between p-4 bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg">
                    <div>
                        <div class="text-xs text-blue-600 font-bold uppercase mb-1">Total Users</div>
                        <div class="text-2xl font-bold text-blue-700 font-serif"><?php echo $countTotalUsers; ?></div>
                    </div>
                    <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white">
                        <i class="fas fa-user-group"></i>
                    </div>
                </div>
                
                <div class="flex items-center justify-between p-4 bg-gradient-to-r from-green-50 to-green-100 rounded-lg">
                    <div>
                        <div class="text-xs text-green-600 font-bold uppercase mb-1">Active This Month</div>
                        <div class="text-2xl font-bold text-green-700 font-serif"><?php echo $countActiveUsersMonth; ?></div>
                    </div>
                    <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center text-white">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
                
                <div class="flex items-center justify-between p-4 bg-gradient-to-r from-purple-50 to-purple-100 rounded-lg">
                    <div>
                        <div class="text-xs text-purple-600 font-bold uppercase mb-1">New Sign-ups</div>
                        <div class="text-2xl font-bold text-purple-700 font-serif"><?php echo $countNewUsersMonth; ?></div>
                    </div>
                    <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center text-white">
                        <i class="fas fa-user-plus"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Active Users -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 slide-in">
            <div class="mb-5">
                <h3 class="font-bold text-gray-800 text-lg flex items-center gap-2">
                    <i class="fas fa-trophy text-yellow-500"></i>
                    Top Active Users
                </h3>
                <p class="text-xs text-gray-500 mt-1">Most bookings this month</p>
            </div>
            <div class="space-y-3">
                <?php if (!empty($topUsers)): ?>
                    <?php foreach ($topUsers as $idx => $user): 
                        $medals = ['🥇', '🥈', '🥉', '4️⃣', '5️⃣'];
                    ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                        <div class="flex items-center gap-3">
                            <span class="text-xl"><?php echo $medals[$idx]; ?></span>
                            <div>
                                <div class="font-medium text-sm text-gray-800">
                                    <?php echo htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']); ?>
                                </div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($user['UserIdentifier']); ?></div>
                            </div>
                        </div>
                        <div class="bg-[#0b4d9d] text-white text-xs px-3 py-1 rounded-full font-bold">
                            <?php echo $user['BookingCount']; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center text-gray-400 italic py-4">No data available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- PEAK HOURS HEATMAP -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8 fade-in">
        <div class="mb-6">
            <h3 class="font-bold text-gray-800 text-lg flex items-center gap-2">
                <i class="fas fa-clock text-[#0b4d9d]"></i>
                Peak Hours Heatmap
            </h3>
            <p class="text-xs text-gray-500 mt-1">Most popular booking times (Last 30 days)</p>
        </div>
        
        <?php if (!empty($peakHoursData)): ?>
            <div class="overflow-x-auto">
                <div class="inline-block min-w-full">
                    <div class="flex gap-1 mb-2 text-xs font-medium text-gray-600">
                        <div class="w-16"></div>
                        <?php for ($h = 8; $h <= 22; $h++): ?>
                            <div class="w-10 text-center"><?php echo $h; ?>h</div>
                        <?php endfor; ?>
                    </div>
                    
                    <?php 
                    $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                    $maxCount = max(array_column($peakHoursData, 'count'));
                    
                    for ($d = 1; $d <= 7; $d++): ?>
                        <div class="flex gap-1 mb-1">
                            <div class="w-16 text-xs font-medium text-gray-600 flex items-center"><?php echo $days[$d - 1]; ?></div>
                            <?php for ($h = 8; $h <= 22; $h++): 
                                $count = 0;
                                foreach ($peakHoursData as $data) {
                                    if ($data['day'] == $d && $data['hour'] == $h) {
                                        $count = $data['count'];
                                        break;
                                    }
                                }
                                $intensity = $maxCount > 0 ? ($count / $maxCount) : 0;
                                $opacity = 0.1 + ($intensity * 0.9);
                                $bgColor = $count > 0 ? "background-color: rgba(11, 77, 157, $opacity);" : "background-color: #f1f5f9;";
                            ?>
                                <div class="w-10 h-10 rounded heatmap-cell flex items-center justify-center text-xs font-medium" 
                                     style="<?php echo $bgColor; ?>"
                                     title="<?php echo $days[$d - 1] . ' ' . $h . ':00 - ' . $count . ' bookings'; ?>">
                                    <?php echo $count > 0 ? $count : ''; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="flex items-center justify-center gap-4 mt-4 text-xs text-gray-500">
                <span>Less</span>
                <div class="flex gap-1">
                    <div class="w-6 h-6 rounded" style="background-color: rgba(11, 77, 157, 0.1);"></div>
                    <div class="w-6 h-6 rounded" style="background-color: rgba(11, 77, 157, 0.3);"></div>
                    <div class="w-6 h-6 rounded" style="background-color: rgba(11, 77, 157, 0.5);"></div>
                    <div class="w-6 h-6 rounded" style="background-color: rgba(11, 77, 157, 0.7);"></div>
                    <div class="w-6 h-6 rounded" style="background-color: rgba(11, 77, 157, 1);"></div>
                </div>
                <span>More</span>
            </div>
        <?php else: ?>
            <p class="text-center text-gray-400 italic py-8">No peak hours data available</p>
        <?php endif; ?>
    </div>

    <!-- RECENT ACTIVITY TIMELINE -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden fade-in">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gradient-to-r from-gray-50 to-white">
            <div>
                <h3 class="font-bold text-gray-800 text-lg flex items-center gap-2">
                    <i class="fas fa-history text-[#0b4d9d]"></i>
                    Recent Activity
                </h3>
                <p class="text-xs text-gray-500 mt-1">Latest booking actions</p>
            </div>
            <a href="bookinglist.php" class="text-xs font-bold text-[#0b4d9d] hover:underline uppercase tracking-wide">View All</a>
        </div>
        <div class="p-6">
            <?php if ($resRecent && $resRecent->num_rows > 0): ?>
                <div class="space-y-4">
                    <?php while ($row = $resRecent->fetch_assoc()): 
                        $statusClass = match($row['Status']) {
                            'Pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                            'Confirmed' => 'bg-green-100 text-green-800 border-green-200',
                            'Canceled' => 'bg-red-100 text-red-800 border-red-200',
                            'Completed' => 'bg-blue-100 text-blue-800 border-blue-200',
                            default => 'bg-gray-100 text-gray-800 border-gray-200'
                        };
                        $statusIcon = match($row['Status']) {
                            'Pending' => 'fa-clock',
                            'Confirmed' => 'fa-check-circle',
                            'Canceled' => 'fa-times-circle',
                            'Completed' => 'fa-flag-checkered',
                            default => 'fa-circle'
                        };
                    ?>
                    <div class="timeline-item pb-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-gray-50 p-4 rounded-lg hover:bg-gray-100 transition">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold border <?php echo $statusClass; ?>">
                                        <i class="fas <?php echo $statusIcon; ?> mr-1"></i><?php echo $row['Status']; ?>
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        <i class="far fa-clock mr-1"></i><?php echo date('M d, h:i A', strtotime($row['BookedAt'])); ?>
                                    </span>
                                </div>
                                <div class="font-medium text-gray-900">
                                    <i class="fas fa-user mr-2 text-gray-400"></i>
                                    <?php echo htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']); ?>
                                    <span class="text-sm text-gray-500">(<?php echo htmlspecialchars($row['UserIdentifier']); ?>)</span>
                                </div>
                                <div class="text-sm text-gray-600 mt-1">
                                    <i class="fas fa-building mr-2 text-gray-400"></i>
                                    <?php echo htmlspecialchars($row['FacilityName']); ?>
                                    <span class="mx-2 text-gray-400">•</span>
                                    <i class="far fa-calendar mr-1 text-gray-400"></i>
                                    <?php echo date('M d, Y h:i A', strtotime($row['StartTime'])); ?>
                                </div>
                            </div>
                            <div>
                                <a href="bookinglist.php?id=<?php echo $row['BookingID']; ?>" class="text-[#0b4d9d] hover:text-[#063a75] text-sm font-medium">
                                    View Details <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="text-center text-gray-400 italic py-8">No recent activity found.</p>
            <?php endif; ?>
        </div>
    </div>

</main>

<!-- FOOTER -->
<?php include 'includes/footer.php'; ?>

<!-- CHART.JS INITIALIZATION -->
<script>
// Color palette
const colors = {
    primary: '#0b4d9d',
    success: '#10b981',
    warning: '#f59e0b',
    danger: '#ef4444',
    info: '#3b82f6',
    purple: '#8b5cf6'
};

// 1. BOOKING TRENDS CHART
const trendCtx = document.getElementById('trendChart').getContext('2d');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($trendDates); ?>,
        datasets: [
            {
                label: 'Total',
                data: <?php echo json_encode($trendTotal); ?>,
                borderColor: colors.primary,
                backgroundColor: colors.primary + '20',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            },
            {
                label: 'Confirmed',
                data: <?php echo json_encode($trendConfirmed); ?>,
                borderColor: colors.success,
                backgroundColor: colors.success + '20',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            },
            {
                label: 'Pending',
                data: <?php echo json_encode($trendPending); ?>,
                borderColor: colors.warning,
                backgroundColor: colors.warning + '20',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            },
            {
                label: 'Canceled',
                data: <?php echo json_encode($trendCanceled); ?>,
                borderColor: colors.danger,
                backgroundColor: colors.danger + '20',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    usePointStyle: true,
                    padding: 15,
                    font: { size: 11, weight: '600' }
                }
            },
            tooltip: {
                mode: 'index',
                intersect: false,
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                cornerRadius: 8
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { font: { size: 11 } },
                grid: { color: 'rgba(0, 0, 0, 0.05)' }
            },
            x: {
                ticks: { font: { size: 10 } },
                grid: { display: false }
            }
        }
    }
});

// 2. STATUS DISTRIBUTION PIE CHART
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($statusLabels); ?>,
        datasets: [{
            data: <?php echo json_encode($statusCounts); ?>,
            backgroundColor: [colors.warning, colors.success, colors.danger, colors.info, colors.purple],
            borderWidth: 3,
            borderColor: '#fff',
            hoverOffset: 10
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: { size: 11, weight: '600' },
                    usePointStyle: true
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                cornerRadius: 8,
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((context.parsed / total) * 100).toFixed(1);
                        return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// 3. FACILITY UTILIZATION BAR CHART
const facilityCtx = document.getElementById('facilityChart').getContext('2d');
new Chart(facilityCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($facilityNames); ?>,
        datasets: [{
            label: 'Bookings',
            data: <?php echo json_encode($facilityBookings); ?>,
            backgroundColor: [
                colors.primary + 'cc',
                colors.success + 'cc',
                colors.info + 'cc',
                colors.purple + 'cc',
                colors.warning + 'cc',
                colors.danger + 'cc',
                colors.primary + '88',
                colors.success + '88',
                colors.info + '88',
                colors.purple + '88'
            ],
            borderRadius: 8,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                cornerRadius: 8
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { font: { size: 11 } },
                grid: { color: 'rgba(0, 0, 0, 0.05)' }
            },
            x: {
                ticks: { 
                    font: { size: 11 },
                    maxRotation: 45,
                    minRotation: 45
                },
                grid: { display: false }
            }
        }
    }
});
</script>

</body>
</html>