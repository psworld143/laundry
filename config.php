<?php
/**
 * Configuration File
 * Clean and Simple - No Interference
 */

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/errors.log');

// Constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'laundry');
define('DB_USER', 'root');
define('DB_PASS', '');
define('APP_NAME', 'LaundryPro');
define('BASE_URL', '/laundry/');

// Google reCAPTCHA Configuration
define('RECAPTCHA_SITE_KEY', '6LeIxAcTAAAAAJcZVRqyHh71UMIEbQjVyyTy_0f6'); // Official v2 test key
define('RECAPTCHA_SECRET_KEY', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'); // Official v2 test key

// Load Security before session
require_once __DIR__ . '/security.php';
// Start Session Once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Apply runtime session security
$security->secureSession();

// Initialize CSRF token after session is active
$security->getCSRFToken();

// Database Connection
try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Set database connection for security class
    $security->setDatabase($db);
    
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Helper Functions
function auth() {
    return isset($_SESSION['user_id']);
}

function user() {
    return $_SESSION ?? null;
}

function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit;
}

function json_response($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function clean($data) {
    return sanitize($data, 'string');
}

function verifyRecaptcha($response) {
    if (empty($response)) {
        return false;
    }
    
    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptcha_secret = RECAPTCHA_SECRET_KEY;
    $recaptcha_response = $response;
    
    $response_data = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
    $response_keys = json_decode($response_data, true);
    
    return isset($response_keys['success']) && $response_keys['success'] === true;
}
?>

