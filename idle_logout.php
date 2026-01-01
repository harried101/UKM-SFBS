<?php
let idleSeconds = 0;

// 10 minutes total idle time
const IDLE_LIMIT = 10 * 60; // 600 seconds (10 minutes)

// Warning at 1 minute (1 minute before logout)
const WARNING_TIME = 9 * 60; // 540 seconds (9 minutes - 1 minute before logout)

let warningShown = false;

// Reset idle timer on user activity
function resetIdleTimer() {
    idleSeconds = 0;
    warningShown = false;
}

// Detect user activity
['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach(event => {
    document.addEventListener(event, resetIdleTimer);
});

// Count idle time every second
setInterval(() => {
    idleSeconds++;

    // Show warning popup at 1 minute
    if (idleSeconds >= WARNING_TIME && !warningShown) {
        alert("You will be logged out in 1 minute due to inactivity.");
        warningShown = true;
    }

    // Auto logout at 10 minutes
    if (idleSeconds >= IDLE_LIMIT) {
        window.location.href = "../logout.php";
    }
}, 1000);
