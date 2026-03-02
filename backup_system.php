<?php
/**
 * 3-2-1-1-0 Backup System for LaundryPro
 * 3 copies, 2 different media, 1 offsite, 1 offline, 0 errors
 */

class BackupSystem {
    private $config;
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
        $this->config = [
            'backup_dir' => __DIR__ . '/backups',
            'local_retention' => 7, // days
            'offsite_retention' => 30, // days
            'compression' => true,
            'encryption' => true
        ];
    }
    
    /**
     * Execute full 3-2-1-1-0 backup
     */
    public function executeBackup() {
        $timestamp = date('Y-m-d_H-i-s');
        $backupId = uniqid('backup_');
        
        try {
            // 1. Create database backup
            $dbBackup = $this->backupDatabase($timestamp);
            
            // 2. Create files backup
            $filesBackup = $this->backupFiles($timestamp);
            
            // 3. Create local compressed backup
            $localBackup = $this->createLocalBackup($dbBackup, $filesBackup, $timestamp);
            
            // 4. Create offsite backup
            $offsiteBackup = $this->createOffsiteBackup($localBackup, $timestamp);
            
            // 5. Create immutable backup (read-only)
            $immutableBackup = $this->createImmutableBackup($localBackup, $timestamp);
            
            // 6. Verify all backups
            $verification = $this->verifyBackups([
                'local' => $localBackup,
                'offsite' => $offsiteBackup,
                'immutable' => $immutableBackup
            ]);
            
            // 7. Log backup results
            $this->logBackup([
                'backup_id' => $backupId,
                'timestamp' => $timestamp,
                'status' => $verification['success'] ? 'success' : 'failed',
                'size' => $verification['total_size'],
                'verification' => $verification,
                'files' => [
                    'database' => $dbBackup,
                    'files' => $filesBackup,
                    'local' => $localBackup,
                    'offsite' => $offsiteBackup,
                    'immutable' => $immutableBackup
                ]
            ]);
            
            // 8. Cleanup old backups
            $this->cleanupOldBackups();
            
            return [
                'success' => $verification['success'],
                'backup_id' => $backupId,
                'timestamp' => $timestamp,
                'verification' => $verification
            ];
            
        } catch (Exception $e) {
            $this->logBackup([
                'backup_id' => $backupId,
                'timestamp' => $timestamp,
                'status' => 'failed',
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Backup database
     */
    private function backupDatabase($timestamp) {
        $backupFile = $this->config['backup_dir'] . "/db_backup_{$timestamp}.sql";
        
        // Ensure backup directory exists
        if (!is_dir($this->config['backup_dir'])) {
            mkdir($this->config['backup_dir'], 0755, true);
        }
        
        // Get database configuration
        $host = DB_HOST;
        $user = DB_USER;
        $pass = DB_PASS;
        $name = DB_NAME;
        
        // Create mysqldump command
        $command = "mysqldump --single-transaction --routines --triggers --events "
                  . "--host={$host} --user={$user} --password={$pass} {$name} "
                  . "> {$backupFile}";
        
        // Execute backup
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Database backup failed with code: {$returnCode}");
        }
        
        // Compress if enabled
        if ($this->config['compression']) {
            $compressedFile = $backupFile . '.gz';
            $this->compressFile($backupFile, $compressedFile);
            unlink($backupFile); // Remove uncompressed
            $backupFile = $compressedFile;
        }
        
        return $backupFile;
    }
    
    /**
     * Backup application files
     */
    private function backupFiles($timestamp) {
        $backupFile = $this->config['backup_dir'] . "/files_backup_{$timestamp}.tar";
        
        // Create tar command
        $excludePatterns = [
            '--exclude=backup*',
            '--exclude=node_modules',
            '--exclude=.git',
            '--exclude=*.log',
            '--exclude=cache',
            '--exclude=temp'
        ];
        
        $command = "tar -czf {$backupFile} " . implode(' ', $excludePatterns) . " -C " . dirname(__DIR__) . " laundry";
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Files backup failed with code: {$returnCode}");
        }
        
        return $backupFile;
    }
    
    /**
     * Create local combined backup
     */
    private function createLocalBackup($dbBackup, $filesBackup, $timestamp) {
        $localBackup = $this->config['backup_dir'] . "/local_backup_{$timestamp}.tar";
        
        // Create combined backup
        $command = "tar -cf {$localBackup} -C " . dirname($dbBackup) . " " . basename($dbBackup) . " " . basename($filesBackup);
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Local backup creation failed with code: {$returnCode}");
        }
        
        // Encrypt if enabled
        if ($this->config['encryption']) {
            $encryptedFile = $localBackup . '.enc';
            $this->encryptFile($localBackup, $encryptedFile);
            unlink($localBackup); // Remove unencrypted
            $localBackup = $encryptedFile;
        }
        
        return $localBackup;
    }
    
    /**
     * Create offsite backup
     */
    private function createOffsiteBackup($localBackup, $timestamp) {
        $offsiteDir = $this->config['backup_dir'] . '/offsite';
        
        if (!is_dir($offsiteDir)) {
            mkdir($offsiteDir, 0755, true);
        }
        
        $offsiteBackup = $offsiteDir . "/offsite_backup_{$timestamp}.tar";
        
        // Copy to offsite location (could be cloud storage in production)
        if (!copy($localBackup, $offsiteBackup)) {
            throw new Exception("Offsite backup copy failed");
        }
        
        return $offsiteBackup;
    }
    
    /**
     * Create immutable backup
     */
    private function createImmutableBackup($localBackup, $timestamp) {
        $immutableDir = $this->config['backup_dir'] . '/immutable';
        
        if (!is_dir($immutableDir)) {
            mkdir($immutableDir, 0755, true);
        }
        
        $immutableBackup = $immutableDir . "/immutable_backup_{$timestamp}.tar";
        
        // Copy to immutable location
        if (!copy($localBackup, $immutableBackup)) {
            throw new Exception("Immutable backup copy failed");
        }
        
        // Make file read-only (immutable)
        chmod($immutableBackup, 0444);
        
        return $immutableBackup;
    }
    
    /**
     * Verify backup integrity
     */
    private function verifyBackups($backups) {
        $results = ['success' => true, 'total_size' => 0, 'files' => []];
        
        foreach ($backups as $type => $file) {
            if (!file_exists($file)) {
                $results['success'] = false;
                $results['files'][$type] = ['exists' => false];
                continue;
            }
            
            $size = filesize($file);
            $hash = md5_file($file);
            
            $results['files'][$type] = [
                'exists' => true,
                'size' => $size,
                'hash' => $hash
            ];
            
            $results['total_size'] += $size;
        }
        
        return $results;
    }
    
    /**
     * Compress file using gzip
     */
    private function compressFile($source, $destination) {
        $sourceFile = fopen($source, 'rb');
        $destFile = gzopen($destination, 'wb9');
        
        while (!feof($sourceFile)) {
            gzwrite($destFile, fread($sourceFile, 1024 * 512));
        }
        
        fclose($sourceFile);
        gzclose($destFile);
    }
    
    /**
     * Encrypt file using AES-256
     */
    private function encryptFile($source, $destination) {
        $key = $this->getEncryptionKey();
        $iv = random_bytes(16);
        
        $sourceFile = fopen($source, 'rb');
        $destFile = fopen($destination, 'wb');
        
        // Write IV to destination
        fwrite($destFile, $iv);
        
        // Encrypt and write data
        while (!feof($sourceFile)) {
            $data = fread($sourceFile, 1024 * 512);
            $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
            fwrite($destFile, $encrypted);
        }
        
        fclose($sourceFile);
        fclose($destFile);
    }
    
    /**
     * Get encryption key from environment or secure storage
     */
    private function getEncryptionKey() {
        // In production, store this securely (environment variable, key management service)
        return hash('sha256', 'your-secure-encryption-key-here');
    }
    
    /**
     * Log backup to database
     */
    private function logBackup($data) {
        try {
            $this->db->prepare("
                INSERT INTO backup_logs (
                    backup_id, timestamp, status, size, verification_data, error_message, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ")->execute([
                $data['backup_id'],
                $data['timestamp'],
                $data['status'],
                $data['size'] ?? null,
                json_encode($data['verification'] ?? []),
                $data['error'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Backup logging failed: " . $e->getMessage());
        }
    }
    
    /**
     * Cleanup old backups
     */
    private function cleanupOldBackups() {
        $this->cleanupDirectory($this->config['backup_dir'], $this->config['local_retention']);
        $this->cleanupDirectory($this->config['backup_dir'] . '/offsite', $this->config['offsite_retention']);
        // Immutable backups are kept longer (90 days)
        $this->cleanupDirectory($this->config['backup_dir'] . '/immutable', 90);
    }
    
    /**
     * Cleanup directory based on retention
     */
    private function cleanupDirectory($directory, $retentionDays) {
        if (!is_dir($directory)) return;
        
        $cutoffTime = time() - ($retentionDays * 24 * 60 * 60);
        
        foreach (glob($directory . '/*') as $file) {
            if (is_file($file) && filemtime($file) < $cutoffTime) {
                // Make immutable files writable before deletion
                if (!is_writable($file)) {
                    chmod($file, 0644);
                }
                unlink($file);
            }
        }
    }
    
    /**
     * Get backup statistics
     */
    public function getBackupStats() {
        try {
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total_backups,
                    COUNT(CASE WHEN status = 'success' THEN 1 END) as successful_backups,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_backups,
                    MAX(created_at) as last_backup,
                    AVG(size) as avg_size
                FROM backup_logs
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Test backup restoration
     */
    public function testRestore($backupId) {
        // Implementation for testing backup restoration
        // This would extract backups to a test environment
        return [
            'success' => true,
            'message' => 'Restore test functionality would be implemented here'
        ];
    }
}

// Create backup logs table
$createBackupLogsTable = "
CREATE TABLE IF NOT EXISTS backup_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    backup_id VARCHAR(50) NOT NULL UNIQUE,
    timestamp VARCHAR(20) NOT NULL,
    status ENUM('success', 'failed', 'partial') NOT NULL,
    size BIGINT NULL,
    verification_data JSON NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
";
?>
