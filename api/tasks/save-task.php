<?php
// Save a new farm task

$db = new Database();
$user = getCurrentUser();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$taskName = trim($input['task_name'] ?? '');
$taskType = $input['task_type'] ?? 'other';
$taskDate = $input['task_date'] ?? null;
$taskTime = !empty($input['task_time']) ? $input['task_time'] : null;
$crop = trim($input['crop'] ?? '');
$notes = trim($input['notes'] ?? '');
$weatherDependent = isset($input['weather_dependent']) ? ($input['weather_dependent'] ? 1 : 0) : 1;
$priority = $input['priority'] ?? 'medium';

// Validation
if (empty($taskName)) {
    echo json_encode(['success' => false, 'message' => 'Task name is required']);
    exit;
}

if (empty($taskDate)) {
    echo json_encode(['success' => false, 'message' => 'Task date is required']);
    exit;
}

// Validate task type
$validTypes = ['planting', 'irrigation', 'fertilizer', 'pesticide', 'harvest', 'maintenance', 'other'];
if (!in_array($taskType, $validTypes)) {
    $taskType = 'other';
}

// Validate priority
$validPriorities = ['low', 'medium', 'high'];
if (!in_array($priority, $validPriorities)) {
    $priority = 'medium';
}

try {
    $sql = "INSERT INTO farm_tasks (user_id, task_name, task_type, task_date, task_time, crop, notes, weather_dependent, priority) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $db->query($sql)
       ->bind(1, $user['user_id'])
       ->bind(2, $taskName)
       ->bind(3, $taskType)
       ->bind(4, $taskDate)
       ->bind(5, $taskTime)
       ->bind(6, $crop ?: null)
       ->bind(7, $notes ?: null)
       ->bind(8, $weatherDependent)
       ->bind(9, $priority)
       ->execute();
    
    $taskId = $db->lastInsertId();
    
    // Fetch the created task
    $task = $db->single("SELECT * FROM farm_tasks WHERE task_id = ?", [$taskId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Task created successfully',
        'task' => $task
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
