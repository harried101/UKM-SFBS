<?php

require_once '../includes/db_connect.php';

$limit = 4; 
$page = $_GET['page'] ?? 1;
$page = max(1, (int)$page);
$offset = ($page - 1) * $limit;
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("DB Connection Failed: " . $conn->connect_error);
}

//put search and get from db
$search = $_GET['search'] ?? "";
$type   = $_GET['type'] ?? "";

//yang ni untuk fetch active facilities sahaja
$sql = "SELECT * FROM facilities WHERE Status='Active' ";

if ($search !== "") {
    $search = $conn->real_escape_string($search);
    $sql .= " AND Name LIKE '$search%' ";
}

if ($type !== "") {
    $type = $conn->real_escape_string($type);
    $sql .= " AND Type = '$type' ";
}

$totalResult = $conn->query($sql);
$total = $totalResult->num_rows;
$totalPages = ceil($total / $limit);

$sql .= " LIMIT $offset, $limit";
$result = $conn->query($sql);

//for list (the dropdown)
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

    <style>
        body { font-family: 'Poppins', sans-serif; }
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
        }
        .btn-check:hover { background-color: #1557b0; }
        .facility-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>

<body class="text-gray-800">

    <!-- HEADER -->
    <header class="flex justify-between items-center py-6 px-10 border-b border-gray-100">
        <div class="text-2xl font-bold leading-tight">
            SPORT FACILITIES<br>BOOKING SYSTEM
        </div>
        <div class="flex items-center gap-3">
            <img src="https://placehold.co/40x40/ccc/fff?text=A" class="rounded-full w-10 h-10">
            <span class="font-medium">Aina</span>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="container mx-auto px-6 py-10">
        
        <!-- Title & Search -->
        <div class="text-center mb-10 relative">
            <h1 class="text-4xl font-bold text-slate-700">Facilities List</h1>

            <form method="GET" 
                class="mt-6 md:absolute md:right-0 md:top-0 md:mt-2 flex items-center justify-end">

                <input type="text" name="search" placeholder="Search..." 
                       value="<?php echo $search; ?>" 
                       class="text-sm border px-3 py-2 rounded">

            <select id="typeFilter" name="type" class="ml-2 border px-3 py-2 text-sm rounded">
    <option value="">All Types</option>
    <?php foreach($types as $t): ?>
        <option value="<?php echo $t; ?>" <?php if($type==$t) echo "selected"; ?>>
            <?php echo $t; ?>
        </option>
    <?php endforeach; ?>
</select>

<script>
document.getElementById('typeFilter').addEventListener('change', function(){
    const type = this.value;
    const search = document.querySelector('input[name="search"]').value;
    window.location.href = `student_facilities.php?type=${type}&search=${search}`;
});
</script>


                <button class="ml-2 text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" 
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" 
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </form>
        </div>

     <!-- FACILITIES GRID -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
    <?php while ($row = $result->fetch_assoc()): ?>

        <?php
        $img = $row['PhotoURL'];
        $img = ($img) ? "../admin/uploads/" . $img : "https://placehold.co/600x400?text=No+Image";
        ?>

        <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">

            <!-- Image -->
            <div class="h-44 w-full overflow-hidden">
                <img src="<?= $img ?>" 
                     class="w-full h-full object-cover hover:scale-105 transition-all duration-300">
            </div>

            <!-- Content -->
            <div class="p-5 flex flex-col h-48">

                <h3 class="text-lg font-semibold text-gray-800 mb-1 leading-tight">
                    <?= $row['Name'] ?>
                </h3>

                <p class="text-sm text-gray-500 mb-1">
                    <i class="fa-solid fa-location-dot mr-1 text-blue-600"></i>
                    <?= $row['Location'] ?>
                </p>

                <p class="text-gray-600 text-sm mb-4 line-clamp-3">
                    <?= $row['Description'] ?>
                </p>

                <a href="check_availability.php?id=<?= $row['FacilityID'] ?>"
                   class="mt-auto block text-center bg-blue-600 hover:bg-blue-700 
                          text-white font-semibold py-2 rounded-lg transition">
                    Check Availability
                </a>
            </div>

        </div>

    <?php endwhile; ?>
</div>

<div class="flex justify-center mt-8 gap-2">
    <?php for($i=1; $i<=$totalPages; $i++): ?>
        <a href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>&type=<?php echo $type; ?>"
           class="w-8 h-8 flex items-center justify-center border rounded hover:bg-gray-100
                  <?php echo $i==$page?'bg-blue-500 text-white':''; ?>">
           <?php echo $i; ?>
        </a>
    <?php endfor; ?>
</div>

    </main>

</body>
</html>
