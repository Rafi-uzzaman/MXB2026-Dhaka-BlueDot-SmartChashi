<?php
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'smartcashi_db');
define('DB_USER', 'root');
define('DB_PASS', '');

define('APP_NAME', 'Smart Chashi - AI Powered Smart Farming');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development');
define('APP_DEBUG', true);

define('DEFAULT_LANGUAGE', 'en');
define('SUPPORTED_LANGUAGES', ['en', 'bn']);

define('PROJECT_ROOT', dirname(__DIR__));
define('UPLOAD_DIR', PROJECT_ROOT . '/public/uploads/');
define('MAX_UPLOAD_SIZE', 50 * 1024 * 1024); // 50MB

define('SESSION_TIMEOUT', 3600);

if (session_status() === PHP_SESSION_NONE) {
    // Determine if running on HTTPS
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
               || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
               || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    
    // Get the base path for the cookie
    $cookiePath = '/';
    if (isset($_SERVER['SCRIPT_NAME'])) {
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        // Find the root of the application
        $cookiePath = preg_replace('#/(pages|ajax|api|admin-secure|agent|public|config)(/.*)?$#', '', $scriptDir);
        if (empty($cookiePath)) $cookiePath = '/';
        if ($cookiePath !== '/' && !str_ends_with($cookiePath, '/')) $cookiePath .= '/';
    }
    
    session_set_cookie_params([
        'lifetime' => SESSION_TIMEOUT,
        'path' => $cookiePath,
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Security
define('PASSWORD_MIN_LENGTH', 8);
define('CSRF_TOKEN_TIMEOUT', 3600);

// AI APIs for Disease Detection
// Google Gemini API (FREE tier: 60 requests/minute)
// Get your API key from: https://makersuite.google.com/app/apikey
define('GEMINI_API_KEY', 'YOUR_GEMINI_API_KEY_HERE');

// Plant.id API (Backup)
define('PLANTID_API_KEY', 'gKoLJOWDeGRReX1DubQNnTxg6Zaqc6OKW2o7kj3ExlvFNovma9');

// External APIs (All Free - No Credit Card Required)
// Maps: Using OpenStreetMap + Leaflet (completely free, no API key needed)
define('OPENSTREETMAP_TILE_URL', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png');
define('OPENSTREETMAP_ATTRIBUTION', '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors');

// Geocoding: Using Nominatim (free, no API key)
define('NOMINATIM_API', 'https://nominatim.openstreetmap.org');

// Weather: Using Open-Meteo (completely free, no API key needed)
// Note: OpenWeather removed - requires credit card
define('GROQ_API_KEY', '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'); // Free AI API - Get key from https://console.groq.com (no card needed)

// Weather & Environmental APIs (All Free - No API Keys Required)
define('OPEN_METEO_API', 'https://api.open-meteo.com/v1/forecast');
define('OPEN_METEO_AIR_QUALITY_API', 'https://air-quality-api.open-meteo.com/v1/air-quality');
define('NASA_EONET_API', 'https://eonet.gsfc.nasa.gov/api/v3/events');
define('RAINVIEWER_API', 'https://api.rainviewer.com/public/weather-maps.json');
define('NASA_GIBS_API', 'https://gibs.earthdata.nasa.gov/wms/epsg4326/best/wms.cgi');

// Windy Embed URL (for satellite/weather maps)
define('WINDY_EMBED_URL', 'https://embed.windy.com/embed2.html');

// Groq AI API (Free tier - for AI recommendations)
define('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');
define('GROQ_MODEL', 'llama-3.1-8b-instant');

// Email Configuration (SMTP)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'bluedot.smartchashi@gmail.com'); // Replace with your Gmail
define('SMTP_PASSWORD', 'jlqveszkzcvpukgy');     // Replace with Gmail App Password
define('SMTP_FROM_NAME', 'Smart Chashi');
define('SMTP_FROM_EMAIL', 'bluedot.smartchashi@gmail.com'); // Replace with your Gmail

// Colors
define('COLOR_PRIMARY', '#557A46');
define('COLOR_SECONDARY', '#8FBC46');
define('COLOR_ACCENT', '#FF8C00');

// Dynamic base URL - works for both localhost and hosted environments
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_path = dirname($_SERVER['SCRIPT_NAME']);
// Normalize the path to get the base directory
$base_path = rtrim(str_replace('\\', '/', $script_path), '/');
// Remove subdirectories like /pages, /ajax, /api, /admin-secure, etc.
$base_path = preg_replace('#/(pages|ajax|api|admin-secure|agent|public|config)(/.*)?$#', '', $base_path);
$base_url = $protocol . $host . $base_path . '/';

// Database Connection Class
class Database {
    private $connection;
    private $statement;
    
    public function __construct() {
        try {
            $this->connection = new PDO(
                'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch(PDOException $e) {
            die('Database Connection Error: ' . $e->getMessage());
        }
    }
    
    public function query($sql) {
        if (empty($sql)) {
            throw new Exception('SQL query cannot be empty.');
        }
        $this->statement = $this->connection->prepare($sql);
        if ($this->statement === false) {
            throw new Exception('Failed to prepare SQL statement.');
        }
        return $this;
    }
    
    public function bind($param, $value, $type = PDO::PARAM_STR) {
        $this->statement->bindValue($param, $value, $type);
        return $this;
    }
    
    public function execute() {
        if ($this->statement === null) {
            throw new Exception('No statement prepared. Call query() first.');
        }
        return $this->statement->execute();
    }
    
    public function resultSet($sql = null, $params = []) {
        if ($sql !== null) {
            $this->query($sql);
            foreach($params as $i => $value) {
                $this->bind(($i + 1), $value);
            }
        }
        $this->execute();
        return $this->statement->fetchAll();
    }
    
    public function single($sql, $params = []) {
        $this->query($sql);
        foreach($params as $i => $value) {
            $this->bind(($i + 1), $value);
        }
        $this->execute();
        return $this->statement->fetch();
    }
    
    public function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $placeholders = implode(',', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        return $this->single($sql, array_values($data));
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    public function rowCount() {
        return $this->statement->rowCount();
    }
}

// Helper function to get clean URLs
if (!function_exists('getCleanUrl')) {
    function getCleanUrl($page, $params = []) {
        global $base_url;
        $url = $base_url . $page;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        return $url;
    }
}

// Helper function to check if user is logged in
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

// Helper function to get current user
if (!function_exists('getCurrentUser')) {
    function getCurrentUser() {
        if (isLoggedIn()) {
            $db = new Database();
            return $db->single('SELECT * FROM users WHERE user_id = ?', [$_SESSION['user_id']]);
        }
        return null;
    }
}

// Helper function to redirect
if (!function_exists('redirect')) {
    function redirect($page, $params = []) {
        header('Location: ' . getCleanUrl($page, $params));
        exit;
    }
}

// Helper function to generate CSRF token
if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }
}

// Helper function to verify CSRF token
if (!function_exists('verifyCSRFToken')) {
    function verifyCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_TIMEOUT) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

// Helper function to hash password
if (!function_exists('hashPassword')) {
    function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}

// Helper function to verify password
if (!function_exists('verifyPassword')) {
    function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}

// Load language translations
require_once __DIR__ . '/languages.php';

// Load settings helper functions
require_once __DIR__ . '/settings_helper.php';
?>
