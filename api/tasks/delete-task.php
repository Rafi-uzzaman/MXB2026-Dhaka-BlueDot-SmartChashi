<?php
// Delete a farm task

$db = new Database();
$user = getCurrentUser();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$taskId = intval($input['task_id'] ?? $_GET['task_id'] ?? 0);

if (!$taskId) {
    echo json_encode(['success' => false, 'message' => 'Task ID is required']);
    exit;
}

// Check if task belongs to user
$existingTask = $db->single("SELECT * FROM farm_tasks WHERE task_id = ? AND user_id = ?", [$taskId, $user['user_id']]);

if (!$existingTask) {
    echo json_encode(['success' => false, 'message' => 'Task not found']);
    exit;
}

try {
    $db->query("DELETE FROM farm_tasks WHERE task_id = ? AND user_id = ?")
       ->bind(1, $taskId)
       ->bind(2, $user['user_id'])
       ->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Task deleted successfully'
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
