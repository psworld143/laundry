<?php
/**
 * Security Middleware for API endpoints
 * Apply to all API files for consistent security
 */

// Include security if not already loaded
if (!class_exists('Security')) {
    require_once __DIR__ . '/security.php';
}

// Include enhanced rate limiter
if (!class_exists('RateLimiter')) {
    require_once __DIR__ . '/rate_limiter.php';
}

// Include AI-WAF
if (!class_exists('AI_WAF')) {
    require_once __DIR__ . '/ai_waf.php';
}

/**
 * Apply security middleware to API requests
 */
function applySecurityMiddleware() {
    global $security, $db;
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $endpoint = $_SERVER['REQUEST_URI'] ?? '';
    $userId = auth() ? $_SESSION['user_id'] : null;
    
    // Initialize enhanced rate limiter
    $rateLimiter = new RateLimiter($db, $security);
    
    // Initialize AI-WAF
    $waf = new AI_WAF($db, $security);
    
    // Prepare request data for WAF analysis
    $requestData = [
        'uri' => $endpoint,
        'get' => $_GET,
        'post' => $_POST,
        'headers' => getallheaders(),
        'cookies' => $_COOKIE
    ];
    
    // Analyze request with AI-WAF
    $wafAnalysis = $waf->analyzeRequest($requestData);
    
    // Block if WAF detects high threat
    if ($waf->shouldBlock($wafAnalysis)) {
        $security->logSecurityEvent('WAF_REQUEST_BLOCKED', [
            'ip' => $ip,
            'score' => $wafAnalysis['score'],
            'threats' => $wafAnalysis['threats'],
            'endpoint' => $endpoint
        ]);
        
        http_response_code(403);
        json_response(false, 'Request blocked by security system');
    }
    
    // Check if IP is whitelisted
    if ($rateLimiter->isWhitelisted($ip)) {
        return true;
    }
    
    // Determine rate limit type based on endpoint
    $limitType = 'api';
    if (strpos($endpoint, '/admin/') !== false) {
        $limitType = 'admin';
    } elseif (strpos($endpoint, 'login') !== false || strpos($endpoint, 'register') !== false) {
        $limitType = 'auth';
    } elseif (strpos($endpoint, 'upload') !== false) {
        $limitType = 'upload';
    }
    
    // Enhanced rate limiting with multiple identifiers
    if (!$rateLimiter->checkLimit($ip, $limitType, $endpoint, $userId)) {
        // Get rate limit headers
        $headers = $rateLimiter->getRateLimitHeaders($ip, $limitType);
        foreach ($headers as $name => $value) {
            header($name . ': ' . $value);
        }
        
        http_response_code(429);
        json_response(false, 'Rate limit exceeded. Please try again later.');
    }
    
    // Validate request method
    $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE'];
    if (!in_array($_SERVER['REQUEST_METHOD'], $allowedMethods)) {
        http_response_code(405);
        json_response(false, 'Method not allowed');
    }
    
    // Enhanced suspicious activity detection
    $suspiciousScore = calculateSuspiciousScore();
    if ($suspiciousScore > 30) {
        $security->logSecurityEvent('SUSPICIOUS_ACTIVITY_DETECTED', [
            'ip' => $ip,
            'score' => $suspiciousScore,
            'endpoint' => $endpoint,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        // Apply adaptive rate limiting for high scores
        if (!$rateLimiter->adaptiveLimit($ip, $suspiciousScore)) {
            http_response_code(429);
            json_response(false, 'Suspicious activity detected. Request blocked.');
        }
    }
    
    // Basic bot detection (enhanced)
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (empty($userAgent) || isSuspiciousUserAgent($userAgent)) {
        $security->logSecurityEvent('SUSPICIOUS_USER_AGENT', [
            'ip' => $ip,
            'user_agent' => $userAgent,
            'endpoint' => $endpoint
        ]);
        
        // Apply stricter limits for suspicious agents
        if (!$rateLimiter->checkLimit($ip, 'auth', $endpoint, $userId)) {
            http_response_code(403);
            json_response(false, 'Access denied');
        }
    }
    
    // Validate content type for POST/PUT requests
    if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT']) && !empty($_SERVER['CONTENT_TYPE'])) {
        $allowedContentTypes = ['application/json', 'application/x-www-form-urlencoded', 'multipart/form-data'];
        $contentType = strtolower($_SERVER['CONTENT_TYPE']);
        $isValidContentType = false;
        
        foreach ($allowedContentTypes as $allowedType) {
            if (strpos($contentType, $allowedType) !== false) {
                $isValidContentType = true;
                break;
            }
        }
        
        if (!$isValidContentType) {
            http_response_code(400);
            json_response(false, 'Invalid content type');
        }
    }
    
    return true;
}

/**
 * Calculate suspicious activity score
 */
function calculateSuspiciousScore() {
    $score = 0;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // Check for common attack patterns
    $patterns = [
        '/union.*select/i',
        '/script.*alert/i',
        '/javascript:/i',
        '/<.*iframe/i',
        '/eval.*base64/i'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $_SERVER['REQUEST_URI'] ?? '')) {
            $score += 20;
        }
    }
    
    // Check for unusual headers
    $suspiciousHeaders = ['X-Forwarded-For', 'X-Real-IP', 'X-Originating-IP'];
    foreach ($suspiciousHeaders as $header) {
        if (!empty($_SERVER['HTTP_' . str_replace('-', '_', strtoupper($header))])) {
            $score += 5;
        }
    }
    
    // Check request size
    $contentLength = intval($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($contentLength > 1048576) { // > 1MB
        $score += 10;
    }
    
    // Check for rapid requests (session-based)
    $sessionKey = 'request_count_' . md5($ip);
    $count = $_SESSION[$sessionKey] ?? 0;
    $_SESSION[$sessionKey] = $count + 1;
    
    if ($count > 100) { // More than 100 requests in session
        $score += 15;
    }
    
    return $score;
}

/**
 * Enhanced suspicious user agent detection
 */
function isSuspiciousUserAgent($userAgent) {
    $suspiciousPatterns = [
        '/bot/i',
        '/crawler/i',
        '/spider/i',
        '/scraper/i',
        '/curl/i',
        '/wget/i',
        '/python/i',
        '/java/i',
        '/perl/i',
        '/php/i',
        '/scanner/i',
        '/nikto/i',
        '/sqlmap/i',
        '/nmap/i'
    ];
    
    foreach ($suspiciousPatterns as $pattern) {
        if (preg_match($pattern, $userAgent)) {
            return true;
        }
    }
    
    // Check for empty or very short user agents
    if (strlen($userAgent) < 10) {
        return true;
    }
    
    // Check for known malicious signatures
    $maliciousSignatures = [
        'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)',
        'Mozilla/3.0 (compatible; Indy Library)'
    ];
    
    return in_array($userAgent, $maliciousSignatures);
}

/**
 * Validate API authentication
 */
function validateAPIAuth() {
    // Check if user is authenticated
    if (!auth()) {
        http_response_code(401);
        json_response(false, 'Authentication required');
    }
    
    // Additional token validation can be added here
    return true;
}

/**
 * Sanitize API input data
 */
function sanitizeAPIInput($data) {
    global $security;
    
    if (is_array($data)) {
        return array_map('sanitizeAPIInput', $data);
    }
    
    // Remove any potential script tags or malicious content
    $data = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $data);
    $data = preg_replace('/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi', '', $data);
    
    return $security->sanitizeInput($data, 'string');
}

/**
 * Validate API input with rules
 */
function validateAPIInput($data, $rules) {
    global $security;
    return $security->validateInput($data, $rules);
}

/**
 * Log API security events
 */
function logAPISecurityEvent($event, $details = []) {
    global $security;
    $details['endpoint'] = $_SERVER['REQUEST_URI'] ?? '';
    $details['method'] = $_SERVER['REQUEST_METHOD'] ?? '';
    $security->logSecurityEvent('API_' . $event, $details);
}

// Auto-apply middleware for all API requests
applySecurityMiddleware();
?>
