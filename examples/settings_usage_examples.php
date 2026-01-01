<?php
/**
 * Settings Usage Examples
 * Demonstrates how to use settings throughout the application
 */

require_once __DIR__ . '/../config/config.php';

// Example 1: Get a single setting
$siteName = getSetting('site_name', 'Default Site Name');
echo "Site Name: $siteName\n\n";

// Example 2: Check maintenance mode
if (isMaintenanceMode()) {
    if (!isAllowedDuringMaintenance()) {
        echo "Site is in maintenance mode!\n";
        exit;
    } else {
        echo "Site is in maintenance mode, but your IP is whitelisted\n\n";
    }
}

// Example 3: Password validation
$testPassword = "Test123";
$validation = validatePassword($testPassword);

if ($validation['valid']) {
    echo "Password is valid!\n\n";
} else {
    echo "Password validation errors:\n";
    foreach ($validation['errors'] as $error) {
        echo "- $error\n";
    }
    echo "\n";
}

// Example 4: Get all settings
$allSettings = getAllSettings();
echo "Total settings: " . count($allSettings) . "\n\n";

// Example 5: Display common settings
echo "=== Common Settings ===\n";
echo "Site Name: " . getSiteName() . "\n";
echo "Timezone: " . getSiteTimezone() . "\n";
echo "2FA Required: " . (is2FARequired() ? 'Yes' : 'No') . "\n";
echo "Session Timeout: " . getSessionTimeout() . " minutes\n";
echo "Max Failed Logins: " . getMaxFailedLogins() . "\n";
echo "Password Min Length: " . getPasswordMinLength() . "\n";
echo "Items Per Page: " . getItemsPerPage() . "\n\n";

// Example 6: Update a setting (in actual use, check permissions first)
$userId = $_SESSION['user_id'] ?? 1;
$updated = updateSetting('test_setting', 'test_value', $userId);
echo "Setting updated: " . ($updated ? 'Success' : 'Failed') . "\n\n";

// Example 7: Using settings in templates
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo getSiteName(); ?></title>
</head>
<body>
    <h1>Welcome to <?php echo getSiteName(); ?></h1>
    
    <?php if (isMaintenanceMode()): ?>
        <div class="maintenance-notice">
            Site is currently under maintenance
        </div>
    <?php endif; ?>
    
    <p>Current timezone: <?php echo getSiteTimezone(); ?></p>
</body>
</html>

<?php
// Example 8: Using settings in login logic
/*
function handleLogin($username, $password) {
    $maxAttempts = getMaxFailedLogins();
    $lockoutDuration = getSetting('lockout_duration', 15);
    
    // Check login attempts...
    $attempts = getLoginAttempts($username);
    
    if ($attempts >= $maxAttempts) {
        return ['error' => "Account locked for $lockoutDuration minutes"];
    }
    
    // Verify password...
    if (verifyPassword($password, $hashedPassword)) {
        // Success - check if 2FA required
        if (is2FARequired()) {
            return ['requires_2fa' => true];
        }
        return ['success' => true];
    }
    
    return ['error' => 'Invalid credentials'];
}
*/

// Example 9: Using settings for pagination
/*
function getUsers($page = 1) {
    $perPage = getItemsPerPage();
    $offset = ($page - 1) * $perPage;
    
    // Query users with pagination...
    $users = $db->resultSet("SELECT * FROM users LIMIT $perPage OFFSET $offset");
    
    return $users;
}
*/

// Example 10: Check settings at runtime
echo "=== Runtime Setting Checks ===\n";

// Check password policy
echo "Password Policy:\n";
echo "- Min Length: " . getPasswordMinLength() . "\n";
echo "- Requires Mixed Case: " . (isPasswordMixedCaseRequired() ? 'Yes' : 'No') . "\n";
echo "- Requires Numbers: " . (isPasswordNumbersRequired() ? 'Yes' : 'No') . "\n";
?>
