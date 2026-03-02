<?php
/**
 * Security Configuration and Middleware
 * Implements CIA Triad: Confidentiality, Integrity, Availability
 */

// Prevent direct access
if (!defined('APP_NAME')) {
    die('Direct access not allowed');
}

class Security {
    private static $instance = null;
    private $db;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Database connection will be set later
        $this->db = null;
        $this->initializeSecurity();
    }
    
    /**
     * Set database connection after it's established
     */
    public function setDatabase($db) {
        $this->db = $db;
    }
    
    /**
     * CONFIDENTIALITY: Initialize security measures
     */
    private function initializeSecurity() {
        // Set security headers first (before any output)
        $this->setSecurityHeaders();
        
        // Configure session settings only if session hasn't started
        if (session_status() === PHP_SESSION_NONE) {
            $this->configureSession();
        }
        
        // Initialize CSRF protection
        $this->initializeCSRF();
    }
    
    /**
     * CONFIDENTIALITY: Configure session settings
     */
    private function configureSession() {
        // Prevent session hijacking
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
    }
    
    /**
     * CONFIDENTIALITY: Secure session configuration (runtime)
     */
    public function secureSession() {
        // Regenerate session ID on login
        if (isset($_SESSION['user_id']) && !isset($_SESSION['session_regenerated'])) {
            session_regenerate_id(true);
            $_SESSION['session_regenerated'] = true;
        }
    }
    
    /**
     * CONFIDENTIALITY: Set security headers
     */
    private function setSecurityHeaders() {
        if (!headers_sent()) {
            // XSS Protection
            header('X-XSS-Protection: 1; mode=block');
            
            // Content Type Protection
            header('X-Content-Type-Options: nosniff');
            
            // Clickjacking Protection
            header('X-Frame-Options: SAMEORIGIN');
            
            // HSTS (HTTPS only) - 6 months with preload
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                header('Strict-Transport-Security: max-age=15768000; includeSubDomains; preload');
            } else {
                // Force HTTPS redirect in production
                if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
                    $httpsUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                    header('Location: ' . $httpsUrl, true, 301);
                    exit;
                }
            }
            
            // Content Security Policy
            header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; font-src 'self' https://cdnjs.cloudflare.com; img-src 'self' data:; connect-src 'self'");
            
            // Referrer Policy
            header('Referrer-Policy: strict-origin-when-cross-origin');
        }
    }
    
    /**
     * INTEGRITY: Initialize CSRF protection
     */
    private function initializeCSRF() {
        // Only initialize if session is active
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (!isset($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
        }
    }
    
    /**
     * INTEGRITY: Generate CSRF token
     */
    public function getCSRFToken() {
        // Ensure token exists
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * INTEGRITY: Validate CSRF token
     */
    public function validateCSRF($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * CONFIDENTIALITY: Enhanced input validation and sanitization
     */
    public function sanitizeInput($data, $type = 'string') {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        
        switch ($type) {
            case 'email':
                return filter_var(trim($data), FILTER_SANITIZE_EMAIL);
            case 'int':
                return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'url':
                return filter_var($data, FILTER_SANITIZE_URL);
            case 'html':
                return htmlspecialchars(trim($data), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            default:
                return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * INTEGRITY: Validate input data
     */
    public function validateInput($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? '';
            
            if (in_array('required', $fieldRules) && empty($value)) {
                $errors[$field] = ucfirst($field) . ' is required';
                continue;
            }
            
            foreach ($fieldRules as $rule) {
                if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = 'Invalid email format';
                }
                
                if (strpos($rule, 'min:') === 0) {
                    $min = (int)substr($rule, 4);
                    if (strlen($value) < $min) {
                        $errors[$field] = ucfirst($field) . ' must be at least ' . $min . ' characters';
                    }
                }
                
                if (strpos($rule, 'max:') === 0) {
                    $max = (int)substr($rule, 4);
                    if (strlen($value) > $max) {
                        $errors[$field] = ucfirst($field) . ' must not exceed ' . $max . ' characters';
                    }
                }
                
                if ($rule === 'phone' && !preg_match('/^[0-9+\-\s()]+$/', $value)) {
                    $errors[$field] = 'Invalid phone number format';
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * AVAILABILITY: Rate limiting
     */
    public function checkRateLimit($identifier, $limit = 10, $window = 300) {
        $key = 'rate_limit_' . md5($identifier);
        $current = $this->getRateLimitData($key);
        
        if ($current['attempts'] >= $limit && (time() - $current['first_attempt']) < $window) {
            return false;
        }
        
        $this->updateRateLimitData($key, $current);
        return true;
    }
    
    public function getRateLimitData($key) {
        // For simplicity, using session. In production, use Redis or database
        $data = $_SESSION[$key] ?? ['attempts' => 0, 'first_attempt' => time()];
        return $data;
    }
    
    private function updateRateLimitData($key, $current) {
        if (time() - $current['first_attempt'] > 300) {
            $_SESSION[$key] = ['attempts' => 1, 'first_attempt' => time()];
        } else {
            $_SESSION[$key] = ['attempts' => $current['attempts'] + 1, 'first_attempt' => $current['first_attempt']];
        }
    }
    
    /**
     * CONFIDENTIALITY: Secure password hashing with Argon2id
     */
    public function hashPassword($password) {
        // Use Argon2id with secure parameters
        $options = [
            'memory_cost' => 65536, // 64 MB
            'time_cost'   => 4,      // 4 iterations
            'threads'     => 3       // 3 threads
        ];
        
        return password_hash($password, PASSWORD_ARGON2ID, $options);
    }
    
    /**
     * CONFIDENTIALITY: Verify password and upgrade hash if needed
     */
    public function verifyPassword($password, $hash) {
        if (!password_verify($password, $hash)) {
            return false;
        }
        
        // Rehash if needed (algorithm or options changed)
        if (password_needs_rehash($hash, PASSWORD_ARGON2ID, ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 3])) {
            $newHash = $this->hashPassword($password);
            // Update hash in database if user is logged in
            if (isset($_SESSION['user_id'])) {
                try {
                    $this->secureQuery("UPDATE users SET password_hash = ? WHERE user_id = ?", [$newHash, $_SESSION['user_id']]);
                    $this->logSecurityEvent('PASSWORD_REHASHED', ['user_id' => $_SESSION['user_id']]);
                } catch (Exception $e) {
                    error_log('Password rehash failed: ' . $e->getMessage());
                }
            }
        }
        
        return true;
    }
    
    /**
     * CONFIDENTIALITY: Password strength validation
     */
    public function validatePasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        return $errors;
    }
    
    /**
     * INTEGRITY: Secure database queries
     */
    public function secureQuery($sql, $params = []) {
        if ($this->db === null) {
            throw new Exception('Database connection not established');
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Log error but don't expose details to user
            error_log('Database error: ' . $e->getMessage());
            throw new Exception('Database operation failed');
        }
    }
    
    /**
     * AVAILABILITY: Error handling
     */
    public function handleError($exception, $showDetails = false) {
        // Log the error
        error_log('Security Error: ' . $exception->getMessage());
        
        // Return generic error message to user
        if ($showDetails) {
            return 'Error: ' . $exception->getMessage();
        }
        
        return 'An error occurred. Please try again later.';
    }
    
    /**
     * CONFIDENTIALITY: Log security events
     */
    public function logSecurityEvent($event, $details = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? null,
            'event' => $event,
            'details' => $details
        ];
        
        error_log('Security Event: ' . json_encode($logEntry));
    }
    
    /**
     * AVAILABILITY: Check if user is blocked
     */
    public function isUserBlocked($identifier) {
        $key = 'blocked_' . md5($identifier);
        return isset($_SESSION[$key]) && $_SESSION[$key] > time();
    }
    
    /**
     * AVAILABILITY: Block user temporarily
     */
    public function blockUser($identifier, $duration = 900) {
        $key = 'blocked_' . md5($identifier);
        $_SESSION[$key] = time() + $duration;
        $this->logSecurityEvent('USER_BLOCKED', ['identifier' => $identifier, 'duration' => $duration]);
    }
}

// Initialize security
$security = Security::getInstance();

// Helper functions for backward compatibility
function sanitize($data, $type = 'string') {
    return Security::getInstance()->sanitizeInput($data, $type);
}

function validateCSRF($token) {
    return Security::getInstance()->validateCSRF($token);
}

function getCSRFToken() {
    return Security::getInstance()->getCSRFToken();
}
?>
