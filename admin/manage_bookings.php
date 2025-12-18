<?php
session_start();
require_once '../includes/db_connect.php';

// 1. Auth Check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

$page_title = "Booking Management";

// Fetch Admin Details
$adminName = 'Admin';
$adminIdentifier = $_SESSION['user_id'] ?? '';

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

// Fetch Facilities for Dropdown (New Booking Modal)
$facilitiesResult = $conn->query("SELECT FacilityID, Name FROM facilities WHERE Status IN ('Active', 'Maintenance') ORDER BY Name ASC");
$facilitiesList = [];
while($f = $facilitiesResult->fetch_assoc()) {
    $facilitiesList[] = $f;
}

// Initialize filter variables
$statusFilter = $_GET['status'] ?? 'all';
$dateFilter = $_GET['date'] ?? '';
$searchQuery = $_GET['search'] ?? '';

// Base SQL
$sql = "SELECT b.*, f.Name as FacilityName, u.FirstName, u.LastName, u.UserIdentifier 
        FROM bookings b 
        LEFT JOIN facilities f ON b.FacilityID = f.FacilityID 
        LEFT JOIN users u ON b.UserID = u.UserID 
        WHERE 1=1";

// Apply Filters
$params = [];
$types = "";

if ($statusFilter !== 'all') {
    $sql .= " AND b.Status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

if (!empty($dateFilter)) {
    $sql .= " AND DATE(b.StartTime) = ?";
    $params[] = $dateFilter;
    $types .= "s";
}

if (!empty($searchQuery)) {
    $searchTerm = "%" . $searchQuery . "%";
    $sql .= " AND (b.BookingID LIKE ? OR u.UserIdentifier LIKE ? OR f.Name LIKE ?)";
    $params[] = $searchTerm; 
    $params[] = $searchTerm; 
    $params[] = $searchTerm;
    $types .= "sss";
}

$sql .= " ORDER BY b.BookedAt DESC";

// Execute Query
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

$bookings = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}

function getStatusClass($status) {
    switch ($status) {
        case 'Pending': return 'bg-yellow-100 text-yellow-800 border-yellow-200'; 
        case 'Confirmed': return 'bg-green-100 text-green-800 border-green-200';
        case 'Approved': return 'bg-green-100 text-green-800 border-green-200';
        case 'Canceled': return 'bg-red-100 text-red-800 border-red-200';
        case 'Cancelled': return 'bg-red-100 text-red-800 border-red-200';
        case 'Rejected': return 'bg-red-100 text-red-800 border-red-200';
        case 'Complete': return 'bg-gray-100 text-gray-800 border-gray-200';
        default: return 'bg-blue-100 text-blue-800 border-blue-200';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking Management - UKM Sports Center</title>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<!-- Bootstrap CSS for Modals -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
:root {
    --primary: #0b4d9d; /* UKM Blue */
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
h1, h2, h3 { font-family: 'Playfair Display', serif; }

/* Filter Bar Style */
.filter-bar {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 20px -1px rgba(0,0,0,0.08);
    border: 1px solid #eee;
}

.fade-in { animation: fadeIn 0.4s ease-in-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

/* Inputs matching addfacilities */
input:focus, textarea:focus, select:focus {
    outline: none;
    border-color: #0b4d9d;
    box-shadow: 0 0 0 1px #0b4d9d;
}
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
            <a href="dashboard.php" class="text-gray-600 hover:text-[#0b4d9d] font-medium transition">
                Home
            </a>
            
            <a href="addfacilities.php" class="text-gray-600 hover:text-[#0b4d9d] font-medium transition">
                Facilities
            </a>
            
            <!-- Active State -->
            <a href="manage_bookings.php" class="text-[#0b4d9d] font-bold transition">
                Bookings
            </a>

            <div class="flex items-center gap-3 pl-6 border-l border-gray-200">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($adminName); ?></p>
                    <p class="text-xs text-gray-500 uppercase tracking-wider"><?php echo htmlspecialchars($adminIdentifier); ?></p>
                </div>
                <div class="relative group">
                    <img src="../assets/img/user.png" alt="Profile" class="w-10 h-10 rounded-full border-2 border-white shadow-md object-cover cursor-pointer hover:scale-105 transition">
                    <!-- Dropdown -->
                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-100 hidden group-hover:block z-50">
                        <div class="bg-white rounded-lg shadow-xl border border-gray-100 overflow-hidden">
                            <a href="../logout.php" onclick="return confirm('Logout?');" class="block px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition">
                                <i class="fa-solid fa-right-from-bracket mr-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- MAIN CONTENT -->
<main class="container mx-auto px-6 py-10 flex-grow max-w-6xl">

    <!-- Header & Actions -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <span class="text-sm font-bold text-gray-400 uppercase tracking-wider">Facility Management</span>
            <h1 class="text-3xl font-bold text-[#0b4d9d] mb-1 font-serif">Booking Management</h1>
            <p class="text-gray-500">View, filter, and manage student facility bookings.</p>
        </div>
        
        <button class="bg-[#0b4d9d] text-white px-8 py-3 rounded-lg font-bold hover:bg-[#083a75] transition shadow-lg shadow-blue-900/20 whitespace-nowrap flex items-center gap-2" data-bs-toggle="modal" data-bs-target="#newBookingModal">
            <i class="fa-solid fa-plus-circle"></i> Walk-in Booking
        </button>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Search</label>
                <input type="text" name="search" class="w-full p-2.5 border border-gray-300 rounded-lg text-sm" placeholder="ID or Name..." value="<?php echo htmlspecialchars($searchQuery); ?>">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Status</label>
                <select name="status" class="w-full p-2.5 border border-gray-300 rounded-lg text-sm bg-white">
                    <option value="all">All Statuses</option>
                    <option value="Pending" <?php if($statusFilter=='Pending') echo 'selected'; ?>>Pending</option>
                    <option value="Approved" <?php if($statusFilter=='Approved') echo 'selected'; ?>>Approved</option>
                    <option value="Confirmed" <?php if($statusFilter=='Confirmed') echo 'selected'; ?>>Confirmed</option>
                    <option value="Canceled" <?php if($statusFilter=='Canceled') echo 'selected'; ?>>Canceled</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Date</label>
                <input type="date" name="date" class="w-full p-2.5 border border-gray-300 rounded-lg text-sm" value="<?php echo htmlspecialchars($dateFilter); ?>">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-gray-800 text-white px-4 py-2.5 rounded-lg hover:bg-gray-900 transition text-sm font-bold flex-grow uppercase">Apply</button>
                <a href="manage_bookings.php" class="bg-white border border-gray-300 text-gray-600 px-4 py-2.5 rounded-lg hover:bg-gray-50 transition text-sm font-bold text-center flex-grow uppercase">Reset</a>
            </div>
        </form>
    </div>

    <!-- Bookings Table -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden min-h-[500px] fade-in">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 text-gray-500 font-semibold uppercase text-xs border-b border-gray-200">
                    <tr>
                        <th class="p-4 px-6">ID</th>
                        <th class="p-4 px-6">Facility</th>
                        <th class="p-4 px-6">User</th>
                        <th class="p-4 px-6">Schedule</th>
                        <th class="p-4 px-6 text-center">Status</th>
                        <th class="p-4 px-6 text-end">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm bg-white">
                    <?php if (count($bookings) === 0): ?>
                        <tr><td colspan="6" class="p-12 text-center text-gray-400 italic">No bookings found matching your criteria.</td></tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $bk): 
                            $startObj = new DateTime($bk['StartTime']);
                            $endObj = new DateTime($bk['EndTime']);
                            $fullName = trim(($bk['FirstName'] ?? '') . ' ' . ($bk['LastName'] ?? ''));
                            if(empty($fullName)) $fullName = $bk['UserIdentifier'];
                        ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="p-4 px-6 font-bold text-gray-700">#<?php echo $bk['BookingID']; ?></td>
                            <td class="p-4 px-6 text-[#0b4d9d] font-bold"><?php echo htmlspecialchars($bk['FacilityName'] ?? 'Unknown'); ?></td>
                            <td class="p-4 px-6">
                                <div class="font-bold text-gray-800"><?php echo htmlspecialchars($fullName); ?></div>
                                <div class="text-xs text-gray-500 font-mono"><?php echo htmlspecialchars($bk['UserIdentifier'] ?? ''); ?></div>
                            </td>
                            <td class="p-4 px-6">
                                <div class="font-bold text-gray-700"><?php echo $startObj->format('d M Y'); ?></div>
                                <div class="text-xs text-gray-500"><?php echo $startObj->format('h:i A') . ' - ' . $endObj->format('h:i A'); ?></div>
                            </td>
                            <td class="p-4 px-6 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-bold border <?php echo getStatusClass($bk['Status']); ?>">
                                    <?php echo $bk['Status']; ?>
                                </span>
                            </td>
                            <td class="p-4 px-6 text-end">
                                <button class="text-[#0b4d9d] hover:text-[#083a75] font-semibold text-xs border border-[#0b4d9d] hover:bg-blue-50 px-4 py-2 rounded-lg transition"
                                        data-bs-toggle="modal" data-bs-target="#actionModal"
                                        data-id="<?php echo $bk['BookingID']; ?>"
                                        data-facility="<?php echo htmlspecialchars($bk['FacilityName'] ?? 'Unknown'); ?>"
                                        data-user="<?php echo htmlspecialchars($fullName); ?>"
                                        data-start="<?php echo $bk['StartTime']; ?>"
                                        data-end="<?php echo $bk['EndTime']; ?>"
                                        data-status="<?php echo $bk['Status']; ?>">
                                    Manage
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- EXTENDED FOOTER -->
<footer class="bg-white border-t border-gray-200 mt-auto">
    <div class="container mx-auto px-6 py-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10 mb-10">
            <!-- About -->
            <div>
                <img src="../assets/img/pusatsukanlogo.png" class="h-14 mb-4">
                <p class="text-sm text-gray-600 leading-relaxed">
                    Pusat Sukan Universiti Kebangsaan Malaysia manages all university
                    sports facilities, bookings, and athletic development programs.
                </p>
            </div>
            <!-- Links -->
            <div>
                <h4 class="text-sm font-bold uppercase mb-4">Quick Access</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="dashboard.php" class="hover:text-[#0b4d9d]">Dashboard</a></li>
                    <li><a href="addfacilities.php" class="hover:text-[#0b4d9d]">Facilities</a></li>
                    <li><a href="manage_bookings.php" class="hover:text-[#0b4d9d]">Booking Management</a></li>
                </ul>
            </div>
            <!-- Contact -->
            <div>
                <h4 class="text-sm font-bold uppercase mb-4">Contact</h4>
                <p class="text-sm text-gray-600">
                    Stadium Universiti, UKM<br>
                    43600 Bangi, Selangor<br>
                    <span class="text-[#0b4d9d] font-semibold">
                        <i class="fa-solid fa-phone mr-1"></i> 03-8921 5306
                    </span>
                </p>
            </div>
        </div>
        <div class="border-t pt-6 flex justify-between items-center">
            <img src="../assets/img/sdg.png" class="h-14 opacity-90">
            <p class="text-xs text-gray-400 text-right">
                © 2025 Universiti Kebangsaan Malaysia<br>All rights reserved
            </p>
        </div>
    </div>
</footer>

<!-- ACTION MODAL -->
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-2xl rounded-xl overflow-hidden">
            <div class="modal-header bg-gray-50 border-bottom">
                <h5 class="modal-title fw-bold text-[#0b4d9d]">Manage Booking #<span id="displayId"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-6">
                <form id="actionForm" action="process.php" method="POST">
                    <input type="hidden" name="booking_id" id="modalBookingId">
                    <input type="hidden" name="action" id="modalAction">
                    
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 mb-6 space-y-2">
                        <div class="flex justify-between text-sm"><span class="font-bold text-blue-800 uppercase text-[10px]">Facility</span><span class="font-semibold" id="modalFacility"></span></div>
                        <div class="flex justify-between text-sm"><span class="font-bold text-blue-800 uppercase text-[10px]">User</span><span class="font-semibold" id="modalUser"></span></div>
                        <div class="flex justify-between text-sm"><span class="font-bold text-blue-800 uppercase text-[10px]">Schedule</span><span class="font-semibold text-end" id="modalTime"></span></div>
                    </div>
                    
                    <div id="actionButtons" class="grid gap-3">
                        <button type="submit" onclick="setAction('approve')" class="bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-bold transition shadow-sm flex items-center justify-center gap-2">
                            <i class="fa-solid fa-check"></i> Approve Booking
                        </button>
                        <button type="button" onclick="rejectBooking()" class="bg-red-600 hover:bg-red-700 text-white py-3 rounded-lg font-bold transition shadow-sm flex items-center justify-center gap-2">
                            <i class="fa-solid fa-times"></i> Reject Booking
                        </button>
                    </div>

                    <div id="rejectSection" class="mt-4 d-none">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Reason for Rejection</label>
                        <textarea name="admin_notes" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white" rows="2" placeholder="Explain why the booking is being rejected..."></textarea>
                        <button type="submit" onclick="setAction('reject')" class="w-full mt-3 bg-gray-800 text-white py-2 rounded-lg font-bold text-sm">Confirm Rejection</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- WALK-IN BOOKING MODAL -->
<div class="modal fade" id="newBookingModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-2xl rounded-xl overflow-hidden" style="height: 85vh;">
            <div class="modal-header bg-[#0b4d9d] text-white">
                <h5 class="modal-title fw-bold">New Walk-in Booking</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 d-flex flex-column bg-light">
                <div class="p-4 bg-white border-bottom shadow-sm flex justify-center gap-3 align-items-center">
                    <label class="fw-bold text-secondary">Select Facility:</label>
                    <select id="newBookingFacility" class="form-select w-auto" style="min-width: 250px;">
                        <option value="">-- Choose Facility --</option>
                        <?php foreach($facilitiesList as $fac): ?>
                            <option value="<?php echo $fac['FacilityID']; ?>"><?php echo htmlspecialchars($fac['Name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="bg-[#0b4d9d] text-white px-6 py-2 rounded-lg font-bold" onclick="loadBookingFrame()">Next</button>
                </div>
                <div class="flex-grow-1 relative w-100">
                    <iframe id="bookingFrame" src="" class="w-100 h-100 border-0 d-none"></iframe>
                    <div id="framePlaceholder" class="flex items-center justify-center h-100 text-gray-400 italic">Please select a facility above to start.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const actionModal = document.getElementById('actionModal');
actionModal.addEventListener('show.bs.modal', event => {
    const btn = event.relatedTarget;
    const id = btn.getAttribute('data-id');
    const status = btn.getAttribute('data-status');
    
    document.getElementById('modalBookingId').value = id;
    document.getElementById('displayId').textContent = id;
    document.getElementById('modalFacility').textContent = btn.getAttribute('data-facility');
    document.getElementById('modalUser').textContent = btn.getAttribute('data-user');
    
    const start = new Date(btn.getAttribute('data-start'));
    const end = new Date(btn.getAttribute('data-end'));
    const timeStr = start.toLocaleDateString() + ' (' + 
                    start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) + ' - ' + 
                    end.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) + ')';
    document.getElementById('modalTime').textContent = timeStr;
    
    document.getElementById('rejectSection').classList.add('d-none');
    
    const btns = document.getElementById('actionButtons');
    if (status !== 'Pending') {
        if (status === 'Approved' || status === 'Confirmed') {
            btns.innerHTML = '<button type="submit" onclick="setAction(\'cancel\')" class="bg-red-600 hover:bg-red-700 text-white py-3 rounded-lg font-bold transition shadow-sm w-full">Cancel Booking</button>';
        } else {
            btns.innerHTML = '<p class="text-center text-gray-400 py-4 italic">No actions available for ' + status + ' bookings.</p>';
        }
    } else {
        btns.innerHTML = `
            <button type="submit" onclick="setAction('approve')" class="bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-bold transition shadow-sm flex items-center justify-center gap-2">
                <i class="fa-solid fa-check"></i> Approve Booking
            </button>
            <button type="button" onclick="rejectBooking()" class="bg-red-600 hover:bg-red-700 text-white py-3 rounded-lg font-bold transition shadow-sm flex items-center justify-center gap-2">
                <i class="fa-solid fa-times"></i> Reject Booking
            </button>
        `;
    }
});

function setAction(act) {
    document.getElementById('modalAction').value = act;
}

function rejectBooking() {
    document.getElementById('rejectSection').classList.remove('d-none');
}

function loadBookingFrame() {
    const fid = document.getElementById('newBookingFacility').value;
    const frame = document.getElementById('bookingFrame');
    const ph = document.getElementById('framePlaceholder');
    if (!fid) { alert("Please select a facility."); return; }
    
    ph.classList.add('d-none');
    frame.classList.remove('d-none');
    frame.src = "book_walkin.php?facility_id=" + encodeURIComponent(fid);
}

window.addEventListener('message', function(event) {
    if (event.data && event.data.type === 'booking_success') {
        window.location.href = "manage_bookings.php?msg=" + encodeURIComponent(event.data.message);
    }
});
</script>
</body>
</html>