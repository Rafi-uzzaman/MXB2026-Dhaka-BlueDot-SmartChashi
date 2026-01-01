<?php

if (!isLoggedIn()) {
    redirect('login');
}

include __DIR__ . '/../layouts/header.php';

$user = getCurrentUser();
$db = new Database();
$userRole = $user['role'];

// Get role-specific profile
$profile = null;
$officerProfile = null;
$adminProfile = null;

if ($userRole === 'farmer') {
    $profile = $db->single("SELECT * FROM farmer_profiles WHERE user_id = ?", [$_SESSION['user_id']]);
} elseif ($userRole === 'officer') {
    $officerProfile = $db->single("SELECT * FROM officer_profiles WHERE user_id = ?", [$_SESSION['user_id']]);
} elseif ($userRole === 'admin') {
    $adminProfile = $db->single("SELECT * FROM admin_profiles WHERE user_id = ?", [$_SESSION['user_id']]);
}

// Get user notification preferences
$preferences = $db->single("SELECT * FROM notification_preferences WHERE user_id = ?", [$_SESSION['user_id']]);
if (!$preferences) {
    // Create default preferences if not exists
    $db->query("INSERT INTO notification_preferences (user_id, weather_alerts, disease_alerts, market_alerts, community_alerts, email_notifications, sms_notifications, push_notifications) VALUES (?, 1, 1, 1, 1, 1, 0, 1)")
       ->bind(1, $_SESSION['user_id'])
       ->execute();
    $preferences = [
        'weather_alerts' => 1,
        'disease_alerts' => 1,
        'market_alerts' => 1,
        'community_alerts' => 1,
        'email_notifications' => 1,
        'sms_notifications' => 0,
        'push_notifications' => 1
    ];
}

// Get statistics based on role
$stats = [];
if ($userRole === 'farmer') {
    $stats['crops'] = $db->single("SELECT COUNT(*) as count FROM crop_data WHERE farmer_id = ?", [$_SESSION['user_id']])['count'] ?? 0;
    $stats['messages'] = $db->single("SELECT COUNT(*) as count FROM chat_messages WHERE sender_id = ?", [$_SESSION['user_id']])['count'] ?? 0;
    $stats['posts'] = $db->single("SELECT COUNT(*) as count FROM community_posts WHERE user_id = ?", [$_SESSION['user_id']])['count'] ?? 0;
} elseif ($userRole === 'officer') {
    $stats['farmers'] = $db->single("SELECT COUNT(*) as count FROM users WHERE role = 'farmer'", [])['count'] ?? 0;
    $stats['reports'] = $db->single("SELECT COUNT(*) as count FROM disease_reports WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)", [])['count'] ?? 0;
    $stats['alerts'] = $db->single("SELECT COUNT(*) as count FROM alerts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)", [])['count'] ?? 0;
} elseif ($userRole === 'admin') {
    $stats['total_users'] = $db->single("SELECT COUNT(*) as count FROM users", [])['count'] ?? 0;
    $stats['farmers'] = $db->single("SELECT COUNT(*) as count FROM users WHERE role = 'farmer'", [])['count'] ?? 0;
    $stats['officers'] = $db->single("SELECT COUNT(*) as count FROM users WHERE role = 'officer'", [])['count'] ?? 0;
    $stats['active_crops'] = $db->single("SELECT COUNT(*) as count FROM crop_data WHERE status = 'growing'", [])['count'] ?? 0;
}
?>

<section class="hero">
    <h1><span class="material-icons" style="vertical-align: middle; font-size: 2rem;">account_circle</span> <?php echo __('your_profile'); ?> - <?php echo ucfirst($userRole); ?></h1>
    <p><?php echo __('manage_account'); ?></p>
</section>

<div class="profile-page-grid">
        <!-- Profile Image Card -->
        <div class="card profile-card profile-image-card">
            <h2><span class="material-icons" style="vertical-align: middle;">photo_camera</span> <?php echo __('profile_photo'); ?></h2>
            <div class="profile-image-container">
                <div class="profile-image-preview">
                    <?php if (!empty($user['profile_img_url'])): ?>
                        <img src="<?php echo $base_url . 'public/' . $user['profile_img_url']; ?>" alt="Profile" id="profileImagePreview">
                    <?php else: ?>
                        <span class="material-icons default-avatar">account_circle</span>
                    <?php endif; ?>
                </div>
                <form id="profileImageForm" enctype="multipart/form-data" class="profile-image-form">
                    <input type="hidden" name="action" value="update_profile_image">
                    <label for="profileImageInput" class="btn btn-secondary btn-small">
                        <span class="material-icons" style="font-size: 16px; vertical-align: middle;">upload</span>
                        <?php echo __('change_photo'); ?>
                    </label>
                    <input type="file" id="profileImageInput" name="profileImage" accept="image/*" style="display: none;">
                    <p class="help-text">JPG, PNG, GIF or WEBP. Max 5MB</p>
                </form>
            </div>
        </div>

        <!-- Personal Information -->
        <div class="card profile-card">
            <h2><span class="material-icons" style="vertical-align: middle;">person</span> <?php echo __('personal_information'); ?></h2>
            <form id="personalInfoForm" class="profile-form">
                <input type="hidden" name="action" value="update_personal">
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstName"><?php echo __('first_name'); ?></label>
                        <input type="text" id="firstName" name="firstName" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="lastName"><?php echo __('last_name'); ?></label>
                        <input type="text" id="lastName" name="lastName" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email"><?php echo __('email'); ?></label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone"><?php echo __('phone'); ?></label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="role"><?php echo __('account_type'); ?></label>
                    <input type="text" id="role" name="role" value="<?php echo ucfirst($user['role']); ?>" disabled>
                </div>

                <button type="submit" class="btn"><?php echo __('update_profile'); ?></button>
            </form>
        </div>

        <!-- Role-Specific Profile -->
        <?php if ($userRole === 'farmer'): ?>
        <div class="card profile-card">
            <h2><span class="material-icons" style="vertical-align: middle;">agriculture</span> <?php echo __('farming_profile'); ?></h2>
            <form id="farmerProfileForm" class="profile-form">
                <input type="hidden" name="action" value="update_farmer_profile">
                <div class="form-group">
                    <label for="landSize"><?php echo __('land_size'); ?></label>
                    <input type="number" id="landSize" name="landSize" value="<?php echo $profile['land_size_hectares'] ?? ''; ?>" step="0.01">
                </div>

                <div class="form-group">
                    <label for="experience"><?php echo __('experience_level'); ?></label>
                    <select id="experience" name="experience">
                        <option value=""><?php echo __('select'); ?></option>
                        <option value="beginner" <?php echo ($profile['experience_level'] ?? '') === 'beginner' ? 'selected' : ''; ?>><?php echo __('beginner'); ?></option>
                        <option value="intermediate" <?php echo ($profile['experience_level'] ?? '') === 'intermediate' ? 'selected' : ''; ?>><?php echo __('intermediate'); ?></option>
                        <option value="advanced" <?php echo ($profile['experience_level'] ?? '') === 'advanced' ? 'selected' : ''; ?>><?php echo __('advanced'); ?></option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="primaryCrop"><?php echo __('primary_crop'); ?></label>
                    <input type="text" id="primaryCrop" name="primaryCrop" placeholder="e.g., Rice, Wheat" value="<?php echo htmlspecialchars($profile['primary_crops'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="region"><?php echo __('region_district'); ?></label>
                    <select id="region" name="region">
                        <option value=""><?php echo __('select'); ?></option>
                        <option value="Dhaka" <?php echo ($profile['region'] ?? '') === 'Dhaka' ? 'selected' : ''; ?>>Dhaka</option>
                        <option value="Chittagong" <?php echo ($profile['region'] ?? '') === 'Chittagong' ? 'selected' : ''; ?>>Chittagong</option>
                        <option value="Khulna" <?php echo ($profile['region'] ?? '') === 'Khulna' ? 'selected' : ''; ?>>Khulna</option>
                        <option value="Rangpur" <?php echo ($profile['region'] ?? '') === 'Rangpur' ? 'selected' : ''; ?>>Rangpur</option>
                        <option value="Sylhet" <?php echo ($profile['region'] ?? '') === 'Sylhet' ? 'selected' : ''; ?>>Sylhet</option>
                        <option value="Barisal" <?php echo ($profile['region'] ?? '') === 'Barisal' ? 'selected' : ''; ?>>Barisal</option>
                        <option value="Rajshahi" <?php echo ($profile['region'] ?? '') === 'Rajshahi' ? 'selected' : ''; ?>>Rajshahi</option>
                        <option value="Mymensingh" <?php echo ($profile['region'] ?? '') === 'Mymensingh' ? 'selected' : ''; ?>>Mymensingh</option>
                    </select>
                </div>

                <button type="submit" class="btn"><?php echo __('update_farming_info'); ?></button>
            </form>
        </div>
        <?php elseif ($userRole === 'officer'): ?>
        <div class="card profile-card">
            <h2><span class="material-icons" style="vertical-align: middle;">badge</span> Officer Profile</h2>
            <form id="officerProfileForm" class="profile-form">
                <input type="hidden" name="action" value="update_officer_profile">
                <div class="form-group">
                    <label for="officerRegion">Assigned Region</label>
                    <select id="officerRegion" name="officerRegion">
                        <option value="">Select Region</option>
                        <option value="Dhaka" <?php echo ($officerProfile['region'] ?? '') === 'Dhaka' ? 'selected' : ''; ?>>Dhaka</option>
                        <option value="Chittagong" <?php echo ($officerProfile['region'] ?? '') === 'Chittagong' ? 'selected' : ''; ?>>Chittagong</option>
                        <option value="Khulna" <?php echo ($officerProfile['region'] ?? '') === 'Khulna' ? 'selected' : ''; ?>>Khulna</option>
                        <option value="Rangpur" <?php echo ($officerProfile['region'] ?? '') === 'Rangpur' ? 'selected' : ''; ?>>Rangpur</option>
                        <option value="Sylhet" <?php echo ($officerProfile['region'] ?? '') === 'Sylhet' ? 'selected' : ''; ?>>Sylhet</option>
                        <option value="Barisal" <?php echo ($officerProfile['region'] ?? '') === 'Barisal' ? 'selected' : ''; ?>>Barisal</option>
                        <option value="Rajshahi" <?php echo ($officerProfile['region'] ?? '') === 'Rajshahi' ? 'selected' : ''; ?>>Rajshahi</option>
                        <option value="Mymensingh" <?php echo ($officerProfile['region'] ?? '') === 'Mymensingh' ? 'selected' : ''; ?>>Mymensingh</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="officerId">Officer ID</label>
                    <input type="text" id="officerId" name="officerId" value="AGR-<?php echo str_pad($user['user_id'], 6, '0', STR_PAD_LEFT); ?>" disabled>
                </div>

                <div class="form-group">
                    <label for="specialization">Specialization</label>
                    <select id="specialization" name="specialization">
                        <option value="">Select Specialization</option>
                        <option value="crop_management" <?php echo ($officerProfile['expertise_area'] ?? '') === 'crop_management' ? 'selected' : ''; ?>>Crop Management</option>
                        <option value="pest_control" <?php echo ($officerProfile['expertise_area'] ?? '') === 'pest_control' ? 'selected' : ''; ?>>Pest Control</option>
                        <option value="soil_health" <?php echo ($officerProfile['expertise_area'] ?? '') === 'soil_health' ? 'selected' : ''; ?>>Soil Health</option>
                        <option value="irrigation" <?php echo ($officerProfile['expertise_area'] ?? '') === 'irrigation' ? 'selected' : ''; ?>>Irrigation Systems</option>
                        <option value="organic_farming" <?php echo ($officerProfile['expertise_area'] ?? '') === 'organic_farming' ? 'selected' : ''; ?>>Organic Farming</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="department">Department</label>
                    <input type="text" id="department" name="department" placeholder="e.g., Department of Agriculture Extension" value="<?php echo htmlspecialchars($officerProfile['department'] ?? ''); ?>">
                </div>

                <button type="submit" class="btn">Update Officer Info</button>
            </form>
        </div>
        <?php elseif ($userRole === 'admin'): ?>
        <div class="card profile-card">
            <h2><span class="material-icons" style="vertical-align: middle;">admin_panel_settings</span> Administrator Profile</h2>
            <form id="adminProfileForm" class="profile-form">
                <input type="hidden" name="action" value="update_admin_profile">
                <div class="form-group">
                    <label for="adminId">Admin ID</label>
                    <input type="text" id="adminId" name="adminId" value="ADM-<?php echo str_pad($user['user_id'], 6, '0', STR_PAD_LEFT); ?>" disabled>
                </div>

                <div class="form-group">
                    <label for="accessLevel">Access Level</label>
                    <select id="accessLevel" name="accessLevel">
                        <option value="super_admin" <?php echo ($adminProfile['access_level'] ?? '') === 'super_admin' ? 'selected' : ''; ?>>Super Admin (Full Access)</option>
                        <option value="admin" <?php echo ($adminProfile['access_level'] ?? 'admin') === 'admin' ? 'selected' : ''; ?>>Admin (Standard Access)</option>
                        <option value="moderator" <?php echo ($adminProfile['access_level'] ?? '') === 'moderator' ? 'selected' : ''; ?>>Moderator (Limited Access)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="responsibilities">Responsibilities</label>
                    <textarea id="responsibilities" name="responsibilities" rows="4" placeholder="Enter your key responsibilities..."><?php echo htmlspecialchars($adminProfile['responsibilities'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn">Update Admin Info</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Security -->
        <div class="card profile-card">
            <h2><span class="material-icons" style="vertical-align: middle;">lock</span> <?php echo __('security'); ?></h2>
            <form id="securityForm" class="profile-form">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                    <label for="currentPassword"><?php echo __('current_password'); ?></label>
                    <input type="password" id="currentPassword" name="currentPassword" placeholder="<?php echo __('current_password'); ?>">
                </div>

                <div class="form-group">
                    <label for="newPassword"><?php echo __('new_password'); ?></label>
                    <input type="password" id="newPassword" name="newPassword" placeholder="<?php echo __('new_password'); ?>">
                </div>

                <div class="form-group">
                    <label for="confirmPassword"><?php echo __('confirm_password'); ?></label>
                    <input type="password" id="confirmPassword" name="confirmPassword" placeholder="<?php echo __('confirm_password'); ?>">
                </div>

                <button type="submit" class="btn"><?php echo __('change_password'); ?></button>
            </form>
        </div>

        <!-- Account Statistics -->
        <div class="card profile-card">
            <h2><span class="material-icons" style="vertical-align: middle;">bar_chart</span> <?php echo __('account_statistics'); ?></h2>
            <div class="profile-stats-list">
                <div class="stat-row">
                    <span><?php echo __('account_created'); ?>:</span>
                    <strong><?php echo date('M d, Y', strtotime($user['created_at'])); ?></strong>
                </div>

                <div class="stat-row">
                    <span><?php echo __('last_login'); ?>:</span>
                    <strong><?php echo date('M d, Y', strtotime($user['last_login'] ?? $user['created_at'])); ?></strong>
                </div>

                <?php if ($userRole === 'farmer'): ?>
                <div class="stat-row">
                    <span><?php echo __('total_crops'); ?>:</span>
                    <strong class="badge badge-success"><?php echo $stats['crops']; ?></strong>
                </div>

                <div class="stat-row">
                    <span><?php echo __('chat_messages'); ?>:</span>
                    <strong class="badge badge-info"><?php echo $stats['messages']; ?></strong>
                </div>

                <div class="stat-row">
                    <span><?php echo __('community_posts'); ?>:</span>
                    <strong class="badge badge-info"><?php echo $stats['posts']; ?></strong>
                </div>
                <?php elseif ($userRole === 'officer'): ?>
                <div class="stat-row">
                    <span>Farmers Supervised:</span>
                    <strong class="badge badge-success"><?php echo $stats['farmers']; ?></strong>
                </div>

                <div class="stat-row">
                    <span>Reports (30 days):</span>
                    <strong class="badge badge-info"><?php echo $stats['reports']; ?></strong>
                </div>

                <div class="stat-row">
                    <span>Alerts Issued (7 days):</span>
                    <strong class="badge badge-warning"><?php echo $stats['alerts']; ?></strong>
                </div>
                <?php elseif ($userRole === 'admin'): ?>
                <div class="stat-row">
                    <span>Total Users:</span>
                    <strong class="badge" style="background: var(--primary);"><?php echo $stats['total_users']; ?></strong>
                </div>

                <div class="stat-row">
                    <span>Farmers:</span>
                    <strong class="badge badge-success"><?php echo $stats['farmers']; ?></strong>
                </div>

                <div class="stat-row">
                    <span>Officers:</span>
                    <strong class="badge badge-info"><?php echo $stats['officers']; ?></strong>
                </div>

                <div class="stat-row">
                    <span>Active Crops:</span>
                    <strong class="badge badge-success"><?php echo $stats['active_crops']; ?></strong>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Role-Specific Quick Actions -->
        <?php if ($userRole === 'officer'): ?>
        <div class="card profile-card">
            <h2><span class="material-icons" style="vertical-align: middle;">bolt</span> Quick Actions</h2>
            <div class="profile-actions-grid">
                <a href="<?php echo $base_url; ?>officer-dashboard" class="btn btn-small"><span class="material-icons" style="font-size: 16px; vertical-align: middle;">dashboard</span> Officer Dashboard</a>
                <a href="<?php echo $base_url; ?>farmer-reports" class="btn btn-small btn-secondary"><span class="material-icons" style="font-size: 16px; vertical-align: middle;">description</span> View Reports</a>
                <a href="<?php echo $base_url; ?>issue-alert" class="btn btn-small btn-warning"><span class="material-icons" style="font-size: 16px; vertical-align: middle;">warning</span> Issue Alert</a>
            </div>
        </div>
        <?php elseif ($userRole === 'admin'): ?>
        <div class="card profile-card">
            <h2><span class="material-icons" style="vertical-align: middle;">build</span> Admin Tools</h2>
            <div class="profile-actions-grid">
                <a href="<?php echo $base_url; ?>admin-dashboard" class="btn btn-small"><span class="material-icons" style="font-size: 16px; vertical-align: middle;">dashboard</span> Admin Dashboard</a>
                <a href="<?php echo $base_url; ?>user-management" class="btn btn-small btn-secondary"><span class="material-icons" style="font-size: 16px; vertical-align: middle;">people</span> Manage Users</a>
                <a href="<?php echo $base_url; ?>system-settings" class="btn btn-small btn-info"><span class="material-icons" style="font-size: 16px; vertical-align: middle;">settings</span> Settings</a>
                <a href="<?php echo $base_url; ?>analytics" class="btn btn-small btn-success"><span class="material-icons" style="font-size: 16px; vertical-align: middle;">analytics</span> Analytics</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Preferences -->
        <div class="card profile-card">
            <h2><span class="material-icons" style="vertical-align: middle;">tune</span> <?php echo __('preferences'); ?></h2>
            <form id="preferencesForm" class="profile-form">
                <input type="hidden" name="action" value="update_preferences">
                
                <h3 style="font-size: 1rem; margin-bottom: 1rem; color: var(--primary);">Alert Notifications</h3>
                
                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="weatherAlerts" <?php echo ($preferences['weather_alerts'] ?? 1) ? 'checked' : ''; ?>>
                        <span><span class="material-icons" style="font-size: 16px; vertical-align: middle;">cloud</span> Weather Alerts</span>
                    </label>
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="diseaseAlerts" <?php echo ($preferences['disease_alerts'] ?? 1) ? 'checked' : ''; ?>>
                        <span><span class="material-icons" style="font-size: 16px; vertical-align: middle;">bug_report</span> Disease Alerts</span>
                    </label>
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="marketAlerts" <?php echo ($preferences['market_alerts'] ?? 1) ? 'checked' : ''; ?>>
                        <span><span class="material-icons" style="font-size: 16px; vertical-align: middle;">store</span> Market Alerts</span>
                    </label>
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="communityAlerts" <?php echo ($preferences['community_alerts'] ?? 1) ? 'checked' : ''; ?>>
                        <span><span class="material-icons" style="font-size: 16px; vertical-align: middle;">groups</span> Community Alerts</span>
                    </label>
                </div>

                <h3 style="font-size: 1rem; margin: 1.5rem 0 1rem; color: var(--primary);">Notification Channels</h3>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="emailNotifications" <?php echo ($preferences['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                        <span><span class="material-icons" style="font-size: 16px; vertical-align: middle;">email</span> <?php echo __('marketing_emails'); ?></span>
                    </label>
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="smsNotifications" <?php echo ($preferences['sms_notifications'] ?? 0) ? 'checked' : ''; ?>>
                        <span><span class="material-icons" style="font-size: 16px; vertical-align: middle;">sms</span> SMS Notifications</span>
                    </label>
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="pushNotifications" <?php echo ($preferences['push_notifications'] ?? 1) ? 'checked' : ''; ?>>
                        <span><span class="material-icons" style="font-size: 16px; vertical-align: middle;">notifications</span> <?php echo __('product_updates'); ?></span>
                    </label>
                </div>

                <button type="submit" class="btn"><?php echo __('save_preferences'); ?></button>
            </form>
        </div>

        <!-- Danger Zone -->
        <div class="card profile-card danger-zone-card">
            <h2 class="danger-heading"><span class="material-icons" style="vertical-align: middle;">warning</span> <?php echo __('danger_zone'); ?></h2>
            <p><?php echo __('delete_account_warning'); ?></p>
            <button type="button" id="deleteAccountBtn" class="btn btn-danger btn-small"><span class="material-icons" style="font-size: 16px; vertical-align: middle;">delete_forever</span> <?php echo __('delete_account'); ?></button>
        </div>
    </div>

<!-- Delete Account Modal -->
<div id="deleteAccountModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><span class="material-icons" style="color: #dc3545; vertical-align: middle;">warning</span> Confirm Account Deletion</h3>
            <button type="button" class="modal-close" id="closeDeleteModal">&times;</button>
        </div>
        <div class="modal-body">
            <p style="margin-bottom: 1rem;">This action cannot be undone. All your data including:</p>
            <ul style="margin-left: 1.5rem; margin-bottom: 1rem;">
                <li>Profile information</li>
                <li>Crop data and history</li>
                <li>Messages and community posts</li>
                <li>All preferences and settings</li>
            </ul>
            <p style="margin-bottom: 1rem;">will be permanently deleted.</p>
            <form id="deleteAccountForm">
                <input type="hidden" name="action" value="delete_account">
                <div class="form-group">
                    <label for="deletePassword">Enter your password to confirm:</label>
                    <input type="password" id="deletePassword" name="password" required placeholder="Your current password">
                </div>
                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="confirmDelete" id="confirmDeleteCheck" required>
                        <span>I understand that this action is irreversible</span>
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelDelete">Cancel</button>
            <button type="button" class="btn btn-danger" id="confirmDeleteBtn" disabled><span class="material-icons" style="font-size: 16px; vertical-align: middle;">delete_forever</span> Delete My Account</button>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    const baseUrl = '<?php echo $base_url; ?>';
    
    // Generic form submit handler
    function handleFormSubmit(formId, successCallback) {
        $(formId).on('submit', function(e) {
            e.preventDefault();
            const $form = $(this);
            const $btn = $form.find('button[type="submit"]');
            const originalText = $btn.html();
            
            $btn.prop('disabled', true).html('<span class="material-icons spinning">sync</span> Saving...');
            
            $.ajax({
                url: baseUrl + 'ajax/profile.php',
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showNotification(response.message, 'success');
                        if (successCallback) successCallback(response);
                    } else {
                        showNotification(response.message, 'error');
                    }
                },
                error: function() {
                    showNotification('An error occurred. Please try again.', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        });
    }
    
    // Personal Info Form
    handleFormSubmit('#personalInfoForm');
    
    // Farmer Profile Form
    handleFormSubmit('#farmerProfileForm');
    
    // Officer Profile Form
    handleFormSubmit('#officerProfileForm');
    
    // Admin Profile Form
    handleFormSubmit('#adminProfileForm');
    
    // Security Form (Password Change)
    handleFormSubmit('#securityForm', function() {
        $('#currentPassword, #newPassword, #confirmPassword').val('');
    });
    
    // Preferences Form
    $('#preferencesForm').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const originalText = $btn.html();
        
        $btn.prop('disabled', true).html('<span class="material-icons spinning">sync</span> Saving...');
        
        // Collect checkbox values properly
        const formData = {
            action: 'update_preferences',
            weatherAlerts: $form.find('input[name="weatherAlerts"]').is(':checked') ? 1 : 0,
            diseaseAlerts: $form.find('input[name="diseaseAlerts"]').is(':checked') ? 1 : 0,
            marketAlerts: $form.find('input[name="marketAlerts"]').is(':checked') ? 1 : 0,
            communityAlerts: $form.find('input[name="communityAlerts"]').is(':checked') ? 1 : 0,
            emailNotifications: $form.find('input[name="emailNotifications"]').is(':checked') ? 1 : 0,
            smsNotifications: $form.find('input[name="smsNotifications"]').is(':checked') ? 1 : 0,
            pushNotifications: $form.find('input[name="pushNotifications"]').is(':checked') ? 1 : 0
        };
        
        $.ajax({
            url: baseUrl + 'ajax/profile.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification(response.message, 'success');
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function() {
                showNotification('An error occurred. Please try again.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Delete Account Modal
    $('#deleteAccountBtn').on('click', function() {
        $('#deleteAccountModal').fadeIn(200);
    });
    
    $('#closeDeleteModal, #cancelDelete').on('click', function() {
        $('#deleteAccountModal').fadeOut(200);
        $('#deleteAccountForm')[0].reset();
        $('#confirmDeleteBtn').prop('disabled', true);
    });
    
    // Enable/disable delete button based on checkbox
    $('#confirmDeleteCheck').on('change', function() {
        $('#confirmDeleteBtn').prop('disabled', !$(this).is(':checked'));
    });
    
    // Handle account deletion
    $('#confirmDeleteBtn').on('click', function() {
        const password = $('#deletePassword').val();
        if (!password) {
            showNotification('Please enter your password', 'error');
            return;
        }
        
        const $btn = $(this);
        const originalText = $btn.html();
        $btn.prop('disabled', true).html('<span class="material-icons spinning">sync</span> Deleting...');
        
        $.ajax({
            url: baseUrl + 'ajax/profile.php',
            type: 'POST',
            data: {
                action: 'delete_account',
                password: password
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification(response.message, 'success');
                    setTimeout(function() {
                        window.location.href = baseUrl + 'logout';
                    }, 1500);
                } else {
                    showNotification(response.message, 'error');
                    $btn.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                showNotification('An error occurred. Please try again.', 'error');
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Close modal when clicking outside
    $('#deleteAccountModal').on('click', function(e) {
        if (e.target === this) {
            $(this).fadeOut(200);
        }
    });
    
    // Profile Image Upload
    $('#profileImageInput').on('change', function() {
        const file = this.files[0];
        if (!file) return;
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            showNotification('Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.', 'error');
            return;
        }
        
        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            showNotification('File size too large. Maximum 5MB allowed.', 'error');
            return;
        }
        
        // Preview image
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = $('#profileImagePreview');
            if (preview.length) {
                preview.attr('src', e.target.result);
            } else {
                $('.profile-image-preview').html('<img src="' + e.target.result + '" alt="Profile" id="profileImagePreview">');
            }
        };
        reader.readAsDataURL(file);
        
        // Upload image
        const formData = new FormData();
        formData.append('action', 'update_profile_image');
        formData.append('profileImage', file);
        
        const $label = $('label[for="profileImageInput"]');
        const originalText = $label.html();
        $label.html('<span class="material-icons spinning">sync</span> Uploading...');
        
        $.ajax({
            url: baseUrl + 'ajax/profile.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification(response.message, 'success');
                    // Update header avatar
                    if (response.imageUrl) {
                        $('.user-menu-toggle .user-avatar').attr('src', response.imageUrl);
                        $('.dropdown-avatar').attr('src', response.imageUrl);
                    }
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function() {
                showNotification('Failed to upload image. Please try again.', 'error');
            },
            complete: function() {
                $label.html(originalText);
            }
        });
    });
    
    // Notification helper
    function showNotification(message, type) {
        const bgColor = type === 'success' ? 'var(--primary)' : '#dc3545';
        const icon = type === 'success' ? 'check_circle' : 'error';
        
        const notification = $('<div class="profile-notification">' +
            '<span class="material-icons">' + icon + '</span> ' + message +
            '</div>');
        
        notification.css({
            'position': 'fixed',
            'top': '20px',
            'right': '20px',
            'padding': '1rem 1.5rem',
            'background': bgColor,
            'color': 'white',
            'border-radius': '8px',
            'box-shadow': '0 4px 12px rgba(0,0,0,0.15)',
            'z-index': '9999',
            'display': 'flex',
            'align-items': 'center',
            'gap': '0.5rem',
            'animation': 'slideInRight 0.3s ease'
        });
        
        $('body').append(notification);
        
        setTimeout(function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
});
</script>

<style>
@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(100px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.spinning {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.profile-image-card {
    text-align: center;
}

.profile-image-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.profile-image-preview {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    overflow: hidden;
    border: 4px solid var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f5f5;
}

.profile-image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-image-preview .default-avatar {
    font-size: 100px;
    color: #ccc;
}

.profile-image-form {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.profile-image-form .help-text {
    font-size: 0.85rem;
    color: #666;
    margin: 0;
}

/* Modal Styles */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 12px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #eee;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.2rem;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #666;
    padding: 0;
    line-height: 1;
}

.modal-close:hover {
    color: #333;
}

.modal-body {
    padding: 1.5rem;
}

.modal-body ul {
    color: #666;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid #eee;
    background: #f9f9f9;
    border-radius: 0 0 12px 12px;
}
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
