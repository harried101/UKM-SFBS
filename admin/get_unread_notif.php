<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['UserID'])) { // Make sure session is started and variable exists
    echo json_encode(["count" => 0]);
    exit;
}

include "db_connect.php"; // Correct if in same folder as notifications.php

$userID = $_SESSION['UserID'];

$stmt = $conn->prepare("SELECT COUNT(*) AS unread FROM notifications WHERE UserID = ? AND IsRead = 0");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

echo json_encode(["count" => (int)$data['unread']]);
