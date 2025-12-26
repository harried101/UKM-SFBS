<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['UserID']) || ($_SESSION['Role'] ?? '') != 'Admin') {
    echo json_encode(["count"=>0]);
    exit;
}

include "../database"; // Adjust if database file is outside admin folder

$userID = $_SESSION['UserID'];

$stmt = $conn->prepare("SELECT COUNT(*) AS unread FROM notifications WHERE UserID=? AND IsRead=0");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

echo json_encode(["count" => (int)$data['unread']]);
