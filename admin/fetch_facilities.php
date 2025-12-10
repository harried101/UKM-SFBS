<?php
require '../includes/db_connect.php';

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'missing id']);
    exit;
}

$id = intval($_GET['id']);  // ensure it's number

$sql = "SELECT * FROM facilities WHERE FacilityID = $id";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'not found']);
    exit;
}

echo json_encode($result->fetch_assoc());
?>
