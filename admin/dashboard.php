<?php
require_once 'includes/admin_auth.php'; // Standardized Auth & User Fetch

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
<!-- Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }
    h1, h2, h3, .font-serif {
        font-family: 'Playfair Display', serif;
    }

    /* Animations */
    .fade-in { animation: fadeIn 0.5s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    /* Card Hover */
    .card-hover { transition: all 0.3s ease; }
    .card-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); }
</style>
</head>

<body class="bg-[#f8fafc] flex flex-col min-h-screen text-slate-800 font-sans">


<!-- NAVBAR -->
<?php 
$nav_active = 'home';
include 'includes/navbar.php'; 
?>

<!-- WELCOME HEADER (No Banner Image) -->
<div class="bg-white border-b border-gray-200 py-10 shadow-sm">
    <div class="container mx-auto px-6 max-w-6xl flex flex-col md:flex-row justify-between items-center gap-6">
        <div>
            <h1 class="text-3xl md:text-4xl font-bold text-[#0b4d9d] mb-2">
                Welcome, <?php echo htmlspecialchars($adminName); ?>
            </h1>
            <p class="text-gray-500">Overview of facility operations and booking status.</p>
        </div>
        <div class="flex gap-3">
             <a href="book_walkin.php" class="bg-white border border-gray-300 text-gray-700 px-6 py-2.5 rounded-lg hover:bg-gray-50 transition font-medium shadow-sm flex items-center gap-2 text-sm">
                <i class="fa-solid fa-user-pen"></i> Walk-in
            </a>
            <a href="bookinglist.php" class="bg-[#0b4d9d] text-white px-6 py-2.5 rounded-lg shadow-md hover:bg-[#083a75] transition font-medium flex items-center gap-2 text-sm">
                <i class="fa-solid fa-list-check"></i> Manage Requests
            </a>
        </div>
    </div>
</div>

<main class="container mx-auto px-6 py-10 flex-grow max-w-6xl fade-in">

    <!-- STATS GRID -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <!-- Stat 1: Pending -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition cursor-pointer" onclick="window.location.href='bookinglist.php?status=Pending'">
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

    <!-- RECENT ACTIVITY TABLE -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="font-bold text-gray-800 text-lg">Recent Bookings</h3>
            <a href="bookinglist.php" class="text-xs font-bold text-[#0b4d9d] hover:underline uppercase tracking-wide">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-white text-gray-500 border-b border-gray-100">
                    <tr>
                        <th class="p-5 font-semibold">Student ID</th>
                        <th class="p-5 font-semibold">Facility</th>
                        <th class="p-5 font-semibold">Date</th>
                        <th class="p-5 font-semibold text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-gray-700">
                    <?php if ($resRecent && $resRecent->num_rows > 0): ?>
                        <?php while ($row = $resRecent->fetch_assoc()): 
                            $statusClass = match($row['Status']) {
                                'Pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                'Approved', 'Confirmed' => 'bg-green-100 text-green-800 border-green-200',
                                'Cancelled', 'Rejected' => 'bg-red-100 text-red-800 border-red-200',
                                default => 'bg-gray-100 text-gray-800 border-gray-200'
                            };
                        ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="p-5 font-medium text-gray-900"><?php echo htmlspecialchars($row['UserIdentifier']); ?></td>
                            <td class="p-5 text-gray-600"><?php echo htmlspecialchars($row['FacilityName']); ?></td>
                            <td class="p-5 text-gray-500"><?php echo date('d M, h:i A', strtotime($row['StartTime'])); ?></td>
                            <td class="p-5 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-bold border <?php echo $statusClass; ?>">
                                    <?php echo $row['Status']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="p-8 text-center text-gray-400 italic">No recent booking activity found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<!-- FOOTER -->
<?php include 'includes/footer.php'; ?>

</body>
</html>