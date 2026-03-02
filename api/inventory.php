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

// Check if user is admin or manager
if (!in_array($_SESSION['position'], ['admin', 'manager'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Read all inventory items
            $stmt = $db->query("SELECT * FROM inventory ORDER BY item_name ASC");
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $items
            ]);
            break;

        case 'POST':
            // Create or Update inventory item
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data)) {
                $data = $_POST;
            }
            
            if (isset($data['inventory_id']) && !empty($data['inventory_id'])) {
                // Update existing item
                $stmt = $db->prepare("
                    UPDATE inventory 
                    SET item_name = :item_name,
                        item_type = :item_type,
                        brand = :brand,
                        price = :price,
                        quantity = :quantity,
                        min_stock_level = :min_stock_level,
                        unit = :unit
                    WHERE inventory_id = :inventory_id
                ");
                
                $stmt->execute([
                    ':inventory_id' => $data['inventory_id'],
                    ':item_name' => $data['item_name'],
                    ':item_type' => $data['item_type'],
                    ':brand' => $data['brand'] ?? null,
                    ':price' => $data['price'] ?? 0,
                    ':quantity' => $data['quantity'] ?? 0,
                    ':min_stock_level' => $data['min_stock_level'] ?? 10,
                    ':unit' => $data['unit'] ?? 'piece'
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Inventory item updated successfully'
                ]);
            } else {
                // Create new item
                $stmt = $db->prepare("
                    INSERT INTO inventory (item_name, item_type, brand, price, quantity, min_stock_level, unit) 
                    VALUES (:item_name, :item_type, :brand, :price, :quantity, :min_stock_level, :unit)
                ");
                
                $stmt->execute([
                    ':item_name' => $data['item_name'],
                    ':item_type' => $data['item_type'],
                    ':brand' => $data['brand'] ?? null,
                    ':price' => $data['price'] ?? 0,
                    ':quantity' => $data['quantity'] ?? 0,
                    ':min_stock_level' => $data['min_stock_level'] ?? 10,
                    ':unit' => $data['unit'] ?? 'piece'
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Inventory item added successfully',
                    'inventory_id' => $db->lastInsertId()
                ]);
            }
            break;

        case 'DELETE':
            // Delete inventory item
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data)) {
                $data = $_POST;
            }
            
            if (!isset($data['inventory_id'])) {
                throw new Exception('Inventory ID is required');
            }
            
            $stmt = $db->prepare("DELETE FROM inventory WHERE inventory_id = :inventory_id");
            $stmt->execute([':inventory_id' => $data['inventory_id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Inventory item deleted successfully'
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
    error_log("Inventory API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

