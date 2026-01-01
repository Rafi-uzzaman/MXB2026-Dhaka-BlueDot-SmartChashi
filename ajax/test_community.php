<?php
// Test the community AJAX endpoint
require_once __DIR__ . '/../config/config.php';

echo "Testing Community AJAX Endpoint\n";
echo "================================\n\n";

// Check if user is logged in
if (!isLoggedIn()) {
    echo "❌ User not logged in. Please login first.\n";
    exit;
}

echo "✓ User is logged in\n";

// Test database connection
try {
    $db = new Database();
    echo "✓ Database connected\n";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit;
}

// Check if community_posts table exists
try {
    $result = $db->single("SHOW TABLES LIKE 'community_posts'");
    if ($result) {
        echo "✓ community_posts table exists\n";
        
        // Count posts
        $count = $db->single("SELECT COUNT(*) as count FROM community_posts")['count'];
        echo "  - Total posts: $count\n";
    } else {
        echo "❌ community_posts table does not exist\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking table: " . $e->getMessage() . "\n";
}

// Check other tables
$tables = ['post_likes', 'post_comments', 'users'];
foreach ($tables as $table) {
    try {
        $result = $db->single("SHOW TABLES LIKE '$table'");
        if ($result) {
            echo "✓ $table table exists\n";
        } else {
            echo "⚠ $table table does not exist\n";
        }
    } catch (Exception $e) {
        echo "❌ Error checking $table: " . $e->getMessage() . "\n";
    }
}

echo "\n";
echo "Test AJAX Endpoints:\n";
echo "====================\n";

// Test get_community_stats
try {
    $_GET['action'] = 'get_community_stats';
    ob_start();
    include __DIR__ . '/community.php';
    $output = ob_get_clean();
    
    $data = json_decode($output, true);
    if ($data && isset($data['success'])) {
        echo "✓ get_community_stats works\n";
        echo "  Stats: " . json_encode($data['stats']) . "\n";
    } else {
        echo "❌ get_community_stats failed\n";
        echo "  Response: $output\n";
    }
} catch (Exception $e) {
    echo "❌ get_community_stats error: " . $e->getMessage() . "\n";
}

echo "\nAll tests completed!\n";
