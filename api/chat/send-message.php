<?php
$_POST = json_decode(file_get_contents('php://input'), true) ?? $_POST;

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$message = $_POST['message'] ?? '';
$language = $_POST['language'] ?? 'english';

if (!$message) {
    echo json_encode(['success' => false, 'message' => 'Message is required']);
    exit;
}

// Mock AI chat response
$mockResponses = [
    'For rice cultivation, ensure proper water management. Keep 5cm standing water during growing season. Apply nitrogen fertilizer in three splits.',
    'To prevent crop diseases, maintain proper field drainage, use disease-resistant varieties, and practice crop rotation.',
    'Tomato plants need 6-8 hours of sunlight daily. Water regularly but avoid waterlogging. Support plants with stakes for better yield.',
    'For vegetable farming, prepare soil with compost, maintain pH 6-7, and use organic fertilizers for better results.',
    'Control pests naturally using neem spray, companion planting with marigold, and removing affected plant parts manually.',
];

$reply = $mockResponses[array_rand($mockResponses)];

// Save chat log
$db = new Database();
$db->query("INSERT INTO ai_chat_logs (user_id, user_message, ai_response, language) VALUES (?, ?, ?, ?)");
$db->bind(1, $_SESSION['user_id']);
$db->bind(2, $message);
$db->bind(3, $reply);
$db->bind(4, $language);
$db->execute();

echo json_encode([
    'success' => true,
    'reply' => $reply
]);
?>
