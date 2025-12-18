<?php
session_start();

// 1. Auth Check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Admin') {
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

// Map Logic (Strings <-> Integers)
$dayMap = ['Sunday'=>0, 'Monday'=>1, 'Tuesday'=>2, 'Wednesday'=>3, 'Thursday'=>4, 'Friday'=>5, 'Saturday'=>6];
$dayMapRev = array_flip($dayMap);

// Vars
$facilityData = null;
$isUpdate = false;
$currentID = $_GET['id'] ?? '';
$formTitle = "Add Facilities"; // Updated Title
$existingSchedules = [];
$existingClosures = [];
$activeTab = $_GET['tab'] ?? 'details'; 

// --- 2. LOAD DATA ---
if ($currentID) {
    // Info
    $stmt = $conn->prepare("SELECT * FROM facilities WHERE FacilityID = ?");
    $stmt->bind_param("i", $currentID);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $facilityData = $res->fetch_assoc();
        $isUpdate = true;
        $formTitle = "Edit: " . htmlspecialchars($facilityData['Name']);
        
        // Schedules
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

        // Closures
        $stmtC = $conn->prepare("SELECT * FROM scheduleoverrides WHERE FacilityID = ? ORDER BY StartTime DESC");
        $stmtC->bind_param("i", $currentID);
        $stmtC->execute();
        $resC = $stmtC->get_result();
        while ($row = $resC->fetch_assoc()) {
            $existingClosures[] = $row;
        }
        $stmtC->close();

    } else {
        header("Location: addfacilities.php");
        exit();
    }
    $stmt->close();
}

// --- 3. PROCESS POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Search
    if (isset($_POST['search_term'])) {
        $term = trim($_POST['search_term']);
        $targetID = 0;
        if (is_numeric($term)) {
            $stmt = $conn->prepare("SELECT FacilityID FROM facilities WHERE FacilityID = ?");
            $idInt = intval($term);
            $stmt->bind_param("i", $idInt);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) $targetID = $idInt;
            $stmt->close();
        }
        if ($targetID === 0) {
            $stmt = $conn->prepare("SELECT FacilityID FROM facilities WHERE Name LIKE ? LIMIT 1");
            $likeTerm = "%" . $term . "%";
            $stmt->bind_param("s", $likeTerm);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) $targetID = $row['FacilityID'];
            $stmt->close();
        }
        if ($targetID > 0) { header("Location: addfacilities.php?id=$targetID"); exit(); }
        else { echo "<script>alert('Facility not found!'); window.location='addfacilities.php';</script>"; exit(); }
    }

    // Save Facility
    if (isset($_POST['save_facility'])) {
        $name = $_POST['Name'];
        $desc = $_POST['Description'];
        $loc = $_POST['Location'];
        $type = $_POST['Type'];
        $status = $_POST['Status'];
        $fid = $_POST['FacilityIDHidden'] ?? null;

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

    // Add Closure
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

// Delete Closure
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Facilities - UKM Sports Center</title>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
:root {
    --primary: #0b4d9d; /* UKM Blue */
    --bg-light: #f8f9fa;
}
body {
    font-family: 'Inter', sans-serif;
    background-color: var(--bg-light);
    color: #333;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}
h1, h2, h3 { font-family: 'Playfair Display', serif; }

/* Custom Scrollbar */
.scrollbar-hide::-webkit-scrollbar { display: none; }

/* Filter Bar */
.filter-bar {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 20px -1px rgba(0,0,0,0.08);
    margin-top: -60px;
    margin-bottom: 40px;
    border: 1px solid #eee;
    position: relative;
    z-index: 10;
}

/* Tabs */
.tab-btn { position: relative; color: #6b7280; transition: all 0.3s; }
.tab-btn::after {
    content: ''; position: absolute; bottom: -2px; left: 0; width: 0%; height: 3px;
    background-color: var(--primary); transition: width 0.3s;
}
.tab-btn.active { color: var(--primary); font-weight: 700; }
.tab-btn.active::after { width: 100%; }

/* Fade Animation */
.fade-in { animation: fadeIn 0.4s ease-in-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

/* Inputs */
input:focus, textarea:focus, select:focus {
    outline: none;
    border-color: #0b4d9d;
    box-shadow: 0 0 0 1px #0b4d9d;
}
</style>
</head>
<body>

<!-- NAVBAR (No Icons, No Closures, White BG) -->
<nav class="bg-white/95 backdrop-blur-sm border-b border-gray-200 sticky top-0 z-50 shadow-md">
    <div class="container mx-auto px-6 py-3 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <img src="../assets/img/ukm.png" alt="UKM Logo" class="h-12 w-auto">
            <div class="h-8 w-px bg-gray-300 hidden sm:block"></div>
            <img src="../assets/img/pusatsukanlogo.png" alt="Pusat Sukan Logo" class="h-12 w-auto hidden sm:block">
        </div>
        <div class="flex items-center gap-6">
            <a href="dashboard.php" class="text-gray-600 hover:text-[#0b4d9d] font-medium transition">
                Home
            </a>
            
            <!-- Active State -->
            <a href="addfacilities.php" class="text-[#0b4d9d] font-bold transition">
                Facilities
            </a>
            
            <a href="bookinglist.php" class="text-gray-600 hover:text-[#0b4d9d] font-medium transition">Bookings</a>

            <div class="flex items-center gap-3 pl-6 border-l border-gray-200">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($adminName); ?></p>
                    <p class="text-xs text-gray-500 uppercase tracking-wider"><?php echo htmlspecialchars($adminIdentifier); ?></p>
                </div>
                <!-- Profile Dropdown Container -->
                <div class="relative group">
                    <button class="flex items-center focus:outline-none">
                        <img src="../assets/img/user.png" alt="Profile" class="w-10 h-10 rounded-full border-2 border-white shadow-md object-cover hover:scale-105 transition">
                    </button>
                    <!-- Dropdown Menu -->
                    <div class="absolute right-0 top-full pt-2 w-48 hidden group-hover:block z-50">
                        <div class="bg-white rounded-lg shadow-xl border border-gray-100 overflow-hidden">
                            <a href="../logout.php" onclick="return confirm('Logout?');" class="block px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition">
                                <i class="fa-solid fa-right-from-bracket mr-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- MAIN CONTENT (No Banner) -->
<main class="container mx-auto px-6 py-10 flex-grow max-w-6xl mt-8">
    
    <!-- Header & Actions -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <span class="text-sm font-bold text-gray-400 uppercase tracking-wider">Facility Management</span>
            <h1 class="text-3xl font-bold text-[#0b4d9d] mb-1 font-serif"><?php echo $formTitle; ?></h1>
            <p class="text-gray-500">Manage facility details, operating hours, and closures.</p>
        </div>
        
        <?php if($isUpdate): ?>
            <a href="addfacilities.php" class="bg-white border border-gray-200 text-gray-600 px-5 py-2.5 rounded-lg hover:bg-gray-50 transition shadow-sm font-medium flex items-center gap-2">
                <i class="fa-solid fa-plus text-[#0b4d9d]"></i> Add New
            </a>
        <?php endif; ?>
    </div>

    <!-- Search Box (Styled like Filter Bar) -->
    <div class="filter-bar mb-8">
        <form method="POST" class="flex flex-col md:flex-row gap-4 items-center">
            <div class="flex-grow w-full relative">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input type="text" name="search_term" class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-[#0b4d9d] focus:ring-1 focus:ring-[#0b4d9d] transition" placeholder="Enter Facility ID (e.g. 12) or Name" required>
            </div>
            <button type="submit" class="bg-[#0b4d9d] text-white px-8 py-3 rounded-lg font-bold hover:bg-[#083a75] transition shadow-lg shadow-blue-900/20 whitespace-nowrap">
                Load Facility
            </button>
        </form>
    </div>

    <!-- MAIN FORM CARD -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden min-h-[600px] fade-in">
        
        <!-- Tabs -->
        <div class="flex border-b border-gray-200 px-8 pt-6 bg-white gap-8">
            <button onclick="switchTab('details')" id="tab-details" class="tab-btn active pb-4 px-2 text-sm font-bold uppercase tracking-wide">
                Details & Schedule
            </button>
            <?php if($isUpdate): ?>
            <button onclick="switchTab('closures')" id="tab-closures" class="tab-btn pb-4 px-2 text-sm font-bold uppercase tracking-wide">
                Closures <span class="bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full text-xs ml-1"><?php echo count($existingClosures); ?></span>
            </button>
            <?php endif; ?>
        </div>

        <div class="p-8">
            
            <!-- TAB 1: DETAILS & SCHEDULE -->
            <div id="view-details" class="fade-in">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="save_facility" value="1">
                    <?php if($isUpdate): ?><input type="hidden" name="FacilityIDHidden" value="<?php echo $facilityData['FacilityID']; ?>"><?php endif; ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                        
                        <!-- LEFT: DETAILS -->
                        <div class="space-y-6">
                            <div class="border-b border-gray-100 pb-2 mb-4">
                                <h3 class="text-lg font-bold text-gray-800">General Information</h3>
                                <?php if($isUpdate): ?>
                                    <p class="text-xs text-[#0b4d9d]">Editing ID: <strong><?php echo $facilityData['FacilityID']; ?></strong></p>
                                <?php else: ?>
                                    <p class="text-xs text-gray-400">ID will be auto-generated</p>
                                <?php endif; ?>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Name</label>
                                <input type="text" name="Name" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white" value="<?php echo htmlspecialchars($facilityData['Name'] ?? ''); ?>" required>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
                                <textarea name="Description" rows="4" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white" required><?php echo htmlspecialchars($facilityData['Description'] ?? ''); ?></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Location</label>
                                    <input type="text" name="Location" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white" value="<?php echo htmlspecialchars($facilityData['Location'] ?? ''); ?>">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Type</label>
                                    <input type="text" name="Type" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white" value="<?php echo htmlspecialchars($facilityData['Type'] ?? ''); ?>" placeholder="e.g. Court">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                                <div class="relative">
                                    <select name="Status" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white appearance-none cursor-pointer">
                                        <?php 
                                        $st = $facilityData['Status'] ?? 'Active';
                                        foreach(['Active','Maintenance','Archived'] as $opt) echo "<option value='$opt' ".($st==$opt?'selected':'').">$opt</option>";
                                        ?>
                                    </select>
                                    <i class="fa-solid fa-chevron-down absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Photo</label>
                                <input type="file" name="PhotoURL" id="photoInput" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-[#0b4d9d] hover:file:bg-blue-100 cursor-pointer">
                                <?php if(!empty($facilityData['PhotoURL'])): ?>
                                    <img src="../admin/uploads/<?php echo $facilityData['PhotoURL']; ?>" id="photoPreview" class="mt-3 rounded-lg border border-gray-200 h-40 w-full object-cover">
                                <?php else: ?>
                                    <img id="photoPreview" class="hidden mt-3 rounded-lg border border-gray-200 h-40 w-full object-cover">
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- RIGHT: SCHEDULE -->
                        <div class="space-y-4">
                            <div class="border-b border-gray-100 pb-2 mb-4">
                                <h3 class="text-lg font-bold text-gray-800">Weekly Schedule</h3>
                                <p class="text-xs text-gray-400">Toggle days to set operating hours.</p>
                            </div>

                            <div class="space-y-3">
                                <?php 
                                $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
                                foreach($days as $day):
                                    $dData = $existingSchedules[$day] ?? ['OpenTime'=>'08:00', 'CloseTime'=>'22:00', 'SlotDuration'=>60];
                                    $checked = isset($existingSchedules[$day]) ? 'checked' : '';
                                    $display = isset($existingSchedules[$day]) ? 'grid' : 'none';
                                ?>
                                <div class="bg-gray-50 border border-gray-100 rounded-lg p-3 transition hover:border-gray-300">
                                    <div class="flex items-center gap-3 mb-3">
                                        <input type="checkbox" name="available_days[]" value="<?php echo $day; ?>" id="chk_<?php echo $day; ?>" class="sched-toggle w-4 h-4 text-[#0b4d9d] rounded border-gray-300 focus:ring-[#0b4d9d] cursor-pointer" <?php echo $checked; ?>>
                                        <label for="chk_<?php echo $day; ?>" class="text-sm font-bold text-gray-700 cursor-pointer select-none"><?php echo $day; ?></label>
                                    </div>
                                    <div class="grid-cols-3 gap-3 inputs-<?php echo $day; ?>" style="display: <?php echo $display; ?>;">
                                        <div>
                                            <span class="text-[10px] text-gray-400 uppercase font-bold tracking-wide">Open</span>
                                            <input type="time" name="start_time[<?php echo $day; ?>]" value="<?php echo substr($dData['OpenTime'],0,5); ?>" class="w-full mt-1 p-1.5 border border-gray-200 rounded text-xs bg-white focus:border-[#0b4d9d]">
                                        </div>
                                        <div>
                                            <span class="text-[10px] text-gray-400 uppercase font-bold tracking-wide">Close</span>
                                            <input type="time" name="end_time[<?php echo $day; ?>]" value="<?php echo substr($dData['CloseTime'],0,5); ?>" class="w-full mt-1 p-1.5 border border-gray-200 rounded text-xs bg-white focus:border-[#0b4d9d]">
                                        </div>
                                        <div>
                                            <span class="text-[10px] text-gray-400 uppercase font-bold tracking-wide">Mins</span>
                                            <input type="number" name="slot_duration[<?php echo $day; ?>]" value="<?php echo $dData['SlotDuration']; ?>" step="15" class="w-full mt-1 p-1.5 border border-gray-200 rounded text-xs bg-white focus:border-[#0b4d9d]">
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-gray-100 flex justify-end">
                        <button type="submit" class="bg-[#0b4d9d] text-white px-8 py-3 rounded-lg font-bold hover:bg-[#083a75] transition shadow-lg shadow-blue-900/20 flex items-center gap-2">
                            <i class="fa-solid fa-save"></i>
                            <?php echo $isUpdate ? 'Save Changes' : 'Create Facility'; ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- TAB 2: CLOSURES -->
            <?php if ($isUpdate): ?>
            <div id="view-closures" class="hidden fade-in">
                <!-- Add Closure -->
                <div class="bg-red-50 border border-red-100 rounded-lg p-6 mb-8">
                    <h4 class="text-red-800 font-bold text-sm mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation"></i> Add New Closure Block
                    </h4>
                    <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <input type="hidden" name="add_closure" value="1">
                        <input type="hidden" name="facility_id" value="<?php echo $currentID; ?>">
                        
                        <div>
                            <label class="block text-xs font-bold text-red-600 mb-1 uppercase">Start Date</label>
                            <input type="date" name="start_date" class="w-full p-2.5 bg-white border border-red-200 rounded-lg text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-red-600 mb-1 uppercase">End Date</label>
                            <input type="date" name="end_date" class="w-full p-2.5 bg-white border border-red-200 rounded-lg text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-red-600 mb-1 uppercase">Reason</label>
                            <input type="text" name="reason" placeholder="e.g. Maintenance" class="w-full p-2.5 bg-white border border-red-200 rounded-lg text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none" required>
                        </div>
                        <button class="bg-red-600 text-white px-6 py-2.5 rounded-lg text-sm font-bold hover:bg-red-700 transition shadow-sm">
                            Block Dates
                        </button>
                    </form>
                </div>

                <!-- List -->
                <div class="overflow-hidden border border-gray-200 rounded-xl shadow-sm">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-gray-500 font-semibold uppercase text-xs">
                            <tr>
                                <th class="p-4">Start Date</th>
                                <th class="p-4">End Date</th>
                                <th class="p-4">Reason</th>
                                <th class="p-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <?php if(empty($existingClosures)): ?>
                                <tr><td colspan="4" class="p-8 text-center text-gray-400 italic">No active closures scheduled.</td></tr>
                            <?php else: ?>
                                <?php foreach($existingClosures as $c): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="p-4 text-gray-800 font-medium"><?php echo date('d M Y', strtotime($c['StartTime'])); ?></td>
                                    <td class="p-4 text-gray-800 font-medium"><?php echo date('d M Y', strtotime($c['EndTime'])); ?></td>
                                    <td class="p-4 text-gray-600"><?php echo htmlspecialchars($c['Reason']); ?></td>
                                    <td class="p-4 text-right">
                                        <a href="?id=<?php echo $currentID; ?>&tab=closures&del_closure=<?php echo $c['OverrideID']; ?>" 
                                           class="text-red-500 hover:text-red-700 font-bold text-xs bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded transition" 
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

</main>

<!-- FOOTER (Exact Match to Student Facilities) -->
<footer class="bg-white border-t border-gray-200 py-6 mt-auto">
    <div class="container mx-auto px-6">
        <div class="flex flex-col md:flex-row justify-between items-center gap-6">
            <!-- Logo & Address -->
            <div class="flex items-center gap-4">
                <img src="../assets/img/pusatsukanlogo.png" alt="Pusat Sukan Logo" class="h-12 w-auto">
                <div class="text-xs text-gray-600 leading-snug">
                    <strong class="block text-gray-800 text-sm mb-0.5">PEJABAT PENGARAH PUSAT SUKAN</strong>
                    Stadium Universiti, Universiti Kebangsaan Malaysia<br>
                    43600 Bangi, Selangor Darul Ehsan<br>
                    <span class="mt-0.5 block text-[#0b4d9d] font-semibold"><i class="fa-solid fa-phone mr-1"></i> 03-8921-5306</span>
                </div>
            </div>
            
            <!-- SDG Logo & Copyright -->
            <div class="flex items-center gap-6">
                <img src="../assets/img/sdg.png" alt="SDG Logo" class="h-14 w-auto opacity-90">
                <p class="text-[10px] text-gray-400 text-right">
                    &copy; 2025 Universiti Kebangsaan Malaysia.<br>All rights reserved.
                </p>
            </div>
        </div>
    </div>
</footer>

<script>
// --- TAB SWITCHING ---
<?php if($isUpdate): ?>
    const tabDetails = document.getElementById('tab-details');
    const tabClosures = document.getElementById('tab-closures');
    const viewDetails = document.getElementById('view-details');
    const viewClosures = document.getElementById('view-closures');

    function showTab(tab) {
        if(tab === 'details') {
            tabDetails.classList.add('text-[#0b4d9d]', 'border-[#0b4d9d]');
            tabDetails.classList.remove('text-gray-500', 'border-transparent');
            tabClosures.classList.remove('text-[#0b4d9d]', 'border-[#0b4d9d]');
            tabClosures.classList.add('text-gray-500', 'border-transparent');
            viewDetails.classList.remove('hidden');
            viewClosures.classList.add('hidden');
        } else {
            tabClosures.classList.add('text-[#0b4d9d]', 'border-[#0b4d9d]');
            tabClosures.classList.remove('text-gray-500', 'border-transparent');
            tabDetails.classList.remove('text-[#0b4d9d]', 'border-[#0b4d9d]');
            tabDetails.classList.add('text-gray-500', 'border-transparent');
            viewClosures.classList.remove('hidden');
            viewDetails.classList.add('hidden');
        }
    }

    tabDetails.addEventListener('click', () => showTab('details'));
    tabClosures.addEventListener('click', () => showTab('closures'));

    // Check PHP active tab
    <?php if($activeTab === 'closures'): ?>
        showTab('closures');
    <?php endif; ?>
<?php endif; ?>

// --- TOGGLE SCHEDULE INPUTS ---
document.querySelectorAll('.sched-toggle').forEach(t => {
    t.addEventListener('change', function() {
        const row = document.querySelector(`.inputs-${this.value}`);
        row.style.display = this.checked ? 'grid' : 'none';
        row.querySelectorAll('input').forEach(i => i.disabled = !this.checked);
    });
    // Init state
    const row = document.querySelector(`.inputs-${t.value}`);
    if(row) {
        row.style.display = t.checked ? 'grid' : 'none';
        row.querySelectorAll('input').forEach(i => i.disabled = !t.checked);
    }
});

// --- PHOTO PREVIEW ---
const pi = document.getElementById('photoInput');
if(pi) {
    pi.addEventListener('change', function() {
        const f = this.files[0];
        if(f) {
            const r = new FileReader();
            r.onload = (e) => {
                let img = document.getElementById('photoPreview');
                if(!img) {
                    img = document.createElement('img');
                    img.id = 'photoPreview';
                    img.className = 'mt-3 rounded-lg border border-gray-200 h-32 w-full object-cover';
                    pi.parentNode.appendChild(img);
                }
                img.src = e.target.result;
                img.classList.remove('hidden');
            }
            r.readAsDataURL(f);
        }
    });
}
</script>
</body>
</html>