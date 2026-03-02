<?php
require_once 'config.php';
require_once 'backup_system.php';

// Check if request is from CLI or authorized admin
if (php_sapi_name() !== 'cli' && (!auth() || $_SESSION['position'] !== 'admin')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

$backupSystem = new BackupSystem($db);

// Handle different backup operations
$action = $_GET['action'] ?? $_POST['action'] ?? 'status';

switch ($action) {
    case 'execute':
        try {
            $result = $backupSystem->executeBackup();
            
            if (php_sapi_name() === 'cli') {
                echo "Backup completed successfully!\n";
                echo "Backup ID: " . $result['backup_id'] . "\n";
                echo "Timestamp: " . $result['timestamp'] . "\n";
                echo "Status: " . ($result['success'] ? 'Success' : 'Failed') . "\n";
            } else {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'data' => $result
                ]);
            }
        } catch (Exception $e) {
            if (php_sapi_name() === 'cli') {
                echo "Backup failed: " . $e->getMessage() . "\n";
                exit(1);
            } else {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
        }
        break;
        
    case 'status':
        $stats = $backupSystem->getBackupStats();
        
        if (php_sapi_name() === 'cli') {
            echo "Backup Statistics (Last 30 days):\n";
            echo "Total Backups: " . $stats['total_backups'] . "\n";
            echo "Successful: " . $stats['successful_backups'] . "\n";
            echo "Failed: " . $stats['failed_backups'] . "\n";
            echo "Last Backup: " . $stats['last_backup'] . "\n";
            echo "Average Size: " . number_format($stats['avg_size'] / 1024 / 1024, 2) . " MB\n";
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
        }
        break;
        
    case 'test':
        $backupId = $_GET['backup_id'] ?? $_POST['backup_id'] ?? null;
        if (!$backupId) {
            $error = 'Backup ID required';
            if (php_sapi_name() === 'cli') {
                echo "Error: $error\n";
                exit(1);
            } else {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $error]);
            }
            break;
        }
        
        $result = $backupSystem->testRestore($backupId);
        
        if (php_sapi_name() === 'cli') {
            echo "Restore test: " . ($result['success'] ? 'Success' : 'Failed') . "\n";
            echo $result['message'] . "\n";
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
        }
        break;
        
    default:
        if (php_sapi_name() === 'cli') {
            echo "Usage:\n";
            echo "  php backup.php execute    - Execute backup\n";
            echo "  php backup.php status     - Show backup statistics\n";
            echo "  php backup.php test       - Test backup restoration\n";
            echo "  php backup.php test <id>  - Test specific backup\n";
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action'
            ]);
        }
}
?>
