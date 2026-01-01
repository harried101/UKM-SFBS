<?php
require_once 'includes/db_connect.php';
$result = $conn->query("SHOW COLUMNS FROM notifications");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
