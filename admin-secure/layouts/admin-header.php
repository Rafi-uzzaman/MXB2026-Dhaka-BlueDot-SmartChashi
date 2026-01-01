<?php
/**
 * Admin Header Layout
 * Premium admin panel header with sidebar navigation
 */

// Simple session-based admin authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . 'admin-login');
    exit;
}

$adminUser = getCurrentUser();
if (!$adminUser || $adminUser['role'] !== 'admin') {
    header('Location: ' . $base_url . 'admin-login');
    exit;
}

// Set admin_logged_in for compatibility
$_SESSION['admin_logged_in'] = true;

// Get current page for active nav highlighting
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentPage = str_replace('admin-', '', $currentPage);

// Get unread notifications count
$db = new Database();
$unreadNotifications = $db->single("SELECT COUNT(*) as count FROM admin_notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0", [$_SESSION['user_id']])['count'] ?? 0;

// Get pending items counts
$pendingReports = $db->single("SELECT COUNT(*) as count FROM content_reports WHERE status = 'pending'")['count'] ?? 0;
$unresolvedErrors = $db->single("SELECT COUNT(*) as count FROM error_logs WHERE is_resolved = 0 AND severity IN ('error', 'critical')")['count'] ?? 0;
$securityAlerts = $db->single("SELECT COUNT(*) as count FROM security_events WHERE is_acknowledged = 0 AND severity IN ('high', 'critical') AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")['count'] ?? 0;

$csrf_token = generateCSRFToken();

// Get saved theme preference or default to light
$theme = 'light'; // Default theme
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        // Load theme before page renders to prevent flash
        (function() {
            const savedTheme = localStorage.getItem('adminTheme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo $base_url; ?>img/logo.png">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    
    <!-- Mermaid.js for flowcharts -->
    <script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>public/css/admin.css">
</head>
<body>
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="<?php echo $base_url; ?>img/logo.png" alt="Logo">
                <span class="logo-text">Admin Panel</span>
            </div>
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">
                <span class="material-icons">menu</span>
            </button>
        </div>
        
        <nav class="sidebar-nav">
            <ul class="nav-list">
                <li class="nav-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                    <a href="<?php echo $base_url; ?>admin-secure/pages/admin-dashboard.php" class="nav-link">
                        <span class="material-icons nav-icon">dashboard</span>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                
                <li class="nav-section">User Management</li>
                
                <li class="nav-item <?php echo $currentPage === 'users' ? 'active' : ''; ?>">
                    <a href="<?php echo $base_url; ?>admin-secure/pages/admin-users.php" class="nav-link">
                        <span class="material-icons nav-icon">people</span>
                        <span class="nav-text">Users</span>
                    </a>
                </li>
                
                <li class="nav-section">Security & Monitoring</li>
                
                <li class="nav-item <?php echo $currentPage === 'security' ? 'active' : ''; ?>">
                    <a href="<?php echo $base_url; ?>admin-secure/pages/admin-security.php" class="nav-link">
                        <span class="material-icons nav-icon">security</span>
                        <span class="nav-text">Security Center</span>
                        <?php if ($securityAlerts > 0): ?>
                            <span class="nav-badge danger"><?php echo $securityAlerts; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                
                <li class="nav-item <?php echo $currentPage === 'monitoring' ? 'active' : ''; ?>">
                    <a href="<?php echo $base_url; ?>admin-secure/pages/admin-monitoring.php" class="nav-link">
                        <span class="material-icons nav-icon">monitor_heart</span>
                        <span class="nav-text">System Monitor</span>
                        <?php if ($unresolvedErrors > 0): ?>
                            <span class="nav-badge warning"><?php echo $unresolvedErrors; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                
                <li class="nav-section">Content</li>
                
                <li class="nav-item <?php echo $currentPage === 'content' ? 'active' : ''; ?>">
                    <a href="<?php echo $base_url; ?>admin-secure/pages/admin-content.php" class="nav-link">
                        <span class="material-icons nav-icon">report</span>
                        <span class="nav-text">Moderation</span>
                        <?php if ($pendingReports > 0): ?>
                            <span class="nav-badge warning"><?php echo $pendingReports; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                
                <li class="nav-section">Analytics & Reports</li>
                
                <li class="nav-item <?php echo $currentPage === 'analytics' ? 'active' : ''; ?>">
                    <a href="<?php echo $base_url; ?>admin-secure/pages/admin-analytics.php" class="nav-link">
                        <span class="material-icons nav-icon">analytics</span>
                        <span class="nav-text">Analytics</span>
                    </a>
                </li>
                
                <li class="nav-item <?php echo $currentPage === 'reports' ? 'active' : ''; ?>">
                    <a href="<?php echo $base_url; ?>admin-secure/pages/admin-reports.php" class="nav-link">
                        <span class="material-icons nav-icon">description</span>
                        <span class="nav-text">Reports</span>
                    </a>
                </li>
                
                <li class="nav-section">System</li>
                
                <li class="nav-item <?php echo $currentPage === 'backup' ? 'active' : ''; ?>">
                    <a href="<?php echo $base_url; ?>admin-secure/pages/admin-backup.php" class="nav-link">
                        <span class="material-icons nav-icon">backup</span>
                        <span class="nav-text">Backup</span>
                    </a>
                </li>
                
                <li class="nav-item <?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
                    <a href="<?php echo $base_url; ?>admin-secure/pages/admin-settings.php" class="nav-link">
                        <span class="material-icons nav-icon">settings</span>
                        <span class="nav-text">Settings</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <div class="sidebar-footer">
            <div class="system-status">
                <span class="status-indicator online"></span>
                <span class="status-text">System Online</span>
            </div>
        </div>
    </aside>
    
    <!-- Main Content Wrapper -->
    <div class="admin-main" id="adminMain">
        <!-- Top Header -->
        <header class="admin-header">
            <div class="header-left">
                <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle Menu">
                    <span class="material-icons">menu</span>
                </button>
                
                <div class="breadcrumb">
                    <a href="<?php echo $base_url; ?>admin-dashboard">Admin</a>
                    <span class="material-icons">chevron_right</span>
                    <span class="current"><?php echo $currPage; ?></span>
                </div>
            </div>
            
            <div class="header-right">
                <!-- Search -->
                <div class="header-search">
                    <span class="material-icons search-icon">search</span>
                    <input type="text" placeholder="Search..." id="globalSearch">
                    <kbd>Ctrl+K</kbd>
                </div>
                
                <!-- Theme Toggle -->
                <button class="header-btn theme-toggle" id="themeToggle" aria-label="Toggle Theme">
                    <span class="material-icons light-icon">light_mode</span>
                    <span class="material-icons dark-icon">dark_mode</span>
                </button>
                
                <!-- Notifications -->
                <div class="notification-dropdown">
                    <button class="header-btn notification-btn" id="notificationBtn" aria-label="Notifications">
                        <span class="material-icons">notifications</span>
                        <?php if ($unreadNotifications > 0): ?>
                            <span class="notification-badge"><?php echo min($unreadNotifications, 99); ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu notification-menu" id="notificationMenu">
                        <div class="dropdown-header">
                            <span>Notifications</span>
                            <a href="#" id="markAllRead">Mark all read</a>
                        </div>
                        <div class="notification-list" id="notificationList">
                            <div class="loading-spinner">
                                <span class="material-icons spinning">sync</span>
                            </div>
                        </div>
                        <div class="dropdown-footer">
                            <a href="<?php echo $base_url; ?>admin-notifications">View all notifications</a>
                        </div>
                    </div>
                </div>
                
                <!-- User Menu -->
                <div class="user-dropdown">
                    <button class="user-btn" id="userMenuBtn">
                        <?php if (!empty($adminUser['profile_img_url'])): ?>
                            <img src="<?php echo $base_url . 'public/' . $adminUser['profile_img_url']; ?>" alt="Profile" class="user-avatar">
                        <?php else: ?>
                            <div class="user-avatar-placeholder">
                                <?php echo strtoupper(substr($adminUser['first_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        <span class="user-name"><?php echo htmlspecialchars($adminUser['first_name']); ?></span>
                        <span class="material-icons">expand_more</span>
                    </button>
                    <div class="dropdown-menu user-menu" id="userMenu">
                        <div class="user-info">
                            <strong><?php echo htmlspecialchars($adminUser['first_name'] . ' ' . $adminUser['last_name']); ?></strong>
                            <span><?php echo htmlspecialchars($adminUser['email']); ?></span>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo $base_url; ?>admin-secure/pages/admin-settings.php?section=profile" class="dropdown-item">
                            <span class="material-icons">person</span>
                            My Profile
                        </a>
                        <a href="<?php echo $base_url; ?>admin-secure/pages/admin-settings.php" class="dropdown-item">
                            <span class="material-icons">settings</span>
                            Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo $base_url; ?>?page=home" class="dropdown-item">
                            <span class="material-icons">exit_to_app</span>
                            Exit Admin
                        </a>
                        <a href="<?php echo $base_url; ?>admin-secure/pages/admin-logout.php" class="dropdown-item logout" id="adminLogout">
                            <span class="material-icons">logout</span>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Page Content -->
        <main class="admin-content">
            <input type="hidden" id="csrfToken" value="<?php echo $csrf_token; ?>">
            <input type="hidden" id="baseUrl" value="<?php echo $base_url; ?>">
