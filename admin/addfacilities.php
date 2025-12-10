<?php
session_start();

// SECURITY CHECK: Only Admin allowed
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/db_connect.php';

// Generate new FacilityID
$sql = "SELECT FacilityID FROM facilities ORDER BY CAST(SUBSTRING(FacilityID,2) AS UNSIGNED) DESC LIMIT 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $lastID = $result->fetch_assoc()['FacilityID'];
    $num = (int)substr($lastID, 1) + 1;
    $newFacilityID = "F" . str_pad($num, 3, "0", STR_PAD_LEFT);
} else {
    $newFacilityID = "F001";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Facilities</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background:#f5f7f8; }
        .card { max-width:900px; margin:40px auto; }
        #photoPreview { width:150px; margin-top:10px; display:none; border-radius:8px; }
    </style>
</head>
<body>

<div class="container">
    <div class="card shadow p-4">
        <h3 class="mb-4">Add / Edit Facility</h3>

        <!-- Note: form posts to a processing script. If you used single-file processing, adapt accordingly -->
        <form action="addfacilities_process.php" method="POST" enctype="multipart/form-data">

            <!-- Facility ID -->
            <div class="mb-3">
                <label class="form-label">Facility ID</label>
                <input type="text" class="form-control" name="FacilityID" id="facilityIDInput" value="<?php echo htmlspecialchars($newFacilityID); ?>" required>
                <div class="form-text">Type an existing ID (e.g. F001) to auto-fill fields for editing.</div>
            </div>

            <!-- Name -->
            <div class="mb-3">
                <label class="form-label">Facility Name</label>
                <input type="text" class="form-control" name="Name" id="nameField" required>
            </div>

            <!-- Description -->
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="Description" id="descriptionField" rows="3" required></textarea>
            </div>

            <!-- Location -->
            <div class="mb-3">
                <label class="form-label">Location</label>
                <input type="text" class="form-control" name="Location" id="locationField" required>
            </div>

            <!-- Type -->
            <div class="mb-3">
                <label class="form-label">Type</label>
                <input type="text" class="form-control" name="Type" id="typeField" required>
            </div>

            <!-- Capacity -->
            <div class="mb-3">
                <label class="form-label">Capacity</label>
                <input type="number" class="form-control" name="Capacity" id="capacityField" required>
            </div>

            <!-- Status -->
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select class="form-select" name="Status" id="statusField" required>
                    <option value="">-- Select Status --</option>
                    <option value="Active">Active</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Archived">Archived</option>
                </select>
            </div>

            <!-- Photo -->
            <div class="mb-3">
                <label class="form-label">Facility Photo</label>
                <input type="file" class="form-control" name="PhotoURL" id="photoInput" accept="image/*">
                <img id="photoPreview" alt="photo preview">
            </div>

            <div class="d-flex gap-2">
                <button type="reset" class="btn btn-secondary">Reset</button>
                <button type="submit" class="btn btn-primary">Save Facility</button>
            </div>
        </form>
    </div>
</div>

<script>
    // auto-fill using fetch_facility.php
    document.getElementById('facilityIDInput').addEventListener('keyup', function () {
        const fid = this.value.trim();
        if (!fid) return;

        fetch('fetch_facility.php?id=' + encodeURIComponent(fid))
            .then(res => res.json())
            .then(data => {
                if (!data || data.error) {
                    // not found — clear fields or leave as-is
                    // Optionally: clear fields when not found
                    // document.getElementById('nameField').value = '';
                    return;
                }

                document.getElementById('nameField').value = data.Name ?? '';
                document.getElementById('descriptionField').value = data.Description ?? '';
                document.getElementById('locationField').value = data.Location ?? '';
                document.getElementById('typeField').value = data.Type ?? '';
                document.getElementById('capacityField').value = data.Capacity ?? '';
                document.getElementById('statusField').value = data.Status ?? '';

                if (data.PhotoURL) {
                    const img = document.getElementById('photoPreview');
                    img.src = 'uploads/' + data.PhotoURL;
                    img.style.display = 'block';
                }
            })
            .catch(err => {
                console.error('Fetch error', err);
            });
    });

    // Optional: show chosen local image preview before upload
    document.getElementById('photoInput').addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (!file) return;
        const img = document.getElementById('photoPreview');
        img.src = URL.createObjectURL(file);
        img.style.display = 'block';
    });
</script>

</body>
</html>
