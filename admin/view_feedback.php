<?php
require_once 'includes/admin_auth.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Student Feedback</title>

  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

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

    /* Custom DataTables Styling */
    table.dataTable thead th {
      background-color: #0b4d9d;
      color: white;
      font-weight: 600;
      padding: 14px 16px;
      border-bottom: none !important;
    }
    
    table.dataTable tbody td {
      padding: 14px 16px;
      border-bottom: 1px solid #f1f5f9;
      vertical-align: middle;
    }

    table.dataTable.no-footer {
        border-bottom: 1px solid #e2e8f0;
    }
    
    .dataTables_wrapper .dataTables_length, 
    .dataTables_wrapper .dataTables_filter, 
    .dataTables_wrapper .dataTables_info, 
    .dataTables_wrapper .dataTables_processing, 
    .dataTables_wrapper .dataTables_paginate {
        color: #64748b;
        font-family: 'Inter', sans-serif;
        font-size: 0.875rem;
        padding-top: 1rem;
        margin-bottom: 1rem;
    }

    /* Search Input Styling */
    .dataTables_filter input {
        border: 1px solid #cbd5e1;
        border-radius: 0.5rem;
        padding: 0.5rem 1rem;
        background-color: #f8fafc;
        outline: none;
        transition: all 0.2s;
        margin-left: 0.5rem;
    }
    .dataTables_filter input:focus {
        border-color: #0b4d9d;
        box-shadow: 0 0 0 2px rgba(11, 77, 157, 0.1);
        background-color: white;
    }

    /* Button Styling */
    button.dt-button {
        background: white !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 0.5rem !important;
        color: #475569 !important;
        font-size: 0.875rem !important;
        padding: 0.5rem 1rem !important;
        transition: all 0.2s !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
    }
    button.dt-button:hover {
        background: #f1f5f9 !important;
        border-color: #94a3b8 !important;
        color: #0f172a !important;
    }

    /* Pagination Styling */
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        border-radius: 0.5rem !important;
        border: 1px solid transparent !important;
        padding: 0.5rem 1rem !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current, 
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: #eff6ff !important;
        color: #0b4d9d !important;
        border: 1px solid #bfdbfe !important;
        font-weight: 600;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: #f8fafc !important;
        color: #0b4d9d !important;
        border: 1px solid #e2e8f0 !important;
    }

    /* Hide default export text */
    .dt-buttons { gap: 0.5rem; display: flex; margin-bottom: 1rem; }
  </style>
</head>

<body class="bg-slate-50 min-h-screen font-body text-slate-800">

<!-- NAVBAR -->
<?php 
$nav_active = 'feedback';
include 'includes/navbar.php'; 
?>

  <div class="max-w-7xl mx-auto px-6 py-8 fade-in">

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
          Review student feedback, analyze ratings, and export reports.
        </p>
      </div>
    </div>

    <div class="glass-panel rounded-2xl p-6 shadow-lg">

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">

        <div class="flex flex-col gap-1">
          <label class="text-xs font-bold text-slate-500 uppercase ml-1">Filter Facility</label>
          <select id="facilityFilter"
            class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-ukm-blue focus:border-ukm-blue shadow-sm transition-all bg-white text-sm">
            <option value="">All Facilities</option>
            </select>
        </div>

        <div class="flex flex-col gap-1">
          <label class="text-xs font-bold text-slate-500 uppercase ml-1">Filter Date</label>
          <input type="date" id="dateFilter"
            class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-ukm-blue focus:border-ukm-blue shadow-sm transition-all bg-white text-sm text-slate-600">
        </div>

        <div class="flex flex-col gap-1">
          <label class="text-xs font-bold text-slate-500 uppercase ml-1">Filter Rating</label>
          <select id="ratingFilter"
            class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-ukm-blue focus:border-ukm-blue shadow-sm transition-all bg-white text-sm">
            <option value="">All Ratings</option>
            <option value="5">5 - Excellent</option>
            <option value="4">4 - Good</option>
            <option value="3">3 - Average</option>
            <option value="2">2 - Poor</option>
            <option value="1">1 - Very Poor</option>
          </select>
        </div>

      </div>

      <div class="overflow-hidden rounded-xl border border-slate-100">
        <table id="feedback" class="w-full text-sm display hover rounded-lg">
          <thead>
            <tr class="bg-ukm-blue text-white">
              <th class="text-left first:rounded-tl-lg">Student</th>
              <th class="text-left">Facility Name</th>
              <th class="text-left">Rating</th>
              <th class="text-left">Feedback</th>
              <th class="text-left last:rounded-tr-lg">Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white"></tbody>
        </table>
      </div>

    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <!-- DataTables Buttons -->
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
  
  <script>
    // Custom Date Filter Function
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex, rowData, counter) {
            var selectedDate = $('#dateFilter').val();
            if (!selectedDate) return true; // Show all if no date selected

            // rowData.SubmittedAt is the raw DB 'YYYY-MM-DD HH:MM:SS'
            // We just need to check if it starts with the selected 'YYYY-MM-DD'
            var rowDate = rowData.SubmittedAt; 
            
            if (rowDate && rowDate.startsWith(selectedDate)) {
                return true;
            }
            return false;
        }
    );

    $(document).ready(function () {
      const table = $('#feedback').DataTable({
        ajax: {
            url: 'fetch_feedback_data.php',
            dataSrc: 'data'
        },
        columns: [
            { 
                data: 'StudentName',
                render: function(data, type, row) {
                    // For searching and sorting, return just the name
                    if (type === 'sort' || type === 'filter') {
                        return data + ' ' + (row.UserIdentifier || '');
                    }
                    // Display
                    return `<div class="font-bold text-slate-800">${data}</div>
                            <div class="text-[11px] text-slate-400 font-mono mt-0.5">${row.UserIdentifier}</div>`;
                }
            },
            { 
               data: 'FacilityName',
               render: function(data) {
                 return `<span class="font-medium text-slate-600">${data}</span>`;
               }
            },
            { 
                data: 'Rating',
                render: function(data) {
                    let stars = '';
                    for(let i=0; i<data; i++) stars += '<i class="fa-solid fa-star text-yellow-400 text-[10px]"></i>';
                    for(let i=data; i<5; i++) stars += '<i class="fa-regular fa-star text-slate-200 text-[10px]"></i>';
                    
                    return `<div class="flex items-center gap-2">
                              <span class="font-bold text-slate-700 w-4 text-center">${data}</span>
                              <div class="flex gap-0.5">${stars}</div>
                            </div>`;
                }
            },
            { 
               data: 'Comment',
               render: function(data) {
                 if(!data) return '<span class="text-slate-300 italic">No comment</span>';
                 return `<div class="max-w-xs text-slate-600 leading-relaxed">${data}</div>`;
               } 
            },
            { 
               data: 'FormattedDate',
               render: function(data, type, row) {
                 if(type === 'sort') return row.SubmittedAt; // Sort by actual DB datetime
                 // Clean Display
                 return `<span class="whitespace-nowrap font-medium text-slate-500">${data}</span>`;
               }
            }
        ],
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        order: [[4, 'desc']],
        dom: 'Bfrtip',
        buttons: [
            { extend: 'copy', className: 'dt-button', text: '<i class="fa-regular fa-copy"></i> Copy' },
            { extend: 'csv', className: 'dt-button', text: '<i class="fa-solid fa-file-csv"></i> CSV' },
            { extend: 'excel', className: 'dt-button', text: '<i class="fa-regular fa-file-excel"></i> Excel' },
            { extend: 'pdf', className: 'dt-button', text: '<i class="fa-regular fa-file-pdf"></i> PDF' },
            { extend: 'print', className: 'dt-button', text: '<i class="fa-solid fa-print"></i> Print' }
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search feedback...",
            emptyTable: "No feedback records found."
        },
        initComplete: function () {
            const api = this.api();

            // Populate Facility Dropdown
            api.column(1).data().unique().sort().each(function (d) {
                $('#facilityFilter').append(`<option value="${d}">${d}</option>`);
            });
        }
      });

      // Filter logic for Facility (Exact match)
      $('#facilityFilter').on('change', function () {
        table.column(1).search(this.value ? '^'+this.value+'$' : '', true, false).draw();
      });

      // Filter logic for Date (Custom Search)
      $('#dateFilter').on('change', function () {
        table.draw();
      });

      // Filter logic for Rating (Exact match)
      $('#ratingFilter').on('change', function () {
        table.column(2).search(this.value ? '^'+this.value+'$' : '', true, false).draw();
      });
    });
  </script>
<?php include 'includes/footer.php'; ?>
<script src="../assets/js/idle_timer.js.php"></script>
</body>
</html>
