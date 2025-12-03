<?php
session_start();

// SECURITY CHECK: Redirect if not logged in or role is not Student
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Student') {
    header("Location: ../index.php");
    exit();
}

// Database Connection
require_once '../includes/db_connect.php';

if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

// --- CONFIGURATION ---
$limit = 6; // Cards per page
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// --- INPUTS ---
$search = $_GET['search'] ?? "";
$typeFilter = $_GET['type'] ?? "";

// --- QUERY BUILDING ---
// Base query: Only show Active facilities
$sql = "SELECT * FROM facilities WHERE Status='Active' ";

if (!empty($search)) {
    $safeSearch = $conn->real_escape_string($search);
    $sql .= " AND (Name LIKE '%$safeSearch%' OR Location LIKE '%$safeSearch%') ";
}

if (!empty($typeFilter)) {
    $safeType = $conn->real_escape_string($typeFilter);
    $sql .= " AND Type = '$safeType' ";
}

// --- PAGINATION COUNT ---
$countSql = str_replace("SELECT *", "SELECT COUNT(*) as total", $sql);
$totalResult = $conn->query($countSql);
$totalRows = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// --- FETCH DATA ---
$sql .= " ORDER BY CreatedAt DESC LIMIT $offset, $limit";
$result = $conn->query($sql);

// --- FETCH FILTER OPTIONS ---
$typesResult = $conn->query("SELECT DISTINCT Type FROM facilities WHERE Status='Active'");
$types = [];
while($t = $typesResult->fetch_assoc()) {
    $types[] = $t['Type'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Facilities - UKM SFBS</title>
    
    <!-- Fonts: Playfair Display & Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        :root {
            --primary: #8a0d19; /* UKM Red */
            --secondary: #006400; /* Dashboard Green */
            --bg-light: #f8f9fa;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        h1, h2, h3 {
            font-family: 'Playfair Display', serif;
        }

        /* Hero Section Styling */
        .hero-section {
            /* Using the court.jpg from your main folder as background */
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('../court.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 80px 0;
            margin-bottom: 40px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-radius: 0 0 30px 30px;
        }

        /* Card Styling */
        .facility-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .facility-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border-color: rgba(138, 13, 25, 0.2);
        }

        .card-img-container {
            position: relative;
            height: 220px;
            overflow: hidden;
        }

        .card-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .facility-card:hover .card-img-container img {
            transform: scale(1.05);
        }

        .facility-type-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.95);
            color: var(--primary);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        /* Filter Bar */
        .filter-bar {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 20px -1px rgba(0, 0, 0, 0.08);
            margin-top: -60px; /* Overlap effect */
            margin-bottom: 40px;
            border: 1px solid #eee;
            position: relative;
            z-index: 10;
        }
        
        /* Footer Styling */
        footer {
            background: white;
            border-top: 1px solid #eee;
            margin-top: auto;
        }
    </style>
</head>
<body>

    <!-- NAV BAR -->
    <nav class="bg-white/90 backdrop-blur-md border-b border-gray-200 sticky top-0 z-50">
        <div class="container mx-auto px-6 py-3 flex justify-between items-center">
            <!-- Left Side: Logos -->
            <div class="flex items-center gap-4">
                <img src="../assets/img/ukm.png" alt="UKM Logo" class="h-14 w-auto">
                <div class="h-8 w-px bg-gray-300 hidden sm:block"></div>
                <img src="../assets/img/pusatsukan.png" alt="Pusat Sukan Logo" class="h-14 w-auto hidden sm:block">
            </div>
            
            <!-- Right Side: Navigation & User Profile -->
            <div class="flex items-center gap-6">
                <a href="dashboard.php" class="text-gray-600 hover:text-[#8a0d19] font-medium transition flex items-center gap-2 group">
                    <span class="p-2 rounded-full bg-gray-100 group-hover:bg-[#8a0d19] group-hover:text-white transition">
                        <i class="fa-solid fa-house"></i>
                    </span>
                    <span class="hidden md:inline">Home</span>
                </a>
                <div class="flex items-center gap-3 pl-6 border-l border-gray-200">
                    <div class="text-right hidden sm:block">
                        <!-- Display Real User Identifier -->
                        <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($_SESSION['user_id'] ?? 'Student'); ?></p>
                        <p class="text-xs text-gray-500 uppercase tracking-wider">Student</p>
                    </div>
                    <!-- User Profile Image -->
                    <img src="../assets/img/user.png" alt="Profile" class="w-10 h-10 rounded-full border-2 border-white shadow-md object-cover">
                </div>
            </div>
        </div>
    </nav>

    <!-- HERO HEADER -->
    <div class="hero-section text-center">
        <div class="container mx-auto px-6">
            <h1 class="text-4xl md:text-6xl font-bold mb-4 tracking-tight">UKM Sport Facility Booking</h1>
            <p class="text-lg md:text-xl opacity-90 max-w-2xl mx-auto font-light leading-relaxed">
                Seamless booking. Active living.
            </p>
        </div>
    </div>

    <main class="container mx-auto px-6 pb-20 flex-grow">
        
        <!-- SEARCH & FILTER -->
        <div class="filter-bar max-w-5xl mx-auto">
            <form method="GET" class="flex flex-col md:flex-row gap-4 items-center">
                <div class="flex-grow w-full relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search facilities..." 
                           class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-[#8a0d19] focus:ring-1 focus:ring-[#8a0d19] transition">
                </div>
                
                <div class="w-full md:w-56 relative">
                    <i class="fa-solid fa-layer-group absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <select name="type" class="w-full pl-10 pr-8 py-3 bg-gray-50 border border-gray-200 rounded-lg appearance-none focus:outline-none focus:border-[#8a0d19] cursor-pointer" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach($types as $t): ?>
                            <option value="<?= $t ?>" <?= ($typeFilter === $t) ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                </div>

                <button type="submit" class="w-full md:w-auto px-8 py-3 bg-[#8a0d19] text-white font-bold rounded-lg hover:bg-[#6d0a14] transition shadow-md transform hover:-translate-y-0.5">
                    Search
                </button>
            </form>
        </div>

        <!-- RESULTS GRID -->
        <?php if ($result->num_rows > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php while($row = $result->fetch_assoc()): ?>
                    <?php 
                        // Image Path Logic - Points to admin uploads folder
                        $photo = $row['PhotoURL'];
                        $imgSrc = (!empty($photo) && file_exists("../admin/uploads/" . $photo)) 
                                  ? "../admin/uploads/" . $photo 
                                  : "https://placehold.co/600x400/e2e8f0/1e293b?text=No+Image";
                    ?>
                    
                    <div class="facility-card group">
                        <!-- Image Top -->
                        <div class="card-img-container">
                            <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($row['Name']) ?>">
                            <span class="facility-type-badge"><?= htmlspecialchars($row['Type']) ?></span>
                            <!-- Overlay on hover -->
                            <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        </div>

                        <!-- Content Bottom -->
                        <div class="p-6 flex flex-col flex-grow relative">
                            <div class="mb-2">
                                <h3 class="text-xl font-bold text-gray-800 leading-tight mb-1">
                                    <?= htmlspecialchars($row['Name']) ?>
                                </h3>
                                <div class="flex items-center text-sm text-gray-500">
                                    <i class="fa-solid fa-map-pin text-[#8a0d19] mr-2"></i>
                                    <?= htmlspecialchars($row['Location']) ?>
                                </div>
                            </div>

                            <p class="text-gray-600 text-sm mb-6 line-clamp-3 leading-relaxed flex-grow">
                                <?= htmlspecialchars($row['Description']) ?>
                            </p>

                            <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-100">
                                <div class="flex items-center gap-2 text-xs font-semibold text-gray-500 bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-100">
                                    <i class="fa-solid fa-user-group text-[#8a0d19]"></i> 
                                    <span>Max: <?= $row['Capacity'] ?></span>
                                </div>
                                <a href="check_availability.php?id=<?= $row['FacilityID'] ?>" class="text-white bg-[#8a0d19] hover:bg-[#6d0a14] px-4 py-2 rounded-lg text-sm font-semibold transition shadow-sm hover:shadow">
                                    Check Availability
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- PAGINATION -->
            <?php if ($totalPages > 1): ?>
            <div class="flex justify-center mt-16 space-x-2">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($typeFilter) ?>" 
                       class="w-10 h-10 flex items-center justify-center rounded-lg font-medium transition-all duration-200 
                              <?= ($i == $page) ? 'bg-[#8a0d19] text-white shadow-lg transform scale-110' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Empty State -->
            <div class="text-center py-24 bg-white rounded-2xl border border-dashed border-gray-300">
                <div class="bg-gray-50 rounded-full w-24 h-24 flex items-center justify-center mx-auto mb-6">
                    <i class="fa-regular fa-calendar-xmark text-4xl text-gray-400"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-700 mb-2">No facilities found</h3>
                <p class="text-gray-500 max-w-md mx-auto">We couldn't find any facilities matching your search criteria. Try removing filters or searching for something else.</p>
                <a href="student_facilities.php" class="inline-flex items-center gap-2 mt-6 text-[#8a0d19] font-bold hover:underline">
                    <i class="fa-solid fa-rotate-left"></i> View all facilities
                </a>
            </div>
        <?php endif; ?>

    </main>

    <!-- FOOTER -->
    <footer class="bg-white border-t border-gray-200 py-8">
        <div class="container mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-3 opacity-80">
                    <img src="../assets/img/ukm.png" alt="UKM" class="h-8 grayscale hover:grayscale-0 transition">
                    <div class="h-4 w-px bg-gray-300"></div>
                    <p class="text-sm text-gray-500">&copy; 2025 UKM Sports Facilities Booking System</p>
                </div>
                
                <a href="../logout.php" class="flex items-center gap-2 text-gray-600 hover:text-[#8a0d19] font-semibold transition px-5 py-2.5 border border-gray-200 rounded-lg hover:bg-red-50 hover:border-red-100 group">
                    <i class="fa-solid fa-right-from-bracket group-hover:translate-x-1 transition-transform"></i> 
                    <span>Sign Out</span>
                </a>
            </div>
        </div>
    </footer>

</body>
</html>