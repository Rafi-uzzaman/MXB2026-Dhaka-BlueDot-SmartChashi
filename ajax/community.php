<?php
// IMPORTANT: Output buffering and immediate response setup
// This ensures we always return JSON, even on fatal errors

// First, set headers immediately
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Start output buffering
ob_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Function to send JSON response and exit
function sendJsonResponse($data) {
    ob_end_clean();
    echo json_encode($data);
    exit;
}

// Global error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    sendJsonResponse([
        'success' => false, 
        'message' => 'PHP Error: ' . $errstr,
        'debug' => basename($errfile) . ':' . $errline
    ]);
});

// Exception handler
set_exception_handler(function($e) {
    sendJsonResponse([
        'success' => false, 
        'message' => 'Exception: ' . $e->getMessage(),
        'debug' => basename($e->getFile()) . ':' . $e->getLine()
    ]);
});

// Shutdown handler for fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Clear any partial output
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false, 
            'message' => 'Fatal Error: ' . $error['message'],
            'debug' => basename($error['file']) . ':' . $error['line']
        ]);
    }
});

// Try to include config
$configFile = __DIR__ . '/../config/config.php';
if (!file_exists($configFile)) {
    sendJsonResponse(['success' => false, 'message' => 'Config file not found']);
}

try {
    require_once $configFile;
} catch (Exception $e) {
    sendJsonResponse(['success' => false, 'message' => 'Config error: ' . $e->getMessage()]);
} catch (Error $e) {
    sendJsonResponse(['success' => false, 'message' => 'Config fatal: ' . $e->getMessage()]);
}

// Clear output buffer
ob_end_clean();

// Debug mode - add ?debug=1 to see diagnostic info
if (isset($_GET['debug']) && $_GET['debug'] == '1') {
    sendJsonResponse([
        'success' => true,
        'debug_info' => [
            'php_version' => PHP_VERSION,
            'session_status' => session_status(),
            'session_id' => session_id(),
            'session_name' => session_name(),
            'session_data' => isset($_SESSION) ? array_keys($_SESSION) : 'not set',
            'user_id' => $_SESSION['user_id'] ?? 'not set',
            'isLoggedIn_exists' => function_exists('isLoggedIn'),
            'isLoggedIn_result' => function_exists('isLoggedIn') ? isLoggedIn() : 'function not found',
            'cookies_received' => array_keys($_COOKIE),
            'base_url' => $base_url ?? 'not set',
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'not set',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'not set'
        ]
    ]);
}

// Check login status
if (!function_exists('isLoggedIn')) {
    sendJsonResponse(['success' => false, 'message' => 'isLoggedIn function not found - config issue']);
}

if (!isLoggedIn()) {
    sendJsonResponse([
        'success' => false, 
        'message' => 'Please login first',
        'debug_hint' => 'Session ID: ' . (session_id() ?: 'none') . ', Cookies: ' . implode(',', array_keys($_COOKIE))
    ]);
}

$db = new Database();
$userId = $_SESSION['user_id'];
$user = getCurrentUser();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'create_post':
        createPost($db, $userId);
        break;
    
    case 'get_posts':
        getPosts($db, $userId);
        break;
    
    case 'get_post':
        getPost($db, $userId);
        break;
    
    case 'like_post':
        likePost($db, $userId);
        break;
    
    case 'unlike_post':
        unlikePost($db, $userId);
        break;
    
    case 'delete_post':
        deletePost($db, $userId);
        break;
    
    case 'add_comment':
        addComment($db, $userId);
        break;
    
    case 'get_comments':
        getComments($db);
        break;
    
    case 'delete_comment':
        deleteComment($db, $userId);
        break;
    
    case 'like_comment':
        likeComment($db, $userId);
        break;
    
    case 'get_nearby_farmers':
        getNearbyFarmers($db, $userId);
        break;
    
    case 'get_officers':
        getOfficers($db);
        break;
    
    case 'get_community_stats':
        getCommunityStats($db);
        break;
    
    case 'get_trending_topics':
        getTrendingTopics($db);
        break;
    
    case 'send_message':
        sendMessage($db, $userId);
        break;
    
    case 'report_post':
        reportPost($db, $userId);
        break;
    
    case 'search_posts':
        searchPosts($db);
        break;
    
    case 'mark_helpful':
        markHelpful($db, $userId);
        break;
    
    case 'mark_unhelpful':
        markUnhelpful($db, $userId);
        break;
    
    case 'bookmark_post':
        bookmarkPost($db, $userId);
        break;
    
    case 'get_bookmarks':
        getBookmarks($db, $userId);
        break;
    
    case 'edit_post':
        editPost($db, $userId);
        break;
    
    case 'edit_comment':
        editComment($db, $userId);
        break;
    
    case 'pin_post':
        pinPost($db, $userId);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
}

// Create a new post
function createPost($db, $userId) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category = $_POST['category'] ?? 'General Discussion';
    $postType = $_POST['postType'] ?? 'discussion';
    $tags = trim($_POST['tags'] ?? '');
    
    if (empty($title) || empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Title and content are required']);
        return;
    }
    
    // Handle image upload
    $imageUrl = null;
    if (isset($_FILES['postPhoto']) && $_FILES['postPhoto']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['postPhoto']['type'], $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Invalid image type. Only JPG, PNG, GIF and WebP allowed.']);
            return;
        }
        
        if ($_FILES['postPhoto']['size'] > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'Image size must be less than 5MB']);
            return;
        }
        
        $uploadDir = __DIR__ . '/../public/uploads/community/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $extension = pathinfo($_FILES['postPhoto']['name'], PATHINFO_EXTENSION);
        $filename = 'post_' . $userId . '_' . time() . '.' . $extension;
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['postPhoto']['tmp_name'], $targetPath)) {
            $imageUrl = 'public/uploads/community/' . $filename;
        }
    }
    
    try {
        $db->query("INSERT INTO community_posts (user_id, title, content, category, post_type, image_url, tags, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())")
           ->bind(1, $userId)
           ->bind(2, $title)
           ->bind(3, $content)
           ->bind(4, $category)
           ->bind(5, $postType)
           ->bind(6, $imageUrl)
           ->bind(7, $tags ?: null)
           ->execute();
        
        $postId = $db->lastInsertId();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Post created successfully',
            'postId' => $postId
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to create post: ' . $e->getMessage()]);
    }
}

// Get posts with pagination and filters
function getPosts($db, $userId) {
    $page = intval($_GET['page'] ?? 1);
    $limit = intval($_GET['limit'] ?? 10);
    $category = $_GET['category'] ?? '';
    $postType = $_GET['postType'] ?? '';
    $sortBy = $_GET['sortBy'] ?? 'newest';
    $search = trim($_GET['search'] ?? '');
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT cp.*, u.first_name, u.last_name, u.profile_img_url,
            (SELECT COUNT(*) FROM post_likes WHERE post_id = cp.post_id) as like_count,
            (SELECT COUNT(*) FROM post_comments WHERE post_id = cp.post_id) as comment_count,
            (SELECT COUNT(*) FROM post_likes WHERE post_id = cp.post_id AND user_id = ?) as user_liked,
            COALESCE((SELECT COUNT(*) FROM post_helpfulness WHERE post_id = cp.post_id AND is_helpful = TRUE), 0) as helpful_count,
            COALESCE((SELECT COUNT(*) FROM post_helpfulness WHERE post_id = cp.post_id AND is_helpful = FALSE), 0) as unhelpful_count,
            (SELECT is_helpful FROM post_helpfulness WHERE post_id = cp.post_id AND user_id = ? LIMIT 1) as user_helpfulness_vote,
            COALESCE((SELECT COUNT(*) FROM post_bookmarks WHERE post_id = cp.post_id AND user_id = ?), 0) as user_bookmarked
            FROM community_posts cp 
            LEFT JOIN users u ON cp.user_id = u.user_id
            WHERE cp.is_approved = 1";
    $params = [$userId, $userId, $userId];
    
    // Search filter
    if (!empty($search)) {
        $sql .= " AND (cp.title LIKE ? OR cp.content LIKE ? OR cp.category LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($category) && $category !== 'all') {
        $sql .= " AND cp.category = ?";
        $params[] = $category;
    }
    
    if (!empty($postType) && $postType !== 'all') {
        $sql .= " AND cp.post_type = ?";
        $params[] = $postType;
    }
    
    // Sorting
    switch ($sortBy) {
        case 'popular':
            $sql .= " ORDER BY like_count DESC, cp.views DESC";
            break;
        case 'most_discussed':
            $sql .= " ORDER BY comment_count DESC";
            break;
        case 'oldest':
            $sql .= " ORDER BY cp.created_at ASC";
            break;
        default:
            $sql .= " ORDER BY cp.is_pinned DESC, cp.created_at DESC";
    }
    
    // Build count query with same WHERE conditions
    $countSql = "SELECT COUNT(*) as total FROM community_posts cp WHERE cp.is_approved = 1";
    $countParams = [];
    
    // Add same filters as main query
    if (!empty($search)) {
        $countSql .= " AND (cp.title LIKE ? OR cp.content LIKE ? OR cp.category LIKE ?)";
        $searchTerm = "%$search%";
        $countParams[] = $searchTerm;
        $countParams[] = $searchTerm;
        $countParams[] = $searchTerm;
    }
    if (!empty($category) && $category !== 'all') {
        $countSql .= " AND cp.category = ?";
        $countParams[] = $category;
    }
    if (!empty($postType) && $postType !== 'all') {
        $countSql .= " AND cp.post_type = ?";
        $countParams[] = $postType;
    }
    
    $sql .= " LIMIT $limit OFFSET $offset";
    
    try {
        $posts = $db->resultSet($sql, $params);
        $total = $db->single($countSql, $countParams)['total'] ?? 0;
        $totalPages = ceil($total / $limit);
        
        echo json_encode([
            'success' => true,
            'posts' => $posts,
            'total' => $total,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'perPage' => $limit,
                'totalResults' => $total,
                'from' => $total > 0 ? (($page - 1) * $limit) + 1 : 0,
                'to' => min($page * $limit, $total),
                'hasNext' => $page < $totalPages,
                'hasPrev' => $page > 1
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get posts: ' . $e->getMessage()]);
    }
}

// Get single post with details
function getPost($db, $userId) {
    $postId = $_GET['postId'] ?? '';
    
    if (empty($postId)) {
        echo json_encode(['success' => false, 'message' => 'Post ID is required']);
        return;
    }
    
    try {
        // Increment view count
        $db->query("UPDATE community_posts SET views = views + 1 WHERE post_id = ?")
           ->bind(1, $postId)
           ->execute();
        
        $post = $db->single("SELECT cp.*, u.first_name, u.last_name, u.profile_img_url, u.role,
            (SELECT COUNT(*) FROM post_likes WHERE post_id = cp.post_id) as like_count,
            (SELECT COUNT(*) FROM post_comments WHERE post_id = cp.post_id) as comment_count,
            (SELECT COUNT(*) FROM post_likes WHERE post_id = cp.post_id AND user_id = ?) as user_liked
            FROM community_posts cp 
            LEFT JOIN users u ON cp.user_id = u.user_id
            WHERE cp.post_id = ?", [$userId, $postId]);
        
        if (!$post) {
            echo json_encode(['success' => false, 'message' => 'Post not found']);
            return;
        }
        
        // Get comments
        $comments = $db->resultSet("SELECT pc.*, u.first_name, u.last_name, u.profile_img_url,
            (SELECT COUNT(*) FROM comment_likes WHERE comment_id = pc.comment_id) as like_count
            FROM post_comments pc 
            LEFT JOIN users u ON pc.user_id = u.user_id
            WHERE pc.post_id = ?
            ORDER BY pc.created_at ASC", [$postId]);
        
        echo json_encode([
            'success' => true,
            'post' => $post,
            'comments' => $comments
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get post: ' . $e->getMessage()]);
    }
}

// Like a post
function likePost($db, $userId) {
    $postId = $_POST['postId'] ?? '';
    
    if (empty($postId)) {
        echo json_encode(['success' => false, 'message' => 'Post ID is required']);
        return;
    }
    
    try {
        // Check if already liked
        $existing = $db->single("SELECT * FROM post_likes WHERE post_id = ? AND user_id = ?", [$postId, $userId]);
        
        if ($existing) {
            // Unlike
            $db->query("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?")
               ->bind(1, $postId)
               ->bind(2, $userId)
               ->execute();
            
            $db->query("UPDATE community_posts SET likes = GREATEST(likes - 1, 0) WHERE post_id = ?")
               ->bind(1, $postId)
               ->execute();
            
            $liked = false;
        } else {
            // Like
            $db->query("INSERT INTO post_likes (post_id, user_id, created_at) VALUES (?, ?, NOW())")
               ->bind(1, $postId)
               ->bind(2, $userId)
               ->execute();
            
            $db->query("UPDATE community_posts SET likes = likes + 1 WHERE post_id = ?")
               ->bind(1, $postId)
               ->execute();
            
            $liked = true;
        }
        
        $likeCount = $db->single("SELECT COUNT(*) as count FROM post_likes WHERE post_id = ?", [$postId])['count'] ?? 0;
        
        echo json_encode([
            'success' => true,
            'liked' => $liked,
            'likeCount' => $likeCount
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to like post: ' . $e->getMessage()]);
    }
}

// Unlike a post
function unlikePost($db, $userId) {
    $postId = $_POST['postId'] ?? '';
    
    if (empty($postId)) {
        echo json_encode(['success' => false, 'message' => 'Post ID is required']);
        return;
    }
    
    try {
        $db->query("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?")
           ->bind(1, $postId)
           ->bind(2, $userId)
           ->execute();
        
        $db->query("UPDATE community_posts SET likes = GREATEST(likes - 1, 0) WHERE post_id = ?")
           ->bind(1, $postId)
           ->execute();
        
        $likeCount = $db->single("SELECT COUNT(*) as count FROM post_likes WHERE post_id = ?", [$postId])['count'] ?? 0;
        
        echo json_encode([
            'success' => true,
            'likeCount' => $likeCount
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to unlike post: ' . $e->getMessage()]);
    }
}

// Delete a post
function deletePost($db, $userId) {
    $postId = $_POST['postId'] ?? '';
    
    if (empty($postId)) {
        echo json_encode(['success' => false, 'message' => 'Post ID is required']);
        return;
    }
    
    // Check ownership or admin
    $user = getCurrentUser();
    $post = $db->single("SELECT * FROM community_posts WHERE post_id = ?", [$postId]);
    
    if (!$post) {
        echo json_encode(['success' => false, 'message' => 'Post not found']);
        return;
    }
    
    if ($post['user_id'] != $userId && $user['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    try {
        // Delete related data
        $db->query("DELETE FROM post_likes WHERE post_id = ?")->bind(1, $postId)->execute();
        $db->query("DELETE FROM comment_likes WHERE comment_id IN (SELECT comment_id FROM post_comments WHERE post_id = ?)")->bind(1, $postId)->execute();
        $db->query("DELETE FROM post_comments WHERE post_id = ?")->bind(1, $postId)->execute();
        
        // Delete post image if exists
        if (!empty($post['image_url']) && file_exists(__DIR__ . '/../' . $post['image_url'])) {
            unlink(__DIR__ . '/../' . $post['image_url']);
        }
        
        $db->query("DELETE FROM community_posts WHERE post_id = ?")->bind(1, $postId)->execute();
        
        echo json_encode(['success' => true, 'message' => 'Post deleted successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete post: ' . $e->getMessage()]);
    }
}

// Add a comment
function addComment($db, $userId) {
    $postId = $_POST['postId'] ?? '';
    $content = trim($_POST['content'] ?? '');
    $parentId = isset($_POST['parentId']) && !empty($_POST['parentId']) ? (int)$_POST['parentId'] : null;
    
    if (empty($postId) || empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Post ID and content are required']);
        return;
    }
    
    // Check if post exists
    $post = $db->single("SELECT post_id, user_id FROM community_posts WHERE post_id = ?", [$postId]);
    if (!$post) {
        echo json_encode(['success' => false, 'message' => 'Post not found']);
        return;
    }
    
    // If this is a reply, verify the parent comment exists
    if ($parentId !== null) {
        $parentComment = $db->single("SELECT comment_id FROM post_comments WHERE comment_id = ? AND post_id = ?", [$parentId, $postId]);
        if (!$parentComment) {
            echo json_encode(['success' => false, 'message' => 'Parent comment not found']);
            return;
        }
    }
    
    try {
        $db->query("INSERT INTO post_comments (post_id, user_id, content, parent_comment_id, created_at) VALUES (?, ?, ?, ?, NOW())")
           ->bind(1, $postId)
           ->bind(2, $userId)
           ->bind(3, $content)
           ->bind(4, $parentId)
           ->execute();
        
        $commentId = $db->lastInsertId();
        
        // Notify post owner if commenter is different
        if ($post['user_id'] != $userId) {
            $db->query("INSERT INTO alerts (user_id, alert_type, title, message, priority, category, created_by, created_at) 
                VALUES (?, 'community', 'New Comment', 'Someone commented on your post.', 'low', 'Community', ?, NOW())")
               ->bind(1, $post['user_id'])
               ->bind(2, $userId)
               ->execute();
        }
        
        // Get the created comment with user info
        $comment = $db->single("SELECT pc.*, u.first_name, u.last_name, u.profile_img_url 
            FROM post_comments pc 
            LEFT JOIN users u ON pc.user_id = u.user_id
            WHERE pc.comment_id = ?", [$commentId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Comment added successfully',
            'comment' => $comment
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to add comment: ' . $e->getMessage()]);
    }
}

// Get comments for a post
function getComments($db) {
    $postId = $_GET['postId'] ?? '';
    
    if (empty($postId)) {
        echo json_encode(['success' => false, 'message' => 'Post ID is required']);
        return;
    }
    
    try {
        $comments = $db->resultSet("SELECT pc.*, u.first_name, u.last_name, u.profile_img_url,
            (SELECT COUNT(*) FROM comment_likes WHERE comment_id = pc.comment_id) as like_count
            FROM post_comments pc 
            LEFT JOIN users u ON pc.user_id = u.user_id
            WHERE pc.post_id = ?
            ORDER BY pc.created_at ASC", [$postId]);
        
        echo json_encode(['success' => true, 'comments' => $comments]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get comments: ' . $e->getMessage()]);
    }
}

// Delete a comment
function deleteComment($db, $userId) {
    $commentId = $_POST['commentId'] ?? '';
    
    if (empty($commentId)) {
        echo json_encode(['success' => false, 'message' => 'Comment ID is required']);
        return;
    }
    
    $user = getCurrentUser();
    $comment = $db->single("SELECT * FROM post_comments WHERE comment_id = ?", [$commentId]);
    
    if (!$comment) {
        echo json_encode(['success' => false, 'message' => 'Comment not found']);
        return;
    }
    
    if ($comment['user_id'] != $userId && $user['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    try {
        // Delete all related data in correct order
        // 1. Delete likes on child comments
        $db->query("DELETE FROM comment_likes WHERE comment_id IN (SELECT comment_id FROM post_comments WHERE parent_comment_id = ?)")
           ->bind(1, $commentId)
           ->execute();
        
        // 2. Delete child comments (replies)
        $db->query("DELETE FROM post_comments WHERE parent_comment_id = ?")
           ->bind(1, $commentId)
           ->execute();
        
        // 3. Delete likes on the comment itself
        $db->query("DELETE FROM comment_likes WHERE comment_id = ?")
           ->bind(1, $commentId)
           ->execute();
        
        // 4. Delete the comment
        $db->query("DELETE FROM post_comments WHERE comment_id = ?")
           ->bind(1, $commentId)
           ->execute();
        
        echo json_encode(['success' => true, 'message' => 'Comment deleted successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete comment: ' . $e->getMessage()]);
    }
}

// Like a comment
function likeComment($db, $userId) {
    $commentId = $_POST['commentId'] ?? '';
    
    if (empty($commentId)) {
        echo json_encode(['success' => false, 'message' => 'Comment ID is required']);
        return;
    }
    
    try {
        $existing = $db->single("SELECT * FROM comment_likes WHERE comment_id = ? AND user_id = ?", [$commentId, $userId]);
        
        if ($existing) {
            $db->query("DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?")
               ->bind(1, $commentId)
               ->bind(2, $userId)
               ->execute();
            $liked = false;
        } else {
            $db->query("INSERT INTO comment_likes (comment_id, user_id, created_at) VALUES (?, ?, NOW())")
               ->bind(1, $commentId)
               ->bind(2, $userId)
               ->execute();
            $liked = true;
        }
        
        $likeCount = $db->single("SELECT COUNT(*) as count FROM comment_likes WHERE comment_id = ?", [$commentId])['count'] ?? 0;
        
        echo json_encode([
            'success' => true,
            'liked' => $liked,
            'likeCount' => $likeCount
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to like comment: ' . $e->getMessage()]);
    }
}

// Get nearby farmers
function getNearbyFarmers($db, $userId) {
    $user = getCurrentUser();
    $search = $_GET['search'] ?? '';
    $distance = intval($_GET['distance'] ?? 50);
    
    if (empty($user['latitude']) || empty($user['longitude'])) {
        echo json_encode(['success' => false, 'message' => 'Location not set']);
        return;
    }
    
    try {
        $sql = "SELECT u.user_id, u.first_name, u.last_name, u.phone, u.profile_img_url,
               fp.region, fp.district, fp.primary_crops, fp.farm_size,
               ROUND(6371 * acos(cos(radians(?)) * cos(radians(u.latitude)) * cos(radians(u.longitude) - radians(?)) + sin(radians(?)) * sin(radians(u.latitude))), 2) as distance
               FROM users u 
               LEFT JOIN farmer_profiles fp ON u.user_id = fp.user_id
               WHERE u.role = 'farmer' AND u.user_id != ? AND u.latitude IS NOT NULL AND u.longitude IS NOT NULL";
        
        $params = [$user['latitude'], $user['longitude'], $user['latitude'], $userId];
        
        if (!empty($search)) {
            $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR fp.region LIKE ? OR fp.district LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql .= " HAVING distance <= ? ORDER BY distance ASC LIMIT 100";
        $params[] = $distance;
        
        $farmers = $db->resultSet($sql, $params);
        
        echo json_encode(['success' => true, 'farmers' => $farmers]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get farmers: ' . $e->getMessage()]);
    }
}

// Get officers
function getOfficers($db) {
    $search = $_GET['search'] ?? '';
    $region = $_GET['region'] ?? '';
    
    try {
        $sql = "SELECT u.user_id, u.first_name, u.last_name, u.phone, u.email, u.profile_img_url,
               op.region, op.district, op.expertise_area, op.department
               FROM users u 
               LEFT JOIN officer_profiles op ON u.user_id = op.user_id
               WHERE u.role = 'officer'";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR op.region LIKE ? OR op.expertise_area LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($region)) {
            $sql .= " AND op.region = ?";
            $params[] = $region;
        }
        
        $sql .= " ORDER BY u.first_name ASC LIMIT 100";
        
        $officers = $db->resultSet($sql, $params);
        
        echo json_encode(['success' => true, 'officers' => $officers]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get officers: ' . $e->getMessage()]);
    }
}

// Get community stats
function getCommunityStats($db) {
    try {
        $stats = [];
        
        // Use try-catch for each query in case tables don't exist
        try {
            $stats['total_farmers'] = $db->single("SELECT COUNT(*) as count FROM users WHERE role = 'farmer'", [])['count'] ?? 0;
        } catch (Exception $e) {
            $stats['total_farmers'] = 0;
        }
        
        try {
            $stats['total_posts'] = $db->single("SELECT COUNT(*) as count FROM community_posts", [])['count'] ?? 0;
        } catch (Exception $e) {
            $stats['total_posts'] = 0;
        }
        
        try {
            $stats['posts_today'] = $db->single("SELECT COUNT(*) as count FROM community_posts WHERE DATE(created_at) = CURDATE()", [])['count'] ?? 0;
        } catch (Exception $e) {
            $stats['posts_today'] = 0;
        }
        
        try {
            $stats['active_discussions'] = $db->single("SELECT COUNT(*) as count FROM community_posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)", [])['count'] ?? 0;
        } catch (Exception $e) {
            $stats['active_discussions'] = 0;
        }
        
        try {
            $stats['total_comments'] = $db->single("SELECT COUNT(*) as count FROM post_comments", [])['count'] ?? 0;
        } catch (Exception $e) {
            $stats['total_comments'] = 0;
        }
        
        try {
            $stats['total_officers'] = $db->single("SELECT COUNT(*) as count FROM users WHERE role = 'officer'", [])['count'] ?? 0;
        } catch (Exception $e) {
            $stats['total_officers'] = 0;
        }
        
        echo json_encode(['success' => true, 'stats' => $stats]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get stats: ' . $e->getMessage()]);
    }
}

// Get trending topics
function getTrendingTopics($db) {
    try {
        // Get top categories by post count in last 30 days
        $topics = [];
        
        try {
            $topics = $db->resultSet("SELECT category as name, COUNT(*) as count 
                FROM community_posts 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND category IS NOT NULL
                GROUP BY category 
                ORDER BY count DESC 
                LIMIT 5", []);
        } catch (Exception $e) {
            // If table doesn't exist or error, return empty array
            $topics = [];
        }
        
        echo json_encode([
            'success' => true,
            'topics' => $topics
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get trending: ' . $e->getMessage()]);
    }
}

// Send message to a user
function sendMessage($db, $userId) {
    $receiverId = $_POST['receiverId'] ?? '';
    $message = trim($_POST['message'] ?? '');
    
    if (empty($receiverId) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Receiver and message are required']);
        return;
    }
    
    try {
        $db->query("INSERT INTO chat_messages (sender_id, receiver_id, message, created_at) VALUES (?, ?, ?, NOW())")
           ->bind(1, $userId)
           ->bind(2, $receiverId)
           ->bind(3, $message)
           ->execute();
        
        echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to send message: ' . $e->getMessage()]);
    }
}

// Report a post
function reportPost($db, $userId) {
    $postId = $_POST['postId'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    
    if (empty($postId) || empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'Post ID and reason are required']);
        return;
    }
    
    try {
        // Check if table exists, if not create it
        $db->query("CREATE TABLE IF NOT EXISTS post_reports (
            report_id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            reason TEXT NOT NULL,
            status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )")->execute();
        
        $db->query("INSERT INTO post_reports (post_id, user_id, reason, created_at) VALUES (?, ?, ?, NOW())")
           ->bind(1, $postId)
           ->bind(2, $userId)
           ->bind(3, $reason)
           ->execute();
        
        echo json_encode(['success' => true, 'message' => 'Report submitted successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to report post: ' . $e->getMessage()]);
    }
}

// Search posts
function searchPosts($db) {
    $query = trim($_GET['query'] ?? '');
    $page = intval($_GET['page'] ?? 1);
    $limit = intval($_GET['limit'] ?? 10);
    $offset = ($page - 1) * $limit;
    
    if (empty($query)) {
        echo json_encode(['success' => false, 'message' => 'Search query is required']);
        return;
    }
    
    try {
        $searchTerm = "%$query%";
        
        $posts = $db->resultSet("SELECT cp.*, u.first_name, u.last_name, u.profile_img_url,
            (SELECT COUNT(*) FROM post_likes WHERE post_id = cp.post_id) as like_count,
            (SELECT COUNT(*) FROM post_comments WHERE post_id = cp.post_id) as comment_count
            FROM community_posts cp 
            LEFT JOIN users u ON cp.user_id = u.user_id
            WHERE cp.is_approved = 1 AND (cp.title LIKE ? OR cp.content LIKE ? OR cp.category LIKE ?)
            ORDER BY cp.created_at DESC
            LIMIT $limit OFFSET $offset", [$searchTerm, $searchTerm, $searchTerm]);
        
        $total = $db->single("SELECT COUNT(*) as count FROM community_posts 
            WHERE is_approved = 1 AND (title LIKE ? OR content LIKE ? OR category LIKE ?)", 
            [$searchTerm, $searchTerm, $searchTerm])['count'] ?? 0;
        
        echo json_encode([
            'success' => true,
            'posts' => $posts,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => ceil($total / $limit)
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to search posts: ' . $e->getMessage()]);
    }
}
// Mark post as helpful
function markHelpful($db, $userId) {
    $postId = $_POST['postId'] ?? '';
    
    if (empty($postId)) {
        echo json_encode(['success' => false, 'message' => 'Post ID is required']);
        return;
    }
    
    try {
        // Create table if not exists
        $db->query("CREATE TABLE IF NOT EXISTS post_helpfulness (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            is_helpful BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_vote (post_id, user_id)
        )")->execute();
        
        // Check if already voted
        $existing = $db->single("SELECT * FROM post_helpfulness WHERE post_id = ? AND user_id = ?", [$postId, $userId]);
        
        if ($existing) {
            // Update vote
            $db->query("UPDATE post_helpfulness SET is_helpful = TRUE WHERE post_id = ? AND user_id = ?")
               ->bind(1, $postId)
               ->bind(2, $userId)
               ->execute();
        } else {
            // Insert new vote
            $db->query("INSERT INTO post_helpfulness (post_id, user_id, is_helpful, created_at) VALUES (?, ?, TRUE, NOW())")
               ->bind(1, $postId)
               ->bind(2, $userId)
               ->execute();
        }
        
        // Get counts
        $helpfulCount = $db->single("SELECT COUNT(*) as count FROM post_helpfulness WHERE post_id = ? AND is_helpful = TRUE", [$postId])['count'] ?? 0;
        $unhelpfulCount = $db->single("SELECT COUNT(*) as count FROM post_helpfulness WHERE post_id = ? AND is_helpful = FALSE", [$postId])['count'] ?? 0;
        
        echo json_encode([
            'success' => true,
            'helpfulCount' => $helpfulCount,
            'unhelpfulCount' => $unhelpfulCount,
            'userVote' => 'helpful'
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to mark as helpful: ' . $e->getMessage()]);
    }
}

// Mark post as unhelpful
function markUnhelpful($db, $userId) {
    $postId = $_POST['postId'] ?? '';
    
    if (empty($postId)) {
        echo json_encode(['success' => false, 'message' => 'Post ID is required']);
        return;
    }
    
    try {
        // Create table if not exists
        $db->query("CREATE TABLE IF NOT EXISTS post_helpfulness (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            is_helpful BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_vote (post_id, user_id)
        )")->execute();
        
        // Check if already voted
        $existing = $db->single("SELECT * FROM post_helpfulness WHERE post_id = ? AND user_id = ?", [$postId, $userId]);
        
        if ($existing) {
            // Update vote
            $db->query("UPDATE post_helpfulness SET is_helpful = FALSE WHERE post_id = ? AND user_id = ?")
               ->bind(1, $postId)
               ->bind(2, $userId)
               ->execute();
        } else {
            // Insert new vote
            $db->query("INSERT INTO post_helpfulness (post_id, user_id, is_helpful, created_at) VALUES (?, ?, FALSE, NOW())")
               ->bind(1, $postId)
               ->bind(2, $userId)
               ->execute();
        }
        
        // Get counts
        $helpfulCount = $db->single("SELECT COUNT(*) as count FROM post_helpfulness WHERE post_id = ? AND is_helpful = TRUE", [$postId])['count'] ?? 0;
        $unhelpfulCount = $db->single("SELECT COUNT(*) as count FROM post_helpfulness WHERE post_id = ? AND is_helpful = FALSE", [$postId])['count'] ?? 0;
        
        echo json_encode([
            'success' => true,
            'helpfulCount' => $helpfulCount,
            'unhelpfulCount' => $unhelpfulCount,
            'userVote' => 'unhelpful'
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to mark as unhelpful: ' . $e->getMessage()]);
    }
}

// Bookmark a post
function bookmarkPost($db, $userId) {
    $postId = $_POST['postId'] ?? '';
    
    if (empty($postId)) {
        echo json_encode(['success' => false, 'message' => 'Post ID is required']);
        return;
    }
    
    try {
        // Create table if not exists
        $db->query("CREATE TABLE IF NOT EXISTS post_bookmarks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_bookmark (post_id, user_id)
        )")->execute();
        
        // Check if already bookmarked
        $existing = $db->single("SELECT * FROM post_bookmarks WHERE post_id = ? AND user_id = ?", [$postId, $userId]);
        
        if ($existing) {
            // Remove bookmark
            $db->query("DELETE FROM post_bookmarks WHERE post_id = ? AND user_id = ?")
               ->bind(1, $postId)
               ->bind(2, $userId)
               ->execute();
            $bookmarked = false;
        } else {
            // Add bookmark
            $db->query("INSERT INTO post_bookmarks (post_id, user_id, created_at) VALUES (?, ?, NOW())")
               ->bind(1, $postId)
               ->bind(2, $userId)
               ->execute();
            $bookmarked = true;
        }
        
        echo json_encode([
            'success' => true,
            'bookmarked' => $bookmarked,
            'message' => $bookmarked ? 'Post bookmarked' : 'Bookmark removed'
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to bookmark: ' . $e->getMessage()]);
    }
}

// Get user's bookmarked posts
function getBookmarks($db, $userId) {
    try {
        $posts = $db->resultSet("SELECT cp.*, u.first_name, u.last_name, u.profile_img_url,
            (SELECT COUNT(*) FROM post_likes WHERE post_id = cp.post_id) as like_count,
            (SELECT COUNT(*) FROM post_comments WHERE post_id = cp.post_id) as comment_count,
            pb.created_at as bookmarked_at
            FROM post_bookmarks pb
            INNER JOIN community_posts cp ON pb.post_id = cp.post_id
            LEFT JOIN users u ON cp.user_id = u.user_id
            WHERE pb.user_id = ?
            ORDER BY pb.created_at DESC", [$userId]);
        
        echo json_encode(['success' => true, 'bookmarks' => $posts]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get bookmarks: ' . $e->getMessage()]);
    }
}

// Edit a post
function editPost($db, $userId) {
    $postId = $_POST['postId'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category = $_POST['category'] ?? '';
    
    if (empty($postId) || empty($title) || empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Post ID, title, and content are required']);
        return;
    }
    
    // Check ownership
    $post = $db->single("SELECT * FROM community_posts WHERE post_id = ?", [$postId]);
    
    if (!$post) {
        echo json_encode(['success' => false, 'message' => 'Post not found']);
        return;
    }
    
    if ($post['user_id'] != $userId) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    try {
        $db->query("UPDATE community_posts SET title = ?, content = ?, category = ?, updated_at = NOW() WHERE post_id = ?")
           ->bind(1, $title)
           ->bind(2, $content)
           ->bind(3, $category)
           ->bind(4, $postId)
           ->execute();
        
        echo json_encode(['success' => true, 'message' => 'Post updated successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to edit post: ' . $e->getMessage()]);
    }
}

// Edit a comment
function editComment($db, $userId) {
    $commentId = $_POST['commentId'] ?? '';
    $content = trim($_POST['content'] ?? '');
    
    if (empty($commentId) || empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Comment ID and content are required']);
        return;
    }
    
    // Check ownership
    $comment = $db->single("SELECT * FROM post_comments WHERE comment_id = ?", [$commentId]);
    
    if (!$comment) {
        echo json_encode(['success' => false, 'message' => 'Comment not found']);
        return;
    }
    
    if ($comment['user_id'] != $userId) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    try {
        $db->query("UPDATE post_comments SET content = ?, updated_at = NOW() WHERE comment_id = ?")
           ->bind(1, $content)
           ->bind(2, $commentId)
           ->execute();
        
        echo json_encode(['success' => true, 'message' => 'Comment updated successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to edit comment: ' . $e->getMessage()]);
    }
}

// Pin/Unpin post (admin only)
function pinPost($db, $userId) {
    $postId = $_POST['postId'] ?? '';
    
    $user = getCurrentUser();
    if ($user['role'] !== 'admin' && $user['role'] !== 'officer') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    if (empty($postId)) {
        echo json_encode(['success' => false, 'message' => 'Post ID is required']);
        return;
    }
    
    try {
        $post = $db->single("SELECT is_pinned FROM community_posts WHERE post_id = ?", [$postId]);
        
        if (!$post) {
            echo json_encode(['success' => false, 'message' => 'Post not found']);
            return;
        }
        
        $newPinStatus = $post['is_pinned'] ? 0 : 1;
        
        $db->query("UPDATE community_posts SET is_pinned = ? WHERE post_id = ?")
           ->bind(1, $newPinStatus)
           ->bind(2, $postId)
           ->execute();
        
        echo json_encode([
            'success' => true,
            'pinned' => $newPinStatus,
            'message' => $newPinStatus ? 'Post pinned' : 'Post unpinned'
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to pin post: ' . $e->getMessage()]);
    }
}