<?php
ob_start(); 
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/db_connect.php';

// Fetch Admin Details
$adminName = 'Admin';
$adminIdentifier = $_SESSION['user_id'] ?? '';
if ($adminIdentifier) {
    $stmt = $conn->prepare("SELECT FirstName, LastName FROM users WHERE UserIdentifier = ?");
    $stmt->bind_param("s", $adminIdentifier);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($r = $res->fetch_assoc()) {
        $adminName = $r['FirstName'] . ' ' . $r['LastName'];
    }
    $stmt->close();
}

// Helpers
$dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

$facilityData = null;
$isUpdate = false;
$currentID = $_GET['id'] ?? '';
$formTitle = "Add New Facility";
$existingSchedules = [];
$existingClosures = [];
$activeTab = $_GET['tab'] ?? 'details'; 

// Load Data
if ($currentID) {
    $stmt = $conn->prepare("SELECT * FROM facilities WHERE FacilityID = ?");
    $stmt->bind_param("s", $currentID); // Using 's' because FacilityID is VARCHAR
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $facilityData = $row;
        $isUpdate = true;
        $formTitle = "Edit: " . htmlspecialchars($row['Name']);
        
        // Load Schedules
        $stmtS = $conn->prepare("SELECT * FROM facilityschedules WHERE FacilityID = ?");
        $stmtS->bind_param("s", $currentID);
        $stmtS->execute();
        $resS = $stmtS->get_result();
        while ($sRow = $resS->fetch_assoc()) {
            $existingSchedules[$sRow['DayOfWeek']] = $sRow;
        }
        $stmtS->close();

        // Load Closures
        $stmtC = $conn->prepare("SELECT * FROM scheduleoverrides WHERE FacilityID = ? ORDER BY StartTime DESC");
        $stmtC->bind_param("s", $currentID);
        $stmtC->execute();
        $resC = $stmtC->get_result();
        while ($cRow = $resC->fetch_assoc()) {
            $existingClosures[] = $cRow;
        }
        $stmtC->close();
    }
    $stmt->close();
}

// Process Post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Search Logic
    if (isset($_POST['search_term'])) {
        $term = trim($_POST['search_term']);
        $like = "%$term%";
        $stmt = $conn->prepare("SELECT FacilityID FROM facilities WHERE FacilityID = ? OR Name LIKE ? LIMIT 1");
        $stmt->bind_param("ss", $term, $like);
        $stmt->execute();
        $found = $stmt->get_result()->fetch_assoc();
        if ($found) header("Location: addfacilities.php?id=".$found['FacilityID']);
        else echo "<script>alert('Not found'); window.location='addfacilities.php';</script>";
        exit();
    }

    // 2. Save Facility Logic
    if (isset($_POST['save_facility'])) {
        $name = $_POST['Name'];
        $desc = $_POST['Description'];
        $loc = $_POST['Location'];
        $type = $_POST['Type'];
        $status = $_POST['Status'];
        $fid = $_POST['FacilityIDHidden'] ?? null;

        // Image Upload
        $photoName = $facilityData['PhotoURL'] ?? NULL;
        if (!empty($_FILES['PhotoURL']['name'])) {
            $newName = time() . "_" . $_FILES['PhotoURL']['name'];
            if(!is_dir("../admin/uploads/")) mkdir("../admin/uploads/", 0777, true);
            if(move_uploaded_file($_FILES['PhotoURL']['tmp_name'], "../admin/uploads/" . $newName)) {
                $photoName = $newName;
            }
        }

        if ($isUpdate && $fid) {
            // Update Existing
            $stmt = $conn->prepare("UPDATE facilities SET Name=?, Description=?, Location=?, Type=?, PhotoURL=?, Status=? WHERE FacilityID=?");
            $stmt->bind_param("sssssss", $name, $desc, $loc, $type, $photoName, $status, $fid);
            $stmt->execute();
            $targetID = $fid;
        } else {
            // Insert New - Generate a Manual ID since DB isn't Auto-Increment
            // We use a prefix + random string to ensure it fits VARCHAR
            $targetID = "FAC-" . strtoupper(substr(md5(time()), 0, 6)); 
            
            $stmt = $conn->prepare("INSERT INTO facilities (FacilityID, Name, Description, Location, Type, PhotoURL, Status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $targetID, $name, $desc, $loc, $type, $photoName, $status);
            $stmt->execute();
        }

        // Update Schedules using the targetID we defined above
        if ($targetID) {
            $conn->query("DELETE FROM facilityschedules WHERE FacilityID = '$targetID'");
            if (!empty($_POST['available_days'])) {
                $ins = $conn->prepare("INSERT INTO facilityschedules (FacilityID, DayOfWeek, OpenTime, CloseTime, SlotDuration) VALUES (?, ?, ?, ?, ?)");
                foreach ($_POST['available_days'] as $day) {
                    $startT = $_POST['start_time'][$day];
                    $endT = $_POST['end_time'][$day];
                    $dur = (int)$_POST['slot_duration'][$day];
                    $ins->bind_param("ssssi", $targetID, $day, $startT, $endT, $dur);
                    $ins->execute();
                }
                $ins->close();
            }
            header("Location: addfacilities.php?id=$targetID&msg=Saved");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Facilities</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #8a0d19; --bg-light: #f8fafc; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-light); color: #1e293b; display: flex; flex-direction: column; min-height: 100vh; }
        .tab-btn.active { color: var(--primary); border-bottom: 3px solid var(--primary); }
    </style>
</head>
<body>

<nav class="bg-white border-b border-slate-200 sticky top-0 z-50 p-3">
    <div class="container mx-auto flex justify-between items-center px-6">
        <div class="flex items-center gap-4">
            <img src="../assets/img/ukm.png" alt="UKM Logo" class="h-10">
            <h1 class="font-bold text-slate-800">Admin Console</h1>
        </div>
        <div class="flex gap-6 items-center">
            <a href="dashboard.php" class="text-slate-600 hover:text-[#8a0d19] font-medium text-decoration-none">Home</a>
            <a href="addfacilities.php" class="text-[#8a0d19] font-bold text-decoration-none">Facilities</a>
            <a href="bookinglist.php" class="text-slate-600 hover:text-[#8a0d19] font-medium text-decoration-none">Bookings</a>
            <a href="../logout.php" class="text-red-600 text-sm font-bold"><i class="fa-solid fa-power-off"></i></a>
        </div>
    </div>
</nav>

<main class="container mx-auto px-6 py-10 max-w-5xl">
    <div class="flex justify-between items-end mb-8">
        <div>
            <h2 class="text-3xl font-bold text-slate-800"><?php echo $formTitle; ?></h2>
            <p class="text-slate-500">Configure facility settings and availability.</p>
        </div>
        <a href="addfacilities.php" class="text-blue-600 font-bold text-sm bg-white px-4 py-2 rounded-lg border shadow-sm text-decoration-none">+ Add New</a>
    </div>

    <!-- Search -->
    <div class="bg-white p-4 rounded-xl shadow-sm border mb-8">
        <form method="POST" class="flex gap-4">
            <input type="text" name="search_term" placeholder="Search by ID or Name..." class="flex-grow p-2 bg-slate-50 border rounded-lg outline-none focus:ring-1 ring-[#8a0d19]">
            <button class="bg-slate-800 text-white px-6 py-2 rounded-lg font-bold">Find</button>
        </form>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="bg-green-100 text-green-700 p-3 rounded-lg mb-6 border border-green-200 text-sm font-bold">
            <i class="fa-solid fa-circle-check mr-2"></i> Facility saved successfully.
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-xl border overflow-hidden">
        <div class="flex border-b">
            <button onclick="showTab('details')" id="btn-details" class="tab-btn active px-8 py-4 font-bold uppercase text-xs tracking-widest border-none bg-transparent">Details</button>
            <?php if($isUpdate): ?>
            <button onclick="showTab('closures')" id="btn-closures" class="tab-btn px-8 py-4 font-bold uppercase text-xs tracking-widest text-slate-400 border-none bg-transparent">Closures</button>
            <?php endif; ?>
        </div>

        <div class="p-8">
            <div id="view-details">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="save_facility" value="1">
                    <input type="hidden" name="FacilityIDHidden" value="<?php echo htmlspecialchars($currentID); ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                        <div class="space-y-4">
                            <h4 class="font-bold text-slate-400 text-xs uppercase tracking-tighter">Basic Info</h4>
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase">Facility Name</label>
                                <input type="text" name="Name" placeholder="e.g. Squash Court A" class="w-full p-3 bg-slate-50 border rounded-lg" value="<?php echo htmlspecialchars($facilityData['Name'] ?? ''); ?>" required>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase">Description</label>
                                <textarea name="Description" placeholder="Details about the facility..." rows="3" class="w-full p-3 bg-slate-50 border rounded-lg"><?php echo htmlspecialchars($facilityData['Description'] ?? ''); ?></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[10px] font-bold text-slate-400 uppercase">Location</label>
                                    <input type="text" name="Location" placeholder="e.g. Level 1" class="w-full p-3 bg-slate-50 border rounded-lg" value="<?php echo htmlspecialchars($facilityData['Location'] ?? ''); ?>">
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-slate-400 uppercase">Category</label>
                                    <input type="text" name="Type" placeholder="e.g. Indoor" class="w-full p-3 bg-slate-50 border rounded-lg" value="<?php echo htmlspecialchars($facilityData['Type'] ?? ''); ?>">
                                </div>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase">Status</label>
                                <select name="Status" class="w-full p-3 bg-slate-50 border rounded-lg">
                                    <option value="Active" <?php if(($facilityData['Status']??'')=='Active') echo 'selected'; ?>>Active</option>
                                    <option value="Maintenance" <?php if(($facilityData['Status']??'')=='Maintenance') echo 'selected'; ?>>Maintenance</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase">Photo</label>
                                <input type="file" name="PhotoURL" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h4 class="font-bold text-slate-400 text-xs uppercase tracking-tighter">Weekly Availability</h4>
                            <div class="space-y-2 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                                <?php foreach($dayNames as $day): 
                                    $s = $existingSchedules[$day] ?? null;
                                ?>
                                <div class="p-3 border rounded-lg bg-slate-50 flex items-center justify-between gap-2 hover:bg-white transition shadow-sm">
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" name="available_days[]" value="<?php echo $day; ?>" <?php if($s) echo 'checked'; ?> class="w-4 h-4 accent-[#8a0d19]">
                                        <span class="text-xs font-bold w-16"><?php echo $day; ?></span>
                                    </div>
                                    <div class="flex gap-1 items-center">
                                        <input type="time" name="start_time[<?php echo $day; ?>]" value="<?php echo $s ? substr($s['OpenTime'],0,5) : '08:00'; ?>" class="text-[10px] p-1 border rounded">
                                        <span class="text-slate-300">-</span>
                                        <input type="time" name="end_time[<?php echo $day; ?>]" value="<?php echo $s ? substr($s['CloseTime'],0,5) : '22:00'; ?>" class="text-[10px] p-1 border rounded">
                                        <input type="number" name="slot_duration[<?php echo $day; ?>]" value="<?php echo $s['SlotDuration'] ?? 60; ?>" class="w-10 text-[10px] p-1 border rounded text-center" title="Duration (min)">
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mt-10 pt-6 border-t flex justify-end">
                        <button class="bg-[#8a0d19] text-white px-10 py-3 rounded-xl font-bold shadow-lg hover:bg-red-800 transition active:scale-95 border-none cursor-pointer">Save Facility Configuration</button>
                    </div>
                </form>
            </div>

            <?php if($isUpdate): ?>
            <div id="view-closures" class="hidden">
                <div class="flex items-center justify-center py-20 text-slate-400 flex-col gap-4">
                    <i class="fa-solid fa-calendar-xmark text-4xl"></i>
                    <p class="text-sm font-medium">Closure management is available in the <a href="manage_closures.php" class="text-blue-600 underline">Closures Dashboard</a>.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
function showTab(tab) {
    document.getElementById('view-details').classList.toggle('hidden', tab !== 'details');
    if(document.getElementById('view-closures')) document.getElementById('view-closures').classList.toggle('hidden', tab !== 'closures');
    
    document.getElementById('btn-details').classList.toggle('active', tab === 'details');
    document.getElementById('btn-details').classList.toggle('text-slate-400', tab !== 'details');
    
    if(document.getElementById('btn-closures')) {
        document.getElementById('btn-closures').classList.toggle('active', tab === 'closures');
        document.getElementById('btn-closures').classList.toggle('text-slate-400', tab !== 'closures');
    }
}
</script>
</body>
</html>