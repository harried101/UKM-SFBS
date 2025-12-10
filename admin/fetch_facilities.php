<?php
require '../includes/db_connect.php';

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'missing id']);
    exit;
}

$id = intval($_GET['id']); // FacilityID is int

// Use prepared statement
$stmt = $conn->prepare("SELECT * FROM facilities WHERE FacilityID = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'not found']);
    exit;
}

// Fetch data and return JSON
$data = $result->fetch_assoc();
echo json_encode($data);
?>
