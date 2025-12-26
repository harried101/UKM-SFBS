<?php
// PHP Configuration for Development
ini_set('display_errors', 1);
error_reporting(E_ALL);

// === CRITICAL FIX: Set Timezone for accurate time calculation and comparison ===
date_default_timezone_set('Asia/Kuala_Lumpur'); 

session_start();
require_once '../includes/db_connect.php';

/* ===== AUTH CHECK & SESSION VALIDATION ===== */
if (
    !isset($_SESSION['logged_in']) ||
    $_SESSION['role'] !== 'Student' ||
    !isset($_SESSION['user_id'])
) {
    header("Location: ../index.php");
    exit();
}

// User Identifier (e.g., 'K203562') is retrieved from the session.
$userIdentifier = $_SESSION['user_id']; 

// === LOGIC TO RESOLVE STRING ID TO INTEGER ID (Database requirement) ===
// We must fetch the UserID (INT) because the 'notifications' table uses it.
$userStmt = $conn->prepare(
    "SELECT UserID FROM users WHERE userIdentifier = ? LIMIT 1"
);
// Bind the string ID ('s')
$userStmt->bind_param("s", $userIdentifier);
$userStmt->execute();
$userResult = $userStmt->get_result();

if ($userResult->num_rows === 0) {
    // Failsafe if the session data does not match any user record
    die("User not found in users table.");
}

$userRow = $userResult->fetch_assoc();
// The final integer ID required for all notification queries.
$userID = (int)$userRow['UserID']; 

/* ===== UTILITY FUNCTION: TIME AGO (Improved for timezone accuracy) ===== */
function time_ago($datetime) {
    $timestamp = strtotime($datetime);
    // Comparison is made against the current time (already set to Asia/KL timezone)
    $time_difference = time() - $timestamp; 

    if ($time_difference < 60) return 'just now';
    if ($time_difference < 3600) return round($time_difference / 60) . ' minutes ago';
    if ($time_difference < 86400) return round($time_difference / 3600) . ' hours ago';
    // Returns full date and time for older notifications
    return date("d M Y, h:i A", $timestamp); 
}

/* ===== AJAX HANDLER: MARK ALL AS READ ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    header('Content-Type: application/json');

    $stmt = $conn->prepare(
        "UPDATE notifications SET IsRead = 1 WHERE UserID = ? AND IsRead = 0"
    );
    // Bind the integer ID ('i') for the notifications table.
    $stmt->bind_param("i", $userID);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'count' => $stmt->affected_rows
    ]);
    exit();
}

/* ===== MAIN LOGIC: FETCH NOTIFICATIONS ===== */
$stmt = $conn->prepare(
    "SELECT NotificationID, Message, IsRead, CreatedAt
     FROM notifications
     WHERE UserID = ?
     ORDER BY CreatedAt DESC
     LIMIT 50"
);
// Bind the integer ID ('i') for the main fetch query.
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
$unreadCount = 0;

// Fetch results and count unread messages
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Base styling for the modern look */
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        /* Style for unread notifications (Maroon accent color) */
        .notification-unread { border-left: 4px solid #8a0d19; background-color: #fef2f2; }
    </style>
</head>
<body class="flex flex-col min-h-screen">

<nav class="bg-white border-b border-slate-200 px-6 py-4 flex justify-between items-center sticky top-0 z-50">
    <div class="flex items-center gap-3">
        <a href="dashboard.php" class="text-slate-400 hover:text-slate-800 transition">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <h1 class="text-xl font-bold text-slate-800">Notifications</h1>
    </div>
    <div class="flex items-center gap-4">
        <span class="text-sm text-slate-500 hidden sm:inline">
            <?= htmlspecialchars($userIdentifier) ?>
        </span>
        <button id="markAllBtn"
            class="text-xs font-bold text-[#8a0d19] hover:underline disabled:opacity-50 transition-all duration-300"
            onclick="markAllRead()"
            <?= ($unreadCount === 0) ? 'disabled' : '' ?>>
            Mark all as read (<?= $unreadCount ?>)
        </button>
    </div>
</nav>

<main class="flex-grow container mx-auto px-6 py-8 max-w-2xl">

<h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 ml-1">
    Recent Activity
</h4>

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
    // Dynamic styling based on read status
    $readClass = $isRead ? 'opacity-60 bg-white border-slate-200' : 'notification-unread border-[#8a0d19] shadow-md hover:shadow-lg transition duration-200';
    $messageClass = $isRead ? 'text-slate-600' : 'text-slate-900 font-medium';
    $timeClass = $isRead ? 'text-slate-400' : 'text-[#8a0d19] font-semibold';
    
    // Logic to determine appropriate icon based on message content
    $msg = strtolower($n['Message']);
    $icon = 'fa-circle-info';
    $iconColor = 'text-slate-500';
    if (strpos($msg, 'approved') !== false || strpos($msg, 'good news') !== false) {
        $icon = 'fa-check-circle'; $iconColor = 'text-green-600'; // Success status
    } elseif (strpos($msg, 'cancel') !== false || strpos(
        $msg, 'reject') !== false) {
        $icon = 'fa-circle-xmark'; $iconColor = 'text-red-600'; // Error/Rejection status
    }
?>

<div class="notification-item p-4 rounded-xl border <?= $readClass ?> flex gap-4 items-start" data-id="<?= $n['NotificationID'] ?>" data-read="<?= $isRead ?>">
    
    <div class="flex-shrink-0 pt-1">
        <i class="fa-solid <?= $icon ?> text-xl <?= $iconColor ?>"></i>
    </div>
    
    <div class="flex-grow">
        <div class="flex justify-between items-start">
            <p class="text-sm <?= $messageClass ?> whitespace-pre-line leading-relaxed">
                <?= htmlspecialchars($n['Message']) ?>
            </p>
            <span class="text-[10px] <?= $timeClass ?> ml-4 flex-shrink-0 pt-1" title="<?= date("d M Y, h:i A", strtotime($n['CreatedAt'])) ?>">
                <?= time_ago($n['CreatedAt']) ?>
            </span>
        </div>
    </div>
</div>

<?php endforeach; ?>

</div>
</main>

<script>
// --- JAVASCRIPT: MARK ALL AS READ (AJAX Fetch) ---
function markAllRead() {
    const markAllBtn = document.getElementById('markAllBtn');
    if (markAllBtn.disabled) return;

    // Provide immediate user feedback and disable button
    markAllBtn.disabled = true;
    markAllBtn.textContent = 'Updating...';

    fetch("notification.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "mark_all_read=1"
    })
    .then(res => res.json())
    // Reload the page upon successful update to reflect changes
    .then(() => location.reload())
    .catch(() => {
        // Fallback to reload even if fetch fails to ensure UI sync
        location.reload(); 
    });
}
</script>

</body>
</html>