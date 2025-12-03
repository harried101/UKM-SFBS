<?php
session_start();

// SECURITY CHECK: Redirect if not logged in or role is not Student
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Student') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/db_connect.php';

if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

// --- PAGINATION ---
$limit = 6; 
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// --- SEARCH & FILTER ---
$search = $_GET['search'] ?? "";
$type   = $_GET['type'] ?? "";

// --- QUERY BUILDER ---
$sql = "SELECT * FROM facilities WHERE Status='Active' ";

if ($search !== "") {
    $search = $conn->real_escape_string($search);
    $sql .= " AND (Name LIKE '%$search%' OR Location LIKE '%$search%') ";
}

if ($type !== "") {
    $type = $conn->real_escape_string($type);
    $sql .= " AND Type = '$type' ";
}

// Count total for pagination
$countSql = str_replace("SELECT *", "SELECT COUNT(*) as total", $sql);
$totalResult = $conn->query($countSql);
$totalRows = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// Fetch data
$sql .= " ORDER BY CreatedAt DESC LIMIT $offset, $limit";
$result = $conn->query($sql);

// Get types for dropdown
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

    <!-- Tailwind for Grid Layout -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        /* --- RESTORED SIMPLE DESIGN --- */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        header {
            background: white;
            border-bottom: 1px solid #ddd;
        }

        h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 20px;
        }

        /* Input Fields */
        input[type="text"], 
        select {
            padding: 10px;
            border: 1px solid #aaa;
            border-radius: 4px;
            font-size: 14px;
            background: white;
        }

        /* Facility Card */
        .facility-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .facility-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        /* Blue Buttons (#0064c8) */
        .btn-check, 
        .pagination-link.active,
        .btn-search {
            background: #0064c8;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 10px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.85rem;
            display: block;
            text-align: center;
            transition: background 0.3s;
        }

        .btn-check:hover, 
        .pagination-link.active:hover,
        .btn-search:hover {
            background: #004a96;
        }

        .pagination-link {
            background: white;
            border: 1px solid #ddd;
            color: #333;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
        }
        
        /* Footer Style */
        footer {
            background: white;
            border-top: 1px solid #ddd;
            margin-top: auto;
            padding: 20px;
            text-align: center;
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <header class="flex justify-between items-center py-4 px-8 shadow-sm">
        <div class="text-xl font-bold leading-tight">
            SPORT FACILITIES<br>BOOKING SYSTEM
        </div>
        <div class="flex items-center gap-6">
            <!-- Home Button -->
            <a href="dashboard.php" class="flex items-center gap-2 text-gray-600 hover:text-[#0064c8] font-semibold transition">
                <i class="fa-solid fa-house"></i> HOME
            </a>
            <div class="flex items-center gap-3">
                <img src="../img/user.png" class="rounded-full w-10 h-10 border border-gray-300 p-1">
                <!-- SOLUTION FOR USER: Displays the logged-in Student ID dynamically -->
                <span class="font-medium">
                    <?php echo htmlspecialchars($_SESSION['userIdentifier'] ?? 'Student'); ?>
                </span>
            </div>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="container mx-auto px-6 py-10 flex-grow">
        
        <div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4">
            <h1 class="font-bold">Facilities List</h1>

            <form method="GET" class="flex items-center gap-2">
                <!-- Search Input -->
                <input type="text" name="search" placeholder="Search..." 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       style="width: 250px;">

                <!-- Filter Dropdown -->
                <select id="typeFilter" name="type">
                    <option value="">All Types</option>
                    <?php foreach($types as $t): ?>
                        <option value="<?php echo $t; ?>" <?php if($type==$t) echo "selected"; ?>>
                            <?php echo $t; ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn-search" style="width: auto; padding: 10px 15px;">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>
        </div>

        <!-- FACILITIES GRID -->
        <?php if ($result->num_rows > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php while ($row = $result->fetch_assoc()): ?>

                    <?php
                    // Dynamic Image Path Logic
                    $img = $row['PhotoURL'];
                    $img = (!empty($img) && file_exists("../admin/uploads/" . $img)) 
                           ? "../admin/uploads/" . $img 
                           : "https://placehold.co/600x400?text=No+Image";
                    ?>

                    <div class="facility-card flex flex-col h-full">
                        
                        <!-- Image -->
                        <div class="h-44 w-full overflow-hidden border-b border-gray-200">
                            <img src="<?= $img ?>" class="w-full h-full object-cover">
                        </div>

                        <!-- Content -->
                        <div class="p-5 flex flex-col flex-grow">
                            <h3 class="text-lg font-bold text-[#333] mb-1 leading-tight">
                                <?= htmlspecialchars($row['Name']) ?>
                            </h3>

                            <p class="text-sm text-gray-500 mb-2">
                                <i class="fa-solid fa-location-dot mr-1 text-[#0064c8]"></i>
                                <?= htmlspecialchars($row['Location']) ?>
                            </p>

                            <p class="text-gray-600 text-sm mb-4 line-clamp-3 flex-grow">
                                <?= htmlspecialchars($row['Description']) ?>
                            </p>

                            <a href="check_availability.php?id=<?= $row['FacilityID'] ?>" class="btn-check mt-auto">
                                Check Availability
                            </a>
                        </div>
                    </div>

                <?php endwhile; ?>
            </div>

            <!-- PAGINATION -->
            <?php if ($totalPages >= 1): ?>
            <div class="flex justify-center mt-10 gap-2">
                <?php for($i=1; $i<=$totalPages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type); ?>"
                       class="pagination-link <?php echo $i==$page ? 'active' : 'hover:bg-gray-200'; ?>">
                       <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="text-center py-20 text-gray-500">
                <i class="fa-regular fa-folder-open text-4xl mb-3 text-gray-300"></i>
                <p>No facilities found.</p>
                <a href="student_facilities.php" class="text-[#0064c8] hover:underline mt-2 inline-block">Reset Filters</a>
            </div>
        <?php endif; ?>

    </main>

    <!-- FOOTER -->
    <footer>
        <div class="flex justify-between items-center max-w-6xl mx-auto px-4">
            <span class="text-sm text-gray-500">&copy; 2025 UKM SFBS</span>
            <a href="../logout.php" class="text-[#0064c8] font-bold text-sm hover:underline flex items-center gap-2">
                <i class="fa-solid fa-right-from-bracket"></i> Sign Out
            </a>
        </div>
    </footer>

    <script>
        document.getElementById('typeFilter').addEventListener('change', function(){
            this.form.submit();
        });
    </script>

</body>
</html>