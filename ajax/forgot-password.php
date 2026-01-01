<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

// Email configuration loaded from config.php
$myEmail = defined('SMTP_USERNAME') ? SMTP_USERNAME : '';
$appPassword = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
$yourname = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Smart Chashi';

$db = new Database();
$response = ['success' => false, 'message' => 'Invalid request'];

// Handle JSON input
$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $input = json_decode($rawInput, true) ?? [];
    }
    if (empty($input)) {
        $input = $_POST;
    }
}

$action = $input['action'] ?? $_GET['action'] ?? null;

// ==================== ACTION 1: SEND RESET CODE ====================
if ($action === 'send_code') {
    $emailOrPhone = htmlspecialchars($input['emailOrPhone'] ?? '', ENT_QUOTES, 'UTF-8');
    
    if (empty($emailOrPhone)) {
        $response['message'] = 'Email or phone number is required';
        echo json_encode($response);
        exit;
    }
    
    // Check if user exists
    $user = $db->single(
        "SELECT user_id, email, first_name, phone FROM users WHERE email = ? OR phone = ?",
        [$emailOrPhone, $emailOrPhone]
    );
    
    if (!$user) {
        $response['message'] = 'No account found with this email or phone';
        echo json_encode($response);
        exit;
    }
    
    // Generate reset code
    $resetCode = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $hashedCode = password_hash($resetCode, PASSWORD_BCRYPT);
    
    // Store in session
    $_SESSION['reset_user_id'] = $user['user_id'];
    $_SESSION['reset_email'] = $user['email'];
    $_SESSION['reset_code'] = $hashedCode;
    $_SESSION['reset_expires'] = time() + 600; // 10 minutes
    
    // Send reset email
    if (sendPasswordResetEmail($user['email'], $resetCode, $user['first_name'])) {
        $response['success'] = true;
        $response['message'] = 'Password reset code sent to your email';
        $response['email'] = maskEmail($user['email']);
    } else {
        $response['message'] = 'Failed to send reset code. Please try again.';
    }
    
    echo json_encode($response);
    exit;
}

// ==================== ACTION 2: VERIFY CODE ====================
if ($action === 'verify_code') {
    $code = htmlspecialchars($input['code'] ?? '', ENT_QUOTES, 'UTF-8');
    
    if (empty($code) || strlen($code) !== 6 || !ctype_digit($code)) {
        $response['message'] = 'Invalid code format';
        echo json_encode($response);
        exit;
    }
    
    if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['reset_expires'])) {
        $response['message'] = 'Session expired. Please request a new code.';
        echo json_encode($response);
        exit;
    }
    
    if (time() > $_SESSION['reset_expires']) {
        $response['message'] = 'Code expired. Please request a new code.';
        echo json_encode($response);
        exit;
    }
    
    if (!password_verify($code, $_SESSION['reset_code'])) {
        $response['message'] = 'Invalid verification code';
        echo json_encode($response);
        exit;
    }
    
    // Code verified, allow password reset
    $_SESSION['reset_verified'] = true;
    unset($_SESSION['reset_code']);
    
    $response['success'] = true;
    $response['message'] = 'Code verified. You can now reset your password.';
    
    echo json_encode($response);
    exit;
}

// ==================== ACTION 3: RESET PASSWORD ====================
if ($action === 'reset_password') {
    $password = $input['password'] ?? '';
    $confirmPassword = $input['confirmPassword'] ?? '';
    
    if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['reset_verified'])) {
        $response['message'] = 'Please verify your code first';
        echo json_encode($response);
        exit;
    }
    
    if ($_SESSION['reset_verified'] !== true) {
        $response['message'] = 'Please verify your code first';
        echo json_encode($response);
        exit;
    }
    
    if (empty($password)) {
        $response['message'] = 'Password is required';
        echo json_encode($response);
        exit;
    }
    
    if (strlen($password) < 8) {
        $response['message'] = 'Password must be at least 8 characters';
        echo json_encode($response);
        exit;
    }
    
    if (!validatePasswordStrength($password)) {
        $response['message'] = 'Password must contain uppercase, lowercase, number, and special character';
        echo json_encode($response);
        exit;
    }
    
    if ($password !== $confirmPassword) {
        $response['message'] = 'Passwords do not match';
        echo json_encode($response);
        exit;
    }
    
    $userId = $_SESSION['reset_user_id'];
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    
    // Update password
    $db->query("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE user_id = ?")
       ->bind(1, $passwordHash)
       ->bind(2, $userId)
       ->execute();
    
    // Clear reset session
    unset($_SESSION['reset_user_id']);
    unset($_SESSION['reset_email']);
    unset($_SESSION['reset_verified']);
    unset($_SESSION['reset_expires']);
    
    $response['success'] = true;
    $response['message'] = 'Password reset successfully! You can now login.';
    $response['redirect'] = 'login';
    
    echo json_encode($response);
    exit;
}

// ==================== RESEND CODE ====================
if ($action === 'resend_code') {
    if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['reset_email'])) {
        $response['message'] = 'Session expired. Please start again.';
        echo json_encode($response);
        exit;
    }
    
    $userId = $_SESSION['reset_user_id'];
    $email = $_SESSION['reset_email'];
    
    // Get user info
    $user = $db->single("SELECT first_name FROM users WHERE user_id = ?", [$userId]);
    
    if (!$user) {
        $response['message'] = 'User not found. Please start again.';
        echo json_encode($response);
        exit;
    }
    
    // Generate new reset code
    $resetCode = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $hashedCode = password_hash($resetCode, PASSWORD_BCRYPT);
    
    // Update session
    $_SESSION['reset_code'] = $hashedCode;
    $_SESSION['reset_expires'] = time() + 600;
    unset($_SESSION['reset_verified']);
    
    if (sendPasswordResetEmail($email, $resetCode, $user['first_name'])) {
        $response['success'] = true;
        $response['message'] = 'New reset code sent!';
    } else {
        $response['message'] = 'Failed to send reset code. Please try again.';
    }
    
    echo json_encode($response);
    exit;
}

// ==================== HELPER FUNCTIONS ====================

function validatePasswordStrength($password) {
    return strlen($password) >= 8 &&
           preg_match('/[A-Z]/', $password) &&
           preg_match('/[a-z]/', $password) &&
           preg_match('/[0-9]/', $password) &&
           preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password);
}

function maskEmail($email) {
    $parts = explode('@', $email);
    $name = $parts[0];
    $domain = $parts[1];
    
    $len = strlen($name);
    if ($len <= 4) {
        $maskedName = substr($name, 0, 1) . str_repeat('*', $len - 1);
    } else {
        $maskedName = substr($name, 0, 2) . str_repeat('*', $len - 4) . substr($name, -2);
    }
    return $maskedName . '@' . $domain;
}

function sendPasswordResetEmail($email, $code, $firstName) {
    global $myEmail, $appPassword, $yourname;
    
    try {
        $mail = new PHPMailer(true);
        
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $myEmail;
        $mail->Password = $appPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->Timeout = 10;
        
        $mail->setFrom($myEmail, $yourname);
        $mail->addAddress($email);
        
        $mail->isHTML(true);
        $mail->Subject = $code . ' is your ' . $yourname . ' password reset code';
        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="font-family: Arial, sans-serif; color: #333; margin: 0; padding: 0; background-color: #f5f5f5;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px; background-color: #fff; border-radius: 10px; margin-top: 20px;">
                <div style="text-align: center; padding-bottom: 20px; border-bottom: 2px solid #557A46;">
                    <h1 style="color: #557A46; margin: 0;">🌾 ' . $yourname . '</h1>
                </div>
                <h2 style="color: #557A46; margin-top: 20px;">Password Reset Request</h2>
                <p>Hello ' . htmlspecialchars($firstName) . '!</p>
                <p>We received a request to reset your password. Use the code below:</p>
                <div style="background-color: #fff3e0; border: 2px solid #FF8C00; padding: 20px; margin: 20px 0; font-size: 32px; font-weight: bold; border-radius: 10px; text-align: center; letter-spacing: 5px; color: #FF8C00;">
                    ' . $code . '
                </div>
                <p style="color: #666;">This code will expire in <strong>10 minutes</strong>.</p>
                <p style="color: #d32f2f; font-size: 14px;"><strong>⚠️ Security Warning:</strong> If you did not request this password reset, please ignore this email and your password will remain unchanged.</p>
                <hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">
                <p style="color: #999; font-size: 12px; text-align: center;">
                    Best regards,<br>
                    <strong>' . $yourname . ' Team</strong><br>
                    <em>AI-Powered Smart Farming</em>
                </p>
            </div>
        </body>
        </html>';
        
        $mail->AltBody = 'Your ' . $yourname . ' password reset code is: ' . $code . '. This code will expire in 10 minutes. If you did not request this, please ignore this email.';
        
        return $mail->send();
    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $e->getMessage());
        return false;
    }
}

echo json_encode($response);
exit;
?>
