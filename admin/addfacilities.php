<?php
session_start();
require '../includes/db_connect.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Facilities</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body class="p-4">

<div class="container">
    <h2 class="mb-4">Add Facility</h2>

    <!-- Select Facility to Autofill -->
    <div class="mb-4">
        <label class="form-label">Select Facility</label>
        <select id="facilityID" class="form-control">
            <option value="">-- Select Facility --</option>

            <?php
            $sql = "SELECT FacilityID, Name FROM facilities ORDER BY FacilityID ASC";
            $result = $conn->query($sql);

            while ($row = $result->fetch_assoc()) {
                $fid = $row['FacilityID'];
                $fname = $row['Name'];
                echo "<option value='$fid'>F0$fid - $fname</option>";
            }
            ?>
        </select>
    </div>

    <!-- Facility Form -->
    <form>

        <div class="mb-3">
            <label class="form-label">Facility Name</label>
            <input type="text" id="nameField" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea id="descriptionField" class="form-control"></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Location</label>
            <input type="text" id="locationField" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Type</label>
            <input type="text" id="typeField" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Capacity</label>
            <input type="number" id="capacityField" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Status</label>
            <select id="statusField" class="form-control">
                <option value="Active">Active</option>
                <option value="Maintenance">Maintenance</option>
                <option value="Archived">Archived</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Photo</label><br>
            <img id="photoPreview" src="" alt="No Photo" width="200" style="border:1px solid #ccc; padding:5px;">
        </div>

    </form>
</div>


<!-- AUTO FILL SCRIPT -->
<script>
document.getElementById("facilityID").addEventListener("change", function () {
    let id = this.value;

    if (id === "") return;

    fetch("fetch_facility.php?id=" + id)
        .then(response => response.json())
        .then(data => {

            if (data.error) {
                alert("Facility not found");
                return;
            }

            document.getElementById("nameField").value = data.Name ?? "";
            document.getElementById("descriptionField").value = data.Description ?? "";
            document.getElementById("locationField").value = data.Location ?? "";
            document.getElementById("typeField").value = data.Type ?? "";
            document.getElementById("capacityField").value = data.Capacity ?? "";
            document.getElementById("statusField").value = data.Status ?? "Active";

            // Show photo if available
            if (data.PhotoURL && data.PhotoURL !== "") {
                document.getElementById("photoPreview").src = data.PhotoURL;
            } else {
                document.getElementById("photoPreview").src = "";
            }
        });
});
</script>

</body>
</html>
