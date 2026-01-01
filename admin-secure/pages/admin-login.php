<?php
/**
 * Admin Login Page
 * Secure admin-only authentication with advanced security features
 */

require_once __DIR__ . '/../../config/config.php';

// Prevent access if already logged in as admin
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: ' . $base_url . 'admin-secure/pages/admin-dashboard.php');
    exit;
}

function getClientIP() {
    $ip = $_SERVER['REMOTE_ADDR'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    return filter_var(trim($ip), FILTER_VALIDATE_IP) ?: '0.0.0.0';
}

function isIPBlocked($db, $ip) {
    $rule = $db->single("SELECT * FROM admin_ip_rules WHERE ip_address = ? AND rule_type = 'blacklist' AND (expires_at IS NULL OR expires_at > NOW())", [$ip]);
    return $rule !== false;
}

function getFailedAttempts($db, $ip, $minutes = 15) {
    $result = $db->single("SELECT COUNT(*) as count FROM admin_login_attempts WHERE ip_address = ? AND success = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)", [$ip, $minutes]);
    return $result['count'] ?? 0;
}

function isRateLimited($db, $ip) {
    $result = $db->single("SELECT * FROM rate_limits WHERE identifier = ? AND endpoint = 'admin_login' AND blocked_until > NOW()", [$ip]);
    return $result !== false;
}

function logLoginAttempt($db, $ip, $email, $success, $reason = null) {
    $db->query("INSERT INTO admin_login_attempts (ip_address, email, success, failure_reason, user_agent) VALUES (?, ?, ?, ?, ?)")
       ->bind(1, $ip)
       ->bind(2, $email)
       ->bind(3, $success ? 1 : 0)
       ->bind(4, $reason)
       ->bind(5, $_SERVER['HTTP_USER_AGENT'] ?? '')
       ->execute();
}

function logSecurityEvent($db, $type, $severity, $description, $userId = null, $ip = null) {
    $db->query("INSERT INTO security_events (event_type, severity, user_id, ip_address, description, raw_data) VALUES (?, ?, ?, ?, ?, ?)")
       ->bind(1, $type)
       ->bind(2, $severity)
       ->bind(3, $userId)
       ->bind(4, $ip ?? getClientIP())
       ->bind(5, $description)
       ->bind(6, json_encode(['user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '', 'time' => date('Y-m-d H:i:s')]))
       ->execute();
}

$db = new Database();
$clientIP = getClientIP();
$error = '';
$showCaptcha = false;
$isLocked = false;
$lockoutRemaining = 0;

// Check if IP is blocked
if (isIPBlocked($db, $clientIP)) {
    $error = 'Access denied. Your IP has been blocked.';
    $isLocked = true;
}

// Check rate limiting
if (!$isLocked && isRateLimited($db, $clientIP)) {
    $result = $db->single("SELECT blocked_until FROM rate_limits WHERE identifier = ? AND endpoint = 'admin_login'", [$clientIP]);
    $lockoutRemaining = max(0, strtotime($result['blocked_until']) - time());
    $error = 'Too many failed attempts. Please try again in ' . ceil($lockoutRemaining / 60) . ' minutes.';
    $isLocked = true;
}

// Check failed attempts for CAPTCHA
$failedAttempts = getFailedAttempts($db, $clientIP);
if ($failedAttempts >= 3) {
    $showCaptcha = true;
}

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Login - <?php echo APP_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo $base_url; ?>img/logo.png">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --secondary: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark: #1e1e2e;
            --darker: #11111b;
            --light: #cdd6f4;
            --surface: #1e1e2e;
            --surface-hover: #313244;
            --text: #cdd6f4;
            --text-muted: #6c7086;
            --border: #45475a;
            --shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            --gradient: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--darker);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Animated background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }

        .bg-animation::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 20% 80%, rgba(99, 102, 241, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(16, 185, 129, 0.1) 0%, transparent 50%);
            animation: bgMove 20s linear infinite;
        }

        @keyframes bgMove {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            background: var(--gradient);
            opacity: 0.1;
            animation: float 15s infinite ease-in-out;
        }

        .shape:nth-child(1) { width: 300px; height: 300px; top: 10%; left: 10%; animation-delay: 0s; }
        .shape:nth-child(2) { width: 200px; height: 200px; top: 60%; right: 10%; animation-delay: 2s; }
        .shape:nth-child(3) { width: 150px; height: 150px; bottom: 10%; left: 30%; animation-delay: 4s; }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(10deg); }
        }

        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
        }

        .login-card {
            background: rgba(30, 30, 46, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 40px;
            box-shadow: var(--shadow);
        }

        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .login-logo {
            width: 70px;
            height: 70px;
            background: var(--gradient);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 40px rgba(99, 102, 241, 0.3);
        }

        .login-logo .material-icons {
            font-size: 36px;
            color: white;
        }

        .login-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 8px;
        }

        .login-subtitle {
            color: var(--text-muted);
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            color: var(--text);
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 20px;
            transition: color 0.3s;
        }

        .form-input {
            width: 100%;
            padding: 14px 14px 14px 48px;
            background: var(--surface);
            border: 2px solid var(--border);
            border-radius: 12px;
            color: var(--text);
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--surface-hover);
        }

        .form-input:focus + .input-icon,
        .form-input:focus ~ .input-icon {
            color: var(--primary);
        }

        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.3s;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
        }

        .checkbox-input {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
            cursor: pointer;
        }

        .checkbox-label {
            color: var(--text-muted);
            font-size: 13px;
            cursor: pointer;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: var(--gradient);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .btn-login:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 40px rgba(99, 102, 241, 0.4);
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn-login .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        .btn-login.loading .btn-text {
            display: none;
        }

        .btn-login.loading .spinner {
            display: block;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #fcd34d;
        }

        .alert .material-icons {
            font-size: 20px;
        }

        /* 2FA Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px;
            max-width: 400px;
            width: 90%;
            text-align: center;
        }

        .modal-icon {
            width: 64px;
            height: 64px;
            background: var(--gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .modal-icon .material-icons {
            font-size: 32px;
            color: white;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
        }

        .modal-subtitle {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 24px;
        }

        .otp-inputs {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-bottom: 24px;
        }

        .otp-input {
            width: 48px;
            height: 56px;
            text-align: center;
            font-size: 24px;
            font-weight: 600;
            background: var(--darker);
            border: 2px solid var(--border);
            border-radius: 12px;
            color: var(--text);
            transition: all 0.3s;
        }

        .otp-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .resend-link {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
            margin-top: 16px;
        }

        .resend-link:hover {
            text-decoration: underline;
        }

        .resend-link.disabled {
            color: var(--text-muted);
            pointer-events: none;
        }

        /* Security indicators */
        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            color: var(--text-muted);
            font-size: 12px;
        }

        .security-badge .material-icons {
            font-size: 16px;
            color: var(--secondary);
        }

        /* Honeypot - Hidden from real users */
        .hp-field {
            position: absolute;
            left: -9999px;
            opacity: 0;
            pointer-events: none;
        }

        /* Lockout countdown */
        .lockout-timer {
            font-size: 48px;
            font-weight: 700;
            color: var(--danger);
            margin: 20px 0;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-card {
                padding: 24px;
            }

            .otp-input {
                width: 40px;
                height: 48px;
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="bg-animation">
        <div class="floating-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <span class="material-icons">admin_panel_settings</span>
                </div>
                <h1 class="login-title">Admin Portal</h1>
                <p class="login-subtitle">Secure administrative access</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span class="material-icons">error</span>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($isLocked): ?>
                <div class="alert alert-warning">
                    <span class="material-icons">lock_clock</span>
                    <span>Account temporarily locked</span>
                </div>
                <?php if ($lockoutRemaining > 0): ?>
                    <div class="lockout-timer" id="lockoutTimer"><?php echo gmdate("i:s", $lockoutRemaining); ?></div>
                <?php endif; ?>
            <?php else: ?>
                <form id="adminLoginForm" method="POST" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="device_fingerprint" id="deviceFingerprint" value="">
                    
                    <!-- Honeypot fields - hidden from users, bots will fill them -->
                    <div class="hp-field">
                        <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                        <input type="text" name="phone_number" id="phone_number" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <div class="input-wrapper">
                            <input type="email" id="email" name="email" class="form-input" placeholder="admin@example.com" required>
                            <span class="material-icons input-icon">mail</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" required>
                            <span class="material-icons input-icon">lock</span>
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <span class="material-icons" id="toggleIcon">visibility</span>
                            </button>
                        </div>
                    </div>

                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="remember_device" name="remember_device" class="checkbox-input">
                        <label for="remember_device" class="checkbox-label">Trust this device for 30 days</label>
                    </div>

                    <button type="submit" class="btn-login" id="loginBtn">
                        <span class="btn-text">Sign In Securely</span>
                        <span class="spinner"></span>
                        <span class="material-icons btn-text">arrow_forward</span>
                    </button>
                </form>
            <?php endif; ?>

            <div class="security-badge">
                <span class="material-icons">verified_user</span>
                <span>256-bit SSL encrypted connection</span>
            </div>
        </div>
    </div>

    <!-- 2FA Modal -->
    <div class="modal-overlay" id="tfaModal">
        <div class="modal-content">
            <div class="modal-icon">
                <span class="material-icons">security</span>
            </div>
            <h3 class="modal-title">Two-Factor Authentication</h3>
            <p class="modal-subtitle">Enter the 6-digit code sent to your email</p>
            
            <form id="tfaForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="temp_session" id="tempSession" value="">
                
                <div class="otp-inputs">
                    <input type="text" class="otp-input" maxlength="1" data-index="0" inputmode="numeric" pattern="[0-9]*">
                    <input type="text" class="otp-input" maxlength="1" data-index="1" inputmode="numeric" pattern="[0-9]*">
                    <input type="text" class="otp-input" maxlength="1" data-index="2" inputmode="numeric" pattern="[0-9]*">
                    <input type="text" class="otp-input" maxlength="1" data-index="3" inputmode="numeric" pattern="[0-9]*">
                    <input type="text" class="otp-input" maxlength="1" data-index="4" inputmode="numeric" pattern="[0-9]*">
                    <input type="text" class="otp-input" maxlength="1" data-index="5" inputmode="numeric" pattern="[0-9]*">
                </div>

                <div id="tfaError" class="alert alert-error" style="display: none;">
                    <span class="material-icons">error</span>
                    <span id="tfaErrorText"></span>
                </div>

                <button type="submit" class="btn-login" id="verifyBtn">
                    <span class="btn-text">Verify Code</span>
                    <span class="spinner"></span>
                </button>

                <a href="#" class="resend-link" id="resendCode">Resend code <span id="resendTimer"></span></a>
            </form>
        </div>
    </div>

    <script>
        // Device fingerprinting
        function generateFingerprint() {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            ctx.textBaseline = 'top';
            ctx.font = '14px Arial';
            ctx.fillText('fingerprint', 2, 2);
            
            const fingerprint = {
                userAgent: navigator.userAgent,
                language: navigator.language,
                platform: navigator.platform,
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                screenResolution: `${screen.width}x${screen.height}`,
                colorDepth: screen.colorDepth,
                canvasHash: canvas.toDataURL().slice(-50),
                plugins: Array.from(navigator.plugins || []).map(p => p.name).join(',')
            };
            
            // Simple hash
            const str = JSON.stringify(fingerprint);
            let hash = 0;
            for (let i = 0; i < str.length; i++) {
                const char = str.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash;
            }
            
            return Math.abs(hash).toString(16).padStart(16, '0');
        }

        document.getElementById('deviceFingerprint').value = generateFingerprint();

        // Toggle password visibility
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = 'visibility_off';
            } else {
                input.type = 'password';
                icon.textContent = 'visibility';
            }
        }

        // Form submission
        document.getElementById('adminLoginForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.disabled = true;

            const formData = new FormData(this);
            formData.append('action', 'admin_login');

            try {
                const response = await fetch('<?php echo $base_url; ?>admin-secure/ajax/admin.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    if (data.require_2fa) {
                        // Show 2FA modal
                        document.getElementById('tempSession').value = data.temp_session;
                        document.getElementById('tfaModal').classList.add('active');
                        document.querySelector('.otp-input').focus();
                        startResendTimer();
                    } else {
                        // Redirect to dashboard
                        window.location.href = '<?php echo $base_url; ?>admin-secure/pages/admin-dashboard.php';
                    }
                } else {
                    showError(data.message || 'Login failed. Please try again.');
                    btn.classList.remove('loading');
                    btn.disabled = false;
                }
            } catch (error) {
                showError('Connection error. Please try again.');
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        });

        // OTP input handling
        document.querySelectorAll('.otp-input').forEach((input, index, inputs) => {
            input.addEventListener('input', (e) => {
                const value = e.target.value.replace(/\D/g, '');
                e.target.value = value;
                
                if (value && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
                
                // Auto-submit when all filled
                const code = Array.from(inputs).map(i => i.value).join('');
                if (code.length === 6) {
                    document.getElementById('tfaForm').dispatchEvent(new Event('submit'));
                }
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    inputs[index - 1].focus();
                }
            });

            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pasteData = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
                pasteData.split('').forEach((char, i) => {
                    if (inputs[i]) inputs[i].value = char;
                });
                if (pasteData.length === 6) {
                    document.getElementById('tfaForm').dispatchEvent(new Event('submit'));
                }
            });
        });

        // 2FA form submission
        document.getElementById('tfaForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const inputs = document.querySelectorAll('.otp-input');
            const code = Array.from(inputs).map(i => i.value).join('');
            
            if (code.length !== 6) {
                showTfaError('Please enter all 6 digits');
                return;
            }

            const btn = document.getElementById('verifyBtn');
            btn.classList.add('loading');
            btn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'verify_2fa');
            formData.append('code', code);
            formData.append('temp_session', document.getElementById('tempSession').value);
            formData.append('csrf_token', '<?php echo $csrf_token; ?>');

            try {
                const response = await fetch('<?php echo $base_url; ?>admin-secure/ajax/admin.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = '<?php echo $base_url; ?>admin-secure/pages/admin-dashboard.php';
                } else {
                    showTfaError(data.message || 'Invalid code. Please try again.');
                    inputs.forEach(i => i.value = '');
                    inputs[0].focus();
                    btn.classList.remove('loading');
                    btn.disabled = false;
                }
            } catch (error) {
                showTfaError('Connection error. Please try again.');
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        });

        function showError(message) {
            const existingError = document.querySelector('.alert-error:not(#tfaError)');
            if (existingError) {
                existingError.querySelector('span:last-child').textContent = message;
            } else {
                const alert = document.createElement('div');
                alert.className = 'alert alert-error';
                alert.innerHTML = `<span class="material-icons">error</span><span>${message}</span>`;
                document.querySelector('.login-header').after(alert);
            }
        }

        function showTfaError(message) {
            const errorDiv = document.getElementById('tfaError');
            document.getElementById('tfaErrorText').textContent = message;
            errorDiv.style.display = 'flex';
        }

        // Resend timer
        let resendInterval;
        function startResendTimer(seconds = 60) {
            const link = document.getElementById('resendCode');
            const timer = document.getElementById('resendTimer');
            link.classList.add('disabled');
            
            let remaining = seconds;
            timer.textContent = `(${remaining}s)`;
            
            clearInterval(resendInterval);
            resendInterval = setInterval(() => {
                remaining--;
                timer.textContent = `(${remaining}s)`;
                
                if (remaining <= 0) {
                    clearInterval(resendInterval);
                    link.classList.remove('disabled');
                    timer.textContent = '';
                }
            }, 1000);
        }

        // Resend code
        document.getElementById('resendCode')?.addEventListener('click', async function(e) {
            e.preventDefault();
            if (this.classList.contains('disabled')) return;

            const formData = new FormData();
            formData.append('action', 'resend_2fa');
            formData.append('temp_session', document.getElementById('tempSession').value);
            formData.append('csrf_token', '<?php echo $csrf_token; ?>');

            try {
                const response = await fetch('<?php echo $base_url; ?>admin-secure/ajax/admin.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    startResendTimer();
                } else {
                    showTfaError(data.message || 'Failed to resend code');
                }
            } catch (error) {
                showTfaError('Connection error');
            }
        });

        // Lockout countdown
        <?php if ($lockoutRemaining > 0): ?>
        (function() {
            let remaining = <?php echo $lockoutRemaining; ?>;
            const timer = document.getElementById('lockoutTimer');
            
            const interval = setInterval(() => {
                remaining--;
                if (remaining <= 0) {
                    clearInterval(interval);
                    location.reload();
                } else {
                    const mins = Math.floor(remaining / 60);
                    const secs = remaining % 60;
                    timer.textContent = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
                }
            }, 1000);
        })();
        <?php endif; ?>
    </script>
</body>
</html>
