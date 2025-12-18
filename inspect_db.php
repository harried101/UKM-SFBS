<?php
require_once 'includes/db_connect.php';

echo "<h2>Table: bookings</h2>";
$res = $conn->query("SHOW CREATE TABLE bookings");
if ($row = $res->fetch_assoc()) {
    echo "<pre>" . htmlspecialchars($row['Create Table']) . "</pre>";
}

echo "<h2>Indexes</h2>";
$res = $conn->query("SHOW INDEX FROM bookings");
echo "<pre>";
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";
?>
