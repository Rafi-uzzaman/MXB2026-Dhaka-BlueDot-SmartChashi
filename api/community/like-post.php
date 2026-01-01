<?php
$_POST = json_decode(file_get_contents('php://input'), true) ?? $_POST;

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$postId = $_POST['postId'] ?? 0;

$db = new Database();
$db->query("UPDATE community_posts SET likes = likes + 1 WHERE post_id = ? AND user_id != ?");
$db->bind(1, $postId);
$db->bind(2, $_SESSION['user_id']);
$db->execute();

$post = $db->single("SELECT likes FROM community_posts WHERE post_id = ?", [$postId]);

echo json_encode([
    'success' => true,
    'likes' => $post['likes'] ?? 0
]);
?>
