<?php
require_once 'config/auth.php';
require_once 'config/functions.php';

// Set no-cache headers
setNoCacheHeaders();

// Destroy session completely
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Redirect to login with success message
$_SESSION['success'] = 'You have been logged out successfully.';
header('Location: /medidonate/login.php');
exit;