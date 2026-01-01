<?php
require_once 'includes/student_auth.php';

}

// Fetch Bookings
$userID = 0;
if ($studentIdentifier) {
    $stmtUser = $conn->prepare("SELECT UserID FROM users WHERE UserIdentifier=?");
    $stmtUser->bind_param("s", $studentIdentifier);
    $stmtUser->execute();
    $resUser = $stmtUser->get_result();
    if ($rowUser = $resUser->fetch_assoc()) $userID = $rowUser['UserID'];
    $stmtUser->close();
}

$bookings = [];
if ($userID > 0) {
    // UPDATED QUERY: Added BookingID and Location
    $stmt = $conn->prepare("
        SELECT f.Name AS FacilityName, b.BookingID, b.StartTime, b.EndTime, b.Status AS BookingStatus, f.Location
        FROM bookings b
        JOIN facilities f ON b.FacilityID = f.FacilityID
        WHERE b.UserID = ?
        ORDER BY b.StartTime DESC
    ");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()) $bookings[] = $row;
    $stmt->close();
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking History - UKM Sports Center</title>
    
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
        
        .fade-in { animation: fadeIn 0.5s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>

<body>

<nav class="bg-white/95 backdrop-blur-sm border-b border-slate-200 sticky top-0 z-40 transition-all duration-300 shadow-sm">
    <div class="container mx-auto px-6 py-3 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <img src="../assets/img/ukm.png" alt="UKM Logo" class="h-10 md:h-12 w-auto">
            <div class="h-8 w-px bg-slate-200 hidden sm:block"></div>
            <img src="../assets/img/pusatsukanlogo.png" alt="Pusat Sukan Logo" class="h-10 md:h-12 w-auto hidden sm:block">
        </div>
        
        <div class="flex items-center gap-8">
            <div class="hidden md:flex items-center gap-6">
                <a href="dashboard.php" class="text-slate-600 hover:text-[#8a0d19] font-medium transition text-decoration-none">Home</a>
                <a href="student_facilities.php" class="text-slate-600 hover:text-[#8a0d19] font-medium transition text-decoration-none">Facilities</a>
                <a href="booking_history.php" class="text-[#8a0d19] font-bold transition flex items-center gap-2 relative text-decoration-none">
                    <span>History</span>
                    <span class="absolute -bottom-1.5 left-0 w-full h-0.5 bg-[#8a0d19] rounded-full"></span>
                </a>
            </div>

            <div class="flex items-center gap-4 pl-4 md:pl-6 md:border-l border-slate-200 relative">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-slate-800 leading-tight"><?php echo htmlspecialchars($studentName); ?></p>
                    <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold"><?php echo htmlspecialchars($studentID); ?></p>
                </div>
                <div class="relative group">
                    <button class="focus:outline-none rounded-full transition-transform active:scale-95">
                        <img src="../assets/img/user.png" alt="Profile" class="w-10 h-10 rounded-full border-2 border-white ring-2 ring-slate-100 object-cover shadow-sm group-hover:ring-[#8a0d19]/20">
                    </button>
                    <div class="absolute right-0 top-full pt-2 w-56 hidden group-hover:block z-50">
                        <div class="bg-white rounded-xl shadow-xl border border-slate-100 overflow-hidden">
                             <div class="px-4 py-3 bg-slate-50 border-b border-slate-100 md:hidden">
                                 <p class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($studentName); ?></p>
                                 <p class="text-xs text-slate-500"><?php echo htmlspecialchars($studentID); ?></p>
                            </div>
                            <a href="dashboard.php" class="block md:hidden px-4 py-3 text-sm text-slate-600 hover:bg-slate-50 hover:text-[#8a0d19]">Home</a>
                            <a href="student_facilities.php" class="block md:hidden px-4 py-3 text-sm text-slate-600 hover:bg-slate-50 hover:text-[#8a0d19]">Facilities</a>
                            <div class="h-px bg-slate-100 md:hidden"></div>
                            <a href="../logout.php" onclick="return confirm('Logout?')" class="block px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition font-medium">
                                <i class="fa-solid fa-right-from-bracket mr-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<main class="container mx-auto px-4 md:px-6 py-8 md:py-12 flex-grow max-w-6xl fade-in">

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="dashboard.php" class="hover:text-[#8a0d19] transition">Dashboard</a>
                <i class="fa-solid fa-chevron-right text-[10px]"></i>
                <span class="text-[#8a0d19]">History</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-slate-900 tracking-tight font-serif">
                Booking History
            </h1>
            <p class="text-slate-500 mt-2 text-lg">Track the status of your facility reservations.</p>
        </div>
        <a href="student_facilities.php" class="bg-[#8a0d19] hover:bg-[#6d0a13] text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-red-900/10 hover:shadow-xl transition transform active:scale-95 flex items-center gap-2 text-decoration-none">
            <i class="fa-solid fa-plus"></i> New Booking
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        
        <?php if (empty($bookings)): ?>
            <div class="px-6 py-16 text-center flex flex-col items-center justify-center">
                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-6 text-slate-300">
                    <i class="fa-regular fa-calendar-xmark text-4xl"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-2 font-serif">No Booking History</h3>
                <p class="text-slate-500 mb-8 max-w-sm mx-auto">You haven't made any facility reservations yet. Start by booking a sport facility!</p>
                <a href="student_facilities.php" class="text-[#8a0d19] font-bold hover:underline flex items-center gap-2 transition group text-decoration-none">
                    View Available Facilities <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500 font-bold tracking-wider">
                        <tr>
                            <th class="px-6 py-4 border-b border-slate-100">Facility</th>
                            <th class="px-6 py-4 border-b border-slate-100">Date & Time</th>
                            <th class="px-6 py-4 border-b border-slate-100">Duration</th>
                            <th class="px-6 py-4 border-b border-slate-100 text-center">Status</th>
                            <th class="px-6 py-4 border-b border-slate-100 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($bookings as $row): 
                            // Status Logic
                            $status = $row['BookingStatus'];
                            $badgeClass = match($status) {
                                'Approved', 'Confirmed' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                'Pending' => 'bg-amber-50 text-amber-700 border-amber-100',
                                'Rejected', 'Cancelled' => 'bg-red-50 text-red-700 border-red-100',
                                default => 'bg-slate-100 text-slate-600 border-slate-200'
                            };
                            $dotClass = match($status) {
                                'Approved', 'Confirmed' => 'bg-emerald-500',
                                'Pending' => 'bg-amber-500',
                                'Rejected', 'Cancelled' => 'bg-red-500',
                                default => 'bg-slate-400'
                            };

                            // Time Formatting
                            $startObj = new DateTime($row['StartTime']);
                            $endObj = new DateTime($row['EndTime']);
                            $duration = $startObj->diff($endObj);
                            $hours = $duration->h;
                            $minutes = $duration->i;
                            $durationStr = $hours . 'h ' . ($minutes > 0 ? $minutes . 'm' : '');
                            
                            // Can Cancel?
                            $isFuture = $startObj > new DateTime();
                            $isActive = in_array($status, ['Pending', 'Approved', 'Confirmed']);
                        ?>
                        <tr class="hover:bg-slate-50/50 transition duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-bold text-slate-800 text-base"><?php echo htmlspecialchars($row['FacilityName']); ?></div>
                                <div class="text-xs text-slate-400">ID: #<?php echo str_pad($userID, 5, '0', STR_PAD_LEFT); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="font-semibold text-slate-700"><?php echo $startObj->format('d M Y'); ?></span>
                                    <span class="text-xs text-slate-500"><?php echo $startObj->format('h:i A'); ?> - <?php echo $endObj->format('h:i A'); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="bg-gray-100 text-gray-600 px-2.5 py-1 rounded text-xs font-semibold">
                                    <i class="fa-regular fa-clock mr-1"></i><?php echo $durationStr; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border <?php echo $badgeClass; ?>">
                                    <span class="w-2 h-2 rounded-full <?php echo $dotClass; ?>"></span>
                                    <?php echo $status; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <?php if ($isFuture && $isActive): ?>
                                    <button onclick="cancelBooking(<?php echo htmlspecialchars($row['BookingID']); ?>)" 
                                       class="text-red-600 hover:text-red-800 font-semibold text-sm hover:underline transition">
                                       Cancel
                                    </button>
                                <?php elseif (!$isFuture && in_array($status, ['Approved', 'Confirmed'])): ?>
                                    <a href="feedback.php?booking_id=<?php echo $row['BookingID']; ?>&facility_name=<?php echo urlencode($row['FacilityName']); ?>&date=<?php echo urlencode($startObj->format('d M Y')); ?>"
                                       class="inline-block px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-600 text-xs font-bold hover:bg-indigo-100 transition text-decoration-none">
                                       Feedback
                                    </a>
                                <?php else: ?>
                                    <span class="text-slate-300 text-xs italic">No actions</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-between items-center text-xs text-slate-500">
                <span>Showing <?php echo count($bookings); ?> records</span>
                <span>Sorted by latest</span>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
async function cancelBooking(id) {
    if(!confirm('Are you sure you want to cancel this booking?')) return;
    
    try {
        const formData = new FormData();
        formData.append('booking_id', id);
        
        const res = await fetch('cancel_booking.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await res.json();
        
        if(data.success) {
            alert('Booking cancelled successfully.');
            location.reload();
        } else {
            alert(data.message || 'Failed to cancel booking.');
        }
    } catch (e) {
        console.error(e);
        alert('An error occurred. Please try again.');
    }
}
</script>
<script src="../assets/js/idle_timer.js"></script>
</body>
</html>