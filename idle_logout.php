<?php
let idleSeconds = 0;

// 2 minutes total idle time
const IDLE_LIMIT = 30 * 60; // 1800 seconds

// Warning at 1 minute (1 minute before logout)
const WARNING_TIME = 29 * 60; // 1740 seconds

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

    // Auto logout at 2 minutes
    if (idleSeconds >= IDLE_LIMIT) {
        window.location.href = "../logout.php";
    }
}, 1000);
