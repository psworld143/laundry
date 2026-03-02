<?php
require_once '../config.php';
require_once '../security_middleware.php';
if (!auth() || !in_array($_SESSION['position'], ['admin', 'manager'])) json_response(false, 'Unauthorized');

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?: [];

// GET - Read
if ($method === 'GET') {
    $stmt = $db->query("SELECT * FROM staff ORDER BY name");
    $staff = $stmt->fetchAll();
    json_response(true, 'Success', $staff);
}

// POST - Create/Update
if ($method === 'POST') {
    $id = $input['staff_id'] ?? null;
    
    if ($id) {
        // Update
        $stmt = $db->prepare("UPDATE staff SET name=?, position=?, contact_number=?, email=?, hire_date=?, salary=?, is_active=? WHERE staff_id=?");
        $stmt->execute([
            $input['name'],
            $input['position'],
            $input['contact_number'] ?? null,
            $input['email'] ?? null,
            $input['hire_date'],
            $input['salary'] ?? null,
            $input['is_active'] ?? 1,
            $id
        ]);
        json_response(true, 'Staff updated successfully');
    } else {
        // Create
        $stmt = $db->prepare("INSERT INTO staff (name, position, contact_number, email, hire_date, salary) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $input['name'],
            $input['position'],
            $input['contact_number'] ?? null,
            $input['email'] ?? null,
            $input['hire_date'],
            $input['salary'] ?? null
        ]);
        json_response(true, 'Staff created successfully');
    }
}

// DELETE - Delete
if ($method === 'DELETE') {
    $id = $input['staff_id'];
    $stmt = $db->prepare("DELETE FROM staff WHERE staff_id = ?");
    $stmt->execute([$id]);
    json_response(true, 'Staff deleted successfully');
}
?>

