<?php
session_start();
require_once '../includes/db_connect.php';

// SECURITY CHECK
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Student') {
    http_response_code(403);
    exit('<div class="col-span-full text-center text-red-600">Session expired. Please refresh the page.</div>');
}

$search = $_GET['search'] ?? '';
$typeFilter = $_GET['type'] ?? '';

// SQL: Fetch based on Name/Location/Type
$sql = "SELECT * FROM facilities WHERE Status IN ('Active', 'Maintenance')";
$params = [];
$types_str = "";

if (!empty($search)) {
    $sql .= " AND (Name LIKE ? OR Location LIKE ?)";
    $search_param = "%" . $search . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types_str .= "ss";
}

if (!empty($typeFilter)) {
    $sql .= " AND Type = ?";
    $params[] = $typeFilter;
    $types_str .= "s";
}

$sql .= " ORDER BY Name ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types_str, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        
        $photo = $row['PhotoURL'];
        $imgSrc = (!empty($photo) && file_exists("../admin/uploads/".$photo))
                  ? "../admin/uploads/".$photo
                  : "https://placehold.co/600x400/f1f5f9/94a3b8?text=No+Image&font=sans";

        // --- STATUS LOGIC ---
        $isMaintenance = ($row['Status'] === 'Maintenance');
        // Use Orange for Maintenance, Green for Active
        $statusClass = $isMaintenance ? 'bg-orange-100 text-orange-700 border-orange-200' : 'bg-green-100 text-green-700 border-green-200';
        $statusIcon = $isMaintenance ? '<i class="fa-solid fa-screwdriver-wrench mr-1"></i>' : '<i class="fa-solid fa-check-circle mr-1"></i>';
        
        // Button Logic
        if ($isMaintenance) {
            // Disabled Grey Button
            $btnAttr = 'disabled class="w-full bg-gray-100 text-gray-400 py-2.5 rounded-lg font-bold cursor-not-allowed border border-gray-200"';
            $btnText = 'Under Maintenance';
        } else {
            // Active UKM Blue Button
            $btnAttr = 'onclick="openCalendar(\''.$row['FacilityID'].'\')" class="w-full bg-[#0b4d9d] text-white py-2.5 rounded-lg font-bold hover:bg-[#083a75] transition shadow-md hover:shadow-lg transform active:scale-[0.98]"';
            $btnText = 'Check Availability';
        }

        echo '
        <div class="facility-card group bg-white rounded-xl overflow-hidden border border-gray-200 shadow-sm hover:shadow-xl hover:shadow-blue-100/50 transition-all duration-300 flex flex-col h-full hover:-translate-y-1">
            <div class="relative h-56 overflow-hidden">
                <img src="'.$imgSrc.'" alt="'.htmlspecialchars($row['Name']).'" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                <div class="absolute top-3 right-3 bg-white/95 backdrop-blur px-3 py-1 rounded-full text-xs font-bold text-[#0b4d9d] shadow-sm uppercase tracking-wide border border-gray-100">
                    '.htmlspecialchars($row['Type']).'
                </div>
            </div>
            
            <div class="p-5 flex flex-col flex-grow">
                <div class="flex justify-between items-start mb-2">
                    <h3 class="text-lg font-bold text-gray-800 leading-tight group-hover:text-[#0b4d9d] transition-colors">
                        '.htmlspecialchars($row['Name']).'
                    </h3>
                </div>

                <div class="mb-4">
                    <p class="text-sm text-gray-500 flex items-center mb-2">
                        <i class="fa-solid fa-location-dot text-[#0b4d9d] w-5 text-center mr-1"></i> 
                        '.htmlspecialchars($row['Location']).'
                    </p>
                    <div class="inline-flex items-center px-2.5 py-1 rounded-md border text-xs font-semibold '.$statusClass.'">
                        '.$statusIcon.' '.htmlspecialchars($row['Status']).'
                    </div>
                </div>
                
                <p class="text-gray-600 text-sm line-clamp-2 mb-6 h-10 leading-relaxed">
                    '.htmlspecialchars($row['Description']).'
                </p>
                
                <div class="mt-auto pt-4 border-t border-gray-100">
                    <button '.$btnAttr.'>
                        '.$btnText.'
                    </button>
                </div>
            </div>
        </div>';
    }
} else {
    // Empty State (Matches Dashboard Style)
    echo '
    <div class="col-span-full py-16 text-center bg-white rounded-xl border border-dashed border-gray-300">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 mb-4 text-gray-300">
            <i class="fa-solid fa-magnifying-glass text-3xl"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-700 mb-1">No facilities found</h3>
        <p class="text-gray-500 text-sm">Try adjusting your search or filters.</p>
    </div>';
}

$stmt->close();
$conn->close();
?>