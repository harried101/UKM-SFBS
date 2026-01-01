<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Kuala_Lumpur');

session_start();
$timeout_limit = 1800; 

if (isset($_SESSION['last_activity'])) {
    $seconds_inactive = time() - $_SESSION['last_activity'];
    if ($seconds_inactive >= $timeout_limit) {
        header("Location: ../logout.php");
        exit;
    }
}
$_SESSION['last_activity'] = time();

require_once '../includes/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'Student' || !isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$userIdentifier = $_SESSION['user_id'];
$userStmt = $conn->prepare("SELECT UserID FROM users WHERE UserIdentifier = ? LIMIT 1");
$userStmt->bind_param("s", $userIdentifier);
$userStmt->execute();
$userResult = $userStmt->get_result();

if ($userResult->num_rows === 0) die("User not found.");
$userRow = $userResult->fetch_assoc();
$userID = (int)$userRow['UserID'];

// Time Ago Helper
function time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return round($diff / 60) . 'm ago';
    if ($diff < 86400) return round($diff / 3600) . 'h ago';
    return date("d M", $timestamp);
}

// Mark All Read Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    header('Content-Type: application/json');
    $upd = $conn->prepare("UPDATE notifications SET IsRead = 1 WHERE UserID = ? AND IsRead = 0");
    $upd->bind_param("i", $userID);
    $upd->execute();
    echo json_encode(['success' => true]);
    exit();
}

// === THE UPDATED QUERY ===
// We join notifications -> bookings -> facilities
$stmt = $conn->prepare("
    SELECT n.NotificationID, n.Message, n.IsRead, n.CreatedAt,
           f.Name AS FacilityName, b.StartTime
    FROM notifications n
    LEFT JOIN bookings b ON n.BookingID = b.BookingID
    LEFT JOIN facilities f ON b.FacilityID = f.FacilityID
    WHERE n.UserID = ?
    ORDER BY n.CreatedAt DESC
    LIMIT 50
");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
$unreadCount = 0;
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
    if ($row['IsRead'] == 0) $unreadCount++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications - UKM Sports</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .unread-indicator { border-left: 4px solid #8a0d19; background-color: #fffafa; }
    </style>
</head>
<body class="min-h-screen flex flex-col">

<nav class="bg-white border-b border-slate-200 px-6 py-4 flex justify-between items-center sticky top-0 z-50">
    <div class="flex items-center gap-3">
        <a href="dashboard.php" class="text-slate-400 hover:text-slate-800 transition"><i class="fa-solid fa-arrow-left"></i></a>
        <h1 class="text-xl font-bold text-slate-800">Notifications</h1>
    </div>
    <button onclick="markAllRead()" id="markBtn" class="text-xs font-bold text-[#8a0d19] hover:underline <?= ($unreadCount === 0) ? 'opacity-30 cursor-default' : '' ?>">
        Mark all read (<?= $unreadCount ?>)
    </button>
</nav>

<main class="flex-grow container mx-auto px-4 py-8 max-w-2xl">
    <div class="space-y-4">
        <?php if (empty($notifications)): ?>
            <div class="text-center py-20 text-slate-400">
                <i class="fa-regular fa-bell-slash text-4xl mb-2"></i>
                <p>No notifications yet</p>
            </div>
        <?php endif; ?>

        <?php foreach ($notifications as $n): 
            $isRead = $n['IsRead'];
            $statusClass = $isRead ? 'bg-white border-slate-200' : 'unread-indicator shadow-sm';
        ?>
        <div class="p-4 rounded-xl border <?= $statusClass ?> flex gap-4 transition-all">
            <div class="mt-1">
                <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-[#8a0d19]">
                    <i class="fa-solid fa-bell"></i>
                </div>
            </div>
            <div class="flex-grow">
                <div class="flex justify-between items-start">
                    <p class="text-sm <?= $isRead ? 'text-slate-600' : 'text-slate-900 font-semibold' ?>">
                        <?= htmlspecialchars($n['Message']) ?>
                    </p>
                    <span class="text-[10px] text-slate-400 ml-2"><?= time_ago($n['CreatedAt']) ?></span>
                </div>

                <?php if ($n['FacilityName']): ?>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span class="text-[11px] bg-slate-100 text-slate-700 px-2 py-1 rounded flex items-center gap-1">
                        <i class="fa-solid fa-location-dot text-[#8a0d19]"></i>
                        <?= htmlspecialchars($n['FacilityName']) ?>
                    </span>
                    <span class="text-[11px] bg-slate-100 text-slate-700 px-2 py-1 rounded flex items-center gap-1">
                        <i class="fa-solid fa-clock text-[#8a0d19]"></i>
                        <?= date("d M, h:i A", strtotime($n['StartTime'])) ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</main>

<script>
function markAllRead() {
    fetch(window.location.href, {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "mark_all_read=1"
    }).then(() => location.reload());
}
</script>
</body>
</html>