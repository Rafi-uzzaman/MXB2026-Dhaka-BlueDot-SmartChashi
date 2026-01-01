<?php
/**
 * Admin Logout Page
 * Handles admin logout and session cleanup
 */

require_once __DIR__ . '/../../config/config.php';

// Update admin session if exists
if (isset($_SESSION['admin_session_id'])) {
    $db = new Database();
    try {
        $db->query("UPDATE admin_sessions SET is_active = 0, terminated_reason = 'user_logout' WHERE session_id = ?")
           ->bind(1, $_SESSION['admin_session_id'])
           ->execute();
    } catch (Exception $e) {
        // Continue with logout even if database update fails
    }
}

// Clear all session variables
$_SESSION = array();

// Destroy session cookie
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

// Destroy the session
session_destroy();

// Redirect to homepage
header('Location: ' . $base_url);
exit;
