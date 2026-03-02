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

// Check if user is a driver or admin
if (!in_array($_SESSION['position'], ['admin', 'driver'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Driver access only.']);
    exit;
}

$driverId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get recent payments processed by this driver
            $stmt = $db->prepare("
                SELECT 
                    dp.*,
                    t.payment_id as order_id,
                    u.name as customer_name,
                    u.phone_number as customer_phone,
                    pm.method_name as payment_method_name
                FROM driver_payments dp
                LEFT JOIN transactions t ON dp.order_id = t.payment_id
                LEFT JOIN users u ON t.user_id = u.user_id
                LEFT JOIN payment_methods pm ON dp.payment_method_id = pm.method_id
                WHERE dp.processed_by = ?
                ORDER BY dp.processed_at DESC
                LIMIT 20
            ");
            $stmt->execute([$driverId]);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $payments
            ]);
            break;

        case 'POST':
            // Process a new payment
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data)) {
                $data = $_POST;
            }
            
            // Validate required fields
            $requiredFields = ['order_id', 'payment_method', 'amount_received'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field '{$field}' is required");
                }
            }
            
            // Get order details
            $stmt = $db->prepare("
                SELECT t.*, u.name as customer_name, u.phone_number as customer_phone
                FROM transactions t
                LEFT JOIN users u ON t.user_id = u.user_id
                WHERE t.payment_id = ?
            ");
            $stmt->execute([$data['order_id']]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                throw new Exception('Order not found');
            }
            
            if ($order['payment_status'] === 'paid') {
                throw new Exception('Order is already paid');
            }
            
            // Validate payment amount
            $expectedAmount = floatval($order['total_price']);
            $receivedAmount = floatval($data['amount_received']);
            
            if ($receivedAmount < $expectedAmount) {
                throw new Exception('Received amount is less than expected amount');
            }
            
            // Get payment method ID
            $paymentMethodMap = [
                'cash' => 1,
                'credit_card' => 2,
                'debit_card' => 3,
                'gcash' => 4,
                'paymaya' => 5,
                'bank_transfer' => 6
            ];
            
            $paymentMethodId = $paymentMethodMap[$data['payment_method']] ?? 1;
            
            // Start transaction
            $db->beginTransaction();
            
            try {
                // Insert driver payment record
                $stmt = $db->prepare("
                    INSERT INTO driver_payments 
                    (order_id, processed_by, payment_method_id, amount_received, transaction_ref, notes, processed_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $data['order_id'],
                    $driverId,
                    $paymentMethodId,
                    $receivedAmount,
                    $data['transaction_ref'] ?? null,
                    $data['notes'] ?? null
                ]);
                
                $paymentId = $db->lastInsertId();
                
                // Update order payment status and delivery status
                $markDelivered = isset($data['mark_delivered']) && $data['mark_delivered'] === true;
                
                $stmt = $db->prepare("
                    UPDATE transactions 
                    SET payment_status = 'paid', 
                        payment_method_id = ?,
                        laundry_status = ?,
                        updated_at = NOW()
                    WHERE payment_id = ?
                ");
                
                $laundryStatus = $markDelivered ? 'delivered' : $order['laundry_status'];
                $stmt->execute([$paymentMethodId, $laundryStatus, $data['order_id']]);
                
                // Calculate change if overpaid
                $change = $receivedAmount - $expectedAmount;
                
                $db->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Payment processed successfully',
                    'payment_id' => $paymentId,
                    'order_id' => $data['order_id'],
                    'amount_received' => $receivedAmount,
                    'expected_amount' => $expectedAmount,
                    'change' => $change,
                    'customer_name' => $order['customer_name']
                ]);
                
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;

        case 'PUT':
            // Update payment (for corrections)
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data) || !isset($data['payment_id'])) {
                throw new Exception('Payment ID is required for update');
            }
            
            // Verify the payment was processed by this driver
            $stmt = $db->prepare("SELECT payment_id FROM driver_payments WHERE payment_id = ? AND processed_by = ?");
            $stmt->execute([$data['payment_id'], $driverId]);
            if (!$stmt->fetch()) {
                throw new Exception('Payment not found or access denied');
            }
            
            $stmt = $db->prepare("
                UPDATE driver_payments 
                SET transaction_ref = ?, notes = ?, updated_at = NOW()
                WHERE payment_id = ? AND processed_by = ?
            ");
            
            $stmt->execute([
                $data['transaction_ref'] ?? null,
                $data['notes'] ?? null,
                $data['payment_id'],
                $driverId
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Payment updated successfully'
            ]);
            break;

        case 'DELETE':
            // Cancel/refund payment
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data)) {
                $data = $_POST;
            }
            
            if (!isset($data['payment_id'])) {
                throw new Exception('Payment ID is required');
            }
            
            // Verify the payment was processed by this driver
            $stmt = $db->prepare("SELECT payment_id FROM driver_payments WHERE payment_id = ? AND processed_by = ?");
            $stmt->execute([$data['payment_id'], $driverId]);
            if (!$stmt->fetch()) {
                throw new Exception('Payment not found or access denied');
            }
            
            // Start transaction
            $db->beginTransaction();
            
            try {
                // Get order ID from payment
                $stmt = $db->prepare("SELECT order_id FROM driver_payments WHERE payment_id = ?");
                $stmt->execute([$data['payment_id']]);
                $payment = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$payment) {
                    throw new Exception('Payment not found');
                }
                
                // Update order payment status back to pending
                $stmt = $db->prepare("
                    UPDATE transactions 
                    SET payment_status = 'pending', 
                        updated_at = NOW()
                    WHERE payment_id = ?
                ");
                $stmt->execute([$payment['order_id']]);
                
                // Mark payment as cancelled
                $stmt = $db->prepare("
                    UPDATE driver_payments 
                    SET status = 'cancelled', 
                        updated_at = NOW()
                    WHERE payment_id = ?
                ");
                $stmt->execute([$data['payment_id']]);
                
                $db->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Payment cancelled successfully'
                ]);
                
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
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
    error_log("Driver Payments API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
