<?php
$db = new Database();
$crops = $db->resultSet('SELECT * FROM crop_data WHERE farmer_id = ? ORDER BY created_at DESC', [$_SESSION['user_id'] ?? 0]);
echo json_encode(['success' => true, 'data' => $crops]);
?>
