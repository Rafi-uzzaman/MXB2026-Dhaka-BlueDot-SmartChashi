<?php
// Authentication check
include __DIR__ . '/../layouts/header.php';

if (!isLoggedIn()) {
    redirect('login');
}

$db = new Database();
$currentUser = getCurrentUser();
$officerId = $_GET['id'] ?? null;

if (!$officerId) {
    redirect('dashboard');
}

// Get officer information
$officer = $db->single("SELECT * FROM users WHERE user_id = ? AND role = 'officer'", [$officerId]);

if (!$officer) {
    redirect('dashboard');
}

// Get officer's statistics - specific to this officer
$farmersInRegion = $db->single("SELECT COUNT(*) as count FROM users WHERE role = 'farmer'", [])['count'] ?? 0;
$alertsIssued = $db->single("SELECT COUNT(*) as count FROM alerts WHERE created_by = ?", [$officerId])['count'] ?? 0;
$alertsThisMonth = $db->single("SELECT COUNT(*) as count FROM alerts WHERE created_by = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)", [$officerId])['count'] ?? 0;

// Field visits stats
$totalVisits = $db->single("SELECT COUNT(*) as count FROM field_visits WHERE officer_id = ?", [$officerId])['count'] ?? 0;
$completedVisits = $db->single("SELECT COUNT(*) as count FROM field_visits WHERE officer_id = ? AND status = 'completed'", [$officerId])['count'] ?? 0;
$scheduledVisits = $db->single("SELECT COUNT(*) as count FROM field_visits WHERE officer_id = ? AND status = 'scheduled'", [$officerId])['count'] ?? 0;

// Advisories stats
$advisoriesCreated = $db->single("SELECT COUNT(*) as count FROM advisories WHERE created_by = ?", [$officerId])['count'] ?? 0;

// Recent field visits
$recentVisits = $db->resultSet("SELECT fv.*, u.first_name as farmer_first, u.last_name as farmer_last, fp.region as farmer_region
    FROM field_visits fv
    LEFT JOIN users u ON fv.farmer_id = u.user_id
    LEFT JOIN farmer_profiles fp ON u.user_id = fp.user_id
    WHERE fv.officer_id = ?
    ORDER BY fv.visit_date DESC LIMIT 5", [$officerId]);

// Recent alerts issued by this officer
$recentAlerts = $db->resultSet("SELECT * FROM alerts WHERE created_by = ? ORDER BY created_at DESC LIMIT 5", [$officerId]);

// Recent advisories
$recentAdvisories = $db->resultSet("SELECT * FROM advisories WHERE created_by = ? ORDER BY created_at DESC LIMIT 5", [$officerId]);

// Disease reports handled/reviewed
$reportsReviewed = $db->single("SELECT COUNT(*) as count FROM disease_reports WHERE verified_by = ?", [$officerId])['count'] ?? 0;

// Check if current user is a farmer (for contact options)
$isFarmer = ($currentUser['role'] === 'farmer');
$isAdmin = ($currentUser['role'] === 'admin');
$isOwnProfile = ($currentUser['user_id'] == $officerId);
?>

<section class="hero">
    <h1><span class="material-icons">badge</span> Officer Profile</h1>
    <p>Viewing profile of <?php echo htmlspecialchars($officer['first_name'] . ' ' . ($officer['last_name'] ?? '')); ?></p>
</section>

<div class="profile-view-container">
    <!-- Left Column - Officer Info -->
    <div class="profile-view-left">
        <!-- Officer Information Card -->
        <div class="card profile-view-card">
            <div class="profile-view-header">
                <div class="profile-view-avatar officer-avatar">
                    <?php if (!empty($officer['profile_img_url'])): ?>
                        <img src="<?php echo $base_url.'public/'. htmlspecialchars($officer['profile_img_url']); ?>" alt="Profile">
                    <?php else: ?>
                        <span class="material-icons">admin_panel_settings</span>
                    <?php endif; ?>
                </div>
                <div class="profile-view-info">
                    <h2><?php echo htmlspecialchars($officer['first_name'] . ' ' . ($officer['last_name'] ?? '')); ?></h2>
                    <p class="profile-view-role">
                        <span class="material-icons">badge</span>
                        Agriculture Officer
                        <?php if ($officer['is_verified']): ?>
                        <span class="badge badge-success"><span class="material-icons" style="font-size: 0.8rem;">verified</span> Verified</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="profile-view-details">
                <div class="profile-detail-item">
                    <span class="material-icons">email</span>
                    <span><strong>Email:</strong> <?php echo htmlspecialchars($officer['email']); ?></span>
                </div>

                <div class="profile-detail-item">
                    <span class="material-icons">phone</span>
                    <span><strong>Phone:</strong> <?php echo htmlspecialchars($officer['phone']); ?></span>
                </div>

                <div class="profile-detail-item">
                    <span class="material-icons">event</span>
                    <span><strong>Member Since:</strong> <?php echo date('M Y', strtotime($officer['created_at'])); ?></span>
                </div>
                
                <?php if (!empty($officer['last_login'])): ?>
                <div class="profile-detail-item">
                    <span class="material-icons">access_time</span>
                    <span><strong>Last Active:</strong> <?php echo date('M d, Y H:i', strtotime($officer['last_login'])); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Contact Actions for Farmers -->
            <?php if ($isFarmer): ?>
            <div class="profile-actions">
                <a href="tel:<?php echo htmlspecialchars($officer['phone']); ?>" class="btn btn-success">
                    <span class="material-icons">phone</span> Call
                </a>
                <a href="mailto:<?php echo htmlspecialchars($officer['email']); ?>" class="btn btn-secondary">
                    <span class="material-icons">email</span> Email
                </a>
                <button class="btn btn-info" onclick="openModal('contactOfficerModal')">
                    <span class="material-icons">message</span> Message
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Statistics Card -->
        <div class="card profile-view-card">
            <h3><span class="material-icons">analytics</span> Officer Statistics</h3>
            <div class="profile-stats-grid">
                <div class="stat-box">
                    <span class="material-icons stat-icon text-primary">people</span>
                    <div class="stat-value"><?php echo $farmersInRegion; ?></div>
                    <div class="stat-label">Farmers</div>
                </div>
                <div class="stat-box">
                    <span class="material-icons stat-icon text-warning">notifications</span>
                    <div class="stat-value"><?php echo $alertsIssued; ?></div>
                    <div class="stat-label">Alerts</div>
                </div>
                <div class="stat-box">
                    <span class="material-icons stat-icon text-success">event_available</span>
                    <div class="stat-value"><?php echo $completedVisits; ?></div>
                    <div class="stat-label">Visits Done</div>
                </div>
                <div class="stat-box">
                    <span class="material-icons stat-icon text-info">schedule</span>
                    <div class="stat-value"><?php echo $scheduledVisits; ?></div>
                    <div class="stat-label">Scheduled</div>
                </div>
                <div class="stat-box">
                    <span class="material-icons stat-icon text-secondary">article</span>
                    <div class="stat-value"><?php echo $advisoriesCreated; ?></div>
                    <div class="stat-label">Advisories</div>
                </div>
                <div class="stat-box">
                    <span class="material-icons stat-icon text-danger">coronavirus</span>
                    <div class="stat-value"><?php echo $reportsReviewed; ?></div>
                    <div class="stat-label">Reports</div>
                </div>
            </div>
        </div>
        
        <!-- Responsibilities Card -->
        <div class="card profile-view-card">
            <h3><span class="material-icons">work</span> Responsibilities</h3>
            <div class="officer-responsibilities">
                <div class="responsibility-item">
                    <span class="material-icons text-success">check_circle</span>
                    <span>Monitor crop health and disease reports</span>
                </div>
                <div class="responsibility-item">
                    <span class="material-icons text-success">check_circle</span>
                    <span>Issue weather and agricultural alerts</span>
                </div>
                <div class="responsibility-item">
                    <span class="material-icons text-success">check_circle</span>
                    <span>Provide farming guidance and support</span>
                </div>
                <div class="responsibility-item">
                    <span class="material-icons text-success">check_circle</span>
                    <span>Coordinate field visits and inspections</span>
                </div>
                <div class="responsibility-item">
                    <span class="material-icons text-success">check_circle</span>
                    <span>Create and distribute advisories</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Column - Activities -->
    <div class="profile-view-right">
        
        <!-- Recent Field Visits -->
        <?php if ($recentVisits): ?>
        <div class="card">
            <div class="card-header-flex">
                <h3><span class="material-icons">event_note</span> Recent Field Visits</h3>
                <span class="badge"><?php echo $totalVisits; ?> total</span>
            </div>
            <div class="visit-timeline">
                <?php foreach ($recentVisits as $visit): ?>
                <div class="visit-item visit-<?php echo $visit['status']; ?>">
                    <div class="visit-date">
                        <span class="visit-day"><?php echo date('d', strtotime($visit['visit_date'])); ?></span>
                        <span class="visit-month"><?php echo date('M', strtotime($visit['visit_date'])); ?></span>
                    </div>
                    <div class="visit-content">
                        <div class="visit-header">
                            <strong><?php echo htmlspecialchars(($visit['farmer_first'] ?? 'Unknown') . ' ' . ($visit['farmer_last'] ?? '')); ?></strong>
                            <span class="badge badge-<?php echo $visit['status'] === 'completed' ? 'success' : ($visit['status'] === 'scheduled' ? 'info' : 'secondary'); ?>">
                                <?php echo ucfirst($visit['status']); ?>
                            </span>
                        </div>
                        <?php if ($visit['farmer_region']): ?>
                        <p class="visit-location"><span class="material-icons">location_on</span> <?php echo htmlspecialchars($visit['farmer_region']); ?></p>
                        <?php endif; ?>
                        <?php if ($visit['purpose']): ?>
                        <p class="visit-purpose"><?php echo htmlspecialchars(substr($visit['purpose'], 0, 100)); ?><?php echo strlen($visit['purpose']) > 100 ? '...' : ''; ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="empty-state">
                <span class="material-icons">event_busy</span>
                <p>No field visits recorded yet</p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Recent Alerts -->
        <?php if ($recentAlerts): ?>
        <div class="card">
            <div class="card-header-flex">
                <h3><span class="material-icons">notifications_active</span> Recent Alerts</h3>
                <span class="badge badge-warning"><?php echo $alertsThisMonth; ?> this month</span>
            </div>
            <div class="alerts-list">
                <?php foreach ($recentAlerts as $alert): ?>
                <div class="alert-item alert-<?php echo $alert['priority'] ?? 'medium'; ?>">
                    <div class="alert-icon">
                        <span class="material-icons">
                            <?php 
                            $iconMap = ['weather' => 'cloud', 'disease' => 'coronavirus', 'market' => 'store', 'advisory' => 'info'];
                            echo $iconMap[$alert['alert_type']] ?? 'notification_important';
                            ?>
                        </span>
                    </div>
                    <div class="alert-content">
                        <div class="alert-header">
                            <strong><?php echo htmlspecialchars($alert['title']); ?></strong>
                            <span class="badge badge-<?php echo $alert['priority'] === 'high' || $alert['priority'] === 'critical' ? 'danger' : ($alert['priority'] === 'medium' ? 'warning' : 'info'); ?>">
                                <?php echo ucfirst($alert['priority'] ?? 'Medium'); ?>
                            </span>
                        </div>
                        <p class="alert-message"><?php echo htmlspecialchars(substr($alert['message'], 0, 120)); ?><?php echo strlen($alert['message']) > 120 ? '...' : ''; ?></p>
                        <span class="alert-date"><span class="material-icons">schedule</span> <?php echo date('M d, Y', strtotime($alert['created_at'])); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Recent Advisories -->
        <?php if ($recentAdvisories): ?>
        <div class="card">
            <div class="card-header-flex">
                <h3><span class="material-icons">article</span> Recent Advisories</h3>
                <span class="badge"><?php echo $advisoriesCreated; ?> total</span>
            </div>
            <div class="advisories-list">
                <?php foreach ($recentAdvisories as $advisory): ?>
                <div class="advisory-item">
                    <div class="advisory-header">
                        <h4><?php echo htmlspecialchars($advisory['title']); ?></h4>
                        <span class="badge badge-<?php echo ($advisory['priority'] ?? 'medium') === 'high' ? 'danger' : 'info'; ?>">
                            <?php echo ucfirst($advisory['advisory_type'] ?? 'General'); ?>
                        </span>
                    </div>
                    <p class="advisory-content"><?php echo htmlspecialchars(substr($advisory['content'], 0, 150)); ?><?php echo strlen($advisory['content']) > 150 ? '...' : ''; ?></p>
                    <div class="advisory-meta">
                        <span><span class="material-icons">schedule</span> <?php echo date('M d, Y', strtotime($advisory['created_at'])); ?></span>
                        <?php if (!empty($advisory['target_region'])): ?>
                        <span><span class="material-icons">location_on</span> <?php echo htmlspecialchars($advisory['target_region']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Contact Officer Modal (for farmers) -->
<?php if ($isFarmer): ?>
<div id="contactOfficerModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><span class="material-icons">message</span> Contact Officer</h3>
            <button class="modal-close" onclick="closeModal('contactOfficerModal')">&times;</button>
        </div>
        <form id="contactOfficerForm">
            <input type="hidden" name="officerId" value="<?php echo $officerId; ?>">
            <div class="modal-body">
                <div class="officer-info-banner">
                    <span class="material-icons">badge</span>
                    <span><?php echo htmlspecialchars($officer['first_name'] . ' ' . ($officer['last_name'] ?? '')); ?> - Agriculture Officer</span>
                </div>
                
                <div class="form-group">
                    <label for="messageSubject">Subject</label>
                    <select id="messageSubject" name="subject" required>
                        <option value="">Select a topic...</option>
                        <option value="Crop Advice">Crop Advice</option>
                        <option value="Disease Concern">Disease Concern</option>
                        <option value="Weather Query">Weather Query</option>
                        <option value="Market Information">Market Information</option>
                        <option value="Visit Request">Request Field Visit</option>
                        <option value="General Inquiry">General Inquiry</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="messageContent">Message</label>
                    <textarea id="messageContent" name="message" rows="5" required placeholder="Type your message here..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('contactOfficerModal')">Cancel</button>
                <button type="submit" class="btn">
                    <span class="material-icons">send</span> Send Message
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
const baseUrl = '<?php echo $base_url; ?>';
const officerId = <?php echo $officerId; ?>;

// Modal functions
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal on outside click
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });
});

<?php if ($isFarmer): ?>
// Contact Officer Form
document.getElementById('contactOfficerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'contact_officer');
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="material-icons spinning">sync</span> Sending...';
    
    fetch(baseUrl + 'ajax/profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message || 'Message sent successfully!', 'success');
            closeModal('contactOfficerModal');
            this.reset();
        } else {
            showNotification(data.message || 'Failed to send message', 'error');
        }
    })
    .catch(error => {
        showNotification('Network error. Please try again.', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});
<?php endif; ?>

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
/* Profile View Container */
.profile-view-container {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.profile-view-left {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.profile-view-right {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Profile Card */
.profile-view-card {
    padding: 1.5rem;
}

.profile-view-header {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.profile-view-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    background: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
}

.profile-view-avatar.officer-avatar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.profile-view-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-view-avatar .material-icons {
    font-size: 50px;
    color: white;
}

.profile-view-info h2 {
    margin: 0 0 0.5rem;
    font-size: 1.3rem;
}

.profile-view-role {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #666;
    flex-wrap: wrap;
}

.profile-view-role .material-icons {
    font-size: 1rem;
}

/* Profile Details */
.profile-view-details {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.profile-detail-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem;
    background: #f9f9f9;
    border-radius: 8px;
}

.profile-detail-item .material-icons {
    color: var(--primary);
    font-size: 1.2rem;
}

/* Profile Actions */
.profile-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
    flex-wrap: wrap;
}

.profile-actions .btn {
    flex: 1;
    min-width: 100px;
}

/* Statistics Grid */
.profile-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
}

.stat-box {
    text-align: center;
    padding: 1rem 0.5rem;
    background: #f9f9f9;
    border-radius: 8px;
}

.stat-box .stat-icon {
    font-size: 1.5rem;
}

.stat-box .stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    margin: 0.25rem 0;
}

.stat-box .stat-label {
    font-size: 0.75rem;
    color: #666;
}

/* Officer Responsibilities */
.officer-responsibilities {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.responsibility-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #f0f9f4;
    border-radius: 8px;
    border-left: 3px solid var(--primary);
}

.responsibility-item .material-icons {
    font-size: 1.2rem;
}

/* Card Header Flex */
.card-header-flex {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #eee;
}

.card-header-flex h3 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Visit Timeline */
.visit-timeline {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.visit-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: #f9f9f9;
    border-radius: 8px;
    border-left: 3px solid #ccc;
}

.visit-item.visit-completed {
    border-left-color: var(--success);
}

.visit-item.visit-scheduled {
    border-left-color: var(--info);
}

.visit-item.visit-cancelled {
    border-left-color: var(--danger);
}

.visit-date {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 50px;
}

.visit-day {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--primary);
}

.visit-month {
    font-size: 0.75rem;
    color: #666;
    text-transform: uppercase;
}

.visit-content {
    flex: 1;
}

.visit-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.visit-location {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 0.25rem;
}

.visit-location .material-icons {
    font-size: 1rem;
}

.visit-purpose {
    font-size: 0.9rem;
    color: #333;
}

/* Alerts List */
.alerts-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.alert-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: #f9f9f9;
    border-radius: 8px;
    border-left: 3px solid #ffc107;
}

.alert-item.alert-high, .alert-item.alert-critical {
    border-left-color: #dc3545;
    background: #fff5f5;
}

.alert-item.alert-low {
    border-left-color: #17a2b8;
}

.alert-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 193, 7, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
}

.alert-item.alert-high .alert-icon,
.alert-item.alert-critical .alert-icon {
    background: rgba(220, 53, 69, 0.2);
}

.alert-item.alert-high .alert-icon .material-icons,
.alert-item.alert-critical .alert-icon .material-icons {
    color: #dc3545;
}

.alert-icon .material-icons {
    color: #ffc107;
}

.alert-content {
    flex: 1;
}

.alert-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.alert-message {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 0.5rem;
}

.alert-date {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.8rem;
    color: #999;
}

.alert-date .material-icons {
    font-size: 0.9rem;
}

/* Advisories List */
.advisories-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.advisory-item {
    padding: 1rem;
    background: #f9f9f9;
    border-radius: 8px;
    border-left: 3px solid var(--info);
}

.advisory-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.advisory-header h4 {
    margin: 0;
    font-size: 1rem;
}

.advisory-content {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 0.5rem;
}

.advisory-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.8rem;
    color: #999;
}

.advisory-meta span {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.advisory-meta .material-icons {
    font-size: 0.9rem;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 2rem;
    color: #999;
}

.empty-state .material-icons {
    font-size: 3rem;
    margin-bottom: 0.5rem;
}

/* Officer Info Banner */
.officer-info-banner {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.officer-info-banner .material-icons {
    font-size: 1.5rem;
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
    z-index: 1000;
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
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
    padding: 1rem 1.5rem;
    border-top: 1px solid #eee;
}

/* Notification */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    z-index: 2000;
    animation: slideIn 0.3s ease;
}

.notification-success {
    background: #d4edda;
    color: #155724;
}

.notification-error {
    background: #f8d7da;
    color: #721c24;
}

.notification.fade-out {
    animation: fadeOut 0.3s ease forwards;
}

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes fadeOut {
    from { opacity: 1; }
    to { opacity: 0; }
}

/* Spinning animation */
.spinning {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 900px) {
    .profile-view-container {
        grid-template-columns: 1fr;
    }
    
    .profile-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .profile-actions {
        flex-direction: column;
    }
    
    .profile-stats-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .stat-box {
        padding: 0.75rem 0.5rem;
    }
    
    .stat-box .stat-value {
        font-size: 1.2rem;
    }
    
    .visit-item {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .visit-date {
        flex-direction: row;
        gap: 0.5rem;
    }
    
    .alert-header, .advisory-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
