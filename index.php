<?php
/**
 * SmartCashi - Agricultural Management System
 * Main Router / Entry Point
 */

include __DIR__ . '/config/config.php';

$page = $_GET['page'] ?? 'home';
$id = $_GET['id'] ?? null;

// Sanitize page input
$page = preg_replace('/[^a-zA-Z0-9_-]/', '', $page);

// Map pages to files
$pages = [
    // Public pages
    'home' => 'pages/home.php',
    'login' => 'pages/login.php',
    'register' => 'pages/register.php',
    'logout' => 'pages/logout.php',
    
    // Admin login (public but separate)
    'admin-login' => 'admin-secure/pages/admin-login.php',
    
    // Authenticated user pages - role-specific dashboards
    'dashboard' => 'pages/farmer-dashboard.php',
    'farmer-dashboard' => 'pages/farmer-dashboard.php',
    'profile' => 'pages/profile.php',
    'crops' => 'pages/crops.php',
    'disease' => 'pages/disease.php',
    'weather' => 'pages/weather.php',
    'marketplace' => 'pages/marketplace.php',
    'community' => 'pages/community.php',
    'alerts' => 'pages/alerts.php',
    'agent' => 'agent/index.php',
    
    // Profile views
    'farmer-profile-view' => 'pages/farmer-profile-view.php',
    'officer-profile-view' => 'pages/officer-profile-view.php',
    
    // Officer pages
    'officer-dashboard' => 'pages/officer-dashboard.php',
    'farmer-reports' => 'pages/farmer-reports.php',
    'issue-alert' => 'pages/issue-alert.php',
    'advisory' => 'pages/advisory.php',
    
    // Admin pages (moved to secure folder)
    'admin-dashboard' => 'admin-secure/pages/admin-dashboard.php',
    'admin-users' => 'admin-secure/pages/admin-users.php',
    'admin-security' => 'admin-secure/pages/admin-security.php',
    'admin-monitoring' => 'admin-secure/pages/admin-monitoring.php',
    'admin-analytics' => 'admin-secure/pages/admin-analytics.php',
    'admin-content' => 'admin-secure/pages/admin-content.php',
    'admin-reports' => 'admin-secure/pages/admin-reports.php',
    'admin-backup' => 'admin-secure/pages/admin-backup.php',
    'admin-settings' => 'admin-secure/pages/admin-settings.php',
    'admin-login' => 'admin-secure/pages/admin-login.php',
    
    // Legacy admin routes (redirect to new)
    'user-management' => 'admin-secure/pages/admin-users.php',
    'system-settings' => 'admin-secure/pages/admin-settings.php',
    'analytics' => 'admin-secure/pages/admin-analytics.php',
];

// Pages that require authentication
$protected_pages = [
    'dashboard', 'profile', 'crops', 'disease', 'weather', 
    'marketplace', 'community', 'alerts', 'farmer-profile-view',
    'officer-profile-view', 'officer-dashboard', 'farmer-reports',
    'issue-alert', 'advisory', 'admin-dashboard', 'admin-users',
    'admin-security', 'admin-monitoring', 'admin-analytics',
    'admin-content', 'admin-reports', 'admin-backup', 'admin-settings',
    'user-management', 'system-settings', 'analytics'
];

// Pages that require officer role
$officer_pages = [
    'officer-dashboard', 'farmer-reports', 'issue-alert', 'advisory'
];

// Pages that require admin role
$admin_pages = [
    'admin-dashboard', 'admin-users', 'admin-security', 'admin-monitoring',
    'admin-analytics', 'admin-content', 'admin-reports', 'admin-backup',
    'admin-settings', 'user-management', 'system-settings', 'analytics'
];

// Check if page exists in routes
if (!isset($pages[$page])) {
    // Check if it's a 404
    http_response_code(404);
    $page = 'home';
}

// Authentication checks
if (in_array($page, $protected_pages) && !isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $page;
    redirect('login');
}

// Role-based access control
if (isLoggedIn()) {
    $user = getCurrentUser();
    $role = $user['role'] ?? 'farmer';
    
    // Check officer pages
    if (in_array($page, $officer_pages) && !in_array($role, ['officer', 'admin'])) {
        redirect('dashboard');
    }
    
    // Check admin pages
    if (in_array($page, $admin_pages) && $role !== 'admin') {
        redirect('dashboard');
    }
}

$page_file = __DIR__ . '/' . $pages[$page];

// Include the page
if (file_exists($page_file)) {
    include $page_file;
} else {
    // Show 404 page if file doesn't exist
    http_response_code(404);
    include __DIR__ . '/pages/404.php';
}
?>
