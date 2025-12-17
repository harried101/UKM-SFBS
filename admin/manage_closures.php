<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'Admin') { header("Location: ../index.php"); exit(); }
require_once '../includes/db_connect.php';

$facilities = $conn->query("SELECT FacilityID, Name FROM facilities");
$closures = $conn->query("SELECT o.*, f.Name FROM scheduleoverrides o JOIN facilities f ON o.FacilityID=f.FacilityID ORDER BY StartTime DESC");

// ADD CLOSURE
if(isset($_POST['add_closure'])) {
    $fid = $_POST['FacilityID'];
    $start = $_POST['start_date'] . ' 00:00:00';
    $end = $_POST['end_date'] . ' 23:59:59';
    $reason = $_POST['reason'];
    
    $stmt = $conn->prepare("INSERT INTO scheduleoverrides (FacilityID, StartTime, EndTime, Reason) VALUES (?,?,?,?)");
    $stmt->bind_param("isss", $fid, $start, $end, $reason);
    $stmt->execute();
    header("Location: manage_closures.php");
}

// DELETE
if(isset($_GET['del'])) {
    $conn->query("DELETE FROM scheduleoverrides WHERE OverrideID=".intval($_GET['del']));
    header("Location: manage_closures.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Closures</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<style>
    :root { --primary: #0b4d9d; }
    body { font-family: 'Inter', sans-serif; background: #f8f9fa; }
    .navbar-custom { background: white; border-bottom: 1px solid #eee; }
    .nav-link.active { color: var(--primary) !important; font-weight: 700; }
    .main-box { background: white; padding: 30px; border-radius: 12px; margin-top: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
    .btn-primary-custom { background: var(--primary); color: white; border: none; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="#">Admin Portal</a>
        <div class="d-flex gap-3">
            <a class="nav-link" href="addfacilities.php">Facilities</a>
            <a class="nav-link" href="manage_bookings.php">Bookings</a>
            <a class="nav-link active" href="manage_closures.php">Closures</a>
            <a class="nav-link text-danger" href="../logout.php">Logout</a>
        </div>
    </div>
</nav>

<div class="container main-box">
    <h3 class="fw-bold mb-4" style="color:var(--primary)">Facility Closures</h3>
    
    <form method="POST" class="row g-3 mb-5 p-4 bg-light rounded border">
        <input type="hidden" name="add_closure" value="1">
        <div class="col-md-3">
            <label class="form-label">Facility</label>
            <select name="FacilityID" class="form-select" required>
                <?php while($f = $facilities->fetch_assoc()): ?>
                    <option value="<?= $f['FacilityID'] ?>"><?= $f['Name'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Start Date</label>
            <input type="date" name="start_date" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">End Date</label>
            <input type="date" name="end_date" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Reason</label>
            <input type="text" name="reason" class="form-control" placeholder="e.g. Maintenance" required>
        </div>
        <div class="col-12 text-end">
            <button class="btn btn-primary-custom px-4">Add Closure</button>
        </div>
    </form>

    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>Facility</th>
                <th>Dates</th>
                <th>Reason</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while($c = $closures->fetch_assoc()): ?>
            <tr>
                <td><?= $c['Name'] ?></td>
                <td><?= substr($c['StartTime'],0,10) ?> to <?= substr($c['EndTime'],0,10) ?></td>
                <td><?= $c['Reason'] ?></td>
                <td><a href="?del=<?= $c['OverrideID'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Remove</a></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>