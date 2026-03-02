<?php
require_once '../config.php';
require_once '../security_middleware.php';
if (!auth() || $_SESSION['position'] !== 'admin') json_response(false, 'Unauthorized');

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?: [];

// GET - Read
if ($method === 'GET') {
    $stmt = $db->query("SELECT * FROM machines ORDER BY machine_name");
    $machines = $stmt->fetchAll();
    json_response(true, 'Success', $machines);
}

// POST - Create/Update
if ($method === 'POST') {
    $id = $input['machine_id'] ?? null;
    
    if ($id) {
        // Update
        $stmt = $db->prepare("UPDATE machines SET machine_name=?, machine_type=?, brand=?, model=?, capacity=?, location=?, status=? WHERE machine_id=?");
        $stmt->execute([
            $input['machine_name'],
            $input['machine_type'],
            $input['brand'],
            $input['model'],
            $input['capacity'],
            $input['location'],
            $input['status'],
            $id
        ]);
        json_response(true, 'Machine updated successfully');
    } else {
        // Create
        $stmt = $db->prepare("INSERT INTO machines (machine_name, machine_type, brand, model, capacity, location, status, purchase_date) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())");
        $stmt->execute([
            $input['machine_name'],
            $input['machine_type'],
            $input['brand'],
            $input['model'],
            $input['capacity'],
            $input['location'],
            $input['status'] ?? 'available'
        ]);
        json_response(true, 'Machine created successfully');
    }
}

// DELETE - Delete
if ($method === 'DELETE') {
    $id = $input['machine_id'];
    $stmt = $db->prepare("DELETE FROM machines WHERE machine_id = ?");
    $stmt->execute([$id]);
    json_response(true, 'Machine deleted successfully');
}
?>

