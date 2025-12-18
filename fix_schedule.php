<?php
require_once 'includes/db_connect.php';

$fid = 'ID002'; // Court Tenis
echo "Fixing schedule for $fid...\n";

// Clear existing just in case
$conn->query("DELETE FROM facilityschedules WHERE FacilityID = '$fid'");

// Insert Mon(1) to Sun(0/6) - treating 0 as Sunday
$days = [0,1,2,3,4,5,6]; // 0=Sunday
$sql = "INSERT INTO facilityschedules (FacilityID, DayOfWeek, OpenTime, CloseTime, SlotDuration) VALUES (?, ?, '08:00:00', '22:00:00', 60)";

$stmt = $conn->prepare($sql);
if(!$stmt) die("Prepare failed: " . $conn->error);

foreach($days as $d) {
    echo "  -> Adding Day $d... ";
    $stmt->bind_param("si", $fid, $d);
    if($stmt->execute()) echo "OK\n";
    else echo "Error: " . $stmt->error . "\n";
}

echo "Done. Please check the booking page again.\n";
?>
