<?php
// Active State Logic
// Expected variable: $nav_active (values: 'home', 'facilities', 'bookings')
$nav_active = $nav_active ?? '';
?>
<nav class="bg-white/95 backdrop-blur-sm border-b border-slate-200 sticky top-0 z-50 shadow-sm">
    <div class="container mx-auto px-6 py-3 flex justify-between items-center">
        <!-- Logo Section -->
        <div class="flex items-center gap-4">
            <img src="../assets/img/ukm.png" alt="UKM Logo" class="h-10 md:h-12 w-auto">
            <div class="h-8 w-px bg-slate-300 hidden sm:block"></div>
            <img src="../assets/img/pusatsukanlogo.png" alt="Pusat Sukan Logo" class="h-10 md:h-12 w-auto hidden sm:block">
            <span class="md:hidden font-serif font-bold text-[#0b4d9d]">Admin</span>
        </div>

        <!-- Navigation Links -->
        <div class="flex items-center gap-8">
            <!-- Home -->
            <a href="dashboard.php" class="<?php echo ($nav_active === 'home') ? 'text-[#0b4d9d] font-bold flex items-center gap-2 relative' : 'text-slate-500 hover:text-[#0b4d9d] font-semibold transition'; ?>">
                Home
                <?php if($nav_active === 'home'): ?>
                <span class="absolute -bottom-1 left-0 w-full h-0.5 bg-[#0b4d9d] rounded-full"></span>
                <?php endif; ?>
            </a>

            <!-- Facilities -->
            <a href="addfacilities.php" class="<?php echo ($nav_active === 'facilities') ? 'text-[#0b4d9d] font-bold flex items-center gap-2 relative' : 'text-slate-500 hover:text-[#0b4d9d] font-semibold transition'; ?>">
                Facilities
                <?php if($nav_active === 'facilities'): ?>
                <span class="absolute -bottom-1 left-0 w-full h-0.5 bg-[#0b4d9d] rounded-full"></span>
                <?php endif; ?>
            </a>

            <!-- Bookings -->
            <a href="bookinglist.php" class="<?php echo ($nav_active === 'bookings') ? 'text-[#0b4d9d] font-bold flex items-center gap-2 relative' : 'text-slate-500 hover:text-[#0b4d9d] font-semibold transition'; ?>">
                Bookings
                <?php if($nav_active === 'bookings'): ?>
                <span class="absolute -bottom-1 left-0 w-full h-0.5 bg-[#0b4d9d] rounded-full"></span>
                <?php endif; ?>
            </a>
             <!-- Feedback -->
            <a href="view_feedback.php" class="<?php echo ($nav_active === 'feedback') ? 'text-[#0b4d9d] font-bold flex items-center gap-2 relative' : 'text-slate-500 hover:text-[#0b4d9d] font-semibold transition'; ?>">
                Feedback
                <?php if($nav_active === 'feedback'): ?>
                <span class="absolute -bottom-1 left-0 w-full h-0.5 bg-[#0b4d9d] rounded-full"></span>
                <?php endif; ?>
            </a>

            <!-- Notifications -->
            <div class="relative cursor-pointer">
                <a href="notification.php" class="flex items-center text-slate-500 hover:text-[#0b4d9d] transition relative">
                    <i class="fa-solid fa-bell text-xl"></i>
                    <span id="notif-count"
                          class="hidden absolute -top-1 -right-2 bg-red-600 text-white text-xs font-bold 
                                 rounded-full w-5 h-5 flex items-center justify-center">
                    </span>
                </a>
            </div>

            <!-- User Profile -->
            <div class="flex items-center gap-4 pl-6 border-l border-slate-200">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($adminName ?? 'Admin'); ?></p>
                    <p class="text-xs text-slate-500 font-bold uppercase tracking-wider"><?php echo htmlspecialchars($adminID ?? 'ADMIN'); ?></p>
                </div>
                <div class="relative group">
                    <img src="../assets/img/user.png" class="w-10 h-10 rounded-full border-2 border-white shadow-sm object-cover cursor-pointer transition hover:scale-105">
                    <div class="absolute right-0 top-full pt-2 w-48 hidden group-hover:block z-50">
                        <div class="bg-white rounded-xl shadow-xl border border-slate-100 overflow-hidden">
                            <a href="../logout.php" onclick="return confirm('Logout?')" class="block px-4 py-3 text-sm text-red-600 hover:bg-slate-50 transition font-medium">
                                <i class="fa-solid fa-right-from-bracket mr-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>
