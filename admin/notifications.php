<?php
session_start();
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] != 'Admin') {
    header("Location: ../login.php");
    exit;
}

include "../db_connect.php";

$userID = $_SESSION['UserID'];

// Mark all as read when visiting page
$conn->query("UPDATE notifications SET IsRead = 1 WHERE UserID = $userID");

// Fetch notifications
$result = $conn->query("SELECT Message, CreatedAt FROM notifications WHERE UserID = $userID ORDER BY CreatedAt DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<?php include "includes/navbar.php"; ?>

<div class="max-w-3xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4 text-[#0b4d9d]">Notifications</h1>

    <?php if ($result->num_rows > 0): ?>
        <ul class="space-y-4">
            <?php while ($row = $result->fetch_assoc()): ?>
                <li class="bg-white shadow p-4 rounded-lg border-l-4 border-[#0b4d9d]">
                    <p class="text-gray-800 font-medium"><?= htmlspecialchars($row['Message']) ?></p>
                    <span class="text-sm text-gray-500"><?= $row['CreatedAt'] ?></span>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p class="text-gray-500 italic">No notifications found.</p>
    <?php endif; ?>
</div>

</body>
</html>
