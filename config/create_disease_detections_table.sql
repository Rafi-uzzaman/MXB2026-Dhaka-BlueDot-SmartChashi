-- Disease Detections Table
CREATE TABLE IF NOT EXISTS disease_detections (
    detection_id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    crop_id INT NOT NULL,
    disease_name VARCHAR(255) NOT NULL,
    severity ENUM('low', 'medium', 'high') NOT NULL,
    confidence DECIMAL(3,2) NOT NULL,
    treatment TEXT,
    detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (crop_id) REFERENCES crop_data(crop_id) ON DELETE CASCADE,
    INDEX idx_farmer (farmer_id),
    INDEX idx_detected_at (detected_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
