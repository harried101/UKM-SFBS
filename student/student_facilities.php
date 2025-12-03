<?php
session_start();

// FIX: Changed 'Admin' to 'Student' so students can access this page.
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Student') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/db_connect.php';

if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

// --- PAGINATION SETUP ---
$limit = 4; 
$page = $_GET['page'] ?? 1;
$page = max(1, (int)$page);
$offset = ($page - 1) * $limit;

// --- SEARCH & FILTER INPUTS ---
$search = $_GET['search'] ?? "";
$type   = $_GET['type'] ?? "";

// --- BUILD QUERY ---
// Start with Active facilities only
$sql = "SELECT * FROM facilities WHERE Status='Active' ";

if ($search !== "") {
    $searchSafe = $conn->real_escape_string($search);
    $sql .= " AND Name LIKE '%$searchSafe%' ";
}

if ($type !== "") {
    $typeSafe = $conn->real_escape_string($type);
    $sql .= " AND Type = '$typeSafe' ";
}

// --- COUNT TOTAL RESULTS (For Pagination) ---
$totalResult = $conn->query($sql);
$total = $totalResult->num_rows;
$totalPages = ceil($total / $limit);

// --- FETCH PAGE RESULTS ---
$sql .= " LIMIT $offset, $limit";
$result = $conn->query($sql);

// --- FETCH DISTINCT TYPES (For Dropdown) ---
$typeResult = $conn->query("SELECT DISTINCT Type FROM facilities WHERE Status='Active'");
$types = [];
while ($row = $typeResult->fetch_assoc()) {
    $types[] = $row['Type'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facilities List - UKM SFBS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .btn-check {
            background-color: #1a73e8;
            color: white;
            font-weight: 600;
            padding: 10px;
            display: block;
            text-align: center;
            border-radius: 6px;
            text-transform: uppercase;
            font-size: .8rem;
            transition: background 0.3s ease;
        }
        .btn-check:hover { background-color: #1557b0; }
        .facility-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .facility-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>

<body class="text-gray-800">

    <!-- HEADER -->
    <header class="bg-white flex justify-between items-center py-6 px-10 border-b border-gray-100 shadow-sm sticky top-0 z-50">
        <div class="text-2xl font-bold leading-tight text-gray-800">
            SPORT FACILITIES<br>BOOKING SYSTEM
        </div>
        <div class="flex items-center gap-6">
            <!-- Home Button -->
            <a href="dashboard.php" class="flex items-center gap-2 text-gray-600 hover:text-[#1a73e8] font-semibold transition">
                <i class="fa-solid fa-house"></i> HOME
            </a>
            <div class="flex items-center gap-3">
                <img src="../img/user.png" class="rounded-full w-10 h-10 border border-gray-200">
                <span class="font-medium">Student</span>
            </div>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="container mx-auto px-6 py-10">
        
        <!-- Title & Search Tools -->
        <div class="flex flex-col md:flex-row justify-between items-end mb-10 gap-4">
            <div>
                <h1 class="text-4xl font-bold text-slate-700">Facilities List</h1>
                <p class="text-gray-500 mt-2">Find and book your preferred sports facility.</p>
            </div>

            <!-- Search Form -->
            <form method="GET" class="flex items-center gap-2 bg-white p-2 rounded-lg shadow-sm border border-gray-200">
                
                <!-- Search Input -->
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="search" placeholder="Search facility..." 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           class="pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-md text-sm focus:outline-none focus:border-blue-500 w-64">
                </div>

                <!-- Type Filter -->
                <select id="typeFilter" name="type" class="px-4 py-2 bg-gray-50 border border-gray-200 rounded-md text-sm focus:outline-none focus:border-blue-500 cursor-pointer">
                    <option value="">All Types</option>
                    <?php foreach($types as $t): ?>
                        <option value="<?php echo $t; ?>" <?php if($type==$t) echo "selected"; ?>>
                            <?php echo $t; ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Hidden submit button for JS trigger, or explicit button -->
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                    Filter
                </button>
            </form>
        </div>

        <!-- FACILITIES GRID -->
        <?php if($result->num_rows > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php while ($row = $result->fetch_assoc()): ?>

                    <?php
                    // Path correction: Images uploaded via admin are in ../admin/uploads/
                    $imgName = $row['PhotoURL'];
                    $imgPath = ($imgName && file_exists("../admin/uploads/" . $imgName)) 
                               ? "../admin/uploads/" . $imgName 
                               : "https://placehold.co/600x400?text=No+Image";
                    ?>

                    <div class="facility-card bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden flex flex-col h-full">
                        
                        <!-- Image Area -->
                        <div class="h-48 w-full overflow-hidden bg-gray-100 relative">
                            <img src="<?= $imgPath ?>" class="w-full h-full object-cover">
                            <div class="absolute top-2 right-2 bg-white/90 backdrop-blur px-2 py-1 rounded text-xs font-bold text-gray-700 shadow-sm">
                                <?= $row['Type'] ?>
                            </div>
                        </div>

                        <!-- Content Area -->
                        <div class="p-5 flex flex-col flex-grow">
                            <h3 class="text-lg font-bold text-gray-800 mb-1 leading-tight">
                                <?= htmlspecialchars($row['Name']) ?>
                            </h3>

                            <p class="text-sm text-gray-500 mb-3 flex items-center gap-1">
                                <i class="fa-solid fa-location-dot text-red-500"></i>
                                <?= htmlspecialchars($row['Location']) ?>
                            </p>

                            <p class="text-gray-600 text-sm mb-5 line-clamp-2 flex-grow">
                                <?= htmlspecialchars($row['Description']) ?>
                            </p>

                            <!-- Booking Button -->
                            <a href="check_availability.php?id=<?= $row['FacilityID'] ?>" class="btn-check mt-auto">
                                Check Availability
                            </a>
                        </div>
                    </div>

                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <!-- No Results State -->
            <div class="text-center py-20 bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="text-6xl mb-4">🔍</div>
                <h3 class="text-xl font-bold text-gray-700">No Facilities Found</h3>
                <p class="text-gray-500 mt-2">Try adjusting your search or filter criteria.</p>
                <a href="student_facilities.php" class="inline-block mt-4 text-blue-600 hover:underline">Clear Filters</a>
            </div>
        <?php endif; ?>

        <!-- PAGINATION -->
        <!-- FIX: Show pagination if pages >= 1 (was > 1) to ensure page '1' is visible -->
        <?php if($totalPages >= 1): ?>
        <div class="flex justify-center mt-12 gap-2">
            <?php for($i=1; $i<=$totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type); ?>"
                   class="w-10 h-10 flex items-center justify-center border rounded-lg font-medium transition-all duration-200
                          <?php echo $i==$page ? 'bg-blue-600 text-white border-blue-600 shadow-md' : 'bg-white text-gray-600 hover:bg-gray-50 hover:border-gray-300'; ?>">
                   <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

    </main>

    <!-- Auto-submit script for Dropdown -->
    <script>
        document.getElementById('typeFilter').addEventListener('change', function(){
            this.form.submit(); // Cleaner way to submit the parent form
        });
    </script>

</body>
</html>