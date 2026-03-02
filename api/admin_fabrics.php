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
            // Read all fabric types
            $stmt = $db->query("SELECT * FROM admin_fabrics WHERE is_active = 1 ORDER BY fabric_name ASC");
            $fabrics = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $fabrics
            ]);
            break;

        case 'POST':
            // Create or Update fabric type
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data)) {
                $data = $_POST;
            }
            
            // Validate required fields
            $requiredFields = ['fabric_name', 'fabric_type', 'price_multiplier', 'wash_temperature', 'processing_time'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field '{$field}' is required");
                }
            }
            
            if (isset($data['fabric_id']) && !empty($data['fabric_id'])) {
                // Update existing fabric
                $stmt = $db->prepare("
                    UPDATE admin_fabrics 
                    SET fabric_name = :fabric_name,
                        fabric_type = :fabric_type,
                        price_multiplier = :price_multiplier,
                        wash_temperature = :wash_temperature,
                        description = :description,
                        care_instructions = :care_instructions,
                        processing_time = :processing_time,
                        is_popular = :is_popular
                    WHERE fabric_id = :fabric_id
                ");
                
                $stmt->execute([
                    ':fabric_id' => $data['fabric_id'],
                    ':fabric_name' => $data['fabric_name'],
                    ':fabric_type' => $data['fabric_type'],
                    ':price_multiplier' => $data['price_multiplier'],
                    ':wash_temperature' => $data['wash_temperature'],
                    ':description' => $data['description'] ?? null,
                    ':care_instructions' => $data['care_instructions'] ?? null,
                    ':processing_time' => $data['processing_time'],
                    ':is_popular' => $data['is_popular'] ?? 0
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Fabric type updated successfully'
                ]);
            } else {
                // Create new fabric
                $stmt = $db->prepare("
                    INSERT INTO admin_fabrics (fabric_name, fabric_type, price_multiplier, wash_temperature, description, care_instructions, processing_time, is_popular) 
                    VALUES (:fabric_name, :fabric_type, :price_multiplier, :wash_temperature, :description, :care_instructions, :processing_time, :is_popular)
                ");
                
                $stmt->execute([
                    ':fabric_name' => $data['fabric_name'],
                    ':fabric_type' => $data['fabric_type'],
                    ':price_multiplier' => $data['price_multiplier'],
                    ':wash_temperature' => $data['wash_temperature'],
                    ':description' => $data['description'] ?? null,
                    ':care_instructions' => $data['care_instructions'] ?? null,
                    ':processing_time' => $data['processing_time'],
                    ':is_popular' => $data['is_popular'] ?? 0
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Fabric type added successfully',
                    'fabric_id' => $db->lastInsertId()
                ]);
            }
            break;

        case 'DELETE':
            // Soft delete fabric type (set is_active = 0)
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data)) {
                $data = $_POST;
            }
            
            if (!isset($data['fabric_id'])) {
                throw new Exception('Fabric ID is required');
            }
            
            $stmt = $db->prepare("UPDATE admin_fabrics SET is_active = 0 WHERE fabric_id = :fabric_id");
            $stmt->execute([':fabric_id' => $data['fabric_id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Fabric type deleted successfully'
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
    error_log("Admin Fabrics API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
