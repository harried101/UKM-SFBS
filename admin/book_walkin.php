<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') die("Access Denied");

$facility_id = $_GET['facility_id'] ?? '';
$facility_name = "Facility";

if ($facility_id) {
    $stmt = $conn->prepare("SELECT Name FROM facilities WHERE FacilityID = ?");
    $stmt->bind_param("i", $facility_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) $facility_name = $row['Name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .calendar-day.selected { background-color: #0b4d9d; color: white; }
        .calendar-day:hover:not(.selected):not(.disabled) { background-color: #e0f2fe; color: #0b4d9d; cursor: pointer; }
        .calendar-day.today { border: 2px solid #0b4d9d; color: #0b4d9d; font-weight: bold; }
        .disabled { color: #ccc; cursor: default; }
    </style>
</head>
<body class="bg-white p-4 font-sans">
    <div class="text-center mb-4">
        <h2 class="text-2xl font-bold text-[#0b4d9d]"><?php echo htmlspecialchars($facility_name); ?></h2>
        <p class="text-gray-500">Admin Walk-in Booking</p>
    </div>

    <div class="max-w-md mx-auto">
        <!-- Calendar Controls -->
        <div class="flex justify-between items-center mb-4 bg-gray-50 p-2 rounded">
            <button id="prevMonth" class="text-[#0b4d9d] font-bold px-3">&lt;</button>
            <span id="monthYear" class="font-bold text-gray-700"></span>
            <button id="nextMonth" class="text-[#0b4d9d] font-bold px-3">&gt;</button>
        </div>
        
        <div id="calendarGrid" class="grid grid-cols-7 gap-2 text-center text-sm mb-6"></div>

        <div id="slotsContainer" class="grid grid-cols-3 gap-2 mb-6"></div>

        <form id="walkinForm" action="book_walkin_fetch.php" method="POST" class="hidden">
            <input type="hidden" name="facility_id" value="<?php echo $facility_id; ?>">
            <input type="hidden" name="start_time" id="hiddenStartTime">
            
            <div class="mb-3">
                <label class="block text-sm font-bold text-[#0b4d9d] mb-1">Student Matric No.</label>
                <input type="text" name="student_id" required class="w-full border rounded p-2 focus:outline-none focus:border-[#0b4d9d]" placeholder="e.g. A123456">
            </div>

            <button type="submit" class="w-full bg-[#0b4d9d] text-white py-3 rounded-lg font-bold hover:bg-[#083a75]">
                Confirm Booking
            </button>
        </form>
    </div>

<script>
const facilityId = "<?php echo $facility_id; ?>";
let currDate = new Date();

// Render Calendar Logic (Simplified for brevity, similar to student side but Admin focused)
// ... (Include your renderCalendar JS here logic here) ...
// Ensure calls to book_walkin_fetch.php
</script>
</body>
</html>