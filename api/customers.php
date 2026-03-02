<?php
require_once '../config.php';
require_once '../security_middleware.php';
if (!auth() || !in_array($_SESSION['position'], ['admin', 'manager', 'cashier'])) json_response(false, 'Unauthorized');

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?: [];

// GET - Read
if ($method === 'GET') {
    $stmt = $db->query("
        SELECT u.user_id, u.username, u.name, u.email, u.phone_number, u.created_at, u.is_active,
               COALESCE(cf.preferred_fabrics, '[]') as preferred_fabrics
        FROM users u
        LEFT JOIN (
            SELECT user_id, JSON_ARRAYAGG(fabric_name) as preferred_fabrics
            FROM customer_inventory_fabric
            WHERE is_active = 1
            GROUP BY user_id
        ) cf ON u.user_id = cf.user_id
        WHERE u.position = 'user' 
        ORDER BY u.name
    ");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Parse JSON for preferred fabrics
    foreach ($customers as &$customer) {
        $customer['preferred_fabrics'] = json_decode($customer['preferred_fabrics']) ?: [];
    }
    
    json_response(true, 'Success', $customers);
}

// POST - Create/Update
if ($method === 'POST') {
    $id = $input['user_id'] ?? null;
    
    if ($id) {
        // Update
        $stmt = $db->prepare("UPDATE users SET name=?, email=?, phone_number=?, is_active=? WHERE user_id=?");
        $stmt->execute([
            $input['name'],
            $input['email'],
            $input['phone_number'] ?? null,
            $input['is_active'] ?? 1,
            $id
        ]);
        
        // Update preferred fabrics
        if (isset($input['preferred_fabrics']) && is_array($input['preferred_fabrics'])) {
            // Remove existing fabric preferences
            $deleteStmt = $db->prepare("DELETE FROM customer_inventory_fabric WHERE user_id = ?");
            $deleteStmt->execute([$id]);
            
            // Add new fabric preferences
            foreach ($input['preferred_fabrics'] as $fabricName) {
                $insertStmt = $db->prepare("
                    INSERT INTO customer_inventory_fabric 
                    (user_id, fabric_name, fabric_type, color, quantity, unit, condition_status) 
                    VALUES (?, ?, 'other', NULL, 1, 'piece', 'good')
                ");
                $insertStmt->execute([$id, $fabricName]);
            }
        }
        
        json_response(true, 'Customer updated successfully');
    } else {
        // Create
        $hash = password_hash($input['password'] ?? 'password123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, name, email, phone_number, password_hash, position) VALUES (?, ?, ?, ?, ?, 'user')");
        $stmt->execute([
            $input['username'],
            $input['name'],
            $input['email'],
            $input['phone_number'] ?? null,
            $hash
        ]);
        
        $newUserId = $db->lastInsertId();
        
        // Add preferred fabrics if provided
        if (isset($input['preferred_fabrics']) && is_array($input['preferred_fabrics'])) {
            foreach ($input['preferred_fabrics'] as $fabricName) {
                $insertStmt = $db->prepare("
                    INSERT INTO customer_inventory_fabric 
                    (user_id, fabric_name, fabric_type, color, quantity, unit, condition_status) 
                    VALUES (?, ?, 'other', NULL, 1, 'piece', 'good')
                ");
                $insertStmt->execute([$newUserId, $fabricName]);
            }
        }
        
        json_response(true, 'Customer created successfully');
    }
}

// DELETE - Delete
if ($method === 'DELETE') {
    $id = $input['user_id'];
    $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$id]);
    json_response(true, 'Customer deleted successfully');
}
?>

