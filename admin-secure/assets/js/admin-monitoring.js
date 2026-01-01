/**
 * Admin Monitoring Dashboard JavaScript
 * Handles real-time monitoring, metrics updates, and system health tracking
 */

// Configuration
const MONITORING_CONFIG = {
    refreshInterval: 30000, // 30 seconds
    chartRefreshInterval: 60000, // 1 minute
    maxDataPoints: 24,
    apiEndpoint: (window.baseUrl || '/') + 'admin-secure/ajax/monitoring.php'
};

// State management
let refreshInterval = null;
let chartRefreshInterval = null;
let currentErrorId = null;
let charts = {
    responseTime: null,
    requests: null
};

// ========================================
// INITIALIZATION
// ========================================

document.addEventListener('DOMContentLoaded', () => {
    initializeMonitoring();
    initializeCharts();
    initializeEventListeners();
    startAutoRefresh();
});

/**
 * Initialize monitoring dashboard
 */
function initializeMonitoring() {
    refreshMetrics();
    updateStatusBar();
}

/**
 * Initialize event listeners
 */
function initializeEventListeners() {
    // Auto-refresh toggle
    const autoRefreshCheckbox = document.getElementById('autoRefresh');
    if (autoRefreshCheckbox) {
        autoRefreshCheckbox.addEventListener('change', function() {
            if (this.checked) {
                startAutoRefresh();
            } else {
                stopAutoRefresh();
            }
        });
    }

    // Modal close on outside click
    const errorModal = document.getElementById('errorModal');
    if (errorModal) {
        errorModal.addEventListener('click', function(e) {
            if (e.target === this) {
                hideErrorModal();
            }
        });
    }

    // ESC key to close modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideErrorModal();
        }
    });
}

// ========================================
// AUTO-REFRESH MANAGEMENT
// ========================================

/**
 * Start auto-refresh intervals
 */
function startAutoRefresh() {
    stopAutoRefresh(); // Clear existing intervals
    
    refreshInterval = setInterval(() => {
        refreshMetrics();
        updateStatusBar();
    }, MONITORING_CONFIG.refreshInterval);

    chartRefreshInterval = setInterval(() => {
        updateCharts();
    }, MONITORING_CONFIG.chartRefreshInterval);

}

/**
 * Stop auto-refresh intervals
 */
function stopAutoRefresh() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
    if (chartRefreshInterval) {
        clearInterval(chartRefreshInterval);
        chartRefreshInterval = null;
    }
}

/**
 * Manual refresh trigger
 */
function refreshMetrics() {
    loadSystemMetrics();
    loadErrorStats();
    loadTasksStatus();
    loadSessionsStats();
    loadBackupStatus();
    loadSecurityEvents();
    loadActivityLog();
}

// ========================================
// SYSTEM METRICS
// ========================================

/**
 * Load and update system metrics
 */
async function loadSystemMetrics() {
    try {
        const response = await fetch(MONITORING_CONFIG.apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'get_system_metrics'
            })
        });

        const data = await response.json();

        if (data.success && data.data) {
            updateGauges(data.data);
            updateSystemInfo(data.data);
        }
    } catch (error) {
        console.error('Failed to load system metrics:', error);
    }
}

/**
 * Update gauge displays
 */
function updateGauges(metrics) {
    // CPU Usage
    if (metrics.cpu !== undefined) {
        setGaugeValue('cpuGauge', Math.round(metrics.cpu));
    }

    // Memory Usage
    if (metrics.memory !== undefined) {
        setGaugeValue('memoryGauge', Math.round(metrics.memory));
    }

    // Disk usage is static from PHP, but can be refreshed
    if (metrics.disk !== undefined) {
        setGaugeValue('diskGauge', Math.round(metrics.disk));
    }
}

/**
 * Set gauge value with color coding
 */
function setGaugeValue(gaugeId, value) {
    const fill = document.getElementById(gaugeId + 'Fill');
    const valueEl = document.getElementById(gaugeId.replace('Gauge', 'Value'));

    if (fill) {
        const offset = 283 - (283 * value / 100);
        fill.style.strokeDashoffset = offset;

        // Color based on value
        if (value > 90) {
            fill.style.stroke = 'var(--danger)';
        } else if (value > 70) {
            fill.style.stroke = 'var(--warning)';
        } else {
            fill.style.stroke = 'var(--secondary)';
        }
    }

    if (valueEl) {
        valueEl.textContent = value;
    }
}

/**
 * Update system information display
 */
function updateSystemInfo(metrics) {
    // Can be expanded to show more detailed metrics
}

/**
 * Update status bar indicators
 */
function updateStatusBar() {
    // Web Server Status
    updateStatusItem('webServerStatus', 'healthy');

    // Database Status
    updateStatusItem('databaseStatus', 'healthy');

    // Disk Status - update based on usage
    const diskGauge = document.getElementById('diskGauge');
    if (diskGauge) {
        const diskPercent = parseInt(diskGauge.dataset.percent) || 0;
        const diskLabel = document.getElementById('diskLabel');
        if (diskLabel) {
            diskLabel.textContent = `Disk (${diskPercent}%)`;
        }
        
        if (diskPercent > 90) {
            updateStatusItem('diskStatus', 'error');
        } else if (diskPercent > 80) {
            updateStatusItem('diskStatus', 'warning');
        } else {
            updateStatusItem('diskStatus', 'healthy');
        }
    }
}

/**
 * Update individual status item
 */
function updateStatusItem(itemId, status) {
    const item = document.getElementById(itemId);
    if (item) {
        item.className = 'status-item ' + status;
    }
}

// ========================================
// ERROR TRACKING
// ========================================

/**
 * Load error statistics
 */
async function loadErrorStats() {
    try {
        const response = await fetch(MONITORING_CONFIG.apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'get_error_stats'
            })
        });

        const data = await response.json();

        if (data.success && data.data) {
            updateErrorDisplay(data.data);
        }
    } catch (error) {
        console.error('Failed to load error stats:', error);
    }
}

/**
 * Update error display
 */
function updateErrorDisplay(stats) {
    const errorLabel = document.getElementById('errorLabel');
    if (errorLabel && stats.unresolved !== undefined) {
        errorLabel.textContent = `Errors (${stats.unresolved})`;
        
        const errorStatus = document.getElementById('errorStatus');
        if (stats.unresolved > 0) {
            updateStatusItem('errorStatus', stats.critical > 0 ? 'error' : 'warning');
        } else {
            updateStatusItem('errorStatus', 'healthy');
        }
    }
}

/**
 * View error details
 */
async function viewError(errorId) {
    currentErrorId = errorId;
    const modal = document.getElementById('errorModal');
    const content = document.getElementById('errorModalContent');

    if (modal && content) {
        modal.classList.add('active');
        content.innerHTML = '<div class="loading-spinner"><span class="material-icons spinning">sync</span></div>';

        try {
            const response = await fetch(MONITORING_CONFIG.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_error_details',
                    error_id: errorId
                })
            });

            const data = await response.json();

            if (data.success && data.data) {
                content.innerHTML = formatErrorDetails(data.data);
            } else {
                content.innerHTML = '<div class="error-detail"><p>Failed to load error details</p></div>';
            }
        } catch (error) {
            console.error('Failed to load error details:', error);
            content.innerHTML = '<div class="error-detail"><p>Error loading details</p></div>';
        }
    }
}

/**
 * Format error details for display
 */
function formatErrorDetails(error) {
    return `
        <div class="error-detail">
            <div class="error-detail-label">Error Type</div>
            <div class="error-detail-value">${escapeHtml(error.error_type || 'Unknown')}</div>
        </div>
        <div class="error-detail">
            <div class="error-detail-label">Severity</div>
            <div class="error-detail-value">${escapeHtml(error.severity || 'Unknown')}</div>
        </div>
        <div class="error-detail">
            <div class="error-detail-label">Message</div>
            <div class="error-detail-value">${escapeHtml(error.error_message || 'No message')}</div>
        </div>
        <div class="error-detail">
            <div class="error-detail-label">File</div>
            <div class="error-detail-value">${escapeHtml(error.file_path || 'Unknown')}${error.line_number ? ':' + error.line_number : ''}</div>
        </div>
        <div class="error-detail">
            <div class="error-detail-label">Occurrences</div>
            <div class="error-detail-value">${error.occurrence_count || 1} times</div>
        </div>
        <div class="error-detail">
            <div class="error-detail-label">Last Seen</div>
            <div class="error-detail-value">${formatDateTime(error.last_seen)}</div>
        </div>
        ${error.stack_trace ? `
        <div class="error-detail">
            <div class="error-detail-label">Stack Trace</div>
            <pre class="stack-trace">${escapeHtml(error.stack_trace)}</pre>
        </div>
        ` : ''}
    `;
}

/**
 * Hide error modal
 */
function hideErrorModal() {
    const modal = document.getElementById('errorModal');
    if (modal) {
        modal.classList.remove('active');
        currentErrorId = null;
    }
}

/**
 * Resolve error
 */
async function resolveError(errorId) {
    try {
        const response = await fetch(MONITORING_CONFIG.apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'resolve_error',
                error_id: errorId
            })
        });

        const data = await response.json();

        if (data.success) {
            showToast('Error marked as resolved', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Failed to resolve error', 'error');
        }
    } catch (error) {
        console.error('Failed to resolve error:', error);
        showToast('An error occurred', 'error');
    }
}

/**
 * Resolve current error from modal
 */
function resolveCurrentError() {
    if (currentErrorId) {
        resolveError(currentErrorId);
        hideErrorModal();
    }
}

// ========================================
// SCHEDULED TASKS
// ========================================

/**
 * Load tasks status
 */
async function loadTasksStatus() {
    // Tasks are loaded from PHP initially
    // This function can refresh the display if needed
}

/**
 * Toggle task enabled status
 */
async function toggleTask(taskId, enabled) {
    try {
        const response = await fetch(MONITORING_CONFIG.apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'toggle_task',
                task_id: taskId,
                enabled: enabled ? 1 : 0
            })
        });

        const data = await response.json();

        if (data.success) {
            showToast(`Task ${enabled ? 'enabled' : 'disabled'}`, 'success');
        } else {
            showToast(data.message || 'Failed to update task', 'error');
        }
    } catch (error) {
        console.error('Failed to toggle task:', error);
        showToast('An error occurred', 'error');
    }
}

/**
 * Run task immediately
 */
async function runTask(taskId) {
    showToast('Running task...', 'info');

    try {
        const response = await fetch(MONITORING_CONFIG.apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'run_task',
                task_id: taskId
            })
        });

        const data = await response.json();

        if (data.success) {
            showToast('Task completed successfully', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message || 'Task failed', 'error');
        }
    } catch (error) {
        console.error('Failed to run task:', error);
        showToast('An error occurred', 'error');
    }
}

// ========================================
// BACKUP MANAGEMENT
// ========================================

/**
 * Load backup status
 */
async function loadBackupStatus() {
    // Backup info loaded from PHP initially
}

/**
 * Trigger manual backup
 */
async function triggerBackup() {
    if (!confirm('Start a full system backup now?')) {
        return;
    }

    showToast('Starting backup...', 'info');

    try {
        const response = await fetch(MONITORING_CONFIG.apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'trigger_backup',
                backup_type: 'full'
            })
        });

        const data = await response.json();

        if (data.success) {
            showToast('Backup initiated successfully', 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showToast(data.message || 'Failed to start backup', 'error');
        }
    } catch (error) {
        console.error('Failed to trigger backup:', error);
        showToast('An error occurred', 'error');
    }
}

// ========================================
// SECURITY EVENTS
// ========================================

/**
 * Load security events
 */
async function loadSecurityEvents() {
    // Security events loaded from PHP initially
}

/**
 * Acknowledge security event
 */
async function acknowledgeEvent(eventId) {
    try {
        const response = await fetch(MONITORING_CONFIG.apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'acknowledge_security_event',
                event_id: eventId
            })
        });

        const data = await response.json();

        if (data.success) {
            showToast('Event acknowledged', 'success');
            event.target.closest('.btn').style.display = 'none';
        } else {
            showToast(data.message || 'Failed to acknowledge event', 'error');
        }
    } catch (error) {
        console.error('Failed to acknowledge event:', error);
        showToast('An error occurred', 'error');
    }
}

// ========================================
// ADMIN SESSIONS & ACTIVITY
// ========================================

/**
 * Load sessions statistics
 */
async function loadSessionsStats() {
    // Sessions loaded from PHP initially
}

/**
 * Load activity log
 */
async function loadActivityLog() {
    // Activity log loaded from PHP initially
}

// ========================================
// CHARTS
// ========================================

/**
 * Initialize all charts
 */
function initializeCharts() {
    initResponseTimeChart();
    initRequestsChart();
}

/**
 * Initialize response time chart
 */
function initResponseTimeChart() {
    const ctx = document.getElementById('responseTimeChart');
    if (!ctx) return;

    // Get data from PHP (embedded in page)
    const apiStatsData = window.apiStatsData || [];
    
    let labels = [];
    let values = [];

    if (apiStatsData && apiStatsData.length > 0) {
        const sorted = [...apiStatsData].reverse();
        labels = sorted.map(stat => {
            const date = new Date(stat.hour);
            return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        });
        values = sorted.map(stat => Math.round(stat.avg_response_time || 0));
    } else {
        // Fallback data
        labels = Array.from({ length: 24 }, (_, i) => i.toString().padStart(2, '0') + ':00');
        values = labels.map(() => Math.floor(Math.random() * 200) + 50);
    }

    charts.responseTime = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Response Time (ms)',
                data: values,
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 2,
                pointBackgroundColor: '#6366f1',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return value + 'ms';
                        }
                    }
                },
                x: {
                    ticks: {
                        maxRotation: 45,
                        callback: function (val, index) {
                            return index % Math.ceil(labels.length / 8) === 0 ? this.getLabelForValue(val) : '';
                        }
                    }
                }
            }
        }
    });
}

/**
 * Initialize requests chart
 */
function initRequestsChart() {
    const ctx = document.getElementById('requestsChart');
    if (!ctx) return;

    // Get data from PHP (embedded in page)
    const apiStatsData = window.apiStatsData || [];
    
    let labels = [];
    let requestCounts = [];
    let errorCounts = [];

    if (apiStatsData && apiStatsData.length > 0) {
        const sorted = [...apiStatsData].reverse();
        labels = sorted.map(stat => {
            const date = new Date(stat.hour);
            return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        });
        requestCounts = sorted.map(stat => Math.round(stat.request_count || 0));
        errorCounts = sorted.map(stat => Math.round(stat.error_count || 0));
    } else {
        // Fallback data
        labels = Array.from({ length: 24 }, (_, i) => i.toString().padStart(2, '0') + ':00');
        requestCounts = labels.map(() => Math.floor(Math.random() * 500) + 100);
        errorCounts = labels.map(() => Math.floor(Math.random() * 50) + 5);
    }

    charts.requests = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Successful Requests',
                    data: requestCounts.map((val, idx) => val - errorCounts[idx]),
                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    borderColor: '#10b981',
                    borderWidth: 1
                },
                {
                    label: 'Failed Requests',
                    data: errorCounts,
                    backgroundColor: 'rgba(239, 68, 68, 0.7)',
                    borderColor: '#ef4444',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: true, position: 'top' }
            },
            scales: {
                x: {
                    stacked: true,
                    ticks: {
                        maxRotation: 45,
                        callback: function (val, index) {
                            return index % Math.ceil(labels.length / 8) === 0 ? this.getLabelForValue(val) : '';
                        }
                    }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return value + ' req';
                        }
                    }
                }
            }
        }
    });
}

/**
 * Update charts with new data
 */
async function updateCharts() {
    try {
        const response = await fetch(MONITORING_CONFIG.apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'get_api_stats'
            })
        });

        const data = await response.json();

        if (data.success && data.data) {
            // Update chart data
            if (charts.responseTime && charts.requests) {
                updateChartData(data.data);
            }
        }
    } catch (error) {
        console.error('Failed to update charts:', error);
    }
}

/**
 * Update chart data
 */
function updateChartData(apiStats) {
    // Implementation for updating chart data dynamically
}

// ========================================
// UTILITY FUNCTIONS
// ========================================

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    // Check if showNotification function exists from admin.js
    if (typeof showNotification === 'function') {
        showNotification(message, type);
        return;
    }

    // Fallback toast implementation
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 24px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#6366f1'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;

    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Format date and time
 */
function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

/**
 * Format numbers with thousand separators
 */
function formatNumber(num) {
    return new Intl.NumberFormat().format(num);
}

// Expose functions globally for inline onclick handlers
window.refreshMetrics = refreshMetrics;
window.viewError = viewError;
window.hideErrorModal = hideErrorModal;
window.resolveError = resolveError;
window.resolveCurrentError = resolveCurrentError;
window.toggleTask = toggleTask;
window.runTask = runTask;
window.triggerBackup = triggerBackup;
window.acknowledgeEvent = acknowledgeEvent;
window.setGaugeValue = setGaugeValue;
