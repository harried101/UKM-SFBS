<?php
session_start();

// Update activity time
$_SESSION['last_activity'] = time();

// No output needed
http_response_code(204);
exit();
