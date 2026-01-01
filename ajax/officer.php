<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

// Check if user is logged in and is an officer
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$user = getCurrentUser();
if ($user['role'] !== 'officer' && $user['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$db = new Database();
$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'issue_alert':
        issueAlert($db, $userId);
        break;
    
    case 'create_advisory':
        createAdvisory($db, $userId);
        break;
    
    case 'schedule_visit':
        scheduleVisit($db, $userId);
        break;
    
    case 'update_visit':
        updateVisit($db, $userId);
        break;
    
    case 'cancel_visit':
        cancelVisit($db, $userId);
        break;
    
    case 'complete_visit':
        completeVisit($db, $userId);
        break;
    
    case 'get_farmers':
        getFarmers($db);
        break;
    
    case 'get_farmer_details':
        getFarmerDetails($db);
        break;
    
    case 'get_visits':
        getVisits($db, $userId);
        break;
    
    case 'get_alerts':
        getAlerts($db, $userId);
        break;
    
    case 'get_advisories':
        getAdvisories($db, $userId);
        break;
    
    case 'delete_alert':
        deleteAlert($db, $userId);
        break;
    
    case 'delete_advisory':
        deleteAdvisory($db, $userId);
        break;
    
    case 'get_detection_details':
        getDetectionDetails($db);
        break;
    
    case 'get_dashboard_stats':
        getDashboardStats($db, $userId);
        break;
    
    case 'send_message':
        sendMessage($db, $userId);
        break;
    
    case 'get_farmer_stats':
        getFarmerStats($db);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
}

function issueAlert($db, $userId) {
    $alertType = $_POST['alertType'] ?? 'system';
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    $targetRegion = $_POST['targetRegion'] ?? '';
    $targetFarmer = $_POST['targetFarmer'] ?? '';
    $sentVia = $_POST['sentVia'] ?? 'app';
    
    if (empty($title) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Title and message are required']);
        return;
    }
    
    try {
        // If specific farmer is selected
        if (!empty($targetFarmer) && $targetFarmer !== 'all') {
            $db->query("INSERT INTO alerts (user_id, alert_type, title, message, priority, category, sent_via, created_by, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())")
               ->bind(1, $targetFarmer)
               ->bind(2, $alertType)
               ->bind(3, $title)
               ->bind(4, $message)
               ->bind(5, $priority)
               ->bind(6, ucfirst($alertType))
               ->bind(7, $sentVia)
               ->bind(8, $userId)
               ->execute();
            
            echo json_encode(['success' => true, 'message' => 'Alert sent to farmer successfully']);
            return;
        }
        
        // Get farmers based on region filter
        $farmers = [];
        if (!empty($targetRegion) && $targetRegion !== 'all') {
            $farmers = $db->resultSet("SELECT u.user_id FROM users u 
                JOIN farmer_profiles fp ON u.user_id = fp.user_id 
                WHERE u.role = 'farmer' AND fp.region = ?", [$targetRegion]);
        } else {
            $farmers = $db->resultSet("SELECT user_id FROM users WHERE role = 'farmer'", []);
        }
        
        if (empty($farmers)) {
            echo json_encode(['success' => false, 'message' => 'No farmers found for the selected criteria']);
            return;
        }
        
        $alertCount = 0;
        foreach ($farmers as $farmer) {
            $db->query("INSERT INTO alerts (user_id, alert_type, title, message, priority, category, sent_via, created_by, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())")
               ->bind(1, $farmer['user_id'])
               ->bind(2, $alertType)
               ->bind(3, $title)
               ->bind(4, $message)
               ->bind(5, $priority)
               ->bind(6, ucfirst($alertType))
               ->bind(7, $sentVia)
               ->bind(8, $userId)
               ->execute();
            $alertCount++;
        }
        
        echo json_encode(['success' => true, 'message' => "Alert sent to $alertCount farmers successfully"]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to issue alert: ' . $e->getMessage()]);
    }
}

function createAdvisory($db, $userId) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $advisoryType = $_POST['advisoryType'] ?? 'general';
    $targetCrops = trim($_POST['targetCrops'] ?? '');
    $targetRegion = $_POST['targetRegion'] ?? '';
    $priority = $_POST['priority'] ?? 'medium';
    $validFrom = $_POST['validFrom'] ?? null;
    $validTo = $_POST['validTo'] ?? null;
    
    if (empty($title) || empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Title and content are required']);
        return;
    }
    
    try {
        $db->query("INSERT INTO advisories (created_by, title, content, advisory_type, target_crops, target_region, priority, valid_from, valid_to, is_active, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())")
           ->bind(1, $userId)
           ->bind(2, $title)
           ->bind(3, $content)
           ->bind(4, $advisoryType)
           ->bind(5, $targetCrops ?: null)
           ->bind(6, $targetRegion ?: null)
           ->bind(7, $priority)
           ->bind(8, $validFrom ?: null)
           ->bind(9, $validTo ?: null)
           ->execute();
        
        echo json_encode(['success' => true, 'message' => 'Advisory created successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to create advisory: ' . $e->getMessage()]);
    }
}

function scheduleVisit($db, $userId) {
    $farmerId = $_POST['farmerId'] ?? '';
    $visitDate = $_POST['visitDate'] ?? '';
    $visitTime = $_POST['visitTime'] ?? null;
    $purpose = trim($_POST['purpose'] ?? '');
    
    if (empty($farmerId) || empty($visitDate)) {
        echo json_encode(['success' => false, 'message' => 'Farmer and visit date are required']);
        return;
    }
    
    // Check if farmer exists
    $farmer = $db->single("SELECT user_id FROM users WHERE user_id = ? AND role = 'farmer'", [$farmerId]);
    if (!$farmer) {
        echo json_encode(['success' => false, 'message' => 'Invalid farmer selected']);
        return;
    }
    
    try {
        $db->query("INSERT INTO field_visits (officer_id, farmer_id, visit_date, visit_time, purpose, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'scheduled', NOW())")
           ->bind(1, $userId)
           ->bind(2, $farmerId)
           ->bind(3, $visitDate)
           ->bind(4, $visitTime ?: null)
           ->bind(5, $purpose)
           ->execute();
        
        // Create an alert for the farmer
        $db->query("INSERT INTO alerts (user_id, alert_type, title, message, priority, category, created_by, created_at) 
            VALUES (?, 'system', 'Field Visit Scheduled', ?, 'medium', 'Visit', ?, NOW())")
           ->bind(1, $farmerId)
           ->bind(2, "An agricultural officer has scheduled a field visit on " . date('M d, Y', strtotime($visitDate)) . ($visitTime ? " at " . date('h:i A', strtotime($visitTime)) : "") . ".")
           ->bind(3, $userId)
           ->execute();
        
        echo json_encode(['success' => true, 'message' => 'Field visit scheduled successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to schedule visit: ' . $e->getMessage()]);
    }
}

function updateVisit($db, $userId) {
    $visitId = $_POST['visitId'] ?? '';
    $visitDate = $_POST['visitDate'] ?? '';
    $visitTime = $_POST['visitTime'] ?? null;
    $purpose = trim($_POST['purpose'] ?? '');
    
    if (empty($visitId)) {
        echo json_encode(['success' => false, 'message' => 'Visit ID is required']);
        return;
    }
    
    // Verify ownership
    $visit = $db->single("SELECT * FROM field_visits WHERE visit_id = ? AND officer_id = ?", [$visitId, $userId]);
    if (!$visit) {
        echo json_encode(['success' => false, 'message' => 'Visit not found or unauthorized']);
        return;
    }
    
    try {
        $db->query("UPDATE field_visits SET visit_date = ?, visit_time = ?, purpose = ?, updated_at = NOW() WHERE visit_id = ?")
           ->bind(1, $visitDate)
           ->bind(2, $visitTime ?: null)
           ->bind(3, $purpose)
           ->bind(4, $visitId)
           ->execute();
        
        echo json_encode(['success' => true, 'message' => 'Visit updated successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update visit: ' . $e->getMessage()]);
    }
}

function cancelVisit($db, $userId) {
    $visitId = $_POST['visitId'] ?? '';
    
    if (empty($visitId)) {
        echo json_encode(['success' => false, 'message' => 'Visit ID is required']);
        return;
    }
    
    // Verify ownership
    $visit = $db->single("SELECT * FROM field_visits WHERE visit_id = ? AND officer_id = ?", [$visitId, $userId]);
    if (!$visit) {
        echo json_encode(['success' => false, 'message' => 'Visit not found or unauthorized']);
        return;
    }
    
    try {
        $db->query("UPDATE field_visits SET status = 'cancelled', updated_at = NOW() WHERE visit_id = ?")
           ->bind(1, $visitId)
           ->execute();
        
        // Notify farmer
        $db->query("INSERT INTO alerts (user_id, alert_type, title, message, priority, category, created_by, created_at) 
            VALUES (?, 'system', 'Field Visit Cancelled', 'A scheduled field visit has been cancelled.', 'low', 'Visit', ?, NOW())")
           ->bind(1, $visit['farmer_id'])
           ->bind(2, $userId)
           ->execute();
        
        echo json_encode(['success' => true, 'message' => 'Visit cancelled successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to cancel visit: ' . $e->getMessage()]);
    }
}

function completeVisit($db, $userId) {
    $visitId = $_POST['visitId'] ?? '';
    $observations = trim($_POST['observations'] ?? '');
    $recommendations = trim($_POST['recommendations'] ?? '');
    $followUpRequired = isset($_POST['followUpRequired']) ? 1 : 0;
    $followUpDate = $_POST['followUpDate'] ?? null;
    
    if (empty($visitId)) {
        echo json_encode(['success' => false, 'message' => 'Visit ID is required']);
        return;
    }
    
    // Verify ownership
    $visit = $db->single("SELECT * FROM field_visits WHERE visit_id = ? AND officer_id = ?", [$visitId, $userId]);
    if (!$visit) {
        echo json_encode(['success' => false, 'message' => 'Visit not found or unauthorized']);
        return;
    }
    
    try {
        $db->query("UPDATE field_visits SET status = 'completed', observations = ?, recommendations = ?, follow_up_required = ?, follow_up_date = ?, updated_at = NOW() WHERE visit_id = ?")
           ->bind(1, $observations)
           ->bind(2, $recommendations)
           ->bind(3, $followUpRequired)
           ->bind(4, $followUpRequired && $followUpDate ? $followUpDate : null)
           ->bind(5, $visitId)
           ->execute();
        
        echo json_encode(['success' => true, 'message' => 'Visit marked as completed']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to complete visit: ' . $e->getMessage()]);
    }
}

function getFarmers($db) {
    $search = $_GET['search'] ?? '';
    $region = $_GET['region'] ?? '';
    
    $sql = "SELECT u.user_id, u.first_name, u.last_name, u.phone, u.email, fp.region, fp.primary_crops 
            FROM users u 
            LEFT JOIN farmer_profiles fp ON u.user_id = fp.user_id 
            WHERE u.role = 'farmer'";
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.phone LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($region)) {
        $sql .= " AND fp.region = ?";
        $params[] = $region;
    }
    
    $sql .= " ORDER BY u.first_name ASC LIMIT 50";
    
    try {
        $farmers = $db->resultSet($sql, $params);
        echo json_encode(['success' => true, 'farmers' => $farmers]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get farmers: ' . $e->getMessage()]);
    }
}

function getFarmerDetails($db) {
    $farmerId = $_GET['farmerId'] ?? '';
    
    if (empty($farmerId)) {
        echo json_encode(['success' => false, 'message' => 'Farmer ID is required']);
        return;
    }
    
    try {
        $farmer = $db->single("SELECT u.*, fp.* FROM users u 
            LEFT JOIN farmer_profiles fp ON u.user_id = fp.user_id 
            WHERE u.user_id = ? AND u.role = 'farmer'", [$farmerId]);
        
        if (!$farmer) {
            echo json_encode(['success' => false, 'message' => 'Farmer not found']);
            return;
        }
        
        // Get farmer stats
        $cropCount = $db->single("SELECT COUNT(*) as count FROM crop_data WHERE farmer_id = ?", [$farmerId])['count'] ?? 0;
        $diseaseReports = $db->single("SELECT COUNT(*) as count FROM disease_reports WHERE user_id = ?", [$farmerId])['count'] ?? 0;
        $recentCrops = $db->resultSet("SELECT * FROM crop_data WHERE farmer_id = ? ORDER BY created_at DESC LIMIT 5", [$farmerId]);
        
        $farmer['crop_count'] = $cropCount;
        $farmer['disease_reports'] = $diseaseReports;
        $farmer['recent_crops'] = $recentCrops;
        
        // Remove sensitive data
        unset($farmer['password_hash']);
        
        echo json_encode(['success' => true, 'farmer' => $farmer]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get farmer details: ' . $e->getMessage()]);
    }
}

function getVisits($db, $userId) {
    $status = $_GET['status'] ?? '';
    $fromDate = $_GET['fromDate'] ?? '';
    $toDate = $_GET['toDate'] ?? '';
    
    $sql = "SELECT fv.*, u.first_name, u.last_name, u.phone, fp.region 
            FROM field_visits fv 
            JOIN users u ON fv.farmer_id = u.user_id 
            LEFT JOIN farmer_profiles fp ON u.user_id = fp.user_id 
            WHERE fv.officer_id = ?";
    $params = [$userId];
    
    if (!empty($status)) {
        $sql .= " AND fv.status = ?";
        $params[] = $status;
    }
    
    if (!empty($fromDate)) {
        $sql .= " AND fv.visit_date >= ?";
        $params[] = $fromDate;
    }
    
    if (!empty($toDate)) {
        $sql .= " AND fv.visit_date <= ?";
        $params[] = $toDate;
    }
    
    $sql .= " ORDER BY fv.visit_date DESC, fv.visit_time DESC LIMIT 50";
    
    try {
        $visits = $db->resultSet($sql, $params);
        echo json_encode(['success' => true, 'visits' => $visits]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get visits: ' . $e->getMessage()]);
    }
}

function getAlerts($db, $userId) {
    try {
        $alerts = $db->resultSet("SELECT a.*, u.first_name, u.last_name 
            FROM alerts a 
            JOIN users u ON a.user_id = u.user_id 
            WHERE a.created_by = ? 
            ORDER BY a.created_at DESC LIMIT 50", [$userId]);
        
        echo json_encode(['success' => true, 'alerts' => $alerts]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get alerts: ' . $e->getMessage()]);
    }
}

function getAdvisories($db, $userId) {
    try {
        $advisories = $db->resultSet("SELECT * FROM advisories WHERE created_by = ? ORDER BY created_at DESC LIMIT 50", [$userId]);
        echo json_encode(['success' => true, 'advisories' => $advisories]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get advisories: ' . $e->getMessage()]);
    }
}

function deleteAlert($db, $userId) {
    $alertId = $_POST['alertId'] ?? '';
    
    if (empty($alertId)) {
        echo json_encode(['success' => false, 'message' => 'Alert ID is required']);
        return;
    }
    
    // Verify ownership
    $alert = $db->single("SELECT * FROM alerts WHERE alert_id = ? AND created_by = ?", [$alertId, $userId]);
    if (!$alert) {
        echo json_encode(['success' => false, 'message' => 'Alert not found or unauthorized']);
        return;
    }
    
    try {
        $db->query("DELETE FROM alerts WHERE alert_id = ?")->bind(1, $alertId)->execute();
        echo json_encode(['success' => true, 'message' => 'Alert deleted successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete alert: ' . $e->getMessage()]);
    }
}

function deleteAdvisory($db, $userId) {
    $advisoryId = $_POST['advisoryId'] ?? '';
    
    if (empty($advisoryId)) {
        echo json_encode(['success' => false, 'message' => 'Advisory ID is required']);
        return;
    }
    
    // Verify ownership
    $advisory = $db->single("SELECT * FROM advisories WHERE advisory_id = ? AND created_by = ?", [$advisoryId, $userId]);
    if (!$advisory) {
        echo json_encode(['success' => false, 'message' => 'Advisory not found or unauthorized']);
        return;
    }
    
    try {
        $db->query("DELETE FROM advisories WHERE advisory_id = ?")->bind(1, $advisoryId)->execute();
        echo json_encode(['success' => true, 'message' => 'Advisory deleted successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete advisory: ' . $e->getMessage()]);
    }
}

function getDetectionDetails($db) {
    $detectionId = $_GET['detectionId'] ?? '';
    
    if (empty($detectionId)) {
        echo json_encode(['success' => false, 'message' => 'Detection ID is required']);
        return;
    }
    
    try {
        $detection = $db->single("SELECT dr.*, u.first_name, u.last_name, u.phone, u.email, 
            c.crop_name, c.variety, fp.region 
            FROM disease_reports dr 
            JOIN users u ON dr.user_id = u.user_id 
            LEFT JOIN crop_data c ON dr.crop_id = c.crop_id 
            LEFT JOIN farmer_profiles fp ON u.user_id = fp.user_id 
            WHERE dr.detection_id = ?", [$detectionId]);
        
        if (!$detection) {
            echo json_encode(['success' => false, 'message' => 'Detection not found']);
            return;
        }
        
        echo json_encode(['success' => true, 'detection' => $detection]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get detection details: ' . $e->getMessage()]);
    }
}

function getDashboardStats($db, $userId) {
    try {
        $stats = [];
        
        // Total farmers
        $stats['totalFarmers'] = $db->single("SELECT COUNT(*) as count FROM users WHERE role = 'farmer'", [])['count'] ?? 0;
        
        // Active crops
        $stats['activeCrops'] = $db->single("SELECT COUNT(*) as count FROM crop_data WHERE status = 'growing'", [])['count'] ?? 0;
        
        // Disease reports (30 days)
        $stats['diseaseReports'] = $db->single("SELECT COUNT(*) as count FROM disease_reports WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)", [])['count'] ?? 0;
        
        // Alerts issued (7 days)
        $stats['alertsIssued'] = $db->single("SELECT COUNT(*) as count FROM alerts WHERE created_by = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)", [$userId])['count'] ?? 0;
        
        // Scheduled visits
        $stats['scheduledVisits'] = $db->single("SELECT COUNT(*) as count FROM field_visits WHERE officer_id = ? AND status = 'scheduled'", [$userId])['count'] ?? 0;
        
        // Completed visits (30 days)
        $stats['completedVisits'] = $db->single("SELECT COUNT(*) as count FROM field_visits WHERE officer_id = ? AND status = 'completed' AND updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)", [$userId])['count'] ?? 0;
        
        echo json_encode(['success' => true, 'stats' => $stats]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get stats: ' . $e->getMessage()]);
    }
}

/**
 * Send message to a farmer
 */
function sendMessage($db, $userId) {
    $receiverId = intval($_POST['receiverId'] ?? 0);
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($receiverId) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Receiver and message are required']);
        return;
    }
    
    try {
        // Check if messages table exists, if not create a notification/alert instead
        $tableExists = $db->single("SHOW TABLES LIKE 'messages'");
        
        if ($tableExists) {
            $db->query("INSERT INTO messages (sender_id, receiver_id, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())", 
                [$userId, $receiverId, $subject, $message]);
        } else {
            // Create as an alert instead
            $db->query("INSERT INTO alerts (alert_type, title, message, priority, target_user_id, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())",
                ['message', $subject ?: 'Message from Officer', $message, 'medium', $receiverId, $userId]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to send message: ' . $e->getMessage()]);
    }
}

/**
 * Get farmer statistics for profile view
 */
function getFarmerStats($db) {
    $farmerId = intval($_GET['farmerId'] ?? 0);
    
    if (empty($farmerId)) {
        echo json_encode(['success' => false, 'message' => 'Farmer ID is required']);
        return;
    }
    
    try {
        $stats = [];
        
        // Crop statistics
        $stats['totalCrops'] = $db->single("SELECT COUNT(*) as count FROM crop_data WHERE farmer_id = ?", [$farmerId])['count'] ?? 0;
        $stats['activeCrops'] = $db->single("SELECT COUNT(*) as count FROM crop_data WHERE farmer_id = ? AND status = 'growing'", [$farmerId])['count'] ?? 0;
        $stats['harvestedCrops'] = $db->single("SELECT COUNT(*) as count FROM crop_data WHERE farmer_id = ? AND status = 'harvested'", [$farmerId])['count'] ?? 0;
        
        // Disease reports
        $stats['diseaseReports'] = $db->single("SELECT COUNT(*) as count FROM disease_reports WHERE user_id = ?", [$farmerId])['count'] ?? 0;
        $stats['highSeverity'] = $db->single("SELECT COUNT(*) as count FROM disease_reports WHERE user_id = ? AND severity = 'high'", [$farmerId])['count'] ?? 0;
        
        // Community activity
        $stats['communityPosts'] = $db->single("SELECT COUNT(*) as count FROM community_posts WHERE user_id = ?", [$farmerId])['count'] ?? 0;
        
        // Marketplace
        $stats['products'] = $db->single("SELECT COUNT(*) as count FROM marketplace_products WHERE seller_id = ?", [$farmerId])['count'] ?? 0;
        $stats['activeProducts'] = $db->single("SELECT COUNT(*) as count FROM marketplace_products WHERE seller_id = ? AND status = 'available'", [$farmerId])['count'] ?? 0;
        
        // Seller stats if available
        $sellerStats = $db->single("SELECT * FROM seller_stats WHERE seller_id = ?", [$farmerId]);
        if ($sellerStats) {
            $stats['sellerRating'] = $sellerStats['average_rating'];
            $stats['totalReviews'] = $sellerStats['total_reviews'];
            $stats['totalOrders'] = $sellerStats['total_orders'];
            $stats['completedOrders'] = $sellerStats['completed_orders'];
            $stats['sellerBadge'] = $sellerStats['badge'];
        }
        
        echo json_encode(['success' => true, 'stats' => $stats]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get farmer stats: ' . $e->getMessage()]);
    }
}
