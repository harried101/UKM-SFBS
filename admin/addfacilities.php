<?php
session_start();

// ----------------------------
// ACCESS CONTROL
// ----------------------------
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

// ----------------------------
// DATABASE CONNECTION
// ----------------------------
require_once '../includes/db_connect.php';

if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

// ----------------------------
// AUTO GENERATE FACILITY ID
// Format: F001, F002, F003 ...
// ----------------------------
function generateFacilityID($conn) {
    $result = $conn->query("SELECT FacilityID FROM facilities ORDER BY FacilityID DESC LIMIT 1");
    if ($result->num_rows == 0) {
        return "F001";
    } else {
        $row = $result->fetch_assoc();
        $lastID = intval(substr($row['FacilityID'], 1));
        $newID = $lastID + 1;
        return "F" . str_pad($newID, 3, "0", STR_PAD_LEFT);
    }
}

$newFacilityID = generateFacilityID($conn);

// ----------------------------
// FORM SUBMISSION LOGIC
// ----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['FacilityID'] ?? '';
    $name = $_POST['Name'] ?? '';
    $description = $_POST['Description'] ?? '';
    $location = $_POST['Location'] ?? '';
    $type = $_POST['Type'] ?? '';
    $capacity = $_POST['Capacity'] ?? '';
    $status = $_POST['Status'] ?? '';

    // ---- Handle File Upload ----
    $newPhotoName = '';
    if (!empty($_FILES['PhotoURL']['name'])) {
        $photoTmp = $_FILES['PhotoURL']['tmp_name'];
        $photoName = $_FILES['PhotoURL']['name'];
        $ext = pathinfo($photoName, PATHINFO_EXTENSION);
        
        // Basic validation for image extension
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array(strtolower($ext), $allowed)) {
            $newPhotoName = $id . "_" . time() . "." . $ext;
            $uploadDir = "uploads/";
            
            // Ensure upload directory exists
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            if (!move_uploaded_file($photoTmp, $uploadDir . $newPhotoName)) {
                die("Error uploading image.");
            }
        } else {
            echo "<script>alert('Invalid file type. Only JPG, PNG, and GIF allowed.');</script>";
        }
    }

    //Insert into database
    $sql = "INSERT INTO facilities 
            (FacilityID, Name, Description, Location, Type, Capacity, PhotoURL, Status) 
            VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        
        $stmt->bind_param("sssssiss", $id, $name, $description, $location, $type, $capacity, $newPhotoName, $status);
        
        if ($stmt->execute()) {
            echo "<script>alert('Facility added successfully'); window.location='addfacilities.php';</script>";
        } else {
            echo "SQL Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Prepare Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Facility</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: url('../assets/img/background.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    font-family: 'Poppins', sans-serif;
}

/* NAVBAR */
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

.nav-logo img {
    height: 65px;
}

.nav-link {
    color: #071239ff;
    font-weight: 600;
    padding: 8px 18px;
    border-radius: 12px;
    transition: 0.3s ease;
}

.nav-link:hover,
.nav-link.active {
    background: rgba(255,255,255,0.5);
}

.main-box {
    background: #bfd9dc;
    border-radius: 25px;
    padding: 30px 40px;
    max-width: 600px;
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
}

.form-control, 
.form-select, 
textarea {
    border-radius: 12px;
    padding: 6px 12px;
    font-size: 14px;
    color: #071239ff;
}

.upload-box {
    background: white;
    width: 180px;
    height: 180px;
    border-radius: 14px;
    border: 2px dashed #ccc;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    cursor: pointer;
    margin: auto;
}

.btn-reset {
    background: #c62828;
    color: white;
    padding: 6px 22px;
    border-radius: 10px;
}

.btn-submit {
    background: #1e40af;
    color: white;
    padding: 6px 22px;
    border-radius: 10px;
}

/* Status colors */
.status-active { background:#2e7d32; color:white; }
.status-maintenance { background:#f9a825; color:black; }
.status-archived { background:#b71c1c; color:white; }
</style>

</head>
<body>

<!-- NAVBAR -->
<nav class="d-flex justify-content-between align-items-center px-4 py-2">
    <div class="nav-logo d-flex align-items-center gap-3">
        <img src="../assets/img/ukm.png" alt="UKM Logo" height="45">
        <img src="../assets/img/pusatsukan.png" alt="Pusat Sukan Logo" height="45">
    </div>

    <div class="d-flex align-items-center gap-4">
        <a class="nav-link active" href="#">Facility</a>
        <a class="nav-link" href="#">Booking</a>
        <a class="nav-link" href="#">Report</a>

        <div class="d-flex align-items-center gap-1">
            <img src="../assets/img/user.png" class="rounded-circle" style="width:45px; height:45px;">
            <span class="fw-bold" style="color:#071239ff;"><?php echo htmlspecialchars($_SESSION['user_id'] ?? 'User'); ?></span>
        </div>
    </div>
</nav>

<!-- FORM -->
<div class="container">
    <div class="main-box position-relative">
        <h1>ADD NEW FACILITY</h1>

        <form method="POST" enctype="multipart/form-data">
            <div class="row g-3 justify-content-center">
                <div class="col-md-8">

                    <div class="mb-2">
                        <label class="form-label">Facility ID</label>
                        <input type="text" class="form-control" name="FacilityID" value="<?php echo $newFacilityID; ?>" readonly>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Facility Name</label>
                        <input type="text" class="form-control" name="Name" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" rows="3" name="Description" required></textarea>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" name="Location" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Facility Type</label>
                        <input type="text" class="form-control" name="Type" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Capacity</label>
                        <input type="number" class="form-control" name="Capacity" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label d-block">Upload Photo</label>
                        <input type="file" name="PhotoURL" class="form-control" accept="image/*">
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="Status" required>
                            <option value="active">Active</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>

                    <div class="text-center mt-4">
                        <button type="reset" class="btn btn-reset me-2">Reset</button>
                        <button type="submit" class="btn btn-submit">Submit</button>
                    </div>

                </div>
            </div>
        </form>

    </div>
</div>

</body>
</html>
