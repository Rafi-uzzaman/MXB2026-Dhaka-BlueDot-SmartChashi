<?php
include __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$action = preg_replace('/[^a-zA-Z0-9_-]/', '', $action);

// Map actions to handler files
$handlers = [
    'login' => 'auth/login.php',
    'register' => 'auth/register.php',
    'logout' => 'auth/logout.php',
    'get-crops' => 'crop/get-crops.php',
    'add-crop' => 'crop/add-crop.php',
    'update-crop' => 'crop/update-crop.php',
    'delete-crop' => 'crop/delete-crop.php',
    'analyze-disease' => 'disease/analyze.php',
    'get-chat-history' => 'chat/history.php',
    'send-message' => 'chat/send-message.php',
    'get-weather' => 'weather/get-weather.php',
    'get-alerts' => 'alerts/get-alerts.php',
    'mark-alert-read' => 'alerts/mark-read.php',
    'upload-image' => 'upload/image.php',
    'get-marketplace' => 'marketplace/get-products.php',
    'add-product' => 'marketplace/add-product.php',
    'get-community' => 'community/get-posts.php',
    'add-post' => 'community/add-post.php',
    'like-post' => 'community/like-post.php',
    // Task management
    'save-task' => 'tasks/save-task.php',
    'get-tasks' => 'tasks/get-tasks.php',
    'update-task' => 'tasks/update-task.php',
    'delete-task' => 'tasks/delete-task.php',
    // SMS settings
    'save-sms-settings' => 'sms/save-settings.php',
    'get-sms-settings' => 'sms/get-settings.php',
];

// Public actions that don't require authentication
$publicActions = ['login', 'register'];

// Check authentication for protected routes
if (!in_array($action, $publicActions) && !isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login first.']);
    exit;
}

if (!isset($handlers[$action])) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Action not found']);
    exit;
}

$handler_file = __DIR__ . '/' . $handlers[$action];

if (file_exists($handler_file)) {
    include $handler_file;
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Handler not found']);
}
?>
