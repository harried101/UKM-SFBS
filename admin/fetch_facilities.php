<?php
require '../includes/db_connect.php';

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'missing id']);
    exit;
}

$id = $_GET['id'];
$id = preg_replace('/[^0-9]/', '', $id); // remove non-numeric
$id = intval($id);

$stmt = $conn->prepare("SELECT * FROM facilities WHERE FacilityID = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'not found']);
    exit;
}

echo json_encode($result->fetch_assoc());
?>
