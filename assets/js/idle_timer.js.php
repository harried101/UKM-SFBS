<?php
/**
 * Client-side idle timeout configuration
 * Dynamically generated from central config.php
 */
header('Content-Type: application/javascript');
require_once __DIR__ . '/../../includes/config.php';
?>
const idleTimeLimit = <?php echo defined('SESSION_TIMEOUT_MS') ? SESSION_TIMEOUT_MS : 600000; ?>; // Default 10 mins if config fails
const syncInterval = <?php echo defined('SESSION_REFRESH_INTERVAL_MS') ? SESSION_REFRESH_INTERVAL_MS : 300000; ?>; // Default 5 mins

let idleTimer;
let lastSyncTime = 0;

function resetTimer() {
    clearTimeout(idleTimer);

    // 1. Reset the Browser Timer
    idleTimer = setTimeout(() => {
        window.location.href = '../logout.php';
    }, idleTimeLimit);

    // 2. Sync with the Server (The "Heartbeat")
    let now = Date.now();
    if (now - lastSyncTime > syncInterval) {
        fetch('../refresh_session.php')
            .then(response => console.log("Server session refreshed!"))
            .catch(err => console.log("Sync failed: ", err));
        lastSyncTime = now;
    }
}

// All your listeners (mouse, scroll, etc.)
window.onload = resetTimer;
window.onmousemove = resetTimer;
window.onmousedown = resetTimer;
window.onkeypress = resetTimer;
window.onscroll = resetTimer;
window.onwheel = resetTimer;
window.ontouchstart = resetTimer;
