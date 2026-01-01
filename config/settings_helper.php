<?php
/**
 * Settings Helper Functions
 * Easy access to system settings throughout the application
 */

/**
 * Get a single setting value
 * 
 * @param string $key Setting key
 * @param mixed $default Default value if setting not found
 * @return mixed Setting value or default
 */
function getSetting($key, $default = null) {
    static $settings = null;
    
    // Load settings once and cache
    if ($settings === null) {
        $settings = getAllSettings();
    }
    
    return $settings[$key] ?? $default;
}

/**
 * Get all settings as key-value array
 * 
 * @return array All settings
 */
function getAllSettings() {
    global $db;
    
    try {
        if (!$db) {
            $db = new Database();
        }
        
        $db->query("SELECT setting_key, setting_value FROM admin_settings");
        $db->execute();
        $rows = $db->resultSet() ?? [];
        
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
        
    } catch (Exception $e) {
        error_log("Error loading settings: " . $e->getMessage());
        return [];
    }
}

/**
 * Update a setting value
 * 
 * @param string $key Setting key
 * @param mixed $value Setting value
 * @param int $userId User making the change
 * @return bool Success
 */
function updateSetting($key, $value, $userId = null) {
    global $db;
    
    try {
        if (!$db) {
            $db = new Database();
        }
        
        // Check if exists
        $db->query("SELECT setting_id FROM admin_settings WHERE setting_key = :key");
        $db->bind(':key', $key);
        $existing = $db->single();
        
        if ($existing) {
            // Update
            $db->query("
                UPDATE admin_settings 
                SET setting_value = :value, updated_by = :user_id, updated_at = NOW() 
                WHERE setting_key = :key
            ");
            $db->bind(':value', $value);
            $db->bind(':user_id', $userId);
            $db->bind(':key', $key);
            $db->execute();
        } else {
            // Insert new
            $group = explode('_', $key)[0] ?? 'general';
            
            $db->query("
                INSERT INTO admin_settings 
                (setting_key, setting_value, setting_group, updated_by, created_at, updated_at) 
                VALUES 
                (:key, :value, :group, :user_id, NOW(), NOW())
            ");
            $db->bind(':key', $key);
            $db->bind(':value', $value);
            $db->bind(':group', $group);
            $db->bind(':user_id', $userId);
            $db->execute();
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error updating setting: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if site is in maintenance mode
 * 
 * @return bool
 */
function isMaintenanceMode() {
    return getSetting('maintenance_mode', '0') === '1';
}

/**
 * Check if current IP is allowed during maintenance
 * 
 * @return bool
 */
function isAllowedDuringMaintenance() {
    $allowedIps = getSetting('maintenance_allowed_ips', '');
    $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
    
    if (empty($allowedIps)) {
        return false;
    }
    
    $allowedList = array_map('trim', explode("\n", $allowedIps));
    return in_array($currentIp, $allowedList);
}

/**
 * Get site name
 * 
 * @return string
 */
function getSiteName() {
    return getSetting('site_name', 'SmartCashi');
}

/**
 * Get site timezone
 * 
 * @return string
 */
function getSiteTimezone() {
    return getSetting('timezone', 'Asia/Dhaka');
}

/**
 * Check if 2FA is required
 * 
 * @return bool
 */
function is2FARequired() {
    return getSetting('require_2fa', '0') === '1';
}

/**
 * Get session timeout in minutes
 * 
 * @return int
 */
function getSessionTimeout() {
    return (int) getSetting('session_timeout', 30);
}

/**
 * Get max failed login attempts
 * 
 * @return int
 */
function getMaxFailedLogins() {
    return (int) getSetting('max_failed_logins', 5);
}

/**
 * Get password minimum length
 * 
 * @return int
 */
function getPasswordMinLength() {
    return (int) getSetting('password_min_length', 8);
}

/**
 * Check if mixed case is required in passwords
 * 
 * @return bool
 */
function isPasswordMixedCaseRequired() {
    return getSetting('require_mixed_case', '1') === '1';
}

/**
 * Check if numbers are required in passwords
 * 
 * @return bool
 */
function isPasswordNumbersRequired() {
    return getSetting('require_numbers', '1') === '1';
}

/**
 * Validate password against policy
 * 
 * @param string $password Password to validate
 * @return array ['valid' => bool, 'errors' => array]
 */
function validatePassword($password) {
    $errors = [];
    
    $minLength = getPasswordMinLength();
    if (strlen($password) < $minLength) {
        $errors[] = "Password must be at least $minLength characters";
    }
    
    if (isPasswordMixedCaseRequired()) {
        if (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain both uppercase and lowercase letters";
        }
    }
    
    if (isPasswordNumbersRequired()) {
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Get items per page for pagination
 * 
 * @return int
 */
function getItemsPerPage() {
    return (int) getSetting('items_per_page', 20);
}
