<?php
/**
 * Multi-Factor Authentication (TOTP) Implementation
 * Requires: PHP 8.0+ with OpenSSL extension
 */

class MFA {
    private $db;
    private $security;
    
    public function __construct($db, $security) {
        $this->db = $db;
        $this->security = $security;
    }
    
    /**
     * Generate a new TOTP secret for user
     */
    public function generateSecret() {
        return trim(base32_encode(random_bytes(20)), '=');
    }
    
    /**
     * Get provisioning URI for QR code
     */
    public function getProvisioningUri($secret, $name, $issuer = 'LaundryPro') {
        return 'otpauth://totp/' . rawurlencode($issuer) . ':' . rawurlencode($name) . 
               '?secret=' . $secret . 
               '&issuer=' . rawurlencode($issuer) . 
               '&algorithm=SHA1' . 
               '&digits=6' . 
               '&period=30';
    }
    
    /**
     * Verify TOTP token
     */
    public function verifyToken($secret, $token, $window = 1) {
        $timeWindow = 30;
        $currentTime = floor(time() / $timeWindow);
        
        // Check current time and adjacent windows
        for ($i = -$window; $i <= $window; $i++) {
            $testTime = $currentTime + $i;
            $expectedToken = $this->generateTOTP($secret, $testTime);
            if (hash_equals($expectedToken, $token)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate TOTP for given time
     */
    private function generateTOTP($secret, $time) {
        $binaryTime = pack('N*', 0) . pack('N*', $time);
        $hash = hash_hmac('sha1', $binaryTime, $secret, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $binary = unpack('N', substr($hash, $offset, 4))[1];
        $code = $binary & 0x7FFFFFFF;
        return str_pad($code % 1000000, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Enable MFA for user
     */
    public function enableMFA($userId, $secret, $backupCodes = []) {
        try {
            $this->security->secureQuery(
                "UPDATE users SET mfa_secret = ?, mfa_enabled = 1, mfa_backup_codes = ? WHERE user_id = ?",
                [$secret, json_encode($backupCodes), $userId]
            );
            
            $this->logMFAEvent($userId, 'ENABLE', [
                'backup_codes_count' => count($backupCodes)
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log('MFA enable failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Disable MFA for user
     */
    public function disableMFA($userId) {
        try {
            $this->security->secureQuery(
                "UPDATE users SET mfa_secret = NULL, mfa_enabled = 0, mfa_backup_codes = NULL WHERE user_id = ?",
                [$userId]
            );
            
            $this->logMFAEvent($userId, 'DISABLE');
            
            // Clear any existing MFA sessions
            $this->clearMFASessions($userId);
            
            return true;
        } catch (Exception $e) {
            error_log('MFA disable failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify MFA token and create temporary session
     */
    public function verifyAndCreateSession($userId, $token) {
        $user = $this->security->secureQuery(
            "SELECT mfa_secret, mfa_enabled FROM users WHERE user_id = ? AND mfa_enabled = 1",
            [$userId]
        )->fetch();
        
        if (!$user || !$user['mfa_secret']) {
            return false;
        }
        
        // Check TOTP token
        if ($this->verifyToken($user['mfa_secret'], $token)) {
            $sessionId = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour
            
            $this->security->secureQuery(
                "INSERT INTO user_mfa_sessions (session_id, user_id, expires_at, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)",
                [
                    $sessionId,
                    $userId,
                    $expiresAt,
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $_SERVER['HTTP_USER_AGENT'] ?? null
                ]
            );
            
            $this->logMFAEvent($userId, 'VERIFY_SUCCESS');
            
            return $sessionId;
        }
        
        $this->logMFAEvent($userId, 'VERIFY_FAIL');
        return false;
    }
    
    /**
     * Verify backup code
     */
    public function verifyBackupCode($userId, $code) {
        $user = $this->security->secureQuery(
            "SELECT mfa_backup_codes FROM users WHERE user_id = ? AND mfa_enabled = 1",
            [$userId]
        )->fetch();
        
        if (!$user || !$user['mfa_backup_codes']) {
            return false;
        }
        
        $backupCodes = json_decode($user['mfa_backup_codes'], true);
        if (!is_array($backupCodes)) {
            return false;
        }
        
        // Find and remove the used backup code
        $key = array_search($code, $backupCodes);
        if ($key !== false) {
            unset($backupCodes[$key]);
            $backupCodes = array_values($backupCodes); // Re-index
            
            // Update remaining backup codes
            $this->security->secureQuery(
                "UPDATE users SET mfa_backup_codes = ? WHERE user_id = ?",
                [json_encode($backupCodes), $userId]
            );
            
            $this->logMFAEvent($userId, 'BACKUP_USED', [
                'remaining_codes' => count($backupCodes)
            ]);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Validate MFA session
     */
    public function validateSession($sessionId, $userId) {
        $session = $this->security->secureQuery(
            "SELECT * FROM user_mfa_sessions WHERE session_id = ? AND user_id = ? AND expires_at > NOW()",
            [$sessionId, $userId]
        )->fetch();
        
        return $session !== false;
    }
    
    /**
     * Clear MFA sessions for user
     */
    public function clearMFASessions($userId) {
        $this->security->secureQuery(
            "DELETE FROM user_mfa_sessions WHERE user_id = ?",
            [$userId]
        );
    }
    
    /**
     * Generate backup codes
     */
    public function generateBackupCodes($count = 10) {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        }
        return $codes;
    }
    
    /**
     * Log MFA events
     */
    private function logMFAEvent($userId, $eventType, $details = null) {
        $this->security->secureQuery(
            "INSERT INTO mfa_audit_log (user_id, event_type, ip_address, user_agent, details) VALUES (?, ?, ?, ?, ?)",
            [
                $userId,
                $eventType,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $details ? json_encode($details) : null
            ]
        );
    }
    
    /**
     * Check if user has MFA enabled
     */
    public function isMFAEnabled($userId) {
        try {
            // Check if mfa_enabled column exists
            $result = $this->security->secureQuery(
                "SELECT mfa_enabled FROM users WHERE user_id = ?",
                [$userId]
            )->fetch();
            
            return $result && $result['mfa_enabled'];
        } catch (Exception $e) {
            // If mfa_enabled column doesn't exist, assume MFA is disabled
            error_log('MFA column not found, assuming MFA disabled: ' . $e->getMessage());
            return false;
        }
    }
}

/**
 * Base32 encoding for TOTP secrets
 */
function base32_encode($data) {
    $base32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $output = '';
    $bits = 0;
    $value = 0;
    
    for ($i = 0; $i < strlen($data); $i++) {
        $value = ($value << 8) | ord($data[$i]);
        $bits += 8;
        
        while ($bits >= 5) {
            $output .= $base32[($value >> ($bits - 5)) & 0x1F];
            $bits -= 5;
        }
    }
    
    if ($bits > 0) {
        $output .= $base32[($value << (5 - $bits)) & 0x1F];
    }
    
    return $output;
}
?>
