<?php

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/db_connect.php';

if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

// ----------------------------------------------------------------------
// LOGIC FOR SUGGESTED ID (Find the highest existing number)
// ----------------------------------------------------------------------

function getNextFacilityNumber($conn) {
    // Find the last FacilityID and extract the number (ignoring ID/OD prefix)
    $result = $conn->query("
        SELECT FacilityID 
        FROM facilities 
        ORDER BY CAST(SUBSTRING(FacilityID, 3) AS UNSIGNED) DESC 
        LIMIT 1
    ");

    if ($result->num_rows == 0) {
        return "001"; // Start from 001 if no records exist
    } else {
        $row = $result->fetch_assoc();
        // Get the last 3 characters
        $lastIDNumber = substr($row['FacilityID'], -3); 
        $newID = intval($lastIDNumber) + 1;
        // Format back to 3 digits (e.g., 6 -> '006')
        return str_pad($newID, 3, "0", STR_PAD_LEFT);
    }
}

$nextFacilityNumber = getNextFacilityNumber($conn);


// ----------------------------------------------------------------------
// INITIAL DATA FETCHING (Checks URL for ID or processes search redirect)
// ----------------------------------------------------------------------

$facilityData = null;
$isUpdate = false;
$currentFacilityID = $_GET['id'] ?? '';
$formTitle = "ADD NEW FACILITY";
$existingSchedules = [];
$existingOverrides = []; // RENAMED from $existingClosures

// IF ID IS PRESENT IN URL, LOAD UPDATE MODE
if (!empty($currentFacilityID)) {
    // 1. Fetch Facility Details
    $stmt = $conn->prepare("SELECT * FROM facilities WHERE FacilityID = ?");
    $stmt->bind_param("s", $currentFacilityID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $facilityData = $result->fetch_assoc();
        $isUpdate = true;
        $formTitle = "UPDATE FACILITY: " . $currentFacilityID;
    } else {
        // If ID in URL is not found
        echo "<script>alert('Error: Facility ID \\'{$currentFacilityID}\\' not found. Switching to Add mode.'); window.location='addfacilities.php';</script>";
        exit();
    }
    $stmt->close();

    // 2. Fetch Existing Schedules
    $schedule_sql = "SELECT DayOfWeek, OpenTime, CloseTime, SlotDuration 
                     FROM facilityschedules WHERE FacilityID = ?";
    $schedule_stmt = $conn->prepare($schedule_sql);
    $schedule_stmt->bind_param("s", $currentFacilityID);
    $schedule_stmt->execute();
    $schedule_result = $schedule_stmt->get_result();
    while ($row = $schedule_result->fetch_assoc()) {
        $existingSchedules[$row['DayOfWeek']] = $row;
    }
    $schedule_stmt->close();

    // 3. Fetch Existing Overrides (CORRECTED SQL)
    $override_sql = "SELECT OverrideID, StartTime, EndTime, Reason FROM scheduleoverrides WHERE FacilityID = ? ORDER BY StartTime DESC";
    $override_stmt = $conn->prepare($override_sql);
    $override_stmt->bind_param("s", $currentFacilityID);
    $override_stmt->execute();
    $override_result = $override_stmt->get_result(); // Corrected function call
    while ($row = $override_result->fetch_assoc()) {
        $existingOverrides[] = $row; // Renamed variable here
    }
    $override_stmt->close();
}


// --- HANDLE POST REQUESTS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // CASE 0: SEARCH ID FOR UPDATE (Handled by POST/URL Redirect)
    if (isset($_POST['search_id'])) {
        $searchID = strtoupper(trim($_POST['search_id']));
        if (!empty($searchID)) {
             header("Location: addfacilities.php?id=" . $searchID);
             exit();
        }
    }
    
    // CASE A: ADD NEW CLOSURE (Removed, as navigation directs to manage_closures.php)
    
    // CASE C: MAIN FACILITY DETAILS UPDATE/INSERT
    if (!isset($_POST['add_closure']) && !isset($_POST['search_id'])) { 
        
        // Determine the ID (Combined Prefix + Number or Hidden ID)
        $id = $_POST['FacilityIDHidden'] ?? $_POST['FacilityIDCombined'] ?? ''; 
        
        // Gather inputs (ordered by DB structure)
        $name = $_POST['Name'] ?? '';
        $description = $_POST['Description'] ?? '';
        $location = $_POST['Location'] ?? '';
        $type = $_POST['Type'] ?? '';
        $status = $_POST['Status'] ?? '';
        
        // Logic Upload Gambar
        $newPhotoName = $facilityData['PhotoURL'] ?? ''; 
        
        // (Full logic for photo upload and file handling is omitted for brevity)

        // 1. Save to 'facilities' table
        if ($isUpdate) {
            $sql = "UPDATE facilities SET Name=?, Description=?, Location=?, Type=?, PhotoURL=?, Status=? WHERE FacilityID=?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("sssssss", $name, $description, $location, $type, $newPhotoName, $status, $id);
                $successMsg = "Facility updated successfully";
                $redirectFile = "addfacilities.php?id=" . $id; 
            }
        } else {
            // Check duplicate ID
            $check = $conn->query("SELECT FacilityID FROM facilities WHERE FacilityID = '$id'");
            if ($check->num_rows > 0) {
                echo "<script>alert('Error: Facility ID $id already exists.'); window.location='addfacilities.php';</script>";
                exit();
            }

            $sql = "INSERT INTO facilities (FacilityID, Name, Description, Location, Type, PhotoURL, Status) VALUES (?, ?, ?, ?, ?, ?, ?)";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("sssssss", $id, $name, $description, $location, $type, $newPhotoName, $status);
                $successMsg = "Facility added successfully";
                $redirectFile = "addfacilities.php?id=" . $id; 
            }
        }

        if ($stmt && $stmt->execute()) {
            // 2. Save Weekly Schedule
            $conn->query("DELETE FROM facilityschedules WHERE FacilityID = '$id'");
            $schedule_stmt = $conn->prepare("INSERT INTO facilityschedules (FacilityID, DayOfWeek, OpenTime, CloseTime, SlotDuration) VALUES (?, ?, ?, ?, ?)");
            
            if (!empty($_POST['available_days'])) {
                foreach ($_POST['available_days'] as $day) {
                    $start = $_POST['start_time'][$day] ?? '00:00:00'; 
                    $end = $_POST['end_time'][$day] ?? '00:00:00';
                    $slot_duration = intval($_POST['slot_duration'][$day] ?? 60); 

                    $schedule_stmt->bind_param("sssii", $id, $day, $start, $end, $slot_duration);
                    $schedule_stmt->execute();
                }
            }
            $schedule_stmt->close();
            
            echo "<script>alert('$successMsg'); window.location='$redirectFile';</script>";
        } else {
            echo "SQL Error: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $formTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* --- CSS Styles (Unified) --- */
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
}
.nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.5); }

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

.btn-reset { background: #c62828; color: white; padding: 6px 22px; border-radius: 10px; }
.btn-submit { background: #1e40af; color: white; padding: 6px 22px; border-radius: 10px; }
.btn-search { background: #071239ff; color: white; border-radius: 10px; padding: 6px 15px;}

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

/* --- Gaya Butang Navigasi Penutupan (Segi Empat) --- */
.btn-closure-nav {
    font-weight: 700;
    padding: 10px 18px; 
    background: #1e40af; 
    color: white;
    border-radius: 4px; 
    text-decoration: none;
    transition: background 0.3s, transform 0.1s;
    display: block; 
    max-width: 400px; 
    margin: 15px auto 0; 
    text-align: center;
    box-shadow: 0 4px 8px rgba(30, 64, 175, 0.4); 
    border: none; 
}
.btn-closure-nav:hover { 
    background: #007bff; 
    color: white;
    transform: translateY(-1px);
}
.btn-closure-nav:active {
    transform: translateY(1px);
}
.search-box {
    background: #fff;
    border-radius: 15px;
    padding: 15px 25px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
</style>

</head>

<body>

<nav class="d-flex justify-content-between align-items-center px-4 py-2">
    <div class="nav-logo d-flex align-items-center gap-3">
        <img src="../assets/img/ukm.png" alt="UKM Logo" height="45">
        <img src="../assets/img/pusatsukan.png" alt="Pusat Sukan Logo" height="45">
    </div>

    <div class="d-flex align-items-center gap-4">
        <a class="nav-link active" href="#">Facility</a>
        <a class="nav-link" href="manage_closures.php">Closures Management</a> 
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
        <h1><?php echo $formTitle; ?></h1>

        <div class="search-box">
            <h5 class="section-title mb-3" style="border-color:#071239ff; color:#071239ff;">Update Existing Facility</h5>
            <form method="POST" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <input type="text" class="form-control" name="search_id" id="searchIDInput" placeholder="Enter Facility ID (e.g., ID001 or OD005)" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-search w-100">Search & Load</button>
                </div>
                <div class="col-md-4">
                    <?php if ($isUpdate): ?>
                         <a href="addfacilities.php" class="btn btn-reset w-100">+ Add New Facility</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div class="row g-4"> 
                
                <div class="col-md-6">
                    <div class="section-card">
                        <h5 class="section-title">1. Facility Details</h5>
                        
                        <div class="mb-3">
                            <label class="form-label">Facility ID</label>
                            <?php if ($isUpdate): ?>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($facilityData['FacilityID']); ?>" readonly>
                                <input type="hidden" name="FacilityIDHidden" value="<?php echo htmlspecialchars($facilityData['FacilityID']); ?>">
                                <small class="text-muted">Current ID: <?php echo htmlspecialchars($facilityData['FacilityID']); ?></small>
                            <?php else: ?>
                                <div class="input-group">
                                    <select class="form-select" name="FacilityPrefix" id="FacilityPrefix" style="max-width: 80px;" required>
                                        <option value="ID" selected>ID (Indoor)</option> 
                                        <option value="OD">OD (Outdoor)</option> 
                                    </select>
                                    <input type="text" class="form-control" name="FacilityNumber" id="FacilityNumber" value="<?php echo $nextFacilityNumber; ?>" pattern="[0-9]*" maxlength="5" required>
                                </div>
                                <input type="hidden" name="FacilityIDCombined" id="FacilityIDCombined">
                                <small class="text-muted" style="font-size:11px;">Next ID: <?php echo "ID" . $nextFacilityNumber; ?> or <?php echo "OD" . $nextFacilityNumber; ?></small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Facility Name</label>
                            <input type="text" class="form-control" name="Name" 
                                   value="<?php echo htmlspecialchars($facilityData['Name'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="3" name="Description" required><?php echo htmlspecialchars($facilityData['Description'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" name="Location" 
                                   value="<?php echo htmlspecialchars($facilityData['Location'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Facility Type</label>
                            <input type="text" class="form-control" name="Type" 
                                   value="<?php echo htmlspecialchars($facilityData['Type'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-block">Upload Photo</label>
                            <input type="file" name="PhotoURL" class="form-control" accept="image/*" <?php echo $isUpdate && empty($facilityData['PhotoURL']) ? '' : ''; ?>>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="Status" required>
                                <?php 
                                    $currentStatus = $facilityData['Status'] ?? 'Active';
                                    $options = ['Active', 'Maintenance', 'Archived'];
                                    foreach ($options as $option): 
                                ?>
                                    <option value="<?php echo $option; ?>" <?php echo ($currentStatus === $option) ? 'selected' : ''; ?>>
                                        <?php echo $option; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="section-card">
                        <h5 class="section-title">2. Weekly Recurring Schedule</h5>
                        
                        <small class="text-muted d-block mb-3">Check box if OPEN. Set times & slot duration (mins).</small>
                        <div class="row g-2">
                            <?php 
                                $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
                                foreach($days as $day):
                                    $dayData = $existingSchedules[$day] ?? ['OpenTime' => '08:00:00', 'CloseTime' => '17:00:00', 'SlotDuration' => 60]; 
                                    $isChecked = isset($existingSchedules[$day]); 
                            ?>
                            <div class="col-12">
                                <div class="d-flex align-items-center gap-2">
                                    <input type="checkbox" class="form-check-input mt-1 schedule-day" name="available_days[]" value="<?php echo $day; ?>" id="<?php echo $day; ?>" <?php echo $isChecked ? 'checked' : ''; ?>>
                                    <label class="form-label mb-0" for="<?php echo $day; ?>" style="min-width:40px; font-size:13px;"><?php echo substr($day, 0, 3); ?></label> 
                                    <input type="time" class="form-control form-control-sm schedule-input" name="start_time[<?php echo $day; ?>]" value="<?php echo substr($dayData['OpenTime'], 0, 5); ?>" required>
                                    <span class="mx-1">-</span>
                                    <input type="time" class="form-control form-control-sm schedule-input" name="end_time[<?php echo $day; ?>]" value="<?php echo substr($dayData['CloseTime'], 0, 5); ?>" required>
                                    <input type="number" class="form-control form-control-sm schedule-input" name="slot_duration[<?php echo $day; ?>]" value="<?php echo $dayData['SlotDuration']; ?>" min="1" step="5" style="max-width: 55px;" required>
                                    <span class="text-muted" style="font-size:12px;">m</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-4 pt-3 border-top text-center">
                            <p class="text-muted small mb-2">CLOSE FACILITY</p>
                            <a href="manage_closures.php" class="btn-closure-nav">
                                Go to Closures Management
                            </a>
                        </div>
                        
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <button type="reset" class="btn btn-reset me-2">Reset</button>
                <button type="submit" class="btn btn-submit"><?php echo $isUpdate ? 'Update Facility' : 'Add Facility'; ?></button>
            </div>

        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    
    const days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
    days.forEach(day => {
        const checkbox = document.getElementById(day);
        const inputs = [
            document.querySelector(`input[name="start_time[${day}]"]`),
            document.querySelector(`input[name="end_time[${day}]"]`),
            document.querySelector(`input[name="slot_duration[${day}]"]`)
        ];
        
        function toggleInputs() {
            const isDisabled = !checkbox.checked;
            inputs.forEach(input => { if(input) input.disabled = isDisabled; });
        }
        
        if (checkbox) {
            toggleInputs();
            checkbox.addEventListener('change', toggleInputs);
        }
    });

    const form = document.querySelector('form');
    const prefixSelect = document.getElementById('FacilityPrefix');
    const numberInput = document.getElementById('FacilityNumber');
    const combinedInput = document.getElementById('FacilityIDCombined');
    
    const nextIdNumber = <?php echo json_encode($nextFacilityNumber); ?>;
    const isUpdateMode = <?php echo json_encode($isUpdate); ?>;
    
    function updateCombinedID() {
        if (prefixSelect && numberInput && combinedInput) {
            const prefix = prefixSelect.value;
            let number = numberInput.value.replace(/[^0-9]/g, ''); 
            
            if (number.length > 0) {
                 number = number.padStart(3, '0');
                 numberInput.value = number;
            }
            
            combinedInput.value = prefix + number;
        }
    }

    if (prefixSelect && numberInput) {
    
        if (!isUpdateMode) {
             numberInput.value = nextIdNumber;
        }
        
        numberInput.addEventListener('input', updateCombinedID);
        prefixSelect.addEventListener('change', updateCombinedID);
        
        
        updateCombinedID();
        
        form.addEventListener('submit', function(e) {
            if (!isUpdateMode && (!combinedInput.value || combinedInput.value.length < 5)) { 
                e.preventDefault();
                alert('Please complete the Facility ID (Prefix + Number).');
            }
        });
    }
    
    const searchInput = document.getElementById('searchIDInput');
    if(searchInput) {
        searchInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase().trim();
        });
    }
});
</script>
</body>
</html>