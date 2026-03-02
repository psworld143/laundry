<?php
require_once '../config.php';
require_once '../security_middleware.php';
if (!auth() || $_SESSION['position'] !== 'admin') json_response(false, 'Unauthorized');

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?: [];

// GET - Read
if ($method === 'GET') {
    $stmt = $db->query("SELECT * FROM services ORDER BY service_name");
    $services = $stmt->fetchAll();
    json_response(true, 'Success', $services);
}

// POST - Create/Update
if ($method === 'POST') {
    $id = $input['service_id'] ?? null;
    
    if ($id) {
        // Update
        $stmt = $db->prepare("UPDATE services SET service_name=?, description=?, base_price=?, service_type=?, estimated_duration=?, is_active=? WHERE service_id=?");
        $stmt->execute([
            $input['service_name'],
            $input['description'],
            $input['base_price'],
            $input['service_type'],
            $input['estimated_duration'] ?? null,
            $input['is_active'] ?? 1,
            $id
        ]);
        json_response(true, 'Service updated successfully');
    } else {
        // Create
        $stmt = $db->prepare("INSERT INTO services (service_name, description, base_price, service_type, estimated_duration) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $input['service_name'],
            $input['description'],
            $input['base_price'],
            $input['service_type'],
            $input['estimated_duration'] ?? null
        ]);
        json_response(true, 'Service created successfully');
    }
}

// DELETE - Delete
if ($method === 'DELETE') {
    $id = $input['service_id'];
    $stmt = $db->prepare("DELETE FROM services WHERE service_id = ?");
    $stmt->execute([$id]);
    json_response(true, 'Service deleted successfully');
}
?>

