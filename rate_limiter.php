<?php
/**
 * Enhanced Rate Limiting with Per-Endpoint, User-Based, and Burst Controls
 */

class RateLimiter {
    private $db;
    private $security;
    
    // Rate limit configurations
    private $limits = [
        'global' => ['requests' => 1000, 'window' => 3600, 'burst' => 50],      // 1000/hour, 50 burst
        'auth' => ['requests' => 5, 'window' => 300, 'burst' => 2],           // 5/5min, 2 burst
        'api' => ['requests' => 60, 'window' => 60, 'burst' => 10],           // 60/min, 10 burst
        'upload' => ['requests' => 10, 'window' => 300, 'burst' => 3],         // 10/5min, 3 burst
        'admin' => ['requests' => 200, 'window' => 3600, 'burst' => 20],      // 200/hour, 20 burst
    ];
    
    public function __construct($db, $security) {
        $this->db = $db;
        $this->security = $security;
    }
    
    /**
     * Check rate limit with advanced controls
     */
    public function checkLimit($identifier, $type = 'global', $endpoint = null, $userId = null) {
        $config = $this->limits[$type] ?? $this->limits['global'];
        
        // Multiple identifiers: IP, User, Endpoint
        $identifiers = [
            'ip' => $identifier,
            'user' => $userId ? 'user_' . $userId : null,
            'endpoint' => $endpoint ? 'endpoint_' . md5($endpoint) : null
        ];
        
        foreach ($identifiers as $key => $id) {
            if (!$id) continue;
            
            // Check standard rate limit
            if (!$this->checkStandardLimit($id, $config['requests'], $config['window'])) {
                $this->security->logSecurityEvent('RATE_LIMIT_EXCEEDED', [
                    'identifier' => $id,
                    'type' => $key,
                    'limit_type' => $type,
                    'endpoint' => $endpoint
                ]);
                return false;
            }
            
            // Check burst limit
            if (!$this->checkBurstLimit($id, $config['burst'])) {
                $this->security->logSecurityEvent('BURST_LIMIT_EXCEEDED', [
                    'identifier' => $id,
                    'type' => $key,
                    'limit_type' => $type,
                    'burst_limit' => $config['burst']
                ]);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Standard sliding window rate limiting
     */
    private function checkStandardLimit($identifier, $limit, $window) {
        $key = 'rate_limit_' . md5($identifier);
        $now = time();
        
        // Clean old entries
        $this->cleanupOldEntries($key, $now - $window);
        
        // Get current requests
        $requests = $this->getRequests($key);
        
        // Check if under limit
        if (count($requests) >= $limit) {
            return false;
        }
        
        // Add current request
        $this->addRequest($key, $now);
        
        return true;
    }
    
    /**
     * Burst rate limiting (requests in last 10 seconds)
     */
    private function checkBurstLimit($identifier, $burstLimit) {
        $key = 'burst_' . md5($identifier);
        $now = time();
        $burstWindow = 10; // 10 seconds
        
        // Clean old entries
        $this->cleanupOldEntries($key, $now - $burstWindow);
        
        // Get current burst requests
        $requests = $this->getRequests($key);
        
        // Check if under burst limit
        if (count($requests) >= $burstLimit) {
            return false;
        }
        
        // Add current request
        $this->addRequest($key, $now);
        
        return true;
    }
    
    /**
     * Get requests from storage
     */
    private function getRequests($key) {
        // Try database first, fallback to session
        try {
            $stmt = $this->security->secureQuery(
                "SELECT timestamps FROM rate_limits WHERE identifier = ? AND expires_at > NOW()",
                [$key]
            );
            $result = $stmt->fetch();
            
            if ($result) {
                return json_decode($result['timestamps'], true) ?: [];
            }
        } catch (Exception $e) {
            // Fallback to session for development
            return $_SESSION[$key] ?? [];
        }
        
        return [];
    }
    
    /**
     * Add request to storage
     */
    private function addRequest($key, $timestamp) {
        $requests = $this->getRequests($key);
        $requests[] = $timestamp;
        
        try {
            // Store in database with expiration
            $expiresAt = date('Y-m-d H:i:s', $timestamp + 3600);
            $this->security->secureQuery(
                "INSERT INTO rate_limits (identifier, timestamps, expires_at) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE timestamps = ?, expires_at = ?",
                [$key, json_encode($requests), $expiresAt, json_encode($requests), $expiresAt]
            );
        } catch (Exception $e) {
            // Fallback to session for development
            $_SESSION[$key] = $requests;
        }
    }
    
    /**
     * Clean old entries
     */
    private function cleanupOldEntries($key, $cutoffTime) {
        try {
            $requests = $this->getRequests($key);
            $filtered = array_filter($requests, function($timestamp) use ($cutoffTime) {
                return $timestamp > $cutoffTime;
            });
            
            if (count($filtered) !== count($requests)) {
                $this->addRequest($key, ...$filtered);
            }
        } catch (Exception $e) {
            // Session cleanup
            if (isset($_SESSION[$key])) {
                $_SESSION[$key] = array_filter($_SESSION[$key], function($timestamp) use ($cutoffTime) {
                    return $timestamp > $cutoffTime;
                });
            }
        }
    }
    
    /**
     * Get rate limit status for headers
     */
    public function getRateLimitHeaders($identifier, $type = 'global') {
        $config = $this->limits[$type] ?? $this->limits['global'];
        $key = 'rate_limit_' . md5($identifier);
        $requests = $this->getRequests($key);
        
        return [
            'X-RateLimit-Limit' => $config['requests'],
            'X-RateLimit-Remaining' => max(0, $config['requests'] - count($requests)),
            'X-RateLimit-Reset' => time() + $config['window']
        ];
    }
    
    /**
     * Adaptive rate limiting based on suspicious patterns
     */
    public function adaptiveLimit($identifier, $score = 0) {
        // Increase limits for trusted users
        if ($score < 0) {
            return true;
        }
        
        // Decrease limits for suspicious activity
        if ($score > 50) {
            $multiplier = 0.5; // Half the normal limit
        } elseif ($score > 20) {
            $multiplier = 0.75; // 75% of normal limit
        } else {
            $multiplier = 1.0; // Normal limit
        }
        
        $config = $this->limits['global'];
        $adjustedLimit = intval($config['requests'] * $multiplier);
        
        return $this->checkStandardLimit($identifier, $adjustedLimit, $config['window']);
    }
    
    /**
     * Whitelist management
     */
    public function isWhitelisted($identifier) {
        try {
            $stmt = $this->security->secureQuery(
                "SELECT 1 FROM rate_limit_whitelist WHERE identifier = ? AND expires_at > NOW()",
                [$identifier]
            );
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Add to whitelist
     */
    public function addToWhitelist($identifier, $duration = 3600, $reason = '') {
        try {
            $expiresAt = date('Y-m-d H:i:s', time() + $duration);
            $this->security->secureQuery(
                "INSERT INTO rate_limit_whitelist (identifier, expires_at, reason) VALUES (?, ?, ?)",
                [$identifier, $expiresAt, $reason]
            );
            
            $this->security->logSecurityEvent('RATE_LIMIT_WHITELISTED', [
                'identifier' => $identifier,
                'duration' => $duration,
                'reason' => $reason
            ]);
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Create rate limits table if not exists
$createRateLimitsTable = "
CREATE TABLE IF NOT EXISTS rate_limits (
    identifier VARCHAR(255) PRIMARY KEY,
    timestamps JSON NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
";

$createWhitelistTable = "
CREATE TABLE IF NOT EXISTS rate_limit_whitelist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identifier (identifier),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
";
?>
