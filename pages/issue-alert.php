<?php
/**
 * Issue Alerts - For Agricultural Officers
 * Create and manage alerts for farmers
 */

// Authentication and role check
if (!isLoggedIn()) {
    redirect('login');
}

$currentUser = getCurrentUser();
if ($currentUser['role'] !== 'officer') {
    redirect('home');
}

include __DIR__ . '/../layouts/header.php';

$db = new Database();

// Get recent alerts
$alerts = $db->resultSet("SELECT a.*, u.first_name, u.last_name,
          DATE_FORMAT(a.created_at, '%M %d, %Y %h:%i %p') as formatted_date
          FROM alerts a
          LEFT JOIN users u ON a.created_by = u.user_id
          WHERE a.created_by = ? OR a.created_by IS NULL
          ORDER BY a.created_at DESC LIMIT 20", [$_SESSION['user_id']]);

$regions = ['Dhaka', 'Chittagong', 'Khulna', 'Rangpur', 'Sylhet', 'Barisal', 'Rajshahi', 'Mymensingh'];
?>

<section class="hero">
    <h1><span class="material-icons">notifications_active</span> <?php echo __('issue_alerts'); ?></h1>
    <p><?php echo __('create_manage_alerts'); ?></p>
</section>

<!-- Create Alert Form -->
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons">add_alert</span>
            <?php echo __('create_new_alert'); ?>
        </h3>
    </div>
    <form id="alertForm" class="card-body">
        <div class="form-group">
            <label for="alertTitle"><?php echo __('alert_title'); ?> *</label>
            <input type="text" id="alertTitle" name="title" placeholder="<?php echo __('eg_severe_weather'); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="alertType"><?php echo __('alert_type'); ?> *</label>
            <select id="alertType" name="alert_type" required>
                <option value=""><?php echo __('select_type'); ?></option>
                <option value="weather"><?php echo __('weather_alert'); ?></option>
                <option value="pest"><?php echo __('pest_outbreak'); ?></option>
                <option value="disease"><?php echo __('disease_warning'); ?></option>
                <option value="market"><?php echo __('market_update'); ?></option>
                <option value="government"><?php echo __('government_notice'); ?></option>
                <option value="general"><?php echo __('general_information'); ?></option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="severity"><?php echo __('severity_level'); ?> *</label>
            <select id="severity" name="severity" required>
                <option value="info"><?php echo __('info'); ?></option>
                <option value="warning"><?php echo __('warning'); ?></option>
                <option value="critical"><?php echo __('critical'); ?></option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="region"><?php echo __('target_region'); ?></label>
            <select id="region" name="region">
                <option value="all"><?php echo __('all_regions'); ?></option>
                <?php foreach ($regions as $r): ?>
                    <option value="<?php echo $r; ?>"><?php echo $r; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="alertMessage"><?php echo __('alert_message'); ?> *</label>
            <textarea id="alertMessage" name="message" rows="4" placeholder="<?php echo __('detailed_alert_message'); ?>" required></textarea>
        </div>
        
        <div class="form-group">
            <label for="actionRequired"><?php echo __('action_required'); ?></label>
            <textarea id="actionRequired" name="action_required" rows="2" placeholder="<?php echo __('what_farmers_do'); ?>"></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <span class="material-icons">send</span>
                <?php echo __('send_alert'); ?>
            </button>
            <button type="reset" class="btn btn-secondary">
                <span class="material-icons">clear</span>
                <?php echo __('clear'); ?>
            </button>
        </div>
    </form>
</div>

<!-- Recent Alerts -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons">history</span>
            <?php echo __('recent_alerts'); ?>
        </h3>
    </div>
    <div class="card-body">
        <?php if (empty($alerts)): ?>
            <div class="empty-state">
                <span class="material-icons" style="font-size: 64px; opacity: 0.3;">notifications_none</span>
                <p><?php echo __('no_alerts_created'); ?></p>
            </div>
        <?php else: ?>
            <div class="alerts-list">
                <?php foreach ($alerts as $alert): ?>
                    <div class="alert-item" style="border-left: 4px solid <?php 
                        echo $alert['severity'] === 'critical' ? '#dc3545' : 
                            ($alert['severity'] === 'warning' ? '#ffc107' : '#17a2b8'); 
                    ?>; padding: 1rem; margin-bottom: 1rem; background: var(--bg-card); border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                            <h4 style="margin: 0; font-size: 1.1rem;">
                                <span class="material-icons" style="vertical-align: middle; font-size: 20px;">
                                    <?php echo $alert['alert_type'] === 'weather' ? 'wb_sunny' : 
                                        ($alert['alert_type'] === 'pest' ? 'bug_report' : 'notifications'); ?>
                                </span>
                                <?php echo htmlspecialchars($alert['title']); ?>
                            </h4>
                            <span class="badge badge-<?php 
                                echo $alert['severity'] === 'critical' ? 'danger' : 
                                    ($alert['severity'] === 'warning' ? 'warning' : 'info'); 
                            ?>">
                                <?php echo ucfirst($alert['severity']); ?>
                            </span>
                        </div>
                        <p style="margin: 0.5rem 0; color: var(--text-secondary);"><?php echo htmlspecialchars($alert['message']); ?></p>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem; font-size: 0.875rem; color: var(--text-muted);">
                            <span>
                                <span class="material-icons" style="font-size: 16px; vertical-align: middle;">person</span>
                                <?php echo htmlspecialchars($alert['first_name'] . ' ' . $alert['last_name']); ?>
                            </span>
                            <span>
                                <span class="material-icons" style="font-size: 16px; vertical-align: middle;">access_time</span>
                                <?php echo $alert['formatted_date']; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('alertForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'create_alert');
    
    fetch('<?php echo $base_url; ?>ajax/officer.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Alert sent successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to send alert. Please try again.');
    });
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
