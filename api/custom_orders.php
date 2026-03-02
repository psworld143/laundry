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

// Check if user is a customer (position = 'user')
if ($_SESSION['position'] !== 'user') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Customer access only.']);
    exit;
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Read all custom orders for the current customer
            $stmt = $db->prepare("
                SELECT 
                    co.*,
                    'N/A' as fabric_name,
                    'N/A' as fabric_type,
                    'N/A' as color,
                    'N/A' as condition_status,
                    'N/A' as fabric_instructions
                FROM custom_orders co
                WHERE co.user_id = ? 
                ORDER BY co.created_at DESC
            ");
            $stmt->execute([$userId]);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $orders
            ]);
            break;

        case 'POST':
            // Create new custom order
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data)) {
                $data = $_POST;
            }
            
            // Validate required fields
            $requiredFields = ['fabric_id', 'service_type', 'soap_type'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field '{$field}' is required");
                }
            }
            
            // Verify the fabric belongs to the current user
            $stmt = $db->prepare("SELECT fabric_id FROM customer_inventory_fabric WHERE fabric_id = ? AND user_id = ?");
            $stmt->execute([$data['fabric_id'], $userId]);
            if (!$stmt->fetch()) {
                throw new Exception('Fabric item not found or access denied');
            }
            
            // Calculate pricing
            $basePrice = $data['service_type'] === 'wash' ? 50.00 : 100.00;
            $additionalPrice = 0;
            if ($data['ironing']) $additionalPrice += 30.00;
            if ($data['express']) $additionalPrice += 30.00;
            
            $totalPrice = $basePrice + $additionalPrice;
            
            $stmt = $db->prepare("
                INSERT INTO custom_orders 
                (user_id, fabric_id, service_type, soap_type, ironing, express, 
                 special_instructions, subtotal, payment_method_id, payment_status, 
                 laundry_status, estimated_completion) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            // Calculate estimated completion (24 hours for wash, 48 hours for dry clean)
            $estimatedCompletion = date('Y-m-d H:i:s', strtotime('+' . ($data['service_type'] === 'wash' ? '24' : '48') . ' hours'));
            
            $stmt->execute([
                $userId,
                $data['fabric_id'],
                $data['service_type'],
                $data['soap_type'],
                $data['ironing'] ? 1 : 0,
                $data['express'] ? 1 : 0,
                $data['special_instructions'] ?? null,
                $totalPrice,
                $data['payment_method_id'] ?? 1,
                $data['payment_status'] ?? 'pending',
                $data['laundry_status'] ?? 'pending',
                $estimatedCompletion
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Custom order created successfully',
                'order_id' => $db->lastInsertId()
            ]);
            break;

        case 'PUT':
            // Update existing custom order
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data) || !isset($data['order_id'])) {
                throw new Exception('Order ID is required for update');
            }
            
            // Verify the order belongs to the current user
            $stmt = $db->prepare("SELECT order_id FROM custom_orders WHERE order_id = ? AND user_id = ?");
            $stmt->execute([$data['order_id'], $userId]);
            if (!$stmt->fetch()) {
                throw new Exception('Order not found or access denied');
            }
            
            $stmt = $db->prepare("
                UPDATE custom_orders 
                SET special_instructions = ?, payment_status = ?, laundry_status = ?
                WHERE order_id = ? AND user_id = ?
            ");
            
            $stmt->execute([
                $data['special_instructions'] ?? null,
                $data['payment_status'] ?? 'pending',
                $data['laundry_status'] ?? 'pending',
                $data['order_id'],
                $userId
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Custom order updated successfully'
            ]);
            break;

        case 'DELETE':
            // Cancel custom order
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data)) {
                $data = $_POST;
            }
            
            if (!isset($data['order_id'])) {
                throw new Exception('Order ID is required');
            }
            
            // Verify the order belongs to the current user
            $stmt = $db->prepare("SELECT order_id FROM custom_orders WHERE order_id = ? AND user_id = ?");
            $stmt->execute([$data['order_id'], $userId]);
            if (!$stmt->fetch()) {
                throw new Exception('Order not found or access denied');
            }
            
            $stmt = $db->prepare("UPDATE custom_orders SET laundry_status = 'cancelled' WHERE order_id = ? AND user_id = ?");
            $stmt->execute([$data['order_id'], $userId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Custom order cancelled successfully'
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
    error_log("Custom Orders API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
