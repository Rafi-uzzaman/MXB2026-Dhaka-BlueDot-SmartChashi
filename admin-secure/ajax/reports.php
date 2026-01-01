<?php
/**
 * Reports AJAX Handler
 * Handles report generation, download, and management
 */

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';

// Helper function for JSON responses (define early)
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Error handling with logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../error.log');

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error: $errstr in $errfile on line $errline");
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

set_exception_handler(function($exception) {
    error_log("Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
    jsonResponse(['success' => false, 'message' => 'Server error: ' . $exception->getMessage()], 500);
});

header('Content-Type: application/json');

// Authentication check - check for user_id first, then admin_id
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    jsonResponse(['error' => 'Unauthorized - No session'], 401);
}

// Check if user is admin
if (isset($_SESSION['user_id'])) {
    $db = new Database();
    $user = $db->single("SELECT role FROM users WHERE user_id = ?", [$_SESSION['user_id']]);
    if (!$user || $user['role'] !== 'admin') {
        jsonResponse(['error' => 'Unauthorized - Not admin'], 403);
    }
    $adminId = $_SESSION['user_id'];
} else {
    $adminId = $_SESSION['admin_id'];
}

$db = new Database();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// CSRF token validation for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        jsonResponse(['error' => 'Invalid CSRF token'], 403);
    }
}

// Main action router
switch ($action) {
    case 'generate_report':
        generateReport();
        break;
        
    case 'download_report':
        downloadReport();
        break;
        
    case 'delete_report':
        deleteReport();
        break;
        
    case 'create_scheduled_report':
        createScheduledReport();
        break;
        
    case 'toggle_scheduled_report':
        toggleScheduledReport();
        break;
        
    case 'delete_scheduled_report':
        deleteScheduledReport();
        break;
        
    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}

/**
 * Generate a new report
 */
function generateReport() {
    global $db, $adminId;
    
    $type = $_POST['report_type'] ?? '';
    $format = $_POST['format'] ?? 'pdf';
    $dateFrom = $_POST['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
    $dateTo = $_POST['date_to'] ?? date('Y-m-d');
    
    if (empty($type)) {
        jsonResponse(['success' => false, 'message' => 'Report type is required'], 400);
    }
    
    // Validate format
    $validFormats = ['pdf', 'csv', 'excel'];
    if (!in_array($format, $validFormats)) {
        $format = 'pdf';
    }
    
    try {
        // Get report data based on type
        $reportData = getReportData($type, $dateFrom, $dateTo);
        
        // Generate report file
        $fileName = generateReportFile($type, $format, $reportData, $dateFrom, $dateTo);
        
        // Get file size
        $filePath = __DIR__ . '/../../reports/' . date('Y-m') . '/' . $fileName;
        $fileSize = file_exists($filePath) ? filesize($filePath) : 0;
        
        // Try to save report record to database (optional - don't fail if table doesn't exist)
        try {
            $db->query("
                INSERT INTO generated_reports 
                (report_type, file_name, file_path, file_size, format, date_from, date_to, generated_by, status, created_at) 
                VALUES 
                (:type, :file_name, :file_path, :file_size, :format, :date_from, :date_to, :generated_by, 'completed', NOW())
            ");
            
            $db->bind(':type', $type);
            $db->bind(':file_name', $fileName);
            $db->bind(':file_path', 'reports/' . date('Y-m') . '/' . $fileName);
            $db->bind(':file_size', $fileSize);
            $db->bind(':format', $format);
            $db->bind(':date_from', $dateFrom);
            $db->bind(':date_to', $dateTo);
            $db->bind(':generated_by', $adminId);
            
            $db->execute();
        } catch (Exception $dbError) {
            // Log but don't fail - table might not exist
            error_log("Could not save report to database: " . $dbError->getMessage());
        }
        
        jsonResponse([
            'success' => true,
            'message' => 'Report generated successfully',
            'file_name' => $fileName,
            'file_path' => 'reports/' . date('Y-m') . '/' . $fileName
        ]);
        
    } catch (Exception $e) {
        error_log("Report generation error: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
        jsonResponse([
            'success' => false,
            'message' => 'Failed to generate report: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Get report data based on type
 */
function getReportData($type, $dateFrom, $dateTo) {
    global $db;
    
    $data = [];
    
    try {
        switch ($type) {
            case 'user_summary':
                // Total users
                try {
                    $db->query("SELECT COUNT(*) as count FROM users WHERE created_at BETWEEN :from AND :to");
                    $db->bind(':from', $dateFrom);
                    $db->bind(':to', $dateTo . ' 23:59:59');
                    $data['total_users'] = $db->single()['count'] ?? 0;
                } catch (Exception $e) {
                    $data['total_users'] = 0;
                }
                
                // Users by role
                try {
                    $db->query("SELECT role, COUNT(*) as count FROM users WHERE created_at BETWEEN :from AND :to GROUP BY role");
                    $db->bind(':from', $dateFrom);
                    $db->bind(':to', $dateTo . ' 23:59:59');
                    $data['users_by_role'] = $db->resultSet() ?? [];
                } catch (Exception $e) {
                    $data['users_by_role'] = [];
                }
                
                // Recent users
                try {
                    $db->query("SELECT user_id, first_name, last_name, email, role, created_at FROM users WHERE created_at BETWEEN :from AND :to ORDER BY created_at DESC LIMIT 100");
                    $db->bind(':from', $dateFrom);
                    $db->bind(':to', $dateTo . ' 23:59:59');
                    $data['recent_users'] = $db->resultSet() ?? [];
                } catch (Exception $e) {
                    $data['recent_users'] = [];
                }
                break;
            
        case 'security_audit':
            // Login attempts
            try {
                $db->query("SELECT COUNT(*) as count, SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful FROM login_attempts WHERE attempted_at BETWEEN :from AND :to");
                $db->bind(':from', $dateFrom);
                $db->bind(':to', $dateTo . ' 23:59:59');
                $loginStats = $db->single() ?? ['count' => 0, 'successful' => 0];
                $data['login_attempts'] = $loginStats['count'];
                $data['successful_logins'] = $loginStats['successful'];
            } catch (Exception $e) {
                $data['login_attempts'] = 0;
                $data['successful_logins'] = 0;
            }
            
            // Failed login attempts
            try {
                $db->query("SELECT ip_address, COUNT(*) as attempts FROM login_attempts WHERE status = 'failed' AND attempted_at BETWEEN :from AND :to GROUP BY ip_address ORDER BY attempts DESC LIMIT 50");
                $db->bind(':from', $dateFrom);
                $db->bind(':to', $dateTo . ' 23:59:59');
                $data['failed_attempts'] = $db->resultSet() ?? [];
            } catch (Exception $e) {
                $data['failed_attempts'] = [];
            }
            
            // Blocked IPs
            try {
                $db->query("SELECT ip_address, reason, blocked_at FROM blocked_ips WHERE blocked_at BETWEEN :from AND :to ORDER BY blocked_at DESC LIMIT 50");
                $db->bind(':from', $dateFrom);
                $db->bind(':to', $dateTo . ' 23:59:59');
                $data['blocked_ips'] = $db->resultSet() ?? [];
            } catch (Exception $e) {
                $data['blocked_ips'] = [];
            }
            break;
            
        case 'activity_log':
            // Admin activities
            try {
                $db->query("SELECT al.*, u.first_name, u.last_name FROM activity_logs al LEFT JOIN users u ON al.user_id = u.user_id WHERE al.created_at BETWEEN :from AND :to ORDER BY al.created_at DESC LIMIT 500");
                $db->bind(':from', $dateFrom);
                $db->bind(':to', $dateTo . ' 23:59:59');
                $data['activities'] = $db->resultSet() ?? [];
            } catch (Exception $e) {
                $data['activities'] = [];
            }
            
            // Activity counts by type
            try {
                $db->query("SELECT action_type, COUNT(*) as count FROM activity_logs WHERE created_at BETWEEN :from AND :to GROUP BY action_type");
                $db->bind(':from', $dateFrom);
                $db->bind(':to', $dateTo . ' 23:59:59');
                $data['activity_counts'] = $db->resultSet() ?? [];
            } catch (Exception $e) {
                $data['activity_counts'] = [];
            }
            break;
            
        case 'content_overview':
            // Community posts
            try {
                $db->query("SELECT COUNT(*) as count FROM community_posts WHERE created_at BETWEEN :from AND :to");
                $db->bind(':from', $dateFrom);
                $db->bind(':to', $dateTo . ' 23:59:59');
                $data['total_posts'] = $db->single()['count'] ?? 0;
            } catch (Exception $e) {
                $data['total_posts'] = 0;
            }
            
            // Marketplace products
            try {
                $db->query("SELECT COUNT(*) as count FROM marketplace_products WHERE created_at BETWEEN :from AND :to");
                $db->bind(':from', $dateFrom);
                $db->bind(':to', $dateTo . ' 23:59:59');
                $data['total_products'] = $db->single()['count'] ?? 0;
            } catch (Exception $e) {
                $data['total_products'] = 0;
            }
            
            // Disease reports
            try {
                $db->query("SELECT COUNT(*) as count FROM disease_detections WHERE detected_at BETWEEN :from AND :to");
                $db->bind(':from', $dateFrom);
                $db->bind(':to', $dateTo . ' 23:59:59');
                $data['disease_reports'] = $db->single()['count'] ?? 0;
            } catch (Exception $e) {
                $data['disease_reports'] = 0;
            }
            break;
            
        default:
            $data['error'] = 'Invalid report type';
        }
    } catch (Exception $e) {
        error_log("Error in getReportData: " . $e->getMessage());
        $data['error'] = $e->getMessage();
    }
    
    $data['date_from'] = $dateFrom;
    $data['date_to'] = $dateTo;
    $data['generated_at'] = date('Y-m-d H:i:s');
    
    return $data;
}

/**
 * Generate report file in specified format
 */
function generateReportFile($type, $format, $data, $dateFrom, $dateTo) {
    $reportDir = __DIR__ . '/../../reports/' . date('Y-m');
    
    // Create directory if it doesn't exist
    if (!is_dir($reportDir)) {
        mkdir($reportDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d_H-i-s');
    $fileName = "report_{$type}_{$timestamp}.{$format}";
    $filePath = $reportDir . '/' . $fileName;
    
    switch ($format) {
        case 'csv':
            generateCSV($filePath, $type, $data);
            break;
            
        case 'excel':
            // For now, generate CSV (you can add Excel library later)
            $fileName = "report_{$type}_{$timestamp}.csv";
            $filePath = $reportDir . '/' . $fileName;
            generateCSV($filePath, $type, $data);
            break;
            
        case 'pdf':
        default:
            generateJSON($filePath, $type, $data);
            break;
    }
    
    return $fileName;
}

/**
 * Generate CSV report
 */
function generateCSV($filePath, $type, $data) {
    $fp = fopen($filePath, 'w');
    
    // Write header
    fputcsv($fp, ['SmartCashi Report', $type, date('Y-m-d H:i:s')]);
    fputcsv($fp, ['Date Range', $data['date_from'], 'to', $data['date_to']]);
    fputcsv($fp, []); // Empty row
    
    // Write data based on type
    switch ($type) {
        case 'user_summary':
            fputcsv($fp, ['Total Users', $data['total_users']]);
            fputcsv($fp, []);
            fputcsv($fp, ['Role', 'Count']);
            foreach ($data['users_by_role'] as $row) {
                fputcsv($fp, [$row['role'], $row['count']]);
            }
            fputcsv($fp, []);
            fputcsv($fp, ['ID', 'Name', 'Email', 'Role', 'Created']);
            foreach ($data['recent_users'] as $user) {
                fputcsv($fp, [
                    $user['user_id'],
                    $user['first_name'] . ' ' . ($user['last_name'] ?? ''),
                    $user['email'],
                    $user['role'],
                    $user['created_at']
                ]);
            }
            break;
            
        case 'security_audit':
            fputcsv($fp, ['Login Attempts', $data['login_attempts']]);
            fputcsv($fp, ['Successful Logins', $data['successful_logins']]);
            fputcsv($fp, []);
            fputcsv($fp, ['Failed Login Attempts by IP']);
            fputcsv($fp, ['IP Address', 'Attempts']);
            foreach ($data['failed_attempts'] as $row) {
                fputcsv($fp, [$row['ip_address'], $row['attempts']]);
            }
            fputcsv($fp, []);
            fputcsv($fp, ['Blocked IPs']);
            fputcsv($fp, ['IP Address', 'Reason', 'Blocked At']);
            foreach ($data['blocked_ips'] as $row) {
                fputcsv($fp, [$row['ip_address'], $row['reason'] ?? 'Multiple failed attempts', $row['blocked_at']]);
            }
            break;
            
        case 'activity_log':
            fputcsv($fp, ['Activity Type', 'Count']);
            foreach ($data['activity_counts'] as $row) {
                fputcsv($fp, [$row['action_type'], $row['count']]);
            }
            fputcsv($fp, []);
            fputcsv($fp, ['User', 'Action', 'Description', 'Date']);
            foreach ($data['activities'] as $activity) {
                fputcsv($fp, [
                    ($activity['first_name'] ?? '') . ' ' . ($activity['last_name'] ?? ''),
                    $activity['action_type'] ?? '',
                    $activity['description'] ?? '',
                    $activity['created_at']
                ]);
            }
            break;
            
        case 'content_overview':
            fputcsv($fp, ['Metric', 'Count']);
            fputcsv($fp, ['Community Posts', $data['total_posts']]);
            fputcsv($fp, ['Marketplace Products', $data['total_products']]);
            fputcsv($fp, ['Disease Reports', $data['disease_reports']]);
            break;
    }
    
    fclose($fp);
}

/**
 * Generate JSON report (placeholder for PDF)
 */
function generateJSON($filePath, $type, $data) {
    // For now, just save as JSON
    // In production, use a PDF library like TCPDF or FPDF
    $filePath = str_replace('.pdf', '.pdf.json', $filePath);
    file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
}

/**
 * Download a report
 */
function downloadReport() {
    global $db;
    
    $reportId = $_GET['report_id'] ?? '';
    
    if (empty($reportId)) {
        die('Invalid report ID');
    }
    
    // Get report info
    $db->query("SELECT * FROM generated_reports WHERE id = :id");
    $db->bind(':id', $reportId);
    $report = $db->single();
    
    if (!$report) {
        die('Report not found');
    }
    
    $filePath = __DIR__ . '/../../' . $report['file_path'];
    
    if (!file_exists($filePath)) {
        die('File not found');
    }
    
    // Determine content type
    $contentType = 'application/octet-stream';
    switch ($report['format']) {
        case 'pdf':
            $contentType = 'application/pdf';
            break;
        case 'csv':
            $contentType = 'text/csv';
            break;
        case 'excel':
            $contentType = 'application/vnd.ms-excel';
            break;
    }
    
    // Send file
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . $report['file_name'] . '"');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}

/**
 * Delete a report
 */
function deleteReport() {
    global $db;
    
    $reportId = $_POST['report_id'] ?? '';
    
    if (empty($reportId)) {
        jsonResponse(['success' => false, 'message' => 'Report ID is required'], 400);
    }
    
    try {
        // Get report info
        $db->query("SELECT * FROM generated_reports WHERE id = :id");
        $db->bind(':id', $reportId);
        $report = $db->single();
        
        if ($report) {
            // Delete file
            $filePath = __DIR__ . '/../../' . $report['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Delete from database
            $db->query("DELETE FROM generated_reports WHERE id = :id");
            $db->bind(':id', $reportId);
            $db->execute();
        }
        
        jsonResponse(['success' => true, 'message' => 'Report deleted']);
        
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Failed to delete report'], 500);
    }
}

/**
 * Create a scheduled report
 */
function createScheduledReport() {
    global $db, $adminId;
    
    $reportType = $_POST['report_type'] ?? '';
    $format = $_POST['format'] ?? 'pdf';
    $scheduleCron = $_POST['schedule_cron'] ?? '';
    $scheduleHuman = $_POST['schedule_human'] ?? '';
    $recipients = $_POST['recipients'] ?? '';
    
    if (empty($reportType) || empty($scheduleCron)) {
        jsonResponse(['success' => false, 'message' => 'Missing required fields'], 400);
    }
    
    try {
        $db->query("
            INSERT INTO scheduled_reports 
            (report_type, format, schedule_cron, schedule_human, recipients, is_enabled, created_by, created_at) 
            VALUES 
            (:type, :format, :cron, :human, :recipients, 1, :created_by, NOW())
        ");
        
        $db->bind(':type', $reportType);
        $db->bind(':format', $format);
        $db->bind(':cron', $scheduleCron);
        $db->bind(':human', $scheduleHuman);
        $db->bind(':recipients', $recipients);
        $db->bind(':created_by', $adminId);
        
        $db->execute();
        
        jsonResponse(['success' => true, 'message' => 'Report scheduled successfully']);
        
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Failed to schedule report'], 500);
    }
}

/**
 * Toggle scheduled report
 */
function toggleScheduledReport() {
    global $db;
    
    $scheduleId = $_POST['schedule_id'] ?? '';
    $isEnabled = $_POST['is_enabled'] ?? 0;
    
    if (empty($scheduleId)) {
        jsonResponse(['success' => false, 'message' => 'Schedule ID is required'], 400);
    }
    
    try {
        $db->query("UPDATE scheduled_reports SET is_enabled = :enabled WHERE id = :id");
        $db->bind(':enabled', $isEnabled);
        $db->bind(':id', $scheduleId);
        $db->execute();
        
        jsonResponse(['success' => true, 'message' => 'Report schedule updated']);
        
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Failed to update schedule'], 500);
    }
}

/**
 * Delete scheduled report
 */
function deleteScheduledReport() {
    global $db;
    
    $scheduleId = $_POST['schedule_id'] ?? '';
    
    if (empty($scheduleId)) {
        jsonResponse(['success' => false, 'message' => 'Schedule ID is required'], 400);
    }
    
    try {
        $db->query("DELETE FROM scheduled_reports WHERE id = :id");
        $db->bind(':id', $scheduleId);
        $db->execute();
        
        jsonResponse(['success' => true, 'message' => 'Scheduled report deleted']);
        
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Failed to delete schedule'], 500);
    }
}
