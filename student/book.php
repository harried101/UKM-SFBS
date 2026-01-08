<?php
// ==========================================
// BOOK.PHP - UPDATED FOR CANCELLATION CHECK INTEGRATION
// ==========================================

require_once 'includes/student_auth.php';



// FIX: Use the correct relative path to include the file.
// This now imports the 'get_cancellation_stats_internal' function.
require_once 'API/fetch_cancellation_health.php'; 


// --- PHP logic to check cancellation status on page load (NEW) ---
$is_blocked = false;
$cancellation_status = [
    'rate_value' => 0,
    'rate_status' => 'No Data',
    'status_color' => 'gray',
    'message' => 'Checking health status...'
];

if (isset($_SESSION['user_id'])) {
    // 1. Get Student Identifier from session
    $studentIdentifier = $_SESSION['user_id'];

    // 2. Use the internal function to get statistics
    $status_data = get_cancellation_stats_internal($conn, $studentIdentifier);

    // 3. Update status variables if successful
    if (!isset($status_data['error'])) {
        $cancellation_status = $status_data;
        $is_blocked = $status_data['is_blocked'];
    }
}
// --- END PHP logic ---


$facility_id = $_GET['facility_id'] ?? '';
$facility_name = "Facility";

if ($facility_id) {
    $stmt = $conn->prepare("SELECT Name FROM facilities WHERE FacilityID = ?");
    $stmt->bind_param("s", $facility_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $facility_name = $row['Name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Facility</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Hide scrollbar for iframe aesthetics */
        body::-webkit-scrollbar { display: none; }
        body { -ms-overflow-style: none; scrollbar-width: none; user-select: none; }
        
        /* Blue Theme Calendar */
        .calendar-day { transition: all 0.2s; }
        .calendar-day:hover:not(.disabled):not(.selected) { background-color: #e0f2fe; color: #0b4d9d; cursor: pointer; font-weight: bold; }
        .calendar-day.selected { background-color: #0b4d9d; color: white; transform: scale(1.1); box-shadow: 0 4px 6px -1px rgba(11, 77, 157, 0.4); z-index: 10; }
        .calendar-day.today { border: 2px solid #0b4d9d; font-weight: bold; color: #0b4d9d; }
        .calendar-day.disabled { color: #cbd5e1; cursor: default; }

        .fade-in { animation: fadeIn 0.4s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-white p-6 font-sans text-gray-800">

    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-[#0b4d9d] tracking-tight"><?php echo htmlspecialchars($facility_name); ?></h2>
        <p class="text-gray-500 text-sm mt-1">Select a date to view available time slots.</p>
    </div>

    <div class="max-w-lg mx-auto bg-white">
        
        <div id="cancellation-status-banner" class="mb-6 rounded-xl p-4 transition-all duration-300 
            <?php 
                // Render the correct color and show the banner based on PHP check
                if ($is_blocked) {
                    echo 'bg-red-50 border border-red-100';
                } elseif ($cancellation_status['status_color'] === 'amber') {
                    echo 'bg-yellow-50 border border-yellow-100';
                } elseif ($cancellation_status['status_color'] === 'green' && ($cancellation_status['total_weekly'] ?? 0) > 0) {
                     echo 'bg-green-50 border border-green-100';
                } else {
                    echo 'hidden'; // Hide if no meaningful data or error
                }
            ?>">
            <div class="flex items-start gap-4">
                <i id="status-icon" class="fa-solid text-xl flex-shrink-0 
                    <?php 
                        if ($is_blocked) echo 'fa-triangle-exclamation text-red-600'; 
                        elseif ($cancellation_status['status_color'] === 'amber') echo 'fa-exclamation text-yellow-600'; 
                        elseif ($cancellation_status['status_color'] === 'green') echo 'fa-circle-check text-green-600'; 
                    ?>"></i>
                <div>
                    <h5 id="status-title" class="font-bold text-lg leading-tight 
                        <?php 
                            if ($is_blocked) echo 'text-red-800'; 
                            elseif ($cancellation_status['status_color'] === 'amber') echo 'text-yellow-800'; 
                            elseif ($cancellation_status['status_color'] === 'green') echo 'text-green-800'; 
                        ?>">
                        Cancellation Health: <?php echo htmlspecialchars($cancellation_status['rate_value'] ?? '0'); ?>% (<?php echo htmlspecialchars($cancellation_status['rate_status'] ?? 'No Data'); ?>)
                    </h5>
                    <p id="status-message" class="text-sm mt-0.5 
                        <?php 
                            if ($is_blocked) echo 'text-red-600'; 
                            elseif ($cancellation_status['status_color'] === 'amber') echo 'text-yellow-600'; 
                            elseif ($cancellation_status['status_color'] === 'green') echo 'text-green-600'; 
                        ?>">
                        <?php echo htmlspecialchars($cancellation_status['message'] ?? 'Checking health status...'); ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center mb-6 bg-gray-50 p-2 rounded-xl border border-gray-100">
            <button id="prevMonth" class="w-10 h-10 flex items-center justify-center rounded-lg hover:bg-white hover:text-[#0b4d9d] hover:shadow-sm transition text-gray-500" <?php if ($is_blocked) echo 'disabled'; ?>>
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <h3 id="monthYear" class="text-lg font-bold text-gray-800"></h3>
            <button id="nextMonth" class="w-10 h-10 flex items-center justify-center rounded-lg hover:bg-white hover:text-[#0b4d9d] hover:shadow-sm transition text-gray-500" <?php if ($is_blocked) echo 'disabled'; ?>>
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>

        <div class="grid grid-cols-7 gap-2 text-center mb-2 text-xs font-bold text-gray-400 uppercase tracking-wider">
            <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
        </div>
        <div id="calendarGrid" class="grid grid-cols-7 gap-2 text-center text-sm mb-6"></div>

        <div id="legend" class="hidden flex justify-center gap-6 text-xs text-gray-500 mb-6 border-t border-gray-100 pt-4">
            <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-white border border-[#0b4d9d]"></span> Available</div>
            <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-gray-100 border border-gray-300"></span> Booked</div>
            <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#0b4d9d]"></span> Selected</div>
        </div>

        <div id="timeSlotsSection" class="hidden fade-in">
            <h4 class="font-bold text-gray-800 mb-4 flex items-center justify-between">
                <span><i class="fa-regular fa-clock mr-2 text-[#0b4d9d]"></i> Available Slots</span>
                <span id="selectedDateDisplay" class="text-xs font-medium bg-blue-50 text-blue-800 px-2 py-1 rounded-md border border-blue-100"></span>
            </h4>
            
            <div id="slotsLoader" class="flex flex-col items-center justify-center py-8 text-gray-400">
                <i class="fa-solid fa-circle-notch fa-spin text-2xl mb-2 text-[#0b4d9d]"></i>
                <span class="text-xs">Checking schedule...</span>
            </div>
            
            <div id="slotsContainer" class="grid grid-cols-3 sm:grid-cols-4 gap-3 max-h-60 overflow-y-auto pr-1"></div>
        </div>

        <form id="bookingForm" action="book_fetch.php" method="POST" class="hidden mt-6 pt-4 border-t border-gray-100 sticky bottom-0 bg-white pb-2 fade-in">
            <input type="hidden" name="facility_id" value="<?php echo htmlspecialchars($facility_id); ?>">
            <input type="hidden" name="start_time" id="hiddenStartTime">
            
            <div class="flex justify-between items-center mb-4 text-sm bg-blue-50 p-3 rounded-lg border border-blue-100">
                <span class="text-blue-800 font-medium">Selected Slot:</span>
                <span id="summaryTime" class="font-bold text-[#0b4d9d] text-lg">--:--</span>
            </div>
            
            <button type="submit" id="confirmBookingBtn" 
                class="w-full py-3.5 rounded-xl font-bold transition shadow-lg flex items-center justify-center gap-2 transform active:scale-[0.98]
                <?php 
                    if ($is_blocked) {
                        echo 'bg-gray-300 text-gray-500 cursor-not-allowed shadow-none';
                    } else {
                        echo 'bg-[#0b4d9d] text-white hover:bg-[#083a75] shadow-blue-900/20';
                    }
                ?>"
                <?php if ($is_blocked) echo 'disabled'; ?>
            >
                <span>
                    <?php 
                        if ($is_blocked) {
                            echo 'Booking Blocked (High Cancellation Rate)';
                        } else {
                            echo 'Confirm Booking';
                        }
                    ?>
                </span>
                <?php if (!$is_blocked) { ?>
                    <i class="fa-solid fa-arrow-right"></i>
                <?php } ?>
            </button>
        </form>
    </div>

    <script>
        const facilityId = "<?php echo $facility_id; ?>";
        // The JS check for the rate is redundant since PHP sets the 'isBlocked' flag, 
        // but we keep the API endpoint for future use or verification.
        const cancelHealthApi = 'API/fetch_cancellation_health.php'; 
        let isBlocked = <?php echo $is_blocked ? 'true' : 'false'; ?>; 

        let currDate = new Date();
        let currMonth = currDate.getMonth();
        let currYear = currDate.getFullYear();

        const calendarGrid = document.getElementById('calendarGrid');
        const monthYear = document.getElementById('monthYear');
        const slotsSection = document.getElementById('timeSlotsSection');
        const slotsContainer = document.getElementById('slotsContainer');
        const slotsLoader = document.getElementById('slotsLoader');
        const bookingForm = document.getElementById('bookingForm');
        const confirmBookingBtn = document.getElementById('confirmBookingBtn');
        const legend = document.getElementById('legend');

        // --- PRELOAD: Fetch Closed Days for this Facility ---
        let closedDaysOfWeek = []; // Stores indices of closed days (e.g. [0, 6] for Sun/Sat)

        function loadFacilitySettings() {
            fetch(`book_fetch.php?get_closed_days=1&facility_id=${facilityId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        closedDaysOfWeek = data.closed_days || [];
                        renderCalendar(currMonth, currYear); // Re-render with new data
                    }
                })
                .catch(err => console.error("Failed to load schedule", err));
        }

        // --- CALENDAR FUNCTIONS (Renders all days as disabled if blocked) ---
        function renderCalendar(month, year) {
            calendarGrid.innerHTML = "";
            monthYear.innerText = new Date(year, month).toLocaleString('default', { month: 'long', year: 'numeric' });

            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const today = new Date();
            today.setHours(0,0,0,0);

            // Empty cells
            for (let i = 0; i < firstDay; i++) {
                calendarGrid.appendChild(document.createElement('div'));
            }

            // Days
            for (let day = 1; day <= daysInMonth; day++) {
                const dateDiv = document.createElement('div');
                dateDiv.innerText = day;
                dateDiv.className = "calendar-day h-10 w-10 mx-auto flex items-center justify-center rounded-full text-sm font-medium";
                
                const checkDate = new Date(year, month, day);
                const dayOfWeek = checkDate.getDay();

                // DISABLE conditions:
                // 1. Past date
                // 2. User is blocked (bad health)
                // 3. Facility is closed on this day (NEW)
                if (checkDate < today || isBlocked || closedDaysOfWeek.includes(dayOfWeek)) { 
                    dateDiv.classList.add('disabled');
                    // Optional: Visual cue for closed facility days vs just past dates
                    if (closedDaysOfWeek.includes(dayOfWeek) && checkDate >= today) {
                        dateDiv.style.opacity = '0.4'; 
                        dateDiv.title = "Facility Closed";
                    }
                } else {
                    dateDiv.onclick = () => selectDate(day, month, year, dateDiv);
                }

                if (day === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
                    dateDiv.classList.add('today');
                }

                calendarGrid.appendChild(dateDiv);
            }
        }
        
        // Initial Load
        loadFacilitySettings();


        function selectDate(day, month, year, element) {
            if (isBlocked) return;

            // UI Selection
            document.querySelectorAll('.calendar-day').forEach(el => el.classList.remove('selected'));
            element.classList.add('selected');
            
            // Format YYYY-MM-DD
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            
            // Display Date
            const displayDate = new Date(year, month, day).toLocaleDateString('en-US', {weekday: 'short', day: 'numeric', month: 'short'});
            document.getElementById('selectedDateDisplay').innerText = displayDate;
            
            legend.classList.remove('hidden');
            slotsSection.classList.remove('hidden');
            bookingForm.classList.add('hidden');
            
            // Auto scroll
            setTimeout(() => slotsSection.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);

            fetchSlots(dateStr);
        }

        function fetchSlots(dateStr) {
            slotsContainer.innerHTML = '';
            slotsLoader.classList.remove('hidden');

            if (isBlocked) {
                slotsLoader.classList.add('hidden');
                slotsContainer.innerHTML = '<div class="col-span-full text-red-600 text-center py-4 font-bold">You are currently blocked from viewing and booking slots.</div>';
                return;
            }

            fetch(`book_fetch.php?get_slots=1&date=${dateStr}&facility_id=${facilityId}`)
                .then(res => res.json())
                .then(data => {
                    slotsLoader.classList.add('hidden');
                    
                    if (data.is_closed) {
                        slotsContainer.innerHTML = `
                            <div class="col-span-full bg-red-50 border border-red-100 text-red-600 rounded-lg p-4 text-center text-sm">
                                <i class="fa-solid fa-ban text-lg mb-2 block"></i>
                                <span class="font-bold block">Not Available</span>
                                ${data.message}
                            </div>`;
                        return;
                    }

                    if (!data.slots || data.slots.length === 0) {
                        slotsContainer.innerHTML = '<div class="col-span-full text-gray-400 text-center py-4">No slots available.</div>';
                        return;
                    }

                    data.slots.forEach(slot => {
                        const btn = document.createElement('button');
                        const isBooked = slot.status === 'booked';
                        
                        btn.className = `py-2.5 px-1 rounded-lg text-xs font-semibold border transition-all duration-200 flex flex-col items-center justify-center gap-1 ${
                            isBooked 
                            ? 'bg-gray-50 text-gray-300 border-gray-100 cursor-not-allowed' 
                            : 'bg-white text-[#0b4d9d] border-blue-100 hover:border-[#0b4d9d] hover:bg-blue-50 hover:shadow-md'
                        }`;
                        
                        btn.type = "button";
                        btn.innerHTML = `<span>${slot.label}</span>`;
                        
                        if(isBooked) {
                            btn.disabled = true;
                            btn.innerHTML += `<i class="fa-solid fa-xmark text-[10px]"></i>`;
                        } else {
                            btn.onclick = () => selectSlot(slot.start, slot.label, dateStr, btn);
                        }
                        slotsContainer.appendChild(btn);
                    });
                })
                .catch(err => {
                    console.error(err);
                    slotsLoader.classList.add('hidden');
                    slotsContainer.innerHTML = '<div class="col-span-full text-red-400 text-center text-sm">Connection Error</div>';
                });
        }

        function selectSlot(startTime, label, dateStr, btn) {
            if (isBlocked) return; 

            // Reset styles
            document.querySelectorAll('#slotsContainer button:not(:disabled)').forEach(b => {
                b.className = 'py-2.5 px-1 rounded-lg text-xs font-semibold border bg-white text-[#0b4d9d] border-blue-100 hover:border-[#0b4d9d] hover:bg-blue-50 transition-all flex flex-col items-center justify-center gap-1';
            });
            
            // Active Style
            btn.className = 'py-2.5 px-1 rounded-lg text-xs font-semibold border bg-[#0b4d9d] border-[#0b4d9d] text-white shadow-md transform scale-105 flex flex-col items-center justify-center gap-1';
            
            document.getElementById('hiddenStartTime').value = startTime;
            document.getElementById('summaryTime').innerText = label;
            
            bookingForm.classList.remove('hidden');
            setTimeout(() => bookingForm.scrollIntoView({ behavior: 'smooth' }), 50);
        }

        // --- Handle Form Submit (Defensive check for BE blocking) ---
        const form = document.getElementById('bookingForm');
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            
            if (isBlocked) {
                alert('Booking is currently blocked due to a high weekly cancellation rate.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';

            const formData = new FormData(this);

            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(res => {
                // Check for 403 Forbidden status, expected if the BE (book_fetch.php) implements blocking
                if (res.status === 403) {
                    // Try to parse the JSON response for the custom error code
                    return res.json().then(data => {
                        if (data && data.code === 'CANCELLATION_BLOCKED') {
                            // The BE sent a specific blocking error
                            throw new Error(data.message || 'Booking denied due to cancellation rate.');
                        }
                        // Other 403 error
                        throw new Error('Access denied: ' + (data.message || 'Unknown reason.'));
                    });
                }
                // Check for other standard JSON responses
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Booking Successful!');
                    // Reload parent page to update any lists
                    if (window.parent && window.parent !== window) {
                        window.parent.location.reload();
                    } else {
                        location.reload();
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error: ' + err.message); // Show the specific error message
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });

        // Init
        document.getElementById('prevMonth').onclick = () => { currMonth--; if(currMonth<0){currMonth=11;currYear--}; renderCalendar(currMonth,currYear); };
        document.getElementById('nextMonth').onclick = () => { currMonth++; if(currMonth>11){currMonth=0;currYear++}; renderCalendar(currMonth,currYear); };
        renderCalendar(currMonth, currYear);
    </script>
    
    <?php include 'includes/footer.php'; ?>
    <script>
// This function tells the parent window to reset its timer
function notifyParent() {
    if (window.parent && window.parent.resetTimer) {
        window.parent.resetTimer();
    }
}

// Listen for activity inside the calendar modal
window.onmousemove = notifyParent;
window.onmousedown = notifyParent;
window.onkeypress = notifyParent;
window.onscroll = notifyParent;
</script>
</body>
</html>