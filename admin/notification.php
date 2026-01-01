<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Kuala_Lumpur');

session_start();

require_once 'includes/admin_auth.php';
require_once '../includes/config.php';
require_once '../includes/db_connect.php';

// === Session Timeout ===
$timeout_limit = SESSION_TIMEOUT_SECONDS; // 10 minutes
if (isset($_SESSION['last_activity'])) {
    $inactive = time() - $_SESSION['last_activity'];
    if ($inactive >= $timeout_limit) {
        header("Location: ../logout.php");
        exit;
    }
}
$_SESSION['last_activity'] = time();

// === Admin Auth Check ===
$session_role = strtolower($_SESSION['role'] ?? '');
if (!isset($_SESSION['logged_in']) || $session_role !== 'admin' || !isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$adminID = $_SESSION['user_id'];

// === Time Ago Function ===
function time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return round($diff/60).'m ago';
    if ($diff < 86400) return round($diff/3600).'h ago';
    return date("d M, h:i A", $timestamp);
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

// === Fetch Notifications with Facility Name and Booking StartTime ===
$stmt = $conn->prepare("
    SELECT n.NotificationID, n.Message, n.IsRead, n.CreatedAt,
           f.Name, b.StartTime
    FROM notifications n
    LEFT JOIN bookings b ON n.BookingID = b.BookingID
    LEFT JOIN facilities f ON b.FacilityID = f.FacilityID
    WHERE n.UserID = ?
    ORDER BY n.CreatedAt DESC
    LIMIT 50
");
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
<title>Admin Notifications</title>
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
<?php if (empty($notifications)): ?>
    <div class="flex flex-col items-center justify-center py-20 text-center">
        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4 text-slate-300">
            <i class="fa-regular fa-bell-slash text-2xl"></i>
        </div>
        <p class="text-slate-500 font-medium">No new notifications</p>
    </div>
<?php endif; ?>

<?php foreach ($notifications as $n):
    $isRead = $n['IsRead'];
    $readClass = $isRead ? 'opacity-60 bg-white border-slate-200' : 'notification-unread shadow-md hover:shadow-lg transition duration-200';
    $messageClass = $isRead ? 'text-slate-600' : 'text-slate-900 font-semibold';
?>
<div class="notification-item p-4 rounded-xl border <?= $readClass ?> flex gap-4 transition-all">
    <div class="flex-shrink-0 pt-1">
        <i class="fa-solid fa-circle-info text-xl text-slate-500"></i>
    </div>
    <div class="flex-grow">
        <div class="flex justify-between items-start">
            <p class="text-sm <?= $messageClass ?> whitespace-pre-line"><?= htmlspecialchars($n['Message']) ?></p>
            <span class="text-[10px] text-slate-400 ml-2"><?= time_ago($n['CreatedAt']) ?></span>
        </div>

        <?php if (!empty($n['Name']) || !empty($n['StartTime'])): ?>
        <div class="mt-2 flex flex-wrap gap-2">
            <?php if (!empty($n['Name'])): ?>
            <span class="text-[11px] bg-slate-100 text-slate-700 px-2 py-1 rounded flex items-center gap-1">
                <i class="fa-solid fa-location-dot text-[#8a0d19]"></i>
                <?= htmlspecialchars($n['Name']) ?>
            </span>
            <?php endif; ?>

            <?php if (!empty($n['StartTime'])): ?>
            <span class="text-[11px] bg-slate-100 text-slate-700 px-2 py-1 rounded flex items-center gap-1">
                <i class="fa-solid fa-clock text-[#8a0d19]"></i>
                <?= date("d M, h:i A", strtotime($n['StartTime'])) ?>
            </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>

<?php if($unreadCount > 0): ?>
<button id="markAllBtn" onclick="markAllRead()" class="mt-6 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Mark All as Read</button>
<?php endif; ?>

<script>
function markAllRead() {
    fetch(window.location.href, {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "mark_all_read=1"
    }).then(() => location.reload());
}
</script>
<script src="../assets/js/idle_timer.js.php"></script>
</body>
</html>
