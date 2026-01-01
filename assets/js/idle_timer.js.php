<?php
/**
 * Client-side idle timeout configuration
 * Dynamically generated from central config.php
 */
header('Content-Type: application/javascript');
require_once __DIR__ . '/../../includes/config.php';
?>
const idleTimeLimit = <?php echo SESSION_TIMEOUT_MS; ?>; // <?php echo (SESSION_TIMEOUT_SECONDS / 60); ?> minutes
const syncInterval = <?php echo SESSION_REFRESH_INTERVAL_MS; ?>; // <?php echo (SESSION_REFRESH_INTERVAL_MS / 60000); ?> minutes

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
