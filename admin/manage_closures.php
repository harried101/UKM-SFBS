<?php
session_start();

// Admin session check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/db_connect.php';

/* ---------------------- FETCH FACILITIES ---------------------- */
$facilities = [];
$facilityResult = $conn->query("SELECT FacilityID, Name FROM facilities ORDER BY FacilityID");
while ($row = $facilityResult->fetch_assoc()) {
    $facilities[] = $row;
}

/* ---------------------- FETCH EXISTING CLOSURES ---------------------- */
$closures = [];
$query = "
    SELECT o.OverrideID, o.FacilityID, o.StartTime, o.EndTime, o.Reason, f.Name 
    FROM scheduleoverrides o
    JOIN facilities f ON o.FacilityID = f.FacilityID
    ORDER BY o.StartTime DESC
";
$res = $conn->query($query);
while ($row = $res->fetch_assoc()) {
    $closures[] = $row;
}

/* ---------------------- ADD NEW CLOSURE ---------------------- */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_override"])) {

    $FacilityID = $_POST["FacilityID"];
    $StartDate = $_POST["ClosureStartDate"];
    $EndDate = $_POST["ClosureEndDate"];
    $Reason = $_POST["Reason"];

    if (!$FacilityID || !$StartDate || !$EndDate || !$Reason) {
        $_SESSION["alert"] = ["type" => "danger", "msg" => "All fields are required."];
        header("Location: manage_closures.php");
        exit();
    }

    if (strtotime($StartDate) > strtotime($EndDate)) {
        $_SESSION["alert"] = ["type" => "danger", "msg" => "End date must be after start date."];
        header("Location: manage_closures.php");
        exit();
    }

    $StartTime = $StartDate . " 00:00:00";
    $EndTime = $EndDate . " 23:59:59";

    $stmt = $conn->prepare("INSERT INTO scheduleoverrides (FacilityID, StartTime, EndTime, Reason) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $FacilityID, $StartTime, $EndTime, $Reason);

    if ($stmt->execute()) {
        $_SESSION["alert"] = ["type" => "success", "msg" => "Closure scheduled successfully."];
    } else {
        $_SESSION["alert"] = ["type" => "danger", "msg" => "Database Error: " . $stmt->error];
    }

    header("Location: manage_closures.php");
    exit();
}

/* ---------------------- DELETE CLOSURE ---------------------- */
if (isset($_GET["delete_id"])) {
    $id = $_GET["delete_id"];

    $stmt = $conn->prepare("DELETE FROM scheduleoverrides WHERE OverrideID=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION["alert"] = ["type" => "success", "msg" => "Closure deleted successfully."];
    } else {
        $_SESSION["alert"] = ["type" => "danger", "msg" => "Error deleting closure."];
    }

    header("Location: manage_closures.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Facility Closures</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #eef2f7;
            font-family: "Poppins", sans-serif;
        }
        .main-box {
            background: #fff;
            border-radius: 18px;
            padding: 30px;
            margin: 40px auto;
            max-width: 1100px;
            box-shadow: 0 6px 25px rgba(0,0,0,0.1);
        }
        .title {
            font-weight: 800;
            color: #1e3a8a;
        }
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e3a8a;
            border-left: 4px solid #1e3a8a;
            padding-left: 10px;
        }
        .btn-primary {
            background: #1e3a8a;
            border: none;
        }
        .btn-primary:hover {
            background: #162f6b;
        }
        .table thead {
            background: #1e3a8a;
            color: #fff;
        }
        .card-box {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #dce5ee;
        }
    </style>
</head>

<body>

<div class="main-box">

    <h1 class="title mb-2">Facility Closure Management</h1>
    <p class="text-muted">Manage temporary shutdowns for maintenance or special events.</p>

    <!-- ALERT SYSTEM -->
    <?php if (isset($_SESSION["alert"])): ?>
        <div class="alert alert-<?= $_SESSION["alert"]["type"]; ?> alert-dismissible fade show d-flex justify-content-between align-items-center">
            <div><?= $_SESSION["alert"]["msg"]; ?></div>
            <div>
                <button class="btn-close" data-bs-dismiss="alert"></button>
                <?php if ($_SESSION["alert"]["type"] === "success"): ?>
                    <a href="addfacilities.php" class="btn btn-sm btn-outline-primary ms-2">Back to Add Facility</a>
                <?php endif; ?>
            </div>
        </div>
        <?php unset($_SESSION["alert"]); ?>
    <?php endif; ?>

    <!-- ADD CLOSURE FORM -->
    <div class="card-box mb-4">
        <h4 class="section-title mb-3">Schedule New Closure</h4>

        <form method="POST" action="manage_closures.php">
            <input type="hidden" name="add_override" value="1">

            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Facility</label>
                    <select class="form-select" name="FacilityID" required>
                        <option value="" disabled selected>Select facility...</option>
                        <?php foreach ($facilities as $f): ?>
                            <option value="<?= $f['FacilityID']; ?>">
                                <?= htmlspecialchars($f['Name']); ?> (<?= $f['FacilityID']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Start Date</label>
                    <input type="date" name="ClosureStartDate" class="form-control" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">End Date</label>
                    <input type="date" name="ClosureEndDate" class="form-control" required>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Reason</label>
                    <input type="text" name="Reason" class="form-control" placeholder="e.g., Maintenance Work" required>
                </div>

                <div class="col-12 text-end">
                    <button class="btn btn-primary px-4 mt-2">Add Closure</button>
                </div>

            </div>
        </form>
    </div>

    <!-- CLOSURE LIST -->
    <h4 class="section-title">Scheduled Closures</h4>
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle mt-3">

            <thead>
                <tr>
                    <th>Facility</th>
                    <th>ID</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Duration</th>
                    <th>Reason</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($closures)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">No closures scheduled.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($closures as $c):
                    $s = new DateTime($c['StartTime']);
                    $e = new DateTime($c['EndTime']);
                    $days = $s->diff($e)->days + 1;
                ?>
                    <tr>
                        <td><?= htmlspecialchars($c['Name']); ?></td>
                        <td><?= $c['FacilityID']; ?></td>
                        <td><?= $s->format("Y-m-d"); ?></td>
                        <td><?= $e->format("Y-m-d"); ?></td>
                        <td><?= $days; ?> days</td>
                        <td><?= htmlspecialchars($c['Reason']); ?></td>
                        <td>
                            <a href="?delete_id=<?= $c['OverrideID']; ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Delete this closure?');">
                               Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>

            </tbody>

        </table>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
