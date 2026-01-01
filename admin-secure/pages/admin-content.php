<?php
/**
 * Admin Content Moderation
 * Manage reported content, user warnings, and bans
 */

$currPage = "Content";

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../layouts/admin-header.php';

$db = new Database();

// Get report stats
$pendingReports = $db->single("SELECT COUNT(*) as count FROM content_reports WHERE status = 'pending'")['count'] ?? 0;
$resolvedToday = $db->single("SELECT COUNT(*) as count FROM content_reports WHERE status = 'resolved' AND DATE(reviewed_at) = CURDATE()")['count'] ?? 0;
$totalWarnings = $db->single("SELECT COUNT(*) as count FROM user_warnings WHERE acknowledged = 0")['count'] ?? 0;
$activeBans = $db->single("SELECT COUNT(*) as count FROM user_bans WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())")['count'] ?? 0;

// Get pending reports (with reporter and reported user info)
$reports = $db->resultSet("SELECT cr.report_id, cr.content_type, cr.content_id, cr.report_reason, cr.report_details, 
    cr.status, cr.priority, cr.assigned_to, cr.action_taken, cr.created_at,
    u.user_id as reporter_user_id, u.first_name, u.last_name, u.email as reporter_email 
    FROM content_reports cr 
    LEFT JOIN users u ON cr.reporter_id = u.user_id 
    WHERE cr.status = 'pending' 
    ORDER BY cr.created_at DESC LIMIT 20");

// Get recent warnings
$warnings = $db->resultSet("SELECT uw.warning_id, uw.user_id, uw.warning_type, uw.severity, uw.reason, 
    uw.created_at, uw.acknowledged, u.first_name, u.last_name, u.email, 
    a.first_name as admin_first, a.last_name as admin_last
    FROM user_warnings uw 
    LEFT JOIN users u ON uw.user_id = u.user_id 
    LEFT JOIN users a ON uw.issued_by = a.user_id 
    ORDER BY uw.created_at DESC LIMIT 10");

// Get active bans
$bans = $db->resultSet("SELECT ub.ban_id, ub.user_id, ub.ban_type, ub.reason, ub.banned_at, ub.expires_at, ub.is_active,
    u.first_name, u.last_name, u.email, 
    a.first_name as admin_first, a.last_name as admin_last
    FROM user_bans ub 
    LEFT JOIN users u ON ub.user_id = u.user_id 
    LEFT JOIN users a ON ub.banned_by = a.user_id 
    WHERE ub.is_active = 1 
    ORDER BY ub.banned_at DESC LIMIT 10");
?>

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title">Content Moderation</h1>
        <p class="page-subtitle">Review reported content and manage user warnings</p>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card mini <?php echo $pendingReports > 0 ? 'warning' : ''; ?>">
        <div class="stat-icon security">
            <span class="material-icons">report</span>
        </div>
        <div class="stat-content">
            <span class="stat-value"><?php echo $pendingReports; ?></span>
            <span class="stat-label">Pending Reports</span>
        </div>
    </div>
    <div class="stat-card mini">
        <div class="stat-icon sessions">
            <span class="material-icons">check_circle</span>
        </div>
        <div class="stat-content">
            <span class="stat-value"><?php echo $resolvedToday; ?></span>
            <span class="stat-label">Resolved Today</span>
        </div>
    </div>
    <div class="stat-card mini">
        <div class="stat-icon logins">
            <span class="material-icons">warning</span>
        </div>
        <div class="stat-content">
            <span class="stat-value"><?php echo $totalWarnings; ?></span>
            <span class="stat-label">Active Warnings</span>
        </div>
    </div>
    <div class="stat-card mini">
        <div class="stat-icon users">
            <span class="material-icons">block</span>
        </div>
        <div class="stat-content">
            <span class="stat-value"><?php echo $activeBans; ?></span>
            <span class="stat-label">Active Bans</span>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="security-tabs">
    <button class="tab-btn active" data-tab="reports">Reports (<?php echo $pendingReports; ?>)</button>
    <button class="tab-btn" data-tab="warnings">Warnings</button>
    <button class="tab-btn" data-tab="bans">Bans</button>
</div>

<!-- Reports Tab -->
<div class="tab-content active" id="tab-reports">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">report</span>
                Pending Reports
            </h3>
        </div>
        <div class="card-body">
            <?php if (empty($reports)): ?>
                <div class="empty-state">
                    <span class="material-icons">check_circle</span>
                    <p>No pending reports</p>
                </div>
            <?php else: ?>
                <div class="reports-list">
                    <?php foreach ($reports as $report): ?>
                        <div class="report-item" data-id="<?php echo $report['report_id']; ?>">
                            <div class="report-type <?php echo strtolower($report['content_type'] ?? 'post'); ?>">
                                <span class="material-icons">
                                    <?php 
                                    echo match($report['content_type'] ?? 'post') {
                                        'post' => 'article',
                                        'comment' => 'chat_bubble',
                                        'product' => 'storefront',
                                        'user' => 'person',
                                        'message' => 'email',
                                        'review' => 'rate_review',
                                        default => 'flag'
                                    };
                                    ?>
                                </span>
                            </div>
                            <div class="report-content">
                                <div class="report-header">
                                    <span class="report-content-type"><?php echo ucfirst($report['content_type'] ?? 'Post'); ?></span>
                                    <span class="report-reason"><?php echo ucwords(str_replace('_', ' ', $report['report_reason'])); ?></span>
                                </div>
                                <p class="report-description"><?php echo htmlspecialchars($report['report_details'] ?? ''); ?></p>
                                <div class="report-meta">
                                    <span>
                                        <strong>Content ID:</strong> 
                                        <?php echo htmlspecialchars($report['content_id'] ?? 'N/A'); ?>
                                    </span>
                                    <span>
                                        <strong>By:</strong> 
                                        <?php echo htmlspecialchars(($report['first_name'] ?? 'Anonymous') . ' ' . ($report['last_name'] ?? '')); ?>
                                    </span>
                                    <span>
                                        <span class="material-icons">schedule</span>
                                        <?php echo date('M d, H:i', strtotime($report['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="report-actions">
                                <button class="btn btn-sm btn-secondary" onclick="viewContent(<?php echo $report['report_id']; ?>)">
                                    <span class="material-icons">visibility</span>
                                    View
                                </button>
                                <button class="btn btn-sm btn-primary" onclick="resolveReport(<?php echo $report['report_id']; ?>, 'dismissed')">
                                    Dismiss
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="showActionModal(<?php echo $report['report_id']; ?>, <?php echo htmlspecialchars($report['content_id'] ?? 0); ?>)">
                                    Take Action
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Warnings Tab -->
<div class="tab-content" id="tab-warnings">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">warning</span>
                User Warnings
            </h3>
            <button class="btn btn-sm btn-primary" onclick="showWarningModal()">
                <span class="material-icons">add</span>
                Issue Warning
            </button>
        </div>
        <div class="card-body no-padding">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Reason</th>
                        <th>Severity</th>
                        <th>Issued By</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($warnings)): ?>
                        <tr><td colspan="6"><div class="empty-state small">No warnings issued</div></td></tr>
                    <?php else: ?>
                        <?php foreach ($warnings as $warning): ?>
                            <tr>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar-xs"><?php echo strtoupper(substr($warning['first_name'] ?? 'U', 0, 1)); ?></div>
                                        <div>
                                            <div class="user-cell-name"><?php echo htmlspecialchars(($warning['first_name'] ?? '') . ' ' . ($warning['last_name'] ?? '')); ?></div>
                                            <div class="user-cell-email"><?php echo htmlspecialchars($warning['email'] ?? ''); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($warning['reason']); ?></td>
                                <td>
                                    <span class="severity-badge <?php echo $warning['severity']; ?>">
                                        <?php echo ucfirst($warning['severity']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($warning['admin_first'] ?? 'System'); ?></td>
                                <td class="date-cell"><?php echo date('M d, Y', strtotime($warning['created_at'])); ?></td>
                                <td>
                                    <?php if ($warning['acknowledged']): ?>
                                        <span class="status-badge active">Acknowledged</span>
                                    <?php else: ?>
                                        <span class="status-badge inactive">Pending</span>
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

<!-- Bans Tab -->
<div class="tab-content" id="tab-bans">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">block</span>
                Active Bans
            </h3>
        </div>
        <div class="card-body no-padding">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Type</th>
                        <th>Reason</th>
                        <th>Banned By</th>
                        <th>Expires</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bans)): ?>
                        <tr><td colspan="6"><div class="empty-state small">No active bans</div></td></tr>
                    <?php else: ?>
                        <?php foreach ($bans as $ban): ?>
                            <tr>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar-xs"><?php echo strtoupper(substr($ban['first_name'] ?? 'U', 0, 1)); ?></div>
                                        <div>
                                            <div class="user-cell-name"><?php echo htmlspecialchars(($ban['first_name'] ?? '') . ' ' . ($ban['last_name'] ?? '')); ?></div>
                                            <div class="user-cell-email"><?php echo htmlspecialchars($ban['email'] ?? ''); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="ban-type <?php echo $ban['ban_type']; ?>">
                                        <?php echo ucfirst($ban['ban_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($ban['reason']); ?></td>
                                <td><?php echo htmlspecialchars($ban['admin_first'] ?? 'System'); ?></td>
                                <td class="date-cell">
                                    <?php echo $ban['expires_at'] ? date('M d, Y', strtotime($ban['expires_at'])) : 'Never'; ?>
                                </td>
                                <td>
                                    <button class="action-btn" onclick="unbanUser(<?php echo $ban['ban_id']; ?>)" title="Unban">
                                        <span class="material-icons">lock_open</span>
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

<!-- Action Modal -->
<div class="modal-overlay" id="actionModal">
    <div class="modal-box action-modal">
        <div class="modal-header">
            <h3>Take Action</h3>
            <button class="modal-close" onclick="hideActionModal()">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="actionReportId">
            <input type="hidden" id="actionUserId">
            
            <div class="action-options">
                <label class="action-option">
                    <input type="radio" name="action_type" value="warn" checked>
                    <div class="action-option-content">
                        <span class="material-icons">warning</span>
                        <div>
                            <strong>Issue Warning</strong>
                            <span>Send a warning to the user</span>
                        </div>
                    </div>
                </label>
                
                <label class="action-option">
                    <input type="radio" name="action_type" value="delete">
                    <div class="action-option-content">
                        <span class="material-icons">delete</span>
                        <div>
                            <strong>Delete Content</strong>
                            <span>Remove the reported content</span>
                        </div>
                    </div>
                </label>
                
                <label class="action-option">
                    <input type="radio" name="action_type" value="ban_temp">
                    <div class="action-option-content">
                        <span class="material-icons">timer</span>
                        <div>
                            <strong>Temporary Ban</strong>
                            <span>Suspend user for a period</span>
                        </div>
                    </div>
                </label>
                
                <label class="action-option">
                    <input type="radio" name="action_type" value="ban_perm">
                    <div class="action-option-content">
                        <span class="material-icons">block</span>
                        <div>
                            <strong>Permanent Ban</strong>
                            <span>Remove user from platform</span>
                        </div>
                    </div>
                </label>
            </div>
            
            <div class="form-group" id="banDurationGroup" style="display: none;">
                <label class="form-label">Ban Duration (days)</label>
                <input type="number" id="actionBanDuration" class="form-input" value="7" min="1" max="365">
            </div>
            
            <div class="form-group">
                <label class="form-label">Reason / Notes</label>
                <textarea id="actionReason" class="form-input" rows="3" placeholder="Explain the action..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="hideActionModal()">Cancel</button>
            <button type="button" class="btn btn-danger" onclick="executeAction()">Confirm Action</button>
        </div>
    </div>
</div>

<!-- Warning Modal -->
<div class="modal-overlay" id="warningModal">
    <div class="modal-box warning-modal">
        <div class="modal-header">
            <h3>Issue Warning</h3>
            <button class="modal-close" onclick="hideWarningModal()">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="modal-body">
            <form id="warningForm">
                <div class="form-group">
                    <label class="form-label">User Email</label>
                    <input type="email" id="warningUserEmail" name="email" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Severity</label>
                    <select id="warningSeverity" name="severity" class="form-input">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Reason</label>
                    <textarea id="warningReason" name="reason" class="form-input" rows="3" required></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="hideWarningModal()">Cancel</button>
            <button type="submit" form="warningForm" class="btn btn-warning">Issue Warning</button>
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

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.stat-card.mini {
    padding: 16px;
}

.stat-card.warning {
    background: rgba(245, 158, 11, 0.1);
    border-color: var(--warning);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.stat-icon.security {
    background: rgba(239, 68, 68, 0.2);
    color: var(--danger);
}

.stat-icon.sessions {
    background: rgba(16, 185, 129, 0.2);
    color: var(--secondary);
}

.stat-icon.logins {
    background: rgba(245, 158, 11, 0.2);
    color: var(--warning);
}

.stat-icon.users {
    background: rgba(99, 102, 241, 0.2);
    color: var(--primary);
}

.stat-content {
    flex: 1;
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

/* Tabs */
.security-tabs {
    display: flex;
    gap: 0;
    border-bottom: 2px solid var(--border);
    margin-bottom: 30px;
    overflow-x: auto;
}

.tab-btn {
    padding: 12px 20px;
    background: none;
    border: none;
    color: var(--text-muted);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    position: relative;
    white-space: nowrap;
    transition: all 0.3s;
}

.tab-btn:hover {
    color: var(--text-primary);
}

.tab-btn.active {
    color: var(--primary);
}

.tab-btn.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 2px;
    background: var(--primary);
}

/* Tab Content */
.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
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
    gap: 16px;
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

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    text-align: center;
    color: var(--text-muted);
}

.empty-state .material-icons {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-state p {
    font-size: 14px;
    margin: 0;
}

.empty-state.small {
    padding: 30px 20px;
}

.empty-state.small .material-icons {
    font-size: 32px;
}

/* Reports List */
.reports-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.report-item {
    display: flex;
    gap: 16px;
    padding: 20px;
    background: var(--bg-tertiary);
    border-radius: var(--border-radius);
    border-left: 4px solid var(--warning);
    transition: all 0.3s;
}

.report-item:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateX(4px);
}

.report-type {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 24px;
}

.report-type.post { background: rgba(99, 102, 241, 0.2); color: var(--primary); }
.report-type.comment { background: rgba(16, 185, 129, 0.2); color: var(--secondary); }
.report-type.product { background: rgba(245, 158, 11, 0.2); color: var(--warning); }
.report-type.message { background: rgba(239, 68, 68, 0.2); color: var(--danger); }
.report-type.user { background: rgba(139, 92, 246, 0.2); color: #8b5cf6; }
.report-type.review { background: rgba(249, 115, 22, 0.2); color: #f97316; }

.report-content {
    flex: 1;
    min-width: 0;
}

.report-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
    flex-wrap: wrap;
}

.report-content-type {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 14px;
}

.report-reason {
    padding: 4px 10px;
    background: rgba(245, 158, 11, 0.2);
    color: var(--warning);
    border-radius: 10px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}

.report-description {
    font-size: 14px;
    color: var(--text-secondary);
    margin-bottom: 12px;
    line-height: 1.5;
    word-break: break-word;
}

.report-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    font-size: 12px;
    color: var(--text-muted);
}

.report-meta span {
    display: flex;
    align-items: center;
    gap: 4px;
}

.report-meta strong {
    color: var(--text-primary);
}

.report-meta .material-icons {
    font-size: 14px;
}

.report-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex-shrink: 0;
}

/* Data Table */
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
    font-weight: 600;
    font-size: 12px;
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

.data-table .date-cell {
    font-size: 12px;
    color: var(--text-muted);
}

/* User Cell */
.user-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-avatar-xs {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    flex-shrink: 0;
}

.user-cell-name {
    font-weight: 500;
    color: var(--text-primary);
    font-size: 13px;
}

.user-cell-email {
    font-size: 12px;
    color: var(--text-muted);
}

/* Severity Badge */
.severity-badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.severity-badge.minor {
    background: rgba(34, 197, 94, 0.2);
    color: #22c55e;
}

.severity-badge.moderate {
    background: rgba(245, 158, 11, 0.2);
    color: var(--warning);
}

.severity-badge.severe {
    background: rgba(239, 68, 68, 0.2);
    color: var(--danger);
}

.severity-badge.low {
    background: rgba(34, 197, 94, 0.2);
    color: #22c55e;
}

.severity-badge.medium {
    background: rgba(245, 158, 11, 0.2);
    color: var(--warning);
}

.severity-badge.high {
    background: rgba(239, 68, 68, 0.2);
    color: var(--danger);
}

/* Status Badge */
.status-badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.status-badge.active {
    background: rgba(16, 185, 129, 0.2);
    color: var(--secondary);
}

.status-badge.inactive {
    background: rgba(107, 114, 128, 0.2);
    color: var(--text-muted);
}

.status-badge.pending {
    background: rgba(245, 158, 11, 0.2);
    color: var(--warning);
}

/* Ban Type Badge */
.ban-type {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.ban-type.temporary {
    background: rgba(245, 158, 11, 0.2);
    color: var(--warning);
}

.ban-type.permanent {
    background: rgba(239, 68, 68, 0.2);
    color: var(--danger);
}

.ban-type.ip_ban {
    background: rgba(99, 102, 241, 0.2);
    color: var(--primary);
}

.ban-type.shadow_ban {
    background: rgba(107, 114, 128, 0.2);
    color: var(--text-muted);
}

/* Action Modal */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal-overlay.active {
    display: flex;
}

.modal-box {
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
    max-width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
}

.action-modal, .warning-modal {
    width: 500px;
}

.modal-header {
    padding: 20px;
    background: var(--bg-tertiary);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.modal-header h3 {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--text-muted);
    font-size: 24px;
    padding: 0;
    display: flex;
    align-items: center;
    transition: color 0.2s;
}

.modal-close:hover {
    color: var(--text-primary);
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 16px 20px;
    background: var(--bg-tertiary);
    border-top: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
}

.action-options {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 20px;
}

.action-option {
    display: block;
    cursor: pointer;
}

.action-option input {
    display: none;
}

.action-option-content {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: var(--bg-tertiary);
    border: 2px solid var(--border);
    border-radius: var(--border-radius);
    transition: all 0.2s;
}

.action-option input:checked + .action-option-content {
    border-color: var(--danger);
    background: rgba(239, 68, 68, 0.1);
}

.action-option-content .material-icons {
    font-size: 24px;
    color: var(--text-muted);
    flex-shrink: 0;
}

.action-option input:checked + .action-option-content .material-icons {
    color: var(--danger);
}

.action-option-content div {
    flex: 1;
}

.action-option-content div strong {
    display: block;
    font-size: 14px;
    color: var(--text-primary);
    margin-bottom: 2px;
}

.action-option-content div span {
    font-size: 12px;
    color: var(--text-muted);
}

/* Form Elements */
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

/* Action Button */
.action-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--bg-tertiary);
    border: 1px solid var(--border);
    color: var(--text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.action-btn:hover {
    background: var(--danger);
    color: white;
    border-color: var(--danger);
}

.action-btn .material-icons {
    font-size: 18px;
}

/* Responsive */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .stat-card {
        flex-direction: column;
        text-align: center;
        padding: 12px;
    }

    .stat-icon {
        width: 40px;
        height: 40px;
        font-size: 20px;
    }

    .stat-value {
        font-size: 20px;
    }

    .security-tabs {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .report-item {
        flex-direction: column;
        gap: 12px;
        padding: 16px;
    }

    .report-actions {
        flex-direction: row;
        gap: 6px;
    }

    .report-actions .btn {
        flex: 1;
        padding: 8px;
        font-size: 11px;
    }

    .report-actions .material-icons {
        font-size: 16px;
    }

    .action-modal, .warning-modal {
        width: 90vw;
    }

    .data-table {
        font-size: 12px;
    }

    .data-table thead th,
    .data-table tbody td {
        padding: 8px 12px;
    }

    .user-cell {
        flex-direction: column;
        align-items: flex-start;
    }

    .page-header-content {
        flex-direction: column;
        align-items: flex-start;
    }

    .page-title {
        font-size: 20px;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .security-tabs {
        gap: 0;
    }

    .tab-btn {
        padding: 10px 16px;
        font-size: 12px;
    }

    .modal-box {
        width: 95vw !important;
    }

    .report-actions {
        flex-direction: column;
    }

    .report-actions .btn {
        flex: none;
    }
}
</style>

<script>
// Tab switching
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        this.classList.add('active');
        document.getElementById('tab-' + this.dataset.tab).classList.add('active');
    });
});

// View content
function viewContent(reportId) {
    showToast('Opening content viewer...', 'info');
    // In production, open a modal with the actual content
}

// Resolve report
async function resolveReport(reportId, resolution) {
    const data = await adminAPI('resolve_report', { report_id: reportId, resolution });
    
    if (data.success) {
        showToast('Report resolved', 'success');
        document.querySelector(`.report-item[data-id="${reportId}"]`)?.remove();
    } else {
        showToast(data.message || 'Failed', 'error');
    }
}

// Action modal
function showActionModal(reportId, userId) {
    document.getElementById('actionReportId').value = reportId;
    document.getElementById('actionUserId').value = userId;
    document.getElementById('actionReason').value = '';
    document.getElementById('actionModal').classList.add('active');
}

function hideActionModal() {
    document.getElementById('actionModal').classList.remove('active');
}

// Show/hide ban duration based on action type
document.querySelectorAll('input[name="action_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('banDurationGroup').style.display = 
            this.value === 'ban_temp' ? 'block' : 'none';
    });
});

async function executeAction() {
    const reportId = document.getElementById('actionReportId').value;
    const userId = document.getElementById('actionUserId').value;
    const actionType = document.querySelector('input[name="action_type"]:checked').value;
    const reason = document.getElementById('actionReason').value;
    const duration = document.getElementById('actionBanDuration').value;
    
    const data = await adminAPI('moderation_action', {
        report_id: reportId,
        user_id: userId,
        action_type: actionType,
        reason,
        duration
    });
    
    if (data.success) {
        showToast('Action completed', 'success');
        hideActionModal();
        location.reload();
    } else {
        showToast(data.message || 'Failed', 'error');
    }
}

// Warning modal
function showWarningModal() {
    document.getElementById('warningForm').reset();
    document.getElementById('warningModal').classList.add('active');
}

function hideWarningModal() {
    document.getElementById('warningModal').classList.remove('active');
}

document.getElementById('warningForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'issue_warning');
    formData.append('csrf_token', CSRF_TOKEN);
    
    const response = await fetch(BASE_URL + 'admin-secure/ajax/admin.php', { method: 'POST', body: formData });
    const data = await response.json();
    
    if (data.success) {
        showToast('Warning issued', 'success');
        hideWarningModal();
        location.reload();
    } else {
        showToast(data.message || 'Failed', 'error');
    }
});

// Unban user
async function unbanUser(banId) {
    showConfirm('Unban User', 'Remove this ban and restore user access?', async () => {
        const data = await adminAPI('unban_user', { ban_id: banId });
        
        if (data.success) {
            showToast('User unbanned', 'success');
            location.reload();
        } else {
            showToast(data.message || 'Failed', 'error');
        }
    });
}
</script>

<?php include __DIR__ . '/../layouts/admin-footer.php'; ?>
