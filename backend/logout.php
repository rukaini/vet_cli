<?php
session_start();

$_SESSION = []; // Clear all session data

session_destroy(); // Destroy the session

header("Location: http://10.48.74.199:81/vetcli/frontend/userlogin.php");  
exit();
?>