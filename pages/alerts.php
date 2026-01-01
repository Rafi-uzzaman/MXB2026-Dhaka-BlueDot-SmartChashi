<?php

if (!isLoggedIn()) {
    redirect('login');
}

include __DIR__ . '/../layouts/header.php';

$db = new Database();
$alerts = $db->resultSet("SELECT * FROM alerts WHERE user_id = ? ORDER BY created_at DESC", [$_SESSION['user_id']]);
$unread_count = $db->single("SELECT COUNT(*) as count FROM alerts WHERE user_id = ? AND is_read = 0", [$_SESSION['user_id']]);
?>

<section class="hero">
    <h1><span class="material-icons">notifications_active</span> Alerts & Notifications</h1>
    <p>Stay informed with real-time weather and farm alerts</p>
</section>

<div class="alerts-stats-grid mt-4 mb-4">
    <div class="alert-stat-card">
        <div class="alert-stat-icon">
            <span class="material-icons">mark_email_unread</span>
        </div>
        <div class="alert-stat-info">
            <h3><?php echo $unread_count['count']; ?></h3>
            <p>Unread Alerts</p>
        </div>
    </div>

    <div class="alert-stat-card">
        <div class="alert-stat-icon">
            <span class="material-icons">notifications</span>
        </div>
        <div class="alert-stat-info">
            <h3><?php echo count($alerts); ?></h3>
            <p>Total Alerts</p>
        </div>
    </div>
</div>

<h2><span class="material-icons">category</span> Alert Categories</h2>
<div class="alert-categories-grid mt-3 mb-4">
    <div class="alert-category-card">
        <span class="material-icons alert-category-icon">wb_cloudy</span>
        <h4>Weather Alerts</h4>
        <p><?php echo count(array_filter($alerts, fn($a) => $a['alert_type'] === 'weather')); ?> active</p>
    </div>

    <div class="alert-category-card">
        <span class="material-icons alert-category-icon">coronavirus</span>
        <h4>Disease Alerts</h4>
        <p><?php echo count(array_filter($alerts, fn($a) => $a['alert_type'] === 'disease')); ?> active</p>
    </div>

    <div class="alert-category-card">
        <span class="material-icons alert-category-icon">trending_up</span>
        <h4>Market Alerts</h4>
        <p><?php echo count(array_filter($alerts, fn($a) => $a['alert_type'] === 'market')); ?> active</p>
    </div>

    <div class="alert-category-card">
        <span class="material-icons alert-category-icon">settings</span>
        <h4>System Alerts</h4>
        <p><?php echo count(array_filter($alerts, fn($a) => $a['alert_type'] === 'system')); ?> active</p>
    </div>
</div>

<h2><span class="material-icons">list</span> All Alerts</h2>
<?php if ($alerts): ?>
    <div class="alerts-list-container">
        <?php foreach ($alerts as $alert): ?>
            <div class="notice notice-<?php echo $alert['priority'] === 'high' ? 'danger' : ($alert['priority'] === 'medium' ? 'warning' : 'info'); ?>">
                <div class="alert-content">
                    <div class="alert-header">
                        <div class="alert-title-section">
                            <span class="material-icons alert-type-icon">
                                <?php 
                                $icon = match($alert['alert_type']) {
                                    'weather' => 'wb_cloudy',
                                    'disease' => 'coronavirus',
                                    'market' => 'trending_up',
                                    default => 'settings'
                                };
                                echo $icon;
                                ?>
                            </span>
                            <strong class="alert-type-title">
                                <?php echo ucfirst($alert['alert_type']) . ' Alert'; ?>
                            </strong>
                        </div>
                        <div class="alert-priority-badge">
                            <span class="badge badge-<?php echo $alert['priority'] === 'high' ? 'danger' : ($alert['priority'] === 'medium' ? 'warning' : 'success'); ?>">
                                <?php echo ucfirst($alert['priority']); ?>
                            </span>
                        </div>
                    </div>
                    <p class="alert-message"><?php echo htmlspecialchars($alert['message']); ?></p>
                    <div class="alert-meta">
                        <span class="material-icons alert-time-icon">schedule</span>
                        <span class="alert-time"><?php echo date('M d, Y H:i', strtotime($alert['created_at'])); ?></span>
                        <?php if (!$alert['is_read']): ?>
                            <span class="alert-new-badge">NEW</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card text-center mt-4 empty-alerts-card">
        <span class="material-icons empty-alerts-icon">notifications_off</span>
        <h3>No Alerts</h3>
        <p>You're all caught up! No new alerts at the moment.</p>
        <a href="<?php echo $base_url; ?>dashboard" class="btn mt-2">
            <span class="material-icons">dashboard</span> Go to Dashboard
        </a>
    </div>
<?php endif; ?>

<div class="card mt-4 alert-preferences-card">
    <div class="card-header">
        <h3 class="card-title"><span class="material-icons">tune</span> Alert Preferences</h3>
    </div>

    <form>
        <div class="alert-preferences-grid">
            <label class="checkbox-label">
                <input type="checkbox" checked>
                <span class="material-icons checkbox-icon">wb_cloudy</span>
                Receive weather alerts
            </label>

            <label class="checkbox-label">
                <input type="checkbox" checked>
                <span class="material-icons checkbox-icon">coronavirus</span>
                Receive disease outbreak alerts
            </label>

            <label class="checkbox-label">
                <input type="checkbox" checked>
                <span class="material-icons checkbox-icon">trending_up</span>
                Receive market price updates
            </label>

            <label class="checkbox-label">
                <input type="checkbox" checked>
                <span class="material-icons checkbox-icon">people</span>
                Receive community notifications
            </label>

            <label class="checkbox-label">
                <input type="checkbox" checked>
                <span class="material-icons checkbox-icon">email</span>
                Email notifications
            </label>

            <label class="checkbox-label">
                <input type="checkbox">
                <span class="material-icons checkbox-icon">sms</span>
                SMS notifications
            </label>
        </div>

        <button type="submit" class="btn mt-3">
            <span class="material-icons">save</span> Save Preferences
        </button>
    </form>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
