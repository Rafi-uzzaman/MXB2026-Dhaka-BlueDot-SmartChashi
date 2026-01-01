<?php
// Save SMS settings

$db = new Database();
$user = getCurrentUser();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$phoneNumber = trim($input['phone_number'] ?? $user['phone'] ?? '');
$rainAlert = isset($input['rain_alert']) ? ($input['rain_alert'] ? 1 : 0) : 1;
$tempAlert = isset($input['temp_alert']) ? ($input['temp_alert'] ? 1 : 0) : 1;
$stormAlert = isset($input['storm_alert']) ? ($input['storm_alert'] ? 1 : 0) : 0;
$pestAlert = isset($input['pest_alert']) ? ($input['pest_alert'] ? 1 : 0) : 0;
$isActive = isset($input['is_active']) ? ($input['is_active'] ? 1 : 0) : 1;

// Format phone number
if ($phoneNumber && !str_starts_with($phoneNumber, '+880')) {
    $phoneNumber = '+880' . ltrim($phoneNumber, '0');
}

try {
    // Check if settings already exist
    $existing = $db->single("SELECT * FROM sms_settings WHERE user_id = ?", [$user['user_id']]);
    
    if ($existing) {
        // Update existing settings
        $sql = "UPDATE sms_settings SET phone_number = ?, rain_alert = ?, temp_alert = ?, storm_alert = ?, pest_alert = ?, is_active = ? WHERE user_id = ?";
        $result = $db->query($sql, [
            $phoneNumber,
            $rainAlert,
            $tempAlert,
            $stormAlert,
            $pestAlert,
            $isActive,
            $user['user_id']
        ]);
    } else {
        // Insert new settings
        $sql = "INSERT INTO sms_settings (user_id, phone_number, rain_alert, temp_alert, storm_alert, pest_alert, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $result = $db->query($sql, [
            $user['user_id'],
            $phoneNumber,
            $rainAlert,
            $tempAlert,
            $stormAlert,
            $pestAlert,
            $isActive
        ]);
    }
    
    if ($result) {
        // Fetch updated settings
        $settings = $db->single("SELECT * FROM sms_settings WHERE user_id = ?", [$user['user_id']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'SMS settings saved successfully',
            'settings' => $settings
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save SMS settings']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
