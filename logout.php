<?php
require_once 'config.php';

// Unset session array
$_SESSION = [];

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy all session data
session_destroy();

// Redirect to login page
header('Location: /login.php');
exit();
