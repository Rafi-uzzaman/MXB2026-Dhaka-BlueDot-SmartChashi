<?php
// Authentication check
include __DIR__ . '/../layouts/header.php';

if (!isLoggedIn()) {
    redirect('login');
}


$db = new Database();
$currentUser = getCurrentUser();
$farmerId = $_GET['id'] ?? null;

if (!$farmerId) {
    redirect('dashboard');
}

// Get farmer information
$farmer = $db->single("SELECT u.*, fp.* FROM users u 
    LEFT JOIN farmer_profiles fp ON u.user_id = fp.user_id 
    WHERE u.user_id = ? AND u.role = 'farmer'", [$farmerId]);

if (!$farmer) {
    redirect('dashboard');
}

// Get farmer's statistics
$crops = $db->resultSet("SELECT * FROM crop_data WHERE farmer_id = ? ORDER BY created_at DESC LIMIT 6", [$farmerId]);
$cropCount = $db->single("SELECT COUNT(*) as count FROM crop_data WHERE farmer_id = ?", [$farmerId])['count'] ?? 0;
$activeCrops = $db->single("SELECT COUNT(*) as count FROM crop_data WHERE farmer_id = ? AND status = 'growing'", [$farmerId])['count'] ?? 0;
$harvestedCrops = $db->single("SELECT COUNT(*) as count FROM crop_data WHERE farmer_id = ? AND status = 'harvested'", [$farmerId])['count'] ?? 0;
$communityPosts = $db->single("SELECT COUNT(*) as count FROM community_posts WHERE user_id = ?", [$farmerId])['count'] ?? 0;

// Get disease reports
$diseaseReports = $db->resultSet("SELECT dr.*, c.crop_name FROM disease_reports dr 
    LEFT JOIN crop_data c ON dr.crop_id = c.crop_id 
    WHERE dr.user_id = ? ORDER BY dr.created_at DESC LIMIT 10", [$farmerId]);
$totalDiseaseReports = $db->single("SELECT COUNT(*) as count FROM disease_reports WHERE user_id = ?", [$farmerId])['count'] ?? 0;
$highSeverityCount = $db->single("SELECT COUNT(*) as count FROM disease_reports WHERE user_id = ? AND severity = 'high'", [$farmerId])['count'] ?? 0;

// Get marketplace products (if farmer sells)
$products = $db->resultSet("SELECT * FROM marketplace_products WHERE seller_id = ? AND status = 'available' ORDER BY created_at DESC LIMIT 6", [$farmerId]);
$productCount = $db->single("SELECT COUNT(*) as count FROM marketplace_products WHERE seller_id = ?", [$farmerId])['count'] ?? 0;

// Get seller stats if they have products
$sellerStats = null;
if ($productCount > 0) {
    $sellerStats = $db->single("SELECT * FROM seller_stats WHERE seller_id = ?", [$farmerId]);
    if (!$sellerStats) {
        // Calculate basic stats
        $totalOrders = $db->single("SELECT COUNT(*) as count FROM marketplace_orders WHERE seller_id = ?", [$farmerId])['count'] ?? 0;
        $completedOrders = $db->single("SELECT COUNT(*) as count FROM marketplace_orders WHERE seller_id = ? AND order_status = 'delivered'", [$farmerId])['count'] ?? 0;
        $avgRating = $db->single("SELECT AVG(pr.rating) as avg_rating, COUNT(pr.review_id) as total_reviews 
            FROM product_reviews pr 
            JOIN marketplace_products mp ON pr.product_id = mp.product_id 
            WHERE mp.seller_id = ? AND pr.parent_review_id IS NULL AND pr.status = 'active'", [$farmerId]);
        
        $sellerStats = [
            'total_products' => $productCount,
            'total_orders' => $totalOrders,
            'completed_orders' => $completedOrders,
            'average_rating' => $avgRating['avg_rating'] ?? 0,
            'total_reviews' => $avgRating['total_reviews'] ?? 0,
            'badge' => 'new'
        ];
        
        // Determine badge
        if ($completedOrders >= 100) $sellerStats['badge'] = 'platinum';
        elseif ($completedOrders >= 50) $sellerStats['badge'] = 'gold';
        elseif ($completedOrders >= 20) $sellerStats['badge'] = 'silver';
        elseif ($completedOrders >= 5) $sellerStats['badge'] = 'bronze';
    }
}

// Get recent product reviews
$recentReviews = [];
if ($productCount > 0) {
    $recentReviews = $db->resultSet("SELECT pr.*, mp.product_name, u.first_name, u.last_name, u.profile_img_url
        FROM product_reviews pr
        JOIN marketplace_products mp ON pr.product_id = mp.product_id
        JOIN users u ON pr.user_id = u.user_id
        WHERE mp.seller_id = ? AND pr.parent_review_id IS NULL AND pr.status = 'active'
        ORDER BY pr.created_at DESC LIMIT 5", [$farmerId]);
}

// Get field visits (for officers viewing)
$fieldVisits = [];
if ($currentUser['role'] === 'officer' || $currentUser['role'] === 'admin') {
    $fieldVisits = $db->resultSet("SELECT fv.*, o.first_name as officer_first, o.last_name as officer_last 
        FROM field_visits fv 
        JOIN users o ON fv.officer_id = o.user_id 
        WHERE fv.farmer_id = ? ORDER BY fv.visit_date DESC LIMIT 5", [$farmerId]);
}

// Get community posts
$recentPosts = $db->resultSet("SELECT cp.*, 
    (SELECT COUNT(*) FROM post_likes WHERE post_id = cp.post_id) as like_count,
    (SELECT COUNT(*) FROM post_comments WHERE post_id = cp.post_id) as comment_count
    FROM community_posts cp 
    WHERE cp.user_id = ? ORDER BY cp.created_at DESC LIMIT 5", [$farmerId]);

// Check if officer can schedule visit
$canScheduleVisit = ($currentUser['role'] === 'officer' || $currentUser['role'] === 'admin');

// Get AI recommendations for this farmer
$recommendations = $db->resultSet("SELECT * FROM ai_recommendations WHERE user_id = ? ORDER BY created_at DESC LIMIT 5", [$farmerId]);
?>

<section class="hero">
    <h1><span class="material-icons">person</span> Farmer Profile</h1>
    <p>Viewing profile of <?php echo htmlspecialchars($farmer['first_name'] . ' ' . ($farmer['last_name'] ?? '')); ?></p>
</section>

<div class="profile-view-container">
    <!-- Left Column - Farmer Info -->
    <div class="profile-view-left">
        <!-- Farmer Information Card -->
        <div class="card profile-view-card">
            <div class="profile-view-header">
                <div class="profile-view-avatar">
                    <?php if (!empty($farmer['profile_img_url'])): ?>
                        <img src="<?php echo $base_url .'public/'. htmlspecialchars($farmer['profile_img_url']); ?>" alt="Profile">
                    <?php else: ?>
                        <span class="material-icons">account_circle</span>
                    <?php endif; ?>
                </div>
                <div class="profile-view-info">
                    <h2><?php echo htmlspecialchars($farmer['first_name'] . ' ' . ($farmer['last_name'] ?? '')); ?></h2>
                    <p class="profile-view-role">
                        <span class="material-icons">agriculture</span>
                        Farmer
                        <?php if ($farmer['experience_level']): ?>
                            <span class="badge badge-<?php echo $farmer['experience_level'] === 'advanced' ? 'success' : ($farmer['experience_level'] === 'intermediate' ? 'warning' : 'info'); ?>">
                                <?php echo ucfirst($farmer['experience_level']); ?>
                            </span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="profile-view-details">
                <?php if (!empty($farmer['region'])): ?>
                <div class="profile-detail-item">
                    <span class="material-icons">location_on</span>
                    <span><strong>Region:</strong> <?php echo htmlspecialchars($farmer['region']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($farmer['district'])): ?>
                <div class="profile-detail-item">
                    <span class="material-icons">map</span>
                    <span><strong>District:</strong> <?php echo htmlspecialchars($farmer['district']); ?><?php echo $farmer['sub_district'] ? ', ' . htmlspecialchars($farmer['sub_district']) : ''; ?></span>
                </div>
                <?php endif; ?>

                <?php if (!empty($farmer['farm_size'])): ?>
                <div class="profile-detail-item">
                    <span class="material-icons">landscape</span>
                    <span><strong>Farm Size:</strong> <?php echo htmlspecialchars($farmer['farm_size']); ?> acres</span>
                </div>
                <?php endif; ?>

                <?php if (!empty($farmer['primary_crops'])): ?>
                <div class="profile-detail-item">
                    <span class="material-icons">eco</span>
                    <span><strong>Primary Crops:</strong> <?php echo htmlspecialchars($farmer['primary_crops']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($farmer['farming_type'])): ?>
                <div class="profile-detail-item">
                    <span class="material-icons">grass</span>
                    <span><strong>Farming Type:</strong> <?php echo ucfirst(htmlspecialchars($farmer['farming_type'])); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($farmer['soil_type'])): ?>
                <div class="profile-detail-item">
                    <span class="material-icons">terrain</span>
                    <span><strong>Soil Type:</strong> <?php echo htmlspecialchars($farmer['soil_type']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($farmer['irrigation_type'])): ?>
                <div class="profile-detail-item">
                    <span class="material-icons">water_drop</span>
                    <span><strong>Irrigation:</strong> <?php echo htmlspecialchars($farmer['irrigation_type']); ?></span>
                </div>
                <?php endif; ?>

                <div class="profile-detail-item">
                    <span class="material-icons">event</span>
                    <span><strong>Member Since:</strong> <?php echo date('M Y', strtotime($farmer['created_at'])); ?></span>
                </div>
            </div>
            
            <!-- Action Buttons for Officers -->
            <?php if ($canScheduleVisit): ?>
            <div class="profile-actions">
                <a href="tel:<?php echo htmlspecialchars($farmer['phone']); ?>" class="btn btn-success">
                    <span class="material-icons">phone</span> Call
                </a>
                <button class="btn btn-secondary" onclick="openModal('scheduleVisitModal')">
                    <span class="material-icons">event</span> Schedule Visit
                </button>
                <button class="btn btn-warning" onclick="openModal('issueAlertModal')">
                    <span class="material-icons">warning</span> Send Alert
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Statistics Card -->
        <div class="card profile-view-card">
            <h3><span class="material-icons">analytics</span> Statistics</h3>
            <div class="profile-stats-grid">
                <div class="stat-box">
                    <span class="material-icons stat-icon text-primary">agriculture</span>
                    <div class="stat-value"><?php echo $cropCount; ?></div>
                    <div class="stat-label">Total Crops</div>
                </div>
                <div class="stat-box">
                    <span class="material-icons stat-icon text-success">eco</span>
                    <div class="stat-value"><?php echo $activeCrops; ?></div>
                    <div class="stat-label">Active</div>
                </div>
                <div class="stat-box">
                    <span class="material-icons stat-icon text-warning">check_circle</span>
                    <div class="stat-value"><?php echo $harvestedCrops; ?></div>
                    <div class="stat-label">Harvested</div>
                </div>
                <div class="stat-box">
                    <span class="material-icons stat-icon text-info">forum</span>
                    <div class="stat-value"><?php echo $communityPosts; ?></div>
                    <div class="stat-label">Posts</div>
                </div>
                <div class="stat-box">
                    <span class="material-icons stat-icon text-danger">coronavirus</span>
                    <div class="stat-value"><?php echo $totalDiseaseReports; ?></div>
                    <div class="stat-label">Reports</div>
                </div>
                <div class="stat-box">
                    <span class="material-icons stat-icon text-secondary">storefront</span>
                    <div class="stat-value"><?php echo $productCount; ?></div>
                    <div class="stat-label">Products</div>
                </div>
            </div>
        </div>
        
        <?php if ($sellerStats && $productCount > 0): ?>
        <!-- Seller Stats Card -->
        <div class="card profile-view-card">
            <h3><span class="material-icons">store</span> Seller Profile</h3>
            <div class="seller-stats-display">
                <div class="seller-rating-big">
                    <?php 
                    $rating = floatval($sellerStats['average_rating'] ?? 0);
                    $badgeColors = ['new' => 'info', 'bronze' => 'secondary', 'silver' => 'light', 'gold' => 'warning', 'platinum' => 'primary'];
                    ?>
                    <div class="rating-circle">
                        <span class="rating-number"><?php echo $rating > 0 ? number_format($rating, 1) : 'N/A'; ?></span>
                        <span class="rating-star material-icons">star</span>
                    </div>
                    <div class="rating-info">
                        <span class="review-count"><?php echo $sellerStats['total_reviews'] ?? 0; ?> reviews</span>
                        <span class="badge badge-<?php echo $badgeColors[$sellerStats['badge']] ?? 'info'; ?> seller-badge">
                            <span class="material-icons"><?php echo $sellerStats['badge'] === 'platinum' ? 'diamond' : ($sellerStats['badge'] === 'gold' ? 'workspace_premium' : 'verified'); ?></span>
                            <?php echo ucfirst($sellerStats['badge'] ?? 'New'); ?> Seller
                        </span>
                    </div>
                </div>
                <div class="seller-stats-row">
                    <div class="seller-stat-item">
                        <span class="material-icons">inventory_2</span>
                        <div>
                            <strong><?php echo $sellerStats['total_products'] ?? $productCount; ?></strong>
                            <small>Products</small>
                        </div>
                    </div>
                    <div class="seller-stat-item">
                        <span class="material-icons">shopping_cart</span>
                        <div>
                            <strong><?php echo $sellerStats['total_orders'] ?? 0; ?></strong>
                            <small>Orders</small>
                        </div>
                    </div>
                    <div class="seller-stat-item">
                        <span class="material-icons">check_circle</span>
                        <div>
                            <strong><?php echo $sellerStats['completed_orders'] ?? 0; ?></strong>
                            <small>Completed</small>
                        </div>
                    </div>
                </div>
            </div>
            <a href="<?php echo $base_url; ?>marketplace?seller=<?php echo $farmerId; ?>" class="btn btn-block mt-2">
                <span class="material-icons">storefront</span> View Shop
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Right Column - Detailed Info -->
    <div class="profile-view-right">

<!-- Recent Crops -->
<?php if ($crops): ?>
<div class="card">
    <div class="card-header-flex">
        <h3><span class="material-icons">agriculture</span> Recent Crops</h3>
        <span class="badge"><?php echo $cropCount; ?> total</span>
    </div>
    <div class="crops-mini-grid">
        <?php foreach ($crops as $crop): ?>
        <div class="crop-mini-card">
            <div class="crop-mini-header">
                <span class="material-icons">eco</span>
                <h4><?php echo htmlspecialchars($crop['crop_name']); ?></h4>
                <span class="badge badge-<?php echo $crop['status'] === 'growing' ? 'success' : ($crop['status'] === 'harvested' ? 'warning' : 'secondary'); ?>">
                    <?php echo ucfirst($crop['status']); ?>
                </span>
            </div>
            <div class="crop-mini-details">
                <span><span class="material-icons">category</span> <?php echo htmlspecialchars($crop['crop_type'] ?? 'N/A'); ?></span>
                <span><span class="material-icons">landscape</span> <?php echo htmlspecialchars($crop['area'] ?? '0'); ?> acres</span>
                <span><span class="material-icons">event</span> <?php echo date('M d, Y', strtotime($crop['planting_date'])); ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Disease Reports (Visible to Officers) -->
<?php if ($diseaseReports && ($canScheduleVisit || $currentUser['user_id'] == $farmerId)): ?>
<div class="card">
    <div class="card-header-flex">
        <h3><span class="material-icons">coronavirus</span> Disease Reports</h3>
        <?php if ($highSeverityCount > 0): ?>
        <span class="badge badge-danger"><?php echo $highSeverityCount; ?> High Severity</span>
        <?php endif; ?>
    </div>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Crop</th>
                    <th>Disease</th>
                    <th>Type</th>
                    <th>Severity</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($diseaseReports as $report): ?>
                <tr>
                    <td><?php echo date('M d, Y', strtotime($report['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars($report['crop_name'] ?? 'Unknown'); ?></td>
                    <td><?php echo htmlspecialchars($report['disease_name'] ?? 'Unknown'); ?></td>
                    <td><?php echo htmlspecialchars($report['disease_type'] ?? 'N/A'); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $report['severity'] === 'high' ? 'danger' : ($report['severity'] === 'medium' ? 'warning' : 'success'); ?>">
                            <?php echo ucfirst($report['severity']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-<?php echo $report['status'] === 'cured' ? 'success' : ($report['status'] === 'treating' ? 'warning' : 'info'); ?>">
                            <?php echo ucfirst($report['status']); ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-small btn-info" onclick="viewDiseaseReport(<?php echo $report['detection_id']; ?>)">
                            <span class="material-icons">visibility</span>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Field Visits (Officers Only) -->
<?php if ($fieldVisits && $canScheduleVisit): ?>
<div class="card">
    <div class="card-header-flex">
        <h3><span class="material-icons">event_available</span> Field Visit History</h3>
        <button class="btn btn-small" onclick="openModal('scheduleVisitModal')">
            <span class="material-icons">add</span> Schedule
        </button>
    </div>
    <div class="visit-timeline">
        <?php foreach ($fieldVisits as $visit): ?>
        <div class="visit-item visit-<?php echo $visit['status']; ?>">
            <div class="visit-date">
                <span class="visit-day"><?php echo date('d', strtotime($visit['visit_date'])); ?></span>
                <span class="visit-month"><?php echo date('M Y', strtotime($visit['visit_date'])); ?></span>
            </div>
            <div class="visit-content">
                <div class="visit-header">
                    <span class="badge badge-<?php echo $visit['status'] === 'completed' ? 'success' : ($visit['status'] === 'scheduled' ? 'info' : 'secondary'); ?>">
                        <?php echo ucfirst($visit['status']); ?>
                    </span>
                    <small>By <?php echo htmlspecialchars($visit['officer_first'] . ' ' . ($visit['officer_last'] ?? '')); ?></small>
                </div>
                <?php if ($visit['purpose']): ?>
                <p class="visit-purpose"><strong>Purpose:</strong> <?php echo htmlspecialchars($visit['purpose']); ?></p>
                <?php endif; ?>
                <?php if ($visit['observations']): ?>
                <p class="visit-obs"><strong>Observations:</strong> <?php echo htmlspecialchars(substr($visit['observations'], 0, 150)); ?>...</p>
                <?php endif; ?>
                <?php if ($visit['recommendations']): ?>
                <p class="visit-rec"><strong>Recommendations:</strong> <?php echo htmlspecialchars(substr($visit['recommendations'], 0, 150)); ?>...</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Community Posts -->
<?php if ($recentPosts): ?>
<div class="card">
    <div class="card-header-flex">
        <h3><span class="material-icons">forum</span> Community Posts</h3>
        <span class="badge"><?php echo $communityPosts; ?> posts</span>
    </div>
    <div class="posts-list">
        <?php foreach ($recentPosts as $post): ?>
        <div class="post-item">
            <div class="post-content">
                <p><?php echo htmlspecialchars(substr($post['content'], 0, 200)); ?><?php echo strlen($post['content']) > 200 ? '...' : ''; ?></p>
            </div>
            <div class="post-meta">
                <span><span class="material-icons">favorite</span> <?php echo $post['like_count'] ?? 0; ?></span>
                <span><span class="material-icons">comment</span> <?php echo $post['comment_count'] ?? 0; ?></span>
                <span><span class="material-icons">schedule</span> <?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Marketplace Products -->
<?php if ($products): ?>
<div class="card">
    <div class="card-header-flex">
        <h3><span class="material-icons">storefront</span> Products for Sale</h3>
        <a href="<?php echo $base_url; ?>marketplace?seller=<?php echo $farmerId; ?>" class="btn btn-small">View All</a>
    </div>
    <div class="products-mini-grid">
        <?php foreach ($products as $product): ?>
        <div class="product-mini-card" onclick="window.location.href='<?php echo $base_url; ?>marketplace?product=<?php echo $product['product_id']; ?>'">
            <?php if ($product['image_url']): ?>
            <img src="<?php echo $base_url . htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
            <?php else: ?>
            <div class="product-placeholder"><span class="material-icons">eco</span></div>
            <?php endif; ?>
            <div class="product-info">
                <h4><?php echo htmlspecialchars($product['product_name']); ?></h4>
                <p class="product-price">৳<?php echo number_format($product['price'], 2); ?></p>
                <p class="product-qty"><?php echo $product['quantity_available']; ?> <?php echo $product['unit'] ?? 'kg'; ?> available</p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Recent Reviews for Seller -->
<?php if ($recentReviews): ?>
<div class="card">
    <div class="card-header-flex">
        <h3><span class="material-icons">rate_review</span> Recent Reviews</h3>
        <span class="badge"><?php echo $sellerStats['total_reviews'] ?? count($recentReviews); ?> reviews</span>
    </div>
    <div class="reviews-list-mini">
        <?php foreach ($recentReviews as $review): ?>
        <div class="review-mini-item">
            <div class="review-mini-header">
                <div class="reviewer-info">
                    <?php if (!empty($review['profile_img_url'])): ?>
                    <img src="<?php echo $base_url . 'public/' . htmlspecialchars($review['profile_img_url']); ?>" alt="" class="reviewer-avatar">
                    <?php else: ?>
                    <span class="material-icons reviewer-avatar-icon">account_circle</span>
                    <?php endif; ?>
                    <span class="reviewer-name"><?php echo htmlspecialchars($review['first_name'] . ' ' . ($review['last_name'] ?? '')); ?></span>
                </div>
                <div class="review-rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="material-icons star-<?php echo $i <= $review['rating'] ? 'filled' : 'empty'; ?>">star</span>
                    <?php endfor; ?>
                </div>
            </div>
            <p class="review-product"><span class="material-icons">eco</span> <?php echo htmlspecialchars($review['product_name']); ?></p>
            <p class="review-text"><?php echo htmlspecialchars(substr($review['review_text'], 0, 150)); ?><?php echo strlen($review['review_text']) > 150 ? '...' : ''; ?></p>
            <span class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
    </div>
</div>

<!-- Contact/Action Card -->
<div class="card mt-4">
    <div class="contact-section">
        <?php if ($canScheduleVisit): ?>
        <div class="contact-officer">
            <h3><span class="material-icons">contact_phone</span> Contact & Actions</h3>
            <p>As an officer, you can contact this farmer directly or schedule visits.</p>
            <div class="contact-buttons">
                <a href="tel:<?php echo htmlspecialchars($farmer['phone']); ?>" class="btn">
                    <span class="material-icons">phone</span> Call: <?php echo htmlspecialchars($farmer['phone']); ?>
                </a>
                <?php if ($farmer['email']): ?>
                <a href="mailto:<?php echo htmlspecialchars($farmer['email']); ?>" class="btn btn-secondary">
                    <span class="material-icons">email</span> Email
                </a>
                <?php endif; ?>
                <button class="btn btn-info" onclick="openModal('sendMessageModal')">
                    <span class="material-icons">message</span> Send Message
                </button>
            </div>
        </div>
        <?php else: ?>
        <div class="contact-community text-center">
            <h3><span class="material-icons">people</span> Connect with Farmers</h3>
            <p>Join our community to connect with farmers and share knowledge.</p>
            <a href="<?php echo $base_url; ?>community" class="btn mt-2">
                <span class="material-icons">forum</span> Visit Community
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Schedule Visit Modal (Officers Only) -->
<?php if ($canScheduleVisit): ?>
<div id="scheduleVisitModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><span class="material-icons">event</span> Schedule Field Visit</h3>
            <button class="modal-close" onclick="closeModal('scheduleVisitModal')">&times;</button>
        </div>
        <form id="scheduleVisitForm">
            <input type="hidden" name="farmerId" value="<?php echo $farmerId; ?>">
            <div class="modal-body">
                <div class="farmer-info-banner">
                    <span class="material-icons">person</span>
                    <span><?php echo htmlspecialchars($farmer['first_name'] . ' ' . ($farmer['last_name'] ?? '')); ?> - <?php echo htmlspecialchars($farmer['region'] ?? 'No region'); ?></span>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="visitDate">Visit Date</label>
                        <input type="date" id="visitDate" name="visitDate" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="visitTime">Visit Time (Optional)</label>
                        <input type="time" id="visitTime" name="visitTime">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="visitPurpose">Purpose of Visit</label>
                    <textarea id="visitPurpose" name="purpose" rows="3" placeholder="Describe the purpose of this field visit..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('scheduleVisitModal')">Cancel</button>
                <button type="submit" class="btn">
                    <span class="material-icons">event_available</span> Schedule Visit
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Issue Alert Modal -->
<div id="issueAlertModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><span class="material-icons">warning</span> Send Alert to Farmer</h3>
            <button class="modal-close" onclick="closeModal('issueAlertModal')">&times;</button>
        </div>
        <form id="issueAlertForm">
            <input type="hidden" name="targetFarmer" value="<?php echo $farmerId; ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label for="alertType">Alert Type</label>
                    <select id="alertType" name="alertType" required>
                        <option value="advisory">Advisory</option>
                        <option value="disease">Disease Alert</option>
                        <option value="weather">Weather Alert</option>
                        <option value="market">Market Alert</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="alertPriority">Priority</label>
                    <select id="alertPriority" name="priority" required>
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="alertTitle">Title</label>
                    <input type="text" id="alertTitle" name="title" required placeholder="Enter alert title">
                </div>
                
                <div class="form-group">
                    <label for="alertMessage">Message</label>
                    <textarea id="alertMessage" name="message" rows="4" required placeholder="Enter alert message..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('issueAlertModal')">Cancel</button>
                <button type="submit" class="btn btn-warning">
                    <span class="material-icons">send</span> Send Alert
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Send Message Modal -->
<div id="sendMessageModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><span class="material-icons">message</span> Send Message</h3>
            <button class="modal-close" onclick="closeModal('sendMessageModal')">&times;</button>
        </div>
        <form id="sendMessageForm">
            <input type="hidden" name="receiverId" value="<?php echo $farmerId; ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label for="messageSubject">Subject</label>
                    <input type="text" id="messageSubject" name="subject" placeholder="Message subject">
                </div>
                <div class="form-group">
                    <label for="messageContent">Message</label>
                    <textarea id="messageContent" name="message" rows="5" required placeholder="Type your message here..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('sendMessageModal')">Cancel</button>
                <button type="submit" class="btn">
                    <span class="material-icons">send</span> Send Message
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Disease Report Modal -->
<div id="diseaseReportModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3><span class="material-icons">coronavirus</span> Disease Report Details</h3>
            <button class="modal-close" onclick="closeModal('diseaseReportModal')">&times;</button>
        </div>
        <div class="modal-body" id="diseaseReportContent">
            <div class="loading-spinner">
                <span class="material-icons spinning">sync</span>
                <p>Loading...</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('diseaseReportModal')">Close</button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
const baseUrl = '<?php echo $base_url; ?>';
const farmerId = <?php echo $farmerId; ?>;

// Modal functions
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal on outside click
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });
});

<?php if ($canScheduleVisit): ?>
// Schedule Visit Form
document.getElementById('scheduleVisitForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'schedule_visit');
    
    submitForm(this, formData, 'scheduleVisitModal', 'Visit scheduled successfully!');
});

// Issue Alert Form
document.getElementById('issueAlertForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'issue_alert');
    formData.append('targetRegion', 'all');
    
    submitForm(this, formData, 'issueAlertModal', 'Alert sent successfully!');
});

// Send Message Form
document.getElementById('sendMessageForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'send_message');
    
    submitForm(this, formData, 'sendMessageModal', 'Message sent successfully!');
});

function submitForm(form, formData, modalId, successMessage) {
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="material-icons spinning">sync</span> Processing...';
    
    fetch(baseUrl + 'ajax/officer.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message || successMessage, 'success');
            closeModal(modalId);
            form.reset();
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message || 'An error occurred', 'error');
        }
    })
    .catch(error => {
        showNotification('Network error. Please try again.', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

// View Disease Report
function viewDiseaseReport(reportId) {
    openModal('diseaseReportModal');
    document.getElementById('diseaseReportContent').innerHTML = '<div class="loading-spinner"><span class="material-icons spinning">sync</span><p>Loading...</p></div>';
    
    fetch(baseUrl + 'ajax/officer.php?action=get_detection_details&detectionId=' + reportId)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const d = data.detection;
            document.getElementById('diseaseReportContent').innerHTML = `
                <div class="report-detail-grid">
                    <div class="report-section">
                        <h4><span class="material-icons">eco</span> Crop Information</h4>
                        <p><strong>Crop:</strong> ${d.crop_name || 'Unknown'}</p>
                        <p><strong>Variety:</strong> ${d.variety || 'N/A'}</p>
                    </div>
                    <div class="report-section">
                        <h4><span class="material-icons">coronavirus</span> Disease Information</h4>
                        <p><strong>Disease:</strong> ${d.disease_name || 'Unknown'}</p>
                        <p><strong>Type:</strong> ${d.disease_type || 'N/A'}</p>
                        <p><strong>Severity:</strong> <span class="badge badge-${d.severity === 'high' ? 'danger' : (d.severity === 'medium' ? 'warning' : 'success')}">${d.severity || 'N/A'}</span></p>
                        <p><strong>Confidence:</strong> ${d.confidence_score ? (d.confidence_score * 100).toFixed(1) + '%' : 'N/A'}</p>
                        <p><strong>Status:</strong> <span class="badge badge-${d.status === 'cured' ? 'success' : (d.status === 'treating' ? 'warning' : 'info')}">${d.status || 'N/A'}</span></p>
                    </div>
                    <div class="report-section full-width">
                        <h4><span class="material-icons">description</span> Symptoms</h4>
                        <p>${d.symptoms || 'No symptoms recorded'}</p>
                    </div>
                    <div class="report-section full-width">
                        <h4><span class="material-icons">medical_services</span> Treatment Recommended</h4>
                        <p>${d.treatment_recommended || 'No treatment recommendation'}</p>
                    </div>
                    ${d.treatment_applied ? `
                    <div class="report-section full-width">
                        <h4><span class="material-icons">check_circle</span> Treatment Applied</h4>
                        <p>${d.treatment_applied}</p>
                    </div>
                    ` : ''}
                    <div class="report-section">
                        <h4><span class="material-icons">schedule</span> Timeline</h4>
                        <p><strong>Detected:</strong> ${new Date(d.detected_date).toLocaleDateString()}</p>
                        <p><strong>Reported:</strong> ${new Date(d.created_at).toLocaleDateString()}</p>
                    </div>
                </div>
            `;
        } else {
            document.getElementById('diseaseReportContent').innerHTML = '<p class="text-center text-danger">Failed to load report details</p>';
        }
    })
    .catch(error => {
        document.getElementById('diseaseReportContent').innerHTML = '<p class="text-center text-danger">Network error</p>';
    });
}
<?php endif; ?>

// Refresh stats dynamically
function refreshStats() {
    fetch(baseUrl + 'ajax/officer.php?action=get_farmer_stats&farmerId=' + farmerId)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const stats = data.stats;
            // Update stat boxes if they exist
            document.querySelectorAll('.stat-box').forEach(box => {
                const label = box.querySelector('.stat-label').textContent.toLowerCase();
                if (label.includes('total crops') && stats.totalCrops !== undefined) {
                    box.querySelector('.stat-value').textContent = stats.totalCrops;
                } else if (label.includes('active') && stats.activeCrops !== undefined) {
                    box.querySelector('.stat-value').textContent = stats.activeCrops;
                } else if (label.includes('harvested') && stats.harvestedCrops !== undefined) {
                    box.querySelector('.stat-value').textContent = stats.harvestedCrops;
                } else if (label.includes('posts') && stats.communityPosts !== undefined) {
                    box.querySelector('.stat-value').textContent = stats.communityPosts;
                } else if (label.includes('reports') && stats.diseaseReports !== undefined) {
                    box.querySelector('.stat-value').textContent = stats.diseaseReports;
                } else if (label.includes('products') && stats.products !== undefined) {
                    box.querySelector('.stat-value').textContent = stats.products;
                }
            });
            console.log('Stats refreshed successfully');
        }
    })
    .catch(err => console.error('Failed to refresh stats:', err));
}

// Auto-refresh stats every 60 seconds
setInterval(refreshStats, 60000);

// Notification helper
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = 'notification notification-' + type;
    notification.innerHTML = `<span class="material-icons">${type === 'success' ? 'check_circle' : 'error'}</span> ${message}`;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
</script>

<style>
/* Profile View Container */
.profile-view-container {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.profile-view-left {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.profile-view-right {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Profile Card */
.profile-view-card {
    padding: 1.5rem;
}

.profile-view-header {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.profile-view-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    background: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
}

.profile-view-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-view-avatar .material-icons {
    font-size: 50px;
    color: white;
}

.profile-view-info h2 {
    margin: 0 0 0.5rem;
    font-size: 1.3rem;
}

.profile-view-role {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #666;
}

.profile-view-role .material-icons {
    font-size: 1rem;
}

/* Profile Details */
.profile-view-details {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.profile-detail-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem;
    background: #f9f9f9;
    border-radius: 8px;
}

.profile-detail-item .material-icons {
    color: var(--primary);
    font-size: 1.2rem;
}

/* Profile Actions */
.profile-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
    flex-wrap: wrap;
}

/* Statistics Grid */
.profile-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.stat-box {
    text-align: center;
    padding: 1rem;
    background: #f9f9f9;
    border-radius: 8px;
}

.stat-box .stat-icon {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.stat-box .stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #333;
}

.stat-box .stat-label {
    font-size: 0.75rem;
    color: #666;
}

/* Card Header Flex */
.card-header-flex {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #eee;
}

.card-header-flex h3 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Crops Mini Grid */
.crops-mini-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.crop-mini-card {
    padding: 1rem;
    background: #f9f9f9;
    border-radius: 8px;
}

.crop-mini-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.crop-mini-header h4 {
    margin: 0;
    flex: 1;
    font-size: 0.95rem;
}

.crop-mini-details {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    font-size: 0.85rem;
    color: #666;
}

.crop-mini-details span {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.crop-mini-details .material-icons {
    font-size: 0.9rem;
}

/* Table Styles */
.table-container {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th, .data-table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.data-table th {
    background: #f9f9f9;
    font-weight: 600;
    font-size: 0.85rem;
}

/* Visit Timeline */
.visit-timeline {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.visit-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: #f9f9f9;
    border-radius: 8px;
    border-left: 4px solid var(--primary);
}

.visit-item.visit-completed {
    border-left-color: #28a745;
}

.visit-item.visit-cancelled {
    border-left-color: #dc3545;
}

.visit-date {
    text-align: center;
    min-width: 60px;
}

.visit-day {
    display: block;
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--primary);
}

.visit-month {
    display: block;
    font-size: 0.75rem;
    color: #666;
}

.visit-content {
    flex: 1;
}

.visit-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.visit-purpose, .visit-obs, .visit-rec {
    margin: 0.5rem 0;
    font-size: 0.9rem;
    color: #555;
}

/* Posts List */
.posts-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.post-item {
    padding: 1rem;
    background: #f9f9f9;
    border-radius: 8px;
}

.post-content p {
    margin: 0 0 0.75rem;
}

.post-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.85rem;
    color: #666;
}

.post-meta span {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.post-meta .material-icons {
    font-size: 1rem;
}

/* Products Mini Grid */
.products-mini-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
}

.product-mini-card {
    background: #f9f9f9;
    border-radius: 8px;
    overflow: hidden;
}

.product-mini-card img {
    width: 100%;
    height: 100px;
    object-fit: cover;
}

.product-placeholder {
    width: 100%;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e9e9e9;
}

.product-placeholder .material-icons {
    font-size: 2rem;
    color: #999;
}

.product-info {
    padding: 0.75rem;
}

.product-info h4 {
    margin: 0 0 0.25rem;
    font-size: 0.9rem;
}

.product-price {
    color: var(--primary);
    font-weight: bold;
    margin: 0;
}

.product-qty {
    font-size: 0.8rem;
    color: #666;
    margin: 0;
}

/* Contact Section */
.contact-section {
    padding: 1.5rem;
}

.contact-buttons {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
    flex-wrap: wrap;
}

/* Farmer Info Banner */
.farmer-info-banner {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: #e8f5e9;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.farmer-info-banner .material-icons {
    color: var(--primary);
}

/* Report Detail Grid */
.report-detail-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

.report-section h4 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0 0 0.75rem;
    color: var(--primary);
    font-size: 0.95rem;
}

.report-section p {
    margin: 0.5rem 0;
    font-size: 0.9rem;
}

.report-section.full-width {
    grid-column: 1 / -1;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 10000;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.modal-lg {
    max-width: 700px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #eee;
}

.modal-header h3 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #666;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid #eee;
    background: #f9f9f9;
}

/* Form styles */
.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: var(--primary);
    outline: none;
}

/* Badges */
.badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.badge-success { background: #d4edda; color: #155724; }
.badge-danger { background: #f8d7da; color: #721c24; }
.badge-warning { background: #fff3cd; color: #856404; }
.badge-info { background: #d1ecf1; color: #0c5460; }
.badge-secondary { background: #e2e3e5; color: #383d41; }

/* Text Colors */
.text-primary { color: var(--primary); }
.text-success { color: #28a745; }
.text-danger { color: #dc3545; }
.text-warning { color: #ffc107; }
.text-info { color: #17a2b8; }
.text-secondary { color: #6c757d; }

/* Utilities */
.mt-4 { margin-top: 1.5rem; }
.text-center { text-align: center; }

/* Loading Spinner */
.loading-spinner {
    text-align: center;
    padding: 2rem;
}

.spinning {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Notification */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    color: white;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    z-index: 10001;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    animation: slideIn 0.3s ease;
}

.notification-success { background: var(--primary); }
.notification-error { background: #dc3545; }

.notification.fade-out {
    opacity: 0;
    transition: opacity 0.3s ease;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateX(100px); }
    to { opacity: 1; transform: translateX(0); }
}

/* Seller Stats Card */
.seller-stats-display {
    text-align: center;
}

.seller-rating-big {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.rating-circle {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    font-size: 2rem;
    font-weight: bold;
    color: var(--primary);
}

.rating-circle .rating-star {
    color: #ffc107;
    font-size: 2rem;
}

.rating-info {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.review-count {
    color: #666;
    font-size: 0.9rem;
}

.seller-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.75rem;
}

.seller-badge .material-icons {
    font-size: 1rem;
}

.seller-stats-row {
    display: flex;
    justify-content: space-around;
    gap: 1rem;
}

.seller-stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background: #f9f9f9;
    border-radius: 8px;
}

.seller-stat-item .material-icons {
    color: var(--primary);
    font-size: 1.5rem;
}

.seller-stat-item strong {
    font-size: 1.2rem;
    display: block;
}

.seller-stat-item small {
    color: #666;
    font-size: 0.75rem;
}

/* Reviews Mini List */
.reviews-list-mini {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.review-mini-item {
    padding: 1rem;
    background: #f9f9f9;
    border-radius: 8px;
}

.review-mini-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.reviewer-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.reviewer-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
}

.reviewer-avatar-icon {
    font-size: 32px;
    color: #ccc;
}

.reviewer-name {
    font-weight: 500;
    font-size: 0.9rem;
}

.review-rating .material-icons {
    font-size: 1rem;
}

.review-rating .star-filled {
    color: #ffc107;
}

.review-rating .star-empty {
    color: #ddd;
}

.review-product {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    color: #666;
    font-size: 0.8rem;
    margin-bottom: 0.5rem;
}

.review-product .material-icons {
    font-size: 0.9rem;
    color: var(--primary);
}

.review-text {
    font-size: 0.9rem;
    color: #333;
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.review-date {
    font-size: 0.75rem;
    color: #999;
}

/* Responsive */
@media (max-width: 900px) {
    .profile-view-container {
        grid-template-columns: 1fr;
    }
    
    .profile-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .report-detail-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .seller-stats-row {
        flex-wrap: wrap;
    }
}

@media (max-width: 600px) {
    .profile-actions {
        flex-direction: column;
    }
    
    .contact-buttons {
        flex-direction: column;
    }
    
    .profile-stats-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .stat-box {
        padding: 0.75rem 0.5rem;
    }
    
    .stat-box .stat-value {
        font-size: 1.2rem;
    }
    
    .seller-stats-row {
        flex-direction: column;
    }
    
    .review-mini-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
