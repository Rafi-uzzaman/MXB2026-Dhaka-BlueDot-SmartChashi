<?php
/**
 * Admin Settings
 * System configuration and preferences
 */
$currPage = "Settings";
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../layouts/admin-header.php';

// Get active section
$activeSection = $_GET['section'] ?? 'general';

// Load settings from database with all details
$settingsRows = $db->resultSet("SELECT setting_id, setting_key, setting_value, setting_type, setting_group, 
    description, is_sensitive, updated_by, updated_at FROM admin_settings ORDER BY setting_group, setting_key");

$settings = [];
$settingsByGroup = [];

foreach ($settingsRows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
    if (!isset($settingsByGroup[$row['setting_group']])) {
        $settingsByGroup[$row['setting_group']] = [];
    }
    $settingsByGroup[$row['setting_group']][] = $row;
}

// Get last modified info
$lastModified = $db->single("SELECT u.first_name, u.last_name, s.updated_at 
    FROM admin_settings s 
    LEFT JOIN users u ON s.updated_by = u.user_id 
    ORDER BY s.updated_at DESC LIMIT 1");

$lastModifiedBy = !empty($lastModified['first_name']) 
    ? $lastModified['first_name'] . ' ' . $lastModified['last_name']
    : 'System';
$lastModifiedTime = !empty($lastModified['updated_at']) 
    ? date('M d, Y H:i', strtotime($lastModified['updated_at']))
    : 'Never';
?>

<div class="page-header">
    <div class="page-header-content">
        <div>
            <h1 class="page-title">Settings</h1>
            <p class="page-subtitle">Configure system preferences and security options</p>
        </div>
        <div class="page-meta">
            <span class="meta-item">
                <span class="material-icons">person</span>
                Last modified by: <strong><?php echo htmlspecialchars($lastModifiedBy); ?></strong>
            </span>
            <span class="meta-item">
                <span class="material-icons">schedule</span>
                <strong><?php echo $lastModifiedTime; ?></strong>
            </span>
        </div>
    </div>
</div>

<div class="settings-layout">
    <!-- Settings Navigation -->
    <nav class="settings-nav">
        <a href="?section=profile" class="settings-nav-item <?php echo $activeSection === 'profile' ? 'active' : ''; ?>">
            <span class="material-icons">person</span>
            My Profile
        </a>
        <a href="?section=general" class="settings-nav-item <?php echo $activeSection === 'general' ? 'active' : ''; ?>">
            <span class="material-icons">settings</span>
            General
        </a>
        <a href="?section=security" class="settings-nav-item <?php echo $activeSection === 'security' ? 'active' : ''; ?>">
            <span class="material-icons">security</span>
            Security
        </a>
        <a href="?section=maintenance" class="settings-nav-item <?php echo $activeSection === 'maintenance' ? 'active' : ''; ?>">
            <span class="material-icons">engineering</span>
            Maintenance
        </a>
    </nav>

    <!-- Settings Content -->
    <div class="settings-content">
        <!-- Profile Settings -->
        <div class="settings-section <?php echo $activeSection === 'profile' ? 'active' : ''; ?>" id="section-profile">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="material-icons">person</span>
                        My Profile
                    </h3>
                </div>
                <div class="card-body">
                    <form id="profileForm" class="settings-form">
                        <div class="profile-section">
                            <div class="profile-avatar-section">
                                <div class="current-avatar">
                                    <?php if (!empty($adminUser['profile_img_url'])): ?>
                                        <img src="<?php echo $base_url . 'public/' . $adminUser['profile_img_url']; ?>" alt="Profile" id="profilePreview">
                                    <?php else: ?>
                                        <div class="avatar-placeholder" id="profilePreview">
                                            <?php echo strtoupper(substr($adminUser['first_name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="avatar-actions">
                                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('profileImageInput').click()">
                                        <span class="material-icons">upload</span>
                                        Upload Photo
                                    </button>
                                    <input type="file" id="profileImageInput" name="profile_image" accept="image/*" style="display: none;">
                                </div>
                            </div>
                        </div>
                        
                        <h4 class="settings-subtitle">Personal Information</h4>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">First Name</label>
                                <input type="text" name="first_name" class="form-input" value="<?php echo htmlspecialchars($adminUser['first_name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" class="form-input" value="<?php echo htmlspecialchars($adminUser['last_name'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($adminUser['email'] ?? ''); ?>" required>
                            <span class="form-hint">This will be your login email</span>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-input" value="<?php echo htmlspecialchars($adminUser['phone'] ?? ''); ?>">
                        </div>
                        
                        <h4 class="settings-subtitle">Change Password</h4>
                        <p class="form-hint">Leave blank to keep current password</p>
                        
                        <div class="form-group">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-input">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-input">
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- General Settings -->
        <div class="settings-section <?php echo $activeSection === 'general' ? 'active' : ''; ?>" id="section-general">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="material-icons">settings</span>
                        General Settings
                    </h3>
                </div>
                <div class="card-body">
                    <form id="generalForm" class="settings-form" data-setting="true">
                        <div class="form-group">
                            <label class="form-label">Site Name</label>
                            <input type="text" name="site_name" class="form-input" data-setting="true">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Site Description</label>
                            <textarea name="site_description" class="form-input" rows="3" data-setting="true"></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Default Language</label>
                                <select name="default_language" class="form-input" data-setting="true">
                                    <option value="en">English</option>
                                    <option value="bn">Bengali</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Timezone</label>
                                <select name="timezone" class="form-input" data-setting="true">
                                    <option value="Asia/Dhaka">Asia/Dhaka</option>
                                    <option value="UTC">UTC</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Items Per Page</label>
                            <input type="number" name="items_per_page" class="form-input" value="20" min="10" max="100" data-setting="true">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Security Settings -->
        <div class="settings-section <?php echo $activeSection === 'security' ? 'active' : ''; ?>" id="section-security">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="material-icons">security</span>
                        Security Settings
                    </h3>
                </div>
                <div class="card-body">
                    <form id="securityForm" class="settings-form">
                        <h4 class="settings-subtitle">Authentication</h4>
                        
                        <div class="form-group">
                            <label class="toggle-setting">
                                <input type="checkbox" name="require_2fa" value="1" <?php echo ($settings['require_2fa'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                                <span class="toggle-label">
                                    <strong>Require 2FA for Admin Login</strong>
                                    <span>All admins must verify with email code</span>
                                </span>
                            </label>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Session Timeout (minutes)</label>
                                <input type="number" name="session_timeout" class="form-input" value="<?php echo $settings['session_timeout'] ?? 30; ?>" min="5" max="1440">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Max Failed Logins</label>
                                <input type="number" name="max_failed_logins" class="form-input" value="<?php echo $settings['max_failed_logins'] ?? 5; ?>" min="3" max="20">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Lockout Duration (minutes)</label>
                            <input type="number" name="lockout_duration" class="form-input" value="<?php echo $settings['lockout_duration'] ?? 15; ?>" min="5" max="60">
                        </div>
                        
                        <h4 class="settings-subtitle">Password Policy</h4>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Minimum Length</label>
                                <input type="number" name="password_min_length" class="form-input" value="<?php echo $settings['password_min_length'] ?? 8; ?>" min="6" max="32">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Password Expiry (days, 0=never)</label>
                                <input type="number" name="password_expiry_days" class="form-input" value="<?php echo $settings['password_expiry_days'] ?? 0; ?>" min="0" max="365">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="toggle-setting">
                                <input type="checkbox" name="require_mixed_case" value="1" <?php echo ($settings['require_mixed_case'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                                <span class="toggle-label">
                                    <strong>Require Mixed Case</strong>
                                    <span>Passwords must contain upper and lower case</span>
                                </span>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="toggle-setting">
                                <input type="checkbox" name="require_numbers" value="1" <?php echo ($settings['require_numbers'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                                <span class="toggle-label">
                                    <strong>Require Numbers</strong>
                                    <span>Passwords must contain at least one number</span>
                                </span>
                            </label>
                        </div>
                        
                        <h4 class="settings-subtitle">Rate Limiting</h4>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">API Rate Limit (requests/min)</label>
                                <input type="number" name="api_rate_limit" class="form-input" value="<?php echo $settings['api_rate_limit'] ?? 100; ?>" min="10" max="1000">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Login Rate Limit (attempts/min)</label>
                                <input type="number" name="login_rate_limit" class="form-input" value="<?php echo $settings['login_rate_limit'] ?? 10; ?>" min="3" max="30">
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save Security Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Maintenance Settings -->
        <div class="settings-section <?php echo $activeSection === 'maintenance' ? 'active' : ''; ?>" id="section-maintenance">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="material-icons">engineering</span>
                        Maintenance Mode
                    </h3>
                </div>
                <div class="card-body">
                    <form id="maintenanceForm" class="settings-form">
                        <div class="maintenance-toggle">
                            <div class="maintenance-status <?php echo ($settings['maintenance_mode'] ?? '0') === '1' ? 'enabled' : ''; ?>">
                                <span class="material-icons"><?php echo ($settings['maintenance_mode'] ?? '0') === '1' ? 'build' : 'check_circle'; ?></span>
                                <span><?php echo ($settings['maintenance_mode'] ?? '0') === '1' ? 'Maintenance Mode Active' : 'Site is Online'; ?></span>
                            </div>
                            <label class="toggle-setting large">
                                <input type="checkbox" name="maintenance_mode" value="1" <?php echo ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Maintenance Message</label>
                            <textarea name="maintenance_message" class="form-input" rows="4"><?php echo htmlspecialchars($settings['maintenance_message'] ?? 'We are currently performing scheduled maintenance. Please check back soon.'); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Allowed IPs (one per line)</label>
                            <textarea name="maintenance_allowed_ips" class="form-input" rows="4" placeholder="Enter IP addresses that can access the site during maintenance"><?php echo htmlspecialchars($settings['maintenance_allowed_ips'] ?? ''); ?></textarea>
                            <span class="form-hint">Your current IP: <?php echo $_SERVER['REMOTE_ADDR']; ?></span>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save Maintenance Settings</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="material-icons">delete_sweep</span>
                        Data Cleanup
                    </h3>
                </div>
                <div class="card-body">
                    <div class="cleanup-actions">
                        <div class="cleanup-item">
                            <div class="cleanup-info">
                                <strong>Clear Cache</strong>
                                <span>Remove all cached data and temporary files</span>
                            </div>
                            <button class="btn btn-secondary" onclick="clearCache()">Clear Cache</button>
                        </div>
                        <div class="cleanup-item">
                            <div class="cleanup-info">
                                <strong>Clear Old Sessions</strong>
                                <span>Remove expired user sessions from database</span>
                            </div>
                            <button class="btn btn-secondary" onclick="clearSessions()">Clear Sessions</button>
                        </div>
                        <div class="cleanup-item">
                            <div class="cleanup-info">
                                <strong>Clear Old Logs</strong>
                                <span>Remove logs older than 90 days</span>
                            </div>
                            <button class="btn btn-secondary" onclick="clearLogs()">Clear Logs</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Page Header */
.page-header {
    margin-bottom: 30px;
}

.page-header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.page-title {
    font-size: 28px;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 8px 0;
}

.page-subtitle {
    font-size: 14px;
    color: var(--text-muted);
    margin: 0;
}

.page-meta {
    display: flex;
    gap: 20px;
    font-size: 13px;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--text-muted);
}

.meta-item .material-icons {
    font-size: 16px;
}

.meta-item strong {
    color: var(--text-primary);
}

/* Profile Section */
.profile-section {
    margin-bottom: 30px;
}

.profile-avatar-section {
    display: flex;
    align-items: center;
    gap: 24px;
    padding: 24px;
    background: var(--bg-tertiary);
    border-radius: var(--border-radius);
    margin-bottom: 30px;
}

.current-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    overflow: hidden;
    border: 4px solid var(--border);
}

.current-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--primary);
    color: white;
    font-size: 48px;
    font-weight: 600;
}

.avatar-actions {
    flex: 1;
}

.avatar-actions p {
    margin: 0 0 16px 0;
    color: var(--text-muted);
    font-size: 14px;
}

/* Settings Layout */
.settings-layout {
    display: grid;
    grid-template-columns: 240px 1fr;
    gap: 24px;
}

.settings-nav {
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: var(--border-radius);
    padding: 8px;
    height: fit-content;
    position: sticky;
    top: 88px;
}

.settings-nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    border-radius: var(--border-radius);
    color: var(--text-secondary);
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s;
    cursor: pointer;
}

.settings-nav-item:hover {
    background: var(--bg-tertiary);
    color: var(--text-primary);
}

.settings-nav-item.active {
    background: var(--primary);
    color: white;
}

.settings-nav-item .material-icons {
    font-size: 20px;
}

/* Settings Content */
.settings-content {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.settings-section {
    display: none;
}

.settings-section.active {
    display: block;
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.settings-section .card {
    margin-bottom: 20px;
}

/* Cards */
.card {
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.card-header {
    padding: 20px;
    background: var(--bg-tertiary);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.card-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 16px;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.card-title .material-icons {
    font-size: 20px;
    color: var(--primary);
}

.card-body {
    padding: 20px;
}

.card-body.no-padding {
    padding: 0;
}

/* Form Styles */
.settings-form {
    max-width: 100%;
}

.form-group {
    margin-bottom: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 8px;
}

.form-input {
    width: 100%;
    padding: 10px 12px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border);
    border-radius: 6px;
    font-size: 13px;
    color: var(--text-primary);
    font-family: inherit;
    transition: all 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.form-input::placeholder {
    color: var(--text-muted);
}

textarea.form-input {
    resize: vertical;
    min-height: 100px;
}

.form-hint {
    display: block;
    font-size: 12px;
    color: var(--text-muted);
    margin-top: 6px;
}

/* Settings Subtitle */
.settings-subtitle {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-primary);
    margin: 24px 0 16px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--border);
}

.settings-subtitle:first-child {
    margin-top: 0;
}

/* Toggle Setting */
.toggle-setting {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    padding: 16px;
    background: var(--bg-tertiary);
    border-radius: var(--border-radius);
    transition: all 0.2s;
}

.toggle-setting:hover {
    background: var(--bg-hover);
}

.toggle-setting input {
    display: none;
}

.toggle-setting .toggle-slider {
    position: relative;
    width: 48px;
    height: 26px;
    background: var(--border);
    border-radius: 26px;
    transition: all 0.3s;
    flex-shrink: 0;
}

.toggle-setting .toggle-slider::before {
    content: '';
    position: absolute;
    width: 22px;
    height: 22px;
    left: 2px;
    top: 2px;
    background: white;
    border-radius: 50%;
    transition: all 0.3s;
}

.toggle-setting input:checked + .toggle-slider {
    background: var(--secondary);
}

.toggle-setting input:checked + .toggle-slider::before {
    transform: translateX(22px);
}

.toggle-label {
    display: flex;
    flex-direction: column;
    flex: 1;
}

.toggle-label strong {
    font-size: 14px;
    color: var(--text-primary);
}

.toggle-label span {
    font-size: 12px;
    color: var(--text-muted);
}

/* Large Toggle */
.toggle-setting.large .toggle-slider {
    width: 60px;
    height: 32px;
}

.toggle-setting.large .toggle-slider::before {
    width: 28px;
    height: 28px;
}

.toggle-setting.large input:checked + .toggle-slider::before {
    transform: translateX(28px);
}

/* Maintenance Section */
.maintenance-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px;
    background: var(--bg-tertiary);
    border-radius: var(--border-radius);
    margin-bottom: 24px;
    gap: 20px;
}

.maintenance-status {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 16px;
    font-weight: 600;
    color: var(--secondary);
}

.maintenance-status .material-icons {
    font-size: 28px;
}

.maintenance-status.enabled {
    color: var(--warning);
}

/* Cleanup Actions */
.cleanup-actions {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.cleanup-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    background: var(--bg-tertiary);
    border-radius: var(--border-radius);
    gap: 16px;
}

.cleanup-info {
    flex: 1;
}

.cleanup-info strong {
    display: block;
    font-size: 14px;
    color: var(--text-primary);
    margin-bottom: 2px;
}

.cleanup-info span {
    font-size: 12px;
    color: var(--text-muted);
}

/* Input Group */
.input-group {
    display: flex;
    gap: 8px;
    align-items: center;
}

.input-group .form-input {
    flex: 1;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid var(--border);
    flex-wrap: wrap;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 16px;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.btn .material-icons {
    font-size: 18px;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: #5a67d8;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
}

.btn-secondary {
    background: var(--bg-tertiary);
    color: var(--text-primary);
    border: 1px solid var(--border);
}

.btn-secondary:hover {
    border-color: var(--primary);
    background: var(--bg-tertiary);
    color: var(--primary);
}

.btn-danger {
    background: var(--danger);
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

.btn-warning {
    background: var(--warning);
    color: #000;
    font-weight: 600;
}

.btn-warning:hover {
    background: #d97706;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}

/* Responsive */
@media (max-width: 1024px) {
    .settings-layout {
        grid-template-columns: 1fr;
    }
    
    .settings-nav {
        position: static;
        display: flex;
        overflow-x: auto;
        gap: 8px;
    }
    
    .settings-nav-item {
        white-space: nowrap;
    }
}

@media (max-width: 768px) {
    .page-header-content {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .page-meta {
        width: 100%;
        flex-wrap: wrap;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .settings-layout {
        gap: 16px;
    }
    
    .settings-nav {
        padding: 8px 0;
        display: flex;
        border: none;
        border-bottom: 1px solid var(--border);
        background: transparent;
    }
    
    .settings-nav-item {
        padding: 12px 16px;
        font-size: 13px;
    }
    
    .maintenance-toggle {
        flex-direction: column;
        text-align: center;
    }
    
    .cleanup-item {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .page-title {
        font-size: 20px;
    }
    
    .page-meta {
        gap: 12px;
        font-size: 12px;
    }
    
    .meta-item .material-icons {
        font-size: 14px;
    }
    
    .card-header {
        padding: 16px;
    }
    
    .card-body {
        padding: 16px;
    }
    
    .toggle-setting {
        padding: 12px;
    }
    
    .form-group {
        margin-bottom: 16px;
    }
}
</style>

<script>
// Settings Configuration
// Note: BASE_URL and CSRF_TOKEN are defined in admin-footer.php
const SETTINGS_API = (window.BASE_URL || '<?php echo $base_url; ?>') + 'admin-secure/ajax/settings.php';

/**
 * Get BASE_URL safely
 */
function getBaseUrl() {
    return window.BASE_URL || '<?php echo $base_url; ?>';
}

/**
 * Get CSRF_TOKEN safely
 */
function getCsrfToken() {
    return window.CSRF_TOKEN || '<?php echo $csrf_token; ?>';
}

/**
 * Load settings values into form fields
 */
function loadSettingsIntoForm(settings) {
    for (const [key, value] of Object.entries(settings)) {
        const input = document.querySelector(`[name="${key}"]`);
        if (!input) continue;
        
        if (input.type === 'checkbox') {
            input.checked = value === '1' || value === 'true' || value === true;
        } else if (input.tagName === 'SELECT') {
            const option = Array.from(input.options).find(opt => opt.value === value);
            if (option) input.value = value;
        } else {
            input.value = value;
        }
    }
}

/**
 * Save settings from a form
 */
async function saveSettings(form) {
    const formData = new FormData(form);
    const settings = {};
    
    // Collect all form inputs
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        const name = input.name;
        if (!name) return;
        
        if (input.type === 'checkbox') {
            settings[name] = input.checked ? '1' : '0';
        } else if (input.type !== 'button' && input.type !== 'submit') {
            settings[name] = input.value;
        }
    });
    
    try {
        const response = await fetch(SETTINGS_API, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'update_settings',
                csrf_token: getCsrfToken(),
                settings: JSON.stringify(settings)
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Settings saved successfully!', 'success');
            
            // Reload page after 1 second to reflect changes
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotification(data.message || 'Failed to save settings', 'error');
        }
        
        return data.success;
        
    } catch (error) {
        console.error('Save error:', error);
        showNotification('Error saving settings', 'error');
        return false;
    }
}

/**
 * Clear cache
 */
async function clearCache() {
    if (!confirm('Clear all cached data?')) return;
    
    showNotification('Clearing cache...', 'info');
    
    try {
        const response = await fetch(SETTINGS_API + '?action=clear_cache&csrf_token=' + getCsrfToken());
        const data = await response.json();
        
        showNotification(data.message || (data.success ? 'Cache cleared' : 'Failed'), data.success ? 'success' : 'error');
    } catch (error) {
        console.error('Clear cache error:', error);
        showNotification('Error clearing cache', 'error');
    }
}

/**
 * Clear old sessions
 */
async function clearSessions() {
    if (!confirm('Remove all expired sessions?')) return;
    
    showNotification('Clearing sessions...', 'info');
    
    try {
        const response = await fetch(SETTINGS_API + '?action=clear_sessions&csrf_token=' + getCsrfToken());
        const data = await response.json();
        
        showNotification(data.message || (data.success ? 'Sessions cleared' : 'Failed'), data.success ? 'success' : 'error');
    } catch (error) {
        console.error('Clear sessions error:', error);
        showNotification('Error clearing sessions', 'error');
    }
}

/**
 * Clear old logs
 */
async function clearLogs() {
    if (!confirm('Remove logs older than 90 days? This cannot be undone.')) return;
    
    showNotification('Clearing logs...', 'info');
    
    try {
        const response = await fetch(SETTINGS_API + '?action=clear_logs&csrf_token=' + getCsrfToken());
        const data = await response.json();
        
        showNotification(data.message || (data.success ? 'Logs cleared' : 'Failed'), data.success ? 'success' : 'error');
    } catch (error) {
        console.error('Clear logs error:', error);
        showNotification('Error clearing logs', 'error');
    }
}

/**
 * Show notification
 */
function showNotification(message, type = 'info') {
    // Try to use existing toast system
    if (typeof showToast === 'function') {
        showToast(message, type);
    } else {
        // Fallback to alert
        alert(message);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Settings page initialized');
    
    // Load current settings into forms
    const settings = <?php echo json_encode($settings); ?>;
    loadSettingsIntoForm(settings);
    
    // Handle profile image preview
    const profileImageInput = document.getElementById('profileImageInput');
    if (profileImageInput) {
        profileImageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('profilePreview');
                    if (preview.tagName === 'IMG') {
                        preview.src = e.target.result;
                    } else {
                        preview.outerHTML = '<img src="' + e.target.result + '" alt="Profile" id="profilePreview">';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Handle profile form submission
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update_profile');
            formData.append('csrf_token', getCsrfToken());
            
            // Validate password fields if provided
            const newPassword = formData.get('new_password');
            const confirmPassword = formData.get('confirm_password');
            
            if (newPassword || confirmPassword) {
                if (newPassword !== confirmPassword) {
                    showNotification('Passwords do not match', 'error');
                    return;
                }
                if (newPassword.length < 6) {
                    showNotification('Password must be at least 6 characters', 'error');
                    return;
                }
                if (!formData.get('current_password')) {
                    showNotification('Current password is required to change password', 'error');
                    return;
                }
            }
            
            try {
                const response = await fetch(BASE_URL + 'admin-secure/ajax/admin.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('Profile updated successfully!', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showNotification(data.message || 'Failed to update profile', 'error');
                }
            } catch (error) {
                console.error('Profile update error:', error);
                showNotification('Error updating profile', 'error');
            }
        });
    }
    
    // Handle form submissions
    const generalForm = document.getElementById('generalForm');
    if (generalForm) {
        generalForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            await saveSettings(this);
        });
    }
    
    const securityForm = document.getElementById('securityForm');
    if (securityForm) {
        securityForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            await saveSettings(this);
        });
    }
    
    const maintenanceForm = document.getElementById('maintenanceForm');
    if (maintenanceForm) {
        maintenanceForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            await saveSettings(this);
        });
    }
    
    // Update maintenance status display when toggle changes
    const maintenanceToggle = document.querySelector('[name="maintenance_mode"]');
    if (maintenanceToggle) {
        maintenanceToggle.addEventListener('change', function() {
            const statusDiv = document.querySelector('.maintenance-status');
            const icon = statusDiv?.querySelector('.material-icons');
            const text = statusDiv?.querySelector('span:last-child');
            
            if (this.checked) {
                statusDiv?.classList.add('enabled');
                if (icon) icon.textContent = 'build';
                if (text) text.textContent = 'Maintenance Mode Active';
            } else {
                statusDiv?.classList.remove('enabled');
                if (icon) icon.textContent = 'check_circle';
                if (text) text.textContent = 'Site is Online';
            }
        });
    }
});
</script>


<?php include __DIR__ . '/../layouts/admin-footer.php'; ?>
