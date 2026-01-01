<?php
/**
 * Central Configuration File
 * UKM Sport Facilities Booking System
 */

// Session timeout in seconds (10 minutes)
define('SESSION_TIMEOUT_SECONDS', 600);

// Session timeout in milliseconds for JavaScript (10 minutes)
define('SESSION_TIMEOUT_MS', 600000);

// Session refresh interval in milliseconds (5 minutes)
// Should be less than SESSION_TIMEOUT_MS to keep session alive
define('SESSION_REFRESH_INTERVAL_MS', 300000);
?>
