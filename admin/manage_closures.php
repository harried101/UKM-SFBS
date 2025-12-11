<?php
session_start();
require_once '../includes/db_connect.php';

// Enable errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Admin access only
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

// --- Fetch Facilities ---
$facilities = [];
$facility_result = $conn->query("SELECT FacilityID, Name FROM facilities ORDER BY FacilityID");
while ($row = $facility_result->fetch_assoc()) {
    $facilities[] = $row;
}

// --- Add Closure ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_override'])) {
    $facilityId = trim($_POST['FacilityID'] ?? '');
    $startDate = trim($_POST['ClosureStartDate'] ?? '');
    $endDate = trim($_POST['ClosureEndDate'] ?? '');
    $reason = trim($_POST['Reason'] ?? '');

    if (!$facilityId || !$startDate || !$endDate || !$reason) {
        die("<script>alert('All fields are required!'); window.location='manage_closures.php';</script>");
    }

    if (strtotime($startDate) > strtotime($endDate)) {
        die("<script>alert('End Date cannot be before Start Date!'); window.location='manage_closures.php';</script>");
    }

    $startTime = $startDate . " 00:00:00";
    $endTime = $endDate . " 23:59:59";

    $stmt = $conn->prepare("INSERT INTO scheduleoverrides (FacilityID, StartTime, EndTime, Reason) VALUES (?, ?, ?, ?)");
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param("ssss", $facilityId, $startTime, $endTime, $reason);

    if ($stmt->execute()) {
        echo "<script>alert('Closure added successfully!'); window.location='manage_closures.php';</script>";
    } else {
        die("<script>alert('Insert failed: " . $stmt->error . "'); window.location='manage_closures.php';</script>");
    }
    $stmt->close();
    exit();
}

// --- Delete Closure ---
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM scheduleoverrides WHERE OverrideID = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        echo "<script>alert('Closure deleted successfully'); window.location='manage_closures.php';</script>";
    } else {
        die("<script>alert('Delete failed: " . $stmt->error . "'); window.location='manage_closures.php';</script>");
    }
    $stmt->close();
    exit();
}

// --- Fetch Overrides ---
$overrides = [];
$sql = "SELECT s.OverrideID, s.FacilityID, s.StartTime, s.EndTime, s.Reason, f.Name 
        FROM scheduleoverrides s 
        JOIN facilities f ON s.FacilityID = f.FacilityID 
        ORDER BY s.StartTime DESC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $overrides[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Facility Closures</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: url('../assets/img/background.jpg') no-repeat center center fixed; background-size: cover; font-family: 'Poppins', sans-serif; }
        nav { background: #bfd9dc; padding: 10px 40px; border-radius: 0 0 25px 25px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        .nav-link { color: #071239; font-weight: 600; padding: 8px 18px; border-radius: 12px; transition: 0.3s; }
        .nav-link:hover { background: rgba(255,255,255,0.5); }
        .main-box { background: #bfd9dc; border-radius: 25px; padding: 30px 40px; max-width: 1200px; margin: 40px auto; box-shadow: 0 0 20px rgba(0,0,0,0.25); }
        h1 { text-align: center; font-weight: 900; margin-bottom: 20px; color: #071239; font-size: 36px; }
        .form-label { font-weight: 600; color: #071239; }
        .btn-submit { background: #1e40af; color: white; border-radius: 8px; font-weight: 700; padding: 8px 25px; }
        .closure-card { background: #fff; border-radius: 15px; padding: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .section-title { font-weight: 800; color: #1e40af; border-bottom: 2px solid #1e40af; padding-bottom: 5px; margin-bottom: 15px; }
        .table thead th { background: #071239; color: #fff; vertical-align: middle; }
        .table tbody tr:hover { background-color: #e9ecef; }
    </style>
</head>
<body>

<nav>
    <div class="d-flex gap-3 align-items-center">
        <img src="../assets/img/ukm.png" height="45">
        <img src="../assets/img/pusatsukan.png" height="45">
    </div>
    <div class="d-flex gap-4 align-items-center">
        <a class="nav-link" href="addfacilities.php">Facility</a>
        <a class="nav-link" href="#">Booking</a>
        <a class="nav-link" href="#">Report</a>
        <div class="d-flex align-items-center gap-2">
            <img src="../assets/img/user.png" class="rounded-circle" width="45" height="45">
            <span class="fw-bold" style="color:#071239;"><?= htmlspecialchars($_SESSION['user_id'] ?? 'User'); ?></span>
        </div>
    </div>
</nav>

<div class="container">
    <div class="main-box">
        <h1>FACILITY CLOSURES MANAGEMENT</h1>
        <p class="text-center text-secondary fw-bold mb-4">Schedule specific date blocks for maintenance or events (Overrides the weekly schedule).</p>

        <div class="closure-card">
            <h5 class="section-title">Schedule New Closure Period</h5>
            <form method="POST">
                <input type="hidden" name="add_override" value="1">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Select Facility</label>
                        <select class="form-select" name="FacilityID" required>
                            <option value="" disabled selected>Choose Facility...</option>
                            <?php foreach ($facilities as $f): ?>
                                <option value="<?= $f['FacilityID'] ?>"><?= htmlspecialchars($f['Name']) ?> (<?= $f['FacilityID'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="ClosureStartDate" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-control" name="ClosureEndDate" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Reason</label>
                        <input type="text" class="form-control" name="Reason" placeholder="e.g., Maintenance" required>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-submit mt-4">Schedule</button>
                    </div>
                </div>
            </form>
        </div>

        <h5 class="section-title">List of Scheduled Overrides</h5>
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
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
                    <?php if (empty($overrides)): ?>
                        <tr><td colspan="7" class="text-center text-muted">No closures scheduled.</td></tr>
                    <?php else: ?>
                        <?php foreach ($overrides as $o): 
                            $start = new DateTime($o['StartTime']);
                            $end = new DateTime($o['EndTime']);
                            $days = $start->diff($end)->days + 1;
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($o['Name']); ?></td>
                                <td><?= htmlspecialchars($o['FacilityID']); ?></td>
                                <td><?= $start->format('Y-m-d'); ?></td>
                                <td><?= $end->format('Y-m-d'); ?></td>
                                <td><?= $days ?> day(s)</td>
                                <td><?= htmlspecialchars($o['Reason']); ?></td>
                                <td>
                                    <a href="?delete_id=<?= $o['OverrideID']; ?>" onclick="return confirm('Delete this closure?')" class="btn btn-danger btn-sm">Delete</a>
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
