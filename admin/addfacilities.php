<?php
session_start();

// 1. Auth Check
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

// Map Logic
$dayMap = ['Sunday'=>0, 'Monday'=>1, 'Tuesday'=>2, 'Wednesday'=>3, 'Thursday'=>4, 'Friday'=>5, 'Saturday'=>6];
$dayMapRev = array_flip($dayMap);

// Vars
$facilityData = null;
$isUpdate = false;
$currentID = $_GET['id'] ?? '';
$formTitle = "Add New Facility";
$existingSchedules = [];
$existingClosures = [];
$activeTab = $_GET['tab'] ?? 'details'; // 'details' or 'closures'

// --- 2. LOAD DATA ---
if ($currentID) {
    // Facility Info
    $stmt = $conn->prepare("SELECT * FROM facilities WHERE FacilityID = ?");
    $stmt->bind_param("i", $currentID);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $facilityData = $res->fetch_assoc();
        $isUpdate = true;
        $formTitle = "Edit Facility: " . htmlspecialchars($facilityData['Name']);
        
        // Load Schedules
        $stmtS = $conn->prepare("SELECT DayOfWeek, OpenTime, CloseTime, SlotDuration FROM facilityschedules WHERE FacilityID = ?");
        $stmtS->bind_param("i", $currentID);
        $stmtS->execute();
        $resS = $stmtS->get_result();
        while ($row = $resS->fetch_assoc()) {
            if (isset($dayMapRev[$row['DayOfWeek']])) {
                $existingSchedules[$dayMapRev[$row['DayOfWeek']]] = $row;
            }
        }
        $stmtS->close();

        // Load Closures
        $stmtC = $conn->prepare("SELECT * FROM scheduleoverrides WHERE FacilityID = ? ORDER BY StartTime DESC");
        $stmtC->bind_param("i", $currentID);
        $stmtC->execute();
        $resC = $stmtC->get_result();
        while ($row = $resC->fetch_assoc()) {
            $existingClosures[] = $row;
        }
        $stmtC->close();

    } else {
        // ID invalid
        header("Location: addfacilities.php");
        exit();
    }
    $stmt->close();
}

// --- 3. PROCESS ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // SEARCH
    if (isset($_POST['search_id'])) {
        $sid = intval($_POST['search_id']);
        if ($sid > 0) header("Location: addfacilities.php?id=$sid");
        exit();
    }

    // SAVE FACILITY DETAILS & SCHEDULE
    if (isset($_POST['save_facility'])) {
        $name = $_POST['Name'];
        $desc = $_POST['Description'];
        $loc = $_POST['Location'];
        $type = $_POST['Type'];
        $status = $_POST['Status'];
        $fid = $_POST['FacilityIDHidden'] ?? null;

        // Photo
        $photoName = $facilityData['PhotoURL'] ?? NULL;
        if (!empty($_FILES['PhotoURL']['name'])) {
            $ext = pathinfo($_FILES['PhotoURL']['name'], PATHINFO_EXTENSION);
            $newName = time() . "_" . uniqid() . "." . $ext;
            if(!is_dir("../admin/uploads/")) mkdir("../admin/uploads/", 0777, true);
            move_uploaded_file($_FILES['PhotoURL']['tmp_name'], "../admin/uploads/" . $newName);
            $photoName = $newName;
        }

        if ($isUpdate && $fid) {
            $sql = "UPDATE facilities SET Name=?, Description=?, Location=?, Type=?, PhotoURL=?, Status=? WHERE FacilityID=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssi", $name, $desc, $loc, $type, $photoName, $status, $fid);
        } else {
            $sql = "INSERT INTO facilities (Name, Description, Location, Type, PhotoURL, Status) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $name, $desc, $loc, $type, $photoName, $status);
        }

        if ($stmt->execute()) {
            $targetID = $isUpdate ? $fid : $stmt->insert_id;
            
            // Save Schedule
            $conn->query("DELETE FROM facilityschedules WHERE FacilityID = $targetID");
            $ins = $conn->prepare("INSERT INTO facilityschedules (FacilityID, DayOfWeek, OpenTime, CloseTime, SlotDuration) VALUES (?, ?, ?, ?, ?)");
            
            if (!empty($_POST['available_days'])) {
                foreach ($_POST['available_days'] as $dName) {
                    $dInt = $dayMap[$dName];
                    $s = $_POST['start_time'][$dName];
                    $e = $_POST['end_time'][$dName];
                    $dur = $_POST['slot_duration'][$dName];
                    $ins->bind_param("iissi", $targetID, $dInt, $s, $e, $dur);
                    $ins->execute();
                }
            }
            echo "<script>alert('Facility details saved successfully!'); window.location='addfacilities.php?id=$targetID';</script>";
        } else {
            echo "<script>alert('Error saving: " . $stmt->error . "');</script>";
        }
    }

    // ADD CLOSURE
    if (isset($_POST['add_closure'])) {
        $fid = $_POST['facility_id'];
        $start = $_POST['start_date'] . ' 00:00:00';
        $end = $_POST['end_date'] . ' 23:59:59';
        $reason = $_POST['reason'];

        $stmt = $conn->prepare("INSERT INTO scheduleoverrides (FacilityID, StartTime, EndTime, Reason) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $fid, $start, $end, $reason);
        if ($stmt->execute()) {
            echo "<script>alert('Closure added.'); window.location='addfacilities.php?id=$fid&tab=closures';</script>";
        } else {
            echo "<script>alert('Error adding closure.');</script>";
        }
    }
}

// DELETE CLOSURE (GET)
if (isset($_GET['del_closure'])) {
    $oid = intval($_GET['del_closure']);
    $fid = intval($_GET['id']);
    $conn->query("DELETE FROM scheduleoverrides WHERE OverrideID = $oid");
    header("Location: addfacilities.php?id=$fid&tab=closures");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Facility</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #0b4d9d; }
        body { background: #f3f4f6; font-family: 'Inter', sans-serif; color: #333; }
        
        .navbar-custom { background: white; border-bottom: 1px solid #e5e7eb; padding: 0.8rem 1rem; }
        .nav-link.active { color: var(--primary) !important; font-weight: 700; }
        
        .main-container { max-width: 1100px; margin: 30px auto; }
        .card-custom { background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); overflow: hidden; }
        
        .nav-tabs .nav-link { color: #64748b; font-weight: 600; border: none; padding: 1rem 1.5rem; }
        .nav-tabs .nav-link.active { color: var(--primary); border-bottom: 3px solid var(--primary); background: transparent; }
        .nav-tabs { border-bottom: 1px solid #e5e7eb; padding-left: 1rem; }
        
        .form-label { font-weight: 600; font-size: 0.9rem; color: #374151; }
        .form-control, .form-select { border-radius: 8px; border-color: #d1d5db; padding: 0.6rem; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(11, 77, 157, 0.1); }
        
        .btn-primary-custom { background: var(--primary); color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; }
        .btn-primary-custom:hover { background: #093b7a; color: white; }
        
        .schedule-row { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; margin-bottom: 8px; }
        .img-preview { width: 100%; height: 180px; object-fit: cover; border-radius: 8px; margin-top: 10px; border: 1px solid #e5e7eb; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="#">
            <img src="../assets/img/ukm.png" height="35">
            <span class="fw-bold text-primary">Admin Portal</span>
        </a>
        <div class="d-flex gap-3 align-items-center">
            <a class="nav-link active" href="addfacilities.php">Facilities</a>
            <a class="nav-link" href="manage_bookings.php">Bookings</a>
            <a class="nav-link" href="book_walkin.php">Walk-in</a>
            <div class="vr mx-2 text-muted"></div>
            <span class="fw-bold small text-secondary"><?= htmlspecialchars($adminName) ?></span>
            <a href="../logout.php" class="btn btn-sm btn-outline-danger ms-2"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
    </div>
</nav>

<div class="container main-container">
    
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1"><?= $isUpdate ? 'Edit Facility' : 'Create Facility' ?></h2>
            <p class="text-muted small mb-0"><?= $isUpdate ? 'Manage details, schedule, and closures.' : 'Add a new sports facility to the system.' ?></p>
        </div>
        <?php if($isUpdate): ?>
            <a href="addfacilities.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-plus me-1"></i> Add New</a>
        <?php endif; ?>
    </div>

    <!-- Search Bar -->
    <div class="card-custom p-3 mb-4">
        <form method="POST" class="row g-2 align-items-center">
            <div class="col-auto fw-bold text-secondary">Find ID:</div>
            <div class="col-auto"><input type="number" name="search_id" class="form-control form-control-sm" placeholder="e.g. 12" style="width: 100px;"></div>
            <div class="col-auto"><button type="submit" class="btn btn-dark btn-sm">Go</button></div>
        </form>
    </div>

    <!-- Main Card -->
    <div class="card-custom">
        
        <!-- Tabs -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item">
                <button class="nav-link <?= $activeTab === 'details' ? 'active' : '' ?>" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button">Details & Schedule</button>
            </li>
            <?php if ($isUpdate): ?>
            <li class="nav-item">
                <button class="nav-link <?= $activeTab === 'closures' ? 'active' : '' ?>" id="closures-tab" data-bs-toggle="tab" data-bs-target="#closures" type="button">Closures <span class="badge bg-secondary rounded-pill ms-1"><?= count($existingClosures) ?></span></button>
            </li>
            <?php endif; ?>
        </ul>

        <div class="tab-content p-4">
            
            <!-- TAB 1: DETAILS & SCHEDULE -->
            <div class="tab-pane fade <?= $activeTab === 'details' ? 'show active' : '' ?>" id="details">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="save_facility" value="1">
                    <?php if($isUpdate): ?><input type="hidden" name="FacilityIDHidden" value="<?= $facilityData['FacilityID'] ?>"><?php endif; ?>

                    <div class="row g-5">
                        <!-- Left: Info -->
                        <div class="col-md-6">
                            <h5 class="fw-bold mb-3 text-primary"><i class="fa-regular fa-id-card me-2"></i>General Info</h5>
                            
                            <div class="mb-3">
                                <label class="form-label">Facility Name</label>
                                <input type="text" name="Name" class="form-control" value="<?= htmlspecialchars($facilityData['Name'] ?? '') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="Description" class="form-control" rows="3"><?= htmlspecialchars($facilityData['Description'] ?? '') ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Location</label>
                                    <input type="text" name="Location" class="form-control" value="<?= htmlspecialchars($facilityData['Location'] ?? '') ?>">
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Type</label>
                                    <input type="text" name="Type" class="form-control" value="<?= htmlspecialchars($facilityData['Type'] ?? '') ?>" placeholder="e.g. Court">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="Status" class="form-select">
                                    <?php 
                                    $st = $facilityData['Status'] ?? 'Active';
                                    foreach(['Active','Maintenance','Archived'] as $opt) echo "<option value='$opt' ".($st==$opt?'selected':'').">$opt</option>";
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Photo</label>
                                <input type="file" name="PhotoURL" class="form-control" accept="image/*" id="photoInput">
                                <?php if(!empty($facilityData['PhotoURL'])): ?>
                                    <img src="../admin/uploads/<?= htmlspecialchars($facilityData['PhotoURL']) ?>" id="photoPreview" class="img-preview">
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Right: Schedule -->
                        <div class="col-md-6">
                            <h5 class="fw-bold mb-3 text-primary"><i class="fa-regular fa-clock me-2"></i>Weekly Schedule</h5>
                            <div class="vstack gap-2">
                                <?php 
                                $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
                                foreach($days as $day):
                                    $dData = $existingSchedules[$day] ?? ['OpenTime'=>'08:00', 'CloseTime'=>'22:00', 'SlotDuration'=>60];
                                    $checked = isset($existingSchedules[$day]) ? 'checked' : '';
                                    $display = isset($existingSchedules[$day]) ? 'flex' : 'none';
                                ?>
                                <div class="schedule-row">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input sched-toggle" type="checkbox" name="available_days[]" value="<?= $day ?>" id="chk_<?= $day ?>" <?= $checked ?>>
                                        <label class="form-check-label fw-bold small" for="chk_<?= $day ?>"><?= $day ?></label>
                                    </div>
                                    <div class="row g-2 mt-1 inputs-<?= $day ?>" style="display:<?= $display ?>">
                                        <div class="col-4"><input type="time" class="form-control form-control-sm" name="start_time[<?= $day ?>]" value="<?= substr($dData['OpenTime'],0,5) ?>"></div>
                                        <div class="col-4"><input type="time" class="form-control form-control-sm" name="end_time[<?= $day ?>]" value="<?= substr($dData['CloseTime'],0,5) ?>"></div>
                                        <div class="col-4"><input type="number" class="form-control form-control-sm" name="slot_duration[<?= $day ?>]" value="<?= $dData['SlotDuration'] ?>" step="15" placeholder="Mins"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="text-end mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-primary-custom"><?= $isUpdate ? 'Save Changes' : 'Create Facility' ?></button>
                    </div>
                </form>
            </div>

            <!-- TAB 2: CLOSURES (Only if Update) -->
            <?php if ($isUpdate): ?>
            <div class="tab-pane fade <?= $activeTab === 'closures' ? 'show active' : '' ?>" id="closures">
                
                <!-- Add Closure Form -->
                <div class="bg-light p-4 rounded-3 border mb-4">
                    <h6 class="fw-bold mb-3">Add New Closure</h6>
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="add_closure" value="1">
                        <input type="hidden" name="facility_id" value="<?= $currentID ?>">
                        
                        <div class="col-md-3">
                            <label class="small text-muted">Start Date</label>
                            <input type="date" name="start_date" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-muted">End Date</label>
                            <input type="date" name="end_date" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-4">
                            <label class="small text-muted">Reason</label>
                            <input type="text" name="reason" class="form-control form-control-sm" placeholder="e.g. Maintenance" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-dark btn-sm w-100">Add Block</button>
                        </div>
                    </form>
                </div>

                <!-- Closure List -->
                <div class="table-responsive">
                    <table class="table table-hover border">
                        <thead class="table-light">
                            <tr>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Reason</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($existingClosures)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">No closures scheduled.</td></tr>
                            <?php else: ?>
                                <?php foreach($existingClosures as $c): ?>
                                <tr>
                                    <td><?= date('d M Y', strtotime($c['StartTime'])) ?></td>
                                    <td><?= date('d M Y', strtotime($c['EndTime'])) ?></td>
                                    <td><?= htmlspecialchars($c['Reason']) ?></td>
                                    <td class="text-end">
                                        <a href="?id=<?= $currentID ?>&del_closure=<?= $c['OverrideID'] ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('Remove this closure?')">Remove</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle Schedule Inputs
document.querySelectorAll('.sched-toggle').forEach(t => {
    t.addEventListener('change', function() {
        const row = document.querySelector(`.inputs-${this.value}`);
        row.style.display = this.checked ? 'flex' : 'none';
        row.querySelectorAll('input').forEach(i => i.disabled = !this.checked);
    });
    // Init state
    const row = document.querySelector(`.inputs-${t.value}`);
    row.querySelectorAll('input').forEach(i => i.disabled = !t.checked);
});

// Photo Preview
const pi = document.getElementById('photoInput');
if(pi) {
    pi.addEventListener('change', function() {
        const f = this.files[0];
        if(f) {
            const r = new FileReader();
            r.onload = (e) => {
                const img = document.getElementById('photoPreview') || document.createElement('img');
                img.id = 'photoPreview';
                img.className = 'img-preview';
                img.src = e.target.result;
                if(!document.getElementById('photoPreview')) pi.parentNode.appendChild(img);
            }
            r.readAsDataURL(f);
        }
    });
}
</script>
</body>
</html>