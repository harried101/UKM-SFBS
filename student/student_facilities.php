<?php
session_start();

// 1. SECURITY CHECK: Redirect if not logged in or role is not Student
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Student') {
    header("Location: ../index.php");
    exit();
}

// 2. DATABASE CONNECTION
require_once '../includes/db_connect.php';

if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

// --- PAGINATION SETUP ---
$limit = 6; 
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// --- SEARCH & FILTER INPUTS ---
$search = $_GET['search'] ?? "";
$typeFilter = $_GET['type'] ?? "";

// --- BUILD QUERY ---
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
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body {
            /* MATCHING ADMIN PAGE: Full page background */
            background: url('../img/background.jpg'); 
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed; /* Keeps background still while scrolling */
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navbar Styling (Matching Admin) */
        nav {
            background: #bfd9dc;
            padding: 10px 40px;
            border-bottom-left-radius: 25px;
            border-bottom-right-radius: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }

        .nav-logo img {
            height: 65px; /* Matching Admin Sizing */
        }

        .nav-link {
            color: #071239ff;
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 12px;
            transition: 0.3s ease;
            text-decoration: none;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.5);
        }

        /* Main Content Container */
        .content-wrapper {
            background: rgba(255, 255, 255, 0.9); /* Slight transparency to see background */
            border-radius: 25px;
            padding: 30px;
            margin-top: 30px;
            margin-bottom: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        /* Facility Card */
        .facility-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .facility-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .btn-check {
            background-color: #1e40af;
            color: white;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            display: block;
            font-weight: 600;
            transition: background 0.3s;
        }
        .btn-check:hover { background-color: #1557b0; }

        /* Footer Button Style */
        .btn-logout {
            background-color: #c62828;
            color: white;
            padding: 10px 30px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.3s;
        }
        .btn-logout:hover { background-color: #b71c1c; }
    </style>
</head>
<body>

    <!-- NAV BAR -->
    <nav>
        <div class="nav-logo flex items-center gap-3">
            <!-- Corrected paths to ../img/ -->
            <img src="../img/ukm.png" alt="UKM Logo">
            <img src="../img/pusatsukan.png" alt="Pusat Sukan Logo">
        </div>

        <div class="flex items-center gap-4">
            <a class="nav-link" href="dashboard.php">Home</a>
            
            <div class="flex items-center gap-2">
                <img src="../img/user.png" class="rounded-full w-10 h-10 border border-gray-400">
                <!-- DISPLAY USER MATRIC NUMBER HERE -->
                <span class="font-bold" style="color:#071239ff;">
                    <?php echo htmlspecialchars($_SESSION['userIdentifier'] ?? 'Student'); ?>
                </span>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="container mx-auto px-4 flex-grow">
        <div class="content-wrapper">
            
            <div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-[#071239ff] mb-2">Facilities List</h1>
                    <p class="text-gray-600">Browse and book your preferred sports facility.</p>
                </div>

                <!-- Search & Filter Form -->
                <form method="GET" class="flex gap-2">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search..." class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    
                    <select name="type" class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        <?php foreach($types as $t): ?>
                            <option value="<?= $t ?>" <?= ($typeFilter === $t) ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <!-- RESULTS GRID -->
            <?php if ($result->num_rows > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php while($row = $result->fetch_assoc()): ?>
                        <?php 
                            // Image Path Logic
                            $photo = $row['PhotoURL'];
                            $imgSrc = (!empty($photo) && file_exists("../admin/uploads/" . $photo)) 
                                      ? "../admin/uploads/" . $photo 
                                      : "https://placehold.co/600x400/e2e8f0/1e293b?text=No+Image";
                        ?>
                        
                        <div class="facility-card">
                            <div class="h-48 overflow-hidden relative">
                                <img src="<?= $imgSrc ?>" class="w-full h-full object-cover">
                                <span class="absolute top-3 right-3 bg-white/90 px-3 py-1 rounded-full text-xs font-bold text-[#8a0d19] shadow-sm">
                                    <?= htmlspecialchars($row['Type']) ?>
                                </span>
                            </div>
                            <div class="p-5">
                                <h3 class="text-xl font-bold text-gray-800 mb-1"><?= htmlspecialchars($row['Name']) ?></h3>
                                <p class="text-sm text-gray-500 mb-3">
                                    <i class="fa-solid fa-location-dot text-[#8a0d19] mr-1"></i> 
                                    <?= htmlspecialchars($row['Location']) ?>
                                </p>
                                <p class="text-gray-600 text-sm mb-4 line-clamp-2"><?= htmlspecialchars($row['Description']) ?></p>
                                
                                <div class="flex justify-between items-center mt-4">
                                    <span class="text-xs font-semibold bg-gray-100 px-2 py-1 rounded text-gray-600">
                                        Cap: <?= $row['Capacity'] ?>
                                    </span>
                                    <a href="check_availability.php?id=<?= $row['FacilityID'] ?>" class="text-[#1e40af] font-bold text-sm hover:underline">
                                        Check Availability →
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- PAGINATION -->
                <?php if ($totalPages > 1): ?>
                <div class="flex justify-center mt-10 gap-2">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($typeFilter) ?>" 
                           class="w-10 h-10 flex items-center justify-center rounded-lg font-medium transition 
                                  <?= ($i == $page) ? 'bg-[#1e40af] text-white' : 'bg-white border hover:bg-gray-100' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="text-center py-10 text-gray-500">
                    <p class="text-xl">No facilities found.</p>
                    <a href="student_facilities.php" class="text-blue-600 hover:underline">Clear Search</a>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <!-- FOOTER with Sign Out -->
    <footer class="bg-[#bfd9dc] py-6 mt-auto">
        <div class="container mx-auto px-6 text-center">
            <a href="../logout.php" class="btn-logout">
                <i class="fa-solid fa-right-from-bracket mr-2"></i> Sign Out
            </a>
            <p class="text-sm text-gray-600 mt-4">&copy; 2025 UKM Sports Facilities Booking System</p>
        </div>
    </footer>

</body>
</html>