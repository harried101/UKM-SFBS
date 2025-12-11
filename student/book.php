<?php
session_start();
require_once '../includes/db_connect.php';

// 1. Get Facility ID
$facility_id = $_GET['facility_id'] ?? '';
$facility_name = "Facility";

if ($facility_id) {
    // Fetch the facility name to display at the top
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Modern scrollbar hiding for clean UI */
        body::-webkit-scrollbar { display: none; }
        body { -ms-overflow-style: none; scrollbar-width: none; }
        
        /* Calendar Styles */
        .calendar-day { transition: all 0.2s ease; }
        .calendar-day:hover:not(.disabled):not(.selected) { background-color: #ffe4e6; color: #8a0d19; cursor: pointer; transform: scale(1.05); }
        .calendar-day.selected { background-color: #8a0d19; color: white; transform: scale(1.05); box-shadow: 0 4px 6px -1px rgba(138, 13, 25, 0.3); }
        .calendar-day.today { border: 2px solid #8a0d19; font-weight: bold; color: #8a0d19; }
        .calendar-day.disabled { color: #d1d5db; cursor: default; background: transparent; }
        
        /* Smooth fade in for slots */
        .fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-white p-4 sm:p-6 font-sans select-none">

    <!-- Header -->
    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-[#8a0d19] tracking-tight"><?php echo htmlspecialchars($facility_name); ?></h2>
        <p class="text-gray-500 text-sm mt-1">Select a date to check availability</p>
    </div>

    <div class="max-w-xl mx-auto bg-white rounded-xl">
        
        <!-- Month Navigation -->
        <div class="flex justify-between items-center mb-6 bg-gray-50 p-3 rounded-lg border border-gray-100">
            <button id="prevMonth" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-white hover:shadow-sm text-gray-600 hover:text-[#8a0d19] transition">
                <i class="fa-solid fa-chevron-left text-sm"></i>
            </button>
            <h3 id="monthYear" class="text-lg font-bold text-gray-800 tracking-wide"></h3>
            <button id="nextMonth" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-white hover:shadow-sm text-gray-600 hover:text-[#8a0d19] transition">
                <i class="fa-solid fa-chevron-right text-sm"></i>
            </button>
        </div>

        <!-- Calendar Grid -->
        <div class="grid grid-cols-7 gap-2 text-center mb-2 text-xs font-bold text-gray-400 uppercase tracking-wider">
            <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
        </div>
        <div id="calendarGrid" class="grid grid-cols-7 gap-2 text-center text-sm mb-8">
            <!-- JS Populates this -->
        </div>

        <!-- Legend -->
        <div id="legend" class="hidden flex justify-center gap-4 text-xs text-gray-500 mb-4 border-t border-gray-100 pt-4">
            <div class="flex items-center"><span class="w-3 h-3 rounded-sm bg-white border border-green-500 mr-1.5"></span> Available</div>
            <div class="flex items-center"><span class="w-3 h-3 rounded-sm bg-gray-100 border border-gray-200 mr-1.5"></span> Booked</div>
            <div class="flex items-center"><span class="w-3 h-3 rounded-sm bg-[#8a0d19] mr-1.5"></span> Selected</div>
        </div>

        <!-- Time Slots Section -->
        <div id="timeSlotsSection" class="hidden fade-in">
            <h4 class="font-bold text-gray-800 mb-4 flex items-center justify-between">
                <span><i class="fa-regular fa-clock mr-2 text-[#8a0d19]"></i> Available Slots</span>
                <span id="selectedDateDisplay" class="text-sm font-normal text-gray-500 bg-gray-100 px-2 py-1 rounded"></span>
            </h4>
            
            <!-- Loader -->
            <div id="slotsLoader" class="flex flex-col items-center justify-center py-8 text-gray-400">
                <i class="fa-solid fa-circle-notch fa-spin text-2xl mb-2 text-[#8a0d19]"></i>
                <span class="text-xs">Fetching schedule...</span>
            </div>

            <!-- Slots Grid -->
            <div id="slotsContainer" class="grid grid-cols-3 sm:grid-cols-4 gap-3"></div>
        </div>

        <!-- Booking Form (Submitted to book_fetch.php) -->
        <form id="bookingForm" action="book_fetch.php" method="POST" class="hidden mt-8 pt-4 border-t border-gray-100 sticky bottom-0 bg-white pb-2 fade-in">
            <input type="hidden" name="facility_id" value="<?php echo htmlspecialchars($facility_id); ?>">
            <input type="hidden" name="start_time" id="hiddenStartTime">
            <input type="hidden" name="end_time" id="hiddenEndTime">
            
            <div class="flex justify-between items-center mb-4 text-sm">
                <span class="text-gray-500">Selected Time:</span>
                <span id="summaryTime" class="font-bold text-[#8a0d19] text-lg">--:--</span>
            </div>

            <button type="submit" class="w-full bg-[#8a0d19] text-white py-3.5 rounded-xl font-bold hover:bg-[#720b15] transition shadow-lg shadow-red-900/20 flex justify-center items-center gap-2 group transform active:scale-[0.98]">
                <span>Confirm Booking</span>
                <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
            </button>
        </form>

    </div>

    <script>
        const facilityId = "<?php echo $facility_id; ?>";
        
        // Define your time slots here. 
        // This list creates the buttons. The 'isBooked' check later will disable buttons if taken.
        const TIME_SLOTS = [
            { start: "09:00:00", label: "09:00 AM" },
            { start: "10:00:00", label: "10:00 AM" },
            { start: "11:00:00", label: "11:00 AM" },
            { start: "12:00:00", label: "12:00 PM" },
            { start: "13:00:00", label: "01:00 PM" },
            { start: "14:00:00", label: "02:00 PM" },
            { start: "15:00:00", label: "03:00 PM" },
            { start: "16:00:00", label: "04:00 PM" },
            { start: "17:00:00", label: "05:00 PM" },
            { start: "18:00:00", label: "06:00 PM" },
            { start: "19:00:00", label: "07:00 PM" },
            { start: "20:00:00", label: "08:00 PM" },
            { start: "21:00:00", label: "09:00 PM" }
        ];

        let currDate = new Date();
        let currMonth = currDate.getMonth();
        let currYear = currDate.getFullYear();

        // DOM Elements
        const calendarGrid = document.getElementById('calendarGrid');
        const monthYear = document.getElementById('monthYear');
        const slotsSection = document.getElementById('timeSlotsSection');
        const slotsContainer = document.getElementById('slotsContainer');
        const slotsLoader = document.getElementById('slotsLoader');
        const bookingForm = document.getElementById('bookingForm');
        const legend = document.getElementById('legend');

        // --- 1. Render Calendar Grid ---
        function renderCalendar(month, year) {
            calendarGrid.innerHTML = "";
            monthYear.innerText = new Date(year, month).toLocaleString('default', { month: 'long', year: 'numeric' });

            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();

            // Empty cells for previous month
            for (let i = 0; i < firstDay; i++) {
                calendarGrid.appendChild(document.createElement('div'));
            }

            // Days
            for (let day = 1; day <= daysInMonth; day++) {
                const dateDiv = document.createElement('div');
                dateDiv.innerText = day;
                dateDiv.className = "calendar-day h-10 w-10 mx-auto flex items-center justify-center rounded-full text-sm font-medium";

                const checkDate = new Date(year, month, day);
                const today = new Date();
                today.setHours(0,0,0,0);
                
                // Disable past dates
                if (checkDate < today) {
                    dateDiv.classList.add('disabled');
                } else {
                    dateDiv.onclick = () => selectDate(day, month, year, dateDiv);
                }

                // Highlight Today
                if (day === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
                    dateDiv.classList.add('today');
                }

                calendarGrid.appendChild(dateDiv);
            }
        }

        // --- 2. Handle Date Selection ---
        function selectDate(day, month, year, element) {
            // UI Updates
            document.querySelectorAll('.calendar-day').forEach(el => el.classList.remove('selected'));
            element.classList.add('selected');
            
            // Format YYYY-MM-DD
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            document.getElementById('selectedDateDisplay').innerText = new Date(year, month, day).toLocaleDateString(undefined, {weekday: 'short', month: 'short', day: 'numeric'});

            // Show sections
            legend.classList.remove('hidden');
            slotsSection.classList.remove('hidden');
            bookingForm.classList.add('hidden'); // Hide form until time selected
            
            // Scroll to slots to make it obvious
            slotsSection.scrollIntoView({ behavior: 'smooth' });

            fetchSlots(dateStr);
        }

        // --- 3. Fetch Booked Slots ---
        function fetchSlots(dateStr) {
            slotsContainer.innerHTML = '';
            slotsLoader.classList.remove('hidden');

            fetch(`book_fetch.php?get_slots=1&date=${dateStr}&facility_id=${facilityId}`)
                .then(res => res.json())
                .then(data => {
                    slotsLoader.classList.add('hidden');
                    if (!data.success) {
                        slotsContainer.innerHTML = `<div class="col-span-full text-red-500 text-center text-sm">${data.message}</div>`;
                        return;
                    }

                    // data.booked_slots contains e.g. ["09:00:00", "14:00:00"]
                    const bookedTimes = data.booked_slots || [];
                    
                    TIME_SLOTS.forEach(slot => {
                        const btn = document.createElement('button');
                        
                        // Check if time is in booked array
                        const isBooked = bookedTimes.includes(slot.start);

                        btn.className = `py-2.5 px-1 rounded-lg text-xs font-semibold border transition-all duration-200 ${
                            isBooked 
                            ? 'bg-gray-50 text-gray-300 border-gray-100 cursor-not-allowed' 
                            : 'bg-white text-green-700 border-green-200 hover:border-green-500 hover:shadow-md hover:-translate-y-0.5'
                        }`;
                        
                        btn.innerHTML = `
                            ${slot.label}
                            ${isBooked ? '<i class="fa-solid fa-ban ml-1 opacity-50"></i>' : ''}
                        `;
                        
                        if(isBooked) {
                            btn.disabled = true;
                        } else {
                            btn.onclick = () => selectSlot(slot.start, slot.label, dateStr, btn);
                        }
                        slotsContainer.appendChild(btn);
                    });
                })
                .catch(err => {
                    slotsLoader.classList.add('hidden');
                    slotsContainer.innerHTML = '<div class="col-span-full text-red-400 text-center text-sm">Connection Error</div>';
                });
        }

        // --- 4. Handle Slot Click ---
        function selectSlot(startTime, label, dateStr, btnElement) {
            // Reset styles
            document.querySelectorAll('#slotsContainer button:not(:disabled)').forEach(b => {
                b.className = 'py-2.5 px-1 rounded-lg text-xs font-semibold border bg-white text-green-700 border-green-200 transition-all';
            });
            // Highlight active
            btnElement.className = 'py-2.5 px-1 rounded-lg text-xs font-semibold border bg-[#8a0d19] border-[#8a0d19] text-white shadow-md transform scale-105';

            // Calculate Times for DB (YYYY-MM-DD HH:MM:SS)
            const fullStart = `${dateStr} ${startTime}`;
            
            // Calculate End Time (Start + 1 Hour)
            let [h, m, s] = startTime.split(':');
            let endH = parseInt(h) + 1;
            const fullEnd = `${dateStr} ${String(endH).padStart(2, '0')}:${m}:${s}`;

            // Populate Hidden Form
            document.getElementById('hiddenStartTime').value = fullStart;
            document.getElementById('hiddenEndTime').value = fullEnd;
            document.getElementById('summaryTime').innerText = label;

            bookingForm.classList.remove('hidden');
            setTimeout(() => {
                bookingForm.scrollIntoView({ behavior: 'smooth' });
            }, 50);
        }

        // Initialize Calendar
        document.getElementById('prevMonth').onclick = () => { currMonth--; if(currMonth<0){currMonth=11;currYear--}; renderCalendar(currMonth,currYear); };
        document.getElementById('nextMonth').onclick = () => { currMonth++; if(currMonth>11){currMonth=0;currYear++}; renderCalendar(currMonth,currYear); };
        
        renderCalendar(currMonth, currYear);
    </script>
</body>
</html>