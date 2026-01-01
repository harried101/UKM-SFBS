<?php
// === PHP Configuration ===
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Kuala_Lumpur');

require_once 'includes/admin_auth.php';

// === DB Connection ===
require_once '../includes/db_connect.php';

// $adminID and $adminName are already set by admin_auth.php

// === Time Ago Function ===
function time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return round($diff/60).' minutes ago';
    if ($diff < 86400) return round($diff/3600).' hours ago';
    return date("d M Y, h:i A", $timestamp);
}

// === AJAX: Mark All as Read ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    header('Content-Type: application/json');
    $stmt = $conn->prepare("UPDATE notifications SET IsRead=1 WHERE UserID=? AND IsRead=0");
    $stmt->bind_param("i", $adminID);
    $stmt->execute();
    echo json_encode(['success'=>true, 'count'=>$stmt->affected_rows]);
    exit();
}

// === Fetch Notifications ===
$stmt = $conn->prepare("SELECT NotificationID, Message, IsRead, CreatedAt
                        FROM notifications
                        WHERE UserID=? 
                        ORDER BY CreatedAt DESC LIMIT 50");
$stmt->bind_param("i", $adminID);
$stmt->execute();
$res = $stmt->get_result();

$notifications = [];
$unreadCount = 0;
while ($row = $res->fetch_assoc()) {
    $notifications[] = $row;
    if ($row['IsRead'] == 0) $unreadCount++;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Notifications - Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
.notification-unread { border-left: 4px solid #8a0d19; background-color: #fef2f2; }
.notification-item:hover { background-color: #f1f5f9; }
</style>
</head>
<body class="flex flex-col min-h-screen">

<?php
$nav_active = 'notifications'; 
include 'includes/navbar.php';
?>

<main class="flex-grow container mx-auto px-6 py-8 max-w-2xl">
<h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 ml-1">Recent Notifications</h4>

<div class="space-y-4" id="notificationList">
<?php if (count($notifications) === 0): ?>
    <div class="flex flex-col items-center justify-center py-20 text-center">
        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4 text-slate-300">
            <i class="fa-regular fa-bell-slash text-2xl"></i>
        </div>
        <p class="text-slate-500 font-medium">No new notifications</p>
    </div>
<?php endif; ?>

<?php foreach ($notifications as $n):
    $isRead = $n['IsRead'];
    $readClass = $isRead ? 'opacity-60 bg-white border-slate-200' : 'notification-unread border-[#8a0d19] shadow-md hover:shadow-lg transition duration-200';
    $messageClass = $isRead ? 'text-slate-600' : 'text-slate-900 font-medium';
    $timeClass = $isRead ? 'text-slate-400' : 'text-[#8a0d19] font-semibold';
    $link = $n['BookingID'] ? "bookinglist.php?id=".$n['BookingID'] : "#";
?>
<a href="<?= $link ?>" class="block notification-item p-4 rounded-xl border <?= $readClass ?> flex gap-4 items-start">
    <div class="flex-shrink-0 pt-1">
        <i class="fa-solid fa-circle-info text-xl text-slate-500"></i>
    </div>
    <div class="flex-grow">
        <div class="flex justify-between items-start">
            <p class="text-sm <?= $messageClass ?> whitespace-pre-line leading-relaxed"><?= htmlspecialchars($n['Message']) ?></p>
            <span class="text-[10px] <?= $timeClass ?> ml-4 flex-shrink-0 pt-1" title="<?= date("d M Y, h:i A", strtotime($n['CreatedAt'])) ?>">
                <?= time_ago($n['CreatedAt']) ?>
            </span>
        </div>
    </div>
</a>
<?php endforeach; ?>
</div>

<?php if(count($notifications) > 0): ?>
<button id="markAllBtn" onclick="markAllRead()" class="mt-6 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Mark All as Read</button>
<?php endif; ?>

<script>
// Mark All as Read
function markAllRead() {
    const btn = document.getElementById('markAllBtn');
    if (btn.disabled) return;
    btn.disabled = true;
    btn.textContent = 'Updating...';

    fetch("notification.php", {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: "mark_all_read=1"
    })
    .then(res => res.json())
    .then(() => location.reload())
    .catch(()=> location.reload());
}
</script>

<script src="../assets/js/idle_timer.js.php"></script>
</body>
</html>
