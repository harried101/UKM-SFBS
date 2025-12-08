<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sports Facility Booking</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    /* Calendar styling */
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
    }

    .calendar-day:hover { background-color: #cce5ff; }
    .selected-day { background-color: #007bff; color: white; }
    .today { border: 2px solid #28a745; font-weight: bold; }
</style>
</head>
<body class="p-4">
<div class="container">
    <h1 class="mb-4 text-center">Sports Facility Booking System</h1>

    <!-- Check Availability Button -->
    <div class="text-center mb-3">
        <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#bookingModal">
            Check Availability
        </button>
    </div>

    <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Book a Court</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <!-- Month Navigation -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <button id="prevMonth" class="btn btn-sm btn-outline-secondary">&lt; Prev</button>
                        <h5 id="monthYear" class="mb-0"></h5>
                        <button id="nextMonth" class="btn btn-sm btn-outline-secondary">Next &gt;</button>
                    </div>

                    <!-- Calendar Grid -->
                    <div id="calendar"></div>

                    <!-- Time slots -->
                    <div id="timeSlots" style="margin-top:20px; display:none;">
                        <h6>Available Time Slots:</h6>
                        <div class="d-flex flex-wrap">
                            <button class="btn btn-outline-primary m-1">9:00 - 10:00</button>
                            <button class="btn btn-outline-primary m-1">10:00 - 11:00</button>
                            <button class="btn btn-outline-primary m-1">11:00 - 12:00</button>
                            <button class="btn btn-outline-primary m-1">12:00 - 13:00</button>
                            <button class="btn btn-outline-primary m-1">13:00 - 14:00</button>
                            <button class="btn btn-outline-primary m-1">14:00 - 15:00</button>
                            <button class="btn btn-outline-primary m-1">16:00 - 17:00</button>
                            <button class="btn btn-outline-primary m-1">18:00 - 19:00</button>
                        </div>
                    </div>

                    <!-- Court selection -->
                    <div id="courtSelect" style="margin-top:20px; display:none;">
                        <h6>Select Court:</h6>
                        <select class="form-select">
                            <option value="court1">Court 1</option>
                            <option value="court2">Court 2</option>
                            <option value="court3">Court 3</option>
                        </select>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success">Confirm Booking</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const calendar = document.getElementById('calendar');
    const monthYear = document.getElementById('monthYear');
    const timeSlots = document.getElementById('timeSlots');
    const courtSelect = document.getElementById('courtSelect');
    const prevMonthBtn = document.getElementById('prevMonth');
    const nextMonthBtn = document.getElementById('nextMonth');

    let today = new Date();
    let currentMonth = today.getMonth();
    let currentYear = today.getFullYear();

    function renderCalendar(month, year) {
        calendar.innerHTML = '';

        // Weekday headers
        const weekdays = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        weekdays.forEach(day => {
            const dayHeader = document.createElement('div');
            dayHeader.classList.add('calendar-weekday');
            dayHeader.textContent = day;
            calendar.appendChild(dayHeader);
        });

        // First day of month (0=Sun,1=Mon...)
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        // Empty cells for first week
        for (let i = 0; i < firstDay; i++) {
            calendar.appendChild(document.createElement('div'));
        }

        // Calendar days
        for (let day = 1; day <= daysInMonth; day++) {
            const dayDiv = document.createElement('div');
            dayDiv.classList.add('calendar-day');
            dayDiv.textContent = day;

            // Highlight today
            if (day === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
                dayDiv.classList.add('today');
            }

            // Click event
            dayDiv.addEventListener('click', () => {
                document.querySelectorAll('.calendar-day').forEach(d => d.classList.remove('selected-day'));
                dayDiv.classList.add('selected-day');
                timeSlots.style.display = 'block';
                courtSelect.style.display = 'block';
            });

            calendar.appendChild(dayDiv);
        }

        // Display current month/year
        monthYear.textContent = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(new Date(year, month));
    }

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
