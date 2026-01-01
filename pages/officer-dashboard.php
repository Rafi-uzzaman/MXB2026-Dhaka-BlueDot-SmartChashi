<?php
/**
 * Extension Officer Dashboard
 * Dedicated dashboard for agricultural extension officers
 */

// Authentication and role check
if (!isLoggedIn()) {
    redirect('login');
}

$currentUser = getCurrentUser();
if ($currentUser['role'] !== 'officer') {
    // Redirect to appropriate dashboard based on role
    if ($currentUser['role'] === 'admin') {
        header('Location: ' . $base_url . 'admin-secure/pages/admin-dashboard.php');
        exit;
    } elseif ($currentUser['role'] === 'farmer') {
        redirect('farmer-dashboard');
    } else {
        redirect('home');
    }
}

include __DIR__ . '/../layouts/header.php';

$user = getCurrentUser();
$db = new Database();

// Get officer profile
$officerProfile = $db->single("SELECT * FROM officer_profiles WHERE user_id = ?", [$_SESSION['user_id']]);

// Get statistics
$totalFarmers = $db->single("SELECT COUNT(*) as count FROM users WHERE role = 'farmer'", [])['count'] ?? 0;
$activeCrops = $db->single("SELECT COUNT(*) as count FROM crop_data WHERE status = 'growing'", [])['count'] ?? 0;
$diseaseReports30 = $db->single("SELECT COUNT(*) as count FROM disease_reports WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)", [])['count'] ?? 0;
$alertsIssued7 = $db->single("SELECT COUNT(*) as count FROM alerts WHERE created_by = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)", [$_SESSION['user_id']])['count'] ?? 0;

// Get upcoming visits
$upcomingVisits = $db->resultSet("SELECT fv.*, u.first_name, u.last_name, u.phone, fp.region 
    FROM field_visits fv 
    JOIN users u ON fv.farmer_id = u.user_id 
    LEFT JOIN farmer_profiles fp ON u.user_id = fp.user_id 
    WHERE fv.officer_id = ? AND fv.status = 'scheduled' AND fv.visit_date >= CURDATE()
    ORDER BY fv.visit_date ASC, fv.visit_time ASC LIMIT 5", [$_SESSION['user_id']]);

// Get recent disease detections
$recentDetections = $db->resultSet("SELECT dr.*, u.first_name, u.last_name, u.phone, c.crop_name, fp.region 
    FROM disease_reports dr 
    JOIN users u ON dr.user_id = u.user_id 
    LEFT JOIN crop_data c ON dr.crop_id = c.crop_id 
    LEFT JOIN farmer_profiles fp ON u.user_id = fp.user_id 
    ORDER BY dr.created_at DESC LIMIT 10", []);

// Get farmers needing attention (high severity issues in last 7 days)
$farmersNeedingAttention = $db->resultSet("SELECT u.user_id, u.first_name, u.last_name, u.phone, fp.region, 
    COUNT(dr.detection_id) as issue_count,
    MAX(dr.created_at) as last_issue
    FROM users u 
    JOIN disease_reports dr ON u.user_id = dr.user_id 
    LEFT JOIN farmer_profiles fp ON u.user_id = fp.user_id 
    WHERE u.role = 'farmer' AND dr.severity = 'high' AND dr.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY u.user_id 
    ORDER BY issue_count DESC, last_issue DESC
    LIMIT 5", []);

// Get crops by region
$cropsByRegion = $db->resultSet("SELECT fp.region, COUNT(cd.crop_id) as crop_count, 
    COUNT(CASE WHEN cd.status = 'growing' THEN 1 END) as active_count
    FROM farmer_profiles fp 
    LEFT JOIN crop_data cd ON fp.user_id = cd.farmer_id 
    WHERE fp.region IS NOT NULL AND fp.region != ''
    GROUP BY fp.region 
    ORDER BY crop_count DESC", []);

// Get recent advisories created by this officer
$recentAdvisories = $db->resultSet("SELECT * FROM advisories WHERE created_by = ? ORDER BY created_at DESC LIMIT 5", [$_SESSION['user_id']]);

// Get regions list
$regions = ['Dhaka', 'Chittagong', 'Khulna', 'Rangpur', 'Sylhet', 'Barisal', 'Rajshahi', 'Mymensingh'];

// Get all farmers for dropdowns
$allFarmers = $db->resultSet("SELECT u.user_id, u.first_name, u.last_name, u.phone, fp.region 
    FROM users u 
    LEFT JOIN farmer_profiles fp ON u.user_id = fp.user_id 
    WHERE u.role = 'farmer' 
    ORDER BY u.first_name ASC", []);
?>

<section class="hero">
    <h1><span class="material-icons">badge</span> <?php echo __('officer_dashboard'); ?></h1>
    <p><?php echo __('welcome_officer'); ?>, <?php echo htmlspecialchars($user['first_name']); ?>! <?php echo __('monitor_support_farmers'); ?></p>
</section>

<!-- Officer Statistics -->
<h2 class="section-title"><span class="material-icons">analytics</span> <?php echo __('your_oversight_stats'); ?></h2>
<div class="officer-stats-grid">
    <div class="officer-stat-box stat-success">
        <span class="material-icons stat-icon">people</span>
        <div class="stat-number" id="statTotalFarmers"><?php echo $totalFarmers; ?></div>
        <div class="stat-label"><?php echo __('total_farmers'); ?></div>
    </div>
    <div class="officer-stat-box stat-primary">
        <span class="material-icons stat-icon">agriculture</span>
        <div class="stat-number" id="statActiveCrops"><?php echo $activeCrops; ?></div>
        <div class="stat-label"><?php echo __('active_crops'); ?></div>
    </div>
    <div class="officer-stat-box stat-warning">
        <span class="material-icons stat-icon">report</span>
        <div class="stat-number" id="statDiseaseReports"><?php echo $diseaseReports30; ?></div>
        <div class="stat-label"><?php echo __('reports_30d'); ?></div>
    </div>
    <div class="officer-stat-box stat-danger">
        <span class="material-icons stat-icon">notifications_active</span>
        <div class="stat-number" id="statAlertsIssued"><?php echo $alertsIssued7; ?></div>
        <div class="stat-label"><?php echo __('alerts_7d'); ?></div>
    </div>
</div>

<!-- Quick Actions -->
<h2 class="section-title"><span class="material-icons">bolt</span> <?php echo __('officer_actions'); ?></h2>
<div class="officer-actions-grid">
    <div class="officer-action-card" onclick="openModal('alertModal')">
        <span class="material-icons officer-action-icon">warning</span>
        <h3><?php echo __('issue_alert'); ?></h3>
        <p><?php echo __('send_alerts_farmers'); ?></p>
        <button class="btn btn-small btn-warning mt-2"><?php echo __('issue_alert'); ?></button>
    </div>
    
    <div class="officer-action-card" onclick="openModal('advisoryModal')">
        <span class="material-icons officer-action-icon">campaign</span>
        <h3><?php echo __('create_advisory'); ?></h3>
        <p><?php echo __('create_publish_advisories'); ?></p>
        <button class="btn btn-small btn-info mt-2"><?php echo __('create_advisory'); ?></button>
    </div>
    
    <div class="officer-action-card" onclick="openModal('visitModal')">
        <span class="material-icons officer-action-icon">event</span>
        <h3><?php echo __('schedule_visit'); ?></h3>
        <p><?php echo __('schedule_field_visits'); ?></p>
        <button class="btn btn-small btn-secondary mt-2"><?php echo __('schedule_visit'); ?></button>
    </div>
    
    <div class="officer-action-card" onclick="openModal('farmerSearchModal')">
        <span class="material-icons officer-action-icon">person_search</span>
        <h3><?php echo __('find_farmer'); ?></h3>
        <p><?php echo __('search_view_farmers'); ?></p>
        <button class="btn btn-small mt-2"><?php echo __('search_farmers'); ?></button>
    </div>
</div>

<!-- Upcoming Field Visits -->
<h2 class="section-title"><span class="material-icons">event_available</span> <?php echo __('upcoming_field_visits'); ?></h2>
<div class="card">
    <?php if ($upcomingVisits): ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th><?php echo __('date_time'); ?></th>
                    <th><?php echo __('farmer'); ?></th>
                    <th><?php echo __('region'); ?></th>
                    <th><?php echo __('purpose'); ?></th>
                    <th><?php echo __('actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($upcomingVisits as $visit): ?>
                <tr>
                    <td>
                        <strong><?php echo date('M d, Y', strtotime($visit['visit_date'])); ?></strong>
                        <?php if ($visit['visit_time']): ?>
                        <br><small><?php echo date('h:i A', strtotime($visit['visit_time'])); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($visit['first_name'] . ' ' . ($visit['last_name'] ?? '')); ?>
                        <br><small><a href="tel:<?php echo $visit['phone']; ?>"><?php echo $visit['phone']; ?></a></small>
                    </td>
                    <td><?php echo htmlspecialchars($visit['region'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars(substr($visit['purpose'] ?? __('general_inspection'), 0, 50)); ?><?php echo strlen($visit['purpose'] ?? '') > 50 ? '...' : ''; ?></td>
                    <td class="table-actions">
                        <button class="btn btn-small btn-success" onclick="completeVisitModal(<?php echo $visit['visit_id']; ?>)" title="<?php echo __('mark_complete'); ?>">
                            <span class="material-icons">check</span>
                        </button>
                        <button class="btn btn-small btn-danger" onclick="cancelVisit(<?php echo $visit['visit_id']; ?>)" title="<?php echo __('cancel_visit'); ?>">
                            <span class="material-icons">close</span>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <span class="material-icons empty-state-icon">event_busy</span>
        <p><?php echo __('no_upcoming_visits'); ?></p>
        <button class="btn btn-small mt-2" onclick="openModal('visitModal')"><?php echo __('schedule_a_visit'); ?></button>
    </div>
    <?php endif; ?>
</div>

<!-- Farmers Needing Attention -->
<?php if ($farmersNeedingAttention): ?>
<h2 class="section-title"><span class="material-icons">priority_high</span> <?php echo __('farmers_needing_attention'); ?></h2>
<div class="card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th><?php echo __('farmer'); ?></th>
                    <th><?php echo __('region'); ?></th>
                    <th><?php echo __('high_severity_issues'); ?></th>
                    <th><?php echo __('last_issue'); ?></th>
                    <th><?php echo __('actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($farmersNeedingAttention as $farmer): ?>
                <tr>
                    <td>
                        <?php echo htmlspecialchars($farmer['first_name'] . ' ' . ($farmer['last_name'] ?? '')); ?>
                        <br><small><?php echo htmlspecialchars($farmer['phone']); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($farmer['region'] ?? 'N/A'); ?></td>
                    <td><span class="badge badge-danger"><?php echo $farmer['issue_count']; ?> <?php echo __('issues'); ?></span></td>
                    <td><?php echo date('M d, Y', strtotime($farmer['last_issue'])); ?></td>
                    <td class="table-actions">
                        <a href="tel:<?php echo $farmer['phone']; ?>" class="btn btn-small" title="<?php echo __('call'); ?>">
                            <span class="material-icons">phone</span>
                        </a>
                        <a href="<?php echo $base_url; ?>?page=farmer-profile-view&id=<?php echo $farmer['user_id']; ?>" class="btn btn-small btn-info" title="<?php echo __('view_profile'); ?>">
                            <span class="material-icons">visibility</span>
                        </a>
                        <button class="btn btn-small btn-secondary" onclick="scheduleVisitFor(<?php echo $farmer['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($farmer['first_name'] . ' ' . ($farmer['last_name'] ?? ''))); ?>')" title="<?php echo __('schedule_visit'); ?>">
                            <span class="material-icons">event</span>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Recent Disease Detections -->
<h2 class="section-title"><span class="material-icons">coronavirus</span> <?php echo __('recent_disease_detections'); ?></h2>
<div class="officer-detections-grid">
    <?php if ($recentDetections): ?>
        <?php foreach (array_slice($recentDetections, 0, 6) as $detection): ?>
        <div class="card detection-card" onclick="viewDetection(<?php echo $detection['detection_id']; ?>)">
            <div class="card-header">
                <div class="detection-crop-info">
                    <span class="material-icons">eco</span>
                    <h4><?php echo htmlspecialchars($detection['crop_name'] ?? 'Unknown Crop'); ?></h4>
                </div>
                <span class="badge badge-<?php echo $detection['severity'] === 'high' ? 'danger' : ($detection['severity'] === 'medium' ? 'warning' : 'success'); ?>">
                    <?php echo __($detection['severity'] ?? 'low'); ?>
                </span>
            </div>
            <div class="card-content">
                <p><span class="material-icons">person</span> <?php echo htmlspecialchars($detection['first_name'] . ' ' . ($detection['last_name'] ?? '')); ?></p>
                <p><span class="material-icons">coronavirus</span> <?php echo htmlspecialchars($detection['disease_name'] ?? 'Unknown'); ?></p>
                <p><span class="material-icons">location_on</span> <?php echo htmlspecialchars($detection['region'] ?? 'N/A'); ?></p>
                <p><span class="material-icons">schedule</span> <?php echo date('M d, Y H:i', strtotime($detection['created_at'])); ?></p>
            </div>
            <div class="card-footer">
                <button class="btn btn-small btn-outline">
                    <span class="material-icons">visibility</span> <?php echo __('view_details'); ?>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="card empty-state-card">
            <span class="material-icons empty-state-icon">check_circle</span>
            <p><?php echo __('no_disease_detections'); ?></p>
        </div>
    <?php endif; ?>
</div>

<!-- Crops by Region & Recent Advisories -->
<div class="officer-two-col-grid">
    <!-- Crops by Region -->
    <div class="card">
        <h3><span class="material-icons">map</span> <?php echo __('crops_by_region'); ?></h3>
        <?php if ($cropsByRegion): ?>
        <div class="region-stats-list">
            <?php foreach ($cropsByRegion as $region): ?>
            <div class="region-stat-row">
                <span class="material-icons region-icon">location_on</span>
                <span class="region-name"><?php echo htmlspecialchars($region['region']); ?></span>
                <div class="region-badges">
                    <span class="badge badge-success"><?php echo $region['active_count']; ?> <?php echo __('active'); ?></span>
                    <span class="badge badge-secondary"><?php echo $region['crop_count']; ?> <?php echo __('total'); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-muted text-center">No crop data available</p>
        <?php endif; ?>
    </div>
    
    <!-- Recent Advisories -->
    <div class="card">
        <h3><span class="material-icons">campaign</span> Your Recent Advisories</h3>
        <?php if ($recentAdvisories): ?>
        <div class="advisory-list">
            <?php foreach ($recentAdvisories as $advisory): ?>
            <div class="advisory-item">
                <div class="advisory-header">
                    <span class="badge badge-<?php echo $advisory['priority'] === 'high' ? 'danger' : ($advisory['priority'] === 'medium' ? 'warning' : 'info'); ?>">
                        <?php echo ucfirst($advisory['advisory_type']); ?>
                    </span>
                    <small><?php echo date('M d', strtotime($advisory['created_at'])); ?></small>
                </div>
                <h4><?php echo htmlspecialchars($advisory['title']); ?></h4>
                <p><?php echo htmlspecialchars(substr($advisory['content'], 0, 80)); ?>...</p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <p class="text-muted">No advisories created yet</p>
            <button class="btn btn-small mt-2" onclick="openModal('advisoryModal')">Create Advisory</button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Issue Alert Modal -->
<div id="alertModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3><span class="material-icons">warning</span> <?php echo __('issue_alert'); ?></h3>
            <button class="modal-close" onclick="closeModal('alertModal')">&times;</button>
        </div>
        <form id="alertForm">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="alertType"><?php echo __('alert_type'); ?></label>
                        <select id="alertType" name="alertType" required>
                            <option value="weather"><?php echo __('weather_alert'); ?></option>
                            <option value="disease"><?php echo __('disease_alert'); ?></option>
                            <option value="market"><?php echo __('market_alert'); ?></option>
                            <option value="advisory"><?php echo __('advisory'); ?></option>
                            <option value="system"><?php echo __('system_alert'); ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="alertPriority"><?php echo __('priority'); ?></label>
                        <select id="alertPriority" name="priority" required>
                            <option value="low"><?php echo __('low'); ?></option>
                            <option value="medium" selected><?php echo __('medium'); ?></option>
                            <option value="high"><?php echo __('high'); ?></option>
                            <option value="critical"><?php echo __('critical'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="alertTitle"><?php echo __('title'); ?></label>
                    <input type="text" id="alertTitle" name="title" required placeholder="<?php echo __('enter_alert_title'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="alertMessage"><?php echo __('message'); ?></label>
                    <textarea id="alertMessage" name="message" rows="4" required placeholder="<?php echo __('enter_alert_message'); ?>"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="alertTargetRegion"><?php echo __('target_region'); ?></label>
                        <select id="alertTargetRegion" name="targetRegion">
                            <option value="all"><?php echo __('all_regions'); ?></option>
                            <?php foreach ($regions as $region): ?>
                            <option value="<?php echo $region; ?>"><?php echo $region; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="alertTargetFarmer"><?php echo __('specific_farmer_optional'); ?></label>
                        <select id="alertTargetFarmer" name="targetFarmer">
                            <option value="all"><?php echo __('all_farmers_in_region'); ?></option>
                            <?php foreach ($allFarmers as $farmer): ?>
                            <option value="<?php echo $farmer['user_id']; ?>">
                                <?php echo htmlspecialchars($farmer['first_name'] . ' ' . ($farmer['last_name'] ?? '') . ' - ' . $farmer['phone']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="alertSentVia"><?php echo __('send_via'); ?></label>
                    <select id="alertSentVia" name="sentVia">
                        <option value="app"><?php echo __('app_notification'); ?></option>
                        <option value="email"><?php echo __('email'); ?></option>
                        <option value="sms"><?php echo __('sms'); ?></option>
                        <option value="all"><?php echo __('all_channels'); ?></option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('alertModal')"><?php echo __('cancel'); ?></button>
                <button type="submit" class="btn btn-warning">
                    <span class="material-icons">send</span> <?php echo __('issue_alert'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Create Advisory Modal -->
<div id="advisoryModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3><span class="material-icons">campaign</span> <?php echo __('create_advisory'); ?></h3>
            <button class="modal-close" onclick="closeModal('advisoryModal')">&times;</button>
        </div>
        <form id="advisoryForm">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="advisoryType"><?php echo __('alert_type'); ?></label>
                        <select id="advisoryType" name="advisoryType" required>
                            <option value="general"><?php echo __('general'); ?></option>
                            <option value="weather"><?php echo __('weather'); ?></option>
                            <option value="seasonal"><?php echo __('seasonal'); ?></option>
                            <option value="pest_control"><?php echo __('pest_control'); ?></option>
                            <option value="irrigation"><?php echo __('irrigation'); ?></option>
                            <option value="market"><?php echo __('market'); ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="advisoryPriority"><?php echo __('priority'); ?></label>
                        <select id="advisoryPriority" name="priority" required>
                            <option value="low"><?php echo __('low'); ?></option>
                            <option value="medium" selected><?php echo __('medium'); ?></option>
                            <option value="high"><?php echo __('high'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="advisoryTitle"><?php echo __('title'); ?></label>
                    <input type="text" id="advisoryTitle" name="title" required placeholder="<?php echo __('enter_advisory_title'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="advisoryContent"><?php echo __('content'); ?></label>
                    <textarea id="advisoryContent" name="content" rows="6" required placeholder="<?php echo __('enter_advisory_content'); ?>"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="advisoryTargetCrops"><?php echo __('target_crops_comma'); ?></label>
                        <input type="text" id="advisoryTargetCrops" name="targetCrops" placeholder="<?php echo __('eg_rice_wheat'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="advisoryTargetRegion"><?php echo __('target_region'); ?></label>
                        <select id="advisoryTargetRegion" name="targetRegion">
                            <option value=""><?php echo __('all_regions'); ?></option>
                            <?php foreach ($regions as $region): ?>
                            <option value="<?php echo $region; ?>"><?php echo $region; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="advisoryValidFrom"><?php echo __('valid_from'); ?></label>
                        <input type="date" id="advisoryValidFrom" name="validFrom">
                    </div>
                    <div class="form-group">
                        <label for="advisoryValidTo"><?php echo __('valid_to'); ?></label>
                        <input type="date" id="advisoryValidTo" name="validTo">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('advisoryModal')"><?php echo __('cancel'); ?></button>
                <button type="submit" class="btn btn-info">
                    <span class="material-icons">publish</span> <?php echo __('publish_advisory'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Schedule Visit Modal -->
<div id="visitModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><span class="material-icons">event</span> <?php echo __('schedule_field_visit'); ?></h3>
            <button class="modal-close" onclick="closeModal('visitModal')">&times;</button>
        </div>
        <form id="visitForm">
            <div class="modal-body">
                <div class="form-group">
                    <label for="visitFarmer"><?php echo __('select_farmer'); ?></label>
                    <select id="visitFarmer" name="farmerId" required>
                        <option value=""><?php echo __('select_farmer_to_visit'); ?></option>
                        <?php foreach ($allFarmers as $farmer): ?>
                        <option value="<?php echo $farmer['user_id']; ?>">
                            <?php echo htmlspecialchars($farmer['first_name'] . ' ' . ($farmer['last_name'] ?? '')); ?> 
                            (<?php echo htmlspecialchars($farmer['region'] ?? 'No region'); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="visitDate"><?php echo __('visit_date'); ?></label>
                        <input type="date" id="visitDate" name="visitDate" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="visitTime"><?php echo __('visit_time'); ?> (<?php echo __('optional'); ?>)</label>
                        <input type="time" id="visitTime" name="visitTime">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="visitPurpose"><?php echo __('visit_purpose'); ?></label>
                    <textarea id="visitPurpose" name="purpose" rows="3" placeholder="<?php echo __('enter_visit_purpose'); ?>"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('visitModal')"><?php echo __('cancel'); ?></button>
                <button type="submit" class="btn">
                    <span class="material-icons">event_available</span> <?php echo __('schedule_visit'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Complete Visit Modal -->
<div id="completeVisitModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><span class="material-icons">check_circle</span> Complete Field Visit</h3>
            <button class="modal-close" onclick="closeModal('completeVisitModal')">&times;</button>
        </div>
        <form id="completeVisitForm">
            <input type="hidden" id="completeVisitId" name="visitId">
            <div class="modal-body">
                <div class="form-group">
                    <label for="visitObservations">Observations</label>
                    <textarea id="visitObservations" name="observations" rows="4" placeholder="What did you observe during the visit?"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="visitRecommendations">Recommendations</label>
                    <textarea id="visitRecommendations" name="recommendations" rows="4" placeholder="What recommendations do you have for the farmer?"></textarea>
                </div>
                
                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="visitFollowUp" name="followUpRequired" onchange="toggleFollowUpDate()">
                        <span>Follow-up Required</span>
                    </label>
                </div>
                
                <div class="form-group" id="followUpDateGroup" style="display: none;">
                    <label for="visitFollowUpDate">Follow-up Date</label>
                    <input type="date" id="visitFollowUpDate" name="followUpDate" min="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('completeVisitModal')">Cancel</button>
                <button type="submit" class="btn btn-success">
                    <span class="material-icons">check</span> Mark Complete
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Farmer Search Modal -->
<div id="farmerSearchModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3><span class="material-icons">person_search</span> Find Farmer</h3>
            <button class="modal-close" onclick="closeModal('farmerSearchModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group">
                    <input type="text" id="farmerSearch" placeholder="Search by name or phone..." oninput="searchFarmers()">
                </div>
                <div class="form-group">
                    <select id="farmerRegionFilter" onchange="searchFarmers()">
                        <option value="">All Regions</option>
                        <?php foreach ($regions as $region): ?>
                        <option value="<?php echo $region; ?>"><?php echo $region; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div id="farmerSearchResults" class="farmer-search-results">
                <p class="text-center text-muted">Enter a search term to find farmers</p>
            </div>
        </div>
    </div>
</div>

<!-- Detection Detail Modal -->
<div id="detectionModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3><span class="material-icons">coronavirus</span> Disease Detection Details</h3>
            <button class="modal-close" onclick="closeModal('detectionModal')">&times;</button>
        </div>
        <div class="modal-body" id="detectionModalContent">
            <div class="loading-spinner">
                <span class="material-icons spinning">sync</span>
                <p>Loading...</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('detectionModal')">Close</button>
            <button type="button" class="btn" id="detectionContactBtn">
                <span class="material-icons">phone</span> Contact Farmer
            </button>
        </div>
    </div>
</div>

<script>
const baseUrl = '<?php echo $base_url; ?>';

// Modal functions
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal when clicking outside
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });
});

// Toggle follow-up date field
function toggleFollowUpDate() {
    const followUp = document.getElementById('visitFollowUp').checked;
    document.getElementById('followUpDateGroup').style.display = followUp ? 'block' : 'none';
}

// Issue Alert Form
document.getElementById('alertForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'issue_alert');
    
    submitForm('alertForm', formData, 'alertModal', 'Alert issued successfully!');
});

// Create Advisory Form
document.getElementById('advisoryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'create_advisory');
    
    submitForm('advisoryForm', formData, 'advisoryModal', 'Advisory created successfully!');
});

// Schedule Visit Form
document.getElementById('visitForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'schedule_visit');
    
    submitForm('visitForm', formData, 'visitModal', 'Visit scheduled successfully!');
});

// Complete Visit Form
document.getElementById('completeVisitForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'complete_visit');
    
    submitForm('completeVisitForm', formData, 'completeVisitModal', 'Visit marked as completed!');
});

// Generic form submission
function submitForm(formId, formData, modalId, successMessage) {
    const form = document.getElementById(formId);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="material-icons spinning">sync</span> Processing...';
    
    fetch(baseUrl + 'ajax/officer.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message || successMessage, 'success');
            closeModal(modalId);
            form.reset();
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message || 'An error occurred', 'error');
        }
    })
    .catch(error => {
        showNotification('Network error. Please try again.', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

// Complete Visit Modal
function completeVisitModal(visitId) {
    document.getElementById('completeVisitId').value = visitId;
    openModal('completeVisitModal');
}

// Cancel Visit
function cancelVisit(visitId) {
    if (!confirm('Are you sure you want to cancel this visit?')) return;
    
    const formData = new FormData();
    formData.append('action', 'cancel_visit');
    formData.append('visitId', visitId);
    
    fetch(baseUrl + 'ajax/officer.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Visit cancelled successfully', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message || 'Failed to cancel visit', 'error');
        }
    })
    .catch(error => {
        showNotification('Network error', 'error');
    });
}

// Schedule visit for specific farmer
function scheduleVisitFor(farmerId, farmerName) {
    document.getElementById('visitFarmer').value = farmerId;
    openModal('visitModal');
}

// Search farmers
let searchTimeout;
function searchFarmers() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const search = document.getElementById('farmerSearch').value;
        const region = document.getElementById('farmerRegionFilter').value;
        
        if (search.length < 2 && !region) {
            document.getElementById('farmerSearchResults').innerHTML = '<p class="text-center text-muted">Enter at least 2 characters to search</p>';
            return;
        }
        
        document.getElementById('farmerSearchResults').innerHTML = '<div class="loading-spinner"><span class="material-icons spinning">sync</span></div>';
        
        fetch(baseUrl + 'ajax/officer.php?action=get_farmers&search=' + encodeURIComponent(search) + '&region=' + encodeURIComponent(region))
        .then(response => response.json())
        .then(data => {
            if (data.success && data.farmers.length > 0) {
                let html = '<div class="farmer-list">';
                data.farmers.forEach(farmer => {
                    html += `
                        <div class="farmer-item">
                            <div class="farmer-info">
                                <strong>${farmer.first_name} ${farmer.last_name || ''}</strong>
                                <span class="badge badge-info">${farmer.region || 'No region'}</span>
                                <br><small>${farmer.phone}</small>
                                ${farmer.primary_crops ? '<br><small>Crops: ' + farmer.primary_crops + '</small>' : ''}
                            </div>
                            <div class="farmer-actions">
                                <a href="${baseUrl}?page=farmer-profile-view&id=${farmer.user_id}" class="btn btn-small btn-info">
                                    <span class="material-icons">visibility</span>
                                </a>
                                <a href="tel:${farmer.phone}" class="btn btn-small">
                                    <span class="material-icons">phone</span>
                                </a>
                                <button class="btn btn-small btn-secondary" onclick="scheduleVisitFor(${farmer.user_id}, '${farmer.first_name}'); closeModal('farmerSearchModal');">
                                    <span class="material-icons">event</span>
                                </button>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                document.getElementById('farmerSearchResults').innerHTML = html;
            } else {
                document.getElementById('farmerSearchResults').innerHTML = '<p class="text-center text-muted">No farmers found</p>';
            }
        })
        .catch(error => {
            document.getElementById('farmerSearchResults').innerHTML = '<p class="text-center text-danger">Error loading farmers</p>';
        });
    }, 300);
}

// View detection details
function viewDetection(detectionId) {
    openModal('detectionModal');
    document.getElementById('detectionModalContent').innerHTML = '<div class="loading-spinner"><span class="material-icons spinning">sync</span><p>Loading...</p></div>';
    
    fetch(baseUrl + 'ajax/officer.php?action=get_detection_details&detectionId=' + detectionId)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const d = data.detection;
            document.getElementById('detectionModalContent').innerHTML = `
                <div class="detection-detail-grid">
                    <div class="detection-info-section">
                        <h4><span class="material-icons">eco</span> Crop Information</h4>
                        <p><strong>Crop:</strong> ${d.crop_name || 'Unknown'}</p>
                        <p><strong>Variety:</strong> ${d.variety || 'N/A'}</p>
                    </div>
                    <div class="detection-info-section">
                        <h4><span class="material-icons">coronavirus</span> Disease Information</h4>
                        <p><strong>Disease:</strong> ${d.disease_name || 'Unknown'}</p>
                        <p><strong>Severity:</strong> <span class="badge badge-${d.severity === 'high' ? 'danger' : (d.severity === 'medium' ? 'warning' : 'success')}">${d.severity || 'N/A'}</span></p>
                        <p><strong>Confidence:</strong> ${d.confidence_score ? (d.confidence_score * 100).toFixed(1) + '%' : 'N/A'}</p>
                    </div>
                    <div class="detection-info-section">
                        <h4><span class="material-icons">person</span> Farmer Information</h4>
                        <p><strong>Name:</strong> ${d.first_name} ${d.last_name || ''}</p>
                        <p><strong>Phone:</strong> <a href="tel:${d.phone}">${d.phone}</a></p>
                        <p><strong>Region:</strong> ${d.region || 'N/A'}</p>
                    </div>
                    <div class="detection-info-section">
                        <h4><span class="material-icons">schedule</span> Detection Info</h4>
                        <p><strong>Detected:</strong> ${new Date(d.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</p>
                    </div>
                </div>
                ${d.treatment_suggestions ? `
                <div class="detection-treatment">
                    <h4><span class="material-icons">medical_services</span> Treatment Suggestions</h4>
                    <p>${d.treatment_suggestions}</p>
                </div>
                ` : ''}
            `;
            document.getElementById('detectionContactBtn').onclick = function() {
                window.location.href = 'tel:' + d.phone;
            };
        } else {
            document.getElementById('detectionModalContent').innerHTML = '<p class="text-center text-danger">Failed to load detection details</p>';
        }
    })
    .catch(error => {
        document.getElementById('detectionModalContent').innerHTML = '<p class="text-center text-danger">Network error</p>';
    });
}

// Notification helper
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = 'notification notification-' + type;
    notification.innerHTML = `<span class="material-icons">${type === 'success' ? 'check_circle' : 'error'}</span> ${message}`;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
</script>

<style>
/* Section titles */
.section-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 2rem 0 1rem;
    font-size: 1.3rem;
    color: var(--primary);
}

/* Officer Stats Grid */
.officer-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.officer-stat-box {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.2s, box-shadow 0.2s;
}

.officer-stat-box:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.stat-icon {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
}

.stat-success .stat-icon { color: #28a745; }
.stat-primary .stat-icon { color: var(--primary); }
.stat-warning .stat-icon { color: #ffc107; }
.stat-danger .stat-icon { color: #dc3545; }

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #333;
}

.stat-label {
    color: #666;
    font-size: 0.9rem;
}

/* Actions Grid */
.officer-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.officer-action-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    text-decoration: none;
    color: inherit;
}

.officer-action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.officer-action-icon {
    font-size: 3rem;
    color: var(--primary);
    margin-bottom: 1rem;
}

.officer-action-card h3 {
    margin: 0 0 0.5rem;
    font-size: 1.1rem;
}

.officer-action-card p {
    color: #666;
    font-size: 0.9rem;
    margin: 0;
}

/* Detections Grid */
.officer-detections-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.detection-card {
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
}

.detection-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.detection-card .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #eee;
    margin-bottom: 0.75rem;
}

.detection-crop-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.detection-crop-info h4 {
    margin: 0;
    font-size: 1rem;
}

.detection-card .card-content p {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0.5rem 0;
    font-size: 0.9rem;
}

.detection-card .card-content .material-icons {
    font-size: 1rem;
    color: #666;
}

.detection-card .card-footer {
    padding-top: 0.75rem;
    border-top: 1px solid #eee;
    margin-top: 0.75rem;
}

/* Two column grid */
.officer-two-col-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.officer-two-col-grid .card h3 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #eee;
}

/* Region stats */
.region-stats-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.region-stat-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0;
}

.region-icon {
    color: var(--primary);
}

.region-name {
    flex: 1;
}

.region-badges {
    display: flex;
    gap: 0.5rem;
}

/* Advisory list */
.advisory-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.advisory-item {
    padding: 0.75rem;
    background: #f9f9f9;
    border-radius: 8px;
}

.advisory-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.advisory-item h4 {
    margin: 0 0 0.25rem;
    font-size: 0.95rem;
}

.advisory-item p {
    margin: 0;
    font-size: 0.85rem;
    color: #666;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 2rem;
}

.empty-state-icon {
    font-size: 3rem;
    color: #ccc;
    margin-bottom: 0.5rem;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 900 !important;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.modal-lg {
    max-width: 700px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #eee;
}

.modal-header h3 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #666;
    line-height: 1;
}

.modal-close:hover {
    color: #333;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid #eee;
    background: #f9f9f9;
    border-radius: 0 0 12px 12px;
}

/* Form styles */
.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #333;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: var(--primary);
    outline: none;
    box-shadow: 0 0 0 3px rgba(85, 122, 70, 0.1);
}

/* Farmer search */
.farmer-search-results {
    max-height: 400px;
    overflow-y: auto;
}

.farmer-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.farmer-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: #f9f9f9;
    border-radius: 8px;
}

.farmer-info {
    flex: 1;
}

.farmer-actions {
    display: flex;
    gap: 0.5rem;
}

/* Detection detail */
.detection-detail-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

.detection-info-section h4 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0 0 0.75rem;
    color: var(--primary);
    font-size: 0.95rem;
}

.detection-info-section p {
    margin: 0.5rem 0;
    font-size: 0.9rem;
}

.detection-treatment {
    grid-column: 1 / -1;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

/* Loading spinner */
.loading-spinner {
    text-align: center;
    padding: 2rem;
}

.spinning {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Notification */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    color: white;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    z-index: 950 !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    animation: slideIn 0.3s ease;
}

.notification-success {
    background: var(--primary);
}

.notification-error {
    background: #dc3545;
}

.notification.fade-out {
    opacity: 0;
    transition: opacity 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(100px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Badges */
.badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
}

.badge-success { background: #d4edda; color: #155724; }
.badge-danger { background: #f8d7da; color: #721c24; }
.badge-warning { background: #fff3cd; color: #856404; }
.badge-info { background: #d1ecf1; color: #0c5460; }
.badge-secondary { background: #e2e3e5; color: #383d41; }

/* Table */
.table-container {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #eee;
}

th {
    background: #f9f9f9;
    font-weight: 600;
}

.table-actions {
    display: flex;
    gap: 0.5rem;
}

/* Button styles */
.btn-outline {
    background: transparent;
    border: 1px solid var(--primary);
    color: var(--primary);
}

.btn-outline:hover {
    background: var(--primary);
    color: white;
}

.mt-2 {
    margin-top: 0.5rem;
}

/* Responsive */
@media (max-width: 768px) {
    .detection-detail-grid {
        grid-template-columns: 1fr;
    }
    
    .officer-actions-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .officer-two-col-grid {
        grid-template-columns: 1fr;
    }
    
    .farmer-item {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
}
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
