<?php
require_once '../config.php';
require_once '../security_middleware.php';
require_once '../security_middleware.php';

header('Content-Type: application/json');

// Validate API authentication
validateAPIAuth();

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Read all orders with customer and staff details
            $stmt = $db->query("
                SELECT 
                    t.*,
                    u.name as customer_name,
                    u.email as customer_email,
                    u.phone_number as customer_phone,
                    s.name as staff_name,
                    pm.method_name as payment_method_name
                FROM transactions t
                LEFT JOIN users u ON t.user_id = u.user_id
                LEFT JOIN staff s ON t.staff_id = s.staff_id
                LEFT JOIN payment_methods pm ON t.payment_method_id = pm.method_id
                ORDER BY t.created_at DESC
            ");
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get items for each order
            foreach ($orders as &$order) {
                $itemStmt = $db->prepare("
                    SELECT ti.*, sv.service_name
                    FROM transaction_items ti
                    LEFT JOIN services sv ON ti.service_id = sv.service_id
                    WHERE ti.payment_id = ?
                ");
                $itemStmt->execute([$order['payment_id']]);
                $order['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            echo json_encode([
                'success' => true,
                'data' => $orders
            ]);
            break;

        case 'POST':
            // Create or Update order
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data)) {
                $data = $_POST;
            }
            
            if (isset($data['payment_id']) && !empty($data['payment_id'])) {
                // Update existing order
                $stmt = $db->prepare("
                    UPDATE transactions 
                    SET payment_status = :payment_status,
                        laundry_status = :laundry_status,
                        staff_id = :staff_id,
                        remarks = :remarks,
                        estimated_completion = :estimated_completion
                    WHERE payment_id = :payment_id
                ");
                
                $stmt->execute([
                    ':payment_id' => $data['payment_id'],
                    ':payment_status' => $data['payment_status'] ?? 'pending',
                    ':laundry_status' => $data['laundry_status'] ?? 'pending',
                    ':staff_id' => $data['staff_id'] ?? null,
                    ':remarks' => $data['remarks'] ?? null,
                    ':estimated_completion' => $data['estimated_completion'] ?? null
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Order updated successfully'
                ]);
            } else {
                // Create new order
                $stmt = $db->prepare("
                    INSERT INTO transactions 
                    (user_id, staff_id, basket_count, package, detergent_qty, softener_qty, 
                     subtotal, discount_amount, total_price, payment_method_id, payment_status, 
                     laundry_status, customer_number, account_name, remarks, estimated_completion, clothing_type) 
                    VALUES 
                    (:user_id, :staff_id, :basket_count, :package, :detergent_qty, :softener_qty, 
                     :subtotal, :discount_amount, :total_price, :payment_method_id, :payment_status, 
                     :laundry_status, :customer_number, :account_name, :remarks, :estimated_completion, :clothing_type)
                ");
                
                $stmt->execute([
                    ':user_id' => $data['user_id'],
                    ':staff_id' => $data['staff_id'] ?? null,
                    ':basket_count' => $data['basket_count'] ?? 0,
                    ':package' => $data['package'] ?? 'none',
                    ':detergent_qty' => $data['detergent_qty'] ?? 0,
                    ':softener_qty' => $data['softener_qty'] ?? 0,
                    ':subtotal' => $data['subtotal'] ?? 0,
                    ':discount_amount' => $data['discount_amount'] ?? 0,
                    ':total_price' => $data['total_price'] ?? 0,
                    ':payment_method_id' => $data['payment_method_id'] ?? 1,
                    ':payment_status' => $data['payment_status'] ?? 'pending',
                    ':laundry_status' => $data['laundry_status'] ?? 'pending',
                    ':customer_number' => $data['customer_number'] ?? 'none',
                    ':account_name' => $data['account_name'] ?? 'none',
                    ':remarks' => $data['remarks'] ?? null,
                    ':estimated_completion' => $data['estimated_completion'] ?? null,
                    ':clothing_type' => $data['clothing_type'] ?? 'regular'
                ]);
                
                $orderId = $db->lastInsertId();
                
                // Insert order items if provided
                if (isset($data['items']) && is_array($data['items'])) {
                    $itemStmt = $db->prepare("
                        INSERT INTO transaction_items 
                        (payment_id, service_id, item_name, quantity, unit_price, total_price, special_instructions, status) 
                        VALUES (:payment_id, :service_id, :item_name, :quantity, :unit_price, :total_price, :special_instructions, :status)
                    ");
                    
                    foreach ($data['items'] as $item) {
                        $itemStmt->execute([
                            ':payment_id' => $orderId,
                            ':service_id' => $item['service_id'],
                            ':item_name' => $item['item_name'],
                            ':quantity' => $item['quantity'],
                            ':unit_price' => $item['unit_price'],
                            ':total_price' => $item['total_price'],
                            ':special_instructions' => $item['special_instructions'] ?? null,
                            ':status' => $item['status'] ?? 'pending'
                        ]);
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Order created successfully',
                    'payment_id' => $orderId
                ]);
            }
            break;

        case 'DELETE':
            // Delete order
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data)) {
                $data = $_POST;
            }
            
            if (!isset($data['payment_id'])) {
                throw new Exception('Order ID is required');
            }
            
            // Delete order items first
            $stmt = $db->prepare("DELETE FROM transaction_items WHERE payment_id = :payment_id");
            $stmt->execute([':payment_id' => $data['payment_id']]);
            
            // Delete order
            $stmt = $db->prepare("DELETE FROM transactions WHERE payment_id = :payment_id");
            $stmt->execute([':payment_id' => $data['payment_id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Order deleted successfully'
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
    error_log("Orders API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

