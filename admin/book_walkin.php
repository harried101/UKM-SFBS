<?php
session_start();
require_once '../includes/db_connect.php';

// SECURITY: Only Admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    die("Access Denied");
}

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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
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
        body::-webkit-scrollbar { display: none; }
        body { 
            -ms-overflow-style: none; 
            scrollbar-width: none; 
            font-family: 'Inter', sans-serif;
            background-color: #fff;
        }
        h1, h2, h3, h4, h5, h6 { font-family: 'Plus Jakarta Sans', sans-serif; }
        .calendar-day:hover:not(.disabled):not(.selected) { background-color: #e0f2fe; color: #0b4d9d; cursor: pointer; transform: scale(1.05); }
        .calendar-day.selected { background-color: #0b4d9d; color: white; transform: scale(1.05); box-shadow: 0 4px 6px -1px rgba(11, 77, 157, 0.3); }
        .calendar-day.today { border: 2px solid #0b4d9d; font-weight: bold; color: #0b4d9d; }
        .calendar-day.disabled { color: #d1d5db; cursor: default; }
        .fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        
        .btn-primary-custom {
            background-color: #0b4d9d;
            color: white;
        }
        .btn-primary-custom:hover {
            background-color: #083a75;
        }
    </style>
    <title>Walk-in Booking - <?php echo htmlspecialchars($facility_name); ?></title>
</head>
<body class="p-4 select-none">

    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-[#071239]"><?php echo htmlspecialchars($facility_name); ?></h2>
        <p class="text-gray-500 text-sm mt-1">Admin Walk-in Booking</p>
    </div>

    <div class="max-w-xl mx-auto bg-white rounded-xl">
        <!-- Month  -->
        <div class="flex justify-between items-center mb-6 bg-[#bfd9dc]/30 p-3 rounded-lg border border-[#bfd9dc]">
            <button id="prevMonth" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-white text-[#071239] transition"><i class="fa-solid fa-chevron-left text-sm"></i></button>
            <h3 id="monthYear" class="text-lg font-bold text-[#071239]"></h3>
            <button id="nextMonth" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-white text-[#071239] transition"><i class="fa-solid fa-chevron-right text-sm"></i></button>
        </div>

        <!-- Calendar -->
        <div class="grid grid-cols-7 gap-2 text-center mb-2 text-xs font-bold text-gray-400 uppercase">
            <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
        </div>
        <div id="calendarGrid" class="grid grid-cols-7 gap-2 text-center text-sm mb-8"></div>

        <!-- Legend -->
        <div id="legend" class="hidden flex justify-center gap-4 text-xs text-gray-500 mb-4 border-t border-gray-100 pt-4">
            <div class="flex items-center"><span class="w-3 h-3 rounded-sm bg-white border border-green-500 mr-1.5"></span> Available</div>
            <div class="flex items-center"><span class="w-3 h-3 rounded-sm bg-gray-100 border border-gray-200 mr-1.5"></span> Booked</div>
            <div class="flex items-center"><span class="w-3 h-3 rounded-sm bg-[#071239] mr-1.5"></span> Selected</div>
        </div>

        <!-- Slots Section -->
        <div id="timeSlotsSection" class="hidden fade-in">
            <h4 class="font-bold text-[#071239] mb-4 flex items-center justify-between">
                <span><i class="fa-regular fa-clock mr-2"></i> Available Slots</span>
                <span id="selectedDateDisplay" class="text-sm font-normal text-gray-600 bg-[#bfd9dc]/30 px-2 py-1 rounded"></span>
            </h4>
            <div id="slotsLoader" class="flex flex-col items-center justify-center py-8 text-gray-400">
                <i class="fa-solid fa-circle-notch fa-spin text-2xl mb-2 text-[#071239]"></i> Checking schedule...
            </div>
            <div id="slotsContainer" class="grid grid-cols-3 sm:grid-cols-4 gap-3"></div>
        </div>

        <!-- Booking Form -->
        <form id="bookingForm" action="book_walkin_fetch.php" method="POST" class="hidden mt-8 pt-4 border-t border-gray-100 sticky bottom-0 bg-white pb-2 fade-in">
            <input type="hidden" name="facility_id" value="<?php echo htmlspecialchars($facility_id); ?>" />
            <input type="hidden" name="start_time" id="hiddenStartTime" />
            
            <div class="mb-4">
                <label class="block text-sm font-bold text-[#071239] mb-2">Student ID (Required)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                        <i class="fa-solid fa-id-card"></i>
                    </span>
                    <input type="text" name="student_id" required placeholder="e.g. A123456" 
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#071239] focus:border-transparent uppercase font-medium text-[#071239]">
                </div>
                <p class="text-xs text-gray-500 mt-1">Enter the student's matric number.</p>
            </div>

            <div class="flex justify-between items-center mb-4 text-sm">
                <span class="text-gray-500">Selected Time:</span>
                <span id="summaryTime" class="font-bold text-[#071239] text-lg">--:--</span>
            </div>
            <button type="submit" class="w-full bg-[#071239] text-white py-3.5 rounded-xl font-bold hover:bg-[#0a1a52] transition shadow-lg flex items-center justify-center gap-2">
                <i class="fa-solid fa-check-circle"></i> Confirm Walk-in Booking
            </button>
        </form>
    </div>

<script>
    const facilityId = "<?php echo $facility_id; ?>";
    let currDate = new Date();
    let currMonth = currDate.getMonth();
    let currYear = currDate.getFullYear();

    const calendarGrid = document.getElementById('calendarGrid');
    const monthYear = document.getElementById('monthYear');
    const slotsSection = document.getElementById('timeSlotsSection');
    const slotsContainer = document.getElementById('slotsContainer');
    const slotsLoader = document.getElementById('slotsLoader');
    const bookingForm = document.getElementById('bookingForm');
    const legend = document.getElementById('legend');

    function renderCalendar(month, year) {
        calendarGrid.innerHTML = "";
        monthYear.innerText = new Date(year, month).toLocaleString('default', { month: 'long', year: 'numeric' });
        let firstDay = new Date(year, month, 1).getDay();
        let daysInMonth = new Date(year, month + 1, 0).getDate();
        for (let i = 0; i < firstDay; i++) calendarGrid.appendChild(document.createElement('div'));

        for (let day = 1; day <= daysInMonth; day++) {
            const dateDiv = document.createElement('div');
            dateDiv.innerText = day;
            dateDiv.className = "calendar-day h-10 w-10 mx-auto flex items-center justify-center rounded-full text-sm font-medium";
            
            const checkDate = new Date(year, month, day);
            const today = new Date();
            today.setHours(0,0,0,0);

            if (checkDate < today) {
                dateDiv.classList.add('disabled');
            } else {
                dateDiv.onclick = () => selectDate(day, month, year, dateDiv);
            }
            if (day === today.getDate() && month === today.getMonth() && year === today.getFullYear()) dateDiv.classList.add('today');
            calendarGrid.appendChild(dateDiv);
        }
    }

    function selectDate(day, month, year, element) {
        document.querySelectorAll('.calendar-day').forEach(el => el.classList.remove('selected'));
        element.classList.add('selected');
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        document.getElementById('selectedDateDisplay').innerText = new Date(year, month, day).toLocaleDateString(undefined, {weekday: 'short', month: 'short', day: 'numeric'});
        
        legend.classList.remove('hidden');
        slotsSection.classList.remove('hidden');
        bookingForm.classList.add('hidden');
        slotsSection.scrollIntoView({ behavior: 'smooth' });
        fetchSlots(dateStr);
    }

    function fetchSlots(dateStr) {
        slotsContainer.innerHTML = '';
        slotsLoader.classList.remove('hidden');

        fetch(`book_walkin_fetch.php?get_slots=1&date=${dateStr}&facility_id=${facilityId}`)
            .then(res => res.json())
            .then(data => {
                slotsLoader.classList.add('hidden');
                
                if (data.is_closed) {
                    slotsContainer.innerHTML = `
                        <div class="col-span-full bg-red-50 border border-red-100 text-red-600 rounded-lg p-4 text-center">
                            <i class="fa-solid fa-circle-exclamation text-xl mb-2 block"></i>
                            <p class="font-bold">Not Available</p>
                            <p class="text-sm opacity-90">${data.message}</p>
                        </div>`;
                    return;
                }

                data.slots.forEach(slot => {
                    const btn = document.createElement('button');
                    const isBooked = slot.status === 'booked';
                    btn.className = `py-2.5 px-1 rounded-lg text-xs font-semibold border transition-all duration-200 ${
                        isBooked 
                        ? 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed opacity-75' 
                        : 'bg-white text-green-700 border-green-200 hover:border-green-500 hover:shadow-md'
                    }`;
                    
                    btn.innerHTML = `${slot.label} ${isBooked ? '<i class="fa-solid fa-ban ml-1 opacity-50"></i>' : ''}`;
                    
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

    function selectSlot(startTime, label, dateStr, btn) {
        document.querySelectorAll('#slotsContainer button:not(:disabled)').forEach(b => b.className = 'py-2.5 px-1 rounded-lg text-xs font-semibold border bg-white text-green-700 border-green-200 transition-all');
        btn.className = 'py-2.5 px-1 rounded-lg text-xs font-semibold border bg-[#071239] border-[#071239] text-white shadow-md transform scale-105';
        
        document.getElementById('hiddenStartTime').value = startTime;
        document.getElementById('summaryTime').innerText = label;
        bookingForm.classList.remove('hidden');
        setTimeout(() => bookingForm.scrollIntoView({ behavior: 'smooth' }), 50);
    }

    bookingForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(bookingForm);

        fetch(bookingForm.action, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.parent.postMessage({ type: 'booking_success', message: data.message }, '*');
            } else {
                alert(`Error: ${data.message}`);
            }
        })
        .catch(() => {
            alert('Network error. Please try again.');
        });
    });

    document.getElementById('prevMonth').onclick = () => { currMonth--; if(currMonth<0){currMonth=11;currYear--}; renderCalendar(currMonth,currYear); };
    document.getElementById('nextMonth').onclick = () => { currMonth++; if(currMonth>11){currMonth=0;currYear++}; renderCalendar(currMonth,currYear); };
    renderCalendar(currMonth, currYear);
</script>

</body>
</html>
