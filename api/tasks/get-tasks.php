<?php
// Get farm tasks for the current user

$db = new Database();
$user = getCurrentUser();

$filter = $_GET['filter'] ?? 'all';
$limit = intval($_GET['limit'] ?? 50);

// Base query
$sql = "SELECT * FROM farm_tasks WHERE user_id = ?";
$params = [$user['user_id']];

// Apply filter
switch ($filter) {
    case 'pending':
        $sql .= " AND status = 'pending'";
        break;
    case 'completed':
        $sql .= " AND status = 'completed'";
        break;
    case 'weather-dependent':
        $sql .= " AND weather_dependent = 1 AND status != 'completed'";
        break;
    case 'today':
        $sql .= " AND task_date = CURDATE()";
        break;
    case 'upcoming':
        $sql .= " AND task_date >= CURDATE() AND status != 'completed'";
        break;
    case 'overdue':
        $sql .= " AND task_date < CURDATE() AND status = 'pending'";
        break;
}

$sql .= " ORDER BY task_date ASC, priority DESC, created_at DESC LIMIT ?";
$params[] = $limit;

try {
    $tasks = $db->resultSet($sql, $params);
    
    echo json_encode([
        'success' => true,
        'tasks' => $tasks ?: [],
        'count' => count($tasks ?: [])
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
