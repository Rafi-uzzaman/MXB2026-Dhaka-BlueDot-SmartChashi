<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display, but log

try {
    require_once __DIR__ . '/../config/config.php';

    $language = $_POST['language'] ?? $_GET['language'] ?? 'en';

    // Validate language
    if (!in_array($language, SUPPORTED_LANGUAGES)) {
        $language = DEFAULT_LANGUAGE;
    }

    // Set session and cookie
    $_SESSION['language'] = $language;
    setcookie('language', $language, time() + (365 * 24 * 60 * 60), '/', '', false, true); // 1 year, httponly

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'language' => $language,
        'message' => 'Language switched successfully'
    ]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'language' => 'en',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
