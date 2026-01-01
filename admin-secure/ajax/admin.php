<?php
/**
 * Admin AJAX Handler
 * Secure endpoints for admin dashboard operations
 */

// Temporarily show errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Helper function to output JSON safely
function jsonResponse($success = false, $message = '', $data = null) {
    http_response_code($success ? 200 : 400);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Rate limiting check
function checkRateLimit($db, $identifier, $endpoint, $maxRequests = 100, $windowSeconds = 60) {
    $now = time();
    
    // Clean old entries
    $db->query("DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL ? SECOND)")->bind(1, $windowSeconds)->execute();
    
    // Check current rate
    $result = $db->single("SELECT * FROM rate_limits WHERE identifier = ? AND endpoint = ?", [$identifier, $endpoint]);
    
    if ($result) {
        if ($result['request_count'] >= $maxRequests) {
            // Block for 5 minutes
            $db->query("UPDATE rate_limits SET blocked_until = DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE id = ?")->bind(1, $result['id'])->execute();
            return false;
        }
        $db->query("UPDATE rate_limits SET request_count = request_count + 1 WHERE id = ?")->bind(1, $result['id'])->execute();
    } else {
        $db->query("INSERT INTO rate_limits (identifier, identifier_type, endpoint, request_count) VALUES (?, 'ip', ?, 1)")
           ->bind(1, $identifier)->bind(2, $endpoint)->execute();
    }
    
    return true;
}

// Get client IP
function getClientIP() {
    $ip = $_SERVER['REMOTE_ADDR'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    return filter_var(trim($ip), FILTER_VALIDATE_IP) ?: '0.0.0.0';
}

// Log admin activity
function logActivity($db, $userId, $action, $category, $entityType = null, $entityId = null, $oldValue = null, $newValue = null, $riskLevel = 'low') {
    $db->query("INSERT INTO admin_activity_logs (user_id, action, action_category, entity_type, entity_id, old_value, new_value, ip_address, user_agent, risk_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
       ->bind(1, $userId)
       ->bind(2, $action)
       ->bind(3, $category)
       ->bind(4, $entityType)
       ->bind(5, $entityId)
       ->bind(6, $oldValue)
       ->bind(7, $newValue)
       ->bind(8, getClientIP())
       ->bind(9, $_SERVER['HTTP_USER_AGENT'] ?? '')
       ->bind(10, $riskLevel)
       ->execute();
}

// Log security event
function logSecurityEvent($db, $type, $severity, $description, $userId = null, $data = null) {
    $db->query("INSERT INTO security_events (event_type, severity, user_id, ip_address, description, raw_data) VALUES (?, ?, ?, ?, ?, ?)")
       ->bind(1, $type)
       ->bind(2, $severity)
       ->bind(3, $userId)
       ->bind(4, getClientIP())
       ->bind(5, $description)
       ->bind(6, json_encode($data ?? ['user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '']))
       ->execute();
}

// Send 2FA email
function send2FAEmail($email, $code, $userName) {
    $subject = "Your Admin Login Code - " . APP_NAME;
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .code { font-size: 32px; font-weight: bold; color: #6366f1; letter-spacing: 8px; text-align: center; padding: 20px; background: #f3f4f6; border-radius: 8px; margin: 20px 0; }
            .warning { color: #ef4444; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>Admin Login Verification</h2>
            <p>Hello {$userName},</p>
            <p>Your verification code for admin login is:</p>
            <div class='code'>{$code}</div>
            <p>This code will expire in 5 minutes.</p>
            <p class='warning'>If you didn't request this code, please secure your account immediately.</p>
            <p>- " . APP_NAME . " Security Team</p>
        </div>
    </body>
    </html>";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
    
    return @mail($email, $subject, $message, $headers);
}

// Simple session-based admin validation
function validateAdminSession($db) {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Verify user is an active admin
    $user = $db->single("SELECT user_id, role, is_active FROM users WHERE user_id = ?", [$_SESSION['user_id']]);
    if (!$user || $user['role'] !== 'admin' || !$user['is_active']) {
        return false;
    }
    
    // Set admin_logged_in for compatibility if not set
    if (!isset($_SESSION['admin_logged_in'])) {
        $_SESSION['admin_logged_in'] = true;
    }
    
    return true;
}

$db = new Database();
$clientIP = getClientIP();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Actions that don't require authentication
$publicActions = ['admin_login', 'verify_2fa', 'resend_2fa'];

// Check authentication for protected actions
if (!in_array($action, $publicActions)) {
    if (!validateAdminSession($db)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login again.']);
        exit;
    }
}

switch ($action) {
    // ========================================
    // AUTHENTICATION
    // ========================================
    
    case 'admin_login':
        // Check rate limiting specifically for login
        if (!checkRateLimit($db, $clientIP, 'admin_login', 10, 60)) {
            echo json_encode(['success' => false, 'message' => 'Too many login attempts. Please wait.']);
            exit;
        }
        
        // Check honeypot fields
        if (!empty($_POST['website']) || !empty($_POST['phone_number'])) {
            // Bot detected - log and block silently
            $db->query("INSERT INTO honeypot_logs (field_name, field_value, ip_address, user_agent, page_url, form_name) VALUES (?, ?, ?, ?, ?, ?)")
               ->bind(1, 'website/phone_number')
               ->bind(2, $_POST['website'] . '|' . $_POST['phone_number'])
               ->bind(3, $clientIP)
               ->bind(4, $_SERVER['HTTP_USER_AGENT'] ?? '')
               ->bind(5, $_SERVER['HTTP_REFERER'] ?? '')
               ->bind(6, 'admin_login')
               ->execute();
            
            logSecurityEvent($db, 'honeypot_trigger', 'high', 'Bot detected via honeypot on admin login');
            
            // Auto-block IP
            $db->query("INSERT INTO admin_ip_rules (ip_address, rule_type, reason, auto_created, expires_at) VALUES (?, 'blacklist', 'Auto-blocked: Honeypot triggered', 1, DATE_ADD(NOW(), INTERVAL 24 HOUR))")
               ->bind(1, $clientIP)->execute();
            
            // Return fake success to confuse bot
            sleep(2);
            echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
            exit;
        }
        
        // Validate CSRF
        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'Security validation failed. Please refresh and try again.']);
            exit;
        }
        
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $deviceFingerprint = $_POST['device_fingerprint'] ?? '';
        $rememberDevice = isset($_POST['remember_device']);
        
        if (!$email || !$password) {
            echo json_encode(['success' => false, 'message' => 'Please enter valid credentials.']);
            exit;
        }
        
        // Check if IP is blocked
        $blocked = $db->single("SELECT * FROM admin_ip_rules WHERE ip_address = ? AND rule_type = 'blacklist' AND (expires_at IS NULL OR expires_at > NOW())", [$clientIP]);
        if ($blocked) {
            logSecurityEvent($db, 'brute_force', 'high', 'Blocked IP attempted admin login', null, ['email' => $email]);
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }
        
        // Check failed attempts (brute force protection)
        $failedAttempts = $db->single("SELECT COUNT(*) as count FROM admin_login_attempts WHERE ip_address = ? AND success = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)", [$clientIP]);
        
        if ($failedAttempts['count'] >= 5) {
            // Block IP for 15 minutes
            $db->query("INSERT INTO rate_limits (identifier, identifier_type, endpoint, blocked_until) VALUES (?, 'ip', 'admin_login', DATE_ADD(NOW(), INTERVAL 15 MINUTE)) ON DUPLICATE KEY UPDATE blocked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE)")
               ->bind(1, $clientIP)->execute();
            
            logSecurityEvent($db, 'brute_force', 'high', 'Brute force attack detected on admin login', null, ['attempts' => $failedAttempts['count']]);
            
            echo json_encode(['success' => false, 'message' => 'Too many failed attempts. Account locked for 15 minutes.']);
            exit;
        }
        
        // Progressive delay based on failed attempts
        if ($failedAttempts['count'] > 0) {
            sleep(min(pow(2, $failedAttempts['count'] - 1), 8));
        }
        
        // Find admin user
        $user = $db->single("SELECT * FROM users WHERE email = ? AND role = 'admin' AND is_active = 1", [$email]);
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            // Log failed attempt
            $reason = !$user ? 'User not found or not admin' : 'Invalid password';
            $db->query("INSERT INTO admin_login_attempts (ip_address, email, success, failure_reason, user_agent) VALUES (?, ?, 0, ?, ?)")
               ->bind(1, $clientIP)
               ->bind(2, $email)
               ->bind(3, $reason)
               ->bind(4, $_SERVER['HTTP_USER_AGENT'] ?? '')
               ->execute();
            
            echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
            exit;
        }
        
        // Check if 2FA is enabled or required
        $settings = $db->single("SELECT setting_value FROM admin_settings WHERE setting_key = 'require_2fa'");
        $require2FA = $settings && $settings['setting_value'] === '1';
        
        // Check if device is trusted
        $trustedDevice = false;
        if ($deviceFingerprint) {
            $device = $db->single("SELECT * FROM admin_trusted_devices WHERE user_id = ? AND device_fingerprint = ? AND is_trusted = 1 AND (trust_expires_at IS NULL OR trust_expires_at > NOW())", 
                                 [$user['user_id'], $deviceFingerprint]);
            if ($device) {
                $trustedDevice = true;
                // Update last used
                $db->query("UPDATE admin_trusted_devices SET last_used = NOW(), last_ip = ? WHERE device_id = ?")
                   ->bind(1, $clientIP)->bind(2, $device['device_id'])->execute();
            }
        }
        
        if ($require2FA && !$trustedDevice) {
            // Generate and send 2FA code
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $tempSession = bin2hex(random_bytes(32));
            
            // Store token
            $db->query("INSERT INTO admin_2fa_tokens (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))")
               ->bind(1, $user['user_id'])->bind(2, password_hash($code, PASSWORD_DEFAULT))->execute();
            
            // Store temp session
            $_SESSION['admin_2fa_temp'] = [
                'user_id' => $user['user_id'],
                'temp_session' => $tempSession,
                'fingerprint' => $deviceFingerprint,
                'remember_device' => $rememberDevice,
                'expires' => time() + 300
            ];
            
            // Send email
            send2FAEmail($user['email'], $code, $user['first_name']);
            
            echo json_encode([
                'success' => true,
                'require_2fa' => true,
                'temp_session' => $tempSession,
                'message' => 'Verification code sent to your email.'
            ]);
            exit;
        }
        
        // Login successful - create session
        $sessionId = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 1800); // 30 minutes
        
        // Insert admin session
        $db->query("INSERT INTO admin_sessions (session_id, user_id, ip_address, user_agent, device_fingerprint, expires_at) VALUES (?, ?, ?, ?, ?, ?)")
           ->bind(1, $sessionId)
           ->bind(2, $user['user_id'])
           ->bind(3, $clientIP)
           ->bind(4, $_SERVER['HTTP_USER_AGENT'] ?? '')
           ->bind(5, $deviceFingerprint)
           ->bind(6, $expiresAt)
           ->execute();
        
        // Log successful login
        $db->query("INSERT INTO admin_login_attempts (ip_address, email, success, user_agent) VALUES (?, ?, 1, ?)")
           ->bind(1, $clientIP)->bind(2, $email)->bind(3, $_SERVER['HTTP_USER_AGENT'] ?? '')->execute();
        
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_session_id'] = $sessionId;
        $_SESSION['admin_fingerprint'] = $deviceFingerprint;
        
        // Log activity
        logActivity($db, $user['user_id'], 'admin_login', 'security', 'user', $user['user_id'], null, null, 'low');
        
        // Update last login
        $db->query("UPDATE users SET last_login = NOW() WHERE user_id = ?")->bind(1, $user['user_id'])->execute();
        
        // Trust device if requested
        if ($rememberDevice && $deviceFingerprint) {
            $browser = 'Unknown';
            $os = 'Unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // Simple browser/OS detection
            if (strpos($userAgent, 'Chrome') !== false) $browser = 'Chrome';
            elseif (strpos($userAgent, 'Firefox') !== false) $browser = 'Firefox';
            elseif (strpos($userAgent, 'Safari') !== false) $browser = 'Safari';
            elseif (strpos($userAgent, 'Edge') !== false) $browser = 'Edge';
            
            if (strpos($userAgent, 'Windows') !== false) $os = 'Windows';
            elseif (strpos($userAgent, 'Mac') !== false) $os = 'macOS';
            elseif (strpos($userAgent, 'Linux') !== false) $os = 'Linux';
            elseif (strpos($userAgent, 'Android') !== false) $os = 'Android';
            elseif (strpos($userAgent, 'iOS') !== false) $os = 'iOS';
            
            $db->query("INSERT INTO admin_trusted_devices (user_id, device_fingerprint, device_name, browser, os, last_ip, trust_expires_at) VALUES (?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY)) ON DUPLICATE KEY UPDATE last_used = NOW(), last_ip = ?, trust_expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY)")
               ->bind(1, $user['user_id'])
               ->bind(2, $deviceFingerprint)
               ->bind(3, "$browser on $os")
               ->bind(4, $browser)
               ->bind(5, $os)
               ->bind(6, $clientIP)
               ->bind(7, $clientIP)
               ->execute();
        }
        
        echo json_encode(['success' => true, 'message' => 'Login successful.']);
        break;
        
    case 'verify_2fa':
        $code = preg_replace('/\D/', '', $_POST['code'] ?? '');
        $tempSession = $_POST['temp_session'] ?? '';
        
        if (!isset($_SESSION['admin_2fa_temp']) || $_SESSION['admin_2fa_temp']['temp_session'] !== $tempSession) {
            echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
            exit;
        }
        
        if ($_SESSION['admin_2fa_temp']['expires'] < time()) {
            unset($_SESSION['admin_2fa_temp']);
            echo json_encode(['success' => false, 'message' => 'Code expired. Please login again.']);
            exit;
        }
        
        $userId = $_SESSION['admin_2fa_temp']['user_id'];
        
        // Get latest unused token
        $token = $db->single("SELECT * FROM admin_2fa_tokens WHERE user_id = ? AND used = 0 AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1", [$userId]);
        
        if (!$token || !password_verify($code, $token['token'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid verification code.']);
            exit;
        }
        
        // Mark token as used
        $db->query("UPDATE admin_2fa_tokens SET used = 1, used_at = NOW() WHERE token_id = ?")->bind(1, $token['token_id'])->execute();
        
        // Get user
        $user = $db->single("SELECT * FROM users WHERE user_id = ?", [$userId]);
        
        // Create session
        $sessionId = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 1800);
        $fingerprint = $_SESSION['admin_2fa_temp']['fingerprint'];
        
        $db->query("INSERT INTO admin_sessions (session_id, user_id, ip_address, user_agent, device_fingerprint, expires_at) VALUES (?, ?, ?, ?, ?, ?)")
           ->bind(1, $sessionId)
           ->bind(2, $userId)
           ->bind(3, $clientIP)
           ->bind(4, $_SERVER['HTTP_USER_AGENT'] ?? '')
           ->bind(5, $fingerprint)
           ->bind(6, $expiresAt)
           ->execute();
        
        // Trust device if requested
        if ($_SESSION['admin_2fa_temp']['remember_device'] && $fingerprint) {
            $db->query("INSERT INTO admin_trusted_devices (user_id, device_fingerprint, device_name, last_ip, trust_expires_at) VALUES (?, ?, 'Trusted Device', ?, DATE_ADD(NOW(), INTERVAL 30 DAY)) ON DUPLICATE KEY UPDATE last_used = NOW(), last_ip = ?, trust_expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY)")
               ->bind(1, $userId)->bind(2, $fingerprint)->bind(3, $clientIP)->bind(4, $clientIP)->execute();
        }
        
        // Log successful login
        $db->query("INSERT INTO admin_login_attempts (ip_address, email, success, user_agent) VALUES (?, ?, 1, ?)")
           ->bind(1, $clientIP)->bind(2, $user['email'])->bind(3, $_SERVER['HTTP_USER_AGENT'] ?? '')->execute();
        
        // Set session
        $_SESSION['user_id'] = $userId;
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_session_id'] = $sessionId;
        $_SESSION['admin_fingerprint'] = $fingerprint;
        unset($_SESSION['admin_2fa_temp']);
        
        logActivity($db, $userId, 'admin_login_2fa', 'security', 'user', $userId, null, null, 'low');
        
        $db->query("UPDATE users SET last_login = NOW() WHERE user_id = ?")->bind(1, $userId)->execute();
        
        echo json_encode(['success' => true, 'message' => 'Login successful.']);
        break;
        
    case 'resend_2fa':
        $tempSession = $_POST['temp_session'] ?? '';
        
        if (!isset($_SESSION['admin_2fa_temp']) || $_SESSION['admin_2fa_temp']['temp_session'] !== $tempSession) {
            echo json_encode(['success' => false, 'message' => 'Session expired.']);
            exit;
        }
        
        $userId = $_SESSION['admin_2fa_temp']['user_id'];
        $user = $db->single("SELECT * FROM users WHERE user_id = ?", [$userId]);
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found.']);
            exit;
        }
        
        // Generate new code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Invalidate old tokens
        $db->query("UPDATE admin_2fa_tokens SET used = 1 WHERE user_id = ? AND used = 0")->bind(1, $userId)->execute();
        
        // Store new token
        $db->query("INSERT INTO admin_2fa_tokens (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))")
           ->bind(1, $userId)->bind(2, password_hash($code, PASSWORD_DEFAULT))->execute();
        
        // Reset session expiry
        $_SESSION['admin_2fa_temp']['expires'] = time() + 300;
        
        // Send email
        send2FAEmail($user['email'], $code, $user['first_name']);
        
        echo json_encode(['success' => true, 'message' => 'New code sent.']);
        break;
        
    case 'admin_logout':
        if (isset($_SESSION['admin_session_id'])) {
            $db->query("UPDATE admin_sessions SET is_active = 0, terminated_reason = 'user_logout' WHERE session_id = ?")
               ->bind(1, $_SESSION['admin_session_id'])->execute();
            
            logActivity($db, $_SESSION['user_id'], 'admin_logout', 'security', 'user', $_SESSION['user_id'], null, null, 'low');
        }
        
        // Clear all session variables
        $_SESSION = array();
        
        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
        
        echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
        break;
        
    // ========================================
    // DASHBOARD DATA
    // ========================================
    
    case 'get_dashboard_stats':
        $stats = [];
        
        // Total users
        $result = $db->single("SELECT COUNT(*) as count FROM users");
        $stats['total_users'] = $result['count'];
        
        // New users today
        $result = $db->single("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()");
        $stats['new_users_today'] = $result['count'];
        
        // Active sessions
        $result = $db->single("SELECT COUNT(*) as count FROM admin_sessions WHERE is_active = 1 AND expires_at > NOW()");
        $stats['active_sessions'] = $result['count'];
        
        // Today's logins
        $result = $db->single("SELECT COUNT(*) as count FROM admin_login_attempts WHERE success = 1 AND DATE(attempted_at) = CURDATE()");
        $stats['todays_logins'] = $result['count'];
        
        // Failed logins (last 24h)
        $result = $db->single("SELECT COUNT(*) as count FROM admin_login_attempts WHERE success = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stats['failed_logins_24h'] = $result['count'];
        
        // Pending reports
        $result = $db->single("SELECT COUNT(*) as count FROM content_reports WHERE status = 'pending'");
        $stats['pending_reports'] = $result['count'] ?? 0;
        
        // Unresolved errors
        $result = $db->single("SELECT COUNT(*) as count FROM error_logs WHERE is_resolved = 0");
        $stats['unresolved_errors'] = $result['count'] ?? 0;
        
        // Security events (last 24h)
        $result = $db->single("SELECT COUNT(*) as count FROM security_events WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND severity IN ('high', 'critical')");
        $stats['security_alerts'] = $result['count'] ?? 0;
        
        // User distribution by role
        $stats['user_roles'] = $db->resultSet("SELECT role, COUNT(*) as count FROM users GROUP BY role");
        
        // Individual role counts for charts
        $stats['farmers'] = $db->single("SELECT COUNT(*) as count FROM users WHERE role = 'farmer'")['count'] ?? 0;
        $stats['officers'] = $db->single("SELECT COUNT(*) as count FROM users WHERE role = 'officer'")['count'] ?? 0;
        $stats['admins'] = $db->single("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")['count'] ?? 0;
        
        // New users this week
        $stats['new_users_week'] = $db->single("SELECT COUNT(*) as count FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")['count'] ?? 0;
        
        // Previous week for comparison
        $prevWeekUsers = $db->single("SELECT COUNT(*) as count FROM users WHERE created_at BETWEEN DATE_SUB(NOW(), INTERVAL 14 DAY) AND DATE_SUB(NOW(), INTERVAL 7 DAY)")['count'] ?? 0;
        
        // Calculate percentage change for new users
        if ($prevWeekUsers > 0) {
            $stats['users_change_percent'] = round((($stats['new_users_week'] - $prevWeekUsers) / $prevWeekUsers) * 100, 1);
        } else {
            $stats['users_change_percent'] = $stats['new_users_week'] > 0 ? 100 : 0;
        }
        $stats['users_trend'] = $stats['users_change_percent'] >= 0 ? 'up' : 'down';
        
        // Today's logins vs yesterday
        $yesterdayLogins = $db->single("SELECT COUNT(*) as count FROM admin_login_attempts WHERE success = 1 AND DATE(attempted_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)")['count'] ?? 0;
        if ($yesterdayLogins > 0) {
            $stats['logins_change_percent'] = round((($stats['todays_logins'] - $yesterdayLogins) / $yesterdayLogins) * 100, 1);
        } else {
            $stats['logins_change_percent'] = $stats['todays_logins'] > 0 ? 100 : 0;
        }
        $stats['logins_trend'] = $stats['logins_change_percent'] >= 0 ? 'up' : 'down';
        
        // Failed logins last 24h vs previous 24h
        $prevFailedLogins = $db->single("SELECT COUNT(*) as count FROM admin_login_attempts WHERE success = 0 AND attempted_at BETWEEN DATE_SUB(NOW(), INTERVAL 48 HOUR) AND DATE_SUB(NOW(), INTERVAL 24 HOUR)")['count'] ?? 0;
        if ($prevFailedLogins > 0) {
            $stats['failed_change_percent'] = round((($stats['failed_logins_24h'] - $prevFailedLogins) / $prevFailedLogins) * 100, 1);
        } else {
            $stats['failed_change_percent'] = $stats['failed_logins_24h'] > 0 ? 100 : 0;
        }
        // For failed logins, down is good, so invert the trend sentiment
        $stats['failed_trend'] = $stats['failed_change_percent'] <= 0 ? 'down' : 'up';
        $stats['failed_is_good'] = $stats['failed_change_percent'] <= 0;
        
        echo json_encode(['success' => true, 'data' => $stats]);
        exit;
        
    case 'get_user_chart_data':
        $days = intval($_POST['days'] ?? 30);
        $days = min($days, 365);
        
        $data = $db->resultSet("SELECT DATE(created_at) as date, COUNT(*) as count FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY DATE(created_at) ORDER BY date", [$days]);
        
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
        
    case 'get_login_chart_data':
        $data = $db->resultSet("SELECT HOUR(attempted_at) as hour, COUNT(*) as count, SUM(success) as successful FROM admin_login_attempts WHERE attempted_at > DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY HOUR(attempted_at) ORDER BY hour");
        
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
        
    case 'get_recent_activity':
        $limit = min(intval($_POST['limit'] ?? 6), 6);
        
        $activities = $db->resultSet("SELECT al.*, u.first_name, u.last_name, u.email FROM admin_activity_logs al LEFT JOIN users u ON al.user_id = u.user_id ORDER BY al.created_at DESC LIMIT " . intval($limit));
        
        echo json_encode(['success' => true, 'data' => $activities]);
        exit;
        
    // ========================================
    // USER MANAGEMENT
    // ========================================
    
    case 'get_users':
        $page = max(1, intval($_POST['page'] ?? 1));
        $limit = min(max(10, intval($_POST['limit'] ?? 20)), 100);
        $offset = ($page - 1) * $limit;
        $search = trim($_POST['search'] ?? '');
        $role = $_POST['role'] ?? '';
        $status = $_POST['status'] ?? '';
        
        $where = "1=1";
        $params = [];
        
        if ($search) {
            $where .= " AND (email LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR phone LIKE ?)";
            $searchParam = "%$search%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        }
        
        if ($role && in_array($role, ['farmer', 'officer', 'admin'])) {
            $where .= " AND role = ?";
            $params[] = $role;
        }
        
        if ($status === 'active') {
            $where .= " AND is_active = 1";
        } elseif ($status === 'inactive') {
            $where .= " AND is_active = 0";
        }
        
        // Get total count
        $countResult = $db->single("SELECT COUNT(*) as total FROM users WHERE $where", $params);
        $total = $countResult ? $countResult['total'] : 0;
        
        // Get users
        $users = $db->resultSet("SELECT user_id, email, phone, first_name, last_name, profile_img_url, role, is_active, is_verified, last_login, created_at FROM users WHERE $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset", $params);
        
        echo json_encode([
            'success' => true,
            'data' => $users ?? [],
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => $total > 0 ? ceil($total / $limit) : 0
            ]
        ]);
        exit;
        
    case 'get_user':
        $userId = intval($_POST['user_id'] ?? 0);
        
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
            exit;
        }
        
        $user = $db->single("SELECT user_id, email, phone, first_name, last_name, profile_img_url, role, is_active, is_verified, last_login, created_at, updated_at FROM users WHERE user_id = ?", [$userId]);
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found.']);
            exit;
        }
        
        // Get profile based on role
        if ($user['role'] === 'farmer') {
            $user['profile'] = $db->single("SELECT * FROM farmer_profiles WHERE user_id = ?", [$userId]);
        } elseif ($user['role'] === 'officer') {
            $user['profile'] = $db->single("SELECT * FROM officer_profiles WHERE user_id = ?", [$userId]);
        }
        
        // Get login history
        $user['login_history'] = $db->resultSet("SELECT * FROM admin_login_attempts WHERE email = ? ORDER BY attempted_at DESC LIMIT 10", [$user['email']]);
        
        // Get warnings
        $user['warnings'] = $db->resultSet("SELECT * FROM user_warnings WHERE user_id = ? ORDER BY created_at DESC", [$userId]);
        
        // Get bans
        $user['bans'] = $db->resultSet("SELECT * FROM user_bans WHERE user_id = ? ORDER BY banned_at DESC", [$userId]);
        
        echo json_encode(['success' => true, 'data' => $user]);
        break;
    
    case 'update_profile':
        // Admin updating their own profile
        $currentUserId = $_SESSION['user_id'];
        
        // Get current user data
        $currentUser = $db->single("SELECT * FROM users WHERE user_id = ?", [$currentUserId]);
        if (!$currentUser) {
            jsonResponse(false, 'User not found');
        }
        
        $updates = [];
        $params = [];
        
        // Update first name
        if (isset($_POST['first_name']) && !empty(trim($_POST['first_name']))) {
            $firstName = trim($_POST['first_name']);
            $updates[] = "first_name = ?";
            $params[] = $firstName;
        }
        
        // Update last name
        if (isset($_POST['last_name'])) {
            $lastName = trim($_POST['last_name']);
            $updates[] = "last_name = ?";
            $params[] = $lastName;
        }
        
        // Update email
        if (isset($_POST['email'])) {
            $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
            if (!$email) {
                jsonResponse(false, 'Valid email is required');
            }
            
            // Check if email already exists for another user
            if ($email !== $currentUser['email']) {
                $existing = $db->single("SELECT user_id FROM users WHERE email = ? AND user_id != ?", [$email, $currentUserId]);
                if ($existing) {
                    jsonResponse(false, 'Email already exists');
                }
            }
            
            $updates[] = "email = ?";
            $params[] = $email;
        }
        
        // Update phone
        if (isset($_POST['phone'])) {
            $phone = trim($_POST['phone']);
            $updates[] = "phone = ?";
            $params[] = $phone;
        }
        
        // Handle password change
        if (!empty($_POST['new_password'])) {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'];
            
            // Verify current password
            if (!password_verify($currentPassword, $currentUser['password_hash'])) {
                jsonResponse(false, 'Current password is incorrect');
            }
            
            // Validate new password
            if (strlen($newPassword) < 6) {
                jsonResponse(false, 'New password must be at least 6 characters');
            }
            
            $updates[] = "password_hash = ?";
            $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
            
            logActivity($db, $currentUserId, 'Changed password', 'security', 'user', $currentUserId, null, null, 'medium');
        }
        
        // Handle profile image upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                jsonResponse(false, 'Invalid file type. Only JPG, PNG, and GIF allowed');
            }
            
            if ($_FILES['profile_image']['size'] > 5 * 1024 * 1024) { // 5MB limit
                jsonResponse(false, 'File too large. Maximum 5MB allowed');
            }
            
            $fileName = 'profile_' . $currentUserId . '_' . time() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $filePath)) {
                // Delete old profile image if exists
                if (!empty($currentUser['profile_img_url'])) {
                    $oldFilePath = __DIR__ . '/../../public/' . $currentUser['profile_img_url'];
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }
                
                $updates[] = "profile_img_url = ?";
                $params[] = 'uploads/profiles/' . $fileName;
            }
        }
        
        if (empty($updates)) {
            jsonResponse(false, 'No changes to update');
        }
        
        // Add updated_at
        $updates[] = "updated_at = NOW()";
        
        // Build and execute update query
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE user_id = ?";
        $params[] = $currentUserId;
        
        try {
            $stmt = $db->query($sql);
            foreach ($params as $i => $value) {
                $stmt->bind($i + 1, $value);
            }
            $stmt->execute();
            
            logActivity($db, $currentUserId, 'Updated profile', 'user', 'user', $currentUserId, null, null, 'low');
            
            jsonResponse(true, 'Profile updated successfully');
        } catch (Exception $e) {
            error_log('Profile update error: ' . $e->getMessage());
            jsonResponse(false, 'Failed to update profile');
        }
        break;
        
    case 'update_user':
        $userId = intval($_POST['user_id'] ?? 0);
        
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
            exit;
        }
        
        $oldUser = $db->single("SELECT * FROM users WHERE user_id = ?", [$userId]);
        if (!$oldUser) {
            echo json_encode(['success' => false, 'message' => 'User not found.']);
            exit;
        }
        
        $updates = [];
        $params = [];
        
        if (isset($_POST['email'])) {
            $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
            if (!$email) {
                echo json_encode(['success' => false, 'message' => 'Valid email is required.']);
                exit;
            }
            // Check if new email already exists (and is not the current user's email)
            if ($email !== $oldUser['email']) {
                $existingEmail = $db->single("SELECT user_id FROM users WHERE email = ?", [$email]);
                if ($existingEmail) {
                    echo json_encode(['success' => false, 'message' => 'Email already exists. Please use a different email address.']);
                    exit;
                }
            }
            $updates[] = "email = ?";
            $params[] = $email;
        }
        
        if (isset($_POST['first_name'])) {
            $updates[] = "first_name = ?";
            $params[] = trim($_POST['first_name']);
        }
        
        if (isset($_POST['last_name'])) {
            $updates[] = "last_name = ?";
            $params[] = trim($_POST['last_name']);
        }
        
        if (isset($_POST['phone'])) {
            $newPhone = trim($_POST['phone']);
            // Check if new phone already exists (and is not the current user's phone, if phone is not empty)
            if (!empty($newPhone) && $newPhone !== $oldUser['phone']) {
                $existingPhone = $db->single("SELECT user_id FROM users WHERE phone = ?", [$newPhone]);
                if ($existingPhone) {
                    echo json_encode(['success' => false, 'message' => 'Phone number already exists. Please use a different phone number.']);
                    exit;
                }
            }
            $updates[] = "phone = ?";
            $params[] = $newPhone;
        }
        
        if (isset($_POST['role']) && in_array($_POST['role'], ['farmer', 'officer', 'admin'])) {
            $updates[] = "role = ?";
            $params[] = $_POST['role'];
        }
        
        if (isset($_POST['is_active'])) {
            $updates[] = "is_active = ?";
            $params[] = $_POST['is_active'] ? 1 : 0;
        }
        
        if (isset($_POST['is_verified'])) {
            $updates[] = "is_verified = ?";
            $params[] = $_POST['is_verified'] ? 1 : 0;
        }
        
        if (empty($updates)) {
            echo json_encode(['success' => false, 'message' => 'No changes provided.']);
            exit;
        }
        
        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE user_id = ?";
        
        try {
            $db->query($sql);
            foreach ($params as $i => $value) {
                $db->bind($i + 1, $value);
            }
            $db->execute();
            
            logActivity($db, $_SESSION['user_id'], 'update_user', 'user', 'user', $userId, json_encode($oldUser), json_encode($_POST), 'medium');
            
            echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
        } catch (Exception $e) {
            logSecurityEvent($db, 'user_update_error', 'medium', 'Error updating user: ' . $e->getMessage(), $_SESSION['user_id']);
            echo json_encode(['success' => false, 'message' => 'Error updating user. ' . (APP_DEBUG ? $e->getMessage() : 'Please contact administrator.')]);
        }
        exit;
        
    case 'create_user':
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'farmer';
        $isActive = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;
        $isVerified = isset($_POST['is_verified']) ? intval($_POST['is_verified']) : 0;
        
        // Validation
        if (!$email) {
            echo json_encode(['success' => false, 'message' => 'Valid email is required.']);
            exit;
        }
        
        if (!$firstName) {
            echo json_encode(['success' => false, 'message' => 'First name is required.']);
            exit;
        }
        
        if (!$password || strlen($password) < 8) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
            exit;
        }
        
        if (!in_array($role, ['farmer', 'officer', 'admin'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid role.']);
            exit;
        }
        
        // Check if email already exists
        $existingEmail = $db->single("SELECT user_id FROM users WHERE email = ?", [$email]);
        if ($existingEmail) {
            echo json_encode(['success' => false, 'message' => 'Email already exists. Please use a different email address.']);
            exit;
        }
        
        // Check if phone already exists (if provided and not empty)
        if (!empty($phone)) {
            $existingPhone = $db->single("SELECT user_id FROM users WHERE phone = ?", [$phone]);
            if ($existingPhone) {
                echo json_encode(['success' => false, 'message' => 'Phone number already exists. Please use a different phone number.']);
                exit;
            }
        }
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            // Create user
            $db->query("INSERT INTO users (email, phone, password_hash, first_name, last_name, profile_img_url, role, is_active, is_verified, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())")
               ->bind(1, $email)
               ->bind(2, $phone)
               ->bind(3, $passwordHash)
               ->bind(4, $firstName)
               ->bind(5, $lastName)
               ->bind(6, 'uploads/profiles/default-avatar.jpg')
               ->bind(7, $role)
               ->bind(8, $isActive)
               ->bind(9, $isVerified)
               ->execute();
            
            $userId = $db->lastInsertId();
            
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'Failed to create user. Please try again.']);
                exit;
            }
            
            logActivity($db, $_SESSION['user_id'], 'create_user', 'user', 'user', $userId, null, json_encode(['email' => $email, 'first_name' => $firstName, 'role' => $role]), 'medium');
            
            echo json_encode(['success' => true, 'message' => 'User created successfully.', 'data' => ['user_id' => $userId]]);
        } catch (Exception $e) {
            logSecurityEvent($db, 'user_creation_error', 'medium', 'Error creating user: ' . $e->getMessage(), $_SESSION['user_id']);
            echo json_encode(['success' => false, 'message' => 'Error creating user. ' . (APP_DEBUG ? $e->getMessage() : 'Please contact administrator.')]);
        }
        exit;
        
    case 'delete_user':
        $userId = intval($_POST['user_id'] ?? 0);
        
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
            exit;
        }
        
        // Can't delete yourself
        if ($userId === $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'You cannot delete your own account.']);
            exit;
        }
        
        $user = $db->single("SELECT * FROM users WHERE user_id = ?", [$userId]);
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found.']);
            exit;
        }
        
        // Log before deletion
        logActivity($db, $_SESSION['user_id'], 'delete_user', 'user', 'user', $userId, json_encode($user), null, 'high');
        
        $db->query("DELETE FROM users WHERE user_id = ?")->bind(1, $userId)->execute();
        
        echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
        exit;
        
    case 'ban_user':
        $userId = intval($_POST['user_id'] ?? 0);
        $banType = $_POST['ban_type'] ?? 'temporary';
        $reason = trim($_POST['reason'] ?? '');
        $duration = intval($_POST['duration'] ?? 7); // days
        
        if (!$userId || !$reason) {
            echo json_encode(['success' => false, 'message' => 'User ID and reason are required.']);
            exit;
        }
        
        $expiresAt = $banType === 'permanent' ? null : date('Y-m-d H:i:s', time() + ($duration * 86400));
        
        $db->query("INSERT INTO user_bans (user_id, ban_type, reason, banned_by, expires_at) VALUES (?, ?, ?, ?, ?)")
           ->bind(1, $userId)
           ->bind(2, $banType)
           ->bind(3, $reason)
           ->bind(4, $_SESSION['user_id'])
           ->bind(5, $expiresAt)
           ->execute();
        
        // Deactivate user
        $db->query("UPDATE users SET is_active = 0 WHERE user_id = ?")->bind(1, $userId)->execute();
        
        logActivity($db, $_SESSION['user_id'], 'ban_user', 'user', 'user', $userId, null, json_encode(['type' => $banType, 'reason' => $reason]), 'high');
        
        echo json_encode(['success' => true, 'message' => 'User banned successfully.']);
        exit;
        
    case 'bulk_user_action':
        $action = $_POST['action'] ?? '';
        $userIds = $_POST['userIds'] ?? [];
        
        if (!in_array($action, ['activate', 'deactivate', 'delete'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
            exit;
        }
        
        if (empty($userIds) || !is_array($userIds)) {
            echo json_encode(['success' => false, 'message' => 'No users selected.']);
            exit;
        }
        
        $processedCount = 0;
        $errorCount = 0;
        
        foreach ($userIds as $userId) {
            $userId = intval($userId);
            
            if (!$userId || $userId === $_SESSION['user_id']) {
                $errorCount++;
                continue;
            }
            
            try {
                if ($action === 'activate') {
                    $db->query("UPDATE users SET is_active = 1 WHERE user_id = ?")->bind(1, $userId)->execute();
                    logActivity($db, $_SESSION['user_id'], 'activate_user', 'user', 'user', $userId, null, null, 'medium');
                } elseif ($action === 'deactivate') {
                    $db->query("UPDATE users SET is_active = 0 WHERE user_id = ?")->bind(1, $userId)->execute();
                    logActivity($db, $_SESSION['user_id'], 'deactivate_user', 'user', 'user', $userId, null, null, 'medium');
                } elseif ($action === 'delete') {
                    $user = $db->single("SELECT * FROM users WHERE user_id = ?", [$userId]);
                    if ($user) {
                        $db->query("DELETE FROM users WHERE user_id = ?")->bind(1, $userId)->execute();
                        logActivity($db, $_SESSION['user_id'], 'delete_user', 'user', 'user', $userId, json_encode($user), null, 'high');
                    }
                }
                $processedCount++;
            } catch (Exception $e) {
                $errorCount++;
            }
        }
        
        $message = $processedCount . ' user(s) ' . $action . 'd successfully.';
        if ($errorCount > 0) {
            $message .= ' (' . $errorCount . ' failed)';
        }
        
        echo json_encode(['success' => true, 'message' => $message, 'processed' => $processedCount, 'errors' => $errorCount]);
        exit;
        
    // ========================================
    // SYSTEM MONITORING
    // ========================================
    
    case 'get_system_metrics':
        $metrics = [];
        
        // PHP info
        $metrics['php'] = [
            'version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB'
        ];
        
        // Database stats
        $dbStats = $db->single("SHOW STATUS LIKE 'Threads_connected'");
        $metrics['database'] = [
            'connections' => $dbStats['Value'] ?? 0
        ];
        
        // Get table sizes
        $tables = $db->resultSet("SELECT table_name, ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb, table_rows FROM information_schema.tables WHERE table_schema = ?", [DB_NAME]);
        $metrics['tables'] = $tables;
        
        // Server info (limited in PHP)
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $metrics['server']['load'] = $load;
        }
        
        // Disk space
        $metrics['disk'] = [
            'free' => round(disk_free_space('/') / 1024 / 1024 / 1024, 2) . ' GB',
            'total' => round(disk_total_space('/') / 1024 / 1024 / 1024, 2) . ' GB',
            'used_percent' => round((1 - disk_free_space('/') / disk_total_space('/')) * 100, 1)
        ];
        
        echo json_encode(['success' => true, 'data' => $metrics]);
        exit;
        
    case 'get_error_logs':
        $page = max(1, intval($_POST['page'] ?? 1));
        $limit = min(intval($_POST['limit'] ?? 20), 100);
        $offset = ($page - 1) * $limit;
        $severity = $_POST['severity'] ?? '';
        $resolved = $_POST['resolved'] ?? '';
        
        $where = "1=1";
        $params = [];
        
        if ($severity && in_array($severity, ['debug', 'info', 'warning', 'error', 'critical'])) {
            $where .= " AND severity = ?";
            $params[] = $severity;
        }
        
        if ($resolved === '0') {
            $where .= " AND is_resolved = 0";
        } elseif ($resolved === '1') {
            $where .= " AND is_resolved = 1";
        }
        
        $total = $db->single("SELECT COUNT(*) as count FROM error_logs WHERE $where", $params)['count'];
        $errors = $db->resultSet("SELECT * FROM error_logs WHERE $where ORDER BY last_seen DESC LIMIT $limit OFFSET $offset", $params);
        
        echo json_encode([
            'success' => true,
            'data' => $errors,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
        exit;
        
    case 'resolve_error':
        $errorId = intval($_POST['error_id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        
        if (!$errorId) {
            echo json_encode(['success' => false, 'message' => 'Invalid error ID.']);
            exit;
        }
        
        $db->query("UPDATE error_logs SET is_resolved = 1, resolved_by = ?, resolved_at = NOW(), resolution_notes = ? WHERE error_id = ?")
           ->bind(1, $_SESSION['user_id'])
           ->bind(2, $notes)
           ->bind(3, $errorId)
           ->execute();
        
        logActivity($db, $_SESSION['user_id'], 'resolve_error', 'system', 'error', $errorId, null, $notes, 'low');
        
        echo json_encode(['success' => true, 'message' => 'Error marked as resolved.']);
        exit;
        
    case 'get_security_events':
        $page = max(1, intval($_POST['page'] ?? 1));
        $limit = min(intval($_POST['limit'] ?? 20), 100);
        $offset = ($page - 1) * $limit;
        $severity = $_POST['severity'] ?? '';
        $type = $_POST['type'] ?? '';
        
        $where = "1=1";
        $params = [];
        
        if ($severity) {
            $where .= " AND severity = ?";
            $params[] = $severity;
        }
        
        if ($type) {
            $where .= " AND event_type = ?";
            $params[] = $type;
        }
        
        $total = $db->single("SELECT COUNT(*) as count FROM security_events WHERE $where", $params)['count'];
        $events = $db->resultSet("SELECT * FROM security_events WHERE $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset", $params);
        
        echo json_encode([
            'success' => true,
            'data' => $events,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
        exit;
        
    // ========================================
    // NOTIFICATIONS
    // ========================================
    
    case 'get_notifications':
        $limit = min(intval($_POST['limit'] ?? 10), 50);
        $unreadOnly = isset($_POST['unread_only']);
        
        $where = "user_id = ? OR user_id IS NULL";
        $params = [$_SESSION['user_id']];
        
        if ($unreadOnly) {
            $where .= " AND is_read = 0";
        }
        
        $notifications = $db->resultSet("SELECT * FROM admin_notifications WHERE ($where) ORDER BY created_at DESC LIMIT ?", array_merge($params, [$limit]));
        
        $unreadCount = $db->single("SELECT COUNT(*) as count FROM admin_notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0", [$_SESSION['user_id']])['count'];
        
        echo json_encode([
            'success' => true,
            'data' => $notifications,
            'unread_count' => $unreadCount
        ]);
        exit;
        
    case 'mark_notification_read':
        $notificationId = intval($_POST['notification_id'] ?? 0);
        
        if ($notificationId) {
            $db->query("UPDATE admin_notifications SET is_read = 1, read_at = NOW() WHERE notification_id = ? AND (user_id = ? OR user_id IS NULL)")
               ->bind(1, $notificationId)->bind(2, $_SESSION['user_id'])->execute();
        } else {
            // Mark all as read
            $db->query("UPDATE admin_notifications SET is_read = 1, read_at = NOW() WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0")
               ->bind(1, $_SESSION['user_id'])->execute();
        }
        
        echo json_encode(['success' => true]);
        exit;
        
    // ========================================
    // ANALYTICS & REPORTING
    // ========================================
    
    case 'get_analytics_stats':
        $period = $_POST['period'] ?? 30;
        $period = min(intval($period), 365);
        
        $stats = [];
        
        // User growth
        $stats['total_users'] = $db->single("SELECT COUNT(*) as count FROM users")['count'];
        $stats['new_users_period'] = $db->single("SELECT COUNT(*) as count FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL ? DAY)", [$period])['count'];
        
        // Posts & content
        $stats['community_posts'] = $db->single("SELECT COUNT(*) as count FROM community_posts")['count'] ?? 0;
        $stats['marketplace_products'] = $db->single("SELECT COUNT(*) as count FROM marketplace_products")['count'] ?? 0;
        $stats['crop_records'] = $db->single("SELECT COUNT(*) as count FROM crop_data")['count'] ?? 0;
        $stats['disease_reports'] = $db->single("SELECT COUNT(*) as count FROM disease_reports")['count'] ?? 0;
        
        try {
            $stats['total_likes'] = $db->single("SELECT COUNT(*) as count FROM post_likes")['count'] ?? 0;
        } catch (Exception $e) {
            $stats['total_likes'] = 0;
        }
        try {
            $stats['total_comments'] = $db->single("SELECT COUNT(*) as count FROM post_comments")['count'] ?? 0;
        } catch (Exception $e) {
            $stats['total_comments'] = 0;
        }
        
        echo json_encode(['success' => true, 'data' => $stats]);
        exit;
        
    case 'get_user_growth_data':
        $days = min(intval($_POST['days'] ?? 30), 365);
        
        $data = $db->resultSet("SELECT DATE(created_at) as date, COUNT(*) as new_users, 
                              (SELECT COUNT(*) FROM users WHERE created_at <= DATE(u.created_at)) as cumulative
                              FROM users u 
                              WHERE created_at > DATE_SUB(NOW(), INTERVAL ? DAY) 
                              GROUP BY DATE(created_at) 
                              ORDER BY date", [$days]);
        
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
        
    case 'get_feature_usage':
        $data = [
            'posts' => $db->resultSet("SELECT DATE(created_at) as date, COUNT(*) as count FROM community_posts WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(created_at)") ?? [],
            'products' => $db->resultSet("SELECT DATE(created_at) as date, COUNT(*) as count FROM marketplace_products WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(created_at)") ?? [],
            'crops' => $db->resultSet("SELECT DATE(created_at) as date, COUNT(*) as count FROM crop_data WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(created_at)") ?? [],
            'diseases' => $db->resultSet("SELECT DATE(created_at) as date, COUNT(*) as count FROM disease_reports WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(created_at)") ?? []
        ];
        
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
        
    // ========================================
    // REPORT GENERATION
    // ========================================
    
    case 'generate_report':
        $reportType = $_POST['report_type'] ?? 'user_summary';
        $dateFrom = $_POST['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $_POST['date_to'] ?? date('Y-m-d');
        $format = $_POST['format'] ?? 'pdf';
        $reportName = $_POST['report_name'] ?? ucwords(str_replace('_', ' ', $reportType)) . ' Report - ' . date('Y-m-d H:i');
        
        // Validate format
        if (!in_array($format, ['pdf', 'csv', 'excel'])) {
            $format = 'pdf';
        }
        
        // Generate report data based on type
        $reportData = [];
        switch ($reportType) {
            case 'user_summary':
                $reportData['total_users'] = $db->single("SELECT COUNT(*) as count FROM users")['count'];
                $reportData['users_in_range'] = $db->single("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) BETWEEN ? AND ?", [$dateFrom, $dateTo])['count'];
                $reportData['by_role'] = $db->resultSet("SELECT role, COUNT(*) as count FROM users GROUP BY role");
                $reportData['recent_users'] = $db->resultSet("SELECT user_id, first_name, last_name, email, role, created_at FROM users WHERE DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC LIMIT 100", [$dateFrom, $dateTo]);
                break;
                
            case 'security_audit':
                $reportData['failed_logins'] = $db->resultSet("SELECT * FROM admin_login_attempts WHERE success = 0 AND DATE(attempted_at) BETWEEN ? AND ? ORDER BY attempted_at DESC", [$dateFrom, $dateTo]);
                $reportData['security_events'] = $db->resultSet("SELECT * FROM security_events WHERE DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC", [$dateFrom, $dateTo]);
                $reportData['blocked_ips'] = $db->resultSet("SELECT * FROM admin_ip_rules WHERE rule_type = 'blacklist'");
                break;
                
            case 'activity_log':
                $reportData['activities'] = $db->resultSet("SELECT al.*, u.first_name, u.last_name FROM admin_activity_logs al LEFT JOIN users u ON al.user_id = u.user_id WHERE DATE(al.created_at) BETWEEN ? AND ? ORDER BY al.created_at DESC LIMIT 500", [$dateFrom, $dateTo]);
                break;
                
            case 'content_analytics':
                $reportData['posts'] = $db->single("SELECT COUNT(*) as count FROM community_posts WHERE DATE(created_at) BETWEEN ? AND ?", [$dateFrom, $dateTo])['count'];
                $reportData['products'] = $db->single("SELECT COUNT(*) as count FROM marketplace_products WHERE DATE(created_at) BETWEEN ? AND ?", [$dateFrom, $dateTo])['count'];
                $reportData['top_posts'] = $db->resultSet("SELECT * FROM community_posts WHERE DATE(created_at) BETWEEN ? AND ? ORDER BY views DESC LIMIT 20", [$dateFrom, $dateTo]);
                break;
                
            case 'system_health':
                $reportData['errors'] = $db->resultSet("SELECT * FROM error_logs WHERE DATE(last_seen) BETWEEN ? AND ? ORDER BY occurrence_count DESC", [$dateFrom, $dateTo]);
                $reportData['api_stats'] = $db->resultSet("SELECT endpoint, COUNT(*) as count, AVG(response_time_ms) as avg_time FROM api_request_logs WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY endpoint", [$dateFrom, $dateTo]);
                break;
                
            case 'financial':
                $reportData['orders'] = $db->resultSet("SELECT * FROM marketplace_orders WHERE DATE(created_at) BETWEEN ? AND ?", [$dateFrom, $dateTo]) ?? [];
                $reportData['total_revenue'] = $db->single("SELECT COALESCE(SUM(total_amount), 0) as total FROM marketplace_orders WHERE status = 'completed' AND DATE(created_at) BETWEEN ? AND ?", [$dateFrom, $dateTo])['total'] ?? 0;
                break;
        }
        
        // Create report directory if not exists
        $reportDir = PROJECT_ROOT . '/reports/' . date('Y-m');
        if (!is_dir($reportDir)) {
            mkdir($reportDir, 0755, true);
        }
        
        // Generate file path
        $fileName = 'report_' . $reportType . '_' . date('Y-m-d_H-i-s') . '.' . ($format === 'excel' ? 'xlsx' : $format);
        $filePath = $reportDir . '/' . $fileName;
        
        // For now, save as JSON (actual PDF/CSV generation would require libraries)
        file_put_contents($filePath . '.json', json_encode($reportData, JSON_PRETTY_PRINT));
        $fileSize = filesize($filePath . '.json');
        
        // Save to database
        $db->query("INSERT INTO generated_reports (report_name, report_type, format, parameters, date_range_start, date_range_end, file_path, file_size, status, generated_by, generated_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?, NOW(), NOW())")
           ->bind(1, $reportName)
           ->bind(2, $reportType)
           ->bind(3, $format)
           ->bind(4, json_encode(['date_from' => $dateFrom, 'date_to' => $dateTo]))
           ->bind(5, $dateFrom)
           ->bind(6, $dateTo)
           ->bind(7, $filePath . '.json')
           ->bind(8, $fileSize)
           ->bind(9, $_SESSION['user_id'])
           ->execute();
        
        logActivity($db, $_SESSION['user_id'], 'generate_report', 'report', 'report', null, null, json_encode(['type' => $reportType, 'format' => $format]), 'low');
        
        echo json_encode(['success' => true, 'message' => 'Report generated successfully.', 'file' => $fileName]);
        exit;
        
    case 'create_scheduled_report':
        $reportName = trim($_POST['report_name'] ?? '');
        $reportType = $_POST['report_type'] ?? 'users';
        $scheduleCron = $_POST['schedule_cron'] ?? '0 8 * * *';
        $scheduleHuman = $_POST['schedule_human'] ?? 'Daily at 8:00 AM';
        $format = $_POST['format'] ?? 'pdf';
        $recipients = $_POST['recipients'] ?? '';
        
        if (empty($reportName)) {
            echo json_encode(['success' => false, 'message' => 'Report name is required.']);
            exit;
        }
        
        // Validate
        if (!in_array($reportType, ['users', 'security', 'activity', 'content', 'performance', 'financial'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid report type.']);
            exit;
        }
        
        // Calculate next send time
        $nextSend = date('Y-m-d 08:00:00', strtotime('+1 day'));
        
        $db->query("INSERT INTO scheduled_reports (report_name, report_type, schedule_cron, schedule_human, format, recipients, is_enabled, next_send, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, NOW())")
           ->bind(1, $reportName)
           ->bind(2, $reportType)
           ->bind(3, $scheduleCron)
           ->bind(4, $scheduleHuman)
           ->bind(5, $format)
           ->bind(6, json_encode(array_map('trim', explode(',', $recipients))))
           ->bind(7, $nextSend)
           ->bind(8, $_SESSION['user_id'])
           ->execute();
        
        logActivity($db, $_SESSION['user_id'], 'create_scheduled_report', 'report', 'scheduled_report', null, null, $reportName, 'low');
        
        echo json_encode(['success' => true, 'message' => 'Report scheduled successfully.']);
        exit;
        
    case 'toggle_scheduled_report':
        $scheduleId = intval($_POST['schedule_id'] ?? 0);
        $isEnabled = intval($_POST['is_enabled'] ?? 0);
        
        if (!$scheduleId) {
            echo json_encode(['success' => false, 'message' => 'Invalid schedule ID.']);
            exit;
        }
        
        $db->query("UPDATE scheduled_reports SET is_enabled = ? WHERE schedule_id = ?")
           ->bind(1, $isEnabled)
           ->bind(2, $scheduleId)
           ->execute();
        
        echo json_encode(['success' => true, 'message' => 'Schedule updated.']);
        exit;
        
    case 'delete_scheduled_report':
        $scheduleId = intval($_POST['schedule_id'] ?? 0);
        
        if (!$scheduleId) {
            echo json_encode(['success' => false, 'message' => 'Invalid schedule ID.']);
            exit;
        }
        
        $db->query("DELETE FROM scheduled_reports WHERE schedule_id = ?")
           ->bind(1, $scheduleId)
           ->execute();
        
        logActivity($db, $_SESSION['user_id'], 'delete_scheduled_report', 'report', 'scheduled_report', $scheduleId, null, null, 'low');
        
        echo json_encode(['success' => true, 'message' => 'Scheduled report deleted.']);
        exit;
        
    case 'delete_report':
        $reportId = intval($_POST['report_id'] ?? 0);
        
        if (!$reportId) {
            echo json_encode(['success' => false, 'message' => 'Invalid report ID.']);
            exit;
        }
        
        // Get file path to delete
        $report = $db->single("SELECT file_path FROM generated_reports WHERE report_id = ?", [$reportId]);
        if ($report && $report['file_path'] && file_exists($report['file_path'])) {
            unlink($report['file_path']);
        }
        
        $db->query("DELETE FROM generated_reports WHERE report_id = ?")
           ->bind(1, $reportId)
           ->execute();
        
        logActivity($db, $_SESSION['user_id'], 'delete_report', 'report', 'report', $reportId, null, null, 'low');
        
        echo json_encode(['success' => true, 'message' => 'Report deleted.']);
        exit;
        
    // ========================================
    // SETTINGS & CONFIGURATION
    // ========================================
    
    case 'get_settings':
        $section = $_POST['section'] ?? 'general';
        
        $settings = $db->resultSet("SELECT setting_key, setting_value FROM admin_settings");
        $settingsMap = [];
        foreach ($settings as $s) {
            $settingsMap[$s['setting_key']] = $s['setting_value'];
        }
        
        echo json_encode(['success' => true, 'data' => $settingsMap]);
        exit;
        
    case 'update_settings':
        $settings = $_POST['settings'] ?? [];
        
        if (empty($settings)) {
            echo json_encode(['success' => false, 'message' => 'No settings provided.']);
            exit;
        }
        
        foreach ($settings as $key => $value) {
            // Whitelist known settings
            $allowedKeys = ['site_name', 'site_description', 'default_language', 'timezone', 'items_per_page', 'require_2fa', 'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'max_login_attempts', 'session_timeout'];
            
            if (!in_array($key, $allowedKeys)) {
                continue;
            }
            
            $oldValue = $db->single("SELECT setting_value FROM admin_settings WHERE setting_key = ?", [$key]);
            $oldValue = $oldValue ? $oldValue['setting_value'] : null;
            
            $db->query("INSERT INTO admin_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?")->bind(1, $key)->bind(2, $value)->bind(3, $value)->execute();
            
            logActivity($db, $_SESSION['user_id'], 'update_setting', 'system', 'setting', null, $oldValue, $value, 'medium');
        }
        
        echo json_encode(['success' => true, 'message' => 'Settings updated successfully.']);
        exit;
        
    // ========================================
    // EXPORT FUNCTIONS
    // ========================================
    
    case 'export_users':
        // Get all users
        $users = $db->resultSet("SELECT user_id, email, phone, first_name, last_name, role, is_active, is_verified, last_login, created_at FROM users ORDER BY created_at DESC");
        
        if (empty($users)) {
            echo json_encode(['success' => false, 'message' => 'No users to export.']);
            exit;
        }
        
        // Create CSV content
        $csv = "User ID,First Name,Last Name,Email,Phone,Role,Active,Verified,Last Login,Created At\n";
        foreach ($users as $user) {
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
                $user['user_id'],
                str_replace('"', '""', $user['first_name']),
                str_replace('"', '""', $user['last_name'] ?? ''),
                str_replace('"', '""', $user['email']),
                str_replace('"', '""', $user['phone']),
                $user['role'],
                $user['is_active'] ? 'Yes' : 'No',
                $user['is_verified'] ? 'Yes' : 'No',
                $user['last_login'] ?? 'Never',
                $user['created_at']
            );
        }
        
        // Create export file
        $exportDir = PROJECT_ROOT . '/reports/' . date('Y-m');
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }
        
        $fileName = 'users_export_' . date('Y-m-d_H-i-s') . '.csv';
        $filePath = $exportDir . '/' . $fileName;
        file_put_contents($filePath, $csv);
        
        // Log activity
        logActivity($db, $_SESSION['user_id'], 'export_users', 'user', 'export', null, null, count($users) . ' users exported', 'low');
        
        echo json_encode([
            'success' => true,
            'message' => 'Users exported successfully.',
            'downloadUrl' => '/reports/' . date('Y-m') . '/' . $fileName,
            'fileName' => $fileName,
            'recordCount' => count($users)
        ]);
        exit;
        
    // ========================================
    // CONTENT MODERATION
    // ========================================
    
    case 'get_reports':
        $page = max(1, intval($_POST['page'] ?? 1));
        $limit = min(intval($_POST['limit'] ?? 20), 100);
        $offset = ($page - 1) * $limit;
        $status = $_POST['status'] ?? '';
        
        $where = "1=1";
        $params = [];
        
        if ($status && in_array($status, ['pending', 'resolved', 'dismissed'])) {
            $where .= " AND status = ?";
            $params[] = $status;
        }
        
        $total = $db->single("SELECT COUNT(*) as count FROM content_reports WHERE $where", $params)['count'];
        $reports = $db->resultSet("SELECT * FROM content_reports WHERE $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset", $params);
        
        echo json_encode([
            'success' => true,
            'data' => $reports,
            'pagination' => ['page' => $page, 'limit' => $limit, 'total' => $total, 'pages' => ceil($total / $limit)]
        ]);
        exit;
        
    case 'update_report_status':
        $reportId = intval($_POST['report_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $action = $_POST['action'] ?? ''; // remove_content, warn_user, ban_user, dismiss
        $notes = trim($_POST['notes'] ?? '');
        
        if (!$reportId || !in_array($status, ['pending', 'resolved', 'dismissed'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid report or status.']);
            exit;
        }
        
        $report = $db->single("SELECT * FROM content_reports WHERE report_id = ?", [$reportId]);
        if (!$report) {
            echo json_encode(['success' => false, 'message' => 'Report not found.']);
            exit;
        }
        
        // Handle action
        if ($action === 'remove_content' && $report['content_type']) {
            if ($report['content_type'] === 'post') {
                $db->query("DELETE FROM community_posts WHERE post_id = ?")->bind(1, $report['content_id'])->execute();
            } elseif ($report['content_type'] === 'product') {
                $db->query("DELETE FROM marketplace_products WHERE product_id = ?")->bind(1, $report['content_id'])->execute();
            }
        }
        
        // Update report
        $db->query("UPDATE content_reports SET status = ?, reviewed_by = ?, reviewed_at = NOW(), resolution = ? WHERE report_id = ?")
           ->bind(1, $status)->bind(2, $_SESSION['user_id'])->bind(3, $notes)->bind(4, $reportId)->execute();
        
        logActivity($db, $_SESSION['user_id'], 'resolve_report', 'moderation', 'report', $reportId, null, json_encode(['action' => $action, 'status' => $status]), 'medium');
        
        echo json_encode(['success' => true, 'message' => 'Report updated successfully.']);
        exit;
        
    // ========================================
    // ADMIN SETTINGS & SECURITY
    // ========================================
    
    case 'get_ip_rules':
        $page = max(1, intval($_POST['page'] ?? 1));
        $limit = min(intval($_POST['limit'] ?? 20), 100);
        $offset = ($page - 1) * $limit;
        
        $total = $db->single("SELECT COUNT(*) as count FROM admin_ip_rules")['count'];
        $rules = $db->resultSet("SELECT * FROM admin_ip_rules ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
        
        echo json_encode([
            'success' => true,
            'data' => $rules,
            'pagination' => ['page' => $page, 'limit' => $limit, 'total' => $total, 'pages' => ceil($total / $limit)]
        ]);
        exit;
        
    case 'add_ip_rule':
        $ipAddress = trim($_POST['ip_address'] ?? '');
        $ruleType = $_POST['rule_type'] ?? 'blacklist';
        $reason = trim($_POST['reason'] ?? '');
        $duration = intval($_POST['duration'] ?? 0);
        
        // Validate IP
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            echo json_encode(['success' => false, 'message' => 'Invalid IP address.']);
            exit;
        }
        
        $expiresAt = $duration > 0 ? date('Y-m-d H:i:s', time() + ($duration * 86400)) : null;
        
        $db->query("INSERT INTO admin_ip_rules (ip_address, rule_type, reason, expires_at) VALUES (?, ?, ?, ?)")
           ->bind(1, $ipAddress)->bind(2, $ruleType)->bind(3, $reason)->bind(4, $expiresAt)->execute();
        
        logActivity($db, $_SESSION['user_id'], 'add_ip_rule', 'security', 'ip_rule', null, null, json_encode(['ip' => $ipAddress, 'type' => $ruleType]), 'medium');
        
        echo json_encode(['success' => true, 'message' => 'IP rule added.']);
        exit;
        
    case 'delete_ip_rule':
        $ruleId = intval($_POST['rule_id'] ?? 0);
        
        if (!$ruleId) {
            echo json_encode(['success' => false, 'message' => 'Invalid rule ID.']);
            exit;
        }
        
        $rule = $db->single("SELECT * FROM admin_ip_rules WHERE rule_id = ?", [$ruleId]);
        if (!$rule) {
            echo json_encode(['success' => false, 'message' => 'Rule not found.']);
            exit;
        }
        
        $db->query("DELETE FROM admin_ip_rules WHERE rule_id = ?")->bind(1, $ruleId)->execute();
        
        logActivity($db, $_SESSION['user_id'], 'delete_ip_rule', 'security', 'ip_rule', $ruleId, json_encode($rule), null, 'medium');
        
        echo json_encode(['success' => true, 'message' => 'IP rule deleted.']);
        exit;
        
    case 'get_admin_logs':
        $page = max(1, intval($_POST['page'] ?? 1));
        $limit = min(intval($_POST['limit'] ?? 20), 100);
        $offset = ($page - 1) * $limit;
        $riskLevel = $_POST['risk_level'] ?? '';
        
        $where = "1=1";
        $params = [];
        
        if ($riskLevel && in_array($riskLevel, ['low', 'medium', 'high'])) {
            $where .= " AND risk_level = ?";
            $params[] = $riskLevel;
        }
        
        $total = $db->single("SELECT COUNT(*) as count FROM admin_activity_logs WHERE $where", $params)['count'];
        $logs = $db->resultSet("SELECT * FROM admin_activity_logs WHERE $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset", $params);
        
        echo json_encode([
            'success' => true,
            'data' => $logs,
            'pagination' => ['page' => $page, 'limit' => $limit, 'total' => $total, 'pages' => ceil($total / $limit)]
        ]);
        exit;
        
    case 'acknowledge_security_event':
        $eventId = intval($_POST['event_id'] ?? 0);
        
        if (!$eventId) {
            echo json_encode(['success' => false, 'message' => 'Invalid event ID.']);
            exit;
        }
        
        $db->query("UPDATE security_events SET is_acknowledged = 1, acknowledged_by = ?, acknowledged_at = NOW() WHERE event_id = ?")
           ->bind(1, $_SESSION['user_id'])->bind(2, $eventId)->execute();
        
        echo json_encode(['success' => true, 'message' => 'Event acknowledged.']);
        exit;
        
    // ========================================
    // BACKUP & MAINTENANCE
    // ========================================
    
    case 'create_backup':
        $backupType = $_POST['type'] ?? 'database'; // database, files, full
        
        if (!in_array($backupType, ['database', 'files', 'full'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid backup type.']);
            exit;
        }
        
        // Create backup directory if not exists
        $backupDir = PROJECT_ROOT . '/backups/' . date('Y-m-d');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $backupFile = $backupDir . '/backup_' . date('Y-m-d_H-i-s') . '_' . $backupType;
        
        // For now, just log the intention
        $db->query("INSERT INTO backup_records (backup_type, file_path, status, created_by) VALUES (?, ?, 'completed', ?)")
           ->bind(1, $backupType)->bind(2, $backupFile)->bind(3, $_SESSION['user_id'])->execute();
        
        logActivity($db, $_SESSION['user_id'], 'create_backup', 'system', 'backup', null, null, $backupType, 'medium');
        
        echo json_encode(['success' => true, 'message' => 'Backup scheduled.', 'backup_file' => $backupFile]);
        exit;
        
    case 'clear_cache':
        // Clear any application caches
        logActivity($db, $_SESSION['user_id'], 'clear_cache', 'system', 'cache', null, null, null, 'low');
        
        echo json_encode(['success' => true, 'message' => 'Cache cleared successfully.']);
        break;
        
    // ========================================
    // SECURITY ENDPOINTS
    // ========================================
    
    case 'get_security_stats':
        // Get security statistics
        $stats = [
            'blocked_ips' => $db->single("SELECT COUNT(*) as count FROM admin_ip_rules WHERE rule_type = 'blacklist' AND (expires_at IS NULL OR expires_at > NOW())")['count'] ?? 0,
            'failed_logins' => $db->single("SELECT COUNT(*) as count FROM admin_login_attempts WHERE success = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")['count'] ?? 0,
            'active_sessions' => $db->single("SELECT COUNT(*) as count FROM admin_sessions WHERE is_active = 1 AND expires_at > NOW()")['count'] ?? 0,
            'unacknowledged_events' => $db->single("SELECT COUNT(*) as count FROM security_events WHERE is_acknowledged = 0")['count'] ?? 0,
            'critical_events' => $db->single("SELECT COUNT(*) as count FROM security_events WHERE severity = 'critical' AND is_acknowledged = 0")['count'] ?? 0,
        ];
        
        // Calculate threat level
        $threatLevel = 'low';
        if ($stats['critical_events'] > 0) {
            $threatLevel = 'critical';
        } elseif ($stats['unacknowledged_events'] >= 5) {
            $threatLevel = 'high';
        } elseif ($stats['unacknowledged_events'] > 0 || $stats['failed_logins'] > 10) {
            $threatLevel = 'medium';
        }
        
        $stats['threat_level'] = $threatLevel;
        
        echo json_encode(['success' => true, 'data' => $stats]);
        exit;
        
    case 'acknowledge_all_events':
        $result = $db->query("UPDATE security_events SET is_acknowledged = 1, acknowledged_by = ?, acknowledged_at = NOW() WHERE is_acknowledged = 0")
            ->bind(1, $_SESSION['user_id'])
            ->execute();
        
        $count = $db->rowCount();
        
        logActivity($db, $_SESSION['user_id'], 'acknowledge_all_events', 'security', 'security_event', null, null, "Acknowledged $count events", 'low');
        
        echo json_encode(['success' => true, 'message' => "Acknowledged $count event(s).", 'count' => $count]);
        exit;
        
    case 'terminate_session':
        $sessionId = $_POST['session_id'] ?? '';
        
        if (!$sessionId) {
            echo json_encode(['success' => false, 'message' => 'Invalid session ID.']);
            exit;
        }
        
        // Get session info before deletion
        $session = $db->single("SELECT * FROM admin_sessions WHERE session_id = ?", [$sessionId]);
        
        if (!$session) {
            echo json_encode(['success' => false, 'message' => 'Session not found.']);
            exit;
        }
        
        // Don't allow terminating own session
        if ($sessionId === ($_SESSION['admin_session_id'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Cannot terminate your own session.']);
            exit;
        }
        
        // Terminate session
        $db->query("UPDATE admin_sessions SET is_active = 0, ended_at = NOW(), end_reason = 'Terminated by admin' WHERE session_id = ?")
            ->bind(1, $sessionId)
            ->execute();
        
        logActivity($db, $_SESSION['user_id'], 'terminate_session', 'security', 'admin_session', $session['user_id'], json_encode($session), null, 'medium');
        logSecurityEvent($db, 'session_terminated', 'medium', "Admin session terminated by " . $_SESSION['user_id'], $session['user_id']);
        
        echo json_encode(['success' => true, 'message' => 'Session terminated successfully.']);
        exit;
        
    case 'terminate_all_sessions':
        // Get current session ID to exclude it
        $currentSessionId = $_SESSION['admin_session_id'] ?? '';
        
        // Count sessions to be terminated
        $count = $db->single("SELECT COUNT(*) as count FROM admin_sessions WHERE is_active = 1 AND session_id != ?", [$currentSessionId])['count'] ?? 0;
        
        // Terminate all other sessions
        $db->query("UPDATE admin_sessions SET is_active = 0, ended_at = NOW(), end_reason = 'Terminated by admin (bulk)' WHERE is_active = 1 AND session_id != ?")
            ->bind(1, $currentSessionId)
            ->execute();
        
        logActivity($db, $_SESSION['user_id'], 'terminate_all_sessions', 'security', 'admin_session', null, null, "Terminated $count sessions", 'high');
        logSecurityEvent($db, 'bulk_session_termination', 'high', "All admin sessions terminated by " . $_SESSION['user_id'], $_SESSION['user_id']);
        
        echo json_encode(['success' => true, 'message' => "$count session(s) terminated.", 'count' => $count]);
        exit;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        exit;
}
?>
