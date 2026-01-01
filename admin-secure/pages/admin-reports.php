<?php
/**
 * Admin Reports
 * Generate and manage system reports
 */
$currPage = "Reports";

require_once __DIR__ . '/../../config/config.php';

$db = new Database();

// Get generated reports with user info
try {
    $reports = $db->resultSet("
        SELECT r.*, u.first_name, u.last_name,
        CONCAT(u.first_name, ' ', COALESCE(u.last_name, '')) as generated_by_name
        FROM generated_reports r 
        LEFT JOIN users u ON r.generated_by = u.user_id 
        ORDER BY r.created_at DESC 
        LIMIT 20
    ") ?? [];
} catch (Exception $e) {
    $reports = [];
}

// Get scheduled reports
try {
    $scheduledReports = $db->resultSet("
        SELECT sr.*, u.first_name, u.last_name
        FROM scheduled_reports sr
        LEFT JOIN users u ON sr.created_by = u.user_id
        ORDER BY sr.created_at DESC
    ") ?? [];
} catch (Exception $e) {
    $scheduledReports = [];
}

// Report stats
$totalReports = count($reports);
$completedReports = count(array_filter($reports, fn($r) => $r['status'] === 'completed'));
$scheduledCount = count(array_filter($scheduledReports, fn($r) => $r['is_enabled']));

// Helper function for file sizes
function formatBytes($bytes, $precision = 2) {
    if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB'];
    $pow = floor(log($bytes) / log(1024));
    return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
}

require_once __DIR__ . '/../layouts/admin-header.php';
?>

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title">Reports</h1>
        <p class="page-subtitle">Generate and manage system reports</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="showGenerateModal()">
            <span class="material-icons">add</span>
            Generate Report
        </button>
    </div>
</div>

<!-- Report Templates -->
<div class="report-templates">
    <h3 class="section-title">Quick Reports</h3>
    <div class="templates-grid">
        <div class="template-card" onclick="generateReport('user_summary')">
            <div class="template-icon users">
                <span class="material-icons">people</span>
            </div>
            <div class="template-info">
                <h4>User Summary</h4>
                <p>Overview of user registrations, roles, and activity</p>
            </div>
            <span class="material-icons template-arrow">arrow_forward</span>
        </div>
        
        <div class="template-card" onclick="generateReport('security_audit')">
            <div class="template-icon security">
                <span class="material-icons">security</span>
            </div>
            <div class="template-info">
                <h4>Security Audit</h4>
                <p>Login attempts, blocked IPs, and security events</p>
            </div>
            <span class="material-icons template-arrow">arrow_forward</span>
        </div>
        
        <div class="template-card" onclick="generateReport('activity_log')">
            <div class="template-icon activity">
                <span class="material-icons">history</span>
            </div>
            <div class="template-info">
                <h4>Activity Log</h4>
                <p>All admin actions and system changes</p>
            </div>
            <span class="material-icons template-arrow">arrow_forward</span>
        </div>
        
        <div class="template-card" onclick="generateReport('content_analytics')">
            <div class="template-icon content">
                <span class="material-icons">analytics</span>
            </div>
            <div class="template-info">
                <h4>Content Analytics</h4>
                <p>Posts, products, and engagement metrics</p>
            </div>
            <span class="material-icons template-arrow">arrow_forward</span>
        </div>
        
        <div class="template-card" onclick="generateReport('system_health')">
            <div class="template-icon system">
                <span class="material-icons">monitor_heart</span>
            </div>
            <div class="template-info">
                <h4>System Health</h4>
                <p>Server metrics, errors, and performance data</p>
            </div>
            <span class="material-icons template-arrow">arrow_forward</span>
        </div>
        
        <div class="template-card" onclick="generateReport('financial')">
            <div class="template-icon financial">
                <span class="material-icons">payments</span>
            </div>
            <div class="template-info">
                <h4>Financial Report</h4>
                <p>Marketplace transactions and revenue data</p>
            </div>
            <span class="material-icons template-arrow">arrow_forward</span>
        </div>
    </div>
</div>

<!-- Generated Reports -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons">description</span>
            Generated Reports
        </h3>
    </div>
    <div class="card-body no-padding">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Report Name</th>
                    <th>Type</th>
                    <th>Format</th>
                    <th>Size</th>
                    <th>Generated By</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reports)): ?>
                    <tr><td colspan="7"><div class="empty-state small"><span class="material-icons">description</span><p>No reports generated yet</p></div></td></tr>
                <?php else: ?>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td>
                                <div class="report-name">
                                    <span class="material-icons">
                                    <?php 
                                        echo match($report['format'] ?? 'pdf') {
                                            'pdf' => 'picture_as_pdf',
                                            'csv' => 'grid_on',
                                            'excel' => 'table_chart',
                                            default => 'description'
                                        };
                                        ?>
                                    </span>
                                    <?php echo htmlspecialchars($report['report_name']); ?>
                                </div>
                            </td>
                            <td><span class="report-type-badge"><?php echo ucwords(str_replace('_', ' ', $report['report_type'])); ?></span></td>
                            <td><span class="format-badge <?php echo $report['format'] ?? 'pdf'; ?>"><?php echo strtoupper($report['format'] ?? 'pdf'); ?></span></td>
                            <td><?php echo formatBytes($report['file_size'] ?? 0); ?></td>
                            <td><?php echo htmlspecialchars($report['generated_by_name'] ?? 'System'); ?></td>
                            <td class="date-cell"><?php echo date('M d, Y H:i', strtotime($report['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-btn" onclick="downloadReport(<?php echo $report['report_id']; ?>)" title="Download">
                                        <span class="material-icons">download</span>
                                    </button>
                                    <button class="action-btn danger" onclick="deleteReport(<?php echo $report['report_id']; ?>)" title="Delete">
                                        <span class="material-icons">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Scheduled Reports -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons">schedule</span>
            Scheduled Reports
        </h3>
        <button class="btn btn-sm btn-secondary" onclick="showScheduleReportModal()">
            <span class="material-icons">add</span>
            Schedule Report
        </button>
    </div>
    <div class="card-body">
        <?php if (empty($scheduledReports)): ?>
            <div class="empty-state">
                <span class="material-icons">event</span>
                <p>No scheduled reports</p>
                <button class="btn btn-primary" onclick="showScheduleReportModal()">Schedule Your First Report</button>
            </div>
        <?php else: ?>
            <div class="scheduled-list">
                <?php foreach ($scheduledReports as $scheduled): ?>
                    <div class="scheduled-item">
                        <div class="scheduled-info">
                            <h4><?php echo htmlspecialchars($scheduled['report_name']); ?></h4>
                            <span class="scheduled-meta">
                                <?php echo ucwords(str_replace('_', ' ', $scheduled['report_type'])); ?> • 
                                <?php echo strtoupper($scheduled['format'] ?? 'pdf'); ?> • 
                                <?php echo htmlspecialchars($scheduled['schedule_human'] ?? 'Custom'); ?>
                            </span>
                        </div>
                        <div class="scheduled-next">
                            <span class="material-icons">schedule</span>
                            Next: <?php echo $scheduled['next_send'] ? date('M d, H:i', strtotime($scheduled['next_send'])) : 'Not scheduled'; ?>
                        </div>
                        <div class="scheduled-actions">
                            <label class="task-toggle">
                                <input type="checkbox" <?php echo $scheduled['is_enabled'] ? 'checked' : ''; ?> onchange="toggleScheduledReport(<?php echo $scheduled['schedule_id']; ?>, this.checked)">
                                <span class="toggle-slider"></span>
                            </label>
                            <button class="action-btn danger" onclick="deleteScheduledReport(<?php echo $scheduled['schedule_id']; ?>)">
                                <span class="material-icons">delete</span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Generate Report Modal -->
<div class="modal-overlay" id="generateModal">
    <div class="modal-box generate-modal">
        <div class="modal-header">
            <h3>Generate Report</h3>
            <button class="modal-close" onclick="hideGenerateModal()">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="modal-body">
            <form id="generateForm">
                <div class="form-group">
                    <label class="form-label">Report Type</label>
                    <select id="reportType" name="report_type" class="form-input" required>
                        <option value="user_summary">User Summary</option>
                        <option value="security_audit">Security Audit</option>
                        <option value="activity_log">Activity Log</option>
                        <option value="content_analytics">Content Analytics</option>
                        <option value="system_health">System Health</option>
                        <option value="financial">Financial Report</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Date From</label>
                        <input type="date" id="dateFrom" name="date_from" class="form-input" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date To</label>
                        <input type="date" id="dateTo" name="date_to" class="form-input" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Format</label>
                    <div class="format-options">
                        <label class="format-option">
                            <input type="radio" name="format" value="pdf" checked>
                            <div class="format-box">
                                <span class="material-icons">picture_as_pdf</span>
                                <span>PDF</span>
                            </div>
                        </label>
                        <label class="format-option">
                            <input type="radio" name="format" value="csv">
                            <div class="format-box">
                                <span class="material-icons">grid_on</span>
                                <span>CSV</span>
                            </div>
                        </label>
                        <label class="format-option">
                            <input type="radio" name="format" value="xlsx">
                            <div class="format-box">
                                <span class="material-icons">table_chart</span>
                                <span>Excel</span>
                            </div>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Report Name (optional)</label>
                    <input type="text" id="reportName" name="report_name" class="form-input" placeholder="Auto-generated if empty">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="hideGenerateModal()">Cancel</button>
            <button type="submit" form="generateForm" class="btn btn-primary">
                <span class="material-icons">description</span>
                Generate
            </button>
        </div>
    </div>
</div>


<style>
/* Page Header */
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

/* Section Title */
.section-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 16px;
}

/* Cards */
.card {
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: var(--border-radius);
    overflow: hidden;
    margin-bottom: 24px;
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 20px;
    background: var(--bg-tertiary);
    border-bottom: 1px solid var(--border);
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

/* Templates Grid */
.report-templates {
    margin-bottom: 24px;
}

.templates-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}

.template-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: all 0.3s;
}

.template-card:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
    transform: translateY(-2px);
}

.template-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.template-icon.users { background: linear-gradient(135deg, #6366f1, #818cf8); }
.template-icon.security { background: linear-gradient(135deg, #ef4444, #f87171); }
.template-icon.activity { background: linear-gradient(135deg, #10b981, #34d399); }
.template-icon.content { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
.template-icon.system { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
.template-icon.financial { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }

.template-icon .material-icons {
    font-size: 24px;
    color: white;
}

.template-info {
    flex: 1;
}

.template-info h4 {
    font-size: 15px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 4px;
    margin: 0 0 4px 0;
}

.template-info p {
    font-size: 12px;
    color: var(--text-muted);
    margin: 0;
}

.template-arrow {
    color: var(--text-muted);
    transition: transform 0.2s;
    flex-shrink: 0;
}

.template-card:hover .template-arrow {
    transform: translateX(4px);
    color: var(--primary);
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

/* Report Name */
.report-name {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text-primary);
    font-weight: 500;
}

.report-name .material-icons {
    color: var(--primary);
    font-size: 18px;
}

/* Badges */
.report-type-badge {
    display: inline-block;
    padding: 4px 10px;
    background: rgba(99, 102, 241, 0.2);
    color: var(--primary);
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.format-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

.format-badge.pdf { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
.format-badge.csv { background: rgba(16, 185, 129, 0.2); color: #10b981; }
.format-badge.xlsx { background: rgba(99, 102, 241, 0.2); color: #6366f1; }

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 8px;
}

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
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.action-btn.danger:hover {
    background: var(--danger);
    border-color: var(--danger);
}

.action-btn .material-icons {
    font-size: 18px;
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
    margin: 0 0 16px 0;
}

.empty-state.small {
    padding: 30px 20px;
}

.empty-state.small .material-icons {
    font-size: 32px;
}

/* Scheduled List */
.scheduled-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.scheduled-item {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 16px 20px;
    background: var(--bg-tertiary);
    border-radius: var(--border-radius);
    transition: all 0.2s;
}

.scheduled-item:hover {
    border-left: 3px solid var(--primary);
    padding-left: 17px;
}

.scheduled-info {
    flex: 1;
}

.scheduled-info h4 {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 4px;
    margin: 0 0 4px 0;
}

.scheduled-meta {
    font-size: 12px;
    color: var(--text-muted);
}

.scheduled-next {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: var(--text-secondary);
    white-space: nowrap;
}

.scheduled-next .material-icons {
    font-size: 18px;
    color: var(--primary);
}

.scheduled-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}

/* Format Options */
.format-options {
    display: flex;
    gap: 12px;
}

.format-option {
    flex: 1;
    cursor: pointer;
}

.format-option input {
    display: none;
}

.format-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 16px;
    background: var(--bg-tertiary);
    border: 2px solid var(--border);
    border-radius: var(--border-radius);
    transition: all 0.2s;
}

.format-option input:checked + .format-box {
    border-color: var(--primary);
    background: rgba(99, 102, 241, 0.1);
}

.format-box .material-icons {
    font-size: 24px;
    color: var(--text-muted);
}

.format-option input:checked + .format-box .material-icons {
    color: var(--primary);
}

.format-box span:last-child {
    font-size: 13px;
    font-weight: 500;
    color: var(--text-secondary);
}

/* Toggle */
.task-toggle {
    position: relative;
    width: 40px;
    height: 22px;
    display: inline-block;
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
    background-color: var(--bg-tertiary);
    border: 1px solid var(--border);
    border-radius: 22px;
    transition: 0.3s;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 3px;
    bottom: 2px;
    background-color: var(--text-muted);
    border-radius: 50%;
    transition: 0.3s;
}

.task-toggle input:checked + .toggle-slider {
    background-color: var(--secondary);
    border-color: var(--secondary);
}

.task-toggle input:checked + .toggle-slider:before {
    transform: translateX(18px);
    background-color: white;
}

/* Form Elements */
.form-group {
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

/* Generate Modal */
.generate-modal {
    width: 500px;
    max-width: 90%;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

/* Modal Overlay */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s;
}

.modal-overlay.active {
    opacity: 1;
    visibility: visible;
}

.modal-box {
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
    box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
    max-height: 90vh;
    overflow-y: auto;
    transform: translateY(-20px);
    transition: transform 0.3s;
}

.modal-overlay.active .modal-box {
    transform: translateY(0);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    background: var(--bg-tertiary);
    border-bottom: 1px solid var(--border);
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
    color: var(--text-muted);
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: all 0.2s;
    display: flex;
    align-items: center;
}

.modal-close:hover {
    background: var(--bg-secondary);
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

/* Responsive */
@media (max-width: 1024px) {
    .templates-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .scheduled-item {
        flex-wrap: wrap;
    }
}

@media (max-width: 768px) {
    .templates-grid {
        grid-template-columns: 1fr;
    }
    
    .card-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .scheduled-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .scheduled-next {
        width: 100%;
    }
    
    .scheduled-actions {
        width: 100%;
        justify-content: flex-end;
    }
    
    .page-title {
        font-size: 20px;
    }
    
    .modal-box {
        width: 90vw;
    }
    
    .data-table {
        font-size: 12px;
    }
    
    .data-table thead th,
    .data-table tbody td {
        padding: 8px 12px;
    }
}

@media (max-width: 480px) {
    .page-header {
        gap: 12px;
    }
    
    .page-actions {
        width: 100%;
    }
    
    .page-actions .btn {
        width: 100%;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .action-btn {
        width: 100%;
    }
    
    .modal-footer {
        flex-direction: column;
    }
    
    .modal-footer .btn {
        width: 100%;
    }
    
    .format-options {
        flex-direction: column;
    }
}
</style>

<!-- Schedule Report Modal -->
<div class="modal-overlay" id="scheduleModal">
    <div class="modal-box generate-modal">
        <div class="modal-header">
            <h3>Schedule Report</h3>
            <button class="modal-close" onclick="hideScheduleModal()">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="modal-body">
            <form id="scheduleForm">
                <div class="form-group">
                    <label class="form-label">Report Name</label>
                    <input type="text" name="report_name" class="form-input" placeholder="e.g., Weekly User Summary" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Report Type</label>
                    <select name="report_type" class="form-input" required>
                        <option value="users">User Summary</option>
                        <option value="security">Security Audit</option>
                        <option value="activity">Activity Log</option>
                        <option value="content">Content Analytics</option>
                        <option value="performance">System Health</option>
                        <option value="financial">Financial Report</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Schedule</label>
                    <select name="schedule_cron" class="form-input" required>
                        <option value="0 8 * * *" data-human="Daily at 8:00 AM">Daily</option>
                        <option value="0 8 * * 1" data-human="Every Monday at 8:00 AM">Weekly (Monday)</option>
                        <option value="0 8 1 * *" data-human="1st of every month at 8:00 AM">Monthly</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Format</label>
                    <div class="format-options">
                        <label class="format-option">
                            <input type="radio" name="format" value="pdf" checked>
                            <div class="format-box">
                                <span class="material-icons">picture_as_pdf</span>
                                <span>PDF</span>
                            </div>
                        </label>
                        <label class="format-option">
                            <input type="radio" name="format" value="csv">
                            <div class="format-box">
                                <span class="material-icons">grid_on</span>
                                <span>CSV</span>
                            </div>
                        </label>
                        <label class="format-option">
                            <input type="radio" name="format" value="excel">
                            <div class="format-box">
                                <span class="material-icons">table_chart</span>
                                <span>Excel</span>
                            </div>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email Recipients (comma separated)</label>
                    <input type="text" name="recipients" class="form-input" placeholder="admin@example.com, manager@example.com">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="hideScheduleModal()">Cancel</button>
            <button type="submit" form="scheduleForm" class="btn btn-primary">
                <span class="material-icons">schedule</span>
                Schedule Report
            </button>
        </div>
    </div>
</div>

<script>
// Reports Configuration
// Note: BASE_URL and CSRF_TOKEN are defined in admin-footer.php which loads after this script
// Use function to get endpoint lazily
const REPORTS_CONFIG = {
    get apiEndpoint() {
        return (window.BASE_URL || '<?php echo $base_url; ?>') + 'admin-secure/ajax/reports.php';
    },
    templates: {
        user_summary: {
            name: 'User Summary',
            description: 'Overview of user registrations, roles, and activity'
        },
        security_audit: {
            name: 'Security Audit',
            description: 'Login attempts, blocked IPs, and security events'
        },
        activity_log: {
            name: 'Activity Log',
            description: 'All admin actions and system changes'
        },
        content_overview: {
            name: 'Content Overview',
            description: 'Posts, products, and platform content metrics'
        }
    }
};

// Notification helper
function showNotification(message, type = 'info') {
    console.log(`[${type.toUpperCase()}] ${message}`);
    if (typeof showToast === 'function') {
        showToast(message, type);
    } else {
        alert(message);
    }
}

// Loading helper
function toggleLoading(show, message = 'Processing...') {
    if (typeof showLoading === 'function' && typeof hideLoading === 'function') {
        show ? showLoading(message) : hideLoading();
    }
}

/**
 * Generate report from template
 */
async function generateReport(type) {
    if (!type) {
        showNotification('Invalid report type', 'error');
        return;
    }
    
    const template = REPORTS_CONFIG.templates[type];
    if (!template) {
        showNotification('Unknown report template', 'error');
        return;
    }
    
    // Set report type in modal
    const reportTypeInput = document.getElementById('reportType');
    if (reportTypeInput) {
        reportTypeInput.value = type;
    }
    
    // Set report title in modal
    const modalTitle = document.querySelector('#generateModal .modal-title');
    if (modalTitle) {
        modalTitle.textContent = `Generate ${template.name}`;
    }
    
    showGenerateModal();
}

/**
 * Show generate report modal
 */
function showGenerateModal() {
    const modal = document.getElementById('generateModal');
    if (modal) {
        modal.classList.add('active');
        
        // Set default date range (last 30 days)
        const dateToInput = document.querySelector('#generateForm [name="date_to"]');
        const dateFromInput = document.querySelector('#generateForm [name="date_from"]');
        
        if (dateToInput && !dateToInput.value) {
            const today = new Date();
            dateToInput.value = today.toISOString().split('T')[0];
        }
        
        if (dateFromInput && !dateFromInput.value) {
            const thirtyDaysAgo = new Date();
            thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
            dateFromInput.value = thirtyDaysAgo.toISOString().split('T')[0];
        }
    }
}

/**
 * Hide generate report modal
 */
function hideGenerateModal() {
    const modal = document.getElementById('generateModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

/**
 * Handle report generation form submission
 */
document.addEventListener('DOMContentLoaded', function() {
    const generateForm = document.getElementById('generateForm');
    
    if (generateForm) {
        generateForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'generate_report');
            formData.append('csrf_token', CSRF_TOKEN);
            
            // Validate form
            const reportType = formData.get('report_type');
            const format = formData.get('format');
            
            if (!reportType) {
                showNotification('Please select a report type', 'error');
                return;
            }
            
            toggleLoading(true, 'Generating report...');
            
            try {
                const response = await fetch(REPORTS_CONFIG.apiEndpoint, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                toggleLoading(false);
                
                if (data.success) {
                    showNotification('Report generated successfully! Refreshing page...', 'success');
                    hideGenerateModal();
                    
                    // Reset form
                    generateForm.reset();
                    
                    // Reload page after short delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showNotification(data.message || 'Failed to generate report', 'error');
                }
            } catch (error) {
                toggleLoading(false);
                console.error('Report generation error:', error);
                showNotification('Error generating report. Please try again.', 'error');
            }
        });
    }
});

/**
 * Download report
 */
function downloadReport(reportId) {
    if (!reportId) {
        showNotification('Invalid report ID', 'error');
        return;
    }
    
    const url = `${REPORTS_CONFIG.apiEndpoint}?action=download_report&report_id=${reportId}&csrf_token=${CSRF_TOKEN}`;
    
    // Create temporary link and trigger download
    const link = document.createElement('a');
    link.href = url;
    link.download = '';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showNotification('Download started...', 'info');
}

/**
 * Delete report
 */
async function deleteReport(reportId) {
    if (!reportId) {
        showNotification('Invalid report ID', 'error');
        return;
    }
    
    // Confirm deletion
    if (!confirm('Are you sure you want to delete this report? This action cannot be undone.')) {
        return;
    }
    
    toggleLoading(true, 'Deleting report...');
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete_report');
        formData.append('report_id', reportId);
        formData.append('csrf_token', CSRF_TOKEN);
        
        const response = await fetch(REPORTS_CONFIG.apiEndpoint, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        toggleLoading(false);
        
        if (data.success) {
            showNotification('Report deleted successfully', 'success');
            
            // Remove report row from table
            const reportRow = document.querySelector(`tr[data-report-id="${reportId}"]`);
            if (reportRow) {
                reportRow.remove();
            } else {
                setTimeout(() => window.location.reload(), 1000);
            }
        } else {
            showNotification(data.message || 'Failed to delete report', 'error');
        }
    } catch (error) {
        toggleLoading(false);
        console.error('Delete error:', error);
        showNotification('Error deleting report', 'error');
    }
}

/**
 * Show schedule report modal
 */
function showScheduleReportModal() {
    const modal = document.getElementById('scheduleModal');
    if (modal) {
        modal.classList.add('active');
    }
}

/**
 * Hide schedule report modal
 */
function hideScheduleModal() {
    const modal = document.getElementById('scheduleModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

/**
 * Handle schedule report form submission
 */
document.addEventListener('DOMContentLoaded', function() {
    const scheduleForm = document.getElementById('scheduleForm');
    
    if (scheduleForm) {
        scheduleForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'create_scheduled_report');
            formData.append('csrf_token', CSRF_TOKEN);
            
            // Get human-readable schedule
            const scheduleSelect = this.querySelector('[name="schedule_cron"]');
            if (scheduleSelect) {
                const selectedOption = scheduleSelect.options[scheduleSelect.selectedIndex];
                const scheduleHuman = selectedOption.getAttribute('data-human');
                formData.append('schedule_human', scheduleHuman || scheduleSelect.value);
            }
            
            toggleLoading(true, 'Scheduling report...');
            
            try {
                const response = await fetch(REPORTS_CONFIG.apiEndpoint, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                toggleLoading(false);
                
                if (data.success) {
                    showNotification('Report scheduled successfully!', 'success');
                    hideScheduleModal();
                    
                    // Reset form
                    scheduleForm.reset();
                    
                    // Reload page
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showNotification(data.message || 'Failed to schedule report', 'error');
                }
            } catch (error) {
                toggleLoading(false);
                console.error('Schedule error:', error);
                showNotification('Error scheduling report', 'error');
            }
        });
    }
});

/**
 * Toggle scheduled report
 */
async function toggleScheduledReport(scheduleId, enabled) {
    if (!scheduleId) {
        showNotification('Invalid schedule ID', 'error');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'toggle_scheduled_report');
        formData.append('schedule_id', scheduleId);
        formData.append('is_enabled', enabled ? 1 : 0);
        formData.append('csrf_token', CSRF_TOKEN);
        
        const response = await fetch(REPORTS_CONFIG.apiEndpoint, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(`Report ${enabled ? 'enabled' : 'disabled'} successfully`, 'success');
        } else {
            showNotification(data.message || 'Failed to update schedule', 'error');
            
            // Revert toggle if failed
            const toggle = document.querySelector(`input[data-schedule-id="${scheduleId}"]`);
            if (toggle) {
                toggle.checked = !enabled;
            }
        }
    } catch (error) {
        console.error('Toggle error:', error);
        showNotification('Error updating schedule', 'error');
        
        // Revert toggle
        const toggle = document.querySelector(`input[data-schedule-id="${scheduleId}"]`);
        if (toggle) {
            toggle.checked = !enabled;
        }
    }
}

/**
 * Delete scheduled report
 */
async function deleteScheduledReport(scheduleId) {
    if (!scheduleId) {
        showNotification('Invalid schedule ID', 'error');
        return;
    }
    
    // Confirm deletion
    if (!confirm('Are you sure you want to delete this scheduled report?')) {
        return;
    }
    
    toggleLoading(true, 'Deleting scheduled report...');
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete_scheduled_report');
        formData.append('schedule_id', scheduleId);
        formData.append('csrf_token', CSRF_TOKEN);
        
        const response = await fetch(REPORTS_CONFIG.apiEndpoint, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        toggleLoading(false);
        
        if (data.success) {
            showNotification('Scheduled report deleted successfully', 'success');
            
            // Remove schedule row from table
            const scheduleRow = document.querySelector(`tr[data-schedule-id="${scheduleId}"]`);
            if (scheduleRow) {
                scheduleRow.remove();
            } else {
                setTimeout(() => window.location.reload(), 1000);
            }
        } else {
            showNotification(data.message || 'Failed to delete schedule', 'error');
        }
    } catch (error) {
        toggleLoading(false);
        console.error('Delete schedule error:', error);
        showNotification('Error deleting scheduled report', 'error');
    }
}

/**
 * Close modals when clicking outside
 */
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    });
});

/**
 * Preview report before generation
 */
function previewReport(type) {
    const template = REPORTS_CONFIG.templates[type];
    if (template) {
        alert(`${template.name}\n\n${template.description}\n\nClick OK to configure and generate this report.`);
        generateReport(type);
    }
}

// Initialize tooltips and UI enhancements
document.addEventListener('DOMContentLoaded', function() {
    // Add loading state to buttons
    document.querySelectorAll('button[onclick*="generateReport"]').forEach(btn => {
        btn.addEventListener('click', function() {
            this.classList.add('loading');
            setTimeout(() => this.classList.remove('loading'), 2000);
        });
    });
    
    console.log('Reports functionality initialized');
});
</script>

<?php include __DIR__ . '/../layouts/admin-footer.php'; ?>
