<?php
require_once '../config.php';
require_once '../security_middleware.php';

header('Content-Type: application/json');

if (!auth()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$orderId = $_GET['order_id'] ?? null;
$orderType = $_GET['type'] ?? 'regular'; // 'regular' or 'custom'

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit;
}

try {
    if ($orderType === 'custom') {
        // Fetch custom order details
        // Allow admin, cashier, and manager to view any order, customers can only view their own
        if (in_array($_SESSION['position'], ['admin', 'cashier', 'manager'])) {
            $stmt = $db->prepare("
                SELECT co.*, cf.fabric_name, cf.fabric_type, cf.color, cf.condition_status, cf.description,
                       u.name, u.email, u.phone_number
                FROM custom_orders co
                LEFT JOIN customer_inventory_fabric cf ON co.fabric_id = cf.fabric_id
                LEFT JOIN users u ON co.user_id = u.user_id
                WHERE co.order_id = ?
            ");
            $stmt->execute([$orderId]);
        } else {
            $stmt = $db->prepare("
                SELECT co.*, cf.fabric_name, cf.fabric_type, cf.color, cf.condition_status, cf.description,
                       u.name, u.email, u.phone_number
                FROM custom_orders co
                LEFT JOIN customer_inventory_fabric cf ON co.fabric_id = cf.fabric_id
                LEFT JOIN users u ON co.user_id = u.user_id
                WHERE co.order_id = ? AND co.user_id = ?
            ");
            $stmt->execute([$orderId, $userId]);
        }
        $order = $stmt->fetch();
        
        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit;
        }
        
        // Calculate pricing breakdown
        $basePrice = 0;
        $ironingPrice = $order['ironing'] ? 30 : 0;
        $expressPrice = $order['express'] ? 30 : 0;
        
        // Service type pricing
        switch ($order['service_type']) {
            case 'wash_and_fold':
                $basePrice = 50;
                break;
            case 'wash_and_iron':
                $basePrice = 80;
                break;
            case 'ironing_only':
                $basePrice = 30;
                break;
            case 'dry_clean':
                $basePrice = 100;
                break;
            default:
                $basePrice = 50;
        }
        
        $totalPrice = $basePrice + $ironingPrice + $expressPrice;
        
        $orderDetails = [
            'order_id' => $order['order_id'],
            'order_type' => 'custom',
            'service_type' => $order['service_type'],
            'fabric_name' => $order['fabric_name'],
            'fabric_type' => $order['fabric_type'],
            'color' => $order['color'],
            'condition_status' => $order['condition_status'],
            'description' => $order['description'],
            'soap_type' => $order['soap_type'],
            'special_instructions' => $order['special_instructions'],
            'ironing' => $order['ironing'],
            'express' => $order['express'],
            'laundry_status' => $order['laundry_status'],
            'payment_status' => $order['payment_status'],
            'created_at' => $order['created_at'],
            'estimated_completion' => $order['estimated_completion'],
            'subtotal' => $order['subtotal'],
            'customer_name' => $order['name'],
            'customer_email' => $order['email'],
            'customer_phone' => $order['phone_number'],
            'pricing_breakdown' => [
                'base_price' => $basePrice,
                'ironing_price' => $ironingPrice,
                'express_price' => $expressPrice,
                'total_price' => $totalPrice
            ]
        ];
        
    } else {
        // Fetch regular order details
        // Allow admin, cashier, and manager to view any order, customers can only view their own
        if (in_array($_SESSION['position'], ['admin', 'cashier', 'manager'])) {
            $stmt = $db->prepare("
                SELECT t.*, 
                       GROUP_CONCAT(ti.item_name SEPARATOR ', ') as items,
                       GROUP_CONCAT(ti.quantity SEPARATOR ', ') as quantities,
                       GROUP_CONCAT(ti.unit_price SEPARATOR ', ') as unit_prices,
                       GROUP_CONCAT(ti.total_price SEPARATOR ', ') as total_prices,
                       u.name, u.email, u.phone_number
                FROM transactions t
                LEFT JOIN transaction_items ti ON t.payment_id = ti.payment_id
                LEFT JOIN users u ON t.user_id = u.user_id
                WHERE t.payment_id = ?
                GROUP BY t.payment_id
            ");
            $stmt->execute([$orderId]);
        } else {
            $stmt = $db->prepare("
                SELECT t.*, 
                       GROUP_CONCAT(ti.item_name SEPARATOR ', ') as items,
                       GROUP_CONCAT(ti.quantity SEPARATOR ', ') as quantities,
                       GROUP_CONCAT(ti.unit_price SEPARATOR ', ') as unit_prices,
                       GROUP_CONCAT(ti.total_price SEPARATOR ', ') as total_prices,
                       u.name, u.email, u.phone_number
                FROM transactions t
                LEFT JOIN transaction_items ti ON t.payment_id = ti.payment_id
                LEFT JOIN users u ON t.user_id = u.user_id
                WHERE t.payment_id = ? AND t.user_id = ?
                GROUP BY t.payment_id
            ");
            $stmt->execute([$orderId, $userId]);
        }
        $order = $stmt->fetch();
        
        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit;
        }
        
        // Parse items and quantities
        $items = $order['items'] ? explode(', ', $order['items']) : [];
        $quantities = $order['quantities'] ? explode(', ', $order['quantities']) : [];
        $unitPrices = $order['unit_prices'] ? explode(', ', $order['unit_prices']) : [];
        $totalPrices = $order['total_prices'] ? explode(', ', $order['total_prices']) : [];
        
        $orderItems = [];
        for ($i = 0; $i < count($items); $i++) {
            $orderItems[] = [
                'name' => $items[$i],
                'quantity' => $quantities[$i] ?? 1,
                'unit_price' => $unitPrices[$i] ?? 0,
                'total_price' => $totalPrices[$i] ?? 0
            ];
        }
        
        $orderDetails = [
            'order_id' => $order['payment_id'],
            'order_type' => 'regular',
            'items' => $orderItems,
            'basket_count' => $order['basket_count'],
            'detergent_qty' => $order['detergent_qty'],
            'softener_qty' => $order['softener_qty'],
            'clothing_type' => $order['clothing_type'],
            'package' => $order['package'],
            'remarks' => $order['remarks'],
            'laundry_status' => $order['laundry_status'],
            'payment_status' => $order['payment_status'],
            'created_at' => $order['created_at'],
            'estimated_completion' => $order['estimated_completion'],
            'total_price' => $order['total_price'],
            'customer_name' => $order['name'],
            'customer_email' => $order['email'],
            'customer_phone' => $order['phone_number']
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $orderDetails]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching order details: ' . $e->getMessage()]);
}
?>
