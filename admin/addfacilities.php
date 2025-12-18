<?php
require_once 'includes/admin_auth.php'; // Standardized Auth & User Fetch

// Logic: Get Next Facility ID
function getNextFacilityNumber($conn) {
    $result = $conn->query("SELECT FacilityID FROM facilities ORDER BY LENGTH(FacilityID) DESC, FacilityID DESC LIMIT 1");
    if ($result->num_rows == 0) return "001";
    $row = $result->fetch_assoc();
    return preg_match('/(\d+)$/', $row['FacilityID'], $matches) ? str_pad($matches[1] + 1, 3, "0", STR_PAD_LEFT) : "001";
}
$nextFacilityNumber = getNextFacilityNumber($conn);

// Logic: Day Mapping
$dayNameToIndex = ["Sunday"=>0,"Monday"=>1,"Tuesday"=>2,"Wednesday"=>3,"Thursday"=>4,"Friday"=>5,"Saturday"=>6];
$dayIndexToName = array_flip($dayNameToIndex);

// Logic: Initialization
$facilityData = null;
$isUpdate = false;
$currentFacilityID = $_GET['id'] ?? '';
$formTitle = "Add New Facility";
$existingSchedules = [];

// Logic: Fetch Data if Update
if (!empty($currentFacilityID)) {
    $stmt = $conn->prepare("SELECT * FROM facilities WHERE FacilityID = ?");
    $stmt->bind_param("s", $currentFacilityID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $facilityData = $result->fetch_assoc();
        $isUpdate = true;
        $formTitle = "Edit Facility";
    } else {
        echo "<script>alert('Facility ID not found.'); window.location='addfacilities.php';</script>"; exit();
    }
    $stmt->close();

    $sched_sql = "SELECT DayOfWeek, OpenTime, CloseTime, SlotDuration FROM facilityschedules WHERE FacilityID = ?";
    $sched_stmt = $conn->prepare($sched_sql);
    $sched_stmt->bind_param("s", $currentFacilityID);
    $sched_stmt->execute();
    $sched_res = $sched_stmt->get_result();
    while ($row = $sched_res->fetch_assoc()) {
        $dayName = $dayIndexToName[intval($row['DayOfWeek'])] ?? null;
        if ($dayName) $existingSchedules[$dayName] = $row;
    }
    $sched_stmt->close();
}

// Logic: Handle Post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['search_id'])) {
        $searchID = strtoupper(trim($_POST['search_id']));
        if (!empty($searchID)) { header("Location: addfacilities.php?id=" . $searchID); exit(); }
    } elseif (!isset($_POST['search_id'])) {
        $id = $_POST['FacilityIDHidden'] ?? $_POST['FacilityIDCombined'] ?? ''; 
        $name = $_POST['Name'] ?? '';
        $description = $_POST['Description'] ?? '';
        $location = $_POST['Location'] ?? '';
        $type = $_POST['Type'] ?? '';
        $status = $_POST['Status'] ?? 'Active';

        if (empty($id) || empty($name)) {
            echo "<script>alert('Please fill Facility ID and Name'); window.history.back();</script>"; exit();
        }

        $newPhotoName = $facilityData['PhotoURL'] ?? ''; 
        if (isset($_FILES['PhotoURL']) && $_FILES['PhotoURL']['error'] === 0) {
            $fileTmp = $_FILES['PhotoURL']['tmp_name'];
            if(getimagesize($fileTmp) !== false) {
                $uploadDir = __DIR__ . "/../uploads/facilities/";
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $ext = pathinfo(basename($_FILES['PhotoURL']['name']), PATHINFO_EXTENSION);
                $fileName = time() . "_" . uniqid() . "." . $ext;
                if (move_uploaded_file($fileTmp, $uploadDir . $fileName)) {
                    if ($isUpdate && !empty($facilityData['PhotoURL'])) {
                        $old = $uploadDir . $facilityData['PhotoURL'];
                        if (is_file($old)) @unlink($old);
                    }
                    $newPhotoName = $fileName;
                }
            }
        }

        if ($isUpdate) {
            $stmt = $conn->prepare("UPDATE facilities SET Name=?, Description=?, Location=?, Type=?, PhotoURL=?, Status=? WHERE FacilityID=?");
            $stmt->bind_param("sssssss", $name, $description, $location, $type, $newPhotoName, $status, $id);
            $msg = "Facility updated successfully!";
        } else {
            $chk = $conn->prepare("SELECT FacilityID FROM facilities WHERE FacilityID=?");
            $chk->bind_param("s", $id);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) { echo "<script>alert('Error: ID exists.'); window.history.back();</script>"; exit(); }
            $chk->close();
            $stmt = $conn->prepare("INSERT INTO facilities (FacilityID, Name, Description, Location, Type, PhotoURL, Status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $id, $name, $description, $location, $type, $newPhotoName, $status);
            $msg = "Facility added successfully!";
        }

        if ($stmt && $stmt->execute()) {
            $conn->query("DELETE FROM facilityschedules WHERE FacilityID = '$id'");
            $ins = $conn->prepare("INSERT INTO facilityschedules (FacilityID, DayOfWeek, OpenTime, CloseTime, SlotDuration) VALUES (?, ?, ?, ?, ?)");
            if (!empty($_POST['available_days'])) {
                foreach ($_POST['available_days'] as $day) {
                    if (!isset($dayNameToIndex[$day])) continue;
                    $idx = $dayNameToIndex[$day];
                    $start = date('H:i:s', strtotime($_POST['start_time'][$day] ?? '08:00'));
                    $end = date('H:i:s', strtotime($_POST['end_time'][$day] ?? '17:00'));
                    $dur = intval($_POST['slot_duration'][$day] ?? 60);
                    $ins->bind_param("sissi", $id, $idx, $start, $end, $dur);
                    $ins->execute();
                }
            }
            $ins->close();
            echo "<script>alert('$msg'); window.location='addfacilities.php?id=$id';</script>";
        } else {
            echo "Error: " . $conn->error;
        }
        if($stmt) $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?php echo $formTitle; ?></title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <script>
tailwind.config = {
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'sans-serif'],       // Body text
                serif: ['Playfair Display', 'serif'], // Headings
            },
            colors: {
                ukm: {
                    blue: '#0b4d9d',
                    dark: '#063a75',
                    light: '#e0f2fe'
                }
            }
        }
    }
}
</script>

    <style>
        
body { font-family: 'Inter', sans-serif; }
h1, h2, h3, .font-serif { font-family: 'Playfair Display', serif; }


        .fade-in { animation: fadeIn 0.4s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Custom Input Styles */
        .input-group-focus:focus-within {
            border-color: #0b4d9d;
            box-shadow: 0 0 0 4px rgba(11, 77, 157, 0.1);
        }
        
        /* Smooth Toggle */
        .toggle-checkbox:checked { right: 0; border-color: #0b4d9d; }
        .toggle-checkbox:checked + .toggle-label { background-color: #0b4d9d; }
        
        .glass-header {
            background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(240,249,255,0.9) 100%);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="font-sans">


<!-- NAVBAR (Preserved from Dashboard) -->
<?php 
$nav_active = 'facilities';
include 'includes/navbar.php'; 
?>

<!-- MAIN CONTENT -->
<main class="flex-grow container mx-auto px-4 md:px-6 py-8 max-w-7xl fade-in">

    <!-- PAGE HEADER -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10 pb-6 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">
                <span class="bg-slate-100 px-2 py-1 rounded">Admin</span>
                <i class="fa-solid fa-chevron-right text-[10px]"></i>
                <span class="text-ukm-blue">Facilities Management</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-extrabold text-[#0b4d9d] tracking-tight">
    <?php echo $isUpdate ? 'Edit Facility' : 'Create New Facility'; ?>
            </h1>
            <p class="text-slate-500 mt-2 text-lg">Manage facility details, availability, and scheduling.</p>
        </div>

        <!-- SEARCH BAR -->
        <div class="w-full md:w-auto">
            <form method="POST" class="relative group">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400 group-focus-within:text-ukm-blue transition">
                    <i class="fa-solid fa-search"></i>
                </div>
                <select name="search_id" onchange="this.form.submit()" class="pl-10 pr-10 py-3 w-full md:w-72 bg-white border border-slate-200 rounded-xl shadow-sm text-sm font-semibold text-slate-700 outline-none focus:ring-2 focus:ring-ukm-blue focus:border-transparent transition appearance-none cursor-pointer">
                    <option value="" disabled selected>Search facility...</option>
                    <?php 
                    $list = $conn->query("SELECT FacilityID, Name FROM facilities ORDER BY FacilityID");
                    while($r = $list->fetch_assoc()):
                    ?>
                        <option value="<?php echo $r['FacilityID']; ?>"><?php echo $r['Name'] . ' (' . $r['FacilityID'] . ')'; ?></option>
                    <?php endwhile; ?>
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400">
                    <i class="fa-solid fa-chevron-down text-xs"></i>
                </div>
                <?php if($isUpdate): ?>
                    <a href="addfacilities.php" class="absolute -top-12 right-0 md:static md:ml-2 md:inline-flex items-center gap-2 bg-ukm-blue text-white px-5 py-3 rounded-xl font-bold shadow-lg shadow-blue-500/20 hover:bg-ukm-dark transition transform active:scale-95">
                        <i class="fa-solid fa-plus"></i> <span class="hidden md:inline">New</span>
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data" id="mainForm">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- LEFT: DETAILS -->
            <div class="lg:col-span-5 space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                        <h3 class="font-bold text-slate-800 flex items-center gap-2">
                             <i class="fa-solid fa-layer-group text-ukm-blue"></i> General Information
                        </h3>
                    </div>
                    
                    <div class="p-6 space-y-5">
                        <!-- ID -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5 ml-1">Facility ID</label>
                            <?php if ($isUpdate): ?>
                                <div class="w-full bg-slate-100 border border-slate-200 text-slate-700 font-mono font-bold rounded-xl px-4 py-3 flex justify-between items-center">
                                    <?php echo htmlspecialchars($facilityData['FacilityID']); ?>
                                    <input type="hidden" name="FacilityIDHidden" value="<?php echo htmlspecialchars($facilityData['FacilityID']); ?>">
                                    <i class="fa-solid fa-lock text-slate-400"></i>
                                </div>
                            <?php else: ?>
                                <div class="flex rounded-xl shadow-sm input-group-focus border border-slate-200 overflow-hidden transition bg-white">
                                    <div class="bg-slate-50 border-r border-slate-200 px-3 flex items-center">
                                        <select id="prefixSelect" class="bg-transparent font-bold text-slate-700 outline-none text-sm cursor-pointer">
                                            <option value="ID">ID</option>
                                            <option value="OD">OD</option>
                                        </select>
                                    </div>
                                    <input type="text" id="idNumberInput" value="<?php echo $nextFacilityNumber; ?>" class="flex-grow px-4 py-3 font-mono font-bold text-slate-800 outline-none" placeholder="001">
                                    <input type="hidden" name="FacilityIDCombined" id="FacilityIDCombined">
                                </div>
                                <p class="text-xs text-slate-400 mt-1 ml-1">Use 'ID' for Indoor, 'OD' for Outdoor.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Name -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5 ml-1">Name</label>
                            <div class="relative">
                                <i class="fa-solid fa-tag absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="text" name="Name" value="<?php echo htmlspecialchars($facilityData['Name'] ?? ''); ?>" class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-ukm-blue focus:border-transparent outline-none transition" placeholder="e.g. Badminton Court 1" required>
                            </div>
                        </div>

                        <!-- Type & Location -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5 ml-1">Type</label>
                                <input type="text" name="Type" value="<?php echo htmlspecialchars($facilityData['Type'] ?? ''); ?>" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-ukm-blue outline-none transition" placeholder="e.g. Court" required>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5 ml-1">Location</label>
                                <input type="text" name="Location" value="<?php echo htmlspecialchars($facilityData['Location'] ?? ''); ?>" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-ukm-blue outline-none transition" placeholder="e.g. Block A" required>
                            </div>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5 ml-1">Status</label>
                            <div class="relative">
                                <select name="Status" class="w-full pl-4 pr-10 py-3 rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-ukm-blue outline-none transition appearance-none bg-white">
                                    <?php 
                                    $st = $facilityData['Status'] ?? 'Active';
                                    foreach(['Active','Maintenance','Archived'] as $s) {
                                        echo "<option value='$s' " . ($st==$s?'selected':'') . ">$s</option>";
                                    }
                                    ?>
                                </select>
                                <i class="fa-solid fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5 ml-1">Description</label>
                            <textarea name="Description" rows="3" class="w-full p-4 rounded-xl border border-slate-200 text-sm font-medium text-slate-800 focus:ring-2 focus:ring-ukm-blue outline-none transition resize-none" placeholder="Enter facility details..."><?php echo htmlspecialchars($facilityData['Description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- PHOTO UPLOAD -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden p-6">
                    <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-image text-ukm-blue"></i> Facility Image
                    </h3>
                    
                    <div class="flex items-start gap-4">
                        <div class="relative group w-28 h-28 rounded-xl bg-slate-100 border-2 border-slate-200 overflow-hidden flex-shrink-0">
                             <?php $imgSrc = (!empty($facilityData['PhotoURL'])) ? '../uploads/facilities/'.$facilityData['PhotoURL'] : '../assets/img/no-photo.png'; ?>
                             <img id="previewImg" src="<?php echo $imgSrc; ?>" class="w-full h-full object-cover">
                        </div>
                        
                        <div class="flex-grow">
                             <label class="flex flex-col items-center justify-center w-full h-28 border-2 border-slate-300 border-dashed rounded-xl cursor-pointer bg-slate-50 hover:bg-slate-100 transition group">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <i class="fa-solid fa-cloud-arrow-up text-2xl text-slate-400 group-hover:text-ukm-blue mb-2 transition"></i>
                                    <p class="text-xs text-slate-500 font-medium">Click to upload image</p>
                                    <p class="text-[10px] text-slate-400 mt-1">SVG, PNG, JPG (Max. 800x600px)</p>
                                </div>
                                <input type="file" name="PhotoURL" id="photoInput" accept="image/*" class="hidden" />
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: SCHEDULE -->
            <div class="lg:col-span-7 flex flex-col">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 flex-1 flex flex-col">
                    <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                                <i class="fa-regular fa-calendar-days text-ukm-blue"></i> Weekly Schedule
                            </h3>
                            <p class="text-xs text-slate-500 mt-1">Configure open hours for each day.</p>
                        </div>
                        <button type="button" onclick="applyToAll()" class="text-xs bg-white text-ukm-blue border border-ukm-blue hover:bg-ukm-blue hover:text-white font-bold px-4 py-2 rounded-lg transition shadow-sm flex items-center gap-2">
                            <i class="fa-solid fa-copy"></i> Copy Mon to All
                        </button>
                    </div>

                    <div class="flex-grow overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-4 font-bold">Weekday</th>
                                    <th class="px-4 py-4 text-center">Open/Close</th>
                                    <th class="px-4 py-4">Start Time</th>
                                    <th class="px-4 py-4">End Time</th>
                                    <th class="px-4 py-4">Slot (Min)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php 
                                foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day):
                                    $sched = $existingSchedules[$day] ?? null;
                                    $isOpen = !empty($sched);
                                    $openVal = $isOpen ? date('H:i', strtotime($sched['OpenTime'])) : '08:00';
                                    $closeVal = $isOpen ? date('H:i', strtotime($sched['CloseTime'])) : '22:00'; 
                                    $slotVal = $isOpen ? $sched['SlotDuration'] : 60;
                                ?>
                                <tr class="transition-colors hover:bg-slate-50 group <?php echo $isOpen ? '' : 'bg-slate-50/50'; ?>" id="row-<?php echo $day; ?>">
                                    <td class="px-6 py-4 font-bold text-slate-700 w-32"><?php echo $day; ?></td>
                                    
                                    <td class="px-4 py-4 text-center w-24">
                                        <div class="flex justify-center">
                                            <label class="inline-flex relative items-center cursor-pointer">
                                                <input type="checkbox" name="available_days[]" value="<?php echo $day; ?>" class="toggle-checkbox sr-only" onchange="toggleRow('<?php echo $day; ?>')" <?php echo $isOpen ? 'checked' : ''; ?>>
                                                <div class="toggle-label w-11 h-6 bg-slate-200 rounded-full border border-slate-300 transition-colors"></div>
                                                <div class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-all border border-slate-300"></div>
                                            </label>
                                        </div>
                                    </td>

                                    <td class="px-4 py-4">
                                        <input type="time" name="start_time[<?php echo $day; ?>]" id="start_<?php echo $day; ?>" value="<?php echo $openVal; ?>" 
                                            class="w-full bg-white border border-slate-200 text-slate-800 text-sm rounded-lg p-2.5 focus:border-ukm-blue focus:ring-1 focus:ring-ukm-blue outline-none font-medium disabled:bg-slate-100 disabled:text-slate-400"
                                            <?php echo !$isOpen ? 'disabled' : ''; ?>>
                                    </td>

                                    <td class="px-4 py-4">
                                        <input type="time" name="end_time[<?php echo $day; ?>]" id="end_<?php echo $day; ?>" value="<?php echo $closeVal; ?>" 
                                            class="w-full bg-white border border-slate-200 text-slate-800 text-sm rounded-lg p-2.5 focus:border-ukm-blue focus:ring-1 focus:ring-ukm-blue outline-none font-medium disabled:bg-slate-100 disabled:text-slate-400"
                                            <?php echo !$isOpen ? 'disabled' : ''; ?>>
                                    </td>

                                    <td class="px-4 py-4">
                                        <div class="relative">
                                            <input type="number" name="slot_duration[<?php echo $day; ?>]" id="slot_<?php echo $day; ?>" value="<?php echo $slotVal; ?>" min="30" step="30"
                                                class="w-24 bg-white border border-slate-200 text-slate-800 text-sm rounded-lg p-2.5 pr-8 focus:border-ukm-blue focus:ring-1 focus:ring-ukm-blue outline-none font-medium disabled:bg-slate-100 disabled:text-slate-400"
                                                <?php echo !$isOpen ? 'disabled' : ''; ?>>
                                            <span class="absolute right-3 top-2.5 text-xs text-slate-400 font-bold">m</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Manage Closures Link -->
                <?php if($isUpdate): ?>
                <div class="mt-6 bg-amber-50 border border-amber-200 rounded-xl p-5 flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center text-amber-600">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-800 text-sm">Facility Closures</h4>
                            <p class="text-xs text-slate-500">Scheduled maintenance or holidays.</p>
                        </div>
                    </div>
                    <a href="manage_closures.php" class="text-amber-700 font-bold text-sm hover:underline">Manage &rarr;</a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- FORM ACTIONS -->
        <div class="mt-10 flex items-center justify-end gap-4 border-t border-slate-200 pt-6">
            <button type="button" onclick="window.history.back()" class="px-6 py-3 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition">
                Cancel
            </button>
            <button type="submit" class="px-8 py-3 rounded-xl bg-ukm-blue text-white font-bold hover:bg-ukm-dark shadow-lg shadow-blue-900/20 transition transform active:scale-95 flex items-center gap-2">
                <i class="fa-solid fa-check-circle"></i>
                <?php echo $isUpdate ? 'Save Changes' : 'Create Facility'; ?>
            </button>
        </div>
        
    </form>
</main>

<script>
    // ID Generator
    const prefix = document.getElementById('prefixSelect');
    const numInput = document.getElementById('idNumberInput');
    const combinedInput = document.getElementById('FacilityIDCombined');
    function updateID() { if(prefix && numInput) combinedInput.value = prefix.value + numInput.value; }
    if(prefix) { prefix.addEventListener('change', updateID); numInput.addEventListener('input', updateID); updateID(); }

    // Image Preview
    const photoInput = document.getElementById('photoInput');
    const previewImg = document.getElementById('previewImg');
    if(photoInput) {
        photoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) { previewImg.src = e.target.result; }
                reader.readAsDataURL(file);
            }
        });
    }

    // Toggle Schedule
    function toggleRow(day) {
        const checkbox = document.querySelector(`input[name="available_days[]"][value="${day}"]`);
        const row = document.getElementById(`row-${day}`);
        const inputs = row.querySelectorAll('input');
        const isChecked = checkbox.checked;
        if (isChecked) {
            row.classList.remove('bg-slate-50/50');
            inputs.forEach(i => i.disabled = false);
        } else {
            row.classList.add('bg-slate-50/50');
            inputs.forEach(i => i.disabled = true);
        }
    }

    // Copy Schedule
    function applyToAll() {
        if(!confirm("Replicate Monday's schedule to all active days?")) return;
        const srcStart = document.getElementById('start_Monday').value;
        const srcEnd = document.getElementById('end_Monday').value;
        const srcSlot = document.getElementById('slot_Monday').value;
        ['Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'].forEach(day => {
            if(!document.getElementById(`start_${day}`).disabled) {
                document.getElementById(`start_${day}`).value = srcStart;
                document.getElementById(`end_${day}`).value = srcEnd;
                document.getElementById(`slot_${day}`).value = srcSlot;
            }
        });
    }
</script>


<!-- FOOTER -->
<?php include 'includes/footer.php'; ?>

</body>
</html>
