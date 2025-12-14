<?php

$page_title = "ALL BOOKINGS MANAGEMENT";

$bookings = [
    ['id' => 1001, 'facility' => 'Lecture Hall A', 'user' => 'Ali Bin Ahmad', 'start' => '2025-12-18 10:00', 'end' => '2025-12-18 12:00', 'status' => 'Pending', 'booked_at' => '2025-12-15 08:30'],
    ['id' => 1002, 'facility' => 'Research Lab 3', 'user' => 'Dr. Siti Norliza', 'start' => '2026-01-05 14:00', 'end' => '2026-01-05 17:00', 'status' => 'Confirmed', 'booked_at' => '2025-12-10 11:00'],
    ['id' => 1003, 'facility' => 'Meeting Room C', 'user' => 'Department of IT', 'start' => '2025-12-20 09:00', 'end' => '2025-12-20 10:00', 'status' => 'Canceled', 'booked_at' => '2025-12-16 16:45'],
    ['id' => 1004, 'facility' => 'Sports Field', 'user' => 'Student Club A', 'start' => '2025-12-17 18:00', 'end' => '2025-12-17 20:00', 'status' => 'Complete', 'booked_at' => '2025-12-01 10:00'],
];

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

        <div class="d-flex align-items-center gap-1">
            <img src="../assets/img/user.png" class="rounded-circle" style="width:45px; height:45px;">
            <span class="fw-bold" style="color:#071239ff;"><?php echo htmlspecialchars($_SESSION['user_id'] ?? 'User'); ?></span>
        </div>
    </div>
</nav>

<div class="main-box">
    <h1><?php echo $page_title; ?></h1>

    <div class="search-box d-flex justify-content-between align-items-center">
        
        <div class="d-flex gap-3 align-items-center">
            <span class="fw-bold me-2" style="color:#071239ff;">Filter:</span>
            
            <a href="?status=all" class="btn btn-sm btn-outline-secondary <?php echo (!isset($_GET['status']) || $_GET['status'] == 'all') ? 'active' : ''; ?>">All</a>
            <a href="?status=Pending" class="btn btn-sm btn-outline-warning <?php echo (isset($_GET['status']) && $_GET['status'] == 'Pending') ? 'active' : ''; ?>">Pending</a>
            <a href="?status=Confirmed" class="btn btn-sm btn-outline-success <?php echo (isset($_GET['status']) && $_GET['status'] == 'Confirmed') ? 'active' : ''; ?>">Confirmed</a>
            <a href="?status=Canceled" class="btn btn-sm btn-outline-danger <?php echo (isset($_GET['status']) && $_GET['status'] == 'Canceled') ? 'active' : ''; ?>">Canceled</a>
            
            <input type="date" class="form-control form-control-sm" style="width: 150px;">
            <button class="btn btn-search btn-sm">Filter</button>
        </div>

        <div class="d-flex gap-3 align-items-center">
            <input type="search" class="form-control form-control-sm" placeholder="Search ID, User, or Facility">
            <button class="btn btn-submit btn-sm" data-bs-toggle="modal" data-bs-target="#newBookingModal">
                <i class="fas fa-plus me-1"></i> Add New Booking
            </button>
        </div>
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
                <?php foreach ($bookings as $booking): ?>
                <tr>
                    <td>#<?php echo $booking['id']; ?></td>
                    <td><strong style="color:#1e40af;"><?php echo $booking['facility']; ?></strong></td>
                    <td><?php echo $booking['user']; ?></td>
                    <td>
                        <div class="fw-bold"><?php echo date('d M Y', strtotime($booking['start'])); ?></div>
                        <small class="text-muted"><?php echo date('H:i', strtotime($booking['start'])) . ' - ' . date('H:i', strtotime($booking['end'])); ?></small>
                    </td>
                    <td>
                        <span class="badge <?php echo getStatusClass($booking['status']); ?>"><?php echo $booking['status']; ?></span>
                    </td>
                    <td><small class="text-muted"><?php echo date('d M Y H:i', strtotime($booking['booked_at'])); ?></small></td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius:10px;">
                                Manage
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#viewEditModal">View Details</a></li>
                                <?php if ($booking['status'] == 'Pending'): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-success" href="process.php?action=approve&id=<?php echo $booking['id']; ?>">Approve</a></li>
                                    <li><a class="dropdown-item text-danger" href="process.php?action=reject&id=<?php echo $booking['id']; ?>">Reject</a></li>
                                <?php endif; ?>
                                <?php if ($booking['status'] == 'Confirmed'): ?>
                                    <li><a class="dropdown-item text-danger" href="process.php?action=cancel&id=<?php echo $booking['id']; ?>">Cancel Booking</a></li>
                                <?php endif; ?>
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
                <h5 class="modal-title" id="viewEditModalLabel">Booking Details - #<span id="bookingIdDisplay">1001</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editBookingForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Facility</label>
                            <input type="text" class="form-control" value="Lecture Hall A">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Booked By</label>
                            <input type="text" class="form-control" value="Ali Bin Ahmad">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="startTime" class="form-label">Start Time</label>
                            <input type="datetime-local" class="form-control" id="startTime" value="2025-12-18T10:00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="endTime" class="form-label">End Time</label>
                            <input type="datetime-local" class="form-control" id="endTime" value="2025-12-18T12:00">
                        </div>
                    </div>
                    
                    <div class="mb-4 p-3 section-card">
                        <h4 class="section-title">Admin Management</h4>
                        <div class="d-flex align-items-center mb-3">
                            <label class="form-label me-3 mb-0">Current Status:</label>
                            <span class="badge bg-warning text-dark fw-bold">Pending</span>
                        </div>

                        <div class="d-flex gap-2 mb-3">
                            <button type="button" class="btn btn-submit" style="background:green;">Approve Booking</button>
                            <button type="button" class="btn btn-reset">Reject Booking</button>
                        </div>

                        <label for="adminNotes" class="form-label">Admin Notes / Justification</label>
                        <textarea class="form-control" id="adminNotes" rows="3"></textarea>
                    </div>

                    <div class="row text-muted small mt-2">
                        <div class="col-md-4">Booked On: 2025-12-15 08:30</div>
                        <div class="col-md-4">Last Updated: 2025-12-15 08:30</div>
                        <div class="col-md-4">Created by Admin: N/A (User booking)</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" style="border-radius:10px;" data-bs-dismiss="modal">Close</button>
                <button type="submit" form="editBookingForm" class="btn btn-submit">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>