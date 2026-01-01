<?php
/**
 * Create Demo Admin Account
 * Run this script once to create a demo admin user
 */

require_once __DIR__ . '/config/config.php';

$db = new Database();

// Demo admin credentials
$email = 'admin@smartcashi.com';
$password = 'Admin@123456'; // Change this!
$first_name = 'Admin';
$last_name = 'Demo';
$phone = '01700000000';

// Check if admin already exists
$existing = $db->single("SELECT * FROM users WHERE email = ?", [$email]);
if ($existing) {
    die("<h2 style='color: red;'>Admin user already exists with email: $email</h2>");
}

// Hash the password
$password_hash = password_hash($password, PASSWORD_BCRYPT);

// Create the admin user
try {
    $db->query("INSERT INTO users (email, phone, password_hash, first_name, last_name, profile_img_url, role, is_active, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
        ->bind(1, $email)
        ->bind(2, $phone)
        ->bind(3, $password_hash)
        ->bind(4, $first_name)
        ->bind(5, $last_name)
        ->bind(6, 'uploads/profiles/admin-default.jpg')
        ->bind(7, 'admin')
        ->bind(8, 1)
        ->bind(9, 1)
        ->execute();
    
    $user_id = $db->lastInsertId();
    
    // Create admin profile
    $db->query("INSERT INTO admin_profiles (user_id, access_level, department) VALUES (?, ?, ?)")
        ->bind(1, $user_id)
        ->bind(2, 'super_admin')
        ->bind(3, 'Administration')
        ->execute();
    
    echo "<h2 style='color: green;'>✓ Demo Admin Account Created Successfully!</h2>";
    echo "<div style='background: #f0f0f0; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>Admin Credentials:</h3>";
    echo "<p><strong>Email:</strong> $email</p>";
    echo "<p><strong>Password:</strong> $password</p>";
    echo "<p><strong>User ID:</strong> $user_id</p>";
    echo "<hr>";
    echo "<p style='color: red;'><strong>⚠️ IMPORTANT:</strong> Change the password immediately after first login!</p>";
    echo "<p><a href='index.php?page=admin-login' style='color: blue; text-decoration: underline;'>Go to Admin Login</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Error creating admin account:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
