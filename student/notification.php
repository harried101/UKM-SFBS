<?php
session_start();
// Basic Auth Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'Student') {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - UKM Sports</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
    </style>
</head>
<body class="flex flex-col min-h-screen">

    <!-- Simple Navbar -->
    <nav class="bg-white border-b border-slate-200 px-6 py-4 flex justify-between items-center sticky top-0 z-50">
        <div class="flex items-center gap-3">
            <a href="dashboard.php" class="text-slate-400 hover:text-[#8a0d19] transition">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <h1 class="text-xl font-bold text-slate-800">Notifications</h1>
        </div>
        <button class="text-xs font-bold text-[#8a0d19] hover:underline" onclick="markAllRead()">Mark all as read</button>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-6 py-8 max-w-2xl">
        
        <!-- Today Header -->
        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 ml-1">Recent Updates</h4>

        <!-- Notification List -->
        <div class="space-y-4" id="notificationList">
            
            <!-- Item 1: Booking Approved (Mock) -->
            <div class="bg-white p-5 rounded-2xl border border-green-100 shadow-sm flex gap-4 relative overflow-hidden group hover:shadow-md transition">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-green-500"></div>
                <div class="w-10 h-10 rounded-full bg-green-50 flex-shrink-0 flex items-center justify-center text-green-600">
                    <i class="fa-solid fa-check"></i>
                </div>
                <div class="flex-grow">
                    <div class="flex justify-between items-start">
                        <h3 class="font-bold text-slate-800 text-sm">Booking Approved</h3>
                        <span class="text-[10px] text-slate-400">2 mins ago</span>
                    </div>
                    <p class="text-sm text-slate-500 mt-1">Your booking for <span class="font-semibold text-slate-700">Badminton Court A</span> on 12 Dec has been approved.</p>
                </div>
            </div>

            <!-- Item 2: Schedule Change (Mock) -->
            <div class="bg-white p-5 rounded-2xl border border-red-100 shadow-sm flex gap-4 relative overflow-hidden group hover:shadow-md transition">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-[#8a0d19]"></div>
                <div class="w-10 h-10 rounded-full bg-red-50 flex-shrink-0 flex items-center justify-center text-[#8a0d19]">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div class="flex-grow">
                    <div class="flex justify-between items-start">
                        <h3 class="font-bold text-slate-800 text-sm">Booking Cancelled</h3>
                        <span class="text-[10px] text-slate-400">1 hour ago</span>
                    </div>
                    <p class="text-sm text-slate-500 mt-1">Admin has cancelled your booking for <span class="font-semibold text-slate-700">Tennis Court</span> due to maintenance.</p>
                </div>
            </div>

            <!-- Item 3: General Info (Mock) -->
            <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex gap-4 relative overflow-hidden group hover:shadow-md transition opacity-60">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-slate-300"></div>
                <div class="w-10 h-10 rounded-full bg-slate-50 flex-shrink-0 flex items-center justify-center text-slate-500">
                    <i class="fa-solid fa-info"></i>
                </div>
                <div class="flex-grow">
                    <div class="flex justify-between items-start">
                        <h3 class="font-bold text-slate-800 text-sm">System Update</h3>
                        <span class="text-[10px] text-slate-400">Yesterday</span>
                    </div>
                    <p class="text-sm text-slate-500 mt-1">The sports center will be closed on public holidays.</p>
                </div>
            </div>

        </div>

        <!-- Empty State (Hidden by default, toggle via JS if list empty) -->
        <div id="emptyState" class="hidden flex-col items-center justify-center py-20 text-center">
            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4 text-slate-300">
                <i class="fa-regular fa-bell-slash text-2xl"></i>
            </div>
            <p class="text-slate-500 font-medium">No new notifications</p>
        </div>

    </main>

    <script>
        // Mock Function - In real integration, this calls backend
        function markAllRead() {
            // Visual feedback
            const items = document.querySelectorAll('#notificationList > div');
            items.forEach(item => {
                item.classList.add('opacity-60');
                // Remove colored accents to show 'read' state
                const accent = item.querySelector('.absolute');
                if(accent) accent.style.backgroundColor = '#cbd5e1'; // slate-300
            });
            alert("All notifications marked as read.");
        }
        
        // This script section will eventually be replaced by the FS developer (Mia) 
        // to fetch real data using the fetch_notifications.php script
        console.log("Notification UI Loaded.");
    </script>
</body>
</html>