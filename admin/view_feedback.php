<?php
require_once 'includes/admin_auth.php'; // Standardized Auth & User Fetch
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Student Feedback</title>

  <!-- Fonts & Icons -->
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

  <!-- DataTables -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

  <!-- Tailwind Config -->
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            heading: ['"Playfair Display"', 'serif'],
            body: ['Inter', 'sans-serif'],
          },
          colors: {
            ukm: {
              blue: '#0b4d9d',
              dark: '#063a75',
              light: '#e0f2fe'
            }
          }
        }
      }
    }
  </script>

  <!-- Custom Styles -->
  <style>
    .fade-in { animation: fadeIn 0.4s ease-out forwards; }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .glass-panel {
      background: white;
      border: 1px solid #e2e8f0;
      box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1),
                  0 2px 4px -2px rgb(0 0 0 / 0.1);
    }

    table.dataTable thead th {
      background-color: #0b4d9d;
      color: white;
    }
  </style>
</head>

<body class="bg-slate-50 min-h-screen font-body text-slate-800">

  <!-- NAVBAR -->
  <?php
    $nav_active = 'feedback';
    include 'includes/navbar.php';
  ?>

  <div class="max-w-7xl mx-auto px-6 py-8 fade-in">

    <!-- PAGE HEADER -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8 pb-6 border-b border-slate-200">
      <div>
        <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">
          <span class="bg-slate-100 px-2 py-1 rounded">Admin</span>
          <i class="fa-solid fa-chevron-right text-[10px]"></i>
          <span class="text-ukm-blue">Feedback</span>
        </div>
        <h1 class="text-3xl md:text-4xl font-extrabold text-ukm-blue tracking-tight font-heading">
          Feedback Management
        </h1>
        <p class="text-slate-500 mt-2 text-lg font-heading">
          View students' feedback on facilities.
        </p>
      </div>
    </div>

    <!-- FILTER & TABLE PANEL -->
    <div class="glass-panel rounded-2xl p-6 shadow-lg">

      <!-- Filter/Search -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">

        <!-- Search Box -->
        <div class="relative w-full md:w-1/3">
          <label class="sr-only" for="searchBox">Search Feedback</label>
          <input type="text" id="searchBox" placeholder="Search students or facilities..."
            class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-ukm-blue focus:border-ukm-blue shadow-sm transition-all"
          />
        </div>

        <!-- Rating Filter -->
        <div class="relative w-full md:w-48 mt-2 md:mt-0">
          <label class="sr-only" for="ratingFilter">Filter by Rating</label>
          <select id="ratingFilter"
            class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-ukm-blue focus:border-ukm-blue text-base shadow-sm transition-all">
            <option value="">All Ratings</option>
            <option value="5">5 - Excellent</option>
            <option value="4">4 - Good</option>
            <option value="3">3 - Average</option>
            <option value="2">2 - Poor</option>
            <option value="1">1 - Very Poor</option>
          </select>
        </div>

      </div>

      <!-- Feedback Table -->
      <div class="overflow-x-auto rounded-xl">
        <table id="feedbackTable" class="w-full text-sm display rounded-lg">
          <thead>
            <tr class="bg-ukm-blue text-white">
              <th>Student</th>
              <th>Facility Name</th>
              <th>Rating</th>
              <th>Feedback</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <tr class="hover:bg-slate-100 transition-all rounded-lg">
              <td class="font-medium">Ali</td>
              <td>Swimming Pool</td>
              <td><span class="px-2 py-1 bg-ukm-light text-ukm-dark rounded-full inline-flex items-center gap-1">
                <i class="fa-solid fa-star text-yellow-400"></i> 5
              </span></td>
              <td>Not clean</td>
              <td>2025-01-10</td>
            </tr>
            <tr class="hover:bg-slate-100 transition-all rounded-lg">
              <td class="font-medium">Siti</td>
              <td>Badminton Court</td>
              <td><span class="px-2 py-1 bg-ukm-light text-ukm-dark rounded-full inline-flex items-center gap-1">
                <i class="fa-solid fa-star text-yellow-400"></i> 3
              </span></td>
              <td>Not bad</td>
              <td>2025-01-11</td>
            </tr>
            <tr class="hover:bg-slate-100 transition-all rounded-lg">
              <td class="font-medium">Kumar</td>
              <td>Field</td>
              <td><span class="px-2 py-1 bg-ukm-light text-ukm-dark rounded-full inline-flex items-center gap-1">
                <i class="fa-solid fa-star text-yellow-400"></i> 2
              </span></td>
              <td>Very spacious</td>
              <td>2025-01-12</td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script>
    $(document).ready(function () {
      const table = $('#feedbackTable').DataTable({
        pageLength: 5,
        lengthMenu: [5, 10, 20],
        order: [[4, 'desc']],
        dom: 'lrtip' // removes default search box
      });

      // Filter by rating
      $('#ratingFilter').on('change', function () {
        table.column(2).search(this.value).draw();
      });

      // Live search for custom search box
      $('#searchBox').on('keyup', function () {
        table.search(this.value).draw();
      });
    });
  </script>

  <?php include 'includes/footer.php'; ?>

</body>
</html>
