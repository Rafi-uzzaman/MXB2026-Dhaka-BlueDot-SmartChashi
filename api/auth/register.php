<?php
$_POST = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$firstName = trim($_POST['firstName'] ?? '');
$lastName = trim($_POST['lastName'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'farmer';

// Validation
if (!$firstName) {
    echo json_encode(['success' => false, 'message' => 'First name is required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
    exit;
}

$db = new Database();

// Check if email already exists
$existing = $db->single('SELECT user_id FROM users WHERE email = ?', [$email]);
if ($existing) {
    echo json_encode(['success' => false, 'message' => 'Email already registered']);
    exit;
}

// Check if phone already exists
$existing_phone = $db->single('SELECT user_id FROM users WHERE phone = ?', [$phone]);
if ($existing_phone) {
    echo json_encode(['success' => false, 'message' => 'Phone number already registered']);
    exit;
}

// Create user
$password_hash = hashPassword($password);
$query = "INSERT INTO users (email, phone, password_hash, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?)";
$db->query($query);
$db->bind(1, $email);
$db->bind(2, $phone);
$db->bind(3, $password_hash);
$db->bind(4, $firstName);
$db->bind(5, $lastName ?? '');
$db->bind(6, $role);

if (!$db->execute()) {
    echo json_encode(['success' => false, 'message' => 'Registration failed']);
    exit;
}

$user_id = $db->lastInsertId();

// Create farmer profile if role is farmer
if ($role === 'farmer') {
    $db->query("INSERT INTO farmer_profiles (user_id) VALUES (?)");
    $db->bind(1, $user_id);
    $db->execute();
}

echo json_encode(['success' => true, 'message' => 'Registration successful']);
?>
