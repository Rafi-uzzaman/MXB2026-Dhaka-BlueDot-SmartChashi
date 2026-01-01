<?php
/**
 * Settings AJAX Handler
 * Handles all admin settings operations
 */

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';

// Helper function for JSON responses (define early)
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Error handling with logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../error.log');

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error: $errstr in $errfile on line $errline");
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

set_exception_handler(function($exception) {
    error_log("Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
    jsonResponse(['success' => false, 'message' => 'Server error: ' . $exception->getMessage()], 500);
});

header('Content-Type: application/json');

// Authentication check
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

// Check if user is admin
if (isset($_SESSION['user_id'])) {
    $db = new Database();
    $user = $db->single("SELECT role FROM users WHERE user_id = ?", [$_SESSION['user_id']]);
    if (!$user || $user['role'] !== 'admin') {
        jsonResponse(['success' => false, 'message' => 'Unauthorized - Not admin'], 403);
    }
    $adminId = $_SESSION['user_id'];
} else {
    $adminId = $_SESSION['admin_id'];
}

$db = new Database();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// CSRF token validation for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        jsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
    }
}

// Main action router
switch ($action) {
    case 'get_settings':
        getSettings();
        break;
        
    case 'update_settings':
        updateSettings();
        break;
        
    case 'clear_cache':
        clearCache();
        break;
        
    case 'clear_sessions':
        clearSessions();
        break;
        
    case 'clear_logs':
        clearLogs();
        break;
        
    case 'test_maintenance':
        testMaintenance();
        break;
        
    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
}

/**
 * Get settings by group or all settings
 */
function getSettings() {
    global $db;
    
    $group = $_GET['group'] ?? null;
    
    try {
        if ($group) {
            $db->query("SELECT * FROM admin_settings WHERE setting_group = :group ORDER BY setting_key");
            $db->bind(':group', $group);
        } else {
            $db->query("SELECT * FROM admin_settings ORDER BY setting_group, setting_key");
        }
        
        $settings = $db->resultSet() ?? [];
        
        // Convert to key-value pairs
        $settingsArray = [];
        foreach ($settings as $setting) {
            $settingsArray[$setting['setting_key']] = $setting['setting_value'];
        }
        
        jsonResponse([
            'success' => true,
            'settings' => $settingsArray
        ]);
        
    } catch (Exception $e) {
        jsonResponse([
            'success' => false,
            'message' => 'Failed to load settings: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Update settings
 */
function updateSettings() {
    global $db, $adminId;
    
    // Get settings from POST data (can be JSON or individual fields)
    $settingsJson = $_POST['settings'] ?? null;
    $settings = [];
    
    if ($settingsJson) {
        // Settings sent as JSON string
        $settings = json_decode($settingsJson, true);
        if (!$settings) {
            jsonResponse(['success' => false, 'message' => 'Invalid settings format'], 400);
        }
    } else {
        // Settings sent as individual POST fields
        foreach ($_POST as $key => $value) {
            if ($key !== 'action' && $key !== 'csrf_token') {
                $settings[$key] = $value;
            }
        }
    }
    
    if (empty($settings) || !is_array($settings)) {
        jsonResponse(['success' => false, 'message' => 'No settings provided'], 400);
    }
    
    try {
        $updated = 0;
        $inserted = 0;
        
        foreach ($settings as $key => $value) {
            // Check if setting exists
            $db->query("SELECT setting_id FROM admin_settings WHERE setting_key = :key");
            $db->bind(':key', $key);
            $existing = $db->single();
            
            if ($existing) {
                // Update existing setting
                $db->query("
                    UPDATE admin_settings 
                    SET setting_value = :value, updated_by = :admin_id, updated_at = NOW() 
                    WHERE setting_key = :key
                ");
                $db->bind(':value', $value);
                $db->bind(':admin_id', $adminId);
                $db->bind(':key', $key);
                $db->execute();
                $updated++;
            } else {
                // Insert new setting (determine group from key prefix)
                $group = explode('_', $key)[0];
                if (!$group) $group = 'general';
                
                $db->query("
                    INSERT INTO admin_settings 
                    (setting_key, setting_value, setting_group, updated_by, created_at, updated_at) 
                    VALUES 
                    (:key, :value, :group, :admin_id, NOW(), NOW())
                ");
                $db->bind(':key', $key);
                $db->bind(':value', $value);
                $db->bind(':group', $group);
                $db->bind(':admin_id', $adminId);
                $db->execute();
                $inserted++;
            }
        }
        
        $total = $updated + $inserted;
        jsonResponse([
            'success' => true,
            'message' => "Successfully saved $total setting(s)",
            'count' => $total,
            'updated' => $updated,
            'inserted' => $inserted
        ]);
        
    } catch (Exception $e) {
        error_log("Settings update error: " . $e->getMessage());
        jsonResponse([
            'success' => false,
            'message' => 'Failed to update settings: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Clear cache
 */
function clearCache() {
    try {
        $cacheDir = __DIR__ . '/../../cache/';
        $cleared = 0;
        
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                    $cleared++;
                }
            }
        }
        
        jsonResponse([
            'success' => true,
            'message' => "Cleared $cleared cache file(s)"
        ]);
        
    } catch (Exception $e) {
        jsonResponse([
            'success' => false,
            'message' => 'Failed to clear cache: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Clear old sessions
 */
function clearSessions() {
    global $db;
    
    try {
        // Clear expired sessions from database
        $db->query("DELETE FROM user_sessions WHERE expires_at < NOW()");
        $db->execute();
        $count = $db->rowCount();
        
        // Also clear PHP session files older than 24 hours
        $sessionPath = session_save_path();
        if ($sessionPath && is_dir($sessionPath)) {
            $files = glob($sessionPath . '/sess_*');
            $deleted = 0;
            $oneDayAgo = time() - (24 * 60 * 60);
            
            foreach ($files as $file) {
                if (filemtime($file) < $oneDayAgo) {
                    unlink($file);
                    $deleted++;
                }
            }
            $count += $deleted;
        }
        
        jsonResponse([
            'success' => true,
            'message' => "Cleared $count expired session(s)"
        ]);
        
    } catch (Exception $e) {
        jsonResponse([
            'success' => false,
            'message' => 'Failed to clear sessions: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Clear old logs
 */
function clearLogs() {
    global $db;
    
    try {
        $deleted = 0;
        
        // Clear old error logs from database
        try {
            $db->query("DELETE FROM error_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
            $db->execute();
            $deleted += $db->rowCount();
        } catch (Exception $e) {
            // Table might not exist
        }
        
        // Clear old activity logs
        try {
            $db->query("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
            $db->execute();
            $deleted += $db->rowCount();
        } catch (Exception $e) {
            // Table might not exist
        }
        
        // Clear old login attempts
        try {
            $db->query("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
            $db->execute();
            $deleted += $db->rowCount();
        } catch (Exception $e) {
            // Table might not exist
        }
        
        jsonResponse([
            'success' => true,
            'message' => "Cleared $deleted old log record(s)"
        ]);
        
    } catch (Exception $e) {
        jsonResponse([
            'success' => false,
            'message' => 'Failed to clear logs: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Test maintenance mode
 */
function testMaintenance() {
    jsonResponse([
        'success' => true,
        'message' => 'Maintenance mode test completed',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
    ]);
}
