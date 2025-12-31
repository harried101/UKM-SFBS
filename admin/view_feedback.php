<?php
require_once '../includes/db_connect.php';
session_start();

// Basic Admin Auth Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}
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
      font-weight: 600;
      padding: 12px 16px;
    }
    
    table.dataTable tbody td {
      padding: 12px 16px;
      border-bottom: 1px solid #f1f5f9;
    }
    
    /* Remove default datatable styling that conflicts */
    .dataTables_wrapper .dataTables_length, 
    .dataTables_wrapper .dataTables_filter, 
    .dataTables_wrapper .dataTables_info, 
    .dataTables_wrapper .dataTables_processing, 
    .dataTables_wrapper .dataTables_paginate {
        color: #64748b;
        font-family: 'Inter', sans-serif;
        font-size: 0.875rem;
        padding-top: 1rem;
    }
  </style>
</head>

<body class="bg-slate-50 min-h-screen font-body text-slate-800">

  <!-- NAVBAR (Standard Admin) -->
  <nav class="bg-white/95 backdrop-blur-sm border-b border-gray-200 sticky top-0 z-50 shadow-md">
    <div class="container mx-auto px-6 py-3 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <img src="../assets/img/ukm.png" alt="UKM Logo" class="h-12 w-auto">
            <div class="h-8 w-px bg-gray-300 hidden sm:block"></div>
            <img src="../assets/img/pusatsukanlogo.png" alt="Pusat Sukan Logo" class="h-12 w-auto hidden sm:block">
        </div>
        <div class="flex items-center gap-6">
            <a href="dashboard.php" class="text-gray-600 hover:text-[#0b4d9d] font-medium transition">Home</a>
            <a href="addfacilities.php" class="text-gray-600 hover:text-[#0b4d9d] font-medium transition">Facilities</a>
            <a href="bookinglist.php" class="text-gray-600 hover:text-[#0b4d9d] font-medium transition">Bookings</a>
            
            <!-- Active State -->
            <a href="view_feedback.php" class="text-[#0b4d9d] font-bold transition">Feedback</a>

            <div class="flex items-center gap-3 pl-6 border-l border-gray-200">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-gray-800">Admin</p>
                </div>
                <div class="relative group">
                    <img src="../assets/img/user.png" alt="Profile" class="w-10 h-10 rounded-full border-2 border-white shadow-md object-cover cursor-pointer hover:scale-105 transition">
                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-100 hidden group-hover:block z-50">
                        <a href="../logout.php" onclick="return confirm('Logout?');" class="block px-4 py-3 text-sm text-red-600 hover:bg-red-50 rounded-lg m-1">
                            <i class="fa-solid fa-right-from-bracket mr-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </nav>

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
              <th class="text-left rounded-tl-lg">Student</th>
              <th class="text-left">Facility Name</th>
              <th class="text-left">Rating</th>
              <th class="text-left">Feedback</th>
              <th class="text-left rounded-tr-lg">Date</th>
            </tr>
          </thead>
          <!-- Tbody empty, populated by JS -->
          <tbody class="divide-y divide-slate-100"></tbody>
        </table>
      </div>

    </div>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script>
    $(document).ready(function () {
      // Initialize DataTable with AJAX
      const table = $('#feedbackTable').DataTable({
        ajax: {
            url: 'fetch_feedback_data.php', // Fetches data from backend
            dataSrc: 'data'
        },
        columns: [
            { 
                data: 'StudentName',
                render: function(data, type, row) {
                    return `<div class="font-medium text-slate-800">${data}</div>
                            <div class="text-xs text-slate-400 font-mono">${row.UserIdentifier}</div>`;
                }
            },
            { data: 'FacilityName' },
            { 
                data: 'Rating',
                render: function(data) {
                    return `<span class="px-2 py-1 bg-ukm-light text-ukm-dark rounded-full inline-flex items-center gap-1 font-bold">
                              <i class="fa-solid fa-star text-yellow-400"></i> ${data}
                            </span>`;
                }
            },
            { data: 'Comment' },
            { data: 'FormattedDate' }
        ],
        pageLength: 10,
        lengthMenu: [5, 10, 20],
        order: [[4, 'desc']], // Sort by Date Descending
        dom: 'lrtip', // Hide default search/pagination styling to use ours
        language: {
            emptyTable: "No feedback records found."
        }
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

</body>
</html>