<?php
require '../includes/db_connect.php';

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'missing id']);
    exit;
}

$id = intval($_GET['id']);

// Debug: check DB connection and query
if($conn->connect_error){
    die(json_encode(['error'=>'DB connection failed']));
}

$sql = "SELECT * FROM facilities WHERE FacilityID = $id";
$result = $conn->query($sql);

if(!$result){
    die(json_encode(['error'=>'Query failed', 'sql'=>$sql, 'err'=>$conn->error]));
}

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'not found', 'sql'=>$sql]);
    exit;
}

echo json_encode($result->fetch_assoc());
?>
