<?php
session_start();
require_once '../includes/db_connect.php';

// SECURITY CHECK
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Student') {
    http_response_code(403);
    exit('Unauthorized');
}

$search = $_GET['search'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$limit = 6;
$page = 1;
$offset = ($page - 1) * $limit;

$sql = "SELECT * FROM facilities WHERE Status='Active'";
if (!empty($search)) {
    $safeSearch = $conn->real_escape_string($search);
    $sql .= " AND (Name LIKE '%$safeSearch%' OR Location LIKE '%$safeSearch%')";
}
if (!empty($typeFilter)) {
    $safeType = $conn->real_escape_string($typeFilter);
    $sql .= " AND Type='$safeType'";
}
$sql .= " ORDER BY CreatedAt DESC LIMIT $offset, $limit";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $photo = $row['PhotoURL'];
        $imgSrc = (!empty($photo) && file_exists("../admin/uploads/".$photo))
                  ? "../admin/uploads/".$photo
                  : "https://placehold.co/600x400/e2e8f0/1e293b?text=No+Image";
        echo '
        <div class="facility-card group">
            <div class="card-img-container">
                <img src="'.$imgSrc.'" alt="'.htmlspecialchars($row['Name']).'">
                <span class="facility-type-badge">'.htmlspecialchars($row['Type']).'</span>
            </div>
            <div class="p-6 flex flex-col flex-grow relative">
                <div class="mb-2">
                    <h3 class="text-xl font-bold text-gray-800 leading-tight mb-1">'.htmlspecialchars($row['Name']).'</h3>
                    <div class="flex items-center text-sm text-gray-500">
                        <i class="fa-solid fa-map-pin text-[#8a0d19] mr-2"></i> '.htmlspecialchars($row['Location']).'
                    </div>
                </div>
                <p class="text-gray-600 text-sm mb-6 line-clamp-3 leading-relaxed flex-grow">'.htmlspecialchars($row['Description']).'</p>
                <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-100">
    <div class="flex items-center gap-2 text-xs font-semibold text-gray-500 bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-100">
        <i class="fa-solid fa-user-group text-[#8a0d19]"></i> Max: '.$row['Capacity'].'
    </div>

    <button 
        onclick="openCalendar(\''.$row['FacilityID'].'\')" 
        class="text-white bg-[#8a0d19] hover:bg-[#6d0a14] px-4 py-2 rounded-lg text-sm font-semibold transition shadow-sm hover:shadow">
        Check Availability
    </button>
</div>

            </div>
        </div>
        ';
    }
} else {
    echo '<div class="col-span-3 text-center py-24 bg-white rounded-2xl border border-dashed border-gray-300">
            <h3 class="text-xl font-bold text-gray-700 mb-2">No facilities found</h3>
            <p class="text-gray-500">Try searching again or remove filters.</p>
          </div>';
}
?>
