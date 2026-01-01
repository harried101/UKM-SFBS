<?php
// Expected variable: $nav_active (values: 'home', 'facilities', 'history', 'notifications')
$nav_active = $nav_active ?? '';
?>
<nav class="bg-white/95 backdrop-blur-sm border-b border-slate-200 sticky top-0 z-40 transition-all duration-300 shadow-sm">
    <div class="container mx-auto px-6 py-3 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <img src="../assets/img/ukm.png" alt="UKM Logo" class="h-10 md:h-12 w-auto transition-transform hover:scale-105">
            <div class="h-8 w-px bg-slate-200 hidden sm:block"></div>
            <img src="../assets/img/pusatsukanlogo.png" alt="Pusat Sukan Logo" class="h-10 md:h-12 w-auto hidden sm:block transition-transform hover:scale-105">
        </div>
        
        <div class="flex items-center gap-8">
            <div class="hidden md:flex items-center gap-6">
                <!-- Home -->
                <a href="dashboard.php" class="<?php echo ($nav_active === 'home') ? 'text-[#8a0d19] font-bold flex items-center gap-2 relative' : 'text-slate-600 hover:text-[#8a0d19] font-medium transition hover:-translate-y-0.5'; ?> text-decoration-none">
                    <span>Home</span>
                    <?php if($nav_active === 'home'): ?>
                    <span class="absolute -bottom-1.5 left-0 w-full h-0.5 bg-[#8a0d19] rounded-full"></span>
                    <?php endif; ?>
                </a>

                <!-- Facilities -->
                <a href="student_facilities.php" class="<?php echo ($nav_active === 'facilities') ? 'text-[#8a0d19] font-bold flex items-center gap-2 relative' : 'text-slate-600 hover:text-[#8a0d19] font-medium transition hover:-translate-y-0.5'; ?> text-decoration-none">
                    <span>Facilities</span>
                    <?php if($nav_active === 'facilities'): ?>
                    <span class="absolute -bottom-1.5 left-0 w-full h-0.5 bg-[#8a0d19] rounded-full"></span>
                    <?php endif; ?>
                </a>

                <!-- History -->
                <a href="booking_history.php" class="<?php echo ($nav_active === 'history') ? 'text-[#8a0d19] font-bold flex items-center gap-2 relative' : 'text-slate-600 hover:text-[#8a0d19] font-medium transition hover:-translate-y-0.5'; ?> text-decoration-none">
                    <span>History</span>
                    <?php if($nav_active === 'history'): ?>
                    <span class="absolute -bottom-1.5 left-0 w-full h-0.5 bg-[#8a0d19] rounded-full"></span>
                    <?php endif; ?>
                </a>
            </div>

            <div class="flex items-center gap-4 pl-4 md:pl-6 md:border-l border-slate-200 relative">
                <!-- Notifications -->
                <a href="notification.php" class="<?php echo ($nav_active === 'notifications') ? 'text-[#8a0d19]' : 'text-slate-400 hover:text-[#8a0d19]'; ?> transition-colors relative p-1 text-decoration-none">
                    <i class="<?php echo ($nav_active === 'notifications') ? 'fa-solid' : 'fa-regular'; ?> fa-bell text-xl"></i>
                </a>

                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-slate-800 leading-tight"><?php echo htmlspecialchars($studentName ?? 'Student'); ?></p>
                    <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold"><?php echo htmlspecialchars($studentID ?? ''); ?></p>
                </div>
                <div class="relative" id="profileDropdownContainer">
                    <button id="profileBtn" class="focus:outline-none focus:ring-2 focus:ring-[#8a0d19] rounded-full transition-transform active:scale-95">
                        <img src="../assets/img/user.png" alt="Profile" class="w-10 h-10 rounded-full border-2 border-white ring-2 ring-slate-100 object-cover shadow-sm hover:ring-[#8a0d19]/20">
                    </button>
                    <div id="dropdownMenu" class="absolute right-0 mt-3 w-56 bg-white rounded-xl shadow-xl border border-slate-100 hidden z-50 overflow-hidden transform origin-top-right transition-all duration-200">
                        <div class="px-4 py-3 bg-slate-50 border-b border-slate-100 md:hidden">
                             <p class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($studentName ?? 'Student'); ?></p>
                             <p class="text-xs text-slate-500"><?php echo htmlspecialchars($studentID ?? ''); ?></p>
                        </div>
                        <a href="student_facilities.php" class="block md:hidden px-4 py-3 text-sm text-slate-600 hover:bg-slate-50 hover:text-[#8a0d19] text-decoration-none">New Booking</a>
                        <a href="booking_history.php" class="block md:hidden px-4 py-3 text-sm text-slate-600 hover:bg-slate-50 hover:text-[#8a0d19] text-decoration-none">History</a>
                        <a href="notification.php" class="block md:hidden px-4 py-3 text-sm text-slate-600 hover:bg-slate-50 hover:text-[#8a0d19] text-decoration-none">Notifications</a>
                        <div class="h-px bg-slate-100 md:hidden"></div>
                        <a href="../logout.php" id="logoutLink" class="block px-4 py-3 text-sm text-red-600 hover:bg-red-50 hover:pl-6 transition-all font-medium flex items-center gap-2 text-decoration-none">
                            <i class="fa-solid fa-right-from-bracket"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>
