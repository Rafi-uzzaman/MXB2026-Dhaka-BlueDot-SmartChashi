<?php
/**
 * Admin System Monitoring
 * Real-time server metrics, database health, and error tracking
 */
$currPage = "System Monitoring";

require_once __DIR__ . '/../../config/config.php';

// Initialize Database
$db = new Database();

// Server metrics
$diskTotal = disk_total_space('/');
$diskFree = disk_free_space('/');
$diskUsedPercent = round((($diskTotal - $diskFree) / $diskTotal) * 100);
$phpVersion = phpversion();
$mysqlVersion = $db->single("SELECT VERSION() as version")['version'] ?? 'Unknown';
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$memoryLimit = ini_get('memory_limit');

// Database stats - fetch actual data from information_schema
$dbTables = $db->resultSet("
    SELECT 
        TABLE_NAME as table_name,
        TABLE_ROWS as table_rows,
        ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) as size_mb
    FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = DATABASE()
    ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC
") ?? [];

$totalDbSize = !empty($dbTables) ? array_sum(array_column($dbTables, 'size_mb')) : 0;
$totalDbRows = !empty($dbTables) ? array_sum(array_column($dbTables, 'table_rows')) : 0;

// Error tracking - fetch from error_logs table
$recentErrors = $db->resultSet("
    SELECT 
        error_id, 
        error_type, 
        error_message, 
        severity, 
        occurrence_count, 
        last_seen,
        is_resolved,
        CASE 
            WHEN severity = 'critical' THEN 'critical'
            WHEN severity = 'error' THEN 'error'
            ELSE 'warning'
        END as severity_class
    FROM error_logs 
    ORDER BY last_seen DESC 
    LIMIT 10
") ?? [];

$unresolvedErrors = $db->single("SELECT COUNT(*) as count FROM error_logs WHERE is_resolved = 0")['count'] ?? 0;
$criticalErrors = $db->single("SELECT COUNT(*) as count FROM error_logs WHERE severity = 'critical' AND is_resolved = 0")['count'] ?? 0;
$todayErrors = $db->single("SELECT COUNT(*) as count FROM error_logs WHERE DATE(last_seen) = CURDATE()")['count'] ?? 0;

// Scheduled tasks - fetch from scheduled_tasks table
try {
    $tasks = $db->resultSet("
        SELECT 
            task_id,
            task_name,
            task_type,
            schedule_cron,
            schedule_human,
            is_enabled,
            is_running,
            last_run,
            next_run,
            last_status,
            last_duration_ms
        FROM scheduled_tasks 
        ORDER BY task_name
    ") ?? [];
} catch (Exception $e) {
    $tasks = [];
    error_log('Scheduled tasks table error: ' . $e->getMessage());
}

// API request stats for last 24 hours
$apiStats = $db->resultSet("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as hour,
        COUNT(*) as request_count,
        AVG(response_time_ms) as avg_response_time,
        SUM(CASE WHEN response_code >= 400 THEN 1 ELSE 0 END) as error_count
    FROM api_request_logs
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')
    ORDER BY hour DESC
") ?? [];

// Security events in last 24 hours
$securityEvents = $db->single("
    SELECT 
        COUNT(*) as total_events,
        SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical_count,
        SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) as high_count,
        SUM(CASE WHEN is_acknowledged = 0 THEN 1 ELSE 0 END) as unacknowledged
    FROM security_events
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
") ?? [];

// Recent admin activity
$recentActivity = $db->resultSet("
    SELECT 
        log_id,
        user_id,
        action,
        action_category,
        entity_type,
        entity_id,
        risk_level,
        created_at
    FROM admin_activity_logs
    ORDER BY created_at DESC
    LIMIT 5
") ?? [];

// Admin sessions info
try {
    $activeSessions = $db->single("
        SELECT 
            COUNT(*) as active_count,
            COUNT(DISTINCT user_id) as unique_users
        FROM admin_sessions
        WHERE is_active = 1 AND expires_at > NOW()
    ") ?? ['active_count' => 0, 'unique_users' => 0];
} catch (Exception $e) {
    $activeSessions = ['active_count' => 0, 'unique_users' => 0];
    error_log('Admin sessions table error: ' . $e->getMessage());
}

// Backup records status
try {
    $backupInfo = $db->resultSet("
        SELECT 
            status,
            COUNT(*) as count,
            MAX(created_at) as last_backup
        FROM backup_records
        GROUP BY status
        ORDER BY created_at DESC
        LIMIT 5
    ") ?? [];
} catch (Exception $e) {
    $backupInfo = [];
    error_log('Backup records table error: ' . $e->getMessage());
}

require_once __DIR__ . '/../layouts/admin-header.php';
?>

<link rel="stylesheet" href="<?php echo $base_url; ?>admin-secure/assets/css/admin-monitoring.css">

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title">System Monitoring</h1>
        <p class="page-subtitle">Real-time server metrics and health monitoring</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-secondary" onclick="refreshMetrics()">
            <span class="material-icons">refresh</span>
            Refresh
        </button>
        <span class="auto-refresh">
            <label>
                <input type="checkbox" id="autoRefresh" checked> Auto-refresh
            </label>
        </span>
    </div>
</div>

<!-- Status Bar -->
<div class="status-bar">
    <div class="status-item healthy" id="webServerStatus">
        <span class="material-icons">dns</span>
        <span>Web Server</span>
    </div>
    <div class="status-item healthy" id="databaseStatus">
        <span class="material-icons">storage</span>
        <span>Database</span>
    </div>
    <div class="status-item healthy" id="diskStatus">
        <span class="material-icons">hard_drive</span>
        <span id="diskLabel">Disk (0%)</span>
    </div>
    <div class="status-item healthy" id="errorStatus">
        <span class="material-icons">error</span>
        <span id="errorLabel">Errors (0)</span>
    </div>
</div>

<!-- Metrics Grid -->
<div class="metrics-grid">
    <!-- Server Metrics -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">memory</span>
                Server Metrics
            </h3>
        </div>
        <div class="card-body">
            <div class="gauge-grid">
                <div class="gauge-item">
                    <div class="gauge-circle" data-percent="0" id="cpuGauge">
                        <svg viewBox="0 0 100 100">
                            <circle class="gauge-bg" cx="50" cy="50" r="45"/>
                            <circle class="gauge-fill" cx="50" cy="50" r="45" id="cpuGaugeFill"/>
                        </svg>
                        <div class="gauge-value"><span id="cpuValue">--</span>%</div>
                    </div>
                    <div class="gauge-label">CPU Usage</div>
                </div>
                <div class="gauge-item">
                    <div class="gauge-circle" data-percent="0" id="memoryGauge">
                        <svg viewBox="0 0 100 100">
                            <circle class="gauge-bg" cx="50" cy="50" r="45"/>
                            <circle class="gauge-fill" cx="50" cy="50" r="45" id="memoryGaugeFill"/>
                        </svg>
                        <div class="gauge-value"><span id="memoryValue">--</span>%</div>
                    </div>
                    <div class="gauge-label">Memory</div>
                </div>
                <div class="gauge-item">
                    <div class="gauge-circle" data-percent="<?php echo $diskUsedPercent; ?>">
                        <svg viewBox="0 0 100 100">
                            <circle class="gauge-bg" cx="50" cy="50" r="45"/>
                            <circle class="gauge-fill" cx="50" cy="50" r="45" style="stroke-dashoffset: <?php echo 283 - (283 * $diskUsedPercent / 100); ?>"/>
                        </svg>
                        <div class="gauge-value"><?php echo $diskUsedPercent; ?>%</div>
                    </div>
                    <div class="gauge-label">Disk</div>
                </div>
            </div>
            
            <div class="metric-details">
                <div class="metric-row">
                    <span class="metric-label">PHP Version</span>
                    <span class="metric-value"><?php echo $phpVersion; ?></span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">MySQL Version</span>
                    <span class="metric-value"><?php echo $mysqlVersion; ?></span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Web Server</span>
                    <span class="metric-value"><?php echo $serverSoftware; ?></span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Memory Limit</span>
                    <span class="metric-value"><?php echo $memoryLimit; ?></span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Disk Free</span>
                    <span class="metric-value"><?php echo round($diskFree / 1024 / 1024 / 1024, 2); ?> GB</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Database Health -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">storage</span>
                Database Health
            </h3>
        </div>
        <div class="card-body">
            <div class="db-stats">
                <div class="db-stat">
                    <span class="db-stat-value"><?php echo number_format($totalDbSize, 2); ?></span>
                    <span class="db-stat-label">MB Total Size</span>
                </div>
                <div class="db-stat">
                    <span class="db-stat-value"><?php echo number_format($totalDbRows); ?></span>
                    <span class="db-stat-label">Total Rows</span>
                </div>
                <div class="db-stat">
                    <span class="db-stat-value"><?php echo count($dbTables); ?></span>
                    <span class="db-stat-label">Tables</span>
                </div>
            </div>
            
            <div class="db-tables-list">
                <h4>Largest Tables</h4>
                <?php 
                usort($dbTables, fn($a, $b) => $b['size_mb'] <=> $a['size_mb']);
                $topTables = array_slice($dbTables, 0, 5);
                foreach ($topTables as $table): 
                ?>
                    <div class="db-table-item">
                        <span class="table-name"><?php echo $table['table_name']; ?></span>
                        <span class="table-rows"><?php echo number_format($table['table_rows']); ?> rows</span>
                        <span class="table-size"><?php echo $table['size_mb']; ?> MB</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="charts-grid">
    <div class="chart-card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">show_chart</span>
                Response Time (Last 24h)
            </h3>
        </div>
        <div class="card-body">
            <canvas id="responseTimeChart" height="250"></canvas>
        </div>
    </div>
    
    <div class="chart-card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">trending_up</span>
                Requests / Hour
            </h3>
        </div>
        <div class="card-body">
            <canvas id="requestsChart" height="250"></canvas>
        </div>
    </div>
</div>

<!-- Error Tracking & Scheduled Tasks -->
<div class="charts-grid">
    <!-- Error Tracking -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">bug_report</span>
                Error Tracking
            </h3>
            <div class="card-actions">
                <span class="error-count"><?php echo $unresolvedErrors; ?> unresolved</span>
            </div>
        </div>
        <div class="card-body">
            <div class="error-summary">
                <div class="error-stat critical">
                    <span class="error-stat-value"><?php echo $criticalErrors; ?></span>
                    <span class="error-stat-label">Critical</span>
                </div>
                <div class="error-stat warning">
                    <span class="error-stat-value"><?php echo $unresolvedErrors - $criticalErrors; ?></span>
                    <span class="error-stat-label">Errors</span>
                </div>
                <div class="error-stat info">
                    <span class="error-stat-value"><?php echo $todayErrors; ?></span>
                    <span class="error-stat-label">Today</span>
                </div>
            </div>
            
            <div class="error-list">
                <?php if (empty($recentErrors)): ?>
                    <div class="empty-state small">
                        <span class="material-icons">check_circle</span>
                        <p>No errors logged</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentErrors as $error): ?>
                        <div class="error-item <?php echo $error['severity_class']; ?>" onclick="viewError(<?php echo $error['error_id']; ?>)">
                            <div class="error-severity">
                                <span class="material-icons"><?php echo ($error['severity'] === 'critical' || $error['severity'] === 'error') ? 'error' : 'warning'; ?></span>
                            </div>
                            <div class="error-info">
                                <span class="error-type"><?php echo htmlspecialchars($error['error_type']); ?></span>
                                <span class="error-message"><?php echo htmlspecialchars(substr($error['error_message'], 0, 80)) . '...'; ?></span>
                                <span class="error-meta">
                                    <span class="material-icons">schedule</span>
                                    <?php echo date('M d, H:i', strtotime($error['last_seen'])); ?>
                                    <span class="occurrence-count">x<?php echo $error['occurrence_count']; ?></span>
                                </span>
                            </div>
                            <?php if (!$error['is_resolved']): ?>
                                <button class="btn btn-sm btn-ghost" onclick="event.stopPropagation(); resolveError(<?php echo $error['error_id']; ?>)">
                                    <span class="material-icons">check</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-footer">
            <a href="#" class="card-link">View all errors</a>
        </div>
    </div>
    
    <!-- Scheduled Tasks -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">schedule</span>
                Scheduled Tasks
            </h3>
        </div>
        <div class="card-body no-padding">
            <div class="task-list">
                <?php foreach ($tasks as $task): ?>
                    <div class="task-item">
                        <div class="task-status <?php echo $task['last_status'] === 'success' ? 'success' : ($task['last_status'] === 'failed' ? 'failed' : 'pending'); ?>">
                            <span class="material-icons">
                                <?php 
                                if ($task['is_running']) echo 'sync';
                                elseif ($task['last_status'] === 'success') echo 'check_circle';
                                elseif ($task['last_status'] === 'failed') echo 'error';
                                else echo 'schedule';
                                ?>
                            </span>
                        </div>
                        <div class="task-info">
                            <span class="task-name"><?php echo htmlspecialchars($task['task_name']); ?></span>
                            <span class="task-schedule"><?php echo $task['schedule_human'] ?: $task['schedule_cron']; ?></span>
                            <span class="task-last-run">
                                <?php if ($task['last_run']): ?>
                                    Last: <?php echo date('M d, H:i', strtotime($task['last_run'])); ?>
                                <?php else: ?>
                                    Never run
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="task-actions">
                            <label class="task-toggle">
                                <input type="checkbox" <?php echo $task['is_enabled'] ? 'checked' : ''; ?> onchange="toggleTask(<?php echo $task['task_id']; ?>, this.checked)">
                                <span class="toggle-slider"></span>
                            </label>
                            <button class="action-btn" onclick="runTask(<?php echo $task['task_id']; ?>)" title="Run Now">
                                <span class="material-icons">play_arrow</span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Admin Sessions & Backups -->
<div class="charts-grid">
    <!-- Active Admin Sessions -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">verified_user</span>
                Active Admin Sessions
            </h3>
        </div>
        <div class="card-body">
            <div class="session-stats">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $activeSessions['active_count'] ?? 0; ?></div>
                    <div class="stat-label">Active Sessions</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $activeSessions['unique_users'] ?? 0; ?></div>
                    <div class="stat-label">Unique Admins</div>
                </div>
            </div>
            <div class="recent-sessions">
                <?php 
                try {
                    $recentSessions = $db->resultSet("
                        SELECT 
                            s.session_id,
                            u.first_name,
                            u.last_name,
                            s.ip_address,
                            s.login_at,
                            s.last_activity
                        FROM admin_sessions s
                        JOIN users u ON s.user_id = u.user_id
                        WHERE s.is_active = 1 AND s.expires_at > NOW()
                        ORDER BY s.last_activity DESC
                        LIMIT 5
                    ") ?? [];
                } catch (Exception $e) {
                    $recentSessions = [];
                    error_log('Recent sessions query error: ' . $e->getMessage());
                }
                
                if (empty($recentSessions)): 
                ?>
                    <div class="empty-state small">
                        <p>No active sessions</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentSessions as $session): ?>
                        <div class="session-item">
                            <div class="session-user">
                                <span class="user-name"><?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?></span>
                                <span class="user-ip"><?php echo htmlspecialchars($session['ip_address']); ?></span>
                            </div>
                            <div class="session-time">
                                <span class="time-label">Last Activity:</span>
                                <span class="time-value"><?php echo date('M d, H:i', strtotime($session['last_activity'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Backup Status -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">backup</span>
                Backup Status
            </h3>
            <div class="card-actions">
                <button class="btn btn-sm btn-primary" onclick="triggerBackup()">
                    <span class="material-icons">cloud_upload</span>
                    Backup Now
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="backup-status-list">
                <?php 
                if (empty($backupInfo)): 
                ?>
                    <div class="empty-state small">
                        <span class="material-icons">storage</span>
                        <p>No backups found</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($backupInfo as $backup): ?>
                        <div class="backup-item">
                            <div class="backup-status-icon <?php echo $backup['status']; ?>">
                                <span class="material-icons">
                                    <?php 
                                    switch($backup['status']) {
                                        case 'completed': echo 'check_circle';break;
                                        case 'failed': echo 'error';break;
                                        case 'in_progress': echo 'sync';break;
                                        default: echo 'schedule';
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="backup-info">
                                <span class="backup-status-text"><?php echo ucfirst(str_replace('_', ' ', $backup['status'])); ?></span>
                                <span class="backup-count"><?php echo $backup['count']; ?> backups</span>
                                <span class="backup-date">
                                    Last: <?php echo $backup['last_backup'] ? date('M d, H:i', strtotime($backup['last_backup'])) : 'N/A'; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Security Events & Activity Log -->
<div class="charts-grid">
    <!-- Security Events -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">security</span>
                Security Events (Last 24h)
            </h3>
        </div>
        <div class="card-body">
            <div class="security-stats">
                <div class="security-stat total">
                    <span class="stat-icon">📊</span>
                    <span class="stat-value"><?php echo $securityEvents['total_events'] ?? 0; ?></span>
                    <span class="stat-label">Total Events</span>
                </div>
                <div class="security-stat critical">
                    <span class="stat-icon">🔴</span>
                    <span class="stat-value"><?php echo $securityEvents['critical_count'] ?? 0; ?></span>
                    <span class="stat-label">Critical</span>
                </div>
                <div class="security-stat high">
                    <span class="stat-icon">🟠</span>
                    <span class="stat-value"><?php echo $securityEvents['high_count'] ?? 0; ?></span>
                    <span class="stat-label">High</span>
                </div>
                <div class="security-stat unread">
                    <span class="stat-icon">⚠️</span>
                    <span class="stat-value"><?php echo $securityEvents['unacknowledged'] ?? 0; ?></span>
                    <span class="stat-label">Unacknowledged</span>
                </div>
            </div>
            
            <div class="security-events-list">
                <?php 
                $latestSecurityEvents = $db->resultSet("
                    SELECT 
                        event_id,
                        event_type,
                        severity,
                        ip_address,
                        description,
                        is_acknowledged,
                        created_at
                    FROM security_events
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    ORDER BY created_at DESC
                    LIMIT 5
                ") ?? [];
                
                if (empty($latestSecurityEvents)): 
                ?>
                    <div class="empty-state small">
                        <span class="material-icons">check_circle</span>
                        <p>No security events</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($latestSecurityEvents as $event): ?>
                        <div class="security-event-item <?php echo strtolower($event['severity']); ?>">
                            <span class="event-type"><?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($event['event_type']))); ?></span>
                            <span class="event-ip"><?php echo htmlspecialchars($event['ip_address']); ?></span>
                            <span class="event-time"><?php echo date('H:i', strtotime($event['created_at'])); ?></span>
                            <?php if (!$event['is_acknowledged']): ?>
                                <button class="btn btn-xs btn-ghost" onclick="acknowledgeEvent(<?php echo $event['event_id']; ?>)" title="Acknowledge">
                                    <span class="material-icons">done</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Admin Activity -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">history</span>
                Recent Admin Activity
            </h3>
        </div>
        <div class="card-body no-padding">
            <div class="activity-log">
                <?php 
                if (empty($recentActivity)): 
                ?>
                    <div class="empty-state small">
                        <span class="material-icons">info</span>
                        <p>No recent activity</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentActivity as $activity): 
                        $user = $db->single("SELECT first_name, last_name FROM users WHERE user_id = ?", [$activity['user_id']]);
                    ?>
                        <div class="activity-item <?php echo $activity['risk_level']; ?>">
                            <div class="activity-icon">
                                <span class="material-icons">
                                    <?php 
                                    switch($activity['action_category']) {
                                        case 'user': echo 'person';break;
                                        case 'security': echo 'security';break;
                                        case 'content': echo 'description';break;
                                        case 'settings': echo 'settings';break;
                                        case 'backup': echo 'backup';break;
                                        default: echo 'info';
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="activity-info">
                                <span class="activity-action"><?php echo str_replace('_', ' ', ucfirst($activity['action'])); ?></span>
                                <span class="activity-user"><?php echo htmlspecialchars(($user['first_name'] ?? 'Unknown') . ' ' . ($user['last_name'] ?? '')); ?></span>
                                <span class="activity-time"><?php echo date('M d, H:i', strtotime($activity['created_at'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<!-- Error Detail Modal -->
<div class="modal-overlay" id="errorModal">
    <div class="modal-box error-modal">
        <div class="modal-header">
            <h3>Error Details</h3>
            <button class="modal-close" onclick="hideErrorModal()">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="modal-body" id="errorModalContent">
            <div class="loading-spinner">
                <span class="material-icons spinning">sync</span>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="hideErrorModal()">Close</button>
            <button class="btn btn-primary" id="resolveErrorBtn" onclick="resolveCurrentError()">Mark Resolved</button>
        </div>
    </div>
</div>

<style>
.auto-refresh {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--text-muted);
}

.auto-refresh input {
    accent-color: var(--primary);
}

/* Status Bar */
.status-bar {
    display: flex;
    gap: 16px;
    margin-bottom: 24px;
}

.status-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--border-radius);
    font-size: 14px;
    font-weight: 500;
}

.status-item .material-icons {
    font-size: 20px;
}

.status-item.healthy {
    border-color: var(--secondary);
    color: var(--secondary);
}

.status-item.warning {
    border-color: var(--warning);
    color: var(--warning);
}

.status-item.error {
    border-color: var(--danger);
    color: var(--danger);
}

/* Metrics Grid */
.metrics-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 24px;
}

/* Gauges */
.gauge-grid {
    display: flex;
    justify-content: space-around;
    margin-bottom: 24px;
}

.gauge-item {
    text-align: center;
}

.gauge-circle {
    position: relative;
    width: 100px;
    height: 100px;
    margin: 0 auto 8px;
}

.gauge-circle svg {
    transform: rotate(-90deg);
}

.gauge-bg {
    fill: none;
    stroke: var(--border);
    stroke-width: 8;
}

.gauge-fill {
    fill: none;
    stroke: var(--primary);
    stroke-width: 8;
    stroke-linecap: round;
    stroke-dasharray: 283;
    stroke-dashoffset: 283;
    transition: stroke-dashoffset 1s ease;
}

.gauge-value {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 20px;
    font-weight: 700;
    color: var(--text-primary);
}

.gauge-label {
    font-size: 13px;
    color: var(--text-muted);
}

/* Metric Details */
.metric-details {
    border-top: 1px solid var(--border);
    padding-top: 16px;
}

.metric-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid var(--border);
}

.metric-row:last-child {
    border-bottom: none;
}

.metric-label {
    font-size: 13px;
    color: var(--text-muted);
}

.metric-value {
    font-size: 13px;
    font-weight: 500;
    color: var(--text-primary);
}

/* Database Stats */
.db-stats {
    display: flex;
    justify-content: space-around;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border);
}

.db-stat {
    text-align: center;
}

.db-stat-value {
    display: block;
    font-size: 28px;
    font-weight: 700;
    color: var(--primary);
}

.db-stat-label {
    font-size: 12px;
    color: var(--text-muted);
}

.db-tables-list h4 {
    font-size: 13px;
    font-weight: 600;
    color: var(--text-secondary);
    margin-bottom: 12px;
}

.db-table-item {
    display: flex;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid var(--border);
}

.db-table-item:last-child {
    border-bottom: none;
}

.table-name {
    flex: 1;
    font-size: 13px;
    font-weight: 500;
    color: var(--text-primary);
}

.table-rows, .table-size {
    font-size: 12px;
    color: var(--text-muted);
    margin-left: 16px;
}

/* Error Tracking */
.error-count {
    font-size: 12px;
    padding: 4px 8px;
    background: rgba(239, 68, 68, 0.2);
    color: var(--danger);
    border-radius: 10px;
}

.error-summary {
    display: flex;
    justify-content: space-around;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border);
}

.error-stat {
    text-align: center;
    padding: 12px 20px;
    border-radius: 8px;
}

.error-stat.critical {
    background: rgba(239, 68, 68, 0.1);
}

.error-stat.warning {
    background: rgba(245, 158, 11, 0.1);
}

.error-stat.info {
    background: rgba(99, 102, 241, 0.1);
}

.error-stat-value {
    display: block;
    font-size: 24px;
    font-weight: 700;
    color: var(--text-primary);
}

.error-stat.critical .error-stat-value { color: var(--danger); }
.error-stat.warning .error-stat-value { color: var(--warning); }
.error-stat.info .error-stat-value { color: var(--info); }

.error-stat-label {
    font-size: 12px;
    color: var(--text-muted);
}

.error-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.error-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    background: var(--bg-tertiary);
    border-radius: 8px;
    border-left: 3px solid;
    cursor: pointer;
    transition: background 0.2s;
}

.error-item:hover {
    background: var(--bg-hover);
}

.error-item.critical {
    border-left-color: var(--danger);
}

.error-item.error {
    border-left-color: var(--warning);
}

.error-item.warning {
    border-left-color: var(--info);
}

.error-severity .material-icons {
    font-size: 20px;
}

.error-item.critical .error-severity { color: var(--danger); }
.error-item.error .error-severity { color: var(--warning); }

.error-info {
    flex: 1;
    min-width: 0;
}

.error-type {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 2px;
}

.error-message {
    display: block;
    font-size: 12px;
    color: var(--text-muted);
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.error-meta {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    color: var(--text-muted);
}

.error-meta .material-icons {
    font-size: 14px;
}

.occurrence-count {
    margin-left: 8px;
    padding: 2px 6px;
    background: var(--border);
    border-radius: 6px;
}

/* Scheduled Tasks */
.task-list {
    display: flex;
    flex-direction: column;
}

.task-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
}

.task-item:last-child {
    border-bottom: none;
}

.task-status {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.task-status.success {
    background: rgba(16, 185, 129, 0.2);
    color: var(--secondary);
}

.task-status.failed {
    background: rgba(239, 68, 68, 0.2);
    color: var(--danger);
}

.task-status.pending {
    background: rgba(99, 102, 241, 0.2);
    color: var(--primary);
}

.task-status .material-icons {
    font-size: 18px;
}

.task-info {
    flex: 1;
}

.task-name {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: var(--text-primary);
}

.task-schedule {
    display: block;
    font-size: 12px;
    color: var(--text-muted);
}

.task-last-run {
    font-size: 11px;
    color: var(--text-muted);
}

.task-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Toggle Switch */
.task-toggle {
    position: relative;
    display: inline-block;
    width: 40px;
    height: 22px;
}

.task-toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: var(--border);
    border-radius: 22px;
    transition: all 0.3s;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 2px;
    bottom: 2px;
    background-color: white;
    border-radius: 50%;
    transition: all 0.3s;
}

.task-toggle input:checked + .toggle-slider {
    background-color: var(--secondary);
}

.task-toggle input:checked + .toggle-slider:before {
    transform: translateX(18px);
}

/* Error Modal */
.error-modal {
    width: 600px;
    max-height: 80vh;
}

.error-detail {
    margin-bottom: 16px;
}

.error-detail-label {
    font-size: 11px;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 4px;
}

.error-detail-value {
    font-size: 14px;
    color: var(--text-primary);
}

.stack-trace {
    background: var(--bg-tertiary);
    padding: 16px;
    border-radius: 8px;
    font-family: monospace;
    font-size: 12px;
    color: var(--text-secondary);
    overflow-x: auto;
    white-space: pre-wrap;
    max-height: 200px;
}

@media (max-width: 768px) {
    .status-bar {
        flex-wrap: wrap;
    }
    
    .status-item {
        flex: 1;
        min-width: 140px;
    }
    
    .metrics-grid {
        grid-template-columns: 1fr;
    }
    
    .gauge-grid {
        flex-wrap: wrap;
        gap: 20px;
    }
}

/* Admin Sessions */
.session-stats {
    display: flex;
    gap: 16px;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border);
}

.stat-box {
    flex: 1;
    text-align: center;
}

.stat-number {
    font-size: 28px;
    font-weight: 700;
    color: var(--primary);
    display: block;
}

.stat-label {
    font-size: 12px;
    color: var(--text-muted);
    display: block;
    margin-top: 4px;
}

.recent-sessions {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.session-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: var(--bg-tertiary);
    border-radius: 8px;
    font-size: 13px;
}

.session-user {
    display: flex;
    flex-direction: column;
}

.user-name {
    font-weight: 500;
    color: var(--text-primary);
}

.user-ip {
    font-size: 12px;
    color: var(--text-muted);
}

.session-time {
    text-align: right;
    font-size: 12px;
}

.time-label {
    color: var(--text-muted);
}

.time-value {
    color: var(--text-primary);
    font-weight: 500;
}

/* Backup Status */
.backup-status-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.backup-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: var(--bg-tertiary);
    border-radius: 8px;
}

.backup-status-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.backup-status-icon.completed {
    background: rgba(16, 185, 129, 0.2);
    color: var(--secondary);
}

.backup-status-icon.failed {
    background: rgba(239, 68, 68, 0.2);
    color: var(--danger);
}

.backup-status-icon.in_progress {
    background: rgba(99, 102, 241, 0.2);
    color: var(--primary);
}

.backup-status-icon .material-icons {
    font-size: 18px;
}

.backup-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.backup-status-text {
    font-weight: 500;
    color: var(--text-primary);
    font-size: 13px;
}

.backup-count {
    font-size: 12px;
    color: var(--text-muted);
}

.backup-date {
    font-size: 11px;
    color: var(--text-muted);
}

/* Security Events */
.security-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border);
}

.security-stat {
    padding: 12px;
    border-radius: 8px;
    text-align: center;
}

.security-stat.total {
    background: rgba(99, 102, 241, 0.1);
}

.security-stat.critical {
    background: rgba(239, 68, 68, 0.1);
}

.security-stat.high {
    background: rgba(245, 158, 11, 0.1);
}

.security-stat.unread {
    background: rgba(59, 130, 246, 0.1);
}

.stat-icon {
    font-size: 20px;
    display: block;
    margin-bottom: 4px;
}

.stat-value {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-primary);
    display: block;
}

.stat-label {
    font-size: 11px;
    color: var(--text-muted);
    display: block;
    margin-top: 4px;
}

.security-events-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.security-event-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 12px;
    background: var(--bg-tertiary);
    border-radius: 6px;
    border-left: 3px solid;
    font-size: 13px;
}

.security-event-item.critical {
    border-left-color: var(--danger);
    background: rgba(239, 68, 68, 0.05);
}

.security-event-item.high {
    border-left-color: var(--warning);
    background: rgba(245, 158, 11, 0.05);
}

.security-event-item.medium {
    border-left-color: var(--info);
    background: rgba(59, 130, 246, 0.05);
}

.security-event-item.low {
    border-left-color: var(--secondary);
    background: rgba(16, 185, 129, 0.05);
}

.event-type {
    font-weight: 500;
    color: var(--text-primary);
    min-width: 150px;
}

.event-ip {
    font-size: 12px;
    color: var(--text-muted);
    flex: 1;
    text-align: center;
}

.event-time {
    font-size: 12px;
    color: var(--text-muted);
    min-width: 50px;
}

/* Activity Log */
.activity-log {
    display: flex;
    flex-direction: column;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid var(--border);
    border-left: 3px solid;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-item.low {
    border-left-color: var(--secondary);
    background: rgba(16, 185, 129, 0.02);
}

.activity-item.medium {
    border-left-color: var(--warning);
    background: rgba(245, 158, 11, 0.02);
}

.activity-item.high {
    border-left-color: var(--danger);
    background: rgba(239, 68, 68, 0.02);
}

.activity-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bg-tertiary);
    flex-shrink: 0;
}

.activity-icon .material-icons {
    font-size: 18px;
    color: var(--primary);
}

.activity-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.activity-action {
    font-weight: 500;
    color: var(--text-primary);
    font-size: 13px;
}

.activity-user {
    font-size: 12px;
    color: var(--text-muted);
}

.activity-time {
    font-size: 11px;
    color: var(--text-muted);
}

@media (max-width: 768px) {
    .security-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .session-item,
    .backup-item,
    .security-event-item {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .session-time,
    .event-ip {
        text-align: left;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Configuration for JavaScript
window.baseUrl = '<?php echo $base_url; ?>';
window.apiStatsData = <?php echo json_encode($apiStats); ?>;
</script>
<script src="<?php echo $base_url; ?>admin-secure/assets/js/admin.js"></script>
<script src="<?php echo $base_url; ?>admin-secure/assets/js/admin-monitoring.js"></script>

<?php include __DIR__ . '/../layouts/admin-footer.php'; ?>
