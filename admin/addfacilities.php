<?php
session_start();

// Auth Check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/db_connect.php';

// Fetch Admin Name
$adminName = 'Admin';
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT FirstName FROM users WHERE UserIdentifier = ?");
    $stmt->bind_param("s", $_SESSION['user_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($r = $res->fetch_assoc()) $adminName = $r['FirstName'];
}

// Map UI Strings -> DB Integers
$dayMap = [
    'Sunday' => 0, 'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3,
    'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6
];
$dayMapReverse = array_flip($dayMap);

$facilityData = null;
$isUpdate = false;
$currentID = $_GET['id'] ?? '';
$formTitle = "Add New Facility";
$existingSchedules = [];

// --- LOAD EXISTING DATA ---
if ($currentID) {
    // 1. Facility Details
    $stmt = $conn->prepare("SELECT * FROM facilities WHERE FacilityID = ?");
    $stmt->bind_param("i", $currentID); // Integer!
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        $facilityData = $res->fetch_assoc();
        $isUpdate = true;
        $formTitle = "Edit Facility #" . $currentID;
    }
    $stmt->close();

    // 2. Schedules
    $stmt = $conn->prepare("SELECT DayOfWeek, OpenTime, CloseTime, SlotDuration FROM facilityschedules WHERE FacilityID = ?");
    $stmt->bind_param("i", $currentID);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $dayInt = $row['DayOfWeek'];
        if (isset($dayMapReverse[$dayInt])) {
            $existingSchedules[$dayMapReverse[$dayInt]] = $row;
        }
    }
    $stmt->close();
}

// --- PROCESS FORM ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Search Redirect
    if (isset($_POST['search_id'])) {
        $sid = intval($_POST['search_id']);
        if($sid > 0) header("Location: addfacilities.php?id=$sid");
        exit();
    }

    // Save Data
    if (isset($_POST['save_facility'])) {
        $name = $_POST['Name'];
        $desc = $_POST['Description'];
        $loc = $_POST['Location'];
        $type = $_POST['Type'];
        $status = $_POST['Status'];
        $id = $_POST['FacilityIDHidden'] ?? null;

        // Photo Upload
        $photoName = $facilityData['PhotoURL'] ?? NULL;
        if (!empty($_FILES['PhotoURL']['name'])) {
            $ext = pathinfo($_FILES['PhotoURL']['name'], PATHINFO_EXTENSION);
            $newName = time() . "_" . uniqid() . "." . $ext;
            move_uploaded_file($_FILES['PhotoURL']['tmp_name'], "../admin/uploads/" . $newName);
            $photoName = $newName;
        }

        if ($isUpdate && $id) {
            $sql = "UPDATE facilities SET Name=?, Description=?, Location=?, Type=?, PhotoURL=?, Status=? WHERE FacilityID=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssi", $name, $desc, $loc, $type, $photoName, $status, $id);
        } else {
            // INSERT (No ID, Auto Increment)
            $sql = "INSERT INTO facilities (Name, Description, Location, Type, PhotoURL, Status) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $name, $desc, $loc, $type, $photoName, $status);
        }

        if ($stmt->execute()) {
            $targetID = $isUpdate ? $id : $stmt->insert_id;
            
            // Update Schedules (Delete Old -> Insert New)
            $conn->query("DELETE FROM facilityschedules WHERE FacilityID = $targetID");
            
            $insSched = $conn->prepare("INSERT INTO facilityschedules (FacilityID, DayOfWeek, OpenTime, CloseTime, SlotDuration) VALUES (?, ?, ?, ?, ?)");
            
            if (!empty($_POST['available_days'])) {
                foreach ($_POST['available_days'] as $dayName) {
                    $dayInt = $dayMap[$dayName];
                    $start = $_POST['start_time'][$dayName];
                    $end = $_POST['end_time'][$dayName];
                    $dur = $_POST['slot_duration'][$dayName];
                    
                    $insSched->bind_param("iissi", $targetID, $dayInt, $start, $end, $dur);
                    $insSched->execute();
                }
            }
            echo "<script>alert('Saved Successfully!'); window.location='addfacilities.php?id=$targetID';</script>";
        } else {
            echo "<script>alert('Error: ".$stmt->error."');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Facilities</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #0b4d9d; --bg-light: #f8f9fa; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-light); color: #333; }
        h1, h2, h3 { font-family: 'Playfair Display', serif; }
        
        .navbar-custom { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .nav-link { color: #555; font-weight: 500; }
        .nav-link.active { color: var(--primary) !important; font-weight: 700; }
        
        .main-card { background: white; border-radius: 16px; padding: 40px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); max-width: 1200px; margin: 30px auto; }
        
        .form-control, .form-select { border-radius: 8px; padding: 10px; border-color: #dee2e6; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 0.2rem rgba(11, 77, 157, 0.15); }
        
        .section-title { color: var(--primary); font-weight: 700; border-bottom: 2px solid #f1f1f1; padding-bottom: 10px; margin-bottom: 20px; }
        .btn-primary-custom { background: var(--primary); color: white; border: none; padding: 10px 30px; border-radius: 8px; font-weight: 600; }
        .btn-primary-custom:hover { background: #083a75; }
        
        .schedule-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 15px; margin-bottom: 10px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="#">
            <img src="../assets/img/ukm.png" height="40">
            <span class="fw-bold" style="color: var(--primary)">Admin Portal</span>
        </a>
        <div class="collapse navbar-collapse justify-content-end">
            <ul class="navbar-nav align-items-center gap-3">
                <li class="nav-item"><a class="nav-link active" href="addfacilities.php">Facilities</a></li>
                <li class="nav-item"><a class="nav-link" href="bookinglist.php">Bookings</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_closures.php">Closures</a></li>
                <li class="nav-item">
                    <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($adminName); ?></span>
                </li>
                <li class="nav-item"><a href="../logout.php" class="btn btn-sm btn-outline-danger">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container">
    <div class="main-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><?php echo $formTitle; ?></h2>
            <?php if($isUpdate): ?>
                <a href="addfacilities.php" class="btn btn-outline-secondary btn-sm">+ Add New</a>
            <?php endif; ?>
        </div>

        <!-- Search -->
        <form method="POST" class="mb-5 bg-light p-3 rounded border">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">Edit ID:</span>
                <input type="number" name="search_id" class="form-control border-start-0" placeholder="e.g. 12" required>
                <button class="btn btn-dark" type="submit">Load Facility</button>
            </div>
        </form>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="save_facility" value="1">
            <?php if($isUpdate): ?><input type="hidden" name="FacilityIDHidden" value="<?php echo $facilityData['FacilityID']; ?>"><?php endif; ?>

            <div class="row g-5">
                <!-- Left: Details -->
                <div class="col-md-6">
                    <h5 class="section-title">Facility Details</h5>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Name</label>
                        <input type="text" name="Name" class="form-control" value="<?php echo htmlspecialchars($facilityData['Name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="Description" class="form-control" rows="3"><?php echo htmlspecialchars($facilityData['Description'] ?? ''); ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Location</label>
                            <input type="text" name="Location" class="form-control" value="<?php echo htmlspecialchars($facilityData['Location'] ?? ''); ?>">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">Type</label>
                            <input type="text" name="Type" class="form-control" value="<?php echo htmlspecialchars($facilityData['Type'] ?? ''); ?>" placeholder="e.g. Court">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <select name="Status" class="form-select">
                            <?php 
                            $st = $facilityData['Status'] ?? 'Active';
                            foreach(['Active','Maintenance','Archived'] as $opt) echo "<option value='$opt' ".($st==$opt?'selected':'').">$opt</option>";
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Image</label>
                        <input type="file" name="PhotoURL" class="form-control">
                        <?php if(!empty($facilityData['PhotoURL'])): ?>
                            <img src="../admin/uploads/<?php echo $facilityData['PhotoURL']; ?>" class="mt-2 rounded" style="height:100px; object-fit:cover;">
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right: Schedule -->
                <div class="col-md-6">
                    <h5 class="section-title">Weekly Schedule</h5>
                    <p class="text-muted small">Check days to open facility.</p>

                    <?php 
                    $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
                    foreach($days as $day):
                        $dData = $existingSchedules[$day] ?? ['OpenTime'=>'08:00', 'CloseTime'=>'22:00', 'SlotDuration'=>60];
                        $checked = isset($existingSchedules[$day]) ? 'checked' : '';
                        $display = isset($existingSchedules[$day]) ? 'flex' : 'none';
                    ?>
                    <div class="schedule-box">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input sched-toggle" type="checkbox" name="available_days[]" value="<?php echo $day; ?>" id="chk_<?php echo $day; ?>" <?php echo $checked; ?>>
                            <label class="form-check-label fw-bold" for="chk_<?php echo $day; ?>"><?php echo $day; ?></label>
                        </div>
                        <div class="row g-2 inputs-<?php echo $day; ?>" style="display:<?php echo $display; ?>">
                            <div class="col-4"><small>Open</small><input type="time" class="form-control form-control-sm" name="start_time[<?php echo $day; ?>]" value="<?php echo substr($dData['OpenTime'],0,5); ?>"></div>
                            <div class="col-4"><small>Close</small><input type="time" class="form-control form-control-sm" name="end_time[<?php echo $day; ?>]" value="<?php echo substr($dData['CloseTime'],0,5); ?>"></div>
                            <div class="col-4"><small>Slot(m)</small><input type="number" class="form-control form-control-sm" name="slot_duration[<?php echo $day; ?>]" value="<?php echo $dData['SlotDuration']; ?>" step="5"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="text-center mt-5">
                <button type="submit" class="btn btn-primary-custom btn-lg shadow w-100">
                    <?php echo $isUpdate ? 'Save Changes' : 'Create Facility'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.sched-toggle').forEach(t => {
    t.addEventListener('change', function() {
        const row = document.querySelector(`.inputs-${this.value}`);
        row.style.display = this.checked ? 'flex' : 'none';
        row.querySelectorAll('input').forEach(i => i.disabled = !this.checked);
    });
    // Init logic
    const row = document.querySelector(`.inputs-${t.value}`);
    row.querySelectorAll('input').forEach(i => i.disabled = !t.checked);
});
</script>
</body>
</html>