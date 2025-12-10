<?php
session_start();
require '../includes/db_connect.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $facilityID = $_POST['FacilityID'] ?? '';
    $name = $_POST['Name'] ?? '';
    $description = $_POST['Description'] ?? '';
    $location = $_POST['Location'] ?? '';
    $type = $_POST['Type'] ?? 'General';
    $capacity = $_POST['Capacity'] ?? NULL;
    $status = $_POST['Status'] ?? 'Active';
    $photoURL = NULL;

    // Handle photo upload
    if (isset($_FILES['Photo']) && $_FILES['Photo']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['Photo']['tmp_name'];
        $filename = time() . '_' . basename($_FILES['Photo']['name']);
        $uploadDir = '../uploads/';
        if (move_uploaded_file($tmp_name, $uploadDir . $filename)) {
            $photoURL = $filename;
        }
    }

    if ($facilityID) {
        // Update existing facility
        $sql = "UPDATE facilities SET Name=?, Description=?, Location=?, Type=?, Capacity=?, Status=?, PhotoURL=? WHERE FacilityID=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssissi", $name, $description, $location, $type, $capacity, $status, $photoURL, $facilityID);
        $stmt->execute();
        $msg = "Facility updated successfully!";
    } else {
        // Insert new facility
        $sql = "INSERT INTO facilities (Name, Description, Location, Type, Capacity, Status, PhotoURL) VALUES (?,?,?,?,?,?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssiss", $name, $description, $location, $type, $capacity, $status, $photoURL);
        $stmt->execute();
        $msg = "New facility added successfully!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add / Update Facility</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4">

<div class="container">
    <h2 class="mb-4">Add / Update Facility</h2>

    <?php if(isset($msg)) echo "<div class='alert alert-success'>$msg</div>"; ?>

    <!-- Facility ID input -->
    <div class="mb-4">
        <label class="form-label">Facility ID (e.g., F001 or 1)</label>
        <input type="text" id="facilityID" class="form-control" placeholder="F001 or 1">
    </div>

    <!-- Facility form -->
    <form action="" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="FacilityID" id="hiddenFacilityID">

        <div class="mb-3">
            <label class="form-label">Facility Name</label>
            <input type="text" name="Name" id="nameField" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="Description" id="descriptionField" class="form-control"></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Location</label>
            <input type="text" name="Location" id="locationField" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Type</label>
            <input type="text" name="Type" id="typeField" class="form-control" value="General">
        </div>

        <div class="mb-3">
            <label class="form-label">Capacity</label>
            <input type="number" name="Capacity" id="capacityField" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="Status" id="statusField" class="form-control">
                <option value="Active">Active</option>
                <option value="Maintenance">Maintenance</option>
                <option value="Archived">Archived</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Photo</label><br>
            <img id="photoPreview" src="" alt="No Photo" width="200" style="border:1px solid #ccc; padding:5px; margin-bottom:10px;"><br>
            <input type="file" name="Photo" class="form-control">
        </div>

        <button type="submit" class="btn btn-primary">Save Facility</button>
    </form>
</div>

<script>
document.getElementById("facilityID").addEventListener("change", function () {
    let id = this.value.trim();
    if (id === "") {
        document.getElementById("hiddenFacilityID").value = "";
        document.getElementById("nameField").value = "";
        document.getElementById("descriptionField").value = "";
        document.getElementById("locationField").value = "";
        document.getElementById("typeField").value = "General";
        document.getElementById("capacityField").value = "";
        document.getElementById("statusField").value = "Active";
        document.getElementById("photoPreview").src = "";
        return;
    }

    // Convert F001 → 1 if necessary
    let numericId = parseInt(id.replace(/[^0-9]/g, ''));

    fetch("fetch_facility.php?id=" + numericId)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert("Facility not found");
                return;
            }
            document.getElementById("hiddenFacilityID").value = data.FacilityID;
            document.getElementById("nameField").value = data.Name ?? "";
            document.getElementById("descriptionField").value = data.Description ?? "";
            document.getElementById("locationField").value = data.Location ?? "";
            document.getElementById("typeField").value = data.Type ?? "General";
            document.getElementById("capacityField").value = data.Capacity ?? "";
            document.getElementById("statusField").value = data.Status ?? "Active";
            document.getElementById("photoPreview").src = data.PhotoURL ? "../uploads/" + data.PhotoURL : "";
        })
        .catch(err => console.error("Fetch error:", err));
});
</script>

</body>
</html>
