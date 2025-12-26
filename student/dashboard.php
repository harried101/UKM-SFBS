<?php
session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');

/* ================= SESSION SECURITY ================= */
if (isset($_SESSION['created_at']) && (time() - $_SESSION['created_at']) > 1800) {
    session_unset();
    session_destroy();
    header("Location: ../index.php?expired=1");
    exit();
}
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 1800) {
    session_unset();
    session_destroy();
    header("Location: ../index.php?idle=1");
    exit();
}
$_SESSION['last_activity'] = time();
if (!isset($_SESSION['created_at'])) {
    $_SESSION['created_at'] = time();
}

/* ================= AUTH ================= */
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Student') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/db_connect.php';

/* ================= FETCH STUDENT ================= */
$studentIdentifier = $_SESSION['user_id'] ?? '';
$studentName = 'Student';
$studentID = '';
$db_numeric_id = 0;

if ($studentIdentifier) {
    $stmtStudent = $conn->prepare("
        SELECT UserID, FirstName, LastName, UserIdentifier 
        FROM users 
        WHERE UserIdentifier = ?
    ");
    $stmtStudent->bind_param("s", $studentIdentifier);
    $stmtStudent->execute();
    $resStudent = $stmtStudent->get_result();

    if ($rowStudent = $resStudent->fetch_assoc()) {
        $studentName   = $rowStudent['FirstName'] . ' ' . $rowStudent['LastName'];
        $studentID     = $rowStudent['UserIdentifier'];
        $db_numeric_id = (int)$rowStudent['UserID'];
    }
    $stmtStudent->close();
}

/* ================= FETCH BOOKINGS ================= */
$all_bookings = [];
$now = new DateTime();

if ($db_numeric_id > 0) {
    $sql = "
        SELECT 
            b.BookingID,
            b.StartTime,
            b.EndTime,
            b.Status,
            f.Name AS FacilityName,
            f.Location
        FROM bookings b
        JOIN facilities f ON b.FacilityID = f.FacilityID
        WHERE b.UserID = ?
        ORDER BY b.StartTime DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $db_numeric_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $startTimeStr = ($row['StartTime'] === '0000-00-00 00:00:00' || empty($row['StartTime'])) 
            ? $row['EndTime'] 
            : $row['StartTime'];

        $start = new DateTime($startTimeStr);
        $end = new DateTime($row['EndTime']);

        $all_bookings[] = array_merge($row, [
            'is_passed'       => ($end < $now),
            'formatted_start' => $start->format('d M Y'),
            'formatted_time'  => $start->format('h:i A') . ' - ' . $end->format('h:i A'),
            'day'             => $start->format('d'),
            'month'           => $start->format('M')
        ]);
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Dashboard – UKM Sports Center</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-slate-50 min-h-screen flex flex-col">

<!-- ================= NAVBAR ================= -->
<nav class="bg-white/95 backdrop-blur-sm border-b border-slate-200 sticky top-0 z-40 shadow-sm">
    <div class="container mx-auto px-6 py-3 flex justify-between items-center">

        <div class="flex items-center gap-4">
            <img src="../assets/img/ukm.png" class="h-10">
            <img src="../assets/img/pusatsukanlogo.png" class="h-10 hidden sm:block">
        </div>

        <div class="flex items-center gap-8">

            <!-- NAV LINKS -->
            <div class="hidden md:flex items-center gap-6">
                <a href="dashboard.php" class="text-[#8a0d19] font-bold">Home</a>
                <a href="student_facilities.php" class="text-slate-600 hover:text-[#8a0d19]">Facilities</a>
                <a href="booking_history.php" class="text-slate-600 hover:text-[#8a0d19]">History</a>

                <!-- ✅ FEEDBACK BUTTON ADDED -->
                <a href="feedback.php"
                   class="text-slate-600 hover:text-[#8a0d19] flex items-center gap-2">
                    <i class="fa-regular fa-comment-dots"></i> Feedback
                </a>
            </div>

            <div class="flex items-center gap-4 pl-4 border-l border-slate-200">

                <!-- ✅ NOTIFICATION BELL ADDED -->
                <a href="notification.php"
                   class="relative w-10 h-10 flex items-center justify-center rounded-full bg-slate-100 text-slate-600 hover:text-[#8a0d19]">
                    <i class="fa-regular fa-bell"></i>
                    <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full"></span>
                </a>

                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold"><?php echo htmlspecialchars($studentName); ?></p>
                    <p class="text-[10px] text-slate-500"><?php echo htmlspecialchars($studentID); ?></p>
                </div>

                <div class="relative" id="profileDropdownContainer">
                    <button id="profileBtn">
                        <img src="../assets/img/user.png" class="w-10 h-10 rounded-full">
                    </button>

                    <div id="dropdownMenu"
                         class="absolute right-0 mt-3 w-56 bg-white rounded-xl shadow-xl hidden">

                        <a href="../logout.php"
                           class="block px-4 py-3 text-red-600 hover:bg-red-50">
                            <i class="fa-solid fa-right-from-bracket"></i> Logout
                        </a>

                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- ================= MAIN ================= -->
<main class="container mx-auto px-6 py-8 flex-grow">
    <!-- your existing dashboard content stays exactly the same -->
</main>

<?php include './includes/footer.php'; ?>

<script>
const profileBtn = document.getElementById('profileBtn');
const dropdownMenu = document.getElementById('dropdownMenu');

profileBtn.addEventListener('click', () => {
    dropdownMenu.classList.toggle('hidden');
});
</script>

</body>
</html>
