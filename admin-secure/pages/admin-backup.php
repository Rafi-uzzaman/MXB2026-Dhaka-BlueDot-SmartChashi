<?php
/**
 * Admin Backup & Recovery
 * Database backup, restore, and disaster recovery management
 */
$currPage = "Backup";

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../layouts/admin-header.php';

$db = new Database();

// Get backup stats
$backupRecords = $db->resultSet("SELECT * FROM backup_records ORDER BY created_at DESC LIMIT 20");
$lastBackup = $db->single("SELECT * FROM backup_records WHERE status = 'completed' ORDER BY created_at DESC LIMIT 1");
$totalBackups = $db->single("SELECT COUNT(*) as count FROM backup_records WHERE status = 'completed'")['count'] ?? 0;

// Get scheduled backup settings
$autoBackup = $db->single("SELECT * FROM scheduled_tasks WHERE task_name = 'Daily Database Backup'");

// Calculate total backup size
$totalSize = $db->single("SELECT SUM(file_size) as total FROM backup_records WHERE status = 'completed'")['total'] ?? 0;
?>

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title">Backup & Recovery</h1>
        <p class="page-subtitle">Manage database backups and disaster recovery</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-secondary" onclick="showScheduleModal()">
            <span class="material-icons">schedule</span>
            Schedule
        </button>
        <button class="btn btn-primary" onclick="createBackup()">
            <span class="material-icons">backup</span>
            Create Backup Now
        </button>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card mini">
        <div class="stat-icon sessions">
            <span class="material-icons">backup</span>
        </div>
        <div class="stat-content">
            <span class="stat-value"><?php echo $totalBackups; ?></span>
            <span class="stat-label">Total Backups</span>
        </div>
    </div>
    <div class="stat-card mini">
        <div class="stat-icon users">
            <span class="material-icons">schedule</span>
        </div>
        <div class="stat-content">
            <span class="stat-value"><?php echo $lastBackup ? date('M d', strtotime($lastBackup['created_at'])) : 'Never'; ?></span>
            <span class="stat-label">Last Backup</span>
        </div>
    </div>
    <div class="stat-card mini">
        <div class="stat-icon logins">
            <span class="material-icons">storage</span>
        </div>
        <div class="stat-content">
            <span class="stat-value"><?php echo formatBytes($totalSize); ?></span>
            <span class="stat-label">Total Size</span>
        </div>
    </div>
    <div class="stat-card mini <?php echo $autoBackup && $autoBackup['is_enabled'] ? '' : 'warning'; ?>">
        <div class="stat-icon security">
            <span class="material-icons"><?php echo $autoBackup && $autoBackup['is_enabled'] ? 'check_circle' : 'warning'; ?></span>
        </div>
        <div class="stat-content">
            <span class="stat-value"><?php echo $autoBackup && $autoBackup['is_enabled'] ? 'Active' : 'Inactive'; ?></span>
            <span class="stat-label">Auto Backup</span>
        </div>
    </div>
</div>

<!-- Backup Actions -->
<div class="backup-actions-grid">
    <div class="backup-action-card">
        <div class="action-icon full">
            <span class="material-icons">storage</span>
        </div>
        <h3>Full Database Backup</h3>
        <p>Complete backup of all tables, data, and structure</p>
        <button class="btn btn-primary" onclick="createBackup('full')">Create Full Backup</button>
    </div>
    
    <div class="backup-action-card">
        <div class="action-icon partial">
            <span class="material-icons">table_chart</span>
        </div>
        <h3>Selective Backup</h3>
        <p>Choose specific tables to backup</p>
        <button class="btn btn-secondary" onclick="showSelectiveBackupModal()">Select Tables</button>
    </div>
    
    <div class="backup-action-card">
        <div class="action-icon restore">
            <span class="material-icons">restore</span>
        </div>
        <h3>Restore Backup</h3>
        <p>Restore database from a previous backup</p>
        <button class="btn btn-secondary" onclick="showRestoreModal()">Restore</button>
    </div>
    
    <div class="backup-action-card">
        <div class="action-icon export">
            <span class="material-icons">cloud_download</span>
        </div>
        <h3>Export Data</h3>
        <p>Export data in CSV, JSON, or SQL format</p>
        <button class="btn btn-secondary" onclick="showExportModal()">Export</button>
    </div>
</div>

<!-- Backup History -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons">history</span>
            Backup History
        </h3>
    </div>
    <div class="card-body no-padding">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Backup Name</th>
                    <th>Type</th>
                    <th>Size</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($backupRecords)): ?>
                    <tr><td colspan="6"><div class="empty-state small"><span class="material-icons">backup</span><p>No backups yet</p></div></td></tr>
                <?php else: ?>
                    <?php foreach ($backupRecords as $backup): ?>
                        <tr>
                            <td>
                                <div class="backup-name">
                                    <span class="material-icons">folder_zip</span>
                                    <?php echo htmlspecialchars($backup['backup_name'] ?? 'Unnamed Backup'); ?>
                                </div>
                            </td>
                            <td>
                                <span class="backup-type <?php echo $backup['backup_type'] ?? 'full'; ?>">
                                    <?php echo ucfirst($backup['backup_type'] ?? 'full'); ?>
                                </span>
                            </td>
                            <td><?php echo formatBytes($backup['file_size'] ?? 0); ?></td>
                            <td>
                                <span class="status-badge <?php echo $backup['status'] ?? 'pending'; ?>">
                                    <?php echo ucfirst($backup['status'] ?? 'pending'); ?>
                                </span>
                            </td>
                            <td class="date-cell">
                                <?php echo date('M d, Y H:i', strtotime($backup['created_at'])); ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($backup['status'] === 'completed'): ?>
                                        <button class="action-btn" onclick="downloadBackup(<?php echo $backup['backup_id']; ?>)" title="Download">
                                            <span class="material-icons">download</span>
                                        </button>
                                        <button class="action-btn" onclick="restoreFromBackup(<?php echo $backup['backup_id']; ?>)" title="Restore">
                                            <span class="material-icons">restore</span>
                                        </button>
                                    <?php endif; ?>
                                    <button class="action-btn danger" onclick="deleteBackup(<?php echo $backup['backup_id']; ?>)" title="Delete">
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

<!-- Backup Progress Modal -->
<div class="modal-overlay" id="backupProgressModal">
    <div class="modal-box progress-modal">
        <div class="modal-body">
            <div class="progress-content">
                <div class="progress-spinner">
                    <span class="material-icons spinning">sync</span>
                </div>
                <h3 id="progressTitle">Creating Backup...</h3>
                <p id="progressMessage">Please wait while we process your request</p>
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill" style="width: 0%"></div>
                </div>
                <span class="progress-percent" id="progressPercent">0%</span>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Modal -->
<div class="modal-overlay" id="scheduleModal">
    <div class="modal-box schedule-modal">
        <div class="modal-header">
            <h3>Backup Schedule</h3>
            <button class="modal-close" onclick="hideScheduleModal()">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="modal-body">
            <form id="scheduleForm">
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="autoBackupEnabled" name="enabled" value="1" <?php echo ($autoBackup && $autoBackup['is_enabled']) ? 'checked' : ''; ?>>
                        <span>Enable automatic backups</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Frequency</label>
                    <select id="backupFrequency" name="frequency" class="form-input">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Time</label>
                    <input type="time" id="backupTime" name="time" class="form-input" value="02:00">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Retention (days)</label>
                    <input type="number" id="backupRetention" name="retention" class="form-input" value="30" min="7" max="365">
                    <span class="form-hint">Backups older than this will be automatically deleted</span>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="hideScheduleModal()">Cancel</button>
            <button type="submit" form="scheduleForm" class="btn btn-primary">Save Schedule</button>
        </div>
    </div>
</div>

<!-- Restore Modal -->
<div class="modal-overlay" id="restoreModal">
    <div class="modal-box restore-modal">
        <div class="modal-header">
            <h3>Restore Backup</h3>
            <button class="modal-close" onclick="hideRestoreModal()">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="modal-body">
            <div class="warning-box">
                <span class="material-icons">warning</span>
                <div>
                    <strong>Warning!</strong>
                    <p>Restoring a backup will replace all current data. This action cannot be undone.</p>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Select Backup to Restore</label>
                <select id="restoreBackupSelect" class="form-input">
                    <?php foreach ($backupRecords as $backup): ?>
                        <?php if ($backup['status'] === 'completed'): ?>
                            <option value="<?php echo $backup['backup_id']; ?>">
                                <?php echo $backup['backup_name']; ?> (<?php echo date('M d, Y H:i', strtotime($backup['created_at'])); ?>)
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="confirmRestore" required>
                    <span>I understand that this will replace all current data</span>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="hideRestoreModal()">Cancel</button>
            <button type="button" class="btn btn-danger" onclick="executeRestore()">Restore Backup</button>
        </div>
    </div>
</div>

<?php
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>

<style>
/* Backup Actions Grid */
.backup-actions-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 24px;
}

.backup-action-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--border-radius-lg);
    padding: 24px;
    text-align: center;
    transition: all 0.3s;
}

.backup-action-card:hover {
    border-color: var(--primary);
    box-shadow: var(--shadow-glow);
}

.action-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
}

.action-icon.full {
    background: linear-gradient(135deg, #6366f1, #818cf8);
}

.action-icon.partial {
    background: linear-gradient(135deg, #10b981, #34d399);
}

.action-icon.restore {
    background: linear-gradient(135deg, #f59e0b, #fbbf24);
}

.action-icon.export {
    background: linear-gradient(135deg, #3b82f6, #60a5fa);
}

.action-icon .material-icons {
    font-size: 28px;
    color: white;
}

.backup-action-card h3 {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 8px;
}

.backup-action-card p {
    font-size: 13px;
    color: var(--text-muted);
    margin-bottom: 16px;
}

/* Backup Table Styles */
.backup-name {
    display: flex;
    align-items: center;
    gap: 8px;
}

.backup-name .material-icons {
    color: var(--primary);
    font-size: 20px;
}

.backup-type {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.backup-type.full {
    background: rgba(99, 102, 241, 0.2);
    color: var(--primary);
}

.backup-type.partial {
    background: rgba(16, 185, 129, 0.2);
    color: var(--secondary);
}

.backup-type.auto {
    background: rgba(245, 158, 11, 0.2);
    color: var(--warning);
}

/* Progress Modal */
.progress-modal {
    width: 400px;
    text-align: center;
}

.progress-content {
    padding: 30px 20px;
}

.progress-spinner .material-icons {
    font-size: 48px;
    color: var(--primary);
    animation: spin 1s linear infinite;
}

.progress-content h3 {
    font-size: 18px;
    color: var(--text-primary);
    margin: 16px 0 8px;
}

.progress-content p {
    font-size: 14px;
    color: var(--text-muted);
    margin-bottom: 20px;
}

.progress-bar {
    height: 8px;
    background: var(--border);
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 8px;
}

.progress-fill {
    height: 100%;
    background: var(--primary);
    border-radius: 4px;
    transition: width 0.3s;
}

.progress-percent {
    font-size: 14px;
    font-weight: 600;
    color: var(--primary);
}

/* Schedule & Restore Modals */
.schedule-modal, .restore-modal {
    width: 450px;
}

.warning-box {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px;
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 8px;
    margin-bottom: 20px;
}

.warning-box .material-icons {
    color: var(--danger);
    font-size: 24px;
    flex-shrink: 0;
}

.warning-box strong {
    color: var(--danger);
    display: block;
    margin-bottom: 4px;
}

.warning-box p {
    font-size: 13px;
    color: var(--text-secondary);
    margin: 0;
}

@media (max-width: 1024px) {
    .backup-actions-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .backup-actions-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Initialize global constants from admin.js
const CSRF_TOKEN = getCSRFToken ? getCSRFToken() : (document.getElementById('csrfToken')?.value || '');
const BASE_URL = getBaseUrl ? getBaseUrl() : (document.getElementById('baseUrl')?.value || '/');

// Helper functions (fallbacks if admin.js not loaded properly)
if (typeof adminAPI !== 'function') {
    async function adminAPI(action, params = {}) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('csrf_token', CSRF_TOKEN);
        
        for (const key in params) {
            formData.append(key, params[key]);
        }
        
        const response = await fetch(BASE_URL + 'admin-secure/ajax/admin.php', {
            method: 'POST',
            body: formData
        });
        
        return await response.json();
    }
}

if (typeof showConfirm !== 'function') {
    function showConfirm(title, message, callback, options = {}) {
        if (confirm(title + '\n\n' + message)) {
            callback();
        }
    }
}

if (typeof showToast !== 'function') {
    function showToast(message, type = 'info') {
        alert(message);
    }
}

// Create backup
async function createBackup(type = 'full') {
    showBackupProgress('Creating Backup...', 'Preparing database export...');
    
    let progress = 0;
    const progressInterval = setInterval(() => {
        progress += Math.random() * 15;
        if (progress > 90) progress = 90;
        updateProgress(progress);
    }, 500);
    
    try {
        const data = await adminAPI('create_backup', { type });
        
        clearInterval(progressInterval);
        
        if (data.success) {
            updateProgress(100);
            document.getElementById('progressTitle').textContent = 'Backup Complete!';
            document.getElementById('progressMessage').textContent = 'Your backup has been created successfully';
            
            setTimeout(() => {
                hideBackupProgress();
                location.reload();
            }, 1500);
        } else {
            hideBackupProgress();
            showToast(data.message || 'Backup failed', 'error');
        }
    } catch (error) {
        clearInterval(progressInterval);
        hideBackupProgress();
        showToast('Failed to create backup', 'error');
    }
}

function showBackupProgress(title, message) {
    document.getElementById('progressTitle').textContent = title;
    document.getElementById('progressMessage').textContent = message;
    document.getElementById('progressFill').style.width = '0%';
    document.getElementById('progressPercent').textContent = '0%';
    document.getElementById('backupProgressModal').classList.add('active');
}

function updateProgress(percent) {
    document.getElementById('progressFill').style.width = percent + '%';
    document.getElementById('progressPercent').textContent = Math.round(percent) + '%';
}

function hideBackupProgress() {
    document.getElementById('backupProgressModal').classList.remove('active');
}

// Download backup
function downloadBackup(backupId) {
    window.location.href = BASE_URL + 'admin-secure/ajax/admin.php?action=download_backup&backup_id=' + backupId + '&csrf_token=' + CSRF_TOKEN;
}

// Delete backup
function deleteBackup(backupId) {
    showConfirm('Delete Backup', 'Are you sure you want to delete this backup?', async () => {
        const data = await adminAPI('delete_backup', { backup_id: backupId });
        
        if (data.success) {
            showToast('Backup deleted', 'success');
            location.reload();
        } else {
            showToast(data.message || 'Failed to delete', 'error');
        }
    }, { type: 'danger' });
}

// Restore
function showRestoreModal() {
    document.getElementById('confirmRestore').checked = false;
    document.getElementById('restoreModal').classList.add('active');
}

function hideRestoreModal() {
    document.getElementById('restoreModal').classList.remove('active');
}

function restoreFromBackup(backupId) {
    document.getElementById('restoreBackupSelect').value = backupId;
    showRestoreModal();
}

async function executeRestore() {
    if (!document.getElementById('confirmRestore').checked) {
        showToast('Please confirm the restore action', 'warning');
        return;
    }
    
    const backupId = document.getElementById('restoreBackupSelect').value;
    
    hideRestoreModal();
    showBackupProgress('Restoring Backup...', 'This may take a few minutes...');
    
    const data = await adminAPI('restore_backup', { backup_id: backupId });
    
    if (data.success) {
        updateProgress(100);
        document.getElementById('progressTitle').textContent = 'Restore Complete!';
        document.getElementById('progressMessage').textContent = 'Database has been restored successfully';
        
        setTimeout(() => {
            hideBackupProgress();
            location.reload();
        }, 2000);
    } else {
        hideBackupProgress();
        showToast(data.message || 'Restore failed', 'error');
    }
}

// Schedule
function showScheduleModal() {
    document.getElementById('scheduleModal').classList.add('active');
}

function hideScheduleModal() {
    document.getElementById('scheduleModal').classList.remove('active');
}

document.getElementById('scheduleForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'update_backup_schedule');
    formData.append('csrf_token', CSRF_TOKEN);
    
    const response = await fetch(BASE_URL + 'admin-secure/ajax/admin.php', { method: 'POST', body: formData });
    const data = await response.json();
    
    if (data.success) {
        showToast('Schedule updated', 'success');
        hideScheduleModal();
    } else {
        showToast(data.message || 'Failed to update', 'error');
    }
});

// Export
function showExportModal() {
    showToast('Export feature coming soon', 'info');
}

// Selective backup
function showSelectiveBackupModal() {
    showToast('Selective backup coming soon', 'info');
}
</script>

<?php include __DIR__ . '/../layouts/admin-footer.php'; ?>
