<?php
/**
 * Managed Updates System for LaundryPro
 * Automated patching and update notifications for PHP, Composer packages, and database
 */

class UpdateManager {
    private $db;
    private $config;
    
    public function __construct($db) {
        $this->db = $db;
        $this->config = [
            'check_interval' => 86400, // 24 hours
            'auto_update' => [
                'security' => true,    // Auto-apply security updates
                'minor' => false,      // Don't auto-apply minor updates
                'major' => false       // Don't auto-apply major updates
            ],
            'notification_email' => 'admin@laundrypro.com',
            'backup_before_update' => true
        ];
    }
    
    /**
     * Check for all available updates
     */
    public function checkAllUpdates() {
        $updates = [
            'php' => $this->checkPHPUpdates(),
            'composer' => $this->checkComposerUpdates(),
            'database' => $this->checkDatabaseUpdates()
        ];
        
        $hasUpdates = false;
        foreach ($updates as $category => $data) {
            if ($data['available']) {
                $hasUpdates = true;
                break;
            }
        }
        
        // Log check results
        $this->logUpdateCheck([
            'php' => $updates['php'],
            'composer' => $updates['composer'],
            'database' => $updates['database'],
            'has_updates' => $hasUpdates
        ]);
        
        // Send notifications if updates available
        if ($hasUpdates) {
            $this->sendUpdateNotifications($updates);
        }
        
        return $updates;
    }
    
    /**
     * Check for PHP updates
     */
    private function checkPHPUpdates() {
        $currentVersion = PHP_VERSION;
        $latestVersion = $this->getLatestPHPVersion();
        
        if (!$latestVersion) {
            return [
                'current' => $currentVersion,
                'latest' => null,
                'available' => false,
                'error' => 'Could not fetch latest PHP version'
            ];
        }
        
        $updateType = $this->getUpdateType($currentVersion, $latestVersion);
        $securityUpdate = $this->isSecurityUpdate($currentVersion, $latestVersion);
        
        return [
            'current' => $currentVersion,
            'latest' => $latestVersion,
            'available' => version_compare($currentVersion, $latestVersion, '<'),
            'type' => $updateType,
            'security' => $securityUpdate,
            'auto_update' => $this->shouldAutoUpdate($updateType, $securityUpdate)
        ];
    }
    
    /**
     * Check for Composer package updates
     */
    private function checkComposerUpdates() {
        $composerPath = __DIR__ . '/composer.json';
        
        if (!file_exists($composerPath)) {
            return [
                'current' => null,
                'latest' => null,
                'available' => false,
                'error' => 'composer.json not found'
            ];
        }
        
        $composerData = json_decode(file_get_contents($composerPath), true);
        $currentPackages = $composerData['require'] ?? [];
        $outdatedPackages = [];
        
        // Check each package for updates
        foreach ($currentPackages as $package => $version) {
            $latestVersion = $this->getLatestPackageVersion($package);
            
            if ($latestVersion && version_compare($version, $latestVersion, '<')) {
                $outdatedPackages[] = [
                    'name' => $package,
                    'current' => $version,
                    'latest' => $latestVersion,
                    'type' => $this->getUpdateType($version, $latestVersion)
                ];
            }
        }
        
        return [
            'current' => count($currentPackages) . ' packages',
            'latest' => count($outdatedPackages) . ' updates available',
            'available' => !empty($outdatedPackages),
            'packages' => $outdatedPackages
        ];
    }
    
    /**
     * Check for database updates/migrations
     */
    private function checkDatabaseUpdates() {
        try {
            // Check if migrations table exists
            $stmt = $this->db->query("SHOW TABLES LIKE 'migrations'");
            $hasMigrations = $stmt->rowCount() > 0;
            
            if (!$hasMigrations) {
                return [
                    'current' => 'No migration system',
                    'latest' => null,
                    'available' => false,
                    'message' => 'Migration system not implemented'
                ];
            }
            
            // Get last migration
            $stmt = $this->db->query("SELECT MAX(version) as last_version FROM migrations");
            $lastMigration = $stmt->fetch()['last_version'];
            
            // Check for new migration files
            $migrationFiles = glob(__DIR__ . '/database/migrations/*.sql');
            $availableMigrations = [];
            
            foreach ($migrationFiles as $file) {
                $version = basename($file, '.sql');
                if (version_compare($version, $lastMigration, '>')) {
                    $availableMigrations[] = $version;
                }
            }
            
            return [
                'current' => $lastMigration,
                'latest' => !empty($availableMigrations) ? max($availableMigrations) : $lastMigration,
                'available' => !empty($availableMigrations),
                'migrations' => $availableMigrations
            ];
            
        } catch (Exception $e) {
            return [
                'current' => 'Unknown',
                'latest' => null,
                'available' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get latest PHP version from official API
     */
    private function getLatestPHPVersion() {
        $url = 'https://www.php.net/releases/index.php?json';
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'LaundryPro-UpdateManager/1.0'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return null;
        }
        
        $data = json_decode($response, true);
        
        // Get latest stable version
        foreach ($data as $version => $info) {
            if (strpos($version, '8') === 0) { // Focus on PHP 8.x
                return $version;
            }
        }
        
        return null;
    }
    
    /**
     * Get latest package version from Packagist
     */
    private function getLatestPackageVersion($package) {
        $url = "https://repo.packagist.org/p/{$package}.json";
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'LaundryPro-UpdateManager/1.0'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return null;
        }
        
        $data = json_decode($response, true);
        $versions = array_keys($data['packages'][$package]);
        
        // Filter out dev versions and get latest stable
        $stableVersions = array_filter($versions, function($version) {
            return !preg_match('/(dev|alpha|beta|rc)/i', $version);
        });
        
        return !empty($stableVersions) ? max($stableVersions) : null;
    }
    
    /**
     * Determine update type (major, minor, patch)
     */
    private function getUpdateType($current, $latest) {
        $currentParts = explode('.', $current);
        $latestParts = explode('.', $latest);
        
        if (count($currentParts) < 2 || count($latestParts) < 2) {
            return 'unknown';
        }
        
        if ($currentParts[0] !== $latestParts[0]) {
            return 'major';
        } elseif ($currentParts[1] !== $latestParts[1]) {
            return 'minor';
        } else {
            return 'patch';
        }
    }
    
    /**
     * Check if update is security-related
     */
    private function isSecurityUpdate($current, $latest) {
        // This would typically check security advisories
        // For now, assume patch versions are security updates
        $updateType = $this->getUpdateType($current, $latest);
        return $updateType === 'patch';
    }
    
    /**
     * Determine if update should be automatic
     */
    private function shouldAutoUpdate($type, $security) {
        if ($security && $this->config['auto_update']['security']) {
            return true;
        }
        
        return $this->config['auto_update'][$type] ?? false;
    }
    
    /**
     * Apply available updates
     */
    public function applyUpdates($categories = []) {
        $results = [];
        
        if (empty($categories) || in_array('php', $categories)) {
            $results['php'] = $this->applyPHPUpdate();
        }
        
        if (empty($categories) || in_array('composer', $categories)) {
            $results['composer'] = $this->applyComposerUpdate();
        }
        
        if (empty($categories) || in_array('database', $categories)) {
            $results['database'] = $this->applyDatabaseUpdate();
        }
        
        return $results;
    }
    
    /**
     * Apply PHP update (system-level)
     */
    private function applyPHPUpdate() {
        // PHP updates typically require system administrator intervention
        // This would integrate with system package manager
        
        $this->logUpdateAction('php_update_attempt', [
            'message' => 'PHP update requires system administrator',
            'auto_update' => false
        ]);
        
        return [
            'success' => false,
            'message' => 'PHP updates require system administrator intervention'
        ];
    }
    
    /**
     * Apply Composer updates
     */
    private function applyComposerUpdate() {
        $composerPath = __DIR__ . '/composer.json';
        $lockPath = __DIR__ . '/composer.lock';
        
        if (!file_exists($composerPath)) {
            return [
                'success' => false,
                'message' => 'composer.json not found'
            ];
        }
        
        // Create backup before update
        if ($this->config['backup_before_update']) {
            $backupResult = $this->createUpdateBackup();
            if (!$backupResult['success']) {
                return [
                    'success' => false,
                    'message' => 'Backup failed, update aborted'
                ];
            }
        }
        
        // Execute composer update
        $command = 'cd ' . __DIR__ . ' && composer update --no-interaction --prefer-dist';
        $output = [];
        $returnCode = 0;
        
        exec($command, $output, $returnCode);
        
        $success = $returnCode === 0;
        
        $this->logUpdateAction('composer_update', [
            'success' => $success,
            'output' => implode("\n", $output),
            'return_code' => $returnCode
        ]);
        
        return [
            'success' => $success,
            'message' => $success ? 'Composer packages updated successfully' : 'Composer update failed',
            'output' => $output
        ];
    }
    
    /**
     * Apply database migrations
     */
    private function applyDatabaseUpdate() {
        try {
            $this->db->beginTransaction();
            
            // Get pending migrations
            $stmt = $this->db->query("SELECT MAX(version) as last_version FROM migrations");
            $lastMigration = $stmt->fetch()['last_version'] ?? '0.0.0';
            
            $migrationFiles = glob(__DIR__ . '/database/migrations/*.sql');
            $pendingMigrations = [];
            
            foreach ($migrationFiles as $file) {
                $version = basename($file, '.sql');
                if (version_compare($version, $lastMigration, '>')) {
                    $pendingMigrations[$version] = $file;
                }
            }
            
            // Apply migrations in order
            ksort($pendingMigrations);
            $appliedMigrations = [];
            
            foreach ($pendingMigrations as $version => $file) {
                $sql = file_get_contents($file);
                
                // Split SQL file into individual statements
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                
                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        $this->db->exec($statement);
                    }
                }
                
                // Record migration
                $this->db->prepare("INSERT INTO migrations (version, applied_at) VALUES (?, NOW())")
                    ->execute([$version]);
                
                $appliedMigrations[] = $version;
            }
            
            $this->db->commit();
            
            $this->logUpdateAction('database_migrations', [
                'success' => true,
                'migrations_applied' => $appliedMigrations
            ]);
            
            return [
                'success' => true,
                'message' => 'Database migrations applied successfully',
                'migrations' => $appliedMigrations
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            
            $this->logUpdateAction('database_migrations', [
                'success' => false,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Database migration failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Create backup before update
     */
    private function createUpdateBackup() {
        $backupDir = __DIR__ . '/backups/pre-update';
        
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $backupDir . "/pre_update_{$timestamp}.tar";
        
        // Create backup of critical files
        $command = "tar -czf {$backupFile} -C " . dirname(__DIR__) . " laundry/composer.json laundry/composer.lock laundry/vendor";
        
        exec($command, $output, $returnCode);
        
        return [
            'success' => $returnCode === 0,
            'backup_file' => $backupFile
        ];
    }
    
    /**
     * Send update notifications
     */
    private function sendUpdateNotifications($updates) {
        $subject = 'LaundryPro Updates Available';
        $message = $this->formatUpdateMessage($updates);
        
        // Send email
        $headers = "From: noreply@laundrypro.com\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        mail($this->config['notification_email'], $subject, $message, $headers);
        
        // Log notification
        $this->logUpdateAction('notification_sent', [
            'email' => $this->config['notification_email'],
            'updates' => $updates
        ]);
    }
    
    /**
     * Format update notification message
     */
    private function formatUpdateMessage($updates) {
        $html = '<h2>LaundryPro System Updates</h2>';
        
        foreach ($updates as $category => $data) {
            if ($data['available']) {
                $html .= "<h3>" . ucfirst($category) . " Updates</h3>";
                
                if ($category === 'php') {
                    $html .= "<p>Current: {$data['current']}<br>";
                    $html .= "Latest: {$data['latest']}<br>";
                    $html .= "Type: {$data['type']}<br>";
                    if ($data['security']) {
                        $html .= "<strong>⚠️ Security Update</strong><br>";
                    }
                    $html .= "</p>";
                } elseif ($category === 'composer') {
                    $html .= "<ul>";
                    foreach ($data['packages'] as $package) {
                        $html .= "<li>{$package['name']}: {$package['current']} → {$package['latest']}</li>";
                    }
                    $html .= "</ul>";
                } elseif ($category === 'database') {
                    $html .= "<p>Pending migrations: " . implode(', ', $data['migrations']) . "</p>";
                }
            }
        }
        
        $html .= '<p><a href="https://yourdomain.com/admin/updates">Manage Updates</a></p>';
        
        return $html;
    }
    
    /**
     * Log update check/action
     */
    private function logUpdateCheck($data) {
        try {
            $this->db->prepare("
                INSERT INTO update_checks (check_data, has_updates, created_at) VALUES (?, ?, NOW())
            ")->execute([json_encode($data), $data['has_updates']]);
        } catch (Exception $e) {
            error_log("Update check logging failed: " . $e->getMessage());
        }
    }
    
    /**
     * Log update action
     */
    private function logUpdateAction($action, $data) {
        try {
            $this->db->prepare("
                INSERT INTO update_actions (action_type, action_data, created_at) VALUES (?, ?, NOW())
            ")->execute([$action, json_encode($data)]);
        } catch (Exception $e) {
            error_log("Update action logging failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get update statistics
     */
    public function getUpdateStats() {
        try {
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total_checks,
                    COUNT(CASE WHEN has_updates = 1 THEN 1 END) as checks_with_updates,
                    MAX(created_at) as last_check
                FROM update_checks
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            
            $checkStats = $stmt->fetch();
            
            $stmt = $this->db->query("
                SELECT 
                    action_type,
                    COUNT(*) as count,
                    MAX(created_at) as last_action
                FROM update_actions
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY action_type
            ");
            
            $actionStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'checks' => $checkStats,
                'actions' => $actionStats
            ];
            
        } catch (Exception $e) {
            return null;
        }
    }
}

// Create update management tables
$createUpdateChecksTable = "
CREATE TABLE IF NOT EXISTS update_checks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    check_data JSON NOT NULL,
    has_updates TINYINT(1) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_has_updates (has_updates),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
";

$createUpdateActionsTable = "
CREATE TABLE IF NOT EXISTS update_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action_type VARCHAR(50) NOT NULL,
    action_data JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
";

$createMigrationsTable = "
CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(20) NOT NULL UNIQUE,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_version (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
";
?>
