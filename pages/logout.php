<?php
require_once __DIR__ . '/../config/config.php';

// Clear all session variables
$_SESSION = array();

// Clear session cookie
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

// Destroy session
session_destroy();

// Redirect to home
header('Location: ' . $base_url);
exit;
?>
