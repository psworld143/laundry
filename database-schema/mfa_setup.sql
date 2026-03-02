-- MFA (TOTP) tables for Laundry Management System
-- Add TOTP secret and backup codes for users

ALTER TABLE users ADD COLUMN mfa_secret VARCHAR(255) NULL COMMENT 'TOTP secret for 2FA';
ALTER TABLE users ADD COLUMN mfa_enabled TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Whether MFA is enabled';
ALTER TABLE users ADD COLUMN mfa_backup_codes TEXT NULL COMMENT 'JSON array of backup codes';

CREATE TABLE user_mfa_sessions (
    session_id VARCHAR(255) PRIMARY KEY,
    user_id INT NOT NULL,
    verified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Temporary sessions after MFA verification';

-- Add audit log for MFA events
CREATE TABLE mfa_audit_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    event_type ENUM('ENABLE', 'DISABLE', 'VERIFY_SUCCESS', 'VERIFY_FAIL', 'BACKUP_USED') NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    details JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='MFA-related security events';
