<?php
require '../includes/db_connect.php';

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'missing id']);
    exit;
}

// Strip non-numeric characters (F001 → 1)
$id = preg_replace('/[^0-9]/', '', $_GET['id']);
$id = intval($id);

// Query the database
$stmt = $conn->prepare("SELECT * FROM facilities WHERE FacilityID = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    echo json_encode(['error' => 'not found']);
    exit;
}

echo json_encode($result->fetch_assoc());
?>
