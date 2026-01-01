<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Kuala_Lumpur');

require_once 'includes/student_auth.php';


// Student details already loaded from student_auth.php
// $studentIdentifier, $studentName, $studentID, $db_numeric_id are available

$studentUserID = $db_numeric_id; // Use the numeric ID from auth


// Helper: convert datetime to "time ago"
function time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return round($diff / 60) . 'm ago';
    if ($diff < 86400) return round($diff / 3600) . 'h ago';
    return date("d M", $timestamp);
}

// Count unread notifications
$countStmt = $conn->prepare("SELECT COUNT(*) AS unread FROM notifications WHERE UserID = ? AND IsRead = 0");
$countStmt->bind_param("i", $studentUserID);
$countStmt->execute();
$countResult = $countStmt->get_result()->fetch_assoc();
$unreadCount = $countResult['unread'];

// Mark all read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    header('Content-Type: application/json');
    $upd = $conn->prepare("UPDATE notifications SET IsRead = 1 WHERE UserID = ? AND IsRead = 0");
    $upd->bind_param("i", $studentUserID);
    $upd->execute();
    echo json_encode(['success' => true]);
    exit();
}

// Fetch notifications
$stmt = $conn->prepare("
    SELECT n.*, f.Name, b.StartTime
    FROM notifications n
    LEFT JOIN bookings b ON n.BookingID = b.BookingID
    LEFT JOIN facilities f ON b.FacilityID = f.FacilityID
    WHERE n.UserID = ?
    ORDER BY n.CreatedAt DESC
    LIMIT 50
");
$stmt->bind_param("i", $studentUserID);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Notifications – UKM Sports Center</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root {
        --primary: #8a0d19; 
        --primary-hover: #6d0a13;
        --bg-light: #f8fafc;
    }
    body {
        font-family: 'Inter', sans-serif;
        background-color: var(--bg-light);
        color: #1e293b;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }
    h1, h2, h3, .font-serif { font-family: 'Playfair Display', serif; }
    
    .fade-in { animation: fadeIn 0.5s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    
    .notification-item:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1); }
    .unread-indicator { border-left: 4px solid #8a0d19; background-color: #fffafa; }
</style>
</head>
<body>

<?php 
$nav_active = 'notifications';
include 'includes/navbar.php'; 
?>

<main class="container mx-auto px-4 md:px-6 py-8 md:py-12 flex-grow max-w-4xl relative z-10 space-y-8 fade-in">
    
    <div class="flex items-end justify-between border-b border-slate-200 pb-6">
        <div>
            <p class="text-slate-500 font-medium text-sm uppercase tracking-wide mb-2">Updates</p>
            <h1 class="text-3xl md:text-4xl font-bold text-[#8a0d19] font-serif">Notifications</h1>
        </div>
        <button onclick="markAllRead()" id="markBtn" 
            class="group flex items-center gap-2 text-sm font-bold text-slate-500 hover:text-[#8a0d19] transition <?= ($unreadCount === 0) ? 'opacity-50 cursor-not-allowed' : '' ?>"
            <?= ($unreadCount === 0) ? 'disabled' : '' ?>>
            <span>Mark all read</span>
            <span class="bg-slate-100 text-slate-600 group-hover:bg-[#8a0d19] group-hover:text-white px-2 py-0.5 rounded-full text-xs transition-colors font-mono">
                <?= $unreadCount ?>
            </span>
        </button>
    </div>

    <div class="space-y-4">
        <?php if (empty($notifications)): ?>
            <div class="bg-white rounded-3xl border-2 border-dashed border-slate-200 p-16 text-center flex flex-col items-center justify-center">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 text-slate-300">
                    <i class="fa-regular fa-bell-slash text-3xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-1">No new notifications</h3>
                <p class="text-slate-500 max-w-sm mx-auto text-sm">You're all caught up! Check back later for updates on your bookings.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($notifications as $n): 
            $isRead = $n['IsRead'];
            $statusClass = $isRead ? 'bg-white border-slate-100 opacity-80' : 'unread-indicator shadow-sm bg-white border-slate-100';
            $iconColor = $isRead ? 'bg-slate-50 text-slate-400' : 'bg-red-50 text-[#8a0d19]';
        ?>
        <div class="notification-item p-5 rounded-2xl border <?= $statusClass ?> flex gap-5 transition-all duration-300">
            <div class="flex-shrink-0 mt-1">
                <div class="w-12 h-12 rounded-full <?= $iconColor ?> flex items-center justify-center text-lg shadow-sm">
                    <i class="fa-solid fa-bell"></i>
                </div>
            </div>
            <div class="flex-grow min-w-0">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-2 mb-2">
                    <p class="text-sm md:text-base <?= $isRead ? 'text-slate-600' : 'text-slate-900 font-bold' ?> leading-relaxed">
                        <?= htmlspecialchars($n['Message']) ?>
                    </p>
                    <span class="text-xs font-bold text-slate-400 whitespace-nowrap flex-shrink-0 flex items-center gap-1.5 bg-slate-50 px-2 py-1 rounded-lg self-start">
                        <i class="fa-regular fa-clock text-[10px]"></i>
                        <?= time_ago($n['CreatedAt']) ?>
                    </span>
                </div>

                <!-- Optional Context Badges -->
                <?php if (!empty($n['Name']) || !empty($n['StartTime'])): ?>
                <div class="flex flex-wrap gap-2 mt-3">
                    <?php if (!empty($n['Name'])): ?>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-50 text-slate-600 border border-slate-100">
                        <i class="fa-solid fa-location-dot text-[#8a0d19]"></i>
                        <?= htmlspecialchars($n['Name']) ?>
                    </span>
                    <?php endif; ?>

                    <?php if (!empty($n['StartTime'])): ?>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-50 text-slate-600 border border-slate-100">
                        <i class="fa-solid fa-calendar-day text-[#8a0d19]"></i>
                        <?= date("d M Y, h:i A", strtotime($n['StartTime'])) ?>
                    </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</main>

<script>
function markAllRead() {
    const btn = document.getElementById('markBtn');
    if(btn.hasAttribute('disabled')) return;
    
    // Optimistic UI update
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';
    
    fetch(window.location.href, {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "mark_all_read=1"
    }).then(() => location.reload());
}
</script>
<?php include 'includes/footer.php'; ?>
<script src="../assets/js/idle_timer.js.php"></script>
</body>
</html>
