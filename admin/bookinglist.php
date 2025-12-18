<?php
require_once 'includes/admin_auth.php'; // Standardized Auth & User Fetch

// Fetch Facilities for Dropdown
$facilitiesResult = $conn->query("SELECT FacilityID, Name FROM facilities WHERE Status IN ('Active', 'Maintenance')");
$facilitiesList = [];
while($f = $facilitiesResult->fetch_assoc()) {
    $facilitiesList[] = $f;
}

// Initialize filter variables
$statusFilter = $_GET['status'] ?? 'all';
$dateFilter = $_GET['date'] ?? '';
$searchQuery = $_GET['search'] ?? '';

// Base SQL
$sql = "SELECT b.*, f.Name as FacilityName, u.FirstName, u.LastName, u.UserIdentifier 
        FROM bookings b 
        LEFT JOIN facilities f ON b.FacilityID = f.FacilityID 
        LEFT JOIN users u ON b.UserID = u.UserID 
        WHERE 1=1";

$params = [];
$types = "";

if ($statusFilter !== 'all') {
    $sql .= " AND b.Status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

if (!empty($dateFilter)) {
    $sql .= " AND DATE(b.StartTime) = ?";
    $params[] = $dateFilter;
    $types .= "s";
}

if (!empty($searchQuery)) {
    $searchTerm = "%" . $searchQuery . "%";
    $sql .= " AND (b.BookingID LIKE ? OR u.UserIdentifier LIKE ? OR u.FirstName LIKE ? OR u.LastName LIKE ? OR f.Name LIKE ?)";
    $params[] = $searchTerm; $params[] = $searchTerm; $params[] = $searchTerm; $params[] = $searchTerm; $params[] = $searchTerm;
    $types .= "sssss";
}

$sql .= " ORDER BY b.BookedAt DESC";

// Execute Query
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

$bookings = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Bookings</title>
    
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
        
        /* Modal Transitions */
        .modal { transition: opacity 0.3s ease; opacity: 0; pointer-events: none; }
        .modal.open { opacity: 1; pointer-events: auto; }
        .modal-content { transform: scale(0.95); transition: transform 0.3s ease; }
        .modal.open .modal-content { transform: scale(1); }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col font-body text-slate-800">

<!-- NAVBAR -->
<?php
$nav_active = 'bookings'; 
include 'includes/navbar.php'; 
?>

<!-- MAIN CONTENT -->
<main class="flex-grow container mx-auto px-4 md:px-6 py-8 max-w-7xl fade-in">

    <!-- PAGE HEADER -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8 pb-6 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">
                <span class="bg-slate-100 px-2 py-1 rounded">Admin</span>
                <i class="fa-solid fa-chevron-right text-[10px]"></i>
                <span class="text-ukm-blue">Bookings</span>
            </div>
           <h1 class="text-3xl md:text-4xl font-extrabold text-ukm-blue tracking-tight" style="font-family: 'Playfair Display', serif;">
    Booking Management
</h1>
<p class="text-slate-500 mt-2 text-lg" style="font-family: 'Playfair Display', serif;">
    Monitor, approve, and manage all facility reservations.
</p>

        </div>
        <button onclick="openNewBookingModal()" class="bg-ukm-blue text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-blue-500/20 hover:bg-ukm-dark transition transform active:scale-95 flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> New Booking
        </button>
    </div>

    <!-- ALERTS -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="mb-6 rounded-xl p-4 flex items-center justify-between shadow-sm border bg-emerald-50 border-emerald-200 text-emerald-800">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-circle-check text-xl"></i>
                <span class="font-bold"><?= htmlspecialchars($_GET['msg']); ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-sm opacity-50 hover:opacity-100 font-bold">DISMISS</button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['err'])): ?>
        <div class="mb-6 rounded-xl p-4 flex items-center justify-between shadow-sm border bg-red-50 border-red-200 text-red-800">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-circle-exclamation text-xl"></i>
                <span class="font-bold"><?= htmlspecialchars($_GET['err']); ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-sm opacity-50 hover:opacity-100 font-bold">DISMISS</button>
        </div>
    <?php endif; ?>

    <!-- FILTERS -->
    <div class="glass-panel rounded-2xl p-4 mb-8">
        <form method="GET" class="flex flex-col md:flex-row gap-4 items-center justify-between">
            <div class="flex items-center gap-2 w-full md:w-auto overflow-x-auto pb-2 md:pb-0">
                <input type="hidden" name="date" value="<?= htmlspecialchars($dateFilter); ?>">
                <input type="hidden" name="search" value="<?= htmlspecialchars($searchQuery); ?>">
                
                <a href="?status=all&date=<?= $dateFilter ?>&search=<?= $searchQuery ?>" 
                   class="px-4 py-2 rounded-lg text-sm font-bold border transition whitespace-nowrap
                   <?= $statusFilter == 'all' ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' ?>">
                   All
                </a>
                <a href="?status=Pending&date=<?= $dateFilter ?>&search=<?= $searchQuery ?>" 
                   class="px-4 py-2 rounded-lg text-sm font-bold border transition whitespace-nowrap
                   <?= $statusFilter == 'Pending' ? 'bg-amber-500 text-white border-amber-500' : 'bg-white text-slate-600 border-slate-200 hover:bg-amber-50' ?>">
                   Pending
                </a>
                <a href="?status=Confirmed&date=<?= $dateFilter ?>&search=<?= $searchQuery ?>" 
                   class="px-4 py-2 rounded-lg text-sm font-bold border transition whitespace-nowrap
                   <?= $statusFilter == 'Confirmed' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-slate-600 border-slate-200 hover:bg-emerald-50' ?>">
                   Confirmed
                </a>
                <a href="?status=Canceled&date=<?= $dateFilter ?>&search=<?= $searchQuery ?>" 
                   class="px-4 py-2 rounded-lg text-sm font-bold border transition whitespace-nowrap
                   <?= $statusFilter == 'Canceled' ? 'bg-red-600 text-white border-red-600' : 'bg-white text-slate-600 border-slate-200 hover:bg-red-50' ?>">
                   Canceled
                </a>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                <div class="relative">
                    <i class="fa-solid fa-calendar absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="date" name="date" value="<?= htmlspecialchars($dateFilter); ?>" onchange="this.form.submit()"
                           class="pl-10 pr-4 py-2 rounded-xl border border-slate-200 text-sm font-semibold focus:ring-2 focus:ring-ukm-blue outline-none w-full sm:w-auto">
                </div>
                <div class="relative">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($searchQuery); ?>" placeholder="Search ID, User..." 
                           class="pl-10 pr-4 py-2 rounded-xl border border-slate-200 text-sm font-semibold focus:ring-2 focus:ring-ukm-blue outline-none w-full sm:w-64">
                </div>
                <button type="submit" class="hidden sm:block px-4 py-2 bg-slate-800 text-white rounded-xl font-bold text-sm hover:bg-slate-700 transition">
                    Search
                </button>
            </div>
        </form>
    </div>

    <!-- BOOKINGS TABLE -->
    <div class="glass-panel rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 font-bold">ID</th>
                        <th class="px-6 py-4 font-bold">Facility</th>
                        <th class="px-6 py-4 font-bold">Booked By</th>
                        <th class="px-6 py-4 font-bold">Timeframe</th>
                        <th class="px-6 py-4 font-bold">Status</th>
                        <th class="px-6 py-4 font-bold">Booked At</th>
                        <th class="px-6 py-4 font-bold text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($bookings)): ?>
                        <tr><td colspan="7" class="px-6 py-8 text-center text-slate-400 italic">No bookings found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $booking): 
                            // Determine "Created By"
                            $createdBy = !empty($booking['CreatedByAdminID']) ? "Staff" : "Student";
                            $userDisplay = htmlspecialchars($booking['FirstName'] . ' ' . $booking['LastName']);
                            if (empty(trim($userDisplay))) $userDisplay = htmlspecialchars($booking['UserIdentifier']);
                            
                            // Status Badge
                            $statusClass = match($booking['Status']) {
                                'Pending' => 'bg-amber-100 text-amber-700 border-amber-200',
                                'Confirmed' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                'Canceled' => 'bg-red-50 text-red-600 border-red-100',
                                'Complete' => 'bg-slate-100 text-slate-600 border-slate-200',
                                default => 'bg-slate-100 text-slate-600'
                            };
                        ?>
                        <tr class="hover:bg-slate-50 transition group">
                            <td class="px-6 py-4 font-mono text-slate-500 font-bold">#<?= $booking['BookingID']; ?></td>
                            <td class="px-6 py-4 font-bold text-ukm-blue"><?= htmlspecialchars($booking['FacilityName']); ?></td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-700"><?= $userDisplay; ?></div>
                                <div class="text-[10px] uppercase font-bold text-slate-400"><?= $createdBy; ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-700"><?= date('d M Y', strtotime($booking['StartTime'])); ?></div>
                                <div class="text-xs text-slate-500"><?= date('H:i', strtotime($booking['StartTime'])) . ' - ' . date('H:i', strtotime($booking['EndTime'])); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border uppercase tracking-wider <?= $statusClass; ?>">
                                    <?= $booking['Status']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500"><?= date('d M Y H:i', strtotime($booking['BookedAt'])); ?></td>
                            <td class="px-6 py-4 text-right">
                                <button onclick='openViewModal(<?= json_encode($booking); ?>, "<?= $userDisplay ?>", "<?= $createdBy ?>")' 
                                        class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 text-xs font-bold hover:bg-ukm-blue hover:text-white hover:border-ukm-blue transition">
                                    Manage
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<!-- VIEW/EDIT MODAL -->
<div id="viewModal" class="modal fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
    <div class="modal-content bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
            <h3 class="font-bold text-lg text-slate-800">Booking Details <span id="modalId" class="text-ukm-blue font-mono ml-2"></span></h3>
            <button onclick="closeViewModal()" class="w-8 h-8 rounded-full bg-slate-200 text-slate-500 hover:bg-red-100 hover:text-red-500 flex items-center justify-center transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        
        <form id="processBookingForm" action="process.php" method="POST" class="flex-grow overflow-y-auto p-6 space-y-6">
            <input type="hidden" name="booking_id" id="modalBookingId">
            <input type="hidden" name="action" id="modalAction">
            
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Facility</label>
                    <input type="text" id="modalFacility" readonly class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-bold text-slate-700">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Booked By</label>
                    <input type="text" id="modalUser" readonly class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-bold text-slate-700">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Start Time</label>
                    <input type="text" id="modalStart" readonly class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-700">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">End Time</label>
                    <input type="text" id="modalEnd" readonly class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-700">
                </div>
            </div>

            <div class="bg-blue-50/50 rounded-xl p-5 border border-blue-100">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-sm font-bold text-slate-500 uppercase">Management</span>
                    <span id="modalStatus" class="px-3 py-1 rounded-full text-xs font-bold border uppercase"></span>
                </div>
                
                <div id="actionButtons" class="flex gap-3 mb-4">
                    <button type="button" onclick="submitAction('approve')" class="flex-1 py-3 rounded-xl bg-emerald-600 text-white font-bold hover:bg-emerald-700 transition">
                        <i class="fa-solid fa-check mr-2"></i> Approve
                    </button>
                    <button type="button" onclick="submitAction('reject')" class="flex-1 py-3 rounded-xl bg-red-600 text-white font-bold hover:bg-red-700 transition">
                        <i class="fa-solid fa-xmark mr-2"></i> Reject
                    </button>
                </div>

                <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Admin Notes</label>
                <textarea name="admin_notes" id="adminNotes" rows="3" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-ukm-blue outline-none" placeholder="Reason for rejection or optional note..."></textarea>
            </div>
            
            <div class="flex justify-between text-xs text-slate-400 font-medium pt-2">
                <span id="modalBookedAt"></span>
                <span id="modalCreatedBy"></span>
            </div>
        </form>
    </div>
</div>

<!-- NEW BOOKING MODAL (IFRAME) -->
<div id="newBookingModal" class="modal fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm">
    <div class="modal-content bg-white w-full max-w-5xl h-[85vh] rounded-2xl shadow-2xl overflow-hidden flex flex-col">
        <div class="px-6 py-4 bg-slate-900 text-white flex justify-between items-center">
            <h3 class="font-bold text-lg">New Walk-in Booking</h3>
            <button onclick="closeNewBookingModal()" class="w-8 h-8 rounded-full bg-slate-700 hover:bg-slate-600 flex items-center justify-center transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        
        <div class="flex-grow flex flex-col">
            <!-- Selector -->
            <div id="facilitySelector" class="p-6 bg-slate-50 border-b border-slate-200 text-center">
                <label class="block font-bold text-slate-700 mb-2">Select Facility</label>
                <div class="flex justify-center gap-3">
                    <select id="newBookingFacility" class="px-4 py-3 rounded-xl border border-slate-200 text-sm font-bold text-slate-700 outline-none focus:ring-2 focus:ring-ukm-blue min-w-[300px]">
                        <option value="">-- Choose Facility --</option>
                        <?php foreach($facilitiesList as $fac): ?>
                            <option value="<?= $fac['FacilityID'] ?>"><?= htmlspecialchars($fac['Name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button onclick="loadBookingFrame()" class="px-6 py-3 bg-ukm-blue text-white rounded-xl font-bold hover:bg-ukm-dark transition">Next</button>
                </div>
            </div>

            <!-- Frame -->
            <div class="flex-grow relative bg-white">
                <div id="framePlaceholder" class="absolute inset-0 flex flex-col items-center justify-center text-slate-300">
                    <i class="fa-solid fa-arrow-up text-4xl mb-4 animate-bounce"></i>
                    <p class="font-bold">Select a facility to start</p>
                </div>
                <div id="frameLoader" class="absolute inset-0 flex flex-col items-center justify-center text-ukm-blue hidden">
                    <i class="fa-solid fa-spinner fa-spin text-4xl mb-4"></i>
                    <p class="font-bold">Loading schedule...</p>
                </div>
                <iframe id="bookingFrame" class="w-full h-full border-0 hidden"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
    // VIEW MODAL LOGIC
    const viewModal = document.getElementById('viewModal');
    
    function openViewModal(booking, user, createdBy) {
        document.getElementById('modalId').innerText = '#' + booking.BookingID;
        document.getElementById('modalBookingId').value = booking.BookingID;
        document.getElementById('modalFacility').value = booking.FacilityName;
        document.getElementById('modalUser').value = user;
        document.getElementById('modalStart').value = booking.StartTime;
        document.getElementById('modalEnd').value = booking.EndTime;
        
        // Status Styling
        const statusEl = document.getElementById('modalStatus');
        statusEl.innerText = booking.Status;
        statusEl.className = 'px-3 py-1 rounded-full text-xs font-bold border uppercase tracking-wider ';
        if(booking.Status === 'Pending') statusEl.classList.add('bg-amber-100', 'text-amber-700', 'border-amber-200');
        else if(booking.Status === 'Confirmed') statusEl.classList.add('bg-emerald-100', 'text-emerald-700', 'border-emerald-200');
        else if(booking.Status === 'Canceled') statusEl.classList.add('bg-red-50', 'text-red-600', 'border-red-100');
        else statusEl.classList.add('bg-slate-100', 'text-slate-600', 'border-slate-200');
        
        document.getElementById('modalBookedAt').innerText = 'Booked: ' + booking.BookedAt;
        document.getElementById('modalCreatedBy').innerText = 'Source: ' + createdBy;
        
        // Buttons Logic
        const btns = document.getElementById('actionButtons');
        const notes = document.getElementById('adminNotes');
        if(booking.Status === 'Pending') {
            btns.style.display = 'flex';
            notes.readOnly = false;
        } else {
            btns.style.display = 'none';
            notes.readOnly = true;
        }
        
        viewModal.classList.add('open');
    }

    function closeViewModal() {
        viewModal.classList.remove('open');
    }

    function submitAction(action) {
        const form = document.getElementById('processBookingForm');
        document.getElementById('modalAction').value = action;
        const notes = document.getElementById('adminNotes').value.trim();
        
        if (action === 'reject' && notes === '') {
            alert('Please provide a reason for rejection in Admin Notes.');
            return;
        }
        if (confirm(`Are you sure you want to ${action} this booking?`)) {
            form.submit();
        }
    }

    // NEW BOOKING MODAL LOGIC
    const newModal = document.getElementById('newBookingModal');
    const iframe = document.getElementById('bookingFrame');
    const loader = document.getElementById('frameLoader');
    const placeholder = document.getElementById('framePlaceholder');
    
    function openNewBookingModal() { newModal.classList.add('open'); }
    function closeNewBookingModal() { 
        newModal.classList.remove('open'); 
        iframe.src = "";
        iframe.classList.add('hidden');
        placeholder.classList.remove('hidden');
        document.getElementById('newBookingFacility').value = "";
    }
    
    function loadBookingFrame() {
        const facilityID = document.getElementById('newBookingFacility').value;
        if (!facilityID) return alert("Select a facility first.");
        
        placeholder.classList.add('hidden');
        loader.classList.remove('hidden');
        iframe.classList.add('hidden');
        
        iframe.src = "book_walkin.php?facility_id=" + facilityID;
        iframe.onload = function() {
            loader.classList.add('hidden');
            iframe.classList.remove('hidden');
        };
    }

    // Listen for success message from iframe
    window.addEventListener('message', function(event) {
        if (event.data.type === 'booking_success') {
            closeNewBookingModal();
            window.location.href = "bookinglist.php?msg=" + encodeURIComponent(event.data.message);
        }
    });
</script>


<!-- FOOTER -->
<?php include 'includes/footer.php'; ?>

</body>
</html>