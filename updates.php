<?php
require_once 'config.php';
require_once 'update_manager.php';

// Check if request is from CLI or authorized admin
if (php_sapi_name() !== 'cli' && (!auth() || $_SESSION['position'] !== 'admin')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

$updateManager = new UpdateManager($db);

// Handle different update operations
$action = $_GET['action'] ?? $_POST['action'] ?? 'check';

switch ($action) {
    case 'check':
        $updates = $updateManager->checkAllUpdates();
        
        if (php_sapi_name() === 'cli') {
            echo "Update Check Results:\n";
            echo "==================\n";
            
            foreach ($updates as $category => $data) {
                echo "\n" . ucfirst($category) . ":\n";
                echo "Current: " . ($data['current'] ?? 'N/A') . "\n";
                echo "Latest: " . ($data['latest'] ?? 'N/A') . "\n";
                echo "Available: " . ($data['available'] ? 'Yes' : 'No') . "\n";
                
                if ($data['available']) {
                    if (isset($data['type'])) {
                        echo "Type: " . $data['type'] . "\n";
                    }
                    if (isset($data['security']) && $data['security']) {
                        echo "⚠️  Security Update\n";
                    }
                }
                
                if (isset($data['error'])) {
                    echo "Error: " . $data['error'] . "\n";
                }
            }
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $updates
            ]);
        }
        break;
        
    case 'apply':
        $categories = $_POST['categories'] ?? [];
        if (!is_array($categories)) {
            $categories = explode(',', $categories);
        }
        
        $results = $updateManager->applyUpdates($categories);
        
        if (php_sapi_name() === 'cli') {
            echo "Update Results:\n";
            echo "==============\n";
            
            foreach ($results as $category => $result) {
                echo "\n" . ucfirst($category) . ":\n";
                echo "Status: " . ($result['success'] ? 'Success' : 'Failed') . "\n";
                echo "Message: " . $result['message'] . "\n";
                
                if (isset($result['migrations'])) {
                    echo "Migrations: " . implode(', ', $result['migrations']) . "\n";
                }
            }
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $results
            ]);
        }
        break;
        
    case 'stats':
        $stats = $updateManager->getUpdateStats();
        
        if (php_sapi_name() === 'cli') {
            echo "Update Statistics (Last 30 days):\n";
            echo "=================================\n";
            
            if ($stats) {
                echo "Total Checks: " . $stats['checks']['total_checks'] . "\n";
                echo "Checks with Updates: " . $stats['checks']['checks_with_updates'] . "\n";
                echo "Last Check: " . $stats['checks']['last_check'] . "\n\n";
                
                echo "Recent Actions:\n";
                foreach ($stats['actions'] as $action) {
                    echo "- " . $action['action_type'] . ": " . $action['count'] . " times (last: " . $action['last_action'] . ")\n";
                }
            } else {
                echo "No statistics available\n";
            }
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
        }
        break;
        
    default:
        if (php_sapi_name() === 'cli') {
            echo "Usage:\n";
            echo "  php update_manager.php check              - Check for updates\n";
            echo "  php update_manager.php apply [categories]  - Apply updates (optional: php,composer,database)\n";
            echo "  php update_manager.php stats              - Show update statistics\n";
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action'
            ]);
        }
}
?>
