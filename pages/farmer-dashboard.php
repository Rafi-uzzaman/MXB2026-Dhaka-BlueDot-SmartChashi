<?php
/**
 * Farmer Dashboard
 * Dedicated dashboard for farmers with crop management, weather, and farming tools
 */

// Authentication and role check
if (!isLoggedIn()) {
    redirect('login');
}

$currentUser = getCurrentUser();
if ($currentUser['role'] !== 'farmer') {
    // Redirect to appropriate dashboard based on role
    if ($currentUser['role'] === 'admin') {
        header('Location: ' . $base_url . 'admin-secure/pages/admin-dashboard.php');
        exit;
    } elseif ($currentUser['role'] === 'officer') {
        redirect('officer-dashboard');
    } else {
        redirect('home');
    }
}

include __DIR__ . '/../layouts/header.php';

$db = new Database();
$userId = $_SESSION['user_id'];

// Get farmer profile
$farmerProfile = $db->single("SELECT * FROM farmer_profiles WHERE user_id = ?", [$userId]);

// Get farmer statistics
$stats = [];
try {
    $stats['total_crops'] = $db->single("SELECT COUNT(*) as count FROM crop_data WHERE farmer_id = ?", [$userId])['count'] ?? 0;
    $stats['active_crops'] = $db->single("SELECT COUNT(*) as count FROM crop_data WHERE farmer_id = ? AND status = 'growing'", [$userId])['count'] ?? 0;
    $stats['total_yield'] = $db->single("SELECT SUM(actual_yield) as total FROM crop_data WHERE farmer_id = ? AND actual_yield IS NOT NULL", [$userId])['total'] ?? 0;
    $stats['disease_reports'] = $db->single("SELECT COUNT(*) as count FROM disease_reports WHERE user_id = ?", [$userId])['count'] ?? 0;
    $stats['marketplace_products'] = $db->single("SELECT COUNT(*) as count FROM marketplace_products WHERE seller_id = ?", [$userId])['count'] ?? 0;
    $stats['community_posts'] = $db->single("SELECT COUNT(*) as count FROM community_posts WHERE user_id = ?", [$userId])['count'] ?? 0;
} catch (Exception $e) {
    // Handle errors silently
}

// Get recent crops
$recentCrops = [];
try {
    $recentCrops = $db->resultSet("SELECT * FROM crop_data WHERE farmer_id = ? ORDER BY planting_date DESC LIMIT 6", [$userId]);
} catch (Exception $e) {
    // Handle errors
}

// Get recent activities
$recentActivities = [];
try {
    $recentActivities = $db->resultSet("
        SELECT 'crop' as type, crop_name as title, planting_date as date, status 
        FROM crop_data WHERE farmer_id = ? 
        UNION ALL 
        SELECT 'post' as type, title, created_at as date, 'published' as status 
        FROM community_posts WHERE user_id = ? 
        ORDER BY date DESC LIMIT 5
    ", [$userId, $userId]);
} catch (Exception $e) {
    // Handle errors
}

// Get upcoming tasks
$upcomingTasks = [];
try {
    $upcomingTasks = $db->resultSet("SELECT * FROM tasks WHERE user_id = ? AND status != 'completed' ORDER BY due_date ASC LIMIT 5", [$userId]);
} catch (Exception $e) {
    // Handle errors
}
?>

<div class="container">
    <div class="dashboard-header">
        <div>
            <h1><?php echo __('dashboard'); ?></h1>
            <p>Welcome back, <?php echo htmlspecialchars($currentUser['first_name']); ?>!</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #4CAF50;">
                <span class="material-icons">agriculture</span>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['total_crops']; ?></h3>
                <p>Total Crops</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #2196F3;">
                <span class="material-icons">eco</span>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['active_crops']; ?></h3>
                <p>Active Crops</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #FF9800;">
                <span class="material-icons">shopping_cart</span>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['marketplace_products']; ?></h3>
                <p>Products Listed</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #F44336;">
                <span class="material-icons">bug_report</span>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['disease_reports']; ?></h3>
                <p>Disease Reports</p>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="dashboard-grid">
        <!-- Recent Crops -->
        <div class="card">
            <div class="card-header">
                <h3>Recent Crops</h3>
                <a href="<?php echo $base_url; ?>?page=crops" class="btn btn-small">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentCrops)): ?>
                    <p class="text-center text-muted">No crops yet. <a href="<?php echo $base_url; ?>?page=crops">Add your first crop</a></p>
                <?php else: ?>
                    <div class="crop-list">
                        <?php foreach ($recentCrops as $crop): ?>
                            <div class="crop-item">
                                <div class="crop-icon">
                                    <span class="material-icons">grass</span>
                                </div>
                                <div class="crop-details">
                                    <strong><?php echo htmlspecialchars($crop['crop_name']); ?></strong>
                                    <small><?php echo htmlspecialchars($crop['status']); ?> | <?php echo date('M d, Y', strtotime($crop['planting_date'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3><?php echo __('quick_actions'); ?></h3>
            </div>
            <div class="card-body">
                <div class="quick-actions">
                    <a href="<?php echo $base_url; ?>?page=crops" class="action-btn">
                        <span class="material-icons">add_circle</span>
                        <span><?php echo __('add_crop'); ?></span>
                    </a>
                    <a href="<?php echo $base_url; ?>?page=disease" class="action-btn">
                        <span class="material-icons">camera_alt</span>
                        <span><?php echo __('detect_disease'); ?></span>
                    </a>
                    <a href="<?php echo $base_url; ?>?page=weather" class="action-btn">
                        <span class="material-icons">wb_sunny</span>
                        <span><?php echo __('check_weather'); ?></span>
                    </a>
                    <a href="<?php echo $base_url; ?>?page=marketplace" class="action-btn">
                        <span class="material-icons">storefront</span>
                        <span><?php echo __('sell_products'); ?></span>
                    </a>
                    <a href="<?php echo $base_url; ?>?page=community" class="action-btn">
                        <span class="material-icons">forum</span>
                        <span>Community</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="card">
            <div class="card-header">
                <h3>Recent Activities</h3>
            </div>
            <div class="card-body">
                <?php if (empty($recentActivities)): ?>
                    <p class="text-center text-muted">No recent activities</p>
                <?php else: ?>
                    <div class="activity-list">
                        <?php foreach ($recentActivities as $activity): ?>
                            <div class="activity-item">
                                <span class="material-icons"><?php echo $activity['type'] === 'crop' ? 'agriculture' : 'forum'; ?></span>
                                <div class="activity-details">
                                    <strong><?php echo htmlspecialchars($activity['title']); ?></strong>
                                    <small><?php echo date('M d, Y', strtotime($activity['date'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upcoming Tasks -->
        <div class="card">
            <div class="card-header">
                <h3>Upcoming Tasks</h3>
            </div>
            <div class="card-body">
                <?php if (empty($upcomingTasks)): ?>
                    <p class="text-center text-muted">No upcoming tasks</p>
                <?php else: ?>
                    <div class="task-list">
                        <?php foreach ($upcomingTasks as $task): ?>
                            <div class="task-item">
                                <input type="checkbox" disabled>
                                <div class="task-details">
                                    <strong><?php echo htmlspecialchars($task['title']); ?></strong>
                                    <small>Due: <?php echo date('M d, Y', strtotime($task['due_date'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.stat-icon .material-icons {
    font-size: 32px;
}

.stat-info h3 {
    margin: 0;
    font-size: 2rem;
    color: #333;
}

.stat-info p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #eee;
}

.card-header h3 {
    margin: 0;
    font-size: 1.1rem;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 1rem;
}

.action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    background: #f5f5f5;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    transition: all 0.3s;
}

.action-btn:hover {
    background: #557A46;
    color: white;
    transform: translateY(-2px);
}

.crop-list, .activity-list, .task-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.crop-item, .activity-item, .task-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #f9f9f9;
    border-radius: 8px;
}

.crop-icon, .activity-item .material-icons {
    color: #557A46;
}

.crop-details, .activity-details, .task-details {
    flex: 1;
}

.crop-details strong, .activity-details strong, .task-details strong {
    display: block;
    margin-bottom: 0.25rem;
}

.crop-details small, .activity-details small, .task-details small {
    color: #666;
    font-size: 0.85rem;
}
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
