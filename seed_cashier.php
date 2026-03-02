<?php
require_once '../config.php';

// Create cashier user
$cashierData = [
    'username' => 'cashier',
    'name' => 'Maria Santos',
    'email' => 'maria.santos@laundrypro.com',
    'phone_number' => '+63 919 345 6789',
    'password' => 'cashier123',
    'position' => 'cashier'
];

try {
    // Check if cashier already exists
    $stmt = $db->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->execute([$cashierData['username']]);
    
    if ($stmt->fetch()) {
        echo "Cashier user already exists.\n";
    } else {
        // Create cashier user
        $stmt = $db->prepare("
            INSERT INTO users (username, name, password_hash, email, phone_number, position, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");
        
        $passwordHash = password_hash($cashierData['password'], PASSWORD_DEFAULT);
        
        $stmt->execute([
            $cashierData['username'],
            $cashierData['name'],
            $passwordHash,
            $cashierData['email'],
            $cashierData['phone_number'],
            $cashierData['position']
        ]);
        
        $userId = $db->lastInsertId();
        echo "Cashier user created successfully with ID: $userId\n";
        
        // Also create staff record
        $stmt = $db->prepare("
            INSERT INTO staff (name, position, contact_number, email, hire_date, salary, is_active) 
            VALUES (?, ?, ?, ?, CURDATE(), ?, 1)
        ");
        
        $stmt->execute([
            $cashierData['name'],
            $cashierData['position'],
            $cashierData['phone_number'],
            $cashierData['email'],
            18000.00
        ]);
        
        echo "Staff record created successfully.\n";
    }
    
    echo "\nCashier Login Credentials:\n";
    echo "Username: " . $cashierData['username'] . "\n";
    echo "Password: " . $cashierData['password'] . "\n";
    echo "Email: " . $cashierData['email'] . "\n";
    echo "Phone: " . $cashierData['phone_number'] . "\n";
    
} catch (Exception $e) {
    echo "Error creating cashier: " . $e->getMessage() . "\n";
}
?>
