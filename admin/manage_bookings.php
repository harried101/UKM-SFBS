<?php
session_start();
require_once '../includes/db_connect.php';

// Auth Check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

$page_title = "ALL BOOKINGS MANAGEMENT";

// Fetch Admin Details
$adminIdentifier = $_SESSION['user_id'] ?? '';
$adminName = 'Admin';
$adminID = $adminIdentifier;

if ($adminIdentifier) {
    $stmtAdmin = $conn->prepare("SELECT FirstName, LastName, UserIdentifier FROM users WHERE UserIdentifier = ?");
    $stmtAdmin->bind_param("s", $adminIdentifier);
    $stmtAdmin->execute();
    $resAdmin = $stmtAdmin->get_result();
    if ($rowAdmin = $resAdmin->fetch_assoc()) {
        $adminName = $rowAdmin['FirstName'] . ' ' . $rowAdmin['LastName'];
        // $adminID already set
    }
    $stmtAdmin->close();
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
    $sql .= " AND (b.BookingID LIKE ? OR u.UserIdentifier LIKE ? OR u.FirstName LIKE ? OR u.LastName LIKE ? OR f.Name LIKE ?)";
    // 5 search params
    $params[] = $searchTerm; $params[] = $searchTerm; $params[] = $searchTerm; $params[] = $searchTerm; $params[] = $searchTerm;
    $types .= "sssss";
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
    <title><?php echo $page_title; ?> - UKM-SFBS Admin</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Bootstrap CSS for Modals -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

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

        /* Overriding Bootstrap for consistent look */
        .btn-primary-custom {
            background-color: #0b4d9d;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: 0.2s;
        }
        .btn-primary-custom:hover {
            background-color: #083a75;
            color: white;
        }

        /* Form Inputs */
        input:focus, select:focus {
            outline: none;
            border-color: #0b4d9d;
            box-shadow: 0 0 0 1px #0b4d9d;
        }
        
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
            <a href="dashboard.php" class="text-gray-600 hover:text-[#0b4d9d] font-medium transition flex items-center gap-2">
                Home
            </a>
            
            <a href="addfacilities.php" class="text-gray-600 hover:text-[#0b4d9d] font-medium transition flex items-center gap-2">
                Facilities
            </a>
            
            <!-- Active State -->
            <a href="bookinglist.php" class="text-[#0b4d9d] font-bold transition flex items-center gap-2">
                <span class="p-2 rounded-full bg-[#0b4d9d] text-white shadow-sm">
                    Bookings
                </span>
            </a>
            
            <div class="flex items-center gap-3 pl-6 border-l border-gray-200">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($adminName); ?></p>
                    <p class="text-xs text-gray-500 uppercase tracking-wider"><?php echo htmlspecialchars($adminID); ?></p>
                </div>
                <!-- Profile Dropdown Container -->
                <div class="relative group">
                    <button class="flex items-center focus:outline-none">
                        <img src="../assets/img/user.png" alt="Profile" class="w-10 h-10 rounded-full border-2 border-white shadow-md object-cover hover:scale-105 transition">
                    </button>
                    <!-- Dropdown Menu -->
                    <div class="absolute right-0 top-full pt-2 w-48 hidden group-hover:block z-50">
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
            <h1 class="text-3xl font-bold text-[#0b4d9d] mb-1">Booking Management</h1>
            <p class="text-gray-500">View, filter, and manage student facility bookings.</p>
        </div>
        
        <button type="button" class="bg-[#0b4d9d] text-white px-5 py-2.5 rounded-lg hover:bg-[#083a75] transition shadow-sm font-medium flex items-center gap-2" data-bs-toggle="modal" data-bs-target="#newBookingModal">
            <i class="fas fa-plus"></i> Walk-in Booking
        </button>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex justify-between items-center shadow-sm">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
            <button onclick="this.parentElement.remove()" class="text-green-500 hover:text-green-700"><i class="fas fa-times"></i></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['err'])): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex justify-between items-center shadow-sm">
            <div class="flex items-center gap-2">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($_GET['err']); ?>
            </div>
            <button onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700"><i class="fas fa-times"></i></button>
        </div>
    <?php endif; ?>

    <!-- Search & Filter Box -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-8">
        <h5 class="font-bold text-gray-700 mb-4 flex items-center gap-2 text-sm uppercase tracking-wide"><i class="fa-solid fa-filter text-[#0b4d9d]"></i> Filters</h5>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Search</label>
                <input type="search" name="search" class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:border-[#0b4d9d] focus:ring-1 focus:ring-[#0b4d9d]" placeholder="ID, Name, Facility..." value="<?php echo htmlspecialchars($searchQuery); ?>">
            </div>
            
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Status</label>
                <select name="status" class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:border-[#0b4d9d] focus:ring-1 focus:ring-[#0b4d9d] bg-white">
                    <option value="all" <?php echo ($statusFilter == 'all') ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="Pending" <?php echo ($statusFilter == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="Confirmed" <?php echo ($statusFilter == 'Confirmed') ? 'selected' : ''; ?>>Confirmed/Approved</option>
                    <option value="Canceled" <?php echo ($statusFilter == 'Canceled') ? 'selected' : ''; ?>>Canceled</option>
                </select>
            </div>
            
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Date</label>
                <input type="date" name="date" class="w-full p-2 border border-gray-300 rounded-lg text-sm focus:border-[#0b4d9d] focus:ring-1 focus:ring-[#0b4d9d]" value="<?php echo htmlspecialchars($dateFilter); ?>">
            </div>
            
            <div class="flex gap-2">
                <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900 transition text-sm font-bold flex-grow">Apply</button>
                <a href="bookinglist.php" class="bg-white border border-gray-300 text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-50 transition text-sm font-bold text-center">Reset</a>
            </div>
        </form>
    </div>

    <!-- Bookings Table -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 text-gray-500 font-semibold uppercase text-xs">
                    <tr>
                        <th class="py-4 px-6">ID</th>
                        <th class="py-4 px-6">Facility</th>
                        <th class="py-4 px-6">User</th>
                        <th class="py-4 px-6">Schedule</th>
                        <th class="py-4 px-6 text-center">Status</th>
                        <th class="py-4 px-6 text-end">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    <?php if (empty($bookings)): ?>
                        <tr><td colspan="6" class="text-center py-8 text-gray-400">No bookings found matching your criteria.</td></tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $booking): 
                            // User Display Logic
                            $fullName = trim(($booking['FirstName'] ?? '') . ' ' . ($booking['LastName'] ?? ''));
                            $userId = $booking['UserIdentifier'] ?? 'Unknown';
                            
                            // Created By Logic
                            $createdBy = !empty($booking['CreatedByAdminID']) ? '<span class="bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded text-[10px] border border-gray-200 ml-1 font-mono">STAFF</span>' : '';
                            
                            // Dates
                            $start = new DateTime($booking['StartTime']);
                            $end = new DateTime($booking['EndTime']);
                        ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-bold text-gray-700">#<?php echo $booking['BookingID']; ?></td>
                            <td class="px-6 py-4 text-[#0b4d9d] font-bold"><?php echo htmlspecialchars($booking['FacilityName'] ?? 'Unknown Facility'); ?></td>
                            <td class="px-6 py-4">
                                <?php if (!empty($fullName)): ?>
                                    <div class="fw-bold text-gray-800"><?php echo htmlspecialchars($fullName); ?></div>
                                <?php endif; ?>
                                <div class="text-xs text-gray-500 flex items-center">
                                    <span class="font-mono bg-gray-50 px-1 rounded text-[11px]"><?php echo htmlspecialchars($userId); ?></span> 
                                    <?php echo $createdBy; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="fw-bold text-gray-700"><?php echo $start->format('d M Y'); ?></div>
                                <small class="text-gray-500 font-medium"><?php echo $start->format('h:i A') . ' - ' . $end->format('h:i A'); ?></small>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2.5 py-1 rounded text-xs font-bold border <?php echo getStatusClass($booking['Status']); ?>">
                                    <?php echo $booking['Status']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-end">
                                <button class="text-[#0b4d9d] hover:text-[#083a75] font-semibold text-xs border border-[#0b4d9d] hover:bg-blue-50 px-3 py-1.5 rounded transition" 
                                        data-bs-toggle="modal" data-bs-target="#viewEditModal"
                                        data-id="<?php echo $booking['BookingID']; ?>"
                                        data-facility="<?php echo htmlspecialchars($booking['FacilityName'] ?? 'Unknown'); ?>"
                                        data-user="<?php echo htmlspecialchars($fullName ?: $userId); ?>"
                                        data-start="<?php echo $booking['StartTime']; ?>"
                                        data-end="<?php echo $booking['EndTime']; ?>"
                                        data-status="<?php echo $booking['Status']; ?>"
                                        data-booked-at="<?php echo $booking['BookedAt']; ?>">
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

<!-- FOOTER (Exact Match to Student Facilities) -->
<footer class="bg-white border-t border-gray-200 py-6 mt-auto">
    <div class="container mx-auto px-6">
        <div class="flex flex-col md:flex-row justify-between items-center gap-6">
            <!-- Logo & Address -->
            <div class="flex items-center gap-4">
                <img src="../assets/img/pusatsukanlogo.png" alt="Pusat Sukan Logo" class="h-12 w-auto">
                <div class="text-xs text-gray-600 leading-snug">
                    <strong class="block text-gray-800 text-sm mb-0.5">PEJABAT PENGARAH PUSAT SUKAN</strong>
                    Stadium Universiti, Universiti Kebangsaan Malaysia<br>
                    43600 Bangi, Selangor Darul Ehsan<br>
                    <span class="mt-0.5 block text-[#0b4d9d] font-semibold"><i class="fa-solid fa-phone mr-1"></i> 03-8921-5306</span>
                </div>
            </div>
            
            <!-- SDG Logo & Copyright -->
            <div class="flex items-center gap-6">
                <img src="../assets/img/sdg.png" alt="SDG Logo" class="h-14 w-auto opacity-90">
                <p class="text-[10px] text-gray-400 text-right">
                    &copy; 2025 Universiti Kebangsaan Malaysia.<br>All rights reserved.
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- VIEW/EDIT MODAL -->
<div class="modal fade" id="viewEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-xl rounded-lg">
            <div class="modal-header bg-gray-50 border-bottom">
                <h5 class="modal-title fw-bold text-[#0b4d9d]">Manage Booking #<span id="bookingIdDisplay"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="processBookingForm" action="process.php" method="POST">
                    <input type="hidden" name="booking_id" id="modalBookingId">
                    <input type="hidden" name="action" id="modalAction">
                    
                    <div class="mb-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small uppercase fw-bold">Facility</span>
                            <span class="fw-bold text-dark" id="modalFacility"></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small uppercase fw-bold">User</span>
                            <span class="fw-bold text-dark" id="modalUser"></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small uppercase fw-bold">Time</span>
                            <span class="fw-bold text-dark" id="modalTime"></span>
                        </div>
                    </div>

                    <div id="actionButtons" class="d-grid gap-2 mb-3">
                        <button type="button" class="btn btn-success fw-bold py-2" onclick="submitAction('approve')">
                            <i class="fas fa-check me-2"></i>Approve Request
                        </button>
                        <button type="button" class="btn btn-danger fw-bold py-2" onclick="submitAction('reject')">
                            <i class="fas fa-times me-2"></i>Reject / Cancel
                        </button>
                    </div>

                    <div class="mb-3">
                        <label for="adminNotes" class="form-label small fw-bold text-gray-500 uppercase">Admin Notes (Reason)</label>
                        <textarea class="form-control" name="admin_notes" id="adminNotes" rows="2" placeholder="Reason for rejection or internal note..."></textarea>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- WALK-IN BOOKING MODAL -->
<div class="modal fade" id="newBookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-2xl rounded-lg" style="height: 85vh;">
            <div class="modal-header bg-[#0b4d9d] text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-calendar-plus me-2"></i>New Walk-in Booking</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 d-flex flex-column bg-light">
                
                <!-- Step 1: Select Facility -->
                <div id="facilitySelector" class="p-4 bg-white border-bottom shadow-sm">
                    <div class="d-flex justify-content-center align-items-center gap-3">
                        <label class="fw-bold text-secondary">Select Facility:</label>
                        <select id="newBookingFacility" class="form-select w-auto" style="min-width: 250px;">
                            <option value="">-- Choose Facility --</option>
                            <?php foreach($facilitiesList as $fac): ?>
                                <option value="<?php echo $fac['FacilityID']; ?>"><?php echo htmlspecialchars($fac['Name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-primary-custom" onclick="loadBookingFrame()">Load Schedule</button>
                    </div>
                </div>

                <!-- Step 2: Iframe Container -->
                <div class="flex-grow-1 position-relative w-100">
                    <div id="frameLoader" class="position-absolute top-50 start-50 translate-middle text-center d-none">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted small">Loading schedule...</p>
                    </div>
                    
                    <iframe id="bookingFrame" src="" class="w-100 h-100 border-0 d-none"></iframe>
                    
                    <div id="framePlaceholder" class="d-flex align-items-center justify-content-center h-100 text-muted">
                        <div class="text-center">
                            <i class="fas fa-arrow-up fa-2x mb-3 text-secondary opacity-50"></i>
                            <p>Please select a facility above to view availability.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // View/Edit Modal Logic
    const viewEditModal = document.getElementById('viewEditModal');
    viewEditModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        
        // Extract info
        const id = button.getAttribute('data-id');
        const status = button.getAttribute('data-status');
        
        // Populate fields
        document.getElementById('bookingIdDisplay').textContent = id;
        document.getElementById('modalBookingId').value = id;
        document.getElementById('modalFacility').textContent = button.getAttribute('data-facility');
        document.getElementById('modalUser').textContent = button.getAttribute('data-user');
        
        // Format time
        const start = new Date(button.getAttribute('data-start'));
        const end = new Date(button.getAttribute('data-end'));
        const timeStr = start.toLocaleDateString() + ' (' + 
                        start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) + ' - ' + 
                        end.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) + ')';
        document.getElementById('modalTime').textContent = timeStr;
        
        // Toggle Buttons based on status
        const actionButtons = document.getElementById('actionButtons');
        const adminNotes = document.getElementById('adminNotes');
        
        if (status === 'Pending') {
            actionButtons.style.display = 'grid';
            adminNotes.readOnly = false;
        } else {
            // Allow cancelling existing bookings
            if (status === 'Approved' || status === 'Confirmed') {
                actionButtons.innerHTML = '<button type="button" class="btn btn-danger w-100 fw-bold py-2" onclick="submitAction(\'cancel\')">Cancel Booking</button>';
                actionButtons.style.display = 'block';
                adminNotes.readOnly = false;
            } else {
                actionButtons.style.display = 'none';
                adminNotes.readOnly = true;
                adminNotes.placeholder = "Booking is " + status + ". No actions available.";
            }
        }
    });
});

function submitAction(action) {
    const form = document.getElementById('processBookingForm');
    const notes = document.getElementById('adminNotes').value.trim();
    
    document.getElementById('modalAction').value = action;
    
    if ((action === 'reject' || action === 'cancel') && notes === '') {
        if(!confirm("Are you sure you want to " + action + " without a note? It's better to provide a reason.")) return;
    }
    
    form.submit();
}

// --- WALK-IN MODAL LOGIC ---
function loadBookingFrame() {
    const facilityID = document.getElementById('newBookingFacility').value;
    const frame = document.getElementById('bookingFrame');
    const loader = document.getElementById('frameLoader');
    const placeholder = document.getElementById('framePlaceholder');

    if (!facilityID) {
        alert("Please select a facility first.");
        return;
    }

    placeholder.classList.add('d-none');
    loader.classList.remove('d-none');
    frame.classList.add('d-none');
    
    // Ensure book_walkin.php exists in the same directory
    frame.src = "book_walkin.php?facility_id=" + encodeURIComponent(facilityID);
    
    frame.onload = function() {
        loader.classList.add('d-none');
        frame.classList.remove('d-none');
    };
}

// Listen for success message from iframe
window.addEventListener('message', function(event) {
    if (event.data && event.data.type === 'booking_success') {
        const modalEl = document.getElementById('newBookingModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if(modal) modal.hide();
        
        // Reload to show new booking
        window.location.href = "bookinglist.php?msg=" + encodeURIComponent(event.data.message);
    }
});

// Reset Walk-in Modal on Close
document.getElementById('newBookingModal').addEventListener('hidden.bs.modal', function () {
    const frame = document.getElementById('bookingFrame');
    frame.src = "";
    frame.classList.add('d-none');
    document.getElementById('framePlaceholder').classList.remove('d-none');
    document.getElementById('newBookingFacility').value = "";
});
</script>
</body>
</html>