<?php
require_once 'includes/admin_auth.php'; // Standardized Auth & User Fetch

/* ---------------------- FETCH FACILITIES ---------------------- */
$facilities = [];
$facilityResult = $conn->query("SELECT FacilityID, Name FROM facilities ORDER BY FacilityID");
while ($row = $facilityResult->fetch_assoc()) {
    $facilities[] = $row;
}

/* ---------------------- ADD NEW CLOSURE ---------------------- */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_override"])) {

    $FacilityID = $_POST["FacilityID"];
    $StartDate = $_POST["ClosureStartDate"];
    $EndDate = $_POST["ClosureEndDate"];
    $Reason = $_POST["Reason"];

    if (!$FacilityID || !$StartDate || !$EndDate || !$Reason) {
        $_SESSION["alert"] = ["type" => "error", "msg" => "All fields are required."];
    } elseif (strtotime($StartDate) > strtotime($EndDate)) {
        $_SESSION["alert"] = ["type" => "error", "msg" => "End date must be after start date."];
    } else {
        $StartTime = $StartDate . " 00:00:00";
        $EndTime = $EndDate . " 23:59:59";

        $stmt = $conn->prepare("INSERT INTO scheduleoverrides (FacilityID, StartTime, EndTime, Reason) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $FacilityID, $StartTime, $EndTime, $Reason);

        if ($stmt->execute()) {
            $_SESSION["alert"] = ["type" => "success", "msg" => "Closure scheduled successfully."];
        } else {
            $_SESSION["alert"] = ["type" => "error", "msg" => "Database Error: " . $stmt->error];
        }
        $stmt->close();
    }
    header("Location: manage_closures.php");
    exit();
}

/* ---------------------- DELETE CLOSURE ---------------------- */
if (isset($_GET["delete_id"])) {
    $id = $_GET["delete_id"];
    $stmt = $conn->prepare("DELETE FROM scheduleoverrides WHERE OverrideID=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION["alert"] = ["type" => "success", "msg" => "Closure deleted successfully."];
    } else {
        $_SESSION["alert"] = ["type" => "error", "msg" => "Error deleting closure."];
    }
    
    header("Location: manage_closures.php");
    exit();
}

/* ---------------------- FETCH EXISTING CLOSURES ---------------------- */
$closures = [];
$query = "
    SELECT o.OverrideID, o.FacilityID, o.StartTime, o.EndTime, o.Reason, f.Name 
    FROM scheduleoverrides o
    JOIN facilities f ON o.FacilityID = f.FacilityID
    ORDER BY o.StartTime DESC
";
$res = $conn->query($query);
while ($row = $res->fetch_assoc()) {
    $closures[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Closures</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        body: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        ukm: {
                            blue: '#0b4d9d',
                            dark: '#063a75',
                            light: '#e0f2fe'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .fade-in { animation: fadeIn 0.4s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .glass-panel {
            background: white;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col font-body text-slate-800">

<!-- NAVBAR (Consistent with Add Facilities) -->
<?php
$nav_active = 'facilities'; 
include 'includes/navbar.php'; 
?>

<!-- MAIN CONTENT -->
<main class="flex-grow container mx-auto px-4 md:px-6 py-8 max-w-7xl fade-in">

    <!-- PAGE HEADER -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10 pb-6 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">
                <span class="bg-slate-100 px-2 py-1 rounded">Admin</span>
                <i class="fa-solid fa-chevron-right text-[10px]"></i>
                <span class="text-ukm-blue">Facilities Management</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 tracking-tight">
                Manage Closures
            </h1>
            <p class="text-slate-500 mt-2 text-lg">Schedule temporary shutdowns for maintenance or holidays.</p>
        </div>
    </div>

    <!-- NOTIFICATION ALERT -->
    <?php if (isset($_SESSION["alert"])): ?>
        <div class="mb-8 rounded-xl p-4 flex items-center justify-between shadow-sm border <?php echo $_SESSION["alert"]["type"] == 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800'; ?>">
            <div class="flex items-center gap-3">
                <i class="fa-solid <?php echo $_SESSION["alert"]["type"] == 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> text-xl"></i>
                <span class="font-bold"><?= $_SESSION["alert"]["msg"]; ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-sm opacity-50 hover:opacity-100 font-bold">DISMISS</button>
        </div>
        <?php unset($_SESSION["alert"]); ?>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        <!-- LEFT: ADD CLOSURE FORM -->
        <div class="lg:col-span-4">
            <div class="glass-panel rounded-2xl overflow-hidden sticky top-24">
                <div class="bg-slate-50/80 px-6 py-4 border-b border-slate-200 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-ukm-blue flex items-center justify-center text-white text-xs">
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <h3 class="font-bold text-slate-800">Schedule New</h3>
                </div>
                
                <form method="POST" class="p-6 space-y-5">
                    <input type="hidden" name="add_override" value="1">
                    
                    <!-- Facility Select -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5 ml-1">Facility</label>
                        <div class="relative">
                            <i class="fa-solid fa-building absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <select name="FacilityID" required class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-ukm-blue outline-none transition appearance-none bg-white">
                                <option value="" disabled selected>Select facility...</option>
                                <?php foreach ($facilities as $f): ?>
                                    <option value="<?= $f['FacilityID']; ?>">
                                        <?= htmlspecialchars($f['Name']); ?> (<?= $f['FacilityID']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400">
                                <i class="fa-solid fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Date Range -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5 ml-1">Start Date</label>
                            <input type="date" name="ClosureStartDate" required class="w-full px-3 py-3 rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-ukm-blue outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5 ml-1">End Date</label>
                            <input type="date" name="ClosureEndDate" required class="w-full px-3 py-3 rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-ukm-blue outline-none transition">
                        </div>
                    </div>

                    <!-- Reason -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5 ml-1">Reason</label>
                        <div class="relative">
                            <i class="fa-solid fa-message absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input type="text" name="Reason" placeholder="e.g. Maintenance Work" required class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-ukm-blue outline-none transition">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-ukm-blue text-white font-bold py-3.5 rounded-xl shadow-lg shadow-blue-900/10 hover:bg-ukm-dark transition transform active:scale-95 flex justify-center items-center gap-2">
                        <i class="fa-solid fa-calendar-check"></i>
                        Confirm Closure
                    </button>
                </form>
            </div>
        </div>

        <!-- RIGHT: CLOSURE LIST -->
        <div class="lg:col-span-8">
            <div class="glass-panel rounded-2xl overflow-hidden min-h-[500px] flex flex-col">
                <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <h3 class="font-bold text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-list-ul text-ukm-blue"></i> Scheduled Closures
                    </h3>
                    <span class="text-xs font-bold bg-slate-200 text-slate-600 px-3 py-1 rounded-full"><?= count($closures); ?> Total</span>
                </div>

                <div class="flex-grow overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 font-bold">Facility</th>
                                <th class="px-4 py-4 font-bold">Duration</th>
                                <th class="px-4 py-4 font-bold">Status</th>
                                <th class="px-4 py-4 font-bold">Reason</th>
                                <th class="px-4 py-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($closures)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-400 italic">
                                        <div class="flex flex-col items-center gap-3">
                                            <i class="fa-regular fa-calendar-xmark text-4xl opacity-50"></i>
                                            <p>No closures currently scheduled.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($closures as $c):
                                    $start = new DateTime($c['StartTime']);
                                    $end = new DateTime($c['EndTime']);
                                    $now = new DateTime();
                                    $days = $start->diff($end)->days + 1;
                                    
                                    // Status Logic
                                    if ($end < $now) {
                                        $statusBadge = '<span class="bg-slate-100 text-slate-500 text-[10px] font-bold px-2 py-0.5 rounded border border-slate-200 uppercase">Past</span>';
                                        $rowOpacity = 'opacity-60 grayscale';
                                    } elseif ($start <= $now && $end >= $now) {
                                        $statusBadge = '<span class="bg-red-50 text-red-600 text-[10px] font-bold px-2 py-0.5 rounded border border-red-100 uppercase animate-pulse">Active Now</span>';
                                        $rowOpacity = '';
                                    } else {
                                        $statusBadge = '<span class="bg-blue-50 text-blue-600 text-[10px] font-bold px-2 py-0.5 rounded border border-blue-100 uppercase">Upcoming</span>';
                                        $rowOpacity = '';
                                    }
                                ?>
                                <tr class="hover:bg-slate-50 transition group <?php echo $rowOpacity; ?>">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-800"><?= htmlspecialchars($c['Name']); ?></div>
                                        <div class="text-xs text-slate-400 font-mono">ID: <?= $c['FacilityID']; ?></div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex items-center gap-2 text-slate-600">
                                            <span class="font-semibold"><?= $start->format('d M'); ?></span>
                                            <i class="fa-solid fa-arrow-right text-[10px] text-slate-300"></i>
                                            <span class="font-semibold"><?= $end->format('d M'); ?></span>
                                        </div>
                                        <div class="text-xs text-slate-400 mt-1"><?= $days; ?> Days</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <?= $statusBadge; ?>
                                    </td>
                                    <td class="px-4 py-4 max-w-[200px] truncate text-slate-600" title="<?= htmlspecialchars($c['Reason']); ?>">
                                        <?= htmlspecialchars($c['Reason']); ?>
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <a href="?delete_id=<?= $c['OverrideID']; ?>" 
                                           onclick="return confirm('Delete this closure?')" 
                                           class="text-slate-400 hover:text-red-600 p-2 rounded-lg hover:bg-red-50 transition"
                                           title="Delete">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</main>


<!-- FOOTER -->
<?php include 'includes/footer.php'; ?>

<script src="../assets/js/idle_timer.js.php"></script>
</body>
</html>
