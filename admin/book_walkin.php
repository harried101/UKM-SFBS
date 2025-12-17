<?php
session_start();
require_once '../includes/db_connect.php';

// Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') die("Access Denied");

$facility_id = $_GET['facility_id'] ?? '';
$facility_name = "Facility";
$msg = "";
$msgType = "";

// Fetch Facility Name
if ($facility_id) {
    $stmt = $conn->prepare("SELECT Name FROM facilities WHERE FacilityID = ?");
    $stmt->bind_param("i", $facility_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) $facility_name = $row['Name'];
}

// --- HANDLE CLOSURE SUBMISSION (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_closure'])) {
    $start = $_POST['closure_start'];
    $end = $_POST['closure_end'];
    $reason = $_POST['closure_reason'];
    $fid = $_POST['facility_id'];

    if($start && $end && $reason && $fid){
        $startDT = $start . " 00:00:00";
        $endDT = $end . " 23:59:59";
        
        $stmt = $conn->prepare("INSERT INTO scheduleoverrides (FacilityID, StartTime, EndTime, Reason) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $fid, $startDT, $endDT, $reason);
        if($stmt->execute()){
            $msg = "Closure scheduled successfully.";
            $msgType = "success";
        } else {
            $msg = "Error adding closure: " . $conn->error;
            $msgType = "danger";
        }
    } else {
        $msg = "All fields are required.";
        $msgType = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Facility - <?php echo htmlspecialchars($facility_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .calendar-day { aspect-ratio: 1; display: flex; align-items: center; justify-content: center; border-radius: 0.5rem; transition: all 0.2s; font-size: 0.9rem; font-weight: 500; }
        .calendar-day:hover:not(.disabled):not(.selected) { background-color: #dbeafe; color: #1e40af; cursor: pointer; transform: scale(1.05); }
        .calendar-day.selected { background-color: #1e40af; color: white; box-shadow: 0 4px 6px -1px rgba(30, 64, 175, 0.4); transform: scale(1.05); }
        .calendar-day.today { border: 2px solid #1e40af; color: #1e40af; font-weight: 700; }
        .calendar-day.disabled { color: #cbd5e1; cursor: default; background-color: transparent; }
        
        .fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        /* Custom Scrollbar for slots */
        .slots-grid::-webkit-scrollbar { width: 4px; }
        .slots-grid::-webkit-scrollbar-track { background: #f1f1f1; }
        .slots-grid::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .slots-grid::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="p-6 min-h-screen flex flex-col items-center">

    <!-- Header -->
    <div class="w-full max-w-5xl mb-6 flex justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-gray-100">
        <div>
            <h1 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($facility_name); ?></h1>
            <p class="text-sm text-gray-500 flex items-center gap-1"><i class="fa-solid fa-lock text-gray-400"></i> Admin Console</p>
        </div>
        <a href="bookinglist.php" class="text-sm text-blue-600 hover:underline font-medium">&larr; Back to Bookings</a>
    </div>

    <!-- Feedback Message -->
    <?php if ($msg): ?>
    <div class="w-full max-w-5xl mb-4 p-4 rounded-lg text-sm font-medium flex items-center gap-2 <?php echo $msgType === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200'; ?>">
        <i class="fa-solid <?php echo $msgType === 'success' ? 'fa-check-circle' : 'fa-triangle-exclamation'; ?>"></i>
        <?php echo $msg; ?>
    </div>
    <?php endif; ?>

    <!-- Main Content Grid -->
    <div class="w-full max-w-5xl grid grid-cols-1 md:grid-cols-12 gap-6">
        
        <!-- Left Column: Calendar -->
        <div class="md:col-span-5 lg:col-span-4 flex flex-col gap-4">
            <div class="bg-white p-6 rounded-2xl shadow-md border border-gray-100">
                <!-- Month Nav -->
                <div class="flex justify-between items-center mb-6">
                    <button id="prevMonth" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-600 transition"><i class="fa-solid fa-chevron-left text-xs"></i></button>
                    <h3 id="monthYear" class="text-lg font-bold text-gray-800 tracking-tight"></h3>
                    <button id="nextMonth" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-600 transition"><i class="fa-solid fa-chevron-right text-xs"></i></button>
                </div>

                <!-- Days Header -->
                <div class="grid grid-cols-7 gap-1 text-center mb-2">
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Su</div>
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Mo</div>
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Tu</div>
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">We</div>
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Th</div>
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Fr</div>
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Sa</div>
                </div>

                <!-- Days Grid -->
                <div id="calendarGrid" class="grid grid-cols-7 gap-1"></div>

                <!-- Legend -->
                <div class="mt-6 pt-4 border-t border-gray-100 flex justify-between text-[10px] text-gray-500 font-medium">
                    <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-100 border border-blue-500"></span> Available</div>
                    <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-gray-100 border border-gray-300"></span> Booked</div>
                    <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-800"></span> Selected</div>
                </div>
            </div>
        </div>

        <!-- Right Column: Actions -->
        <div class="md:col-span-7 lg:col-span-8">
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 h-full overflow-hidden flex flex-col">
                
                <!-- Tabs -->
                <div class="flex border-b border-gray-100">
                    <button onclick="switchTab('booking')" id="tab-booking" class="flex-1 py-4 text-sm font-semibold text-blue-800 border-b-2 border-blue-800 bg-blue-50/50 transition">
                        <i class="fa-regular fa-calendar-check mr-2"></i> Book Walk-in
                    </button>
                    <button onclick="switchTab('closure')" id="tab-closure" class="flex-1 py-4 text-sm font-semibold text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition border-b-2 border-transparent">
                        <i class="fa-solid fa-ban mr-2"></i> Block Dates
                    </button>
                </div>

                <div class="p-6 flex-grow flex flex-col">
                    
                    <!-- TAB 1: BOOKING -->
                    <div id="view-booking" class="fade-in flex flex-col h-full">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-bold text-gray-800"><i class="fa-solid fa-clock text-blue-600 mr-2"></i>Available Slots</h4>
                            <span id="selectedDateDisplay" class="text-xs font-semibold bg-gray-100 text-gray-600 px-2 py-1 rounded">Select a date</span>
                        </div>

                        <!-- Slots Grid -->
                        <div id="slotsLoader" class="hidden flex-col items-center justify-center py-10 text-gray-400 flex-grow">
                            <i class="fa-solid fa-circle-notch fa-spin text-2xl mb-3 text-blue-600"></i>
                            <span class="text-xs">Fetching schedule...</span>
                        </div>
                        
                        <div id="slotsContainer" class="slots-grid grid grid-cols-3 sm:grid-cols-4 gap-3 overflow-y-auto max-h-[300px] content-start pr-1 mb-6 min-h-[100px]">
                            <p class="col-span-full text-center text-gray-400 py-10 text-sm">Please select a date on the calendar.</p>
                        </div>

                        <!-- Booking Form (Hidden until slot selected) -->
                        <form id="walkinForm" action="book_walkin_fetch.php" method="POST" class="hidden mt-auto border-t border-gray-100 pt-4">
                            <input type="hidden" name="facility_id" value="<?php echo $facility_id; ?>">
                            <input type="hidden" name="start_time" id="hiddenStartTime">
                            
                            <div class="mb-4">
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Student Matric ID</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fa-solid fa-id-card"></i></span>
                                    <input type="text" name="student_id" required class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-gray-700 font-medium uppercase placeholder-gray-400 transition" placeholder="e.g. A173456">
                                </div>
                            </div>

                            <div class="flex items-center justify-between bg-blue-50 p-3 rounded-lg mb-4">
                                <span class="text-xs text-blue-700 font-medium">Selected Time</span>
                                <span id="summaryTime" class="text-sm font-bold text-blue-900">--:--</span>
                            </div>

                            <button type="submit" class="w-full bg-blue-800 hover:bg-blue-900 text-white py-3 rounded-lg font-bold shadow-md hover:shadow-lg transition flex items-center justify-center gap-2 transform active:scale-[0.98]">
                                <span>Confirm Booking</span>
                                <i class="fa-solid fa-check"></i>
                            </button>
                        </form>
                    </div>

                    <!-- TAB 2: CLOSURE -->
                    <div id="view-closure" class="hidden fade-in h-full">
                        <div class="bg-red-50 border border-red-100 rounded-xl p-5 mb-6">
                            <h4 class="font-bold text-red-800 mb-2 flex items-center gap-2">
                                <i class="fa-solid fa-triangle-exclamation"></i> Block Facility Dates
                            </h4>
                            <p class="text-xs text-red-600 leading-relaxed">
                                Schedule maintenance or closures. This will prevent any students or admins from booking this facility during the selected range.
                            </p>
                        </div>

                        <form method="POST" class="flex flex-col gap-4">
                            <input type="hidden" name="add_closure" value="1">
                            <input type="hidden" name="facility_id" value="<?php echo $facility_id; ?>">
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Start Date</label>
                                    <input type="date" id="closureStart" name="closure_start" required class="w-full p-2.5 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">End Date</label>
                                    <input type="date" id="closureEnd" name="closure_end" required class="w-full p-2.5 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Reason</label>
                                <input type="text" name="closure_reason" required placeholder="e.g. Cleaning, Repair Work" class="w-full p-2.5 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm">
                            </div>

                            <div class="mt-auto pt-4">
                                <button type="submit" onclick="return confirm('Confirm blocking these dates?')" class="w-full bg-red-600 hover:bg-red-700 text-white py-3 rounded-lg font-bold shadow-md transition flex items-center justify-center gap-2">
                                    <i class="fa-solid fa-ban"></i> Apply Closure
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

<script>
    const facilityId = "<?php echo $facility_id; ?>";
    let currDate = new Date();
    let currMonth = currDate.getMonth();
    let currYear = currDate.getFullYear();
    let selectedDateStr = null; // Store formatted YYYY-MM-DD

    // DOM Elements
    const calendarGrid = document.getElementById('calendarGrid');
    const monthYear = document.getElementById('monthYear');
    const slotsSection = document.getElementById('timeSlotsSection');
    const slotsContainer = document.getElementById('slotsContainer');
    const slotsLoader = document.getElementById('slotsLoader');
    const bookingForm = document.getElementById('walkinForm');
    
    // Tab Switching Logic
    function switchTab(tab) {
        // Reset styles
        document.getElementById('tab-booking').className = "flex-1 py-4 text-sm font-semibold text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition border-b-2 border-transparent";
        document.getElementById('tab-closure').className = "flex-1 py-4 text-sm font-semibold text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition border-b-2 border-transparent";
        
        // Active Style
        const activeClass = "flex-1 py-4 text-sm font-semibold text-blue-800 border-b-2 border-blue-800 bg-blue-50/50 transition";
        document.getElementById('tab-' + tab).className = activeClass;

        // View Toggling
        document.getElementById('view-booking').classList.add('hidden');
        document.getElementById('view-closure').classList.add('hidden');
        document.getElementById('view-' + tab).classList.remove('hidden');
    }

    // --- CALENDAR LOGIC ---
    function renderCalendar(month, year) {
        calendarGrid.innerHTML = "";
        monthYear.innerText = new Date(year, month).toLocaleString('default', { month: 'long', year: 'numeric' });
        let firstDay = new Date(year, month, 1).getDay();
        let daysInMonth = new Date(year, month + 1, 0).getDate();
        
        // Empty cells
        for (let i = 0; i < firstDay; i++) {
            const empty = document.createElement('div');
            calendarGrid.appendChild(empty);
        }

        const today = new Date();
        today.setHours(0,0,0,0);

        for (let day = 1; day <= daysInMonth; day++) {
            const dateDiv = document.createElement('div');
            dateDiv.innerText = day;
            dateDiv.className = "calendar-day";
            
            const checkDate = new Date(year, month, day);
            const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

            // Styling logic
            if (checkDate < today) {
                dateDiv.classList.add('disabled');
            } else {
                dateDiv.onclick = () => selectDate(day, month, year, dateDiv);
                // Pre-select today if visible
                if (dateString === selectedDateStr) {
                    dateDiv.classList.add('selected');
                }
            }
            
            if (day === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
                dateDiv.classList.add('today');
            }
            
            calendarGrid.appendChild(dateDiv);
        }
    }

    function selectDate(day, month, year, element) {
        document.querySelectorAll('.calendar-day').forEach(el => el.classList.remove('selected'));
        element.classList.add('selected');
        
        selectedDateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        
        // Update display text
        const displayDate = new Date(year, month, day).toLocaleDateString('en-US', {weekday: 'short', month: 'short', day: 'numeric'});
        document.getElementById('selectedDateDisplay').innerText = displayDate;
        
        // --- 1. Update Booking Tab ---
        fetchSlots(selectedDateStr);
        
        // --- 2. Update Closure Tab ---
        // Auto-fill the Start Date for convenience
        const closureStart = document.getElementById('closureStart');
        const closureEnd = document.getElementById('closureEnd');
        if(closureStart) {
            closureStart.value = selectedDateStr;
            // Optional: Auto-fill end date same as start date for 1-day closure
            if(!closureEnd.value) closureEnd.value = selectedDateStr; 
        }
    }

    function fetchSlots(dateStr) {
        slotsContainer.innerHTML = '';
        slotsLoader.classList.remove('hidden');
        slotsLoader.classList.add('flex'); // Ensure flex display
        bookingForm.classList.add('hidden'); // Hide form on new date selection

        fetch(`book_walkin_fetch.php?get_slots=1&date=${dateStr}&facility_id=${facilityId}`)
            .then(res => res.json())
            .then(data => {
                slotsLoader.classList.add('hidden');
                slotsLoader.classList.remove('flex');
                
                if (data.is_closed) {
                    slotsContainer.innerHTML = `
                        <div class="col-span-full bg-red-50 border border-red-100 text-red-600 rounded-lg p-4 text-center text-sm">
                            <i class="fa-solid fa-ban text-lg mb-1 block"></i>
                            <span class="font-bold">Closed:</span> ${data.message}
                        </div>`;
                    return;
                }

                if(!data.slots || data.slots.length === 0) {
                    slotsContainer.innerHTML = '<p class="col-span-full text-center text-gray-400 py-4 text-sm">No slots available.</p>';
                    return;
                }

                data.slots.forEach(slot => {
                    const btn = document.createElement('button');
                    const isBooked = slot.status === 'booked';
                    
                    btn.className = `py-2 px-1 rounded-lg text-xs font-semibold border transition-all duration-200 ${
                        isBooked 
                        ? 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed' 
                        : 'bg-white text-blue-700 border-blue-100 hover:border-blue-500 hover:bg-blue-50 hover:shadow-sm'
                    }`;
                    
                    btn.innerHTML = `${slot.label} ${isBooked ? '<i class="fa-solid fa-xmark ml-1 text-[10px]"></i>' : ''}`;
                    
                    if(isBooked) {
                        btn.disabled = true;
                    } else {
                        btn.onclick = () => selectSlot(slot.start, slot.label, btn);
                    }
                    slotsContainer.appendChild(btn);
                });
            })
            .catch(err => {
                console.error(err);
                slotsLoader.classList.add('hidden');
                slotsLoader.classList.remove('flex');
                slotsContainer.innerHTML = '<div class="col-span-full text-red-400 text-center text-xs">Error loading data</div>';
            });
    }

    function selectSlot(startTime, label, btn) {
        // Reset styles
        document.querySelectorAll('#slotsContainer button:not(:disabled)').forEach(b => {
            b.className = 'py-2 px-1 rounded-lg text-xs font-semibold border bg-white text-blue-700 border-blue-100 hover:border-blue-500 hover:bg-blue-50';
        });
        
        // Active style
        btn.className = 'py-2 px-1 rounded-lg text-xs font-semibold border bg-blue-800 border-blue-800 text-white shadow-md transform scale-105';
        
        document.getElementById('hiddenStartTime').value = startTime; // Ensure this is full datetime string from fetch
        document.getElementById('summaryTime').innerText = label;
        bookingForm.classList.remove('hidden');
        
        // Smooth scroll to form inside the container
        bookingForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // Handle Form Submit via AJAX for better UX
    bookingForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';

        const formData = new FormData(this);

        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                // Success feedback
                alert("Booking Confirmed!"); // Simple alert or you can use a nice modal
                // Refresh slots
                fetchSlots(selectedDateStr);
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(err => alert("Network Error"))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    });

    // Calendar Navigation
    document.getElementById('prevMonth').onclick = () => { currMonth--; if(currMonth<0){currMonth=11;currYear--}; renderCalendar(currMonth,currYear); };
    document.getElementById('nextMonth').onclick = () => { currMonth++; if(currMonth>11){currMonth=0;currYear++}; renderCalendar(currMonth,currYear); };
    
    // Init
    renderCalendar(currMonth, currYear);
</script>
</body>
</html>