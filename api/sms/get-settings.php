<?php
// Get SMS settings for the current user

$db = new Database();
$user = getCurrentUser();

try {
    $settings = $db->single("SELECT * FROM sms_settings WHERE user_id = ?", [$user['user_id']]);
    
    if ($settings) {
        echo json_encode([
            'success' => true,
            'settings' => $settings
        ]);
    } else {
        // Return default settings
        echo json_encode([
            'success' => true,
            'settings' => [
                'phone_number' => $user['phone'] ?? '',
                'rain_alert' => 1,
                'temp_alert' => 1,
                'storm_alert' => 0,
                'pest_alert' => 0,
                'is_active' => 1
            ],
            'is_default' => true
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
