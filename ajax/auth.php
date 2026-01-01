<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

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

$action = $_GET['action'] ?? $_POST['action'] ?? null;

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
    $action = $input['action'] ?? $action;
}

// ==================== SEND VERIFICATION CODE (Step 1 of Registration) ====================
if ($action === 'send_code') {
    $email = filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $firstName = htmlspecialchars($input['firstName'] ?? '', ENT_QUOTES, 'UTF-8');
    $lastName = htmlspecialchars($input['lastName'] ?? '', ENT_QUOTES, 'UTF-8');
    $phone = htmlspecialchars($input['phone'] ?? '', ENT_QUOTES, 'UTF-8');
    $role = htmlspecialchars($input['role'] ?? 'farmer', ENT_QUOTES, 'UTF-8');
    $password = $input['password'] ?? '';
    
    // Validate email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email address';
        echo json_encode($response);
        exit;
    }
    
    // Validate first name
    if (empty($firstName) || strlen($firstName) < 2) {
        $response['message'] = 'First name must be at least 2 characters';
        echo json_encode($response);
        exit;
    }
    
    // Validate phone
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    if (empty($cleanPhone) || strlen($cleanPhone) < 10) {
        $response['message'] = 'Invalid phone number';
        echo json_encode($response);
        exit;
    }
    
    // Validate role
    if (empty($role) || !in_array($role, ['farmer', 'officer'])) {
        $response['message'] = 'Invalid role selected';
        echo json_encode($response);
        exit;
    }
    
    // Validate password
    if (empty($password) || strlen($password) < 8) {
        $response['message'] = 'Password must be at least 8 characters';
        echo json_encode($response);
        exit;
    }
    
    if (!validatePasswordStrength($password)) {
        $response['message'] = 'Password must contain uppercase, lowercase, number, and special character';
        echo json_encode($response);
        exit;
    }
    
    // Check if email already exists and is verified
    $existingUser = $db->single("SELECT user_id, is_verified FROM users WHERE email = ?", [$email]);
    
    if ($existingUser) {
        if ($existingUser['is_verified'] == 1) {
            $response['message'] = 'Email already registered. Please login.';
            echo json_encode($response);
            exit;
        } else {
            // User exists but not verified, delete and allow re-registration
            $db->query("DELETE FROM users WHERE user_id = ?")->bind(1, $existingUser['user_id'])->execute();
        }
    }
    
    // Check if phone already exists
    $existingPhone = $db->single("SELECT user_id FROM users WHERE phone = ?", [$phone]);
    if ($existingPhone) {
        $response['message'] = 'Phone number already registered';
        echo json_encode($response);
        exit;
    }
    
    // Generate verification code
    $verificationCode = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $hashedCode = password_hash($verificationCode, PASSWORD_BCRYPT);
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    
    // Insert new user with email_verification_token stored in a session (table doesn't have that column)
    $db->query("INSERT INTO users (email, phone, password_hash, first_name, last_name, role, is_active, is_verified) 
                VALUES (?, ?, ?, ?, ?, ?, 1, 0)")
       ->bind(1, $email)
       ->bind(2, $phone)
       ->bind(3, $passwordHash)
       ->bind(4, $firstName)
       ->bind(5, $lastName)
       ->bind(6, $role)
       ->execute();
    
    $userId = $db->lastInsertId();
    
    // Store verification code in session
    $_SESSION['pending_user_id'] = $userId;
    $_SESSION['pending_email'] = $email;
    $_SESSION['pending_first_name'] = $firstName;
    $_SESSION['verification_code'] = $hashedCode;
    $_SESSION['verification_expires'] = time() + 600; // 10 minutes
    
    // Send verification email
    if (sendVerificationEmail($email, $verificationCode, $firstName)) {
        $response['success'] = true;
        $response['message'] = 'Verification code sent to your email';
        $response['email'] = maskEmail($email);
    } else {
        // Delete the user if email failed
        $db->query("DELETE FROM users WHERE user_id = ?")->bind(1, $userId)->execute();
        $response['message'] = 'Failed to send verification email. Please try again.';
    }
    
    echo json_encode($response);
    exit;
}

// ==================== VERIFY CODE (Step 2 of Registration) ====================
if ($action === 'verify_code') {
    $code = htmlspecialchars($input['code'] ?? '', ENT_QUOTES, 'UTF-8');
    
    if (empty($code) || strlen($code) !== 6 || !ctype_digit($code)) {
        $response['message'] = 'Invalid verification code format';
        echo json_encode($response);
        exit;
    }
    
    if (!isset($_SESSION['pending_user_id']) || !isset($_SESSION['verification_expires'])) {
        $response['message'] = 'Session expired. Please start registration again.';
        echo json_encode($response);
        exit;
    }
    
    if (time() > $_SESSION['verification_expires']) {
        $response['message'] = 'Verification code expired. Please request a new code.';
        echo json_encode($response);
        exit;
    }
    
    $userId = $_SESSION['pending_user_id'];
    
    // Verify code from session
    if (!password_verify($code, $_SESSION['verification_code'])) {
        $response['message'] = 'Invalid verification code';
        echo json_encode($response);
        exit;
    }
    
    // Mark user as verified
    $db->query("UPDATE users SET is_verified = 1 WHERE user_id = ?")
       ->bind(1, $userId)
       ->execute();
    
    // Set session for authenticated user
    $_SESSION['user_id'] = $userId;
    $_SESSION['email_verified'] = true;
    unset($_SESSION['verification_code']);
    unset($_SESSION['verification_expires']);
    
    $response['success'] = true;
    $response['message'] = 'Email verified successfully!';
    $response['redirect'] = 'register?step=profile';
    
    echo json_encode($response);
    exit;
}

// ==================== RESEND CODE ====================
if ($action === 'resend_code') {
    if (!isset($_SESSION['pending_user_id']) || !isset($_SESSION['pending_email'])) {
        $response['message'] = 'Session expired. Please start registration again.';
        echo json_encode($response);
        exit;
    }
    
    $userId = $_SESSION['pending_user_id'];
    $email = $_SESSION['pending_email'];
    $firstName = $_SESSION['pending_first_name'] ?? 'User';
    
    // Check if user exists
    $user = $db->single("SELECT first_name FROM users WHERE user_id = ?", [$userId]);
    
    if (!$user) {
        $response['message'] = 'User not found. Please start registration again.';
        echo json_encode($response);
        exit;
    }
    
    // Generate new verification code
    $verificationCode = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $hashedCode = password_hash($verificationCode, PASSWORD_BCRYPT);
    
    // Update verification code in session
    $_SESSION['verification_code'] = $hashedCode;
    $_SESSION['verification_expires'] = time() + 600;
    
    if (sendVerificationEmail($email, $verificationCode, $user['first_name'])) {
        $response['success'] = true;
        $response['message'] = 'New verification code sent!';
    } else {
        $response['message'] = 'Failed to send verification code. Please try again.';
    }
    
    echo json_encode($response);
    exit;
}

// ==================== COMPLETE PROFILE (Step 3 - Upload Photo) ====================
if ($action === 'complete_profile') {
    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'Unauthorized. Please login first.';
        echo json_encode($response);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    
    // Handle profile photo upload - MANDATORY
    $profilePhotoUrl = null;
    
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $profilePhotoUrl = uploadProfilePhoto($_FILES['profile_photo'], $userId);
        
        if (!$profilePhotoUrl) {
            $response['message'] = 'Failed to upload profile photo. Invalid format or size.';
            echo json_encode($response);
            exit;
        }
    } elseif (!empty($input['profile_photo_base64'])) {
        $profilePhotoUrl = saveBase64Photo($input['profile_photo_base64'], $userId);
        
        if (!$profilePhotoUrl) {
            $response['message'] = 'Failed to save profile photo';
            echo json_encode($response);
            exit;
        }
    } else {
        // Profile photo is mandatory
        $response['message'] = 'Profile photo is required. Please upload a photo.';
        echo json_encode($response);
        exit;
    }
    
    // Update user with profile photo URL
    $db->query("UPDATE users SET profile_img_url = ? WHERE user_id = ?")
       ->bind(1, $profilePhotoUrl)
       ->bind(2, $userId)
       ->execute();
    
    // Get user info for session
    $user = $db->single("SELECT * FROM users WHERE user_id = ?", [$userId]);
    
    // Set full session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['profile_img_url'] = $user['profile_img_url'];
    
    // Clean up registration sessions
    unset($_SESSION['pending_user_id']);
    unset($_SESSION['pending_email']);
    unset($_SESSION['pending_first_name']);
    unset($_SESSION['email_verified']);
    
    $response['success'] = true;
    $response['message'] = 'Registration complete! Welcome to Smart Chashi!';
    $response['redirect'] = 'dashboard';
    
    echo json_encode($response);
    exit;
}

// ==================== LOGIN ====================
if ($action === 'login') {
    $emailOrPhone = htmlspecialchars($input['email'] ?? $input['emailOrPhone'] ?? '', ENT_QUOTES, 'UTF-8');
    $password = $input['password'] ?? '';
    
    if (empty($emailOrPhone)) {
        $response['message'] = 'Email or phone is required';
        echo json_encode($response);
        exit;
    }
    
    if (empty($password)) {
        $response['message'] = 'Password is required';
        echo json_encode($response);
        exit;
    }
    
    // Check user by email or phone
    $user = $db->single(
        "SELECT * FROM users WHERE (email = ? OR phone = ?) AND is_active = 1",
        [$emailOrPhone, $emailOrPhone]
    );
    
    if (!$user) {
        $response['message'] = 'User not found. Please register first.';
        echo json_encode($response);
        exit;
    }
    
    if ($user['is_verified'] != 1) {
        // Store for resending verification
        $_SESSION['pending_user_id'] = $user['user_id'];
        $_SESSION['pending_email'] = $user['email'];
        $_SESSION['pending_first_name'] = $user['first_name'];
        $_SESSION['verification_expires'] = time() + 600;
        
        $response['message'] = 'Email not verified. Please verify your email.';
        $response['needs_verification'] = true;
        $response['email'] = maskEmail($user['email']);
        echo json_encode($response);
        exit;
    }
    
    if (!password_verify($password, $user['password_hash'])) {
        $response['message'] = 'Invalid password';
        echo json_encode($response);
        exit;
    }
    
    // Successful login
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    
    // Update last login
    $db->query("UPDATE users SET last_login = NOW() WHERE user_id = ?")
       ->bind(1, $user['user_id'])
       ->execute();
    
    $response['success'] = true;
    $response['message'] = 'Login successful!';
    
    // Role-based redirect
    if ($user['role'] === 'admin') {
        $response['redirect'] = 'admin-secure/pages/admin-dashboard.php';
        $_SESSION['admin_logged_in'] = true;
    } elseif ($user['role'] === 'officer') {
        $response['redirect'] = '?page=officer-dashboard';
    } else {
        // farmer or default
        $response['redirect'] = '?page=dashboard';
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

function sendVerificationEmail($email, $code, $firstName) {
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
        $mail->Subject = $code . ' is your ' . $yourname . ' verification code';
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
                <h2 style="color: #557A46; margin-top: 20px;">Email Verification</h2>
                <p>Hello ' . htmlspecialchars($firstName) . '!</p>
                <p>Welcome to ' . $yourname . '! Use the code below to verify your email:</p>
                <div style="background-color: #f1f8e9; border: 2px solid #557A46; padding: 20px; margin: 20px 0; font-size: 32px; font-weight: bold; border-radius: 10px; text-align: center; letter-spacing: 5px; color: #557A46;">
                    ' . $code . '
                </div>
                <p style="color: #666;">This code will expire in <strong>10 minutes</strong>.</p>
                <p style="color: #999; font-size: 12px;">If you did not request this code, please ignore this email.</p>
                <hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">
                <p style="color: #999; font-size: 12px; text-align: center;">
                    Best regards,<br>
                    <strong>' . $yourname . ' Team</strong><br>
                    <em>AI-Powered Smart Farming</em>
                </p>
            </div>
        </body>
        </html>';
        
        $mail->AltBody = 'Your ' . $yourname . ' verification code is: ' . $code . '. This code will expire in 10 minutes.';
        
        return $mail->send();
    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $e->getMessage());
        return false;
    }
}

function uploadProfilePhoto($file, $userId) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }
    
    if ($file['size'] > $maxSize) {
        return false;
    }
    
    $uploadDir = PROJECT_ROOT . '/public/uploads/profiles/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return 'uploads/profiles/' . $filename;
    }
    
    return false;
}

function saveBase64Photo($base64Data, $userId) {
    // Extract image data from base64 string
    if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $matches)) {
        $extension = $matches[1];
        $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
    } else {
        return false;
    }
    
    $imageData = base64_decode($base64Data);
    if ($imageData === false) {
        return false;
    }
    
    $uploadDir = PROJECT_ROOT . '/public/uploads/profiles/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (file_put_contents($filepath, $imageData)) {
        return 'uploads/profiles/' . $filename;
    }
    
    return false;
}

echo json_encode($response);
exit;
?>
