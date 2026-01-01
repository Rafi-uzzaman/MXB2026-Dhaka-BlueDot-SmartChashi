<?php
// Simple test endpoint - always outputs JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$response = [
    'success' => true,
    'message' => 'Test endpoint working',
    'php_version' => PHP_VERSION,
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
    'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'Unknown',
];

// Check if session works
$response['session_status_before'] = session_status();

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$response['session_status_after'] = session_status();
$response['session_id'] = session_id() ?: 'No session';
$response['session_save_path'] = session_save_path() ?: 'Default';

// Check cookies
$response['cookies'] = !empty($_COOKIE) ? array_keys($_COOKIE) : 'No cookies received';

// Check if config exists and loads
$configPath = __DIR__ . '/../config/config.php';
$response['config_path'] = $configPath;
$response['config_exists'] = file_exists($configPath);

if (file_exists($configPath)) {
    try {
        require_once $configPath;
        $response['config_loaded'] = true;
        $response['base_url'] = isset($base_url) ? $base_url : 'Not set';
        
        // Check functions exist
        $response['isLoggedIn_exists'] = function_exists('isLoggedIn');
        
        if (function_exists('isLoggedIn')) {
            $response['is_logged_in'] = isLoggedIn();
            
            if (isLoggedIn()) {
                $response['user_id'] = $_SESSION['user_id'] ?? 'Not in session';
            }
        }
        
        // Check session data
        $response['session_keys'] = !empty($_SESSION) ? array_keys($_SESSION) : 'Empty session';
        
    } catch (Exception $e) {
        $response['config_error'] = $e->getMessage();
    } catch (Error $e) {
        $response['config_fatal_error'] = $e->getMessage();
    }
} else {
    $response['config_error'] = 'Config file does not exist at: ' . $configPath;
}

echo json_encode($response, JSON_PRETTY_PRINT);
