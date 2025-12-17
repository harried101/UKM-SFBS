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
        case 'Pending': return 'bg-warning text-dark'; 
        case 'Confirmed': return 'bg-success';
        case 'Approved': return 'bg-success'; // Handle Approved as Confirmed visually
        case 'Canceled': return 'bg-danger';
        case 'Cancelled': return 'bg-danger'; // Handle spelling variation
        case 'Complete': return 'bg-secondary';
        default: return 'bg-info';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - UKM-SFBS Admin</title>
    
    <!-- Fonts & CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --primary: #0b4d9d; /* UKM Blue */
            --bg-light: #f8f9fa;
        }
        body {
            background-color: var(--bg-light);
            font-family: 'Inter', sans-serif;
            color: #333;
        }
        h1, h2, h3 { font-family: 'Playfair Display', serif; }

        /* Navbar Customization */
        .navbar-custom {
            background: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 0.8rem 1rem;
        }
        .nav-link { color: #555; font-weight: 500; }
        .nav-link:hover, .nav-link.active { color: var(--primary) !important; font-weight: 700; }

        /* Card & Table Styling */
        .main-box {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin: 40px auto;
            max-width: 1200px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
        }
        .search-box {
            background: #fff;
            padding: 15px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            margin-bottom: 25px;
        }
        .table thead th {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
            border: none;
        }
        .table-hover tbody tr:hover { background-color: #f0f7ff; }
        .btn-primary-custom {
            background-color: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        .btn-primary-custom:hover {
            background-color: #083a75;
            border-color: #083a75;
        }

        /* Status Badges */
        .badge { font-weight: 600; padding: 0.5em 0.8em; }
    </style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="#">
            <img src="../assets/img/ukm.png" height="35" alt="UKM">
            <span class="fw-bold" style="color: var(--primary)">Admin Portal</span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <a class="nav-link" href="addfacilities.php">Facilities</a>
            <a class="nav-link active" href="bookinglist.php">Bookings</a>
            <!-- Removed 'Closures' link from top nav as requested -->
            
            <div class="vr mx-2 text-muted"></div>
            
            <div class="d-flex align-items-center gap-2">
                <div class="text-end d-none d-md-block lh-1">
                    <div class="fw-bold small"><?php echo htmlspecialchars($adminName); ?></div>
                    <div class="text-muted" style="font-size: 0.75rem"><?php echo htmlspecialchars($adminID); ?></div>
                </div>
                <div class="dropdown">
                    <a href="#" class="d-block link-dark text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="../assets/img/user.png" alt="mdo" width="35" height="35" class="rounded-circle border">
                    </a>
                    <ul class="dropdown-menu text-small shadow">
                        <li><a class="dropdown-item text-danger" href="../logout.php" onclick="return confirm('Logout?');">Sign out</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<div class="container main-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary fw-bold mb-0">Booking Management</h2>
        
        <button type="button" class="btn btn-primary-custom btn-sm d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#newBookingModal">
            <i class="fas fa-plus"></i> Walk-in Booking
        </button>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_GET['msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['err'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($_GET['err']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Search & Filter -->
    <div class="search-box">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Search Term</label>
                <input type="search" name="search" class="form-control form-control-sm" placeholder="ID, Name, Facility..." value="<?php echo htmlspecialchars($searchQuery); ?>">
            </div>
            
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Filter by Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="all" <?php echo ($statusFilter == 'all') ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="Pending" <?php echo ($statusFilter == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="Confirmed" <?php echo ($statusFilter == 'Confirmed') ? 'selected' : ''; ?>>Confirmed/Approved</option>
                    <option value="Canceled" <?php echo ($statusFilter == 'Canceled') ? 'selected' : ''; ?>>Canceled</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Filter by Date</label>
                <input type="date" name="date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($dateFilter); ?>">
            </div>
            
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-dark btn-sm flex-grow-1">Apply Filters</button>
                <a href="bookinglist.php" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>

    <!-- Bookings Table -->
    <div class="table-responsive rounded-3 border">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th scope="col" class="py-3">ID</th>
                    <th scope="col" class="py-3">Facility</th>
                    <th scope="col" class="py-3">User</th>
                    <th scope="col" class="py-3">Schedule</th>
                    <th scope="col" class="py-3 text-center">Status</th>
                    <th scope="col" class="py-3 text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted">No bookings found matching your criteria.</td></tr>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): 
                        // User Display Logic
                        $userDisplay = htmlspecialchars($booking['FirstName'] . ' ' . $booking['LastName']);
                        if (empty(trim($userDisplay))) $userDisplay = htmlspecialchars($booking['UserIdentifier']);

                        // Created By Logic
                        $createdBy = !empty($booking['CreatedByAdminID']) ? '<span class="badge bg-light text-dark border">Staff</span>' : '';
                        
                        // Dates
                        $start = new DateTime($booking['StartTime']);
                        $end = new DateTime($booking['EndTime']);
                    ?>
                    <tr>
                        <td class="fw-bold">#<?php echo $booking['BookingID']; ?></td>
                        <td class="text-primary fw-semibold"><?php echo htmlspecialchars($booking['FacilityName']); ?></td>
                        <td>
                            <div class="fw-bold text-dark"><?php echo $userDisplay; ?></div>
                            <div class="small text-muted"><?php echo htmlspecialchars($booking['UserIdentifier']); ?> <?php echo $createdBy; ?></div>
                        </td>
                        <td>
                            <div class="fw-bold"><?php echo $start->format('d M Y'); ?></div>
                            <small class="text-muted"><?php echo $start->format('h:i A') . ' - ' . $end->format('h:i A'); ?></small>
                        </td>
                        <td class="text-center">
                            <span class="badge <?php echo getStatusClass($booking['Status']); ?>">
                                <?php echo $booking['Status']; ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" 
                                    data-bs-toggle="modal" data-bs-target="#viewEditModal"
                                    data-id="<?php echo $booking['BookingID']; ?>"
                                    data-facility="<?php echo htmlspecialchars($booking['FacilityName']); ?>"
                                    data-user="<?php echo $userDisplay; ?>"
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

<!-- VIEW/EDIT MODAL -->
<div class="modal fade" id="viewEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-primary">Manage Booking #<span id="bookingIdDisplay"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="processBookingForm" action="process.php" method="POST">
                    <input type="hidden" name="booking_id" id="modalBookingId">
                    <input type="hidden" name="action" id="modalAction">
                    
                    <div class="mb-3 p-3 bg-light rounded border">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Facility:</span>
                            <span class="fw-bold" id="modalFacility"></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">User:</span>
                            <span class="fw-bold" id="modalUser"></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Time:</span>
                            <span class="fw-bold" id="modalTime"></span>
                        </div>
                    </div>

                    <div id="actionButtons" class="d-grid gap-2 mb-3">
                        <button type="button" class="btn btn-success" onclick="submitAction('approve')">
                            <i class="fas fa-check me-2"></i>Approve Request
                        </button>
                        <button type="button" class="btn btn-danger" onclick="submitAction('reject')">
                            <i class="fas fa-times me-2"></i>Reject / Cancel
                        </button>
                    </div>

                    <div class="mb-3">
                        <label for="adminNotes" class="form-label small fw-bold">Admin Notes (Optional/Reason)</label>
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
        <div class="modal-content" style="height: 85vh;">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-calendar-plus me-2"></i>New Walk-in Booking</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 d-flex flex-column bg-light">
                
                <!-- Step 1: Select Facility -->
                <div id="facilitySelector" class="p-3 bg-white border-bottom shadow-sm">
                    <div class="d-flex justify-content-center align-items-center gap-3">
                        <label class="fw-bold text-secondary">Select Facility:</label>
                        <select id="newBookingFacility" class="form-select w-auto" style="min-width: 250px;">
                            <option value="">-- Choose Facility --</option>
                            <?php foreach($facilitiesList as $fac): ?>
                                <option value="<?php echo $fac['FacilityID']; ?>"><?php echo htmlspecialchars($fac['Name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-primary" onclick="loadBookingFrame()">Load Schedule</button>
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
                            <i class="fas fa-arrow-up fa-2x mb-3 text-secondary"></i>
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
            // If already processed, hide action buttons (view only mode)
            // Or allow cancellation of approved bookings
            if (status === 'Approved' || status === 'Confirmed') {
                actionButtons.innerHTML = '<button type="button" class="btn btn-danger w-100" onclick="submitAction(\'cancel\')">Cancel Booking</button>';
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