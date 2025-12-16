<?php
require_once '../includes/db_connect.php';

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
        $adminID = $rowAdmin['UserIdentifier'];
    }
    $stmtAdmin->close();
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
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
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
        case 'Canceled': return 'bg-danger';
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        body {
            background: url('../assets/img/background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            font-family: 'Poppins', sans-serif;
        }

        nav {
            background: #bfd9dc;
            padding: 10px 40px;
            border-bottom-left-radius: 25px;
            border-bottom-right-radius: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }

        .nav-logo img { height: 65px; }
        .nav-link {
            color: #071239ff;
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 12px;
            transition: 0.3s ease;
            text-decoration: none;
        }
        .nav-link:hover, .nav-link.active { 
            background: rgba(255,255,255,0.5); 
            color: #071239ff;
        }

        .dropdown-menu {
            border-radius: 12px;
            background: #bfd9dc;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            padding: 5px;
        }
        .dropdown-item {
            color: #071239ff;
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 10px;
            transition: 0.3s ease;
        }
        .dropdown-item:hover {
            background: rgba(255,255,255,0.5);
            color: #071239ff;
        }
        
        .main-box {
            background: #bfd9dc;
            border-radius: 25px;
            padding: 30px 40px;
            max-width: 1200px; 
            margin: 40px auto;
            box-shadow: 0 0 20px rgba(0,0,0,0.25);
        }

        h1 {
            font-weight: 900;
            text-align: center;
            margin-bottom: 20px;
            font-size: 36px;
            color: #071239ff;
        }

        .form-label {
            font-weight: 600;
            font-size: 14px;  
            color: #071239ff;
            margin-bottom: 5px; 
        }

        .form-control, .form-select, textarea {
            border-radius: 12px;
            padding: 6px 12px;
            font-size: 14px;
            color: #071239ff;
        }

        .btn-submit { background: #1e40af; color: white; padding: 6px 22px; border-radius: 10px; }
        .btn-search { background: #071239ff; color: white; border-radius: 10px; padding: 6px 15px;}
        
        .search-box {
            background: #fff;
            border-radius: 15px;
            padding: 15px 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .table thead th {
            background-color: #071239ff;
            color: white;
            border-bottom: 3px solid #1e40af;
            font-weight: 700;
        }
        .table-hover > tbody > tr:hover > * {
            background-color: rgba(30, 64, 175, 0.2) !important;
        }
        .section-card {
            background: rgba(255, 255, 255, 0.5); 
            border-radius: 15px;
            padding: 20px;
            border: 1px solid #bfd9dc; 
            height: 100%;
        }

        .section-title {
            font-weight: 800;
            color: #1e40af;
            margin-top: 0;
            margin-bottom: 15px;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 5px;
        }
    </style>
</head>

<body>

<nav class="d-flex justify-content-between align-items-center px-4 py-2">
    <div class="nav-logo d-flex align-items-center gap-3">
        <img src="../assets/img/ukm.png" alt="UKM Logo" height="45">
        <img src="../assets/img/pusatsukanlogo.png" alt="Pusat Sukan Logo" height="45">
    </div>

    <div class="d-flex align-items-center gap-4">
        
        <div class="dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                Facility
            </a>

            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="addfacilities.php">Add Facility</a></li>
                <li><a class="dropdown-item" href="manage_closures.php">Facility Closures</a></li>
            </ul>
        </div>
        
        <a class="nav-link active" href="bookinglist.php">Booking</a>
        
        <a class="nav-link" href="report.php">Report</a>

        <div class="dropdown">
            <a href="#" class="d-flex align-items-center gap-2 text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="../assets/img/user.png" class="rounded-circle" style="width:45px; height:45px;">
                <div style="line-height:1.2; text-align: left;">
                    <div class="fw-bold" style="color:#071239ff;"><?php echo htmlspecialchars($adminName); ?></div>
                    <small class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars($adminID); ?></small>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item text-danger" href="../logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="main-box">
    <h1><?php echo $page_title; ?></h1>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_GET['msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['err'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_GET['err']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="search-box d-flex justify-content-between align-items-center">
        
        <form method="GET" class="d-flex gap-3 align-items-center">
            <span class="fw-bold me-2" style="color:#071239ff;">Filter:</span>
            
            <div class="btn-group">
                <a href="?status=all&date=<?php echo htmlspecialchars($dateFilter); ?>&search=<?php echo htmlspecialchars($searchQuery); ?>" class="btn btn-sm btn-outline-secondary <?php echo ($statusFilter == 'all') ? 'active' : ''; ?>">All</a>
                <a href="?status=Pending&date=<?php echo htmlspecialchars($dateFilter); ?>&search=<?php echo htmlspecialchars($searchQuery); ?>" class="btn btn-sm btn-outline-warning <?php echo ($statusFilter == 'Pending') ? 'active' : ''; ?>">Pending</a>
                <a href="?status=Confirmed&date=<?php echo htmlspecialchars($dateFilter); ?>&search=<?php echo htmlspecialchars($searchQuery); ?>" class="btn btn-sm btn-outline-success <?php echo ($statusFilter == 'Confirmed') ? 'active' : ''; ?>">Confirmed</a>
                <a href="?status=Canceled&date=<?php echo htmlspecialchars($dateFilter); ?>&search=<?php echo htmlspecialchars($searchQuery); ?>" class="btn btn-sm btn-outline-danger <?php echo ($statusFilter == 'Canceled') ? 'active' : ''; ?>">Canceled</a>
            </div>
            
            <input type="date" name="date" class="form-control form-control-sm" style="width: 150px;" value="<?php echo htmlspecialchars($dateFilter); ?>">
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
            <button type="submit" class="btn btn-search btn-sm">Filter</button>
        </form>

        <form method="GET" class="d-flex gap-3 align-items-center">
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
            <input type="hidden" name="date" value="<?php echo htmlspecialchars($dateFilter); ?>">
            <input type="search" name="search" class="form-control form-control-sm" placeholder="Search ID, User, or Facility" value="<?php echo htmlspecialchars($searchQuery); ?>">
            <button type="submit" class="btn btn-search btn-sm">Search</button>
            
            <button type="button" class="btn btn-submit btn-sm" data-bs-toggle="modal" data-bs-target="#newBookingModal">
                <i class="fas fa-plus me-1"></i> Add New Booking
            </button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Facility Name</th>
                    <th scope="col">Booked By</th>
                    <th scope="col">Timeframe</th>
                    <th scope="col">Status</th>
                    <th scope="col">Booked At</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking): 
                    // Determine "Created By"
                    $createdBy = "Created by Student";
                    if (!empty($booking['CreatedByAdminID'])) {
                        $createdBy = "Created by Staff";
                    }

                    // User Display
                    $userDisplay = htmlspecialchars($booking['FirstName'] . ' ' . $booking['LastName']);
                    if (empty(trim($userDisplay))) {
                        $userDisplay = htmlspecialchars($booking['UserIdentifier']); // Fallback to ID
                    }
                ?>
                <tr>
                    <td>#<?php echo $booking['BookingID']; ?></td>
                    <td><strong style="color:#1e40af;"><?php echo htmlspecialchars($booking['FacilityName']); ?></strong></td>
                    <td>
                        <?php echo $userDisplay; ?><br>
                        <small class="text-muted"><?php echo $createdBy; ?></small>
                    </td>
                    <td>
                        <div class="fw-bold"><?php echo date('d M Y', strtotime($booking['StartTime'])); ?></div>
                        <small class="text-muted"><?php echo date('H:i', strtotime($booking['StartTime'])) . ' - ' . date('H:i', strtotime($booking['EndTime'])); ?></small>
                    </td>
                    <td>
                        <span class="badge <?php echo getStatusClass($booking['Status']); ?>"><?php echo $booking['Status']; ?></span>
                    </td>
                    <td><small class="text-muted"><?php echo date('d M Y H:i', strtotime($booking['BookedAt'])); ?></small></td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius:10px;">
                                Manage
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="#" 
                                       data-bs-toggle="modal" data-bs-target="#viewEditModal"
                                       data-id="<?php echo $booking['BookingID']; ?>"
                                       data-facility="<?php echo htmlspecialchars($booking['FacilityName']); ?>"
                                       data-user="<?php echo $userDisplay; ?>"
                                       data-start="<?php echo $booking['StartTime']; ?>"
                                       data-end="<?php echo $booking['EndTime']; ?>"
                                       data-status="<?php echo $booking['Status']; ?>"
                                       data-booked-at="<?php echo $booking['BookedAt']; ?>"
                                       data-created-by="<?php echo $createdBy; ?>">
                                       View Details
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <nav aria-label="Page navigation" class="pt-3">
        <ul class="pagination justify-content-end">
            <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
            <li class="page-item active"><a class="page-link" href="#" style="background:#1e40af; border-color:#1e40af;">1</a></li>
            <li class="page-item"><a class="page-link" href="#">2</a></li>
            <li class="page-item"><a class="page-link" href="#">Next</a></li>
        </ul>
    </nav>

</div>

<div class="modal fade" id="viewEditModal" tabindex="-1" aria-labelledby="viewEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:15px;">
            <div class="modal-header" style="background:#071239ff; color:white; border-top-left-radius:15px; border-top-right-radius:15px;">
                <h5 class="modal-title" id="viewEditModalLabel">Booking Details - #<span id="bookingIdDisplay"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="processBookingForm" action="process.php" method="POST">
                    <input type="hidden" name="booking_id" id="modalBookingId">
                    <input type="hidden" name="action" id="modalAction">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Facility</label>
                            <input type="text" class="form-control" id="modalFacility" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Booked By</label>
                            <input type="text" class="form-control" id="modalUser" readonly>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="startTime" class="form-label">Start Time</label>
                            <input type="text" class="form-control" id="startTime" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="endTime" class="form-label">End Time</label>
                            <input type="text" class="form-control" id="endTime" readonly>
                        </div>
                    </div>
                    
                    <div class="mb-4 p-3 section-card">
                        <h4 class="section-title">Admin Management</h4>
                        <div class="d-flex align-items-center mb-3">
                            <label class="form-label me-3 mb-0">Current Status:</label>
                            <span class="badge" id="modalStatusBadge"></span>
                        </div>

                        <div id="actionButtons" class="d-flex gap-2 mb-3">
                            <button type="button" class="btn btn-submit" style="background:green;" onclick="submitAction('approve')">Approve Booking</button>
                            <button type="button" class="btn btn-reset" style="background:#dc3545; color:white; border-radius:10px; padding:6px 22px;" onclick="submitAction('reject')">Reject Booking</button>
                        </div>

                        <label for="adminNotes" class="form-label">Admin Notes / Reason</label>
                        <textarea class="form-control" name="admin_notes" id="adminNotes" rows="3" placeholder="Required for rejection. Optional for approval."></textarea>
                    </div>

                    <div class="row text-muted small mt-2">
                        <div class="col-md-4" id="modalBookedAt"></div>
                        <div class="col-md-4" id="modalUpdatedInfo"></div>
                        <div class="col-md-4" id="modalCreatedBy"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" style="border-radius:10px;" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const viewEditModal = document.getElementById('viewEditModal');
    viewEditModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        
        const id = button.getAttribute('data-id');
        const facility = button.getAttribute('data-facility');
        const user = button.getAttribute('data-user');
        const start = button.getAttribute('data-start');
        const end = button.getAttribute('data-end');
        const status = button.getAttribute('data-status');
        const bookedAt = button.getAttribute('data-booked-at');
        const createdBy = button.getAttribute('data-created-by');

        // Populate
        document.getElementById('bookingIdDisplay').textContent = id;
        document.getElementById('modalBookingId').value = id;
        document.getElementById('modalFacility').value = facility;
        document.getElementById('modalUser').value = user;
        document.getElementById('startTime').value = start;
        document.getElementById('endTime').value = end;
        document.getElementById('adminNotes').value = '';
        
        // Status Badge
        const statusBadge = document.getElementById('modalStatusBadge');
        statusBadge.textContent = status;
        statusBadge.className = 'badge fw-bold ' + getStatusClass(status);
        
        // Footer
        document.getElementById('modalBookedAt').textContent = 'Booked On: ' + bookedAt;
        document.getElementById('modalCreatedBy').textContent = createdBy;
        
        // Action Buttons
        const actionButtons = document.getElementById('actionButtons');
        const adminNotes = document.getElementById('adminNotes');
        
        if (status === 'Pending') {
            actionButtons.style.display = 'flex';
            adminNotes.readOnly = false;
        } else {
            actionButtons.style.display = 'none';
            adminNotes.readOnly = true;
        }
    });
});

function getStatusClass(status) {
    switch (status) {
        case 'Pending': return 'bg-warning text-dark'; 
        case 'Confirmed': return 'bg-success';
        case 'Canceled': return 'bg-danger';
        case 'Complete': return 'bg-secondary';
        default: return 'bg-info';
    }
}

function submitAction(action) {
    const form = document.getElementById('processBookingForm');
    const notes = document.getElementById('adminNotes').value.trim();
    
    document.getElementById('modalAction').value = action;
    
    if (action === 'reject' && notes === '') {
        alert('Please provide a reason for rejection in the Admin Notes field.');
        document.getElementById('adminNotes').focus();
        return;
    }
    
    if (confirm('Are you sure you want to ' + action + ' this booking?')) {
        form.submit();
    }
}
</script>
</body>
</html>