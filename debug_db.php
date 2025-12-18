<?php
require_once 'includes/db_connect.php';

// Check Facility
echo "--- Facilities ---\n";
$res = $conn->query("SELECT FacilityID, Name FROM facilities");
while($row = $res->fetch_assoc()) {
    echo "ID: " . $row['FacilityID'] . " | Name: " . $row['Name'] . "\n";
    
    // Check Schedule for this facility
    $sched = $conn->query("SELECT * FROM facilityschedules WHERE FacilityID = '" . $conn->real_escape_string($row['FacilityID']) . "'");
    echo "  -> Schedules Found: " . $sched->num_rows . "\n";
    while($s = $sched->fetch_assoc()) {
        echo "     Day: " . $s['DayOfWeek'] . " (" . $s['OpenTime'] . " - " . $s['CloseTime'] . ")\n";
    }
}
?>
