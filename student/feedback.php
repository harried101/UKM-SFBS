<?php
require_once 'includes/student_auth.php';


/* =========================
   VALIDATE BOOKING ID
========================= */
$bookingId = $_GET['booking_id'] ?? null;

if (!$bookingId || !is_numeric($bookingId)) {
    header("Location: dashboard.php?error=invalid_booking");
    exit();
}

$safeBookingId = htmlspecialchars($bookingId);

/* =========================
   DISPLAY DATA
========================= */
$facilityName = htmlspecialchars($_GET['facility_name'] ?? 'Facility Name Missing');
$date = htmlspecialchars($_GET['date'] ?? date('d M Y'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Give Feedback - UKM Sports</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        .star-rating i {
            cursor: pointer;
            transition: transform 0.2s, color 0.2s;
        }
        .star-rating i:hover {
            transform: scale(1.2);
        }
        .star-rating i.active {
            color: #f59e0b;
        }
        .star-rating i.inactive {
            color: #cbd5e1;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-6">

<div class="max-w-lg w-full bg-white rounded-3xl shadow-2xl p-8 border border-slate-100 relative overflow-hidden">
    <div class="absolute top-0 left-0 w-full h-2 bg-[#8a0d19]"></div>

    <div class="text-center mb-8">
        <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4 text-[#8a0d19]">
            <i class="fa-regular fa-comment-dots text-3xl"></i>
        </div>

        <h1 class="text-2xl font-bold text-slate-800">Rate Your Session</h1>
        <p class="text-slate-500 text-sm mt-2">
            How was your experience at <br>
            <span class="font-bold text-[#8a0d19]"><?= $facilityName ?></span>
            on <?= $date ?>?
        </p>
    </div>

    <form action="submit_feedback.php" method="POST" id="feedbackForm">
        <input type="hidden" name="booking_id" value="<?= $safeBookingId ?>">
        <input type="hidden" name="rating" id="ratingValue" value="0">

        <!-- STAR RATING -->
        <div id="starContainer" class="flex justify-center gap-4 mb-8 star-rating">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="fa-solid fa-star text-4xl inactive" data-index="<?= $i ?>"></i>
            <?php endfor; ?>
        </div>

        <p id="ratingText"
           class="text-center text-sm font-bold text-[#8a0d19] h-5 mb-6 opacity-0 transition-opacity">
        </p>

        <!-- REASON CATEGORY (Hidden by default) -->
        <div id="categorySection" class="mb-6 hidden transition-all duration-300">
            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">
                What was the main issue? <span class="text-red-500">*</span>
            </label>
            <select name="category" id="categoryInput" class="w-full p-4 bg-red-50 border border-red-100 rounded-xl focus:outline-none focus:ring-1 focus:ring-red-500 text-sm font-bold text-[#8a0d19]">
                <option value="" disabled selected>Select a reason...</option>
                <option value="Broken/Damaged Equipment">Broken/Damaged Equipment</option>
                <option value="Lighting/Electrical Issue">Lighting/Electrical Issue</option>
                <option value="Dirty/Cleaning Needed">Dirty/Cleaning Needed</option>
                <option value="Crowd/Booking Conflict">Crowd/Booking Conflict</option>
                <option value="Other/Personal Experience">Other/Personal Experience</option>
            </select>
        </div>

       <!-- COMMENT -->
<div class="mb-6">
    <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">
        Additional Comments
    </label>
    <textarea
        id="comment"
        name="comment"
        rows="4"
        class="w-full p-4 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-[#8a0d19] focus:ring-1 focus:ring-[#8a0d19] text-sm resize-none"
        placeholder="The facility was clean..."></textarea>
</div>


        <!-- ACTIONS -->
        <div class="flex gap-4">
            <a href="dashboard.php"
               class="flex-1 py-3.5 text-center text-slate-500 font-bold hover:bg-slate-50 rounded-xl transition text-sm">
                Skip
            </a>

            <button type="submit"
                    class="flex-1 bg-[#8a0d19] hover:bg-[#6d0a13] text-white py-3.5 rounded-xl font-bold shadow-lg shadow-red-900/20 transition transform active:scale-95 text-sm">
                Submit Feedback
            </button>
        </div>
    </form>
</div>

<script>
    const stars = document.querySelectorAll('#starContainer i');
    const ratingInput = document.getElementById('ratingValue');
    const ratingText = document.getElementById('ratingText');
    const categorySection = document.getElementById('categorySection');
    const categoryInput = document.getElementById('categoryInput');

    const messages = ["Terrible", "Bad", "Okay", "Good", "Excellent!"];

    stars.forEach(star => {
        star.addEventListener('mouseenter', () => {
            const value = parseInt(star.dataset.index);
            highlight(value);
            showText(value);
        });

        star.addEventListener('click', () => {
            const value = parseInt(star.dataset.index);
            ratingInput.value = value;
            highlight(value);
            toggleCategory(value);
        });
    });

    document.getElementById('starContainer').addEventListener('mouseleave', () => {
        const current = parseInt(ratingInput.value);
        highlight(current);
        current ? showText(current) : ratingText.style.opacity = '0';
    });

    function highlight(count) {
        stars.forEach(star => {
            star.classList.toggle('active', star.dataset.index <= count);
            star.classList.toggle('inactive', star.dataset.index > count);
        });
    }

    function showText(value) {
        ratingText.textContent = messages[value - 1];
        ratingText.style.opacity = '1';
    }

    function toggleCategory(rating) {
    const commentInput = document.getElementById('comment');
    const categorySection = document.getElementById('categorySection');
    const categoryInput = document.getElementById('category');

    if (rating <= 3) {
        // Show category & force comment
        categorySection.classList.remove('hidden');
        categorySection.classList.add('fade-in');

        commentInput.setAttribute('required', 'required');
        categoryInput.setAttribute('required', 'required');
    } else {
        // Hide & reset
        categorySection.classList.add('hidden');
        categorySection.classList.remove('fade-in');

        commentInput.removeAttribute('required');
        categoryInput.removeAttribute('required');

        categoryInput.value = '';
        commentInput.value = '';
    }
}

</script>
<script src="../assets/js/idle_timer.js.php"></script>
</body>
</html>
