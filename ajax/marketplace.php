<?php
/**
 * SmartCashi - Marketplace AJAX Handler
 * Handles all marketplace-related AJAX requests
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

$db = new Database();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Public actions that don't require login
$publicActions = ['get_products', 'get_product_details', 'get_categories', 'get_regions', 'get_market_prices', 'get_reviews', 'get_seller_stats'];

// Check if user is logged in for protected actions
if (!in_array($action, $publicActions) && !isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$userId = isLoggedIn() ? $_SESSION['user_id'] : 0;
$user = isLoggedIn() ? getCurrentUser() : null;

switch ($action) {
    case 'add_product':
        addProduct($db, $userId);
        break;
    
    case 'update_product':
        updateProduct($db, $userId);
        break;
    
    case 'delete_product':
        deleteProduct($db, $userId);
        break;
    
    case 'get_products':
        getProducts($db);
        break;
    
    case 'get_product_details':
        getProductDetails($db);
        break;
    
    case 'get_my_products':
        getMyProducts($db, $userId);
        break;
    
    case 'place_order':
        placeOrder($db, $userId);
        break;
    
    case 'get_my_orders':
        getMyOrders($db, $userId);
        break;
    
    case 'get_seller_orders':
        getSellerOrders($db, $userId);
        break;
    
    case 'update_order_status':
        updateOrderStatus($db, $userId);
        break;
    
    case 'cancel_order':
        cancelOrder($db, $userId);
        break;
    
    case 'get_market_prices':
        getMarketPrices($db);
        break;
    
    case 'increment_views':
        incrementViews($db, $userId);
        break;
    
    case 'get_categories':
        getCategories($db);
        break;
    
    case 'get_regions':
        getRegions($db);
        break;
    
    // Review actions
    case 'add_review':
        addReview($db, $userId);
        break;
    
    case 'get_reviews':
        getReviews($db, $userId);
        break;
    
    case 'delete_review':
        deleteReview($db, $userId);
        break;
    
    case 'like_review':
        likeReview($db, $userId);
        break;
    
    case 'vote_helpful':
        voteHelpful($db, $userId);
        break;
    
    case 'reply_review':
        replyReview($db, $userId);
        break;
    
    // Extra features
    case 'toggle_wishlist':
        toggleWishlist($db, $userId);
        break;
    
    case 'get_wishlist':
        getWishlist($db, $userId);
        break;
    
    case 'make_offer':
        makeOffer($db, $userId);
        break;
    
    case 'get_offers':
        getOffers($db, $userId);
        break;
    
    case 'respond_offer':
        respondOffer($db, $userId);
        break;
    
    case 'report_product':
        reportProduct($db, $userId);
        break;
    
    case 'get_recently_viewed':
        getRecentlyViewed($db, $userId);
        break;
    
    case 'toggle_compare':
        toggleCompare($db, $userId);
        break;
    
    case 'get_compare_list':
        getCompareList($db, $userId);
        break;
    
    case 'get_seller_stats':
        getSellerStats($db);
        break;
    
    case 'share_product':
        shareProduct($db);
        break;
    
    case 'check_price_alert':
        checkPriceAlert($db, $userId);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
}

/**
 * Add a new product listing
 */
function addProduct($db, $userId) {
    $productName = trim($_POST['productName'] ?? '');
    $productType = $_POST['productType'] ?? 'crop';
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $priceUnit = $_POST['priceUnit'] ?? 'kg';
    $quantity = intval($_POST['quantity'] ?? 0);
    $unit = $_POST['unit'] ?? 'kg';
    $qualityGrade = $_POST['qualityGrade'] ?? 'standard';
    $location = trim($_POST['location'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $contactPhone = trim($_POST['contactPhone'] ?? '');
    $contactEmail = trim($_POST['contactEmail'] ?? '');
    
    // New extra features fields
    $isNegotiable = isset($_POST['isNegotiable']) ? 1 : 0;
    $minOrderQuantity = intval($_POST['minOrderQuantity'] ?? 1);
    $bulkMinQuantity = !empty($_POST['bulkMinQuantity']) ? intval($_POST['bulkMinQuantity']) : null;
    $bulkDiscountPercent = !empty($_POST['bulkDiscountPercent']) ? floatval($_POST['bulkDiscountPercent']) : null;
    
    // Validation
    if (empty($productName)) {
        echo json_encode(['success' => false, 'message' => 'Product name is required']);
        return;
    }
    
    if ($price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Valid price is required']);
        return;
    }
    
    if ($quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Valid quantity is required']);
        return;
    }
    
    // Handle image upload
    $imageUrl = null;
    if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../public/uploads/products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExt = strtolower(pathinfo($_FILES['productImage']['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($fileExt, $allowedExts)) {
            $fileName = 'product_' . $userId . '_' . time() . '.' . $fileExt;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['productImage']['tmp_name'], $filePath)) {
                $imageUrl = 'public/uploads/products/' . $fileName;
            }
        }
    }
    
    try {
        $sql = "INSERT INTO marketplace_products 
                (seller_id, product_name, product_type, category, description, price, price_unit, 
                 quantity_available, unit, quality_grade, location, district, region, 
                 image_url, contact_phone, contact_email, is_negotiable, min_order_quantity,
                 bulk_min_quantity, bulk_discount_percent, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available', NOW())";
        
        $db->query($sql)
           ->bind(1, $userId)
           ->bind(2, $productName)
           ->bind(3, $productType)
           ->bind(4, $category)
           ->bind(5, $description)
           ->bind(6, $price)
           ->bind(7, $priceUnit)
           ->bind(8, $quantity)
           ->bind(9, $unit)
           ->bind(10, $qualityGrade)
           ->bind(11, $location)
           ->bind(12, $district)
           ->bind(13, $region)
           ->bind(14, $imageUrl)
           ->bind(15, $contactPhone)
           ->bind(16, $contactEmail)
           ->bind(17, $isNegotiable)
           ->bind(18, $minOrderQuantity)
           ->bind(19, $bulkMinQuantity)
           ->bind(20, $bulkDiscountPercent)
           ->execute();
        
        $productId = $db->lastInsertId();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Product listed successfully!',
            'product_id' => $productId
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to add product: ' . $e->getMessage()]);
    }
}

/**
 * Update an existing product
 */
function updateProduct($db, $userId) {
    $productId = intval($_POST['productId'] ?? 0);
    $productName = trim($_POST['productName'] ?? '');
    $productType = $_POST['productType'] ?? 'crop';
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $priceUnit = $_POST['priceUnit'] ?? 'kg';
    $quantity = intval($_POST['quantity'] ?? 0);
    $unit = $_POST['unit'] ?? 'kg';
    $qualityGrade = $_POST['qualityGrade'] ?? 'standard';
    $location = trim($_POST['location'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $contactPhone = trim($_POST['contactPhone'] ?? '');
    $contactEmail = trim($_POST['contactEmail'] ?? '');
    $status = $_POST['status'] ?? 'available';
    
    if (empty($productId)) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        return;
    }
    
    // Verify ownership
    $product = $db->single("SELECT * FROM marketplace_products WHERE product_id = ? AND seller_id = ?", [$productId, $userId]);
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found or unauthorized']);
        return;
    }
    
    // Handle image upload
    $imageUrl = $product['image_url'];
    if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../public/uploads/products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExt = strtolower(pathinfo($_FILES['productImage']['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($fileExt, $allowedExts)) {
            $fileName = 'product_' . $userId . '_' . time() . '.' . $fileExt;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['productImage']['tmp_name'], $filePath)) {
                // Delete old image
                if ($imageUrl && file_exists(__DIR__ . '/../' . $imageUrl)) {
                    unlink(__DIR__ . '/../' . $imageUrl);
                }
                $imageUrl = 'public/uploads/products/' . $fileName;
            }
        }
    }
    
    try {
        $sql = "UPDATE marketplace_products SET 
                product_name = ?, product_type = ?, category = ?, description = ?, 
                price = ?, price_unit = ?, quantity_available = ?, unit = ?, 
                quality_grade = ?, location = ?, district = ?, region = ?, 
                image_url = ?, contact_phone = ?, contact_email = ?, status = ?, 
                updated_at = NOW() 
                WHERE product_id = ? AND seller_id = ?";
        
        $db->query($sql)
           ->bind(1, $productName)
           ->bind(2, $productType)
           ->bind(3, $category)
           ->bind(4, $description)
           ->bind(5, $price)
           ->bind(6, $priceUnit)
           ->bind(7, $quantity)
           ->bind(8, $unit)
           ->bind(9, $qualityGrade)
           ->bind(10, $location)
           ->bind(11, $district)
           ->bind(12, $region)
           ->bind(13, $imageUrl)
           ->bind(14, $contactPhone)
           ->bind(15, $contactEmail)
           ->bind(16, $status)
           ->bind(17, $productId)
           ->bind(18, $userId)
           ->execute();
        
        echo json_encode(['success' => true, 'message' => 'Product updated successfully!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update product: ' . $e->getMessage()]);
    }
}

/**
 * Delete a product
 */
function deleteProduct($db, $userId) {
    $productId = intval($_POST['productId'] ?? 0);
    
    if (empty($productId)) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        return;
    }
    
    // Verify ownership
    $product = $db->single("SELECT * FROM marketplace_products WHERE product_id = ? AND seller_id = ?", [$productId, $userId]);
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found or unauthorized']);
        return;
    }
    
    try {
        // Delete image file
        if ($product['image_url'] && file_exists(__DIR__ . '/../' . $product['image_url'])) {
            unlink(__DIR__ . '/../' . $product['image_url']);
        }
        
        $db->query("DELETE FROM marketplace_products WHERE product_id = ? AND seller_id = ?")
           ->bind(1, $productId)
           ->bind(2, $userId)
           ->execute();
        
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete product: ' . $e->getMessage()]);
    }
}

/**
 * Get products with filters
 */
function getProducts($db) {
    $search = $_GET['search'] ?? '';
    $category = $_GET['category'] ?? '';
    $productType = $_GET['productType'] ?? '';
    $region = $_GET['region'] ?? '';
    $minPrice = $_GET['minPrice'] ?? '';
    $maxPrice = $_GET['maxPrice'] ?? '';
    $sortBy = $_GET['sortBy'] ?? 'newest';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(50, max(10, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    $sellerId = $_GET['seller'] ?? '';
    
    $sql = "SELECT mp.*, u.first_name, u.last_name, u.phone as seller_phone, fp.region as seller_region 
            FROM marketplace_products mp 
            LEFT JOIN users u ON mp.seller_id = u.user_id 
            LEFT JOIN farmer_profiles fp ON u.user_id = fp.user_id 
            WHERE mp.status = 'available'";
    $countSql = "SELECT COUNT(*) as total FROM marketplace_products mp WHERE mp.status = 'available'";
    $params = [];
    $countParams = [];
    
    if (!empty($search)) {
        $sql .= " AND (mp.product_name LIKE ? OR mp.description LIKE ? OR mp.category LIKE ?)";
        $countSql .= " AND (mp.product_name LIKE ? OR mp.description LIKE ? OR mp.category LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $countParams[] = $searchTerm;
        $countParams[] = $searchTerm;
        $countParams[] = $searchTerm;
    }
    
    if (!empty($category)) {
        $sql .= " AND mp.category = ?";
        $countSql .= " AND mp.category = ?";
        $params[] = $category;
        $countParams[] = $category;
    }
    
    if (!empty($productType)) {
        $sql .= " AND mp.product_type = ?";
        $countSql .= " AND mp.product_type = ?";
        $params[] = $productType;
        $countParams[] = $productType;
    }
    
    if (!empty($region)) {
        $sql .= " AND mp.region = ?";
        $countSql .= " AND mp.region = ?";
        $params[] = $region;
        $countParams[] = $region;
    }
    
    if (!empty($sellerId)) {
        $sql .= " AND mp.seller_id = ?";
        $countSql .= " AND mp.seller_id = ?";
        $params[] = $sellerId;
        $countParams[] = $sellerId;
    }
    
    if (!empty($minPrice)) {
        $sql .= " AND mp.price >= ?";
        $countSql .= " AND mp.price >= ?";
        $params[] = floatval($minPrice);
        $countParams[] = floatval($minPrice);
    }
    
    if (!empty($maxPrice)) {
        $sql .= " AND mp.price <= ?";
        $countSql .= " AND mp.price <= ?";
        $params[] = floatval($maxPrice);
        $countParams[] = floatval($maxPrice);
    }
    
    // Sorting
    switch ($sortBy) {
        case 'price_low':
            $sql .= " ORDER BY mp.price ASC";
            break;
        case 'price_high':
            $sql .= " ORDER BY mp.price DESC";
            break;
        case 'popular':
            $sql .= " ORDER BY mp.views DESC";
            break;
        case 'oldest':
            $sql .= " ORDER BY mp.created_at ASC";
            break;
        default:
            $sql .= " ORDER BY mp.is_featured DESC, mp.created_at DESC";
    }
    
    $sql .= " LIMIT $limit OFFSET $offset";
    
    try {
        $products = $db->resultSet($sql, $params);
        $totalResult = $db->single($countSql, $countParams);
        $total = $totalResult['total'] ?? 0;
        
        echo json_encode([
            'success' => true,
            'products' => $products,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit)
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get products: ' . $e->getMessage()]);
    }
}

/**
 * Get single product details
 */
function getProductDetails($db) {
    $productId = intval($_GET['productId'] ?? 0);
    
    if (empty($productId)) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        return;
    }
    
    try {
        $product = $db->single("SELECT mp.*, u.first_name, u.last_name, u.phone as seller_phone, u.email as seller_email, 
                                       u.role as seller_role, u.profile_img_url as seller_image,
                                       fp.region as seller_region, fp.district as seller_district 
                                FROM marketplace_products mp 
                                LEFT JOIN users u ON mp.seller_id = u.user_id 
                                LEFT JOIN farmer_profiles fp ON u.user_id = fp.user_id 
                                WHERE mp.product_id = ?", [$productId]);
        
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            return;
        }
        
        // Get related products
        $related = $db->resultSet("SELECT mp.*, u.first_name, u.last_name 
                                   FROM marketplace_products mp 
                                   LEFT JOIN users u ON mp.seller_id = u.user_id 
                                   WHERE mp.status = 'available' 
                                   AND mp.product_id != ? 
                                   AND (mp.category = ? OR mp.product_type = ?) 
                                   ORDER BY RAND() LIMIT 4", 
                                   [$productId, $product['category'], $product['product_type']]);
        
        echo json_encode([
            'success' => true,
            'product' => $product,
            'related' => $related
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get product details: ' . $e->getMessage()]);
    }
}

/**
 * Get user's own products
 */
function getMyProducts($db, $userId) {
    $status = $_GET['status'] ?? '';
    
    $sql = "SELECT * FROM marketplace_products WHERE seller_id = ?";
    $params = [$userId];
    
    if (!empty($status)) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    try {
        $products = $db->resultSet($sql, $params);
        echo json_encode(['success' => true, 'products' => $products]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get products: ' . $e->getMessage()]);
    }
}

/**
 * Place an order
 */
function placeOrder($db, $userId) {
    $productId = intval($_POST['productId'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $deliveryAddress = trim($_POST['deliveryAddress'] ?? '');
    $paymentMethod = $_POST['paymentMethod'] ?? 'cash';
    $notes = trim($_POST['notes'] ?? '');
    
    if (empty($productId) || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Valid product and quantity required']);
        return;
    }
    
    // Get product details
    $product = $db->single("SELECT * FROM marketplace_products WHERE product_id = ? AND status = 'available'", [$productId]);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not available']);
        return;
    }
    
    if ($product['seller_id'] == $userId) {
        echo json_encode(['success' => false, 'message' => 'You cannot order your own product']);
        return;
    }
    
    if ($quantity > $product['quantity_available']) {
        echo json_encode(['success' => false, 'message' => 'Requested quantity not available. Only ' . $product['quantity_available'] . ' available.']);
        return;
    }
    
    $totalPrice = $product['price'] * $quantity;
    
    try {
        // Start transaction
        $db->query("START TRANSACTION")->execute();
        
        // Create order
        $db->query("INSERT INTO marketplace_orders (product_id, seller_id, buyer_id, quantity, total_price, delivery_address, payment_method, notes, order_status, payment_status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW())")
           ->bind(1, $productId)
           ->bind(2, $product['seller_id'])
           ->bind(3, $userId)
           ->bind(4, $quantity)
           ->bind(5, $totalPrice)
           ->bind(6, $deliveryAddress)
           ->bind(7, $paymentMethod)
           ->bind(8, $notes)
           ->execute();
        
        $orderId = $db->lastInsertId();
        
        // Update product quantity
        $newQuantity = $product['quantity_available'] - $quantity;
        $newStatus = $newQuantity <= 0 ? 'sold' : 'available';
        
        $db->query("UPDATE marketplace_products SET quantity_available = ?, status = ?, updated_at = NOW() WHERE product_id = ?")
           ->bind(1, max(0, $newQuantity))
           ->bind(2, $newStatus)
           ->bind(3, $productId)
           ->execute();
        
        $db->query("COMMIT")->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Order placed successfully!',
            'order_id' => $orderId,
            'total_price' => $totalPrice
        ]);
    } catch (Exception $e) {
        $db->query("ROLLBACK")->execute();
        echo json_encode(['success' => false, 'message' => 'Failed to place order: ' . $e->getMessage()]);
    }
}

/**
 * Get user's orders (as buyer)
 */
function getMyOrders($db, $userId) {
    $status = $_GET['status'] ?? '';
    
    $sql = "SELECT mo.*, mp.product_name, mp.image_url, mp.price_unit, 
                   u.first_name as seller_first, u.last_name as seller_last, u.phone as seller_phone 
            FROM marketplace_orders mo 
            JOIN marketplace_products mp ON mo.product_id = mp.product_id 
            JOIN users u ON mo.seller_id = u.user_id 
            WHERE mo.buyer_id = ?";
    $params = [$userId];
    
    if (!empty($status)) {
        $sql .= " AND mo.order_status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY mo.created_at DESC";
    
    try {
        $orders = $db->resultSet($sql, $params);
        echo json_encode(['success' => true, 'orders' => $orders]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get orders: ' . $e->getMessage()]);
    }
}

/**
 * Get orders for seller
 */
function getSellerOrders($db, $userId) {
    $status = $_GET['status'] ?? '';
    
    $sql = "SELECT mo.*, mp.product_name, mp.image_url, mp.price_unit, 
                   u.first_name as buyer_first, u.last_name as buyer_last, u.phone as buyer_phone 
            FROM marketplace_orders mo 
            JOIN marketplace_products mp ON mo.product_id = mp.product_id 
            JOIN users u ON mo.buyer_id = u.user_id 
            WHERE mo.seller_id = ?";
    $params = [$userId];
    
    if (!empty($status)) {
        $sql .= " AND mo.order_status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY mo.created_at DESC";
    
    try {
        $orders = $db->resultSet($sql, $params);
        echo json_encode(['success' => true, 'orders' => $orders]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get orders: ' . $e->getMessage()]);
    }
}

/**
 * Update order status (for seller)
 */
function updateOrderStatus($db, $userId) {
    $orderId = intval($_POST['orderId'] ?? 0);
    $orderStatus = $_POST['orderStatus'] ?? '';
    $paymentStatus = $_POST['paymentStatus'] ?? '';
    
    if (empty($orderId)) {
        echo json_encode(['success' => false, 'message' => 'Order ID is required']);
        return;
    }
    
    // Verify seller ownership
    $order = $db->single("SELECT * FROM marketplace_orders WHERE order_id = ? AND seller_id = ?", [$orderId, $userId]);
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found or unauthorized']);
        return;
    }
    
    try {
        $updates = [];
        $params = [];
        
        if (!empty($orderStatus)) {
            $updates[] = "order_status = ?";
            $params[] = $orderStatus;
        }
        
        if (!empty($paymentStatus)) {
            $updates[] = "payment_status = ?";
            $params[] = $paymentStatus;
        }
        
        if (empty($updates)) {
            echo json_encode(['success' => false, 'message' => 'No updates provided']);
            return;
        }
        
        $updates[] = "updated_at = NOW()";
        $params[] = $orderId;
        $params[] = $userId;
        
        $sql = "UPDATE marketplace_orders SET " . implode(', ', $updates) . " WHERE order_id = ? AND seller_id = ?";
        
        $query = $db->query($sql);
        foreach ($params as $i => $param) {
            $query->bind($i + 1, $param);
        }
        $query->execute();
        
        echo json_encode(['success' => true, 'message' => 'Order updated successfully!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update order: ' . $e->getMessage()]);
    }
}

/**
 * Cancel order (for buyer)
 */
function cancelOrder($db, $userId) {
    $orderId = intval($_POST['orderId'] ?? 0);
    
    if (empty($orderId)) {
        echo json_encode(['success' => false, 'message' => 'Order ID is required']);
        return;
    }
    
    // Verify buyer ownership
    $order = $db->single("SELECT * FROM marketplace_orders WHERE order_id = ? AND buyer_id = ?", [$orderId, $userId]);
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found or unauthorized']);
        return;
    }
    
    if ($order['order_status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Only pending orders can be cancelled']);
        return;
    }
    
    try {
        // Start transaction
        $db->query("START TRANSACTION")->execute();
        
        // Cancel order
        $db->query("UPDATE marketplace_orders SET order_status = 'cancelled', updated_at = NOW() WHERE order_id = ?")
           ->bind(1, $orderId)
           ->execute();
        
        // Restore product quantity
        $db->query("UPDATE marketplace_products SET quantity_available = quantity_available + ?, status = 'available', updated_at = NOW() WHERE product_id = ?")
           ->bind(1, $order['quantity'])
           ->bind(2, $order['product_id'])
           ->execute();
        
        $db->query("COMMIT")->execute();
        
        echo json_encode(['success' => true, 'message' => 'Order cancelled successfully!']);
    } catch (Exception $e) {
        $db->query("ROLLBACK")->execute();
        echo json_encode(['success' => false, 'message' => 'Failed to cancel order: ' . $e->getMessage()]);
    }
}

/**
 * Get market prices
 */
function getMarketPrices($db) {
    $cropName = $_GET['crop'] ?? '';
    $region = $_GET['region'] ?? '';
    
    $sql = "SELECT * FROM market_prices WHERE 1=1";
    $params = [];
    
    if (!empty($cropName)) {
        $sql .= " AND crop_name LIKE ?";
        $params[] = "%$cropName%";
    }
    
    if (!empty($region)) {
        $sql .= " AND region = ?";
        $params[] = $region;
    }
    
    $sql .= " ORDER BY recorded_date DESC LIMIT 50";
    
    try {
        $prices = $db->resultSet($sql, $params);
        echo json_encode(['success' => true, 'prices' => $prices]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get market prices: ' . $e->getMessage()]);
    }
}

/**
 * Get product categories
 */
function getCategories($db) {
    try {
        $categories = $db->resultSet("SELECT DISTINCT category FROM marketplace_products WHERE category IS NOT NULL AND category != '' ORDER BY category");
        echo json_encode(['success' => true, 'categories' => array_column($categories, 'category')]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get categories']);
    }
}

/**
 * Get regions
 */
function getRegions($db) {
    try {
        $regions = $db->resultSet("SELECT DISTINCT region FROM marketplace_products WHERE region IS NOT NULL AND region != '' ORDER BY region");
        echo json_encode(['success' => true, 'regions' => array_column($regions, 'region')]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get regions']);
    }
}

/**
 * Add a review or reply for a product
 */
function addReview($db, $userId) {
    $productId = intval($_POST['productId'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $reviewText = trim($_POST['reviewText'] ?? '');
    
    if (empty($productId)) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        return;
    }
    
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
        return;
    }
    
    // Check if user already reviewed this product
    $existing = $db->single("SELECT review_id FROM product_reviews WHERE product_id = ? AND user_id = ? AND parent_review_id IS NULL", [$productId, $userId]);
    if ($existing) {
        echo json_encode(['success' => false, 'message' => 'You have already reviewed this product']);
        return;
    }
    
    // Check if user purchased this product (verified purchase)
    $purchased = $db->single("SELECT order_id FROM marketplace_orders WHERE product_id = ? AND buyer_id = ? AND order_status = 'delivered'", [$productId, $userId]);
    $isVerifiedPurchase = $purchased ? 1 : 0;
    
    // Handle image uploads
    $images = [];
    if (isset($_FILES['reviewImages'])) {
        $uploadDir = __DIR__ . '/../public/uploads/reviews/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $files = $_FILES['reviewImages'];
        $fileCount = is_array($files['name']) ? count($files['name']) : 1;
        
        for ($i = 0; $i < min($fileCount, 5); $i++) {
            $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $fileName = is_array($files['name']) ? $files['name'][$i] : $files['name'];
            $fileError = is_array($files['error']) ? $files['error'][$i] : $files['error'];
            
            if ($fileError === UPLOAD_ERR_OK) {
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($fileExt, $allowedExts)) {
                    $newFileName = 'review_' . $userId . '_' . time() . '_' . $i . '.' . $fileExt;
                    $filePath = $uploadDir . $newFileName;
                    
                    if (move_uploaded_file($tmpName, $filePath)) {
                        $images[] = 'public/uploads/reviews/' . $newFileName;
                    }
                }
            }
        }
    }
    
    $imagesJson = !empty($images) ? json_encode($images) : null;
    
    try {
        $db->query("INSERT INTO product_reviews (product_id, user_id, rating, review_text, images, is_verified_purchase) VALUES (?, ?, ?, ?, ?, ?)")
           ->bind(1, $productId)
           ->bind(2, $userId)
           ->bind(3, $rating)
           ->bind(4, $reviewText)
           ->bind(5, $imagesJson)
           ->bind(6, $isVerifiedPurchase)
           ->execute();
        
        // Update product average rating and review count
        updateProductRating($db, $productId);
        
        echo json_encode(['success' => true, 'message' => 'Review submitted successfully!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to submit review: ' . $e->getMessage()]);
    }
}

/**
 * Reply to a review
 */
function replyReview($db, $userId) {
    $reviewId = intval($_POST['reviewId'] ?? 0);
    $replyText = trim($_POST['replyText'] ?? '');
    
    if (empty($reviewId)) {
        echo json_encode(['success' => false, 'message' => 'Review ID is required']);
        return;
    }
    
    if (empty($replyText)) {
        echo json_encode(['success' => false, 'message' => 'Reply text is required']);
        return;
    }
    
    // Get parent review to get product_id
    $parentReview = $db->single("SELECT product_id FROM product_reviews WHERE review_id = ?", [$reviewId]);
    if (!$parentReview) {
        echo json_encode(['success' => false, 'message' => 'Review not found']);
        return;
    }
    
    try {
        $db->query("INSERT INTO product_reviews (product_id, user_id, parent_review_id, review_text) VALUES (?, ?, ?, ?)")
           ->bind(1, $parentReview['product_id'])
           ->bind(2, $userId)
           ->bind(3, $reviewId)
           ->bind(4, $replyText)
           ->execute();
        
        echo json_encode(['success' => true, 'message' => 'Reply submitted successfully!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to submit reply: ' . $e->getMessage()]);
    }
}

/**
 * Get reviews for a product
 */
function getReviews($db, $userId) {
    $productId = intval($_GET['productId'] ?? 0);
    
    if (empty($productId)) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        return;
    }
    
    try {
        // Get main reviews (not replies)
        $reviews = $db->resultSet("
            SELECT r.*, u.first_name, u.last_name, u.role, u.profile_img_url as profile_image,
                   (SELECT COUNT(*) FROM review_likes rl WHERE rl.review_id = r.review_id) as likes_count,
                   (SELECT COUNT(*) FROM review_likes rl WHERE rl.review_id = r.review_id AND rl.user_id = ?) as user_liked
            FROM product_reviews r
            JOIN users u ON r.user_id = u.user_id
            WHERE r.product_id = ? AND r.parent_review_id IS NULL AND r.status = 'active'
            ORDER BY r.created_at DESC
        ", [$userId, $productId]);
        
        // Get replies for each review
        foreach ($reviews as &$review) {
            $review['images'] = $review['images'] ? json_decode($review['images'], true) : [];
            $review['replies'] = $db->resultSet("
                SELECT r.*, u.first_name, u.last_name, u.role, u.profile_img_url as profile_image,
                       (SELECT COUNT(*) FROM review_likes rl WHERE rl.review_id = r.review_id) as likes_count,
                       (SELECT COUNT(*) FROM review_likes rl WHERE rl.review_id = r.review_id AND rl.user_id = ?) as user_liked
                FROM product_reviews r
                JOIN users u ON r.user_id = u.user_id
                WHERE r.parent_review_id = ? AND r.status = 'active'
                ORDER BY r.created_at ASC
            ", [$userId, $review['review_id']]);
            
            // Check if user voted helpful
            $helpfulVote = $db->single("SELECT is_helpful FROM review_helpfulness WHERE review_id = ? AND user_id = ?", [$review['review_id'], $userId]);
            $review['user_helpful_vote'] = $helpfulVote ? ($helpfulVote['is_helpful'] ? 'helpful' : 'not_helpful') : null;
        }
        
        // Get rating stats
        $stats = $db->single("
            SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as avg_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
            FROM product_reviews 
            WHERE product_id = ? AND parent_review_id IS NULL AND status = 'active'
        ", [$productId]);
        
        echo json_encode([
            'success' => true, 
            'reviews' => $reviews, 
            'stats' => $stats,
            'currentUserId' => $userId
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get reviews: ' . $e->getMessage()]);
    }
}

/**
 * Delete a review (only owner can delete)
 */
function deleteReview($db, $userId) {
    $reviewId = intval($_POST['reviewId'] ?? 0);
    
    if (empty($reviewId)) {
        echo json_encode(['success' => false, 'message' => 'Review ID is required']);
        return;
    }
    
    // Verify ownership
    $review = $db->single("SELECT * FROM product_reviews WHERE review_id = ? AND user_id = ?", [$reviewId, $userId]);
    if (!$review) {
        echo json_encode(['success' => false, 'message' => 'Review not found or unauthorized']);
        return;
    }
    
    try {
        // Delete associated images
        if ($review['images']) {
            $images = json_decode($review['images'], true);
            foreach ($images as $img) {
                $imgPath = __DIR__ . '/../' . $img;
                if (file_exists($imgPath)) {
                    unlink($imgPath);
                }
            }
        }
        
        // Soft delete - mark as deleted
        $db->query("UPDATE product_reviews SET status = 'deleted' WHERE review_id = ? OR parent_review_id = ?")
           ->bind(1, $reviewId)
           ->bind(2, $reviewId)
           ->execute();
        
        // Update product rating if it was a main review
        if (!$review['parent_review_id']) {
            updateProductRating($db, $review['product_id']);
        }
        
        echo json_encode(['success' => true, 'message' => 'Review deleted successfully!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete review: ' . $e->getMessage()]);
    }
}

/**
 * Like/Unlike a review
 */
function likeReview($db, $userId) {
    $reviewId = intval($_POST['reviewId'] ?? 0);
    
    if (empty($reviewId)) {
        echo json_encode(['success' => false, 'message' => 'Review ID is required']);
        return;
    }
    
    try {
        // Check if already liked
        $existing = $db->single("SELECT like_id FROM review_likes WHERE review_id = ? AND user_id = ?", [$reviewId, $userId]);
        
        if ($existing) {
            // Unlike
            $db->query("DELETE FROM review_likes WHERE review_id = ? AND user_id = ?")
               ->bind(1, $reviewId)
               ->bind(2, $userId)
               ->execute();
            echo json_encode(['success' => true, 'action' => 'unliked', 'message' => 'Like removed']);
        } else {
            // Like
            $db->query("INSERT INTO review_likes (review_id, user_id) VALUES (?, ?)")
               ->bind(1, $reviewId)
               ->bind(2, $userId)
               ->execute();
            echo json_encode(['success' => true, 'action' => 'liked', 'message' => 'Review liked']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to process like: ' . $e->getMessage()]);
    }
}

/**
 * Vote helpful/not helpful on a review
 */
function voteHelpful($db, $userId) {
    $reviewId = intval($_POST['reviewId'] ?? 0);
    $isHelpful = isset($_POST['isHelpful']) ? ($_POST['isHelpful'] === 'true' || $_POST['isHelpful'] === '1' ? 1 : 0) : null;
    
    if (empty($reviewId)) {
        echo json_encode(['success' => false, 'message' => 'Review ID is required']);
        return;
    }
    
    if ($isHelpful === null) {
        echo json_encode(['success' => false, 'message' => 'Vote type is required']);
        return;
    }
    
    try {
        // Check if already voted
        $existing = $db->single("SELECT vote_id, is_helpful FROM review_helpfulness WHERE review_id = ? AND user_id = ?", [$reviewId, $userId]);
        
        if ($existing) {
            if ($existing['is_helpful'] == $isHelpful) {
                // Same vote - remove it
                $db->query("DELETE FROM review_helpfulness WHERE review_id = ? AND user_id = ?")
                   ->bind(1, $reviewId)
                   ->bind(2, $userId)
                   ->execute();
                
                // Update count
                $field = $isHelpful ? 'helpful_count' : 'not_helpful_count';
                $db->query("UPDATE product_reviews SET $field = GREATEST(0, $field - 1) WHERE review_id = ?")
                   ->bind(1, $reviewId)
                   ->execute();
                
                echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Vote removed']);
            } else {
                // Different vote - update it
                $db->query("UPDATE review_helpfulness SET is_helpful = ? WHERE review_id = ? AND user_id = ?")
                   ->bind(1, $isHelpful)
                   ->bind(2, $reviewId)
                   ->bind(3, $userId)
                   ->execute();
                
                // Update counts
                if ($isHelpful) {
                    $db->query("UPDATE product_reviews SET helpful_count = helpful_count + 1, not_helpful_count = GREATEST(0, not_helpful_count - 1) WHERE review_id = ?")
                       ->bind(1, $reviewId)
                       ->execute();
                } else {
                    $db->query("UPDATE product_reviews SET not_helpful_count = not_helpful_count + 1, helpful_count = GREATEST(0, helpful_count - 1) WHERE review_id = ?")
                       ->bind(1, $reviewId)
                       ->execute();
                }
                
                echo json_encode(['success' => true, 'action' => 'changed', 'message' => 'Vote updated']);
            }
        } else {
            // New vote
            $db->query("INSERT INTO review_helpfulness (review_id, user_id, is_helpful) VALUES (?, ?, ?)")
               ->bind(1, $reviewId)
               ->bind(2, $userId)
               ->bind(3, $isHelpful)
               ->execute();
            
            // Update count
            $field = $isHelpful ? 'helpful_count' : 'not_helpful_count';
            $db->query("UPDATE product_reviews SET $field = $field + 1 WHERE review_id = ?")
               ->bind(1, $reviewId)
               ->execute();
            
            echo json_encode(['success' => true, 'action' => 'voted', 'message' => $isHelpful ? 'Marked as helpful' : 'Marked as not helpful']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to process vote: ' . $e->getMessage()]);
    }
}

/**
 * Update product average rating
 */
function updateProductRating($db, $productId) {
    try {
        $stats = $db->single("
            SELECT COUNT(*) as review_count, COALESCE(AVG(rating), 0) as avg_rating 
            FROM product_reviews 
            WHERE product_id = ? AND parent_review_id IS NULL AND status = 'active'
        ", [$productId]);
        
        $db->query("UPDATE marketplace_products SET average_rating = ?, review_count = ? WHERE product_id = ?")
           ->bind(1, round($stats['avg_rating'], 2))
           ->bind(2, $stats['review_count'])
           ->bind(3, $productId)
           ->execute();
    } catch (Exception $e) {
        // Silently fail
    }
}

// ==================== EXTRA FEATURES ====================

/**
 * Toggle product in wishlist
 */
function toggleWishlist($db, $userId) {
    $productId = intval($_POST['productId'] ?? 0);
    
    if (empty($productId)) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        return;
    }
    
    try {
        $existing = $db->single("SELECT wishlist_id FROM product_wishlist WHERE user_id = ? AND product_id = ?", [$userId, $productId]);
        
        if ($existing) {
            $db->query("DELETE FROM product_wishlist WHERE user_id = ? AND product_id = ?")
               ->bind(1, $userId)
               ->bind(2, $productId)
               ->execute();
            echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Removed from wishlist']);
        } else {
            $db->query("INSERT INTO product_wishlist (user_id, product_id) VALUES (?, ?)")
               ->bind(1, $userId)
               ->bind(2, $productId)
               ->execute();
            echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Added to wishlist']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update wishlist']);
    }
}

/**
 * Get user's wishlist
 */
function getWishlist($db, $userId) {
    try {
        $wishlist = $db->resultSet("
            SELECT w.wishlist_id, w.user_id as wishlist_user_id, w.product_id, 
                   w.created_at as added_at,
                   mp.product_name, mp.product_type, mp.category, mp.description,
                   mp.price, mp.price_unit, mp.quantity_available, mp.unit,
                   mp.quality_grade, mp.location, mp.region, mp.image_url,
                   mp.status, mp.is_negotiable, mp.average_rating, mp.review_count,
                   u.first_name, u.last_name, u.phone as seller_phone
            FROM product_wishlist w
            JOIN marketplace_products mp ON w.product_id = mp.product_id
            LEFT JOIN users u ON mp.seller_id = u.user_id
            WHERE w.user_id = ?
            ORDER BY w.created_at DESC
        ", [$userId]);
        
        echo json_encode(['success' => true, 'items' => $wishlist, 'count' => count($wishlist)]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get wishlist: ' . $e->getMessage()]);
    }
}

/**
 * Make an offer on a product
 */
function makeOffer($db, $userId) {
    $productId = intval($_POST['productId'] ?? 0);
    $offeredPrice = floatval($_POST['offeredPrice'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    $message = trim($_POST['message'] ?? '');
    
    if (empty($productId) || $offeredPrice <= 0) {
        echo json_encode(['success' => false, 'message' => 'Product ID and offered price are required']);
        return;
    }
    
    // Get product info
    $product = $db->single("SELECT * FROM marketplace_products WHERE product_id = ? AND status = 'available'", [$productId]);
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found or unavailable']);
        return;
    }
    
    if ($product['seller_id'] == $userId) {
        echo json_encode(['success' => false, 'message' => 'You cannot make an offer on your own product']);
        return;
    }
    
    // Check for existing pending offer
    $existing = $db->single("SELECT offer_id FROM product_offers WHERE product_id = ? AND buyer_id = ? AND status = 'pending'", [$productId, $userId]);
    if ($existing) {
        echo json_encode(['success' => false, 'message' => 'You already have a pending offer on this product']);
        return;
    }
    
    try {
        $expiresAt = date('Y-m-d H:i:s', strtotime('+48 hours'));
        
        $db->query("INSERT INTO product_offers (product_id, buyer_id, seller_id, offered_price, quantity, message, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?)")
           ->bind(1, $productId)
           ->bind(2, $userId)
           ->bind(3, $product['seller_id'])
           ->bind(4, $offeredPrice)
           ->bind(5, $quantity)
           ->bind(6, $message)
           ->bind(7, $expiresAt)
           ->execute();
        
        echo json_encode(['success' => true, 'message' => 'Offer submitted! The seller will respond within 48 hours.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to submit offer']);
    }
}

/**
 * Get offers (as buyer or seller)
 */
function getOffers($db, $userId) {
    $type = $_GET['type'] ?? 'received'; // received or sent
    
    try {
        if ($type === 'sent') {
            $offers = $db->resultSet("
                SELECT o.*, mp.product_name, mp.price as original_price, mp.image_url, mp.price_unit,
                       u.first_name as seller_first, u.last_name as seller_last, u.phone as seller_phone
                FROM product_offers o
                JOIN marketplace_products mp ON o.product_id = mp.product_id
                LEFT JOIN users u ON o.seller_id = u.user_id
                WHERE o.buyer_id = ?
                ORDER BY o.created_at DESC
            ", [$userId]);
        } else {
            $offers = $db->resultSet("
                SELECT o.*, mp.product_name, mp.price as original_price, mp.image_url, mp.price_unit,
                       u.first_name as buyer_first, u.last_name as buyer_last, u.phone as buyer_phone
                FROM product_offers o
                JOIN marketplace_products mp ON o.product_id = mp.product_id
                LEFT JOIN users u ON o.buyer_id = u.user_id
                WHERE o.seller_id = ?
                ORDER BY o.created_at DESC
            ", [$userId]);
        }
        
        echo json_encode(['success' => true, 'offers' => $offers]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get offers']);
    }
}

/**
 * Respond to an offer (accept, reject, counter)
 */
function respondOffer($db, $userId) {
    $offerId = intval($_POST['offerId'] ?? 0);
    $action = $_POST['offerAction'] ?? '';
    $counterPrice = floatval($_POST['counterPrice'] ?? 0);
    $sellerMessage = trim($_POST['sellerMessage'] ?? '');
    
    if (empty($offerId) || empty($action)) {
        echo json_encode(['success' => false, 'message' => 'Offer ID and action are required']);
        return;
    }
    
    // Verify seller ownership
    $offer = $db->single("SELECT * FROM product_offers WHERE offer_id = ? AND seller_id = ?", [$offerId, $userId]);
    if (!$offer) {
        echo json_encode(['success' => false, 'message' => 'Offer not found']);
        return;
    }
    
    if ($offer['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'This offer has already been processed']);
        return;
    }
    
    try {
        switch ($action) {
            case 'accept':
                $db->query("UPDATE product_offers SET status = 'accepted', seller_message = ?, updated_at = NOW() WHERE offer_id = ?")
                   ->bind(1, $sellerMessage)
                   ->bind(2, $offerId)
                   ->execute();
                echo json_encode(['success' => true, 'message' => 'Offer accepted! The buyer will be notified.']);
                break;
                
            case 'reject':
                $db->query("UPDATE product_offers SET status = 'rejected', seller_message = ?, updated_at = NOW() WHERE offer_id = ?")
                   ->bind(1, $sellerMessage)
                   ->bind(2, $offerId)
                   ->execute();
                echo json_encode(['success' => true, 'message' => 'Offer rejected.']);
                break;
                
            case 'counter':
                if ($counterPrice <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Counter price is required']);
                    return;
                }
                $db->query("UPDATE product_offers SET status = 'countered', counter_price = ?, seller_message = ?, updated_at = NOW() WHERE offer_id = ?")
                   ->bind(1, $counterPrice)
                   ->bind(2, $sellerMessage)
                   ->bind(3, $offerId)
                   ->execute();
                echo json_encode(['success' => true, 'message' => 'Counter offer sent!']);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to process response']);
    }
}

/**
 * Report a product
 */
function reportProduct($db, $userId) {
    $productId = intval($_POST['productId'] ?? 0);
    $reason = $_POST['reason'] ?? '';
    $description = trim($_POST['description'] ?? '');
    
    $validReasons = ['fake', 'inappropriate', 'spam', 'wrong_category', 'overpriced', 'scam', 'other'];
    
    if (empty($productId) || !in_array($reason, $validReasons)) {
        echo json_encode(['success' => false, 'message' => 'Product ID and valid reason are required']);
        return;
    }
    
    // Check if already reported
    $existing = $db->single("SELECT report_id FROM product_reports WHERE product_id = ? AND reporter_id = ? AND status = 'pending'", [$productId, $userId]);
    if ($existing) {
        echo json_encode(['success' => false, 'message' => 'You have already reported this product']);
        return;
    }
    
    try {
        $db->query("INSERT INTO product_reports (product_id, reporter_id, reason, description) VALUES (?, ?, ?, ?)")
           ->bind(1, $productId)
           ->bind(2, $userId)
           ->bind(3, $reason)
           ->bind(4, $description)
           ->execute();
        
        echo json_encode(['success' => true, 'message' => 'Report submitted. Our team will review it shortly.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to submit report']);
    }
}

/**
 * Get recently viewed products
 */
function getRecentlyViewed($db, $userId) {
    try {
        $products = $db->resultSet("
            SELECT rv.viewed_at, 
                   mp.product_id, mp.product_name, mp.product_type, mp.category,
                   mp.price, mp.price_unit, mp.quantity_available, mp.unit,
                   mp.image_url, mp.region, mp.status,
                   u.first_name, u.last_name
            FROM recently_viewed rv
            JOIN marketplace_products mp ON rv.product_id = mp.product_id
            LEFT JOIN users u ON mp.seller_id = u.user_id
            WHERE rv.user_id = ? AND mp.status = 'available'
            ORDER BY rv.viewed_at DESC
            LIMIT 12
        ", [$userId]);
        
        echo json_encode(['success' => true, 'items' => $products]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get recently viewed: ' . $e->getMessage()]);
    }
}

/**
 * Toggle product in comparison list
 */
function toggleCompare($db, $userId) {
    $productId = intval($_POST['productId'] ?? 0);
    
    if (empty($productId)) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        return;
    }
    
    try {
        $existing = $db->single("SELECT comparison_id FROM product_comparisons WHERE user_id = ? AND product_id = ?", [$userId, $productId]);
        
        if ($existing) {
            $db->query("DELETE FROM product_comparisons WHERE user_id = ? AND product_id = ?")
               ->bind(1, $userId)
               ->bind(2, $productId)
               ->execute();
            echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Removed from comparison']);
        } else {
            // Limit to 4 products
            $count = $db->single("SELECT COUNT(*) as cnt FROM product_comparisons WHERE user_id = ?", [$userId]);
            if ($count['cnt'] >= 4) {
                echo json_encode(['success' => false, 'message' => 'Maximum 4 products can be compared. Remove one first.']);
                return;
            }
            
            $db->query("INSERT INTO product_comparisons (user_id, product_id) VALUES (?, ?)")
               ->bind(1, $userId)
               ->bind(2, $productId)
               ->execute();
            echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Added to comparison']);
        }
        
        // Return updated count
        $newCount = $db->single("SELECT COUNT(*) as cnt FROM product_comparisons WHERE user_id = ?", [$userId]);
        echo json_encode(['success' => true, 'count' => $newCount['cnt']]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update comparison list']);
    }
}

/**
 * Get comparison list with full product details
 */
function getCompareList($db, $userId) {
    try {
        $products = $db->resultSet("
            SELECT mp.*, u.first_name, u.last_name, u.phone as seller_phone,
                   fp.region as seller_region
            FROM product_comparisons pc
            JOIN marketplace_products mp ON pc.product_id = mp.product_id
            LEFT JOIN users u ON mp.seller_id = u.user_id
            LEFT JOIN farmer_profiles fp ON u.user_id = fp.user_id
            WHERE pc.user_id = ?
            ORDER BY pc.added_at DESC
        ", [$userId]);
        
        echo json_encode(['success' => true, 'products' => $products, 'count' => count($products)]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get comparison list']);
    }
}

/**
 * Get seller statistics and rating
 */
function getSellerStats($db) {
    $sellerId = intval($_GET['sellerId'] ?? 0);
    
    if (empty($sellerId)) {
        echo json_encode(['success' => false, 'message' => 'Seller ID is required']);
        return;
    }
    
    try {
        // Try to get from cache table first
        $stats = $db->single("SELECT * FROM seller_stats WHERE seller_id = ?", [$sellerId]);
        
        if (!$stats) {
            // Calculate stats dynamically
            $totalProducts = $db->single("SELECT COUNT(*) as cnt FROM marketplace_products WHERE seller_id = ?", [$sellerId])['cnt'] ?? 0;
            $totalOrders = $db->single("SELECT COUNT(*) as cnt FROM marketplace_orders WHERE seller_id = ?", [$sellerId])['cnt'] ?? 0;
            $completedOrders = $db->single("SELECT COUNT(*) as cnt FROM marketplace_orders WHERE seller_id = ? AND order_status = 'delivered'", [$sellerId])['cnt'] ?? 0;
            
            // Get average rating from reviews on seller's products
            $ratingData = $db->single("
                SELECT AVG(pr.rating) as avg_rating, COUNT(pr.review_id) as total_reviews
                FROM product_reviews pr
                JOIN marketplace_products mp ON pr.product_id = mp.product_id
                WHERE mp.seller_id = ? AND pr.parent_review_id IS NULL AND pr.status = 'active'
            ", [$sellerId]);
            
            // Determine badge
            $badge = 'new';
            if ($completedOrders >= 100) $badge = 'platinum';
            elseif ($completedOrders >= 50) $badge = 'gold';
            elseif ($completedOrders >= 20) $badge = 'silver';
            elseif ($completedOrders >= 5) $badge = 'bronze';
            
            $stats = [
                'seller_id' => $sellerId,
                'total_products' => $totalProducts,
                'total_orders' => $totalOrders,
                'completed_orders' => $completedOrders,
                'average_rating' => round($ratingData['avg_rating'] ?? 0, 2),
                'total_reviews' => $ratingData['total_reviews'] ?? 0,
                'badge' => $badge,
                'response_rate' => $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 1) : 0
            ];
        }
        
        // Get seller info
        $seller = $db->single("SELECT first_name, last_name, created_at, profile_image FROM users WHERE user_id = ?", [$sellerId]);
        
        echo json_encode([
            'success' => true,
            'stats' => $stats,
            'seller' => $seller,
            'member_since' => $seller ? date('M Y', strtotime($seller['created_at'])) : null
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get seller stats']);
    }
}

/**
 * Get shareable product info
 */
function shareProduct($db) {
    $productId = intval($_GET['productId'] ?? 0);
    
    if (empty($productId)) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        return;
    }
    
    try {
        $product = $db->single("
            SELECT mp.product_id, mp.product_name, mp.price, mp.price_unit, mp.description, mp.image_url,
                   u.first_name, u.last_name
            FROM marketplace_products mp
            LEFT JOIN users u ON mp.seller_id = u.user_id
            WHERE mp.product_id = ?
        ", [$productId]);
        
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            return;
        }
        
        // Generate share URLs
        $productUrl = SITE_URL . 'marketplace?view=' . $productId;
        $shareText = urlencode($product['product_name'] . ' - ৳' . number_format($product['price'], 2) . '/' . $product['price_unit']);
        
        echo json_encode([
            'success' => true,
            'product' => $product,
            'shareUrls' => [
                'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($productUrl),
                'twitter' => 'https://twitter.com/intent/tweet?text=' . $shareText . '&url=' . urlencode($productUrl),
                'whatsapp' => 'https://wa.me/?text=' . $shareText . '%20' . urlencode($productUrl),
                'telegram' => 'https://t.me/share/url?url=' . urlencode($productUrl) . '&text=' . $shareText,
                'copy' => $productUrl
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to get share info']);
    }
}

/**
 * Check price alerts for wishlist items
 */
function checkPriceAlert($db, $userId) {
    try {
        // Get wishlist items where price dropped
        $alerts = $db->resultSet("
            SELECT w.*, mp.product_name, mp.price, mp.image_url,
                   (SELECT price FROM marketplace_products WHERE product_id = w.product_id) as current_price
            FROM product_wishlist w
            JOIN marketplace_products mp ON w.product_id = mp.product_id
            WHERE w.user_id = ? AND w.notify_price_drop = 1
        ", [$userId]);
        
        echo json_encode(['success' => true, 'alerts' => $alerts]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to check alerts']);
    }
}

/**
 * Increment views and track recently viewed (updated)
 */
function incrementViews($db, $userId) {
    $productId = intval($_POST['productId'] ?? 0);
    
    if ($productId > 0) {
        try {
            $db->query("UPDATE marketplace_products SET views = views + 1 WHERE product_id = ?")
               ->bind(1, $productId)
               ->execute();
            
            // Track recently viewed
            $db->query("INSERT INTO recently_viewed (user_id, product_id) VALUES (?, ?) 
                       ON DUPLICATE KEY UPDATE viewed_at = NOW()")
               ->bind(1, $userId)
               ->bind(2, $productId)
               ->execute();
            
            // Keep only last 20 viewed products
            $db->query("DELETE FROM recently_viewed WHERE user_id = ? AND product_id NOT IN (
                       SELECT product_id FROM (SELECT product_id FROM recently_viewed WHERE user_id = ? ORDER BY viewed_at DESC LIMIT 20) as t)")
               ->bind(1, $userId)
               ->bind(2, $userId)
               ->execute();
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false]);
        }
    } else {
        echo json_encode(['success' => false]);
    }
}
