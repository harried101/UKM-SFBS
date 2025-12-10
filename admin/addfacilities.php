<?php
session_start();
require '../includes/db_connect.php';
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

    <!-- Facility ID Input -->
    <div class="mb-4">
        <label class="form-label">Facility ID (e.g., F001)</label>
        <input type="text" id="facilityID" class="form-control" placeholder="F001">
    </div>

    <!-- Facility Form -->
    <form action="save_facility.php" method="POST" enctype="multipart/form-data">

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

<!-- AUTO FILL SCRIPT -->
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

    fetch("fetch_facility.php?id=" + id)
        .then(response => response.json())
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
