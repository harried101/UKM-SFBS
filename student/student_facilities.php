<?php

 //connect db here
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "ukm-sfbs";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("DB Connection Failed: " . $conn->connect_error);
}

//put search and get from db
$search = $_GET['search'] ?? "";
$type   = $_GET['type'] ?? "";

//yang ni untul fetch active facilities sahaja
$sql = "SELECT * FROM facilities WHERE Status='Active' ";

if ($search !== "") {
    $search = $conn->real_escape_string($search);
    $sql .= " AND Name LIKE '$search%' ";
}

if ($type !== "") {
    $type = $conn->real_escape_string($type);
    $sql .= " AND Type = '$type' ";
}

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
        body {
            font-family: 'Poppins', sans-serif;
        }
        .btn-check {
            background-color: #1a73e8;
            color: white;
            font-weight: 600;
            padding: 10px;
            display: block;
            text-align: center;
            border-radius: 6px;
            text-transform: uppercase;
            font-size: 0.85rem;
        }
        .btn-check:hover {
            background-color: #1557b0;
        }
        .facility-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>

<body class="text-gray-800">

    <!-- 1. HEADER -->
    <header class="flex justify-between items-center py-6 px-10 border-b border-gray-100">
        <div class="text-2xl font-bold leading-tight">
            SPORT FACILITIES<br>BOOKING SYSTEM
        </div>
        <div class="flex items-center gap-3">
            <img src="https://placehold.co/40x40/ccc/fff?text=A" class="rounded-full w-10 h-10">
            <span class="font-medium">Aina</span>
        </div>
    </header>

    <!-- 2. MAIN CONTENT -->
    <main class="container mx-auto px-6 py-10">
        
        <!-- Title & Search -->
        <div class="text-center mb-10 relative">
            <h1 class="text-4xl font-bold text-slate-700">Facilities List</h1>
            
            <!-- Search bar positioned absolutely to the right (desktop) or stacked (mobile) -->
            <div class="mt-6 md:absolute md:right-0 md:top-0 md:mt-2 flex items-center justify-end">
                <input type="text" placeholder="Search..." class="search-input text-sm">
                <button class="ml-2 text-gray-500">
                    <!-- Search Icon SVG -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </button>
            </div>
        </div>

        <!-- 3. THE GRID (The container for cards) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">

            <!-- CARD 1: Badminton -->
            <div class="facility-card shadow-sm hover:shadow-lg">
                <img src="https://placehold.co/600x400/2F4F4F/ffffff?text=Badminton" class="w-full h-48 object-cover">
                <div class="p-5">
                    <h3 class="text-lg font-bold text-gray-800">Badminton Court</h3>
                    <p class="text-gray-500 text-sm mb-6">Kolej Keris Mas</p>
                    <a href="#" class="btn-check">Check Availability</a>
                </div>
            </div>

            <!-- CARD 2: Basketball -->
            <div class="facility-card shadow-sm hover:shadow-lg">
                <img src="https://placehold.co/600x400/D2691E/ffffff?text=Basketball" class="w-full h-48 object-cover">
                <div class="p-5">
                    <h3 class="text-lg font-bold text-gray-800">Basketball Court</h3>
                    <p class="text-gray-500 text-sm mb-6">Dewan Gemilang</p>
                    <a href="#" class="btn-check">Check Availability</a>
                </div>
            </div>

            <!-- CARD 3: Football -->
            <div class="facility-card shadow-sm hover:shadow-lg">
                <img src="https://placehold.co/600x400/228B22/ffffff?text=Football" class="w-full h-48 object-cover">
                <div class="p-5">
                    <h3 class="text-lg font-bold text-gray-800">Football Court</h3>
                    <p class="text-gray-500 text-sm mb-6">Padang D</p>
                    <a href="#" class="btn-check">Check Availability</a>
                </div>
            </div>

            <!-- CARD 4: Futsal -->
            <div class="facility-card shadow-sm hover:shadow-lg">
                <img src="https://placehold.co/600x400/4169E1/ffffff?text=Futsal" class="w-full h-48 object-cover">
                <div class="p-5">
                    <h3 class="text-lg font-bold text-gray-800">Futsal Court</h3>
                    <p class="text-gray-500 text-sm mb-6">Kolej Pendeta Zaba</p>
                    <a href="#" class="btn-check">Check Availability</a>
                </div>
            </div>

        </div>

        <!-- 4. PAGINATION (Bottom numbers) -->
        <div class="flex justify-end mt-12 gap-3 text-sm font-medium text-gray-600">
            <span class="w-8 h-8 flex items-center justify-center border border-gray-300 rounded-full cursor-pointer hover:bg-gray-100">1</span>
            <span class="w-8 h-8 flex items-center justify-center border border-gray-300 rounded-full cursor-pointer hover:bg-gray-100">2</span>
            <span class="w-8 h-8 flex items-center justify-center border border-gray-300 rounded-full cursor-pointer hover:bg-gray-100">3</span>
            <span class="w-8 h-8 flex items-center justify-center border border-gray-300 rounded-full cursor-pointer hover:bg-gray-100">4</span>
            <span class="flex items-center">...</span>
            <span class="w-8 h-8 flex items-center justify-center border border-gray-300 rounded-full cursor-pointer hover:bg-gray-100">10</span>
        </div>

    </main>

</body>
</html>