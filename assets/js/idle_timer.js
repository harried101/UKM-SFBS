// Time in milliseconds (10 minutes = 600,000ms)
const idleTimeLimit = 10000; 
let idleTimer;

function resetTimer() {
    // This clears the previous countdown
    clearTimeout(idleTimer);
    
    // This starts a brand new 10-minute countdown
    idleTimer = setTimeout(() => {
        // This only happens if 10 minutes pass with NO movement
        window.location.href = '../logout.php';
    }, idleTimeLimit);
}

// List of activities that "Reset" the timer
window.onload = resetTimer;       // Page loads
window.onmousemove = resetTimer;  // Mouse moves
window.onmousedown = resetTimer;  // Mouse clicks
window.onkeypress = resetTimer;   // Typing
window.onscroll = resetTimer;     // Scrolling with bar or trackpad <--- NEW
window.onwheel = resetTimer;      // Mouse wheel usage <--- NEW
window.ontouchstart = resetTimer; // Phone screen touch