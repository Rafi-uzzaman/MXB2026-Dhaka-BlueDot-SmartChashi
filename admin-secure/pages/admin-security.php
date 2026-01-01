<?php
/**
 * Admin Security Center
 * Security monitoring, threat detection, and access management
 */
$currPage = "System Security";

require_once __DIR__ . '/../../config/config.php';

// Initialize Database
$db = new Database();

// Security events - get all with proper fields
$events = $db->resultSet("
    SELECT 
        event_id,
        event_type,
        severity,
        user_id,
        ip_address,
        description,
        is_acknowledged,
        acknowledged_by,
        acknowledged_at,
        action_taken,
        auto_blocked,
        created_at
    FROM security_events 
    ORDER BY created_at DESC 
    LIMIT 20
") ?? [];

// Failed login attempts (last 24 hours)
$failedLogins = $db->resultSet("
    SELECT 
        id,
        ip_address,
        email,
        attempted_at,
        success,
        failure_reason,
        geo_country,
        geo_city,
        user_agent
    FROM admin_login_attempts 
    WHERE success = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) 
    ORDER BY attempted_at DESC 
    LIMIT 20
") ?? [];

// Active admin sessions
$sessions = $db->resultSet("
    SELECT 
        s.session_id,
        s.user_id,
        s.ip_address,
        s.user_agent,
        s.device_fingerprint,
        s.login_at,
        s.last_activity,
        s.expires_at,
        u.first_name,
        u.last_name,
        u.email 
    FROM admin_sessions s 
    LEFT JOIN users u ON s.user_id = u.user_id 
    WHERE s.is_active = 1 AND s.expires_at > NOW()
    ORDER BY s.last_activity DESC
") ?? [];

// IP rules (whitelist/blacklist) - not expired
$ipRules = $db->resultSet("
    SELECT 
        rule_id,
        ip_address,
        ip_range_start,
        ip_range_end,
        rule_type,
        country_code,
        reason,
        auto_created,
        created_by,
        created_at,
        expires_at
    FROM admin_ip_rules 
    WHERE (expires_at IS NULL OR expires_at > NOW()) 
    ORDER BY created_at DESC
") ?? [];

// Security statistics
$securityStats = $db->single("
    SELECT 
        COUNT(*) as total_events,
        SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical_count,
        SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) as high_count,
        SUM(CASE WHEN is_acknowledged = 0 THEN 1 ELSE 0 END) as unacknowledged_count,
        SUM(CASE WHEN auto_blocked = 1 THEN 1 ELSE 0 END) as auto_blocked_count
    FROM security_events
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
") ?? [];

// Failed logins in last 24 hours
$failedLoginsCount = $db->single("
    SELECT COUNT(*) as count 
    FROM admin_login_attempts 
    WHERE success = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
")['count'] ?? 0;

// Count blocked IPs
$blockedIPsCount = $db->single("
    SELECT COUNT(*) as count 
    FROM admin_ip_rules 
    WHERE rule_type = 'blacklist' AND (expires_at IS NULL OR expires_at > NOW())
")['count'] ?? 0;

// Determine threat level based on statistics
$threatLevel = 'low';
$threatValue = 'LOW';
if ($securityStats['critical_count'] > 0) {
    $threatLevel = 'critical';
    $threatValue = 'CRITICAL';
} elseif ($securityStats['high_count'] >= 3) {
    $threatLevel = 'high';
    $threatValue = 'HIGH';
} elseif ($securityStats['high_count'] > 0 || $failedLoginsCount > 10) {
    $threatLevel = 'medium';
    $threatValue = 'MEDIUM';
}

require_once __DIR__ . '/../layouts/admin-header.php';
?>

<!-- Additional CSS for Security Page -->
<link rel="stylesheet" href="<?php echo $base_url; ?>admin-secure/assets/css/admin-security.css">

<script>
// Set base URL for AJAX calls
window.adminBaseUrl = '<?php echo $base_url; ?>';
</script>

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title">Security Center</h1>
        <p class="page-subtitle">Monitor threats, manage access, and configure security settings</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-secondary" onclick="window.location.href='<?php echo $base_url; ?>admin-settings'">
            <span class="material-icons">settings</span>
            Settings
        </button>
    </div>
</div>

<!-- Threat Level Indicator -->
<div class="threat-level <?php echo $threatLevel; ?>" id="threatLevel">
    <div class="threat-icon">
        <span class="material-icons">shield</span>
    </div>
    <div class="threat-info">
        <span class="threat-label">Threat Level</span>
        <span class="threat-value" id="threatValue"><?php echo $threatValue; ?></span>
    </div>
    <div class="threat-stats">
        <span id="unacknowledgedCount"><?php echo $securityStats['unacknowledged_count'] ?? 0; ?> unacknowledged events</span>
        <span id="failedLoginsCount"><?php echo $failedLoginsCount; ?> failed logins (24h)</span>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card mini">
        <div class="stat-icon security">
            <span class="material-icons">block</span>
        </div>
        <div class="stat-content">
            <span class="stat-value" id="blockedIPsCard"><?php echo $blockedIPsCount; ?></span>
            <span class="stat-label">Blocked IPs</span>
        </div>
    </div>
    <div class="stat-card mini">
        <div class="stat-icon logins">
            <span class="material-icons">error_outline</span>
        </div>
        <div class="stat-content">
            <span class="stat-value" id="failedLoginsCard"><?php echo $failedLoginsCount; ?></span>
            <span class="stat-label">Failed Logins (24h)</span>
        </div>
    </div>
    <div class="stat-card mini">
        <div class="stat-icon sessions">
            <span class="material-icons">devices</span>
        </div>
        <div class="stat-content">
            <span class="stat-value" id="activeSessionsCard"><?php echo count($sessions); ?></span>
            <span class="stat-label">Active Sessions</span>
        </div>
    </div>
    <div class="stat-card mini">
        <div class="stat-icon users">
            <span class="material-icons">warning</span>
        </div>
        <div class="stat-content">
            <span class="stat-value" id="securityEventsCard"><?php echo $securityStats['unacknowledged_count'] ?? 0; ?></span>
            <span class="stat-label">Unacknowledged Events</span>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="security-tabs">
    <button class="tab-btn active" data-tab="events">Security Events</button>
    <button class="tab-btn" data-tab="logins">Failed Logins</button>
    <button class="tab-btn" data-tab="sessions">Active Sessions</button>
    <button class="tab-btn" data-tab="ip-rules">IP Rules</button>
</div>

<!-- Security Events Tab -->
<div class="tab-content active" id="tab-events">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">warning</span>
                Recent Security Events
            </h3>
            <div class="card-actions">
                <button class="btn btn-sm btn-secondary" onclick="acknowledgeAllEvents()">Acknowledge All</button>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($events)): ?>
                <div class="empty-state">
                    <span class="material-icons">verified_user</span>
                    <p>No security events</p>
                </div>
            <?php else: ?>
                <div class="events-list">
                    <?php foreach ($events as $event): ?>
                        <div class="event-item <?php echo $event['severity']; ?> <?php echo $event['is_acknowledged'] ? 'acknowledged' : ''; ?>">
                            <div class="event-severity">
                                <span class="material-icons">
                                    <?php 
                                    echo match($event['severity']) {
                                        'critical' => 'error',
                                        'high' => 'warning',
                                        'medium' => 'info',
                                        default => 'info'
                                    };
                                    ?>
                                </span>
                            </div>
                            <div class="event-content">
                                <div class="event-header">
                                    <span class="event-type"><?php echo ucwords(str_replace('_', ' ', $event['event_type'])); ?></span>
                                    <span class="severity-badge <?php echo $event['severity']; ?>"><?php echo ucfirst($event['severity']); ?></span>
                                </div>
                                <p class="event-description"><?php echo htmlspecialchars($event['description'] ?? ''); ?></p>
                                <div class="event-meta">
                                    <span><span class="material-icons">location_on</span> <?php echo htmlspecialchars($event['ip_address'] ?? 'Unknown'); ?></span>
                                    <span><span class="material-icons">schedule</span> <?php echo date('M d, H:i', strtotime($event['created_at'])); ?></span>
                                </div>
                            </div>
                            <?php if (!$event['is_acknowledged']): ?>
                                <button class="btn btn-sm btn-ghost" onclick="acknowledgeEvent(<?php echo $event['event_id']; ?>)">
                                    <span class="material-icons">check</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Failed Logins Tab -->
<div class="tab-content" id="tab-logins">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">error_outline</span>
                Failed Login Attempts
            </h3>
        </div>
        <div class="card-body no-padding">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>IP Address</th>
                        <th>Email</th>
                        <th>Reason</th>
                        <th>Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($failedLogins)): ?>
                        <tr><td colspan="5"><div class="empty-state small">No failed login attempts</div></td></tr>
                    <?php else: ?>
                        <?php foreach ($failedLogins as $login): ?>
                            <tr>
                                <td>
                                    <code class="ip-code"><?php echo htmlspecialchars($login['ip_address']); ?></code>
                                </td>
                                <td><?php echo htmlspecialchars($login['email'] ?? '-'); ?></td>
                                <td><span class="reason-badge"><?php echo htmlspecialchars($login['failure_reason'] ?? 'Unknown'); ?></span></td>
                                <td class="date-cell"><?php echo date('M d, H:i', strtotime($login['attempted_at'])); ?></td>
                                <td>
                                    <button class="action-btn" onclick="blockIP('<?php echo htmlspecialchars($login['ip_address']); ?>')" title="Block IP">
                                        <span class="material-icons">block</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Active Sessions Tab -->
<div class="tab-content" id="tab-sessions">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">devices</span>
                Active Admin Sessions
            </h3>
            <div class="card-actions">
                <button class="btn btn-sm btn-danger" onclick="terminateAllSessions()">Terminate All</button>
            </div>
        </div>
        <div class="card-body no-padding">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>IP Address</th>
                        <th>Device</th>
                        <th>Login Time</th>
                        <th>Last Activity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sessions)): ?>
                        <tr><td colspan="6"><div class="empty-state small">No active sessions</div></td></tr>
                    <?php else: ?>
                        <?php foreach ($sessions as $session): ?>
                            <tr class="<?php echo $session['session_id'] === ($_SESSION['admin_session_id'] ?? '') ? 'current-session' : ''; ?>">
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar-xs"><?php echo strtoupper(substr($session['first_name'], 0, 1)); ?></div>
                                        <div>
                                            <div class="user-cell-name"><?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?></div>
                                            <div class="user-cell-email"><?php echo htmlspecialchars($session['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><code class="ip-code"><?php echo htmlspecialchars($session['ip_address']); ?></code></td>
                                <td class="device-cell">
                                    <?php 
                                    $ua = $session['user_agent'] ?? '';
                                    if (strpos($ua, 'Chrome') !== false) echo '<span class="material-icons">laptop</span> Chrome';
                                    elseif (strpos($ua, 'Firefox') !== false) echo '<span class="material-icons">laptop</span> Firefox';
                                    elseif (strpos($ua, 'Safari') !== false) echo '<span class="material-icons">laptop</span> Safari';
                                    else echo '<span class="material-icons">devices</span> Unknown';
                                    ?>
                                </td>
                                <td class="date-cell"><?php echo date('M d, H:i', strtotime($session['login_at'])); ?></td>
                                <td class="date-cell"><?php echo date('H:i:s', strtotime($session['last_activity'])); ?></td>
                                <td>
                                    <?php if ($session['session_id'] !== ($_SESSION['admin_session_id'] ?? '')): ?>
                                        <button class="action-btn danger" onclick="terminateSession('<?php echo htmlspecialchars($session['session_id']); ?>')" title="Terminate">
                                            <span class="material-icons">logout</span>
                                        </button>
                                    <?php else: ?>
                                        <span class="current-badge">Current</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- IP Rules Tab -->
<div class="tab-content" id="tab-ip-rules">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">security</span>
                IP Whitelist / Blacklist
            </h3>
            <div class="card-actions">
                <button class="btn btn-sm btn-primary" onclick="showAddIPModal()">
                    <span class="material-icons">add</span>
                    Add Rule
                </button>
            </div>
        </div>
        <div class="card-body no-padding">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>IP Address</th>
                        <th>Type</th>
                        <th>Reason</th>
                        <th>Created</th>
                        <th>Expires</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ipRules)): ?>
                        <tr><td colspan="6"><div class="empty-state small">No IP rules configured</div></td></tr>
                    <?php else: ?>
                        <?php foreach ($ipRules as $rule): ?>
                            <tr>
                                <td><code class="ip-code"><?php echo htmlspecialchars($rule['ip_address']); ?></code></td>
                                <td>
                                    <span class="rule-type <?php echo $rule['rule_type']; ?>">
                                        <?php echo ucfirst($rule['rule_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($rule['reason'] ?? '-'); ?></td>
                                <td class="date-cell"><?php echo date('M d, Y', strtotime($rule['created_at'])); ?></td>
                                <td class="date-cell">
                                    <?php echo $rule['expires_at'] ? date('M d, Y', strtotime($rule['expires_at'])) : 'Never'; ?>
                                </td>
                                <td>
                                    <button class="action-btn danger" onclick="deleteIPRule(<?php echo $rule['rule_id']; ?>)" title="Delete">
                                        <span class="material-icons">delete</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add IP Rule Modal -->
<div class="modal-overlay" id="add-ip-modal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Add IP Rule</h3>
            <button class="modal-close" type="button">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="modal-body">
            <form id="ip-form">
                <div class="form-group">
                    <label class="form-label">IP Address *</label>
                    <input type="text" id="ip-address" class="form-input" placeholder="e.g., 192.168.1.1" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Rule Type *</label>
                    <select id="rule-type" class="form-input" required>
                        <option value="blacklist">Blacklist (Block)</option>
                        <option value="whitelist">Whitelist (Allow)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Reason</label>
                    <input type="text" id="ip-reason" class="form-input" placeholder="Optional reason">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" type="button" onclick="hideAddIPModal()">
                Cancel
            </button>
            <button class="btn btn-primary" type="button" onclick="addIPRule(event)">
                <span class="material-icons">add</span>
                Add Rule
            </button>
        </div>
    </div>
</div>

<style>
/* Threat Level Indicator */
.threat-level {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px 24px;
    border-radius: var(--border-radius-lg);
    margin-bottom: 24px;
    border: 2px solid;
}

.threat-level.low {
    background: rgba(16, 185, 129, 0.1);
    border-color: var(--secondary);
}

.threat-level.medium {
    background: rgba(245, 158, 11, 0.1);
    border-color: var(--warning);
}

.threat-level.high {
    background: rgba(249, 115, 22, 0.1);
    border-color: #f97316;
}

.threat-level.critical {
    background: rgba(239, 68, 68, 0.1);
    border-color: var(--danger);
    animation: pulse-border 2s infinite;
}

@keyframes pulse-border {
    0%, 100% { border-color: var(--danger); }
    50% { border-color: rgba(239, 68, 68, 0.5); }
}

.threat-icon {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.threat-level.low .threat-icon {
    background: var(--secondary);
    color: white;
}

.threat-level.medium .threat-icon {
    background: var(--warning);
    color: white;
}

.threat-level.high .threat-icon {
    background: #f97316;
    color: white;
}

.threat-level.critical .threat-icon {
    background: var(--danger);
    color: white;
}

.threat-icon .material-icons {
    font-size: 28px;
}

.threat-info {
    flex: 1;
}

.threat-label {
    display: block;
    font-size: 12px;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 4px;
}

.threat-value {
    font-size: 24px;
    font-weight: 700;
}

.threat-level.low .threat-value { color: var(--secondary); }
.threat-level.medium .threat-value { color: var(--warning); }
.threat-level.high .threat-value { color: #f97316; }
.threat-level.critical .threat-value { color: var(--danger); }

.threat-stats {
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 13px;
    color: var(--text-muted);
}

/* Tabs */
.security-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
    padding: 4px;
    background: var(--bg-card);
    border-radius: var(--border-radius);
    border: 1px solid var(--border);
}

.tab-btn {
    padding: 10px 20px;
    background: transparent;
    border: none;
    border-radius: var(--border-radius-sm);
    font-size: 14px;
    font-weight: 500;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.2s;
}

.tab-btn:hover {
    color: var(--text-primary);
}

.tab-btn.active {
    background: var(--primary);
    color: white;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* Events List */
.events-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.event-item {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 16px;
    background: var(--bg-tertiary);
    border-radius: var(--border-radius);
    border-left: 4px solid;
}

.event-item.critical { border-left-color: var(--danger); }
.event-item.high { border-left-color: #f97316; }
.event-item.medium { border-left-color: var(--warning); }
.event-item.low { border-left-color: var(--info); }

.event-item.acknowledged {
    opacity: 0.6;
}

.event-severity {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.event-item.critical .event-severity { background: rgba(239, 68, 68, 0.2); color: var(--danger); }
.event-item.high .event-severity { background: rgba(249, 115, 22, 0.2); color: #f97316; }
.event-item.medium .event-severity { background: rgba(245, 158, 11, 0.2); color: var(--warning); }
.event-item.low .event-severity { background: rgba(59, 130, 246, 0.2); color: var(--info); }

.event-content {
    flex: 1;
}

.event-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 4px;
}

.event-type {
    font-weight: 600;
    color: var(--text-primary);
}

.event-description {
    font-size: 13px;
    color: var(--text-secondary);
    margin-bottom: 8px;
}

.event-meta {
    display: flex;
    gap: 16px;
    font-size: 12px;
    color: var(--text-muted);
}

.event-meta span {
    display: flex;
    align-items: center;
    gap: 4px;
}

.event-meta .material-icons {
    font-size: 14px;
}

/* Table Styles */
.ip-code {
    padding: 4px 8px;
    background: var(--bg-tertiary);
    border-radius: 4px;
    font-family: monospace;
    font-size: 13px;
}

.reason-badge {
    padding: 4px 8px;
    background: rgba(245, 158, 11, 0.2);
    color: var(--warning);
    border-radius: 6px;
    font-size: 12px;
}

.device-cell {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
}

.device-cell .material-icons {
    font-size: 18px;
    color: var(--text-muted);
}

.current-session {
    background: rgba(99, 102, 241, 0.1);
}

.current-badge {
    padding: 4px 8px;
    background: var(--primary);
    color: white;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
}

.user-cell-email {
    font-size: 12px;
    color: var(--text-muted);
}

.rule-type {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.rule-type.blacklist {
    background: rgba(239, 68, 68, 0.2);
    color: var(--danger);
}

.rule-type.whitelist {
    background: rgba(16, 185, 129, 0.2);
    color: var(--secondary);
}

/* Additional Security Page Styles */
.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 30px;
}

.page-header-content {
    flex: 1;
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

.page-actions {
    display: flex;
    gap: 12px;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: var(--border-radius);
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.3s;
}

.stat-card:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
    transform: translateY(-2px);
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.stat-icon.security {
    background: rgba(239, 68, 68, 0.2);
    color: var(--danger);
}

.stat-icon.logins {
    background: rgba(249, 115, 22, 0.2);
    color: #f97316;
}

.stat-icon.sessions {
    background: rgba(16, 185, 129, 0.2);
    color: var(--secondary);
}

.stat-icon.users {
    background: rgba(245, 158, 11, 0.2);
    color: var(--warning);
}

.stat-value {
    display: block;
    font-size: 24px;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1;
    margin-bottom: 4px;
}

.stat-label {
    display: block;
    font-size: 12px;
    color: var(--text-muted);
    font-weight: 500;
}

/* Card Styles */
.card {
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: var(--border-radius);
    overflow: hidden;
    margin-bottom: 24px;
}

.card-header {
    padding: 20px;
    background: var(--bg-tertiary);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
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

.card-actions {
    display: flex;
    gap: 10px;
}

.card-body {
    padding: 20px;
}

.card-body.no-padding {
    padding: 0;
}

/* Table Styles */
.table-responsive {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table thead {
    background: var(--bg-tertiary);
}

.data-table thead th {
    padding: 12px 16px;
    text-align: left;
    font-size: 12px;
    font-weight: 600;
    color: var(--text-primary);
    border-bottom: 1px solid var(--border);
}

.data-table tbody tr {
    border-bottom: 1px solid var(--border);
    transition: background 0.2s;
}

.data-table tbody tr:hover {
    background: var(--bg-tertiary);
}

.data-table tbody td {
    padding: 16px;
    font-size: 13px;
    color: var(--text-secondary);
}

.date-cell {
    font-size: 13px;
    color: var(--text-muted);
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

.btn-sm {
    padding: 8px 12px;
    font-size: 12px;
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

.btn-ghost {
    background: transparent;
    color: var(--text-muted);
    border: 1px solid transparent;
}

.btn-ghost:hover {
    background: var(--bg-tertiary);
    color: var(--primary);
}

/* Action Buttons */
.action-btn {
    background: transparent;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 6px;
    border-radius: 6px;
    transition: all 0.2s;
    display: flex;
    align-items: center;
}

.action-btn:hover {
    background: var(--bg-secondary);
    color: var(--primary);
}

.action-btn.danger:hover {
    color: var(--danger);
}

/* Form Styles */
.form-group {
    margin-bottom: 16px;
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
    color: var(--text-primary);
    font-size: 14px;
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

/* Modal Styles */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    overflow-y: auto;
    padding: 20px;
}

.modal-overlay.active {
    display: flex;
}

.modal-box {
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
    width: 100%;
    max-width: 500px;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
    position: relative;
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-header h3 {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.modal-close {
    background: transparent;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: all 0.2s;
    display: flex;
    align-items: center;
}

.modal-close:hover {
    background: var(--bg-tertiary);
    color: var(--text-primary);
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 24px;
    background: var(--bg-tertiary);
    border-top: 1px solid var(--border);
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    color: var(--text-muted);
}

.empty-state.small {
    padding: 40px 20px;
}

.empty-state .material-icons {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.3;
}

.empty-state p {
    margin: 0;
    font-size: 14px;
}

/* Severity Badge */
.severity-badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.severity-badge.critical {
    background: rgba(239, 68, 68, 0.2);
    color: var(--danger);
}

.severity-badge.high {
    background: rgba(249, 115, 22, 0.2);
    color: #f97316;
}

.severity-badge.medium {
    background: rgba(245, 158, 11, 0.2);
    color: var(--warning);
}

.severity-badge.low {
    background: rgba(59, 130, 246, 0.2);
    color: var(--info);
}

/* Responsive Design */
@media (max-width: 1024px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
}

@media (max-width: 768px) {
    .threat-level {
        flex-wrap: wrap;
    }
    
    .threat-stats {
        width: 100%;
        flex-direction: row;
        justify-content: space-between;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid var(--border);
    }
    
    .security-tabs {
        overflow-x: auto;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .data-table {
        font-size: 12px;
    }
    
    .data-table thead th,
    .data-table tbody td {
        padding: 8px 12px;
    }
    
    .modal-box {
        width: 95%;
    }
}
</style>

<script src="<?php echo $base_url; ?>admin-secure/assets/js/admin-security.js"></script>

<?php include __DIR__ . '/../layouts/admin-footer.php'; ?>
