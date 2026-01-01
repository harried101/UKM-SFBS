<?php
session_start();
// This line updates the "last activity" timestamp on the server
$_SESSION['last_activity'] = time();
echo "Server clock reset!";
?>
