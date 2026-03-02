<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

// Check authentication
if (!auth()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Read inventory items for customers (detergents and fabric softeners only)
            $stmt = $db->query("
                SELECT inventory_id, item_name, item_type, brand, price, quantity, unit 
                FROM inventory 
                WHERE item_type IN ('detergent', 'fabric_softener') 
                AND quantity > 0
                ORDER BY item_type, brand, item_name ASC
            ");
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $items
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
    error_log("Public Inventory API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
