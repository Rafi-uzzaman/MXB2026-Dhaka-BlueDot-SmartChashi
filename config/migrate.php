<?php
/**
 * Database Migration Script
 * Creates all tables for Smart Chashi application
 * Database: smartcashi_db
 */

include __DIR__ . '/config.php';

// SQL for creating tables
$tables = [
    // ============================================
    // CORE USER MANAGEMENT TABLES
    // ============================================
    
    // Users Table: Core user account information
    "CREATE TABLE IF NOT EXISTS `users` (
        `user_id` INT PRIMARY KEY AUTO_INCREMENT,
        `email` VARCHAR(255) UNIQUE NOT NULL,
        `phone` VARCHAR(20) UNIQUE NOT NULL,
        `password_hash` VARCHAR(255) NOT NULL,
        `first_name` VARCHAR(100) NOT NULL,
        `last_name` VARCHAR(100),
        `role` ENUM('farmer', 'officer', 'admin') DEFAULT 'farmer',
        `is_active` BOOLEAN DEFAULT TRUE,
        `is_verified` BOOLEAN DEFAULT FALSE,
        `last_login` TIMESTAMP NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_email` (`email`),
        INDEX `idx_phone` (`phone`),
        INDEX `idx_role` (`role`),
        INDEX `idx_is_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // Farmer Profiles: Detailed profile information for farmer users
    "CREATE TABLE IF NOT EXISTS `farmer_profiles` (
        `profile_id` INT PRIMARY KEY AUTO_INCREMENT,
        `user_id` INT UNIQUE NOT NULL,
        `farm_size` DECIMAL(10, 2) COMMENT 'in acres',
        `land_size_hectares` DECIMAL(10, 2),
        `experience_level` ENUM('beginner', 'intermediate', 'advanced'),
        `primary_crops` VARCHAR(255),
        `region` VARCHAR(100),
        `district` VARCHAR(100),
        `sub_district` VARCHAR(100),
        `village` VARCHAR(100),
        `address` TEXT,
        `location_lat` DECIMAL(10, 8),
        `location_lng` DECIMAL(10, 8),
        `farming_type` ENUM('organic', 'conventional', 'mixed') DEFAULT 'conventional',
        `soil_type` VARCHAR(100),
        `irrigation_type` VARCHAR(100),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
        INDEX `idx_region` (`region`),
        INDEX `idx_farming_type` (`farming_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // Officer Profiles: Detailed profile information for agriculture officer users
    "CREATE TABLE IF NOT EXISTS `officer_profiles` (
        `profile_id` INT PRIMARY KEY AUTO_INCREMENT,
        `user_id` INT UNIQUE NOT NULL,
        `designation` VARCHAR(100),
        `department` VARCHAR(100),
        `office_location` VARCHAR(255),
        `region` VARCHAR(100),
        `district` VARCHAR(100),
        `expertise_area` TEXT,
        `license_number` VARCHAR(50),
        `joining_date` DATE,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
        INDEX `idx_region` (`region`),
        INDEX `idx_department` (`department`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // ============================================
    // CROP MANAGEMENT TABLES
    // ============================================
    
    // Crop Data: Information about crops grown by farmers
    "CREATE TABLE IF NOT EXISTS `crop_data` (
        `crop_id` INT PRIMARY KEY AUTO_INCREMENT,
        `farmer_id` INT NOT NULL,
        `crop_name` VARCHAR(100) NOT NULL,
        `crop_type` VARCHAR(100) COMMENT 'e.g., grain, vegetable, fruit',
        `variety` VARCHAR(100),
        `planting_date` DATE,
        `planted_date` DATE,
        `expected_harvest` DATE,
        `actual_harvest_date` DATE,
        `area` DECIMAL(10, 2) COMMENT 'in acres',
        `area_hectares` DECIMAL(10, 2),
        `field_location` VARCHAR(255),
        `status` ENUM('planning', 'growing', 'harvesting', 'harvested', 'completed', 'failed') DEFAULT 'planning',
        `expected_yield` DECIMAL(10, 2),
        `actual_yield` DECIMAL(10, 2),
        `notes` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`farmer_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
        INDEX `idx_farmer_id` (`farmer_id`),
        INDEX `idx_status` (`status`),
        INDEX `idx_crop_name` (`crop_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // Crop Activities: Log of activities performed on crops
    "CREATE TABLE IF NOT EXISTS `crop_activities` (
        `activity_id` INT PRIMARY KEY AUTO_INCREMENT,
        `crop_id` INT NOT NULL,
        `activity_type` ENUM('planting', 'irrigation', 'fertilization', 'pesticide', 'weeding', 'harvesting', 'other') NOT NULL,
        `activity_date` DATE NOT NULL,
        `description` TEXT,
        `cost` DECIMAL(10, 2),
        `quantity` VARCHAR(50),
        `unit` VARCHAR(20),
        `notes` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`crop_id`) REFERENCES `crop_data`(`crop_id`) ON DELETE CASCADE,
        INDEX `idx_crop_id` (`crop_id`),
        INDEX `idx_activity_type` (`activity_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // ============================================
    // DISEASE DETECTION & MANAGEMENT TABLES
    // ============================================
    
    // Disease Reports: Records of detected diseases in crops
    "CREATE TABLE IF NOT EXISTS `disease_reports` (
        `detection_id` INT PRIMARY KEY AUTO_INCREMENT,
        `report_id` INT,
        `user_id` INT NOT NULL,
        `crop_id` INT,
        `disease_name` VARCHAR(100),
        `disease_type` VARCHAR(100),
        `severity` ENUM('low', 'medium', 'high') DEFAULT 'low',
        `confidence_score` DECIMAL(5, 2),
        `image_url` VARCHAR(255),
        `symptoms` TEXT,
        `detected_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `treatment_recommended` TEXT,
        `treatment_applied` TEXT,
        `treatment_cost` DECIMAL(10, 2),
        `status` ENUM('detected', 'treating', 'cured', 'failed') DEFAULT 'detected',
        `verified_by` INT COMMENT 'officer user_id who verified',
        `verified_at` TIMESTAMP NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
        FOREIGN KEY (`crop_id`) REFERENCES `crop_data`(`crop_id`) ON DELETE SET NULL,
        FOREIGN KEY (`verified_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_crop_id` (`crop_id`),
        INDEX `idx_severity` (`severity`),
        INDEX `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // Disease Library: Knowledge base of diseases and their information
    "CREATE TABLE IF NOT EXISTS `disease_library` (
        `disease_id` INT PRIMARY KEY AUTO_INCREMENT,
        `disease_name` VARCHAR(100) NOT NULL,
        `disease_name_bn` VARCHAR(100),
        `common_name` VARCHAR(100),
        `scientific_name` VARCHAR(255),
        `affected_crops` TEXT,
        `symptoms` TEXT,
        `causes` TEXT,
        `prevention` TEXT,
        `treatment` TEXT,
        `organic_treatment` TEXT,
        `image_url` VARCHAR(255),
        `severity_level` ENUM('low', 'medium', 'high'),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_disease_name` (`disease_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // ============================================
    // AI CHAT & MESSAGING TABLES
    // ============================================
    
    // AI Chat Logs: Records of user interactions with AI assistant
    "CREATE TABLE IF NOT EXISTS `ai_chat_logs` (
        `log_id` INT PRIMARY KEY AUTO_INCREMENT,
        `user_id` INT NOT NULL,
        `user_message` TEXT,
        `ai_response` TEXT,
        `message_type` ENUM('general', 'crop_advice', 'disease', 'weather', 'market') DEFAULT 'general',
        `language` ENUM('bangla', 'english') DEFAULT 'english',
        `sentiment` VARCHAR(50),
        `rating` INT COMMENT '1-5 rating',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_message_type` (`message_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // Chat Messages: Direct messages between users
    "CREATE TABLE IF NOT EXISTS `chat_messages` (
        `message_id` INT PRIMARY KEY AUTO_INCREMENT,
        `sender_id` INT NOT NULL,
        `receiver_id` INT NOT NULL,
        `message` TEXT NOT NULL,
        `is_read` BOOLEAN DEFAULT FALSE,
        `read_at` TIMESTAMP NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`sender_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
        FOREIGN KEY (`receiver_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
        INDEX `idx_sender_id` (`sender_id`),
        INDEX `idx_receiver_id` (`receiver_id`),
        INDEX `idx_is_read` (`is_read`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // ============================================
    // WEATHER & ENVIRONMENT TABLES
    // ============================================
    
    // Weather Data: Current and forecast weather information
    "CREATE TABLE IF NOT EXISTS `weather_data` (
        `weather_id` INT PRIMARY KEY AUTO_INCREMENT,
        `location` VARCHAR(100),
        `region` VARCHAR(100),
        `district` VARCHAR(100),
        `latitude` DECIMAL(10, 8),
        `longitude` DECIMAL(11, 8),
        `temperature` DECIMAL(5, 2),
        `temperature_min` DECIMAL(5, 2),
        `temperature_max` DECIMAL(5, 2),
        `feels_like` DECIMAL(5, 2),
        `humidity` INT,
        `pressure` INT,
        `rainfall` DECIMAL(10, 2),
        `wind_speed` DECIMAL(5, 2),
        `wind_direction` INT,
        `cloud_coverage` INT,
        `weather_condition` VARCHAR(100),
        `weather_description` TEXT,
        `uv_index` INT,
        `visibility` INT,
        `recorded_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `forecast_date` DATE,
        `is_forecast` BOOLEAN DEFAULT FALSE,
        INDEX `idx_location` (`location`),
        INDEX `idx_recorded_date` (`recorded_date`),
        INDEX `idx_forecast_date` (`forecast_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // Weather Alerts: Severe weather alerts for regions
    "CREATE TABLE IF NOT EXISTS `weather_alerts` (
        `alert_id` INT PRIMARY KEY AUTO_INCREMENT,
        `region` VARCHAR(100),
        `district` VARCHAR(100),
        `alert_type` ENUM('storm', 'flood', 'drought', 'heatwave', 'frost', 'cyclone', 'heavy_rain') NOT NULL,
        `severity` ENUM('low', 'medium', 'high', 'extreme') DEFAULT 'medium',
        `title` VARCHAR(255),
        `description` TEXT,
        `start_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `end_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `is_active` BOOLEAN DEFAULT TRUE,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_region` (`region`),
        INDEX `idx_is_active` (`is_active`),
        INDEX `idx_severity` (`severity`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // ============================================
    // MARKET & PRICING TABLES
    // ============================================
    
    // Market Prices: Current market prices for agricultural products
    "CREATE TABLE IF NOT EXISTS `market_prices` (
        `price_id` INT PRIMARY KEY AUTO_INCREMENT,
        `crop_name` VARCHAR(100) NOT NULL,
        `crop_type` VARCHAR(100),
        `variety` VARCHAR(100),
        `market_location` VARCHAR(100),
        `district` VARCHAR(100),
        `region` VARCHAR(100),
        `price_per_unit` DECIMAL(10, 2) NOT NULL,
        `unit_type` VARCHAR(20) DEFAULT 'kg',
        `min_price` DECIMAL(10, 2),
        `max_price` DECIMAL(10, 2),
        `avg_price` DECIMAL(10, 2),
        `quality_grade` ENUM('A', 'B', 'C', 'standard') DEFAULT 'standard',
        `demand_level` ENUM('low', 'medium', 'high') DEFAULT 'medium',
        `supply_level` ENUM('low', 'medium', 'high') DEFAULT 'medium',
        `recorded_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `price_date` DATE,
        INDEX `idx_crop_name` (`crop_name`),
        INDEX `idx_market_location` (`market_location`),
        INDEX `idx_price_date` (`price_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // ============================================
    // NOTIFICATION & ALERT TABLES
    // ============================================
    
    // Alerts: User alerts and notifications
    "CREATE TABLE IF NOT EXISTS `alerts` (
        `alert_id` INT PRIMARY KEY AUTO_INCREMENT,
        `user_id` INT NOT NULL,
        `alert_type` ENUM('weather', 'disease', 'market', 'system', 'advisory', 'crop', 'community') DEFAULT 'system',
        `title` VARCHAR(255),
        `message` TEXT,
        `priority` ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low',
        `category` VARCHAR(100),
        `action_url` VARCHAR(255),
        `is_read` BOOLEAN DEFAULT FALSE,
        `read_at` TIMESTAMP NULL,
        `sent_via` ENUM('app', 'email', 'sms', 'all') DEFAULT 'app',
        `created_by` INT COMMENT 'officer/admin who created alert',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `expires_at` TIMESTAMP NULL DEFAULT NULL,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
        FOREIGN KEY (`created_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_alert_type` (`alert_type`),
        INDEX `idx_is_read` (`is_read`),
        INDEX `idx_priority` (`priority`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // Notification Preferences: User notification settings
    "CREATE TABLE IF NOT EXISTS `notification_preferences` (
        `preference_id` INT PRIMARY KEY AUTO_INCREMENT,
        `user_id` INT UNIQUE NOT NULL,
        `weather_alerts` BOOLEAN DEFAULT TRUE,
        `disease_alerts` BOOLEAN DEFAULT TRUE,
        `market_alerts` BOOLEAN DEFAULT TRUE,
        `community_alerts` BOOLEAN DEFAULT TRUE,
        `email_notifications` BOOLEAN DEFAULT TRUE,
        `sms_notifications` BOOLEAN DEFAULT FALSE,
        `push_notifications` BOOLEAN DEFAULT TRUE,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // ============================================
    // COMMUNITY & SOCIAL TABLES
    // ============================================
    
    // Community Posts: Posts in the community forum
    "CREATE TABLE IF NOT EXISTS `community_posts` (
        `post_id` INT PRIMARY KEY AUTO_INCREMENT,
        `user_id` INT NOT NULL,
        `title` VARCHAR(255),
        `content` TEXT,
        `category` VARCHAR(100),
        `post_type` ENUM('question', 'discussion', 'tip', 'success_story', 'problem') DEFAULT 'discussion',
        `image_url` VARCHAR(255),
        `likes` INT DEFAULT 0,
        `views` INT DEFAULT 0,
        `is_pinned` BOOLEAN DEFAULT FALSE,
        `is_featured` BOOLEAN DEFAULT FALSE,
        `is_approved` BOOLEAN DEFAULT TRUE,
        `approved_by` INT,
        `tags` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
        FOREIGN KEY (`approved_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_category` (`category`),
        INDEX `idx_is_approved` (`is_approved`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // Post Comments: Comments on community posts
    "CREATE TABLE IF NOT EXISTS `post_comments` (
        `comment_id` INT PRIMARY KEY AUTO_INCREMENT,
        `post_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `parent_comment_id` INT,
        `comment` TEXT NOT NULL,
        `likes` INT DEFAULT 0,
        `is_approved` BOOLEAN DEFAULT TRUE,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`post_id`) REFERENCES `community_posts`(`post_id`) ON DELETE CASCADE,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
        FOREIGN KEY (`parent_comment_id`) REFERENCES `post_comments`(`comment_id`) ON DELETE CASCADE,
        INDEX `idx_post_id` (`post_id`),
        INDEX `idx_user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // Post Likes: Likes on community posts
    "CREATE TABLE IF NOT EXISTS `post_likes` (
        `like_id` INT PRIMARY KEY AUTO_INCREMENT,
        `post_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_post_like` (`post_id`, `user_id`),
        FOREIGN KEY (`post_id`) REFERENCES `community_posts`(`post_id`) ON DELETE CASCADE,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
        INDEX `idx_post_id` (`post_id`),
        INDEX `idx_user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // Comment Likes: Likes on post comments
    "CREATE TABLE IF NOT EXISTS `comment_likes` (
        `like_id` INT PRIMARY KEY AUTO_INCREMENT,
        `comment_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_comment_like` (`comment_id`, `user_id`),
        FOREIGN KEY (`comment_id`) REFERENCES `post_comments`(`comment_id`) ON DELETE CASCADE,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
        INDEX `idx_comment_id` (`comment_id`),
        INDEX `idx_user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // ============================================
    // RECOMMENDATIONS & ADVISORIES TABLES
    // ============================================
    
    // Fertilizer Recommendations: Fertilizer recommendations for crops
    "CREATE TABLE IF NOT EXISTS `fertilizer_recommendations` (
        `recommendation_id` INT PRIMARY KEY AUTO_INCREMENT,
        `crop_id` INT NOT NULL,
        `recommended_by` INT COMMENT 'officer user_id',
        `fertilizer_type` VARCHAR(100),
        `fertilizer_name` VARCHAR(255),
        `quantity_kg` DECIMAL(10, 2),
        `application_date` DATE,
        `application_method` VARCHAR(100),
        `frequency` VARCHAR(50),
        `duration_days` INT,
        `cost_estimate` DECIMAL(10, 2),
        `reason` TEXT,
        `benefits` TEXT,
        `precautions` TEXT,
        `is_organic` BOOLEAN DEFAULT FALSE,
        `status` ENUM('recommended', 'applied', 'declined') DEFAULT 'recommended',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`crop_id`) REFERENCES `crop_data`(`crop_id`) ON DELETE CASCADE,
        FOREIGN KEY (`recommended_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
        INDEX `idx_crop_id` (`crop_id`),
        INDEX `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // Advisories: Agricultural advisories from officers
    "CREATE TABLE IF NOT EXISTS `advisories` (
        `advisory_id` INT PRIMARY KEY AUTO_INCREMENT,
        `created_by` INT NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `content` TEXT NOT NULL,
        `advisory_type` ENUM('general', 'weather', 'seasonal', 'pest_control', 'irrigation', 'market') DEFAULT 'general',
        `target_crops` VARCHAR(255),
        `target_region` VARCHAR(100),
        `priority` ENUM('low', 'medium', 'high') DEFAULT 'medium',
        `valid_from` DATE,
        `valid_to` DATE,
        `is_active` BOOLEAN DEFAULT TRUE,
        `views` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`created_by`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
        INDEX `idx_advisory_type` (`advisory_type`),
        INDEX `idx_is_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // AI Recommendations: AI-generated recommendations
    "CREATE TABLE IF NOT EXISTS `ai_recommendations` (
        `recommendation_id` INT PRIMARY KEY AUTO_INCREMENT,
        `user_id` INT NOT NULL,
        `crop_id` INT,
        `recommendation_type` ENUM('crop_selection', 'planting_time', 'irrigation', 'fertilizer', 'pest_control', 'harvesting') NOT NULL,
        `recommendation` TEXT NOT NULL,
        `confidence_score` DECIMAL(5, 2),
        `based_on` TEXT COMMENT 'weather, soil, history, etc',
        `is_accepted` BOOLEAN,
        `feedback` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
        FOREIGN KEY (`crop_id`) REFERENCES `crop_data`(`crop_id`) ON DELETE CASCADE,
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_recommendation_type` (`recommendation_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // ============================================
    // MARKETPLACE TABLES
    // ============================================
    
    // Marketplace Products: Products listed for sale
    "CREATE TABLE IF NOT EXISTS `marketplace_products` (
        `product_id` INT PRIMARY KEY AUTO_INCREMENT,
        `seller_id` INT NOT NULL,
        `product_name` VARCHAR(100) NOT NULL,
        `product_type` ENUM('crop', 'seed', 'fertilizer', 'equipment', 'service', 'other') DEFAULT 'crop',
        `category` VARCHAR(100),
        `description` TEXT,
        `price` DECIMAL(10, 2) NOT NULL,
        `price_unit` VARCHAR(20) DEFAULT 'kg',
        `quantity_available` INT,
        `unit` VARCHAR(20),
        `quality_grade` ENUM('A', 'B', 'C', 'standard'),
        `location` VARCHAR(255),
        `district` VARCHAR(100),
        `region` VARCHAR(100),
        `image_url` VARCHAR(255),
        `images` TEXT COMMENT 'JSON array of image URLs',
        `contact_phone` VARCHAR(20),
        `contact_email` VARCHAR(255),
        `status` ENUM('available', 'sold', 'pending', 'expired') DEFAULT 'available',
        `views` INT DEFAULT 0,
        `is_featured` BOOLEAN DEFAULT FALSE,
        `is_verified` BOOLEAN DEFAULT FALSE,
        `verified_by` INT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `expires_at` TIMESTAMP NULL DEFAULT NULL,
        FOREIGN KEY (`seller_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
        FOREIGN KEY (`verified_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
        INDEX `idx_seller_id` (`seller_id`),
        INDEX `idx_product_type` (`product_type`),
        INDEX `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // Product Inquiries: Inquiries from buyers
    "CREATE TABLE IF NOT EXISTS `product_inquiries` (
        `inquiry_id` INT PRIMARY KEY AUTO_INCREMENT,
        `product_id` INT NOT NULL,
        `buyer_id` INT NOT NULL,
        `message` TEXT,
        `inquiry_type` ENUM('price', 'availability', 'quality', 'delivery', 'general') DEFAULT 'general',
        `status` ENUM('pending', 'responded', 'closed') DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`product_id`) REFERENCES `marketplace_products`(`product_id`) ON DELETE CASCADE,
        FOREIGN KEY (`buyer_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
        INDEX `idx_product_id` (`product_id`),
        INDEX `idx_buyer_id` (`buyer_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // Marketplace Orders: Orders placed in marketplace
    "CREATE TABLE IF NOT EXISTS `marketplace_orders` (
        `order_id` INT PRIMARY KEY AUTO_INCREMENT,
        `product_id` INT NOT NULL,
        `seller_id` INT NOT NULL,
        `buyer_id` INT NOT NULL,
        `quantity` INT NOT NULL,
        `total_price` DECIMAL(10, 2) NOT NULL,
        `delivery_address` TEXT,
        `payment_method` ENUM('cash', 'bkash', 'nagad', 'bank', 'other') DEFAULT 'cash',
        `order_status` ENUM('pending', 'confirmed', 'delivered', 'cancelled') DEFAULT 'pending',
        `payment_status` ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
        `notes` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`product_id`) REFERENCES `marketplace_products`(`product_id`) ON DELETE CASCADE,
        FOREIGN KEY (`seller_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
        FOREIGN KEY (`buyer_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
        INDEX `idx_seller_id` (`seller_id`),
        INDEX `idx_buyer_id` (`buyer_id`),
        INDEX `idx_order_status` (`order_status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // ============================================
    // EDUCATION & CONTENT TABLES
    // ============================================
    
    // Video Content: Educational videos
    "CREATE TABLE IF NOT EXISTS `video_content` (
        `video_id` INT PRIMARY KEY AUTO_INCREMENT,
        `title` VARCHAR(255) NOT NULL,
        `title_bn` VARCHAR(255),
        `description` TEXT,
        `video_url` VARCHAR(255) NOT NULL,
        `thumbnail_url` VARCHAR(255),
        `duration_seconds` INT,
        `category` VARCHAR(100),
        `tags` TEXT,
        `language` ENUM('english', 'bangla', 'both') DEFAULT 'both',
        `target_audience` ENUM('beginner', 'intermediate', 'advanced', 'all') DEFAULT 'all',
        `views` INT DEFAULT 0,
        `likes` INT DEFAULT 0,
        `is_featured` BOOLEAN DEFAULT FALSE,
        `uploaded_by` INT,
        `uploaded_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
        INDEX `idx_category` (`category`),
        INDEX `idx_is_featured` (`is_featured`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // Articles: Blog articles and educational content
    "CREATE TABLE IF NOT EXISTS `articles` (
        `article_id` INT PRIMARY KEY AUTO_INCREMENT,
        `author_id` INT NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `title_bn` VARCHAR(255),
        `content` TEXT NOT NULL,
        `excerpt` TEXT,
        `category` VARCHAR(100),
        `tags` TEXT,
        `featured_image` VARCHAR(255),
        `language` ENUM('english', 'bangla', 'both') DEFAULT 'both',
        `views` INT DEFAULT 0,
        `likes` INT DEFAULT 0,
        `is_published` BOOLEAN DEFAULT FALSE,
        `published_at` TIMESTAMP NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`author_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
        INDEX `idx_author_id` (`author_id`),
        INDEX `idx_category` (`category`),
        INDEX `idx_is_published` (`is_published`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // ============================================
    // FIELD VISITS & OFFICER ACTIVITIES TABLES
    // ============================================
    
    // Field Visits: Records of officer field visits
    "CREATE TABLE IF NOT EXISTS `field_visits` (
        `visit_id` INT PRIMARY KEY AUTO_INCREMENT,
        `officer_id` INT NOT NULL,
        `farmer_id` INT NOT NULL,
        `visit_date` DATE NOT NULL,
        `visit_time` TIME,
        `purpose` TEXT,
        `observations` TEXT,
        `recommendations` TEXT,
        `follow_up_required` BOOLEAN DEFAULT FALSE,
        `follow_up_date` DATE,
        `status` ENUM('scheduled', 'completed', 'cancelled', 'postponed') DEFAULT 'scheduled',
        `report_file` VARCHAR(255),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`officer_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
        FOREIGN KEY (`farmer_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
        INDEX `idx_officer_id` (`officer_id`),
        INDEX `idx_farmer_id` (`farmer_id`),
        INDEX `idx_visit_date` (`visit_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // ============================================
    // SYSTEM & ADMIN TABLES
    // ============================================
    
    // System Logs: System activity and action logs
    "CREATE TABLE IF NOT EXISTS `system_logs` (
        `log_id` INT PRIMARY KEY AUTO_INCREMENT,
        `user_id` INT,
        `action` VARCHAR(100),
        `entity_type` VARCHAR(50),
        `entity_id` INT,
        `ip_address` VARCHAR(45),
        `user_agent` TEXT,
        `details` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_action` (`action`),
        INDEX `idx_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // File Uploads: Records of uploaded files
    "CREATE TABLE IF NOT EXISTS `file_uploads` (
        `file_id` INT PRIMARY KEY AUTO_INCREMENT,
        `user_id` INT NOT NULL,
        `file_name` VARCHAR(255) NOT NULL,
        `file_path` VARCHAR(255) NOT NULL,
        `file_type` VARCHAR(50),
        `file_size` INT,
        `entity_type` VARCHAR(50) COMMENT 'crop, disease, post, etc',
        `entity_id` INT,
        `is_public` BOOLEAN DEFAULT FALSE,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_entity_type` (`entity_type`),
        INDEX `idx_entity_id` (`entity_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // User Sessions: User session information
    "CREATE TABLE IF NOT EXISTS `user_sessions` (
        `session_id` VARCHAR(128) PRIMARY KEY,
        `user_id` INT NOT NULL,
        `ip_address` VARCHAR(45),
        `user_agent` TEXT,
        `last_activity` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `expires_at` TIMESTAMP NULL DEFAULT NULL,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_expires_at` (`expires_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    
    // Settings: Application configuration settings
    "CREATE TABLE IF NOT EXISTS `settings` (
        `setting_id` INT PRIMARY KEY AUTO_INCREMENT,
        `setting_key` VARCHAR(100) UNIQUE NOT NULL,
        `setting_value` TEXT,
        `setting_type` VARCHAR(50),
        `description` TEXT,
        `is_public` BOOLEAN DEFAULT FALSE,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_setting_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
];

// Initialize database connection
try {
    // Connect to MySQL server
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Use the database
    $pdo->exec("USE `" . DB_NAME . "`");
    
    // Create tables
    $created_tables = 0;
    foreach ($tables as $sql) {
        try {
            $pdo->exec($sql);
            $created_tables++;
        } catch(PDOException $e) {
            echo "Error creating table: " . $e->getMessage() . "\n";
        }
    }
    
    echo "✓ Database migration completed successfully!\n";
    echo "✓ Tables created: " . $created_tables . " of " . count($tables) . "\n";
    
} catch(PDOException $e) {
    echo "✗ Database connection error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
