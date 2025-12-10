<?php
require_once '../includes/db_connect.php';

$id = $_GET['id'] ?? '';
$id = trim($id);

header('Content-Type: application/json');

if ($id === '') {
    echo json_encode(["error" => "empty id"]);
    exit;
}

$sql = "SELECT FacilityID, Name, Description, Location, Type, Capacity, PhotoURL, Status FROM facilities WHERE FacilityID = ? LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["error" => "db prepare failed"]);
    exit;
}
$stmt->bind_param("s", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    echo json_encode($row);
} else {
    echo json_encode(["error" => "not found"]);
}
$stmt->close();
?>
