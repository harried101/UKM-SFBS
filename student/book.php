<?php
session_start();

require_once '../includes/db_connect.php'; 

if ($conn->connect_error) {
    die("DB Connection failed. Please try again later.");
}

if (!isset($_GET['facility_id'])) {
    die("Error: Facility ID is missing.");
}

$facilityID = htmlspecialchars($_GET['facility_id']);
$facilityName = $facilityID;

// Fetch facility name for display
$sql_facility = "SELECT Name FROM facilities WHERE FacilityID = ? AND Status = 'Active'";
if ($stmt_f = $conn->prepare($sql_facility)) {
    $stmt_f->bind_param("s", $facilityID);
    $stmt_f->execute();
    $result_f = $stmt_f->get_result();
    if ($row_f = $result_f->fetch_assoc()) {
        $facilityName = $row_f['Name'] . " (" . $facilityID . ")";
    }
    $stmt_f->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sports Facility Booking</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    #calendar {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 5px;
        margin-top: 10px;
    }

    .calendar-weekday {
        font-weight: bold;
        text-align: center;
        padding: 10px 0;
        background-color: #007bff;
        color: white;
        border-radius: 5px;
    }

    .calendar-day {
        padding: 20px 0;
        text-align: center;
        cursor: pointer;
        border-radius: 5px;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        transition: all 0.2s;
        opacity: 1; 
        pointer-events: auto;
    }
    
    .past-day {
        opacity: 0.5;
        pointer-events: none;
    }

    .calendar-day:hover { background-color: #cce5ff; }
    .selected-day { background-color: #007bff; color: white; }
    .today { border: 2px solid #28a745; font-weight: bold; }

    .time-slot-btn {
        transition: background-color 0.2s, color 0.2s;
    }
    .time-slot-btn.selected-day {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
    }
    .time-slot-btn:disabled {
        background-color: #ffcccc !important;
        color: #8a0000 !important;
        border-color: #ffcccc !important;
        cursor: not-allowed;
    }
</style>
</head>
<body class="p-4">
<div class="container">
    <h1 class="mb-4 text-center">UKM Sports Facility Booking System</h1>

    <div class="text-center mb-3">
        <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#bookingModal">
            Check Availability
        </button>
    </div>

    <div class="modal fade" id="bookingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="bookingForm" action="book_fetch.php" method="POST"> 
                    <div class="modal-header">
                        <h5 class="modal-title">Book Facility: **<?php echo htmlspecialchars($facilityName); ?>**</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        
                        <input type="hidden" name="facility_id" id="hiddenFacilityID" value="<?php echo $facilityID; ?>">
                        <input type="hidden" name="start_time" id="hiddenStartTime" value="">
                        <input type="hidden" name="end_time" id="hiddenEndTime" value="">
                        
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <button id="prevMonth" type="button" class="btn btn-sm btn-outline-secondary">&lt; Prev</button>
                            <h5 id="monthYear" class="mb-0"></h5>
                            <button id="nextMonth" type="button" class="btn btn-sm btn-outline-secondary">Next &gt;</button>
                        </div>

                        <div id="calendar"></div>

                        <div id="courtSelect" style="margin-top:20px;"> 
                            <h6>Selected Facility: **<?php echo htmlspecialchars($facilityName); ?>**</h6>
                            <p class="text-muted small">Please select a date and time slot below.</p>
                        </div>

                        <div id="timeSlots" style="margin-top:20px; display:none;">
                            <h6>Available Time Slots for Selected Date:</h6>
                            <div class="d-flex flex-wrap" id="timeSlotButtons">
                                <p class="text-muted" id="timeSlotMessage">Select a date to check availability.</p>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success" id="submitBookingBtn" disabled>Confirm Booking</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const calendar = document.getElementById('calendar');
    const monthYear = document.getElementById('monthYear');
    const timeSlotsDiv = document.getElementById('timeSlots');
    const timeSlotButtonsDiv = document.getElementById('timeSlotButtons');
    const timeSlotMessage = document.getElementById('timeSlotMessage');
    const submitBookingBtn = document.getElementById('submitBookingBtn');
    const prevMonthBtn = document.getElementById('prevMonth');
    const nextMonthBtn = document.getElementById('nextMonth');

    const hiddenFacilityID = document.getElementById('hiddenFacilityID');
    const hiddenStartTime = document.getElementById('hiddenStartTime');
    const hiddenEndTime = document.getElementById('hiddenEndTime');

    const ALL_TIME_SLOTS = [
        { start: "09:00:00", end: "10:00:00", label: "9:00 - 10:00" },
        { start: "10:00:00", end: "11:00:00", label: "10:00 - 11:00" },
        { start: "11:00:00", end: "12:00:00", label: "11:00 - 12:00" },
        { start: "12:00:00", end: "13:00:00", label: "12:00 - 13:00" },
        { start: "13:00:00", end: "14:00:00", label: "13:00 - 14:00" },
        { start: "14:00:00", end: "15:00:00", label: "14:00 - 15:00" },
        { start: "16:00:00", end: "17:00:00", label: "16:00 - 17:00" },
        { start: "18:00:00", end: "19:00:00", label: "18:00 - 19:00" },
    ];

    let today = new Date();
    let currentMonth = today.getMonth();
    let currentYear = today.getFullYear();
    let selectedDate = null;
    let selectedStartTime = null; 
    let bookedSlots = []; 

    function updateSubmitButton() {
        submitBookingBtn.disabled = !(selectedDate && selectedStartTime);
    }

    function renderCalendar(month, year) {
        calendar.innerHTML = '';

        const weekdays = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        weekdays.forEach(day => {
            const dayHeader = document.createElement('div');
            dayHeader.classList.add('calendar-weekday');
            dayHeader.textContent = day;
            calendar.appendChild(dayHeader);
        });

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        for (let i = 0; i < firstDay; i++) {
            calendar.appendChild(document.createElement('div'));
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const dayDiv = document.createElement('div');
            dayDiv.classList.add('calendar-day');
            dayDiv.textContent = day;

            const currentDayDate = new Date(year, month, day);
            currentDayDate.setHours(0,0,0,0); 
            const todayReset = new Date();
            todayReset.setHours(0,0,0,0);

            const isToday = (day === today.getDate() && month === today.getMonth() && year === today.getFullYear());
            if (isToday) {
                dayDiv.classList.add('today');
            }

            if (currentDayDate < todayReset) {
                 dayDiv.classList.add('past-day');
            }

            dayDiv.addEventListener('click', () => {
                if (dayDiv.classList.contains('past-day')) return;

                document.querySelectorAll('.calendar-day').forEach(d => d.classList.remove('selected-day'));
                dayDiv.classList.add('selected-day');
                
                selectedStartTime = null;
                hiddenStartTime.value = '';
                hiddenEndTime.value = '';
                updateSubmitButton();

                selectedDate = new Date(year, month, day);
                timeSlotsDiv.style.display = 'block';
                
                const yearStr = selectedDate.getFullYear();
                const monthStr = String(selectedDate.getMonth() + 1).padStart(2, '0');
                const dayStr = String(selectedDate.getDate()).padStart(2, '0');
                const selectedDateStr = `${yearStr}-${monthStr}-${dayStr}`;

                fetchBookedSlots(selectedDateStr);
            });

            calendar.appendChild(dayDiv);
        }

        monthYear.textContent = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(new Date(year, month));
    }
    
    // Function to fetch booked slots via AJAX/Fetch
    async function fetchBookedSlots(date) {
        timeSlotButtonsDiv.innerHTML = '<p class="text-info" id="timeSlotMessage">Checking availability...</p>';
        try {
            const response = await fetch(`book_fetch.php?get_slots=true&date=${date}&facility_id=${hiddenFacilityID.value}`);
            const data = await response.json();
            
            if (data.success) {
                bookedSlots = data.booked_slots;
                renderTimeSlots(date);
            } else {
                timeSlotButtonsDiv.innerHTML = `<p class="text-danger">Error fetching slots: ${data.message}</p>`;
            }

        } catch (error) {
            console.error('Fetch error:', error);
            timeSlotButtonsDiv.innerHTML = '<p class="text-danger">Failed to connect to server to check slots.</p>';
        }
    }

    // Function to render time slots
    function renderTimeSlots(dateStr) {
        timeSlotButtonsDiv.innerHTML = '';
        let availableCount = 0;

        ALL_TIME_SLOTS.forEach(slot => {
            const slotStartDateTime = `${dateStr} ${slot.start}`;
            
            // Check if this slot is in the bookedSlots array
            const isBooked = bookedSlots.includes(slotStartDateTime);
            
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn m-1 time-slot-btn';
            button.textContent = slot.label;
            button.dataset.start = slot.start;
            button.dataset.end = slot.end;

            if (isBooked) {
                button.classList.add('btn-danger');
                button.disabled = true;
                button.textContent += ' (Booked)';
            } else {
                button.classList.add('btn-outline-primary');
                availableCount++;
                button.addEventListener('click', handleTimeSlotClick);
            }
            timeSlotButtonsDiv.appendChild(button);
        });

        if (availableCount === 0) {
            timeSlotButtonsDiv.innerHTML = '<p class="text-warning">No time slots available for this day.</p>';
        }
    }
    
    function handleTimeSlotClick(e) {
        const btn = e.target.closest('.time-slot-btn');
        if (!btn || !selectedDate) return;

        document.querySelectorAll('.time-slot-btn').forEach(b => b.classList.remove('selected-day'));
        btn.classList.add('selected-day');

        selectedStartTime = btn.dataset.start;
        const selectedEndTime = btn.dataset.end;
        
        const year = selectedDate.getFullYear();
        const month = String(selectedDate.getMonth() + 1).padStart(2, '0');
        const day = String(selectedDate.getDate()).padStart(2, '0');
        const datePart = `${year}-${month}-${day}`;

        hiddenStartTime.value = `${datePart} ${selectedStartTime}`;
        hiddenEndTime.value = `${datePart} ${selectedEndTime}`;
        
        updateSubmitButton();
    }

    // Event Listeners for Month Navigation
    prevMonthBtn.addEventListener('click', () => {
        currentMonth--;
        if (currentMonth < 0) { currentMonth = 11; currentYear--; }
        renderCalendar(currentMonth, currentYear);
    });

    nextMonthBtn.addEventListener('click', () => {
        currentMonth++;
        if (currentMonth > 11) { currentMonth = 0; currentYear++; }
        renderCalendar(currentMonth, currentYear);
    });

    renderCalendar(currentMonth, currentYear);
});
</script>
</body>
</html>