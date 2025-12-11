<?php
session_start();

// Only allow admin access
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/db_connect.php';

if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

$facilities = [];
$overrides = [];

// Fetch list of all facilities
$facility_result = $conn->query("SELECT FacilityID, Name FROM facilities ORDER BY FacilityID");
if ($facility_result) {
    while ($row = $facility_result->fetch_assoc()) {
        $facilities[] = $row;
    }
}

// Fetch all existing overrides
$override_sql = "SELECT c.OverrideID, c.FacilityID, c.StartTime, c.EndTime, c.Reason, f.Name 
                FROM scheduleoverrides c 
                JOIN facilities f ON c.FacilityID = f.FacilityID
                ORDER BY c.StartTime DESC";
$override_result = $conn->query($override_sql);
if ($override_result) {
    while ($row = $override_result->fetch_assoc()) {
        $overrides[] = $row;
    }
}

// Handle Override Addition (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_override'])) {
    // Debugging: print POST array
    // echo '<pre>'; print_r($_POST); echo '</pre>'; exit();

    $facilityId = trim($_POST['FacilityID'] ?? '');
    $startDate = trim($_POST['ClosureStartDate'] ?? '');
    $endDate = trim($_POST['ClosureEndDate'] ?? '');
    $reason = trim($_POST['Reason'] ?? '');

    if (empty($facilityId) || empty($startDate) || empty($endDate) || empty($reason)) {
        echo "<script>alert('Please fill in Facility, Start Date, End Date, and Reason.'); window.location='manage_closures.php';</script>";
        exit();
    }

    if (strtotime($startDate) > strtotime($endDate)) {
        echo "<script>alert('Error: End Date cannot be before Start Date.'); window.location='manage_closures.php';</script>";
        exit();
    }

    $startTime = $startDate . " 00:00:00"; 
    $endTime = $endDate . " 23:59:59";

    $stmt = $conn->prepare("INSERT INTO scheduleoverrides (FacilityID, StartTime, EndTime, Reason) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $facilityId, $startTime, $endTime, $reason);

    if ($stmt->execute()) {
        echo "<script>alert('Facility closure successfully scheduled from {$startDate} to {$endDate}.'); window.location='manage_closures.php';</script>";
    } else {
        echo "<script>alert('Error adding closure: " . $stmt->error . "'); window.location='manage_closures.php';</script>";
    }
    $stmt->close();
    exit();
}

// Handle Override Deletion (GET)
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    $stmt = $conn->prepare("DELETE FROM scheduleoverrides WHERE OverrideID = ?");
    $stmt->bind_param("i", $delete_id);

    if ($stmt->execute()) {
        echo "<script>alert('Closure successfully deleted.'); window.location='manage_closures.php';</script>";
    } else {
        echo "<script>alert('Error deleting closure: " . $stmt->error . "'); window.location='manage_closures.php';</script>";
    }
    $stmt->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Facility Closures</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: url('../assets/img/background.jpg') no-repeat center/cover; font-family: 'Poppins', sans-serif; }
nav { background: #bfd9dc; padding: 10px 40px; border-radius: 0 0 25px 25px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.3); position: sticky; top:0; z-index:1000;}
.nav-link { color: #071239ff; font-weight:600; padding:8px 18px; border-radius:12px; text-decoration:none;}
.nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.5);}
.main-box { background:#bfd9dc; border-radius:25px; padding:30px 40px; max-width:1200px; margin:40px auto; box-shadow:0 0 20px rgba(0,0,0,0.25);}
h1 { font-weight:900; text-align:center; margin-bottom:20px; font-size:36px; color:#071239ff;}
.form-label { font-weight:600; font-size:14px; color:#071239ff; margin-bottom:5px; }
.form-control, .form-select { border-radius:8px; padding:8px 12px; font-size:14px; color:#071239ff;}
.btn-submit { background:#1e40af; color:white; padding:8px 25px; border-radius:8px; font-weight:700; }
.btn-danger { padding:6px 12px; border-radius:6px; }
.closure-card { background:#ffffff; border-radius:15px; padding:25px; box-shadow:0 5px 15px rgba(0,0,0,0.1); border:1px solid #ddd;}
.section-title { font-weight:800; color:#1e40af; margin-top:0; margin-bottom:15px; border-bottom:2px solid #1e40af; padding-bottom:5px; text-align:left; }
.table { border-radius:10px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.05); }
.table thead th { background-color:#071239ff; color:white; font-weight:600; vertical-align: middle; }
.table tbody tr:hover { background-color:#e9ecef; }
.date-range-group { background:#f8f9fa; padding:15px; border-radius:10px; border:2px solid #ced4da; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1); display:flex; flex-direction:column; gap:10px;}
.date-range-group input[type="date"] { border-radius:6px; padding:10px 15px; font-size:16px; height:auto; text-align:center;}
.date-range-group .input-group-text { background:#071239ff; color:white; border-color:#071239ff; font-weight:600; padding:10px 15px; border-radius:0;}
</style>
</head>
<body>

<nav>
    <div class="nav-logo d-flex align-items-center gap-3">
        <img src="../assets/img/ukm.png" alt="UKM Logo" height="45">
        <img src="../assets/img/pusatsukan.png" alt="Pusat Sukan Logo" height="45">
    </div>
    <div class="d-flex align-items-center gap-4">
        <a class="nav-link" href="addfacilities.php">Facility</a>
        <a class="nav-link" href="#">Booking</a>
        <a class="nav-link" href="#">Report</a>
        <div class="d-flex align-items-center gap-1">
            <img src="../assets/img/user.png" class="rounded-circle" style="width:45px; height:45px;">
            <span class="fw-bold" style="color:#071239ff;"><?php echo htmlspecialchars($_SESSION['user_id'] ?? 'User'); ?></span>
        </div>
    </div>
</nav>

<div class="container">
<div class="main-box position-relative">
<h1>FACILITY CLOSURES MANAGEMENT</h1>
<p class="text-center text-secondary fw-bold mb-4">Schedule specific date blocks for maintenance or events (Overrides the weekly schedule).</p>

<div class="closure-card mb-5">
<h5 class="section-title">Schedule New Closure Period (Date Range Selection)</h5>

<?php if(empty($facilities)): ?>
    <p class="text-danger">No facilities found. Please add facilities first.</p>
<?php else: ?>
<form method="POST">
    <input type="hidden" name="add_override" value="1">
    <div class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Select Facility</label>
            <select class="form-select" name="FacilityID" required>
                <option value="" hidden>Choose Facility...</option>
                <?php foreach ($facilities as $fac): ?>
                    <option value="<?php echo $fac['FacilityID']; ?>"><?php echo htmlspecialchars($fac['Name']) . " (" . $fac['FacilityID'] . ")"; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-5">
            <label class="form-label">Closure Date Range</label>
            <div class="date-range-group">
                <div class="input-group">
                    <input type="date" class="form-control" name="ClosureStartDate" required title="Closure Start Date">
                    <span class="input-group-text">TO</span>
                    <input type="date" class="form-control" name="ClosureEndDate" required title="Closure End Date">
                </div>
                <small class="text-muted d-block" style="font-size: 11px;">Use the calendar icon on the inputs to select the exact start and end dates.</small>
            </div>
        </div>

        <div class="col-md-3">
            <label class="form-label">Reason for Closure</label>
            <input type="text" class="form-control" name="Reason" placeholder="e.g., Annual Maintenance" required>
        </div>

        <div class="col-md-1 d-grid">
            <button type="submit" class="btn btn-submit h-100">Schedule</button>
        </div>
    </div>
</form>
<?php endif; ?>
</div>

<h5 class="section-title">List of Scheduled Overrides</h5>
<div class="table-responsive">
<table class="table table-striped mt-3 align-middle">
<thead>
<tr>
<th>Facility Name</th>
<th>ID</th>
<th>Closure Begins</th>
<th>Closure Ends</th>
<th>Duration</th>
<th>Reason</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php if(empty($overrides)): ?>
<tr><td colspan="7" class="text-center text-muted">No specific date closures are currently scheduled.</td></tr>
<?php else: ?>
<?php foreach($overrides as $override): 
    $start_date = new DateTime(substr($override['StartTime'], 0, 10));
    $end_date = new DateTime(substr($override['EndTime'], 0, 10));
    $interval = $start_date->diff($end_date);
    $durationDays = $interval->days + 1;
?>
<tr>
<td><?php echo htmlspecialchars($override['Name']); ?></td>
<td><?php echo htmlspecialchars($override['FacilityID']); ?></td>
<td><?php echo htmlspecialchars($start_date->format('Y-m-d')); ?></td>
<td><?php echo htmlspecialchars($end_date->format('Y-m-d')); ?></td>
<td><?php echo $durationDays . " days"; ?></td>
<td><?php echo htmlspecialchars($override['Reason']); ?></td>
<td>
<a href="?delete_id=<?php echo $override['OverrideID']; ?>" onclick="return confirm('Confirm permanent deletion of this schedule override?')" class="btn btn-danger btn-sm">Delete</a>
</td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>

</div>
</div>
</body>
</html>
