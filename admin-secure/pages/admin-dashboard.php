<?php
/**
 * Admin Dashboard
 * Main command center with statistics, charts, and quick actions
 */
$currPage = "Dashboard";
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../layouts/admin-header.php';
?>

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Welcome back! Here's what's happening.</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-secondary" onclick="refreshDashboard()">
            <span class="material-icons">refresh</span>
            Refresh
        </button>
        <button class="btn btn-primary" onclick="showQuickActions()">
            <span class="material-icons">bolt</span>
            Quick Actions
        </button>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon users">
            <span class="material-icons">people</span>
        </div>
        <div class="stat-content">
            <span class="stat-value" id="totalUsers">-</span>
            <span class="stat-label">Total Users</span>
        </div>
        <div class="stat-trend positive" id="usersTrend">
            <span class="material-icons" id="usersTrendIcon">trending_up</span>
            <span id="newUsersWeek">+ - this week</span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon sessions">
            <span class="material-icons">devices</span>
        </div>
        <div class="stat-content">
            <span class="stat-value" id="activeSessions">-</span>
            <span class="stat-label">Active Sessions</span>
        </div>
        <div class="stat-info">
            <span class="material-icons">schedule</span>
            <span>Right now</span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon logins">
            <span class="material-icons">login</span>
        </div>
        <div class="stat-content">
            <span class="stat-value" id="todaysLogins">-</span>
            <span class="stat-label">Today's Logins</span>
        </div>
        <div class="stat-trend positive" id="loginsTrend">
            <span class="material-icons" id="loginsTrendIcon">trending_up</span>
            <span id="loginsChange">-</span>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon security">
            <span class="material-icons">shield</span>
        </div>
        <div class="stat-content">
            <span class="stat-value" id="failedLogins">-</span>
            <span class="stat-label">Failed Logins (24h)</span>
        </div>
        <div class="stat-trend neutral" id="failedTrend">
            <span class="material-icons" id="failedTrendIcon">trending_flat</span>
            <span id="failedLoginsStatus">Normal</span>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="charts-grid">
    <div class="chart-card large">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">show_chart</span>
                User Registration Trend
            </h3>
            <div class="card-actions">
                <select id="userChartPeriod" class="chart-select">
                    <option value="7">Last 7 days</option>
                    <option value="30" selected>Last 30 days</option>
                    <option value="90">Last 90 days</option>
                </select>
            </div>
        </div>
        <div class="card-body">
            <canvas id="userChart" height="300"></canvas>
        </div>
    </div>
    
    <div class="chart-card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">pie_chart</span>
                User Distribution
            </h3>
        </div>
        <div class="card-body">
            <canvas id="roleChart" height="250"></canvas>
        </div>
        <div class="chart-legend" id="roleLegend"></div>
    </div>
</div>

<!-- Second Row: Security Alerts -->
<div class="charts-grid">
    <div class="card security-card large">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">security</span>
                Security Alerts
            </h3>
            <a href="<?php echo $base_url; ?>admin-secure/pages/admin-security.php" class="card-link">View All</a>
        </div>
        <div class="card-body">
            <div id="securityAlertsContainer" class="alert-list">
                <div class="loading-spinner">
                    <span class="material-icons spinning">sync</span>
                    Loading...
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Third Row: Database Diagram & Activity -->
<div class="charts-grid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">account_tree</span>
                Database Schema
            </h3>
            <button class="btn btn-sm btn-ghost" onclick="toggleDiagram()">
                <span class="material-icons">fullscreen</span>
            </button>
        </div>
        <div class="card-body diagram-container">
            <div class="mermaid">
erDiagram
    users ||--o{ farmer_profiles : has
    users ||--o{ officer_profiles : has
    users ||--o{ admin_profiles : has
    users ||--o{ community_posts : creates
    users ||--o{ marketplace_products : sells
    users ||--o{ crop_data : manages
    users ||--o{ disease_reports : reports
    users ||--o{ alerts : receives
    
    crop_data ||--o{ crop_activities : has
    crop_data ||--o{ fertilizer_recommendations : gets
    
    community_posts ||--o{ post_comments : has
    community_posts ||--o{ post_likes : has
    
    marketplace_products ||--o{ product_reviews : has
    marketplace_products ||--o{ marketplace_orders : ordered
            </div>
        </div>
    </div>
    
    <div class="card activity-card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">history</span>
                Recent Activity
            </h3>
            <a href="#" class="card-link">View All</a>
        </div>
        <div class="card-body">
            <div id="activityContainer" class="activity-list">
                <div class="loading-spinner">
                    <span class="material-icons spinning">sync</span>
                    Loading...
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fourth Row: Recent Users & Quick Stats -->
<div class="charts-grid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">person_add</span>
                Recent Users
            </h3>
            <a href="<?php echo $base_url; ?>admin-secure/pages/admin-users.php" class="card-link">View All</a>
        </div>
        <div class="card-body">
            <div id="recentUsersContainer" class="user-list">
                <div class="loading-spinner">
                    <span class="material-icons spinning">sync</span>
                    Loading...
                </div>
            </div>
        </div>
    </div>
    
    <div class="card quick-stats-card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">insights</span>
                Quick Statistics
            </h3>
        </div>
        <div class="card-body">
            <div id="quickStatsContainer" class="quick-stats">
                <div class="loading-spinner">
                    <span class="material-icons spinning">sync</span>
                    Loading...
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions Modal -->
<div class="modal-overlay" id="quickActionsModal" onclick="if(event.target === this) hideQuickActions()">
    <div class="modal-box quick-actions-modal">
        <div class="modal-header">
            <h3>Quick Actions</h3>
            <button class="modal-close" onclick="hideQuickActions()">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="modal-body">
            <div class="quick-actions-grid">
                <a href="<?php echo $base_url; ?>admin-secure/pages/admin-users.php?action=create" class="quick-action-item">
                    <span class="material-icons">person_add</span>
                    <span>Add User</span>
                </a>
                <a href="<?php echo $base_url; ?>admin-secure/pages/admin-backup.php?action=create" class="quick-action-item">
                    <span class="material-icons">backup</span>
                    <span>Create Backup</span>
                </a>
                <a href="<?php echo $base_url; ?>admin-secure/pages/admin-reports.php?action=generate" class="quick-action-item">
                    <span class="material-icons">assessment</span>
                    <span>Generate Report</span>
                </a>
                <a href="<?php echo $base_url; ?>admin-secure/pages/admin-security.php" class="quick-action-item">
                    <span class="material-icons">security</span>
                    <span>Security Check</span>
                </a>
                <a href="<?php echo $base_url; ?>admin-secure/pages/admin-settings.php?section=maintenance" class="quick-action-item">
                    <span class="material-icons">engineering</span>
                    <span>Maintenance Mode</span>
                </a>
                <a href="<?php echo $base_url; ?>admin-secure/pages/admin-monitoring.php" class="quick-action-item">
                    <span class="material-icons">monitor_heart</span>
                    <span>System Health</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo $base_url; ?>admin-secure/assets/js/admin.js"></script>
<script>
// Load all dashboard data dynamically
function initDashboard() {
    loadDashboardStats();
    loadChartData();
    loadRecentActivity();
    loadSecurityAlerts();
    loadRecentUsers();
    loadQuickStats();
    
    // Auto-refresh every 5 minutes
    setInterval(() => {
        loadDashboardStats();
        loadSecurityAlerts();
        loadRecentActivity();
    }, 300000);
}

function loadSecurityAlerts() {
    adminAjax('get_security_events', { limit: 5 }, (response) => {
        const container = document.getElementById('securityAlertsContainer');
        if (!container) return;
        
        if (response.success && response.data.length > 0) {
            container.innerHTML = response.data.map(alert => `
                <div class="alert-item ${alert.severity}">
                    <span class="material-icons alert-icon">
                        ${alert.severity === 'critical' ? 'error' : 'warning'}
                    </span>
                    <div class="alert-content">
                        <span class="alert-type">${alert.event_type.replace('_', ' ')}</span>
                        <span class="alert-time">${formatDate(alert.created_at)}</span>
                    </div>
                    <span class="severity-badge ${alert.severity}">
                        ${alert.severity.charAt(0).toUpperCase() + alert.severity.slice(1)}
                    </span>
                </div>
            `).join('');
        } else {
            container.innerHTML = `
                <div class="empty-state small">
                    <span class="material-icons">verified_user</span>
                    <p>No security alerts</p>
                </div>
            `;
        }
    });
}

function loadRecentActivity() {
    adminAjax('get_recent_activity', { limit: 10 }, (response) => {
        const container = document.getElementById('activityContainer');
        if (!container) return;
        
        if (response.success && response.data.length > 0) {
            const activityIcons = {
                'user': 'person',
                'security': 'security',
                'system': 'settings',
                'content': 'article',
                'settings': 'tune',
                'data': 'database',
                'backup': 'backup',
                'report': 'description'
            };
            
            container.innerHTML = response.data.map(activity => `
                <div class="activity-item">
                    <div class="activity-icon ${activity.action_category}">
                        <span class="material-icons">${activityIcons[activity.action_category] || 'history'}</span>
                    </div>
                    <div class="activity-content">
                        <p class="activity-text">
                            <strong>${activity.first_name || 'System'}</strong>
                            ${activity.action}
                        </p>
                        <span class="activity-time">${formatDate(activity.created_at)}</span>
                    </div>
                    ${activity.risk_level !== 'low' ? `
                        <span class="risk-badge ${activity.risk_level}">
                            ${activity.risk_level.charAt(0).toUpperCase() + activity.risk_level.slice(1)}
                        </span>
                    ` : ''}
                </div>
            `).join('');
        } else {
            container.innerHTML = '<p>No recent activity</p>';
        }
    });
}

function loadRecentUsers() {
    adminAjax('get_users', { limit: 5, page: 1 }, (response) => {
        const container = document.getElementById('recentUsersContainer');
        if (!container) return;
        
        if (response.success && response.data.length > 0) {
            container.innerHTML = response.data.map(user => `
                <div class="user-item">
                    <div class="user-avatar-sm">
                        ${user.first_name.charAt(0).toUpperCase()}
                    </div>
                    <div class="user-info">
                        <p class="user-name">${user.first_name} ${user.last_name || ''}</p>
                        <span class="user-email">${user.email}</span>
                    </div>
                    <span class="role-badge ${user.role}">${user.role.charAt(0).toUpperCase() + user.role.slice(1)}</span>
                </div>
            `).join('');
        }
    });
}

function loadQuickStats() {
    adminAjax('get_analytics_stats', {}, (response) => {
        const container = document.getElementById('quickStatsContainer');
        if (!container) return;
        
        if (response.success) {
            const data = response.data;
            container.innerHTML = `
                <div class="quick-stat">
                    <span class="material-icons">forum</span>
                    <div class="quick-stat-info">
                        <span class="value">${formatNumber(data.community_posts || 0)}</span>
                        <span class="label">Posts</span>
                    </div>
                </div>
                <div class="quick-stat">
                    <span class="material-icons">storefront</span>
                    <div class="quick-stat-info">
                        <span class="value">${formatNumber(data.marketplace_products || 0)}</span>
                        <span class="label">Products</span>
                    </div>
                </div>
                <div class="quick-stat">
                    <span class="material-icons">agriculture</span>
                    <div class="quick-stat-info">
                        <span class="value">${formatNumber(data.crop_records || 0)}</span>
                        <span class="label">Crops</span>
                    </div>
                </div>
                <div class="quick-stat">
                    <span class="material-icons">bug_report</span>
                    <div class="quick-stat-info">
                        <span class="value">${formatNumber(data.disease_reports || 0)}</span>
                        <span class="label">Reports</span>
                    </div>
                </div>
            `;
        }
    });
}

function updateDashboardUI(stats) {
    // Helper function to animate stat update
    const animateUpdate = (element) => {
        if (element) {
            element.classList.add('updating');
            setTimeout(() => element.classList.remove('updating'), 300);
        }
    };
    
    // Total Users
    if (stats.total_users !== undefined) {
        const el = document.getElementById('totalUsers');
        if (el) {
            el.textContent = formatNumber(stats.total_users);
            animateUpdate(el);
        }
    }
    
    // New Users This Week with dynamic trend
    if (stats.new_users_week !== undefined) {
        const el = document.getElementById('newUsersWeek');
        const trendEl = document.getElementById('usersTrend');
        const iconEl = document.getElementById('usersTrendIcon');
        
        if (el) {
            let text = `+${formatNumber(stats.new_users_week)} this week`;
            if (stats.users_change_percent !== undefined) {
                const sign = stats.users_change_percent >= 0 ? '+' : '';
                text += ` (${sign}${stats.users_change_percent}%)`;
            }
            el.textContent = text;
            animateUpdate(el);
        }
        
        // Update trend icon and class
        if (trendEl && iconEl && stats.users_trend) {
            trendEl.className = 'stat-trend';
            if (stats.users_trend === 'up') {
                trendEl.classList.add('positive');
                iconEl.textContent = 'trending_up';
            } else if (stats.users_trend === 'down') {
                trendEl.classList.add('negative');
                iconEl.textContent = 'trending_down';
            } else {
                trendEl.classList.add('neutral');
                iconEl.textContent = 'trending_flat';
            }
            animateUpdate(trendEl);
        }
    }
    
    // Active Sessions
    if (stats.active_sessions !== undefined) {
        const el = document.getElementById('activeSessions');
        if (el) {
            el.textContent = formatNumber(stats.active_sessions);
            animateUpdate(el);
        }
    }
    
    // Today's Logins with dynamic trend
    if (stats.todays_logins !== undefined) {
        const el = document.getElementById('todaysLogins');
        const changeEl = document.getElementById('loginsChange');
        const trendEl = document.getElementById('loginsTrend');
        const iconEl = document.getElementById('loginsTrendIcon');
        
        if (el) {
            el.textContent = formatNumber(stats.todays_logins);
            animateUpdate(el);
        }
        
        // Update change percentage
        if (changeEl && stats.logins_change_percent !== undefined) {
            const sign = stats.logins_change_percent >= 0 ? '+' : '';
            changeEl.textContent = `${sign}${stats.logins_change_percent}% from yesterday`;
        }
        
        // Update trend icon and class
        if (trendEl && iconEl && stats.logins_trend) {
            trendEl.className = 'stat-trend';
            if (stats.logins_trend === 'up') {
                trendEl.classList.add('positive');
                iconEl.textContent = 'trending_up';
            } else if (stats.logins_trend === 'down') {
                trendEl.classList.add('negative');
                iconEl.textContent = 'trending_down';
            } else {
                trendEl.classList.add('neutral');
                iconEl.textContent = 'trending_flat';
            }
            animateUpdate(trendEl);
        }
    }
    
    // Failed Logins with dynamic trend
    if (stats.failed_logins_24h !== undefined) {
        const failed = stats.failed_logins_24h;
        const el = document.getElementById('failedLogins');
        const statusEl = document.getElementById('failedLoginsStatus');
        const trendEl = document.getElementById('failedTrend');
        const iconEl = document.getElementById('failedTrendIcon');
        const cardEl = el?.closest('.stat-card');
        
        if (el) {
            el.textContent = formatNumber(failed);
            animateUpdate(el);
        }
        
        // Update status based on count
        if (statusEl) {
            if (failed > 10) {
                statusEl.textContent = 'Review needed';
                if (cardEl) cardEl.classList.add('warning');
            } else {
                if (cardEl) cardEl.classList.remove('warning');
                // Show percentage change if available
                if (stats.failed_change_percent !== undefined) {
                    const sign = stats.failed_change_percent >= 0 ? '+' : '';
                    statusEl.textContent = `${sign}${stats.failed_change_percent}% from previous 24h`;
                } else {
                    statusEl.textContent = 'Normal';
                }
            }
        }
        
        // Update trend icon and class - inverted for failed logins
        if (trendEl && iconEl && stats.failed_trend) {
            trendEl.className = 'stat-trend';
            if (stats.failed_is_good !== undefined) {
                // Down is good for failed logins
                if (stats.failed_is_good) {
                    trendEl.classList.add('positive');
                    iconEl.textContent = 'trending_down';
                } else {
                    trendEl.classList.add('negative');
                    iconEl.textContent = 'trending_up';
                }
            } else {
                trendEl.classList.add('neutral');
                iconEl.textContent = 'trending_flat';
            }
            animateUpdate(trendEl);
        }
    }
}

// Initialize on load
document.addEventListener('DOMContentLoaded', initDashboard);
</script>

<?php include __DIR__ . '/../layouts/admin-footer.php'; ?>
