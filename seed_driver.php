<?php
require_once 'config.php';

// Create driver user
$driverData = [
    'username' => 'driver',
    'name' => 'Pedro Garcia',
    'email' => 'pedro.garcia@laundrypro.com',
    'phone_number' => '+63 919 456 7890',
    'password' => 'driver123',
    'position' => 'driver'
];

try {
    // Check if driver already exists
    $stmt = $db->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->execute([$driverData['username']]);
    
    if ($stmt->fetch()) {
        echo "Driver user already exists.\n";
    } else {
        // Create driver user
        $stmt = $db->prepare("
            INSERT INTO users (username, name, password_hash, email, phone_number, position, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");
        
        $passwordHash = password_hash($driverData['password'], PASSWORD_DEFAULT);
        
        $stmt->execute([
            $driverData['username'],
            $driverData['name'],
            $passwordHash,
            $driverData['email'],
            $driverData['phone_number'],
            $driverData['position']
        ]);
        
        $userId = $db->lastInsertId();
        echo "Driver user created successfully with ID: $userId\n";
        
        // Also create staff record
        $stmt = $db->prepare("
            INSERT INTO staff (name, position, contact_number, email, hire_date, salary, is_active) 
            VALUES (?, ?, ?, ?, CURDATE(), ?, 1)
        ");
        
        $stmt->execute([
            $driverData['name'],
            $driverData['position'],
            $driverData['phone_number'],
            $driverData['email'],
            15000.00
        ]);
        
        echo "Staff record created successfully.\n";
    }
    
    echo "\nDriver Login Credentials:\n";
    echo "Username: " . $driverData['username'] . "\n";
    echo "Password: " . $driverData['password'] . "\n";
    echo "Email: " . $driverData['email'] . "\n";
    echo "Phone: " . $driverData['phone_number'] . "\n";
    
    echo "\nDriver Features:\n";
    echo "- Payment scanning with QR codes\n";
    echo "- Delivery route management\n";
    echo "- Pickup scheduling\n";
    echo "- Receipt generation\n";
    echo "- Mobile-optimized interface\n";
    
} catch (Exception $e) {
    echo "Error creating driver: " . $e->getMessage() . "\n";
}
?>
