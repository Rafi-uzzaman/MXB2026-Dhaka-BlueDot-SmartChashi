<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$db = new Database();
$userId = $_SESSION['user_id'];
$user = getCurrentUser();
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'update_personal':
        updatePersonalInfo($db, $userId);
        break;
    
    case 'update_farmer_profile':
        updateFarmerProfile($db, $userId);
        break;
    
    case 'update_officer_profile':
        updateOfficerProfile($db, $userId);
        break;
    
    case 'update_admin_profile':
        updateAdminProfile($db, $userId);
        break;
    
    case 'change_password':
        changePassword($db, $userId);
        break;
    
    case 'update_profile_image':
        updateProfileImage($db, $userId);
        break;
    
    case 'update_preferences':
        updatePreferences($db, $userId);
        break;
    
    case 'delete_account':
        deleteAccount($db, $userId);
        break;
    
    case 'contact_officer':
        contactOfficer($db, $userId);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
}

function updatePersonalInfo($db, $userId) {
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($firstName) || empty($email) || empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'First name, email and phone are required']);
        return;
    }
    
    // Check if email already exists for other users
    $existingUser = $db->single("SELECT user_id FROM users WHERE email = ? AND user_id != ?", [$email, $userId]);
    if ($existingUser) {
        echo json_encode(['success' => false, 'message' => 'Email already in use by another account']);
        return;
    }
    
    // Check if phone already exists for other users
    $existingPhone = $db->single("SELECT user_id FROM users WHERE phone = ? AND user_id != ?", [$phone, $userId]);
    if ($existingPhone) {
        echo json_encode(['success' => false, 'message' => 'Phone number already in use by another account']);
        return;
    }
    
    try {
        $db->query("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, updated_at = NOW() WHERE user_id = ?")
           ->bind(1, $firstName)
           ->bind(2, $lastName)
           ->bind(3, $email)
           ->bind(4, $phone)
           ->bind(5, $userId)
           ->execute();
        
        // Update session
        $_SESSION['first_name'] = $firstName;
        $_SESSION['last_name'] = $lastName;
        $_SESSION['email'] = $email;
        
        echo json_encode(['success' => true, 'message' => 'Personal information updated successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update profile: ' . $e->getMessage()]);
    }
}

function updateFarmerProfile($db, $userId) {
    $landSize = floatval($_POST['landSize'] ?? 0);
    $experience = $_POST['experience'] ?? '';
    $primaryCrop = trim($_POST['primaryCrop'] ?? '');
    $region = $_POST['region'] ?? '';
    
    try {
        // Check if profile exists
        $profile = $db->single("SELECT profile_id FROM farmer_profiles WHERE user_id = ?", [$userId]);
        
        if ($profile) {
            // Update existing profile
            $db->query("UPDATE farmer_profiles SET land_size_hectares = ?, experience_level = ?, primary_crops = ?, region = ?, updated_at = NOW() WHERE user_id = ?")
               ->bind(1, $landSize)
               ->bind(2, $experience ?: null)
               ->bind(3, $primaryCrop)
               ->bind(4, $region)
               ->bind(5, $userId)
               ->execute();
        } else {
            // Create new profile
            $db->query("INSERT INTO farmer_profiles (user_id, land_size_hectares, experience_level, primary_crops, region, created_at) VALUES (?, ?, ?, ?, ?, NOW())")
               ->bind(1, $userId)
               ->bind(2, $landSize)
               ->bind(3, $experience ?: null)
               ->bind(4, $primaryCrop)
               ->bind(5, $region)
               ->execute();
        }
        
        echo json_encode(['success' => true, 'message' => 'Farming profile updated successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update farming profile: ' . $e->getMessage()]);
    }
}

function updateOfficerProfile($db, $userId) {
    $officerRegion = $_POST['officerRegion'] ?? '';
    $specialization = $_POST['specialization'] ?? '';
    $department = trim($_POST['department'] ?? '');
    
    try {
        // Check if profile exists
        $profile = $db->single("SELECT profile_id FROM officer_profiles WHERE user_id = ?", [$userId]);
        
        if ($profile) {
            // Update existing profile
            $db->query("UPDATE officer_profiles SET region = ?, expertise_area = ?, department = ?, updated_at = NOW() WHERE user_id = ?")
               ->bind(1, $officerRegion)
               ->bind(2, $specialization)
               ->bind(3, $department)
               ->bind(4, $userId)
               ->execute();
        } else {
            // Create new profile
            $db->query("INSERT INTO officer_profiles (user_id, region, expertise_area, department, created_at) VALUES (?, ?, ?, ?, NOW())")
               ->bind(1, $userId)
               ->bind(2, $officerRegion)
               ->bind(3, $specialization)
               ->bind(4, $department)
               ->execute();
        }
        
        echo json_encode(['success' => true, 'message' => 'Officer profile updated successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update officer profile: ' . $e->getMessage()]);
    }
}

function updateAdminProfile($db, $userId) {
    $accessLevel = $_POST['accessLevel'] ?? 'admin';
    $responsibilities = trim($_POST['responsibilities'] ?? '');
    
    try {
        // Check if profile exists
        $profile = $db->single("SELECT profile_id FROM admin_profiles WHERE user_id = ?", [$userId]);
        
        if ($profile) {
            // Update existing profile
            $db->query("UPDATE admin_profiles SET access_level = ?, responsibilities = ?, updated_at = NOW() WHERE user_id = ?")
               ->bind(1, $accessLevel)
               ->bind(2, $responsibilities)
               ->bind(3, $userId)
               ->execute();
        } else {
            // Create new profile
            $db->query("INSERT INTO admin_profiles (user_id, access_level, responsibilities, created_at) VALUES (?, ?, ?, NOW())")
               ->bind(1, $userId)
               ->bind(2, $accessLevel)
               ->bind(3, $responsibilities)
               ->execute();
        }
        
        echo json_encode(['success' => true, 'message' => 'Admin profile updated successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update admin profile: ' . $e->getMessage()]);
    }
}

function changePassword($db, $userId) {
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        echo json_encode(['success' => false, 'message' => 'All password fields are required']);
        return;
    }
    
    if ($newPassword !== $confirmPassword) {
        echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
        return;
    }
    
    if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters']);
        return;
    }
    
    // Verify current password
    $user = $db->single("SELECT password_hash FROM users WHERE user_id = ?", [$userId]);
    if (!password_verify($currentPassword, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        return;
    }
    
    try {
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $db->query("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE user_id = ?")
           ->bind(1, $newHash)
           ->bind(2, $userId)
           ->execute();
        
        echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to change password: ' . $e->getMessage()]);
    }
}

function updateProfileImage($db, $userId) {
    if (!isset($_FILES['profileImage']) || $_FILES['profileImage']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No image uploaded or upload error']);
        return;
    }
    
    $file = $_FILES['profileImage'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed']);
        return;
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        echo json_encode(['success' => false, 'message' => 'File size too large. Maximum 5MB allowed']);
        return;
    }
    
    // Create upload directory if not exists
    $uploadDir = UPLOAD_DIR . 'profiles/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Delete old profile image if exists
    $currentUser = $db->single("SELECT profile_img_url FROM users WHERE user_id = ?", [$userId]);
    if (!empty($currentUser['profile_img_url'])) {
        $oldFile = PROJECT_ROOT . '/public/' . $currentUser['profile_img_url'];
        if (file_exists($oldFile)) {
            unlink($oldFile);
        }
    }
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $relativeUrl = 'uploads/profiles/' . $filename;
        
        try {
            $db->query("UPDATE users SET profile_img_url = ?, updated_at = NOW() WHERE user_id = ?")
               ->bind(1, $relativeUrl)
               ->bind(2, $userId)
               ->execute();
            
            // Update session
            $_SESSION['profile_img_url'] = $relativeUrl;
            
            global $base_url;
            echo json_encode([
                'success' => true, 
                'message' => 'Profile image updated successfully',
                'imageUrl' => $base_url . 'public/' . $relativeUrl
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to update profile image: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
    }
}

function updatePreferences($db, $userId) {
    $weatherAlerts = isset($_POST['weatherAlerts']) ? intval($_POST['weatherAlerts']) : 0;
    $diseaseAlerts = isset($_POST['diseaseAlerts']) ? intval($_POST['diseaseAlerts']) : 0;
    $marketAlerts = isset($_POST['marketAlerts']) ? intval($_POST['marketAlerts']) : 0;
    $communityAlerts = isset($_POST['communityAlerts']) ? intval($_POST['communityAlerts']) : 0;
    $emailNotifications = isset($_POST['emailNotifications']) ? intval($_POST['emailNotifications']) : 0;
    $smsNotifications = isset($_POST['smsNotifications']) ? intval($_POST['smsNotifications']) : 0;
    $pushNotifications = isset($_POST['pushNotifications']) ? intval($_POST['pushNotifications']) : 0;
    
    try {
        // Check if preferences exist
        $existing = $db->single("SELECT preference_id FROM notification_preferences WHERE user_id = ?", [$userId]);
        
        if ($existing) {
            // Update existing preferences
            $db->query("UPDATE notification_preferences SET 
                weather_alerts = ?, 
                disease_alerts = ?, 
                market_alerts = ?, 
                community_alerts = ?, 
                email_notifications = ?, 
                sms_notifications = ?, 
                push_notifications = ?,
                updated_at = NOW() 
                WHERE user_id = ?")
               ->bind(1, $weatherAlerts)
               ->bind(2, $diseaseAlerts)
               ->bind(3, $marketAlerts)
               ->bind(4, $communityAlerts)
               ->bind(5, $emailNotifications)
               ->bind(6, $smsNotifications)
               ->bind(7, $pushNotifications)
               ->bind(8, $userId)
               ->execute();
        } else {
            // Insert new preferences
            $db->query("INSERT INTO notification_preferences (user_id, weather_alerts, disease_alerts, market_alerts, community_alerts, email_notifications, sms_notifications, push_notifications, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())")
               ->bind(1, $userId)
               ->bind(2, $weatherAlerts)
               ->bind(3, $diseaseAlerts)
               ->bind(4, $marketAlerts)
               ->bind(5, $communityAlerts)
               ->bind(6, $emailNotifications)
               ->bind(7, $smsNotifications)
               ->bind(8, $pushNotifications)
               ->execute();
        }
        
        echo json_encode(['success' => true, 'message' => 'Preferences saved successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to save preferences: ' . $e->getMessage()]);
    }
}

function deleteAccount($db, $userId) {
    $password = $_POST['password'] ?? '';
    
    if (empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Password is required to delete your account']);
        return;
    }
    
    // Verify password
    $user = $db->single("SELECT password_hash, role, profile_img_url FROM users WHERE user_id = ?", [$userId]);
    if (!$user || !password_verify($password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Incorrect password. Account deletion cancelled.']);
        return;
    }
    
    // Prevent admin from deleting their account (optional security measure)
    if ($user['role'] === 'admin') {
        // Check if this is the last admin
        $adminCount = $db->single("SELECT COUNT(*) as count FROM users WHERE role = 'admin'", [])['count'] ?? 0;
        if ($adminCount <= 1) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete the only admin account. Please create another admin first.']);
            return;
        }
    }
    
    try {
        // Delete profile image if exists
        if (!empty($user['profile_img_url'])) {
            $imagePath = PROJECT_ROOT . '/public/' . $user['profile_img_url'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        // Delete related data (order matters due to foreign keys)
        // Delete notification preferences
        $db->query("DELETE FROM notification_preferences WHERE user_id = ?")->bind(1, $userId)->execute();
        
        // Delete role-specific profile
        $db->query("DELETE FROM farmer_profiles WHERE user_id = ?")->bind(1, $userId)->execute();
        $db->query("DELETE FROM officer_profiles WHERE user_id = ?")->bind(1, $userId)->execute();
        $db->query("DELETE FROM admin_profiles WHERE user_id = ?")->bind(1, $userId)->execute();
        
        // Delete alerts
        $db->query("DELETE FROM alerts WHERE user_id = ?")->bind(1, $userId)->execute();
        
        // Delete chat messages (both sent and received)
        $db->query("DELETE FROM chat_messages WHERE sender_id = ? OR receiver_id = ?")->bind(1, $userId)->bind(2, $userId)->execute();
        
        // Delete community posts and likes
        $db->query("DELETE FROM post_likes WHERE user_id = ?")->bind(1, $userId)->execute();
        $db->query("DELETE FROM community_comments WHERE user_id = ?")->bind(1, $userId)->execute();
        $db->query("DELETE FROM community_posts WHERE user_id = ?")->bind(1, $userId)->execute();
        
        // Delete crop data
        $db->query("DELETE FROM crop_data WHERE farmer_id = ?")->bind(1, $userId)->execute();
        
        // Delete disease reports
        $db->query("DELETE FROM disease_reports WHERE user_id = ?")->bind(1, $userId)->execute();
        
        // Delete AI chat logs
        $db->query("DELETE FROM ai_chat_logs WHERE user_id = ?")->bind(1, $userId)->execute();
        
        // Delete AI recommendations
        $db->query("DELETE FROM ai_recommendations WHERE user_id = ?")->bind(1, $userId)->execute();
        
        // Finally, delete the user
        $db->query("DELETE FROM users WHERE user_id = ?")->bind(1, $userId)->execute();
        
        // Destroy session
        session_destroy();
        
        echo json_encode(['success' => true, 'message' => 'Your account has been permanently deleted.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete account: ' . $e->getMessage()]);
    }
}

/**
 * Contact an officer (for farmers)
 */
function contactOfficer($db, $userId) {
    $officerId = intval($_POST['officerId'] ?? 0);
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($officerId) || empty($subject) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Officer, subject, and message are required']);
        return;
    }
    
    // Verify the officer exists
    $officer = $db->single("SELECT * FROM users WHERE user_id = ? AND role = 'officer'", [$officerId]);
    if (!$officer) {
        echo json_encode(['success' => false, 'message' => 'Officer not found']);
        return;
    }
    
    // Get farmer info
    $farmer = $db->single("SELECT * FROM users WHERE user_id = ?", [$userId]);
    
    try {
        // Check if messages table exists
        $tableExists = $db->single("SHOW TABLES LIKE 'messages'");
        
        if ($tableExists) {
            // Insert into messages table
            $db->query("INSERT INTO messages (sender_id, receiver_id, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())", 
                [$userId, $officerId, $subject, $message]);
        } else {
            // Create as an alert to the officer
            $alertTitle = "Message from Farmer: " . $subject;
            $alertMessage = "From: " . $farmer['first_name'] . " " . ($farmer['last_name'] ?? '') . "\n\n" . $message;
            
            $db->query("INSERT INTO alerts (alert_type, title, message, priority, target_user_id, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())",
                ['message', $alertTitle, $alertMessage, 'medium', $officerId, $userId]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Your message has been sent to the officer']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to send message: ' . $e->getMessage()]);
    }
}
