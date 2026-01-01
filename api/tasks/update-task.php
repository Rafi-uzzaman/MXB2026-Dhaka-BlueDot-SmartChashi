<?php
// Update a farm task

$db = new Database();
$user = getCurrentUser();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$taskId = intval($input['task_id'] ?? 0);

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

// Build update query dynamically
$updates = [];
$params = [];

if (isset($input['task_name'])) {
    $updates[] = "task_name = ?";
    $params[] = trim($input['task_name']);
}

if (isset($input['task_type'])) {
    $validTypes = ['planting', 'irrigation', 'fertilizer', 'pesticide', 'harvest', 'maintenance', 'other'];
    $updates[] = "task_type = ?";
    $params[] = in_array($input['task_type'], $validTypes) ? $input['task_type'] : 'other';
}

if (isset($input['task_date'])) {
    $updates[] = "task_date = ?";
    $params[] = $input['task_date'];
}

if (isset($input['task_time'])) {
    $updates[] = "task_time = ?";
    $params[] = !empty($input['task_time']) ? $input['task_time'] : null;
}

if (isset($input['crop'])) {
    $updates[] = "crop = ?";
    $params[] = trim($input['crop']) ?: null;
}

if (isset($input['notes'])) {
    $updates[] = "notes = ?";
    $params[] = trim($input['notes']) ?: null;
}

if (isset($input['weather_dependent'])) {
    $updates[] = "weather_dependent = ?";
    $params[] = $input['weather_dependent'] ? 1 : 0;
}

if (isset($input['priority'])) {
    $validPriorities = ['low', 'medium', 'high'];
    $updates[] = "priority = ?";
    $params[] = in_array($input['priority'], $validPriorities) ? $input['priority'] : 'medium';
}

if (isset($input['status'])) {
    $validStatuses = ['pending', 'in_progress', 'completed', 'cancelled'];
    if (in_array($input['status'], $validStatuses)) {
        $updates[] = "status = ?";
        $params[] = $input['status'];
        
        // Set completed_at timestamp
        if ($input['status'] === 'completed') {
            $updates[] = "completed_at = NOW()";
        } else {
            $updates[] = "completed_at = NULL";
        }
    }
}

if (empty($updates)) {
    echo json_encode(['success' => false, 'message' => 'No fields to update']);
    exit;
}

try {
    $sql = "UPDATE farm_tasks SET " . implode(', ', $updates) . " WHERE task_id = ? AND user_id = ?";
    $params[] = $taskId;
    $params[] = $user['user_id'];
    
    $stmt = $db->query($sql);
    foreach ($params as $i => $value) {
        $stmt->bind($i + 1, $value);
    }
    $stmt->execute();
    
    // Fetch updated task
    $task = $db->single("SELECT * FROM farm_tasks WHERE task_id = ?", [$taskId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Task updated successfully',
        'task' => $task
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
