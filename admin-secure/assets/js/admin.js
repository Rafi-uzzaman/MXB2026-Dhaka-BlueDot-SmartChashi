/**
 * Admin Dashboard JavaScript
 * Handles all admin page interactions and AJAX requests
 */

// Get base URL from hidden input or default to root
const getBaseUrl = () => {
    const baseUrlInput = document.getElementById('baseUrl');
    if (baseUrlInput && baseUrlInput.value) {
        // Ensure trailing slash for proper concatenation
        let url = baseUrlInput.value;
        if (!url.endsWith('/')) {
            url += '/';
        }
        return url;
    }
    return '/';
};

const ADMIN_CONFIG = {
    ajaxUrl: getBaseUrl() + 'admin-secure/ajax/admin.php',
    refreshInterval: 30000, // 30 seconds
    autoRefresh: true
};

// ========================================
// UTILITY FUNCTIONS
// ========================================

function getCSRFToken() {
    // Try multiple selectors for CSRF token
    let token = document.getElementById('csrfToken')?.value ||
        document.querySelector('[name="csrf_token"]')?.value ||
        document.querySelector('input[type="hidden"][value*="csrf"]')?.value ||
        '';
    return token;
}

function getDeviceFingerprint() {
    // Generate a simple device fingerprint
    return btoa(navigator.userAgent + navigator.language + screen.width + screen.height).substring(0, 32);
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <span>${message}</span>
        <button class="close-btn" onclick="this.parentElement.remove()">×</button>
    `;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 5000);
}

function showAlert(message, type = 'info') {
    showNotification(message, type);
}

function showConfirm(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString() + ' ' + new Date(dateString).toLocaleTimeString();
}

function formatNumber(num) {
    return new Intl.NumberFormat().format(num);
}

// ========================================
// AJAX REQUEST HANDLER
// ========================================

function adminAjax(action, data = {}, callback = null) {
    const formData = new FormData();
    formData.append('action', action);
    formData.append('csrf_token', getCSRFToken());
    formData.append('device_fingerprint', getDeviceFingerprint());

    Object.keys(data).forEach(key => {
        if (data[key] !== null && data[key] !== undefined) {
            if (typeof data[key] === 'object') {
                formData.append(key, JSON.stringify(data[key]));
            } else {
                formData.append(key, data[key]);
            }
        }
    });

    return fetch(ADMIN_CONFIG.ajaxUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
        .then(response => {
            // Check if response is ok
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            // Get text first to check if it's valid JSON
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Response text:', text);
                    throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                }
            });
        })
        .then(data => {
            if (callback) {
                callback(data);
            } else if (!data.success) {
                showNotification(data.message || 'An error occurred', 'error');
            }
            return data;
        })
        .catch(error => {
            console.error('AJAX Error:', error.message);
            showNotification('Error: ' + error.message, 'error');
            throw error;
        });
}

// Alias for monitoring compatibility
function adminAPI(action, data = {}) {
    return adminAjax(action, data);
}

// ========================================
// DASHBOARD
// ========================================

function loadDashboardStats() {
    adminAjax('get_dashboard_stats', {}, (response) => {
        if (response.success) {
            const data = response.data;

            // Update stat cards
            const totalUsersEl = document.getElementById('totalUsers');
            if (totalUsersEl) totalUsersEl.textContent = formatNumber(data.total_users);

            const newUsersTodayEl = document.getElementById('newUsersToday');
            if (newUsersTodayEl) newUsersTodayEl.textContent = formatNumber(data.new_users_today);

            const activeSessionsEl = document.getElementById('activeSessions');
            if (activeSessionsEl) activeSessionsEl.textContent = formatNumber(data.active_sessions);

            const todaysLoginsEl = document.getElementById('todaysLogins');
            if (todaysLoginsEl) todaysLoginsEl.textContent = formatNumber(data.todays_logins);

            const failedLoginsEl = document.getElementById('failedLogins');
            if (failedLoginsEl) failedLoginsEl.textContent = formatNumber(data.failed_logins_24h);

            const pendingReportsEl = document.getElementById('pendingReports');
            if (pendingReportsEl) pendingReportsEl.textContent = formatNumber(data.pending_reports);

            const unresolvedErrorsEl = document.getElementById('unresolvedErrors');
            if (unresolvedErrorsEl) unresolvedErrorsEl.textContent = formatNumber(data.unresolved_errors);

            const securityAlertsEl = document.getElementById('securityAlerts');
            if (securityAlertsEl) securityAlertsEl.textContent = formatNumber(data.security_alerts);

            // Update dashboard UI with additional stats
            if (typeof updateDashboardUI === 'function') {
                updateDashboardUI(data);
            }
        }
    }).catch(error => {
        console.error('Error loading dashboard stats:', error);
    });
}

function loadChartData() {
    // User growth chart
    adminAjax('get_user_growth_data', { days: 30 }, (response) => {
        if (response.success && typeof Chart !== 'undefined') {
            const ctx = document.getElementById('userChart');
            if (ctx) {
                const labels = response.data.map(d => d.date);
                const counts = response.data.map(d => d.new_users);

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'New Users',
                            data: counts,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }
        }
    });

    // User role distribution chart
    adminAjax('get_dashboard_stats', {}, (response) => {
        if (response.success && typeof Chart !== 'undefined') {
            const ctx = document.getElementById('roleChart');
            if (ctx) {
                const data = response.data;
                const roles = ['farmer', 'officer', 'admin'];
                const counts = [
                    data.farmers || 0,
                    data.officers || 0,
                    data.admins || 0
                ];
                const colors = ['#10b981', '#6366f1', '#f59e0b'];

                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Farmers', 'Officers', 'Admins'],
                        datasets: [{
                            data: counts,
                            backgroundColor: colors,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        }
    });
}

function loadRecentActivity() {
    adminAjax('get_recent_activity', { limit: 10 }, (response) => {
        if (response.success) {
            const tbody = document.getElementById('activityTableBody');
            if (tbody) {
                tbody.innerHTML = response.data.map(activity => `
                    <tr>
                        <td>${activity.first_name} ${activity.last_name || ''}</td>
                        <td>${activity.action}</td>
                        <td><span class="badge badge-${activity.risk_level}">${activity.risk_level}</span></td>
                        <td>${formatDate(activity.created_at)}</td>
                    </tr>
                `).join('');
            }
        }
    });
}

function refreshDashboard() {
    loadDashboardStats();
    loadChartData();
    loadRecentActivity();
}

// ========================================
// USER MANAGEMENT
// ========================================

function viewUser(userId) {
    adminAjax('get_user', { user_id: userId }, (response) => {
        if (response.success) {
            const user = response.data;
            const content = document.getElementById('viewUserContent');
            if (content) {
                content.innerHTML = `
                    <div class="user-details">
                        <div class="detail-row">
                            <span class="label">Name:</span>
                            <span class="value">${user.first_name} ${user.last_name}</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Email:</span>
                            <span class="value">${user.email}</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Phone:</span>
                            <span class="value">${user.phone || '-'}</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Role:</span>
                            <span class="value"><span class="badge badge-role-${user.role}">${user.role}</span></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Status:</span>
                            <span class="value"><span class="badge badge-${user.is_active ? 'success' : 'danger'}">${user.is_active ? 'Active' : 'Inactive'}</span></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Verified:</span>
                            <span class="value">${user.is_verified ? 'Yes' : 'No'}</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Last Login:</span>
                            <span class="value">${user.last_login ? formatDate(user.last_login) : 'Never'}</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Joined:</span>
                            <span class="value">${formatDate(user.created_at)}</span>
                        </div>
                    </div>
                `;
                document.getElementById('viewUserModal').classList.add('open');
            }
        }
    });
}

function editUser(userId) {
    const modal = document.getElementById('userModal');
    if (modal) modal.classList.add('active');
    
    adminAjax('get_user', { user_id: userId }, (response) => {
        if (response.success) {
            const user = response.data;
            document.getElementById('userId').value = userId;
            document.getElementById('firstName').value = user.first_name;
            document.getElementById('lastName').value = user.last_name || '';
            document.getElementById('email').value = user.email;
            document.getElementById('phone').value = user.phone || '';
            document.getElementById('role').value = user.role;
            document.getElementById('status').value = user.is_active ? 1 : 0;
            document.getElementById('isVerified').checked = user.is_verified;

            document.getElementById('userModalTitle').textContent = 'Edit User';
            document.getElementById('passwordGroup').style.display = 'none';
        } else {
            showAlert('Error loading user', 'error');
        }
    });
}

function createUser() {
    document.getElementById('userId').value = '';
    document.getElementById('firstName').value = '';
    document.getElementById('lastName').value = '';
    document.getElementById('email').value = '';
    document.getElementById('phone').value = '';
    document.getElementById('role').value = 'farmer';
    document.getElementById('status').value = 1;
    document.getElementById('isVerified').checked = false;
    document.getElementById('password').value = '';

    document.getElementById('userModalTitle').textContent = 'Add User';
    document.getElementById('passwordGroup').style.display = 'block';
    document.getElementById('userModal').classList.add('active');
}

function saveUser() {
    const userId = document.getElementById('userId').value;
    const password = document.getElementById('password').value;
    const firstName = document.getElementById('firstName').value.trim();
    const email = document.getElementById('email').value.trim();
    
    // Validate first name
    if (!firstName) {
        showAlert('First name is required', 'error');
        return;
    }
    
    // Validate email
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email)) {
        showAlert('Valid email is required', 'error');
        return;
    }
    
    // Validate phone (if provided)
    const phone = document.getElementById('phone').value.trim();
    if (phone && !/^\d{10,20}$/.test(phone.replace(/[^\d]/g, ''))) {
        showAlert('Phone number must be 10-20 digits', 'error');
        return;
    }
    
    // Validate password on new user
    if (!userId && !password) {
        showAlert('Password is required for new users', 'error');
        return;
    }
    
    // Validate password length
    if (password && password.length < 8) {
        showAlert('Password must be at least 8 characters', 'error');
        return;
    }
    
    const data = {
        first_name: firstName,
        last_name: document.getElementById('lastName').value.trim(),
        email: email,
        phone: phone,
        role: document.getElementById('role').value,
        is_active: document.getElementById('status').value,
        is_verified: document.getElementById('isVerified').checked ? 1 : 0
    };

    if (password) {
        data.password = password;
    }

    const action = userId ? 'update_user' : 'create_user';
    if (userId) {
        data.user_id = userId;
    }


    adminAjax(action, data, (response) => {
        if (response.success) {
            showAlert('User saved successfully', 'success');
            const modal = document.getElementById('userModal');
            if (modal) modal.classList.remove('active');
            setTimeout(() => location.reload(), 500);
        } else {
            showAlert(response.message || 'Error saving user', 'error');
        }
    });
}

function banUser(userId) {
    const modal = document.getElementById('banUserModal');
    document.getElementById('banUserId').value = userId;
    document.getElementById('banType').value = 'temporary';
    document.getElementById('banDuration').value = '7';
    document.getElementById('banReason').value = '';
    const durationGroup = document.getElementById('banDurationGroup');
    if (durationGroup) durationGroup.style.display = 'block';
    if (modal) modal.classList.add('active');
}

function submitBan() {
    const banReason = document.getElementById('banReason').value;
    if (!banReason.trim()) {
        showAlert('Please provide a reason for banning', 'error');
        return;
    }
    
    const data = {
        user_id: document.getElementById('banUserId').value,
        ban_type: document.getElementById('banType').value,
        duration: document.getElementById('banDuration').value,
        reason: banReason
    };

    adminAjax('ban_user', data, (response) => {
        if (response.success) {
            showAlert('User banned successfully', 'success');
            const modal = document.getElementById('banUserModal');
            if (modal) modal.classList.remove('active');
            setTimeout(() => location.reload(), 500);
        } else {
            showAlert(response.message || 'Error banning user', 'error');
        }
    });
}

function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        adminAjax('delete_user', { user_id: userId }, (response) => {
            if (response.success) {
                showAlert('User deleted successfully', 'success');
                setTimeout(() => location.reload(), 500);
            } else {
                showAlert(response.message || 'Error deleting user', 'error');
            }
        });
    }
}

// ========================================
// ANALYTICS
// ========================================

function loadAnalyticsData() {
    const period = document.getElementById('dateRange')?.value || 30;

    adminAjax('get_analytics_stats', { period: period }, (response) => {
        if (response.success) {
            const data = response.data;
            document.querySelectorAll('[data-metric]').forEach(el => {
                const metric = el.getAttribute('data-metric');
                if (data[metric] !== undefined) {
                    el.textContent = formatNumber(data[metric]);
                }
            });
        }
    });

    adminAjax('get_user_growth_data', { days: period }, (response) => {
        if (response.success && typeof Chart !== 'undefined') {
            const ctx = document.getElementById('growthChart');
            if (ctx) {
                // Create chart
            }
        }
    });
}

// ========================================
// SYSTEM MONITORING
// ========================================

function loadSystemMetrics() {
    adminAjax('get_system_metrics', {}, (response) => {
        if (response.success) {
            const metrics = response.data;

            // Display PHP metrics
            if (metrics.php) {
                document.querySelectorAll('[data-php-metric]').forEach(el => {
                    const metric = el.getAttribute('data-php-metric');
                    if (metrics.php[metric]) {
                        el.textContent = metrics.php[metric];
                    }
                });
            }

            // Display table sizes
            if (metrics.tables) {
                const tableList = document.getElementById('tableList');
                if (tableList) {
                    tableList.innerHTML = metrics.tables.map(table => `
                        <div class="table-item">
                            <span class="table-name">${table.table_name}</span>
                            <span class="table-size">${table.size_mb} MB</span>
                            <span class="table-rows">${formatNumber(table.table_rows)} rows</span>
                        </div>
                    `).join('');
                }
            }
        }
    });
}

function loadErrorLogs(page = 1) {
    const severity = document.getElementById('severityFilter')?.value || '';

    adminAjax('get_error_logs', {
        page: page,
        severity: severity,
        limit: 20
    }, (response) => {
        if (response.success) {
            const tbody = document.getElementById('errorLogsBody');
            if (tbody) {
                tbody.innerHTML = response.data.map(error => `
                    <tr class="severity-${error.severity}">
                        <td>${formatDate(error.first_seen)}</td>
                        <td>${error.error_type}</td>
                        <td>${error.message}</td>
                        <td><span class="badge badge-${error.is_resolved ? 'success' : 'warning'}">${error.is_resolved ? 'Resolved' : 'Unresolved'}</span></td>
                        <td class="actions-col">
                            <button class="btn-icon" onclick="resolveError(${error.error_id})">
                                <span class="material-icons">done</span>
                            </button>
                        </td>
                    </tr>
                `).join('');
            }
            updatePagination(response.pagination);
        }
    });
}

function resolveError(errorId) {
    const notes = prompt('Add resolution notes:');
    if (notes !== null) {
        adminAjax('resolve_error', {
            error_id: errorId,
            notes: notes
        }, (response) => {
            if (response.success) {
                showNotification(response.message, 'success');
                loadErrorLogs();
            }
        });
    }
}

// ========================================
// SECURITY CENTER
// ========================================

function loadSecurityEvents(page = 1) {
    adminAjax('get_security_events', {
        page: page,
        limit: 20
    }, (response) => {
        if (response.success) {
            const tbody = document.getElementById('securityEventsBody');
            if (tbody) {
                tbody.innerHTML = response.data.map(event => `
                    <tr class="severity-${event.severity}">
                        <td>${formatDate(event.created_at)}</td>
                        <td>${event.event_type}</td>
                        <td>${event.description}</td>
                        <td><span class="badge badge-${event.severity}">${event.severity}</span></td>
                        <td><span class="badge badge-${event.is_acknowledged ? 'success' : 'warning'}">${event.is_acknowledged ? 'Acknowledged' : 'New'}</span></td>
                        <td class="actions-col">
                            ${!event.is_acknowledged ? `<button class="btn-icon" onclick="acknowledgeEvent(${event.event_id})"><span class="material-icons">check</span></button>` : ''}
                        </td>
                    </tr>
                `).join('');
            }
            updatePagination(response.pagination);
        }
    });
}

function acknowledgeEvent(eventId) {
    adminAjax('acknowledge_security_event', { event_id: eventId }, (response) => {
        if (response.success) {
            loadSecurityEvents();
        }
    });
}

function loadIPRules(page = 1) {
    adminAjax('get_ip_rules', {
        page: page,
        limit: 20
    }, (response) => {
        if (response.success) {
            const tbody = document.getElementById('ipRulesBody');
            if (tbody) {
                tbody.innerHTML = response.data.map(rule => `
                    <tr>
                        <td>${rule.ip_address}</td>
                        <td><span class="badge badge-${rule.rule_type}">${rule.rule_type}</span></td>
                        <td>${rule.reason}</td>
                        <td>${rule.expires_at ? formatDate(rule.expires_at) : 'Permanent'}</td>
                        <td class="actions-col">
                            <button class="btn-icon danger" onclick="deleteIPRule(${rule.rule_id})">
                                <span class="material-icons">delete</span>
                            </button>
                        </td>
                    </tr>
                `).join('');
            }
        }
    });
}

function deleteIPRule(ruleId) {
    showConfirm('Delete this IP rule?', () => {
        adminAjax('delete_ip_rule', { rule_id: ruleId }, (response) => {
            if (response.success) {
                showNotification(response.message, 'success');
                loadIPRules();
            }
        });
    });
}

// ========================================
// SETTINGS
// ========================================

function loadSettings(section = 'general') {
    adminAjax('get_settings', { section: section }, (response) => {
        if (response.success) {
            const settings = response.data;

            // Populate form fields with settings
            document.querySelectorAll('[name]').forEach(field => {
                const key = field.getAttribute('name');
                if (settings[key] !== undefined) {
                    if (field.type === 'checkbox') {
                        field.checked = settings[key] === '1' || settings[key] === true;
                    } else {
                        field.value = settings[key];
                    }
                }
            });
        }
    });
}

function saveSettings() {
    const settings = {};
    document.querySelectorAll('[name][data-setting]').forEach(field => {
        const key = field.getAttribute('name');
        if (field.type === 'checkbox') {
            settings[key] = field.checked ? '1' : '0';
        } else {
            settings[key] = field.value;
        }
    });

    adminAjax('update_settings', { settings: settings }, (response) => {
        if (response.success) {
            showNotification(response.message, 'success');
        }
    });
}

// ========================================
// BACKUP & MAINTENANCE
// ========================================

function createBackup(type) {
    showConfirm(`Create ${type} backup?`, () => {
        adminAjax('create_backup', { type: type }, (response) => {
            if (response.success) {
                showNotification(response.message, 'success');
            }
        });
    });
}

function clearCache() {
    showConfirm('Clear application cache?', () => {
        adminAjax('clear_cache', {}, (response) => {
            if (response.success) {
                showNotification(response.message, 'success');
            }
        });
    });
}

// ========================================
// MODAL FUNCTIONS
// ========================================

function hideUserModal() {
    const modal = document.getElementById('userModal');
    if (modal) modal.classList.remove('active');
}

function hideViewUserModal() {
    const modal = document.getElementById('viewUserModal');
    if (modal) modal.classList.remove('active');
}

function hideBanModal() {
    const modal = document.getElementById('banUserModal');
    if (modal) modal.classList.remove('active');
}

// ========================================
// TABLE & PAGINATION
// ========================================
// Pagination is handled in individual page files

// ========================================
// EVENT LISTENERS
// ========================================

document.addEventListener('DOMContentLoaded', function () {
    // Page-specific event listeners are handled in individual page files

    // Date range change
    document.getElementById('dateRange')?.addEventListener('change', function () {
        loadAnalyticsData();
    });

    // User form submission
    document.getElementById('userForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        saveUser();
    });

    // Ban form submission
    document.getElementById('banForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        submitBan();
    });

    // Ban type change
    document.getElementById('banType')?.addEventListener('change', function () {
        const durationGroup = document.getElementById('banDurationGroup');
        if (durationGroup) {
            durationGroup.style.display = this.value === 'temporary' ? 'block' : 'none';
        }
    });

    // Select all checkbox
    document.getElementById('selectAll')?.addEventListener('change', function () {
        document.querySelectorAll('.user-checkbox').forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActionUI();
    });

    // Individual checkbox changes
    document.querySelectorAll('.user-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActionUI);
    });

    // Auto-refresh dashboard
    if (ADMIN_CONFIG.autoRefresh && document.getElementById('dashboardPage')) {
        setInterval(refreshDashboard, ADMIN_CONFIG.refreshInterval);
    }
});

// ========================================
// BULK ACTIONS
// ========================================

function updateBulkActionUI() {
    const checked = document.querySelectorAll('.user-checkbox:checked').length;
    const selectedCount = document.getElementById('selectedCount');
    const bulkActions = document.getElementById('bulkActions');

    if (selectedCount && bulkActions) {
        if (checked > 0) {
            selectedCount.style.display = 'inline';
            selectedCount.querySelector('.count').textContent = checked;
            bulkActions.style.display = 'flex';
        } else {
            selectedCount.style.display = 'none';
            bulkActions.style.display = 'none';
        }
    }
}

function bulkAction(action) {
    const selectedIds = Array.from(document.querySelectorAll('.user-checkbox:checked')).map(cb => cb.value);

    if (selectedIds.length === 0) {
        showNotification('No users selected', 'warning');
        return;
    }

    showConfirm(`${action.charAt(0).toUpperCase() + action.slice(1)} ${selectedIds.length} user(s)?`, () => {
        adminAjax('bulk_user_action', { action, userIds: selectedIds }, (response) => {
            if (response.success) {
                showAlert(response.message, 'success');
                setTimeout(() => location.reload(), 500);
            } else {
                showAlert(response.message || 'Action failed', 'error');
            }
        });
    });
}

// ========================================
// EXPORT FUNCTIONS
// ========================================

function exportUsers() {
    adminAjax('export_users', {}, (response) => {
        if (response.success && response.downloadUrl) {
            // Create a link and trigger download
            const link = document.createElement('a');
            link.href = response.downloadUrl;
            link.download = response.fileName || 'users_export.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            showAlert(`Exported ${response.recordCount} users successfully`, 'success');
        } else {
            showAlert(response.message || 'Export failed', 'error');
        }
    });
}

function exportAnalytics() {
    const data = {
        exported_at: new Date().toISOString(),
        // Add analytics data here
    };
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'analytics_' + new Date().getTime() + '.json';
    a.click();
}

// ========================================
// QUICK ACTIONS
// ========================================

function showQuickActions() {
    const modal = document.getElementById('quickActionsModal');
    if (modal) {
        modal.classList.add('active');
    }
}

// Export functions globally
window.adminAjax = adminAjax;
window.viewUser = viewUser;
window.editUser = editUser;
window.createUser = createUser;
window.saveUser = saveUser;
window.banUser = banUser;
window.submitBan = submitBan;
window.deleteUser = deleteUser;
window.loadDashboardStats = loadDashboardStats;
window.loadAnalyticsData = loadAnalyticsData;
window.loadSystemMetrics = loadSystemMetrics;
window.loadErrorLogs = loadErrorLogs;
window.resolveError = resolveError;
window.loadSecurityEvents = loadSecurityEvents;
window.acknowledgeEvent = acknowledgeEvent;
window.loadIPRules = loadIPRules;
window.deleteIPRule = deleteIPRule;
window.loadSettings = loadSettings;
window.saveSettings = saveSettings;
window.createBackup = createBackup;
window.clearCache = clearCache;
window.hideUserModal = hideUserModal;
window.hideViewUserModal = hideViewUserModal;
window.hideBanModal = hideBanModal;
window.showQuickActions = showQuickActions;
window.hideQuickActions = hideQuickActions;
window.toggleDiagram = toggleDiagram;
window.exportUsers = exportUsers;
window.exportAnalytics = exportAnalytics;
window.bulkAction = bulkAction;
window.refreshDashboard = refreshDashboard;
window.showNotification = showNotification;
window.showConfirm = showConfirm;
window.updateBulkActionUI = updateBulkActionUI;

function hideQuickActions() {
    const modal = document.getElementById('quickActionsModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

function toggleDiagram() {
    const container = document.querySelector('.diagram-container');
    if (container) {
        container.classList.toggle('fullscreen');
    }
}
