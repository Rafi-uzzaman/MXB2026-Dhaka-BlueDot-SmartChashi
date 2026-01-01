<?php
/**
 * Farmer Reports - For Agricultural Officers
 * View and analyze reports from farmers
 */

// Authentication and role check
if (!isLoggedIn()) {
    redirect('login');
}

$currentUser = getCurrentUser();
if ($currentUser['role'] !== 'officer') {
    redirect('home');
}

include __DIR__ . '/../layouts/header.php';

$db = new Database();

// Get filter parameters
$region = $_GET['region'] ?? 'all';
$reportType = $_GET['type'] ?? 'all';
$status = $_GET['status'] ?? 'all';

// Get all reports
$query = "SELECT dr.*, u.first_name, u.last_name, u.email, u.phone, 
          c.crop_name, fp.region,
          DATE_FORMAT(dr.created_at, '%M %d, %Y') as formatted_date
          FROM disease_reports dr
          JOIN users u ON dr.user_id = u.user_id
          LEFT JOIN crop_data c ON dr.crop_id = c.crop_id
          LEFT JOIN farmer_profiles fp ON u.user_id = fp.user_id
          WHERE 1=1";

$params = [];

if ($region !== 'all') {
    $query .= " AND fp.region = ?";
    $params[] = $region;
}

if ($reportType !== 'all') {
    $query .= " AND dr.severity = ?";
    $params[] = $reportType;
}

$query .= " ORDER BY dr.created_at DESC LIMIT 50";

$reports = $db->resultSet($query, $params);

// Get statistics
$stats = [
    'total' => $db->single("SELECT COUNT(*) as count FROM disease_reports")['count'] ?? 0,
    'critical' => $db->single("SELECT COUNT(*) as count FROM disease_reports WHERE severity = 'high'")['count'] ?? 0,
    'this_week' => $db->single("SELECT COUNT(*) as count FROM disease_reports WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['count'] ?? 0,
];

$regions = ['Dhaka', 'Chittagong', 'Khulna', 'Rangpur', 'Sylhet', 'Barisal', 'Rajshahi', 'Mymensingh'];
?>

<section class="hero">
    <h1><span class="material-icons">assessment</span> <?php echo __('farmer_reports'); ?></h1>
    <p><?php echo __('monitor_respond_reports'); ?></p>
</section>

<!-- Statistics Cards -->
<div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <span class="material-icons" style="font-size: 48px; opacity: 0.9;">description</span>
            <div>
                <div style="font-size: 2rem; font-weight: bold;"><?php echo $stats['total']; ?></div>
                <div style="opacity: 0.9;"><?php echo __('total_reports'); ?></div>
            </div>
        </div>
    </div>
    
    <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <span class="material-icons" style="font-size: 48px; opacity: 0.9;">warning</span>
            <div>
                <div style="font-size: 2rem; font-weight: bold;"><?php echo $stats['critical']; ?></div>
                <div style="opacity: 0.9;"><?php echo __('critical_issues'); ?></div>
            </div>
        </div>
    </div>
    
    <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <span class="material-icons" style="font-size: 48px; opacity: 0.9;">today</span>
            <div>
                <div style="font-size: 2rem; font-weight: bold;"><?php echo $stats['this_week']; ?></div>
                <div style="opacity: 0.9;"><?php echo __('this_week'); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons">filter_list</span>
            <?php echo __('filter_reports'); ?>
        </h3>
    </div>
    <form method="GET" class="card-body" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
        <div class="form-group" style="flex: 1; min-width: 200px; margin: 0;">
            <label><?php echo __('region'); ?></label>
            <select name="region" class="form-control">
                <option value="all"><?php echo __('all_regions'); ?></option>
                <?php foreach ($regions as $r): ?>
                    <option value="<?php echo $r; ?>" <?php echo $region === $r ? 'selected' : ''; ?>><?php echo $r; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="flex: 1; min-width: 200px; margin: 0;">
            <label><?php echo __('severity'); ?></label>
            <select name="type" class="form-control">
                <option value="all"><?php echo __('all_severities'); ?></option>
                <option value="low" <?php echo $reportType === 'low' ? 'selected' : ''; ?>><?php echo __('low'); ?></option>
                <option value="medium" <?php echo $reportType === 'medium' ? 'selected' : ''; ?>><?php echo __('medium'); ?></option>
                <option value="high" <?php echo $reportType === 'high' ? 'selected' : ''; ?>><?php echo __('high'); ?></option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">
            <span class="material-icons">search</span>
            <?php echo __('apply_filters'); ?>
        </button>
    </form>
</div>

<!-- Reports List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons">list</span>
            <?php echo __('recent_reports'); ?> (<?php echo count($reports); ?>)
        </h3>
    </div>
    <div class="card-body">
        <?php if (empty($reports)): ?>
            <div class="empty-state">
                <span class="material-icons" style="font-size: 64px; opacity: 0.3;">inbox</span>
                <p><?php echo __('no_reports_found'); ?></p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><?php echo __('date'); ?></th>
                            <th><?php echo __('farmer'); ?></th>
                            <th><?php echo __('region'); ?></th>
                            <th><?php echo __('crop'); ?></th>
                            <th><?php echo __('disease'); ?></th>
                            <th><?php echo __('severity'); ?></th>
                            <th><?php echo __('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><?php echo $report['formatted_date'] ?? 'N/A'; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($report['phone'] ?? 'No phone'); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($report['region'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($report['crop_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($report['disease_name'] ?? 'Unknown'); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $report['severity'] === 'high' ? 'danger' : 
                                            ($report['severity'] === 'medium' ? 'warning' : 'info'); 
                                    ?>">
                                        <?php echo __($report['severity'] ?? 'low'); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-small btn-primary" onclick="viewReport(<?php echo $report['detection_id']; ?>)">
                                        <span class="material-icons">visibility</span>
                                        <?php echo __('view'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function viewReport(id) {
    alert('Report details view - Feature coming soon! Report ID: ' + id);
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
