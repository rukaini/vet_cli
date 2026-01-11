<?php
session_start();

// Clear session array
$_SESSION = [];

// Destroy session
session_destroy();

// Remove session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Redirect to MAIN login page
header("Location: http://10.48.74.199:81/vetcli/frontend/userlogin.php");
exit;
?>