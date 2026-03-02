<?php
require_once '../config.php';
require_once '../security_middleware.php';

header('Content-Type: application/json');

// Check authentication
if (!auth()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Block access to fabric inventory API
http_response_code(403);
echo json_encode(['success' => false, 'message' => 'Fabric inventory feature is not available']);
exit;

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Read all fabric items for the current customer
            $stmt = $db->prepare("SELECT * FROM customer_inventory_fabric WHERE user_id = ? AND is_active = 1 ORDER BY fabric_name ASC");
            $stmt->execute([$userId]);
            $fabrics = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $fabrics
            ]);
            break;

        case 'POST':
            // Create new fabric item
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data)) {
                $data = $_POST;
            }
            
            // Validate required fields
            $requiredFields = ['fabric_name', 'fabric_type'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field '{$field}' is required");
                }
            }
            
            $stmt = $db->prepare("
                INSERT INTO customer_inventory_fabric 
                (user_id, fabric_name, fabric_type, color, quantity, unit, condition_status, special_instructions, last_wash_date, next_wash_reminder) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $data['fabric_name'],
                $data['fabric_type'],
                $data['color'] ?? null,
                $data['quantity'] ?? 1,
                $data['unit'] ?? 'piece',
                $data['condition_status'] ?? 'good',
                $data['special_instructions'] ?? null,
                $data['last_wash_date'] ?? null,
                $data['next_wash_reminder'] ?? null
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Fabric item added successfully',
                'fabric_id' => $db->lastInsertId()
            ]);
            break;

        case 'PUT':
            // Update existing fabric item
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data) || !isset($data['fabric_id'])) {
                throw new Exception('Fabric ID is required for update');
            }
            
            // Verify the fabric belongs to the current user
            $stmt = $db->prepare("SELECT fabric_id FROM customer_inventory_fabric WHERE fabric_id = ? AND user_id = ?");
            $stmt->execute([$data['fabric_id'], $userId]);
            if (!$stmt->fetch()) {
                throw new Exception('Fabric item not found or access denied');
            }
            
            $stmt = $db->prepare("
                UPDATE customer_inventory_fabric 
                SET fabric_name = ?, fabric_type = ?, color = ?, quantity = ?, unit = ?, 
                    condition_status = ?, special_instructions = ?, last_wash_date = ?, next_wash_reminder = ?
                WHERE fabric_id = ? AND user_id = ?
            ");
            
            $stmt->execute([
                $data['fabric_name'] ?? null,
                $data['fabric_type'] ?? null,
                $data['color'] ?? null,
                $data['quantity'] ?? null,
                $data['unit'] ?? null,
                $data['condition_status'] ?? null,
                $data['special_instructions'] ?? null,
                $data['last_wash_date'] ?? null,
                $data['next_wash_reminder'] ?? null,
                $data['fabric_id'],
                $userId
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Fabric item updated successfully'
            ]);
            break;

        case 'DELETE':
            // Soft delete fabric item (set is_active = 0)
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data)) {
                $data = $_POST;
            }
            
            if (!isset($data['fabric_id'])) {
                throw new Exception('Fabric ID is required');
            }
            
            // Verify the fabric belongs to the current user
            $stmt = $db->prepare("SELECT fabric_id FROM customer_inventory_fabric WHERE fabric_id = ? AND user_id = ?");
            $stmt->execute([$data['fabric_id'], $userId]);
            if (!$stmt->fetch()) {
                throw new Exception('Fabric item not found or access denied');
            }
            
            $stmt = $db->prepare("UPDATE customer_inventory_fabric SET is_active = 0 WHERE fabric_id = ? AND user_id = ?");
            $stmt->execute([$data['fabric_id'], $userId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Fabric item deleted successfully'
            ]);
            break;

        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed'
            ]);
            break;
    }
} catch (Exception $e) {
    error_log("Customer Inventory Fabric API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

