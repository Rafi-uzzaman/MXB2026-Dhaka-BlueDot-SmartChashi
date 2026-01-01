<?php
/**
 * Admin Analytics
 * User analytics, feature usage, and growth metrics
 */
$currPage = "Analytics";
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../layouts/admin-header.php';
?>

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title">Analytics</h1>
        <p class="page-subtitle">User growth, engagement, and platform metrics</p>
    </div>
    <div class="page-actions">
        <select id="dateRange" class="filter-select">
            <option value="7">Last 7 days</option>
            <option value="30" selected>Last 30 days</option>
            <option value="90">Last 90 days</option>
            <option value="365">Last year</option>
        </select>
        <button class="btn btn-secondary" onclick="exportAnalytics()">
            <span class="material-icons">download</span>
            Export
        </button>
    </div>
</div>

<!-- Key Metrics -->
<div class="metrics-row">
    <div class="metric-card highlight">
        <div class="metric-header">
            <span class="material-icons">people</span>
            <span class="metric-change positive" id="newUsersMonthBadge">+0</span>
        </div>
        <div class="metric-value" id="totalUsersMetric">0</div>
        <div class="metric-label">Total Users</div>
    </div>
    
    <div class="metric-card">
        <div class="metric-header">
            <span class="material-icons">trending_up</span>
            <span class="metric-change positive" id="growthRateBadge">+0%</span>
        </div>
        <div class="metric-value" id="newUsersWeekMetric">0</div>
        <div class="metric-label">New This Week</div>
    </div>
    
    <div class="metric-card">
        <div class="metric-header">
            <span class="material-icons">bolt</span>
        </div>
        <div class="metric-value" id="activeTodayMetric">0</div>
        <div class="metric-label">Active Today</div>
    </div>
    
    <div class="metric-card">
        <div class="metric-header">
            <span class="material-icons">schedule</span>
        </div>
        <div class="metric-value" id="activeWeekMetric">0</div>
        <div class="metric-label">Active This Week</div>
    </div>
</div>

<!-- Charts Row -->
<div class="charts-grid">
    <div class="chart-card large">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">show_chart</span>
                User Growth Trend
            </h3>
        </div>
        <div class="card-body">
            <canvas id="growthChart" height="280"></canvas>
        </div>
    </div>
    
    <div class="chart-card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">donut_large</span>
                User Distribution
            </h3>
        </div>
        <div class="card-body">
            <canvas id="distributionChart" height="250"></canvas>
        </div>
    </div>
</div>

<!-- Feature Usage -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons">apps</span>
            Feature Usage
        </h3>
    </div>
    <div class="card-body">
        <div class="feature-grid" id="featureGrid">
            <div class="loading-spinner">
                <span class="material-icons spinning">sync</span>
                Loading...
            </div>
        </div>
    </div>
</div>

<!-- Activity Heatmap & Top Users -->
<div class="charts-grid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">schedule</span>
                Activity by Hour
            </h3>
        </div>
        <div class="card-body">
            <canvas id="activityChart" height="200"></canvas>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">leaderboard</span>
                Top Active Users
            </h3>
        </div>
        <div class="card-body">
            <?php
            $topUsers = $db->resultSet("SELECT user_id, first_name, last_name, email, role, last_login FROM users WHERE last_login IS NOT NULL ORDER BY last_login DESC LIMIT 5");
            ?>
            <div class="top-users-list">
                <?php foreach ($topUsers as $i => $user): ?>
                    <div class="top-user-item">
                        <span class="rank"><?php echo $i + 1; ?></span>
                        <div class="user-avatar-xs"><?php echo strtoupper(substr($user['first_name'], 0, 1)); ?></div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                            <div class="user-meta"><?php echo ucfirst($user['role']); ?></div>
                        </div>
                        <div class="user-activity">Last active <?php echo date('M d', strtotime($user['last_login'])); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
.metrics-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 24px;
}

.metric-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--border-radius-lg);
    padding: 20px;
}

.metric-card.highlight {
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(99, 102, 241, 0.05) 100%);
    border-color: var(--primary);
}

.metric-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}

.metric-header .material-icons {
    font-size: 24px;
    color: var(--primary);
}

.metric-change {
    font-size: 12px;
    font-weight: 600;
    padding: 4px 8px;
    border-radius: 10px;
}

.metric-change.positive {
    background: rgba(16, 185, 129, 0.2);
    color: var(--secondary);
}

.metric-change.negative {
    background: rgba(239, 68, 68, 0.2);
    color: var(--danger);
}

.metric-value {
    font-size: 32px;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1.2;
}

.metric-label {
    font-size: 13px;
    color: var(--text-muted);
    margin-top: 4px;
}

/* Feature Grid */
.feature-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.feature-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background: var(--bg-tertiary);
    border-radius: var(--border-radius);
}

.feature-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.feature-icon.community { background: linear-gradient(135deg, #6366f1, #818cf8); }
.feature-icon.marketplace { background: linear-gradient(135deg, #10b981, #34d399); }
.feature-icon.crops { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
.feature-icon.disease { background: linear-gradient(135deg, #ef4444, #f87171); }

.feature-icon .material-icons {
    font-size: 24px;
    color: white;
}

.feature-info {
    flex: 1;
}

.feature-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--text-primary);
}

.feature-label {
    font-size: 12px;
    color: var(--text-muted);
}

.feature-chart {
    flex-shrink: 0;
}

/* Top Users */
.top-users-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.top-user-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: var(--bg-tertiary);
    border-radius: var(--border-radius-sm);
}

.rank {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: var(--primary);
    color: white;
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
}

.top-user-item:nth-child(1) .rank { background: #fbbf24; }
.top-user-item:nth-child(2) .rank { background: #94a3b8; }
.top-user-item:nth-child(3) .rank { background: #cd7f32; }

.user-activity {
    font-size: 12px;
    color: var(--text-muted);
    margin-left: auto;
}

@media (max-width: 1024px) {
    .metrics-row, .feature-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .metrics-row, .feature-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Growth Chart
async function initGrowthChart() {
    const days = document.getElementById('dateRange').value;
    const data = await adminAPI('get_user_chart_data', { days });
    
    if (!data.success) return;
    
    const ctx = document.getElementById('growthChart').getContext('2d');
    
    const labels = data.data.map(d => {
        const date = new Date(d.date);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });
    const values = data.data.map(d => d.count);
    
    // Calculate cumulative
    let cumulative = [];
    let sum = 0;
    values.forEach(v => {
        sum += v;
        cumulative.push(sum);
    });
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'New Users',
                    data: values,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y'
                },
                {
                    label: 'Cumulative',
                    data: cumulative,
                    borderColor: '#10b981',
                    borderDash: [5, 5],
                    fill: false,
                    tension: 0.4,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                y: { position: 'left', beginAtZero: true },
                y1: { position: 'right', grid: { drawOnChartArea: false } }
            }
        }
    });
}

// Distribution Chart
function initDistributionChart() {
    const ctx = document.getElementById('distributionChart').getContext('2d');
    
    <?php
    $roles = $db->resultSet("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $roleLabels = array_column($roles, 'role');
    $roleValues = array_column($roles, 'count');
    ?>
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_map('ucfirst', $roleLabels)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_map('intval', $roleValues)); ?>,
                backgroundColor: ['#10b981', '#f59e0b', '#6366f1'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
}

// Activity Chart
function initActivityChart() {
    const ctx = document.getElementById('activityChart').getContext('2d');
    
    // Sample data for hours
    const hours = Array.from({length: 24}, (_, i) => i.toString().padStart(2, '0') + ':00');
    const values = [10, 5, 3, 2, 2, 5, 15, 35, 45, 50, 55, 60, 58, 55, 50, 48, 52, 58, 62, 55, 45, 35, 25, 15];
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: hours,
            datasets: [{
                label: 'Active Users',
                data: values,
                backgroundColor: values.map(v => v > 50 ? 'rgba(99, 102, 241, 0.8)' : 'rgba(99, 102, 241, 0.4)'),
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true },
                x: {
                    ticks: {
                        callback: (val, index) => index % 4 === 0 ? hours[index] : ''
                    }
                }
            }
        }
    });
}

// Mini sparkline charts
function initSparklines() {
    const configs = [
        { id: 'postsChart', color: '#6366f1' },
        { id: 'productsChart', color: '#10b981' },
        { id: 'cropsChart', color: '#f59e0b' },
        { id: 'diseasesChart', color: '#ef4444' }
    ];
    
    configs.forEach(config => {
        const canvas = document.getElementById(config.id);
        if (!canvas) {
            console.warn(`Canvas element with id '${config.id}' not found`);
            return;
        }
        
        const ctx = canvas.getContext('2d');
        const data = Array.from({length: 7}, () => Math.floor(Math.random() * 50) + 10);
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map((_, i) => i),
                datasets: [{
                    data,
                    borderColor: config.color,
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 0
                }]
            },
            options: {
                responsive: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { display: false },
                    y: { display: false }
                }
            }
        });
    });
}

// Export to Excel
async function exportAnalytics() {
    showNotification('Preparing analytics export...', 'info');
    
    const period = document.getElementById('dateRange')?.value || 30;
    
    try {
        // Fetch all analytics data using Promise.all
        const [statsResponse, growthResponse, featureResponse] = await Promise.all([
            adminAjax('get_analytics_stats', { period: period }),
            adminAjax('get_user_growth_data', { days: period }),
            adminAjax('get_feature_usage', {})
        ]);
        
        if (!statsResponse || !statsResponse.success) {
            showNotification('Failed to fetch analytics data', 'error');
            return;
        }
        
        // Check if XLSX library is loaded
        if (typeof XLSX === 'undefined') {
            showNotification('Excel library not loaded. Please refresh the page.', 'error');
            return;
        }
        
        // Create Excel workbook
        const workbook = XLSX.utils.book_new();
        
        // Sheet 1: Summary Statistics
        const summaryData = [
            ['Analytics Report', ''],
            ['Generated:', new Date().toLocaleString()],
            ['Period:', period + ' days'],
            [''],
            ['Metric', 'Value'],
            ['Total Users', statsResponse.data?.total_users || 0],
            ['New Users (Period)', statsResponse.data?.new_users_period || 0],
            ['Active Today', statsResponse.data?.active_today || 0],
            ['Active This Week', statsResponse.data?.active_week || 0]
        ];
        
        const summarySheet = XLSX.utils.aoa_to_sheet(summaryData);
        summarySheet['!cols'] = [{ wch: 25 }, { wch: 15 }];
        XLSX.utils.book_append_sheet(workbook, summarySheet, 'Summary');
        
        // Sheet 2: User Growth
        if (growthResponse && growthResponse.success && growthResponse.data && growthResponse.data.length > 0) {
            const growthData = [['Date', 'New Users', 'Cumulative Users']];
            growthResponse.data.forEach(row => {
                growthData.push([
                    row.date || '',
                    row.new_users || 0,
                    row.cumulative || 0
                ]);
            });
            
            const growthSheet = XLSX.utils.aoa_to_sheet(growthData);
            growthSheet['!cols'] = [{ wch: 15 }, { wch: 12 }, { wch: 18 }];
            XLSX.utils.book_append_sheet(workbook, growthSheet, 'User Growth');
        }
        
        // Sheet 3: Feature Usage
        if (featureResponse && featureResponse.success && featureResponse.data) {
            const featureData = [
                ['Feature', 'Count'],
                ['Community Posts', featureResponse.data.posts?.length || 0],
                ['Products Listed', featureResponse.data.products?.length || 0],
                ['Crops Tracked', featureResponse.data.crops?.length || 0],
                ['Disease Reports', featureResponse.data.diseases?.length || 0]
            ];
            
            const featureSheet = XLSX.utils.aoa_to_sheet(featureData);
            featureSheet['!cols'] = [{ wch: 20 }, { wch: 10 }];
            XLSX.utils.book_append_sheet(workbook, featureSheet, 'Feature Usage');
        }
        
        // Generate filename
        const today = new Date();
        const dateStr = today.getFullYear() + '-' + 
                       String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                       String(today.getDate()).padStart(2, '0');
        const filename = 'analytics_' + period + 'days_' + dateStr + '.xlsx';
        
        // Export file
        XLSX.writeFile(workbook, filename);
        
        showNotification('Analytics exported successfully to ' + filename, 'success');
    } catch (error) {
        console.error('Export error:', error);
        showNotification('Failed to export analytics: ' + error.message, 'error');
    }
}

// Date range change
document.getElementById('dateRange').addEventListener('change', function() {
    location.reload(); // In production, you'd reload just the charts
});

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    initGrowthChart();
    initDistributionChart();
    initActivityChart();
    initSparklines();
});
</script>

<!-- SheetJS Library for Excel Export -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<script src="<?php echo $base_url; ?>admin-secure/assets/js/admin.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadAnalyticsData();
    
    document.getElementById('dateRange')?.addEventListener('change', function() {
        loadAnalyticsData();
    });
});

function loadAnalyticsData() {
    const period = document.getElementById('dateRange')?.value || 30;
    
    adminAjax('get_analytics_stats', { period: period }, (response) => {
        if (response.success) {
            const data = response.data;
            
            document.getElementById('totalUsersMetric').textContent = formatNumber(data.total_users);
            document.getElementById('newUsersMonthBadge').textContent = '+' + formatNumber(data.new_users_period);
            document.getElementById('newUsersWeekMetric').textContent = formatNumber(data.new_users_period);
            
            // Load growth rate
            adminAjax('get_user_growth_data', { days: period }, (growthResponse) => {
                if (growthResponse.success) {
                    const cumulative = growthResponse.data[growthResponse.data.length - 1]?.cumulative || 0;
                    const previous = growthResponse.data[Math.max(0, growthResponse.data.length - 8)]?.cumulative || 0;
                    const rate = previous > 0 ? ((cumulative - previous) / previous * 100).toFixed(1) : 0;
                    
                    const badgeEl = document.getElementById('growthRateBadge');
                    badgeEl.textContent = (rate >= 0 ? '+' : '') + rate + '%';
                    badgeEl.className = 'metric-change ' + (rate >= 0 ? 'positive' : 'negative');
                }
            });
        }
    });
    
    // Load feature usage
    adminAjax('get_feature_usage', {}, (response) => {
        if (response.success) {
            const data = response.data;
            const grid = document.getElementById('featureGrid');
            
            grid.innerHTML = `
                <div class="feature-card">
                    <div class="feature-icon community">
                        <span class="material-icons">forum</span>
                    </div>
                    <div class="feature-info">
                        <div class="feature-value">${formatNumber(data.posts?.length || 0)}</div>
                        <div class="feature-label">Community Posts</div>
                    </div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon marketplace">
                        <span class="material-icons">storefront</span>
                    </div>
                    <div class="feature-info">
                        <div class="feature-value">${formatNumber(data.products?.length || 0)}</div>
                        <div class="feature-label">Products Listed</div>
                    </div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon crops">
                        <span class="material-icons">agriculture</span>
                    </div>
                    <div class="feature-info">
                        <div class="feature-value">${formatNumber(data.crops?.length || 0)}</div>
                        <div class="feature-label">Crops Tracked</div>
                    </div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon disease">
                        <span class="material-icons">bug_report</span>
                    </div>
                    <div class="feature-info">
                        <div class="feature-value">${formatNumber(data.diseases?.length || 0)}</div>
                        <div class="feature-label">Disease Reports</div>
                    </div>
                </div>
            `;
        }
    });
}
</script>

<?php include __DIR__ . '/../layouts/admin-footer.php'; ?>
