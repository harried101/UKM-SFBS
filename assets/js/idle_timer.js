const idleTimeLimit = 10000; // 10 seconds for testing
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
    // During your 10-second test, we sync every 2 seconds
    if (now - lastSyncTime > 2000) { 
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