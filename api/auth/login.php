<?php
$_POST = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

$db = new Database();

// Find user by email
$user = $db->single('SELECT * FROM users WHERE email = ?', [$email]);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    exit;
}

// Verify password
if (!verifyPassword($password, $user['password_hash'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    exit;
}

// Set session
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['user_role'] = $user['role'];

echo json_encode(['success' => true, 'message' => 'Login successful']);
?>
