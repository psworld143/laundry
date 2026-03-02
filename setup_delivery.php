<?php
require_once 'config.php';

echo "<!DOCTYPE html><html><head><title>Delivery Setup</title>";
echo "<script src='https://cdn.tailwindcss.com'></script>";
echo "</head><body class='bg-gray-100 p-8'>";
echo "<div class='max-w-4xl mx-auto bg-white rounded-lg shadow p-6'>";

echo "<h1 class='text-2xl font-bold mb-6'>Delivery Tables Setup</h1>";

try {
    // Check if delivery tables exist
    $tables = ['pickup_delivery', 'driver_payments'];
    $missingTables = [];
    
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->fetch();
        
        if (!$exists) {
            $missingTables[] = $table;
        }
    }
    
    if (empty($missingTables)) {
        echo "<div class='bg-green-100 p-4 rounded mb-6'>";
        echo "<p class='text-green-800'>✅ All delivery tables already exist</p>";
        echo "</div>";
    } else {
        echo "<div class='bg-yellow-100 p-4 rounded mb-6'>";
        echo "<p class='text-yellow-800'>⚠️ Missing tables: " . implode(', ', $missingTables) . "</p>";
        echo "<p class='text-yellow-700'>Creating delivery tables...</p>";
        echo "</div>";
        
        // Read and execute delivery tables setup
        $deliverySqlFile = __DIR__ . '/database-schema/pickup_delivery.sql';
        
        if (file_exists($deliverySqlFile)) {
            $sql = file_get_contents($deliverySqlFile);
            
            // Split SQL into individual statements
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($statements as $statement) {
                if (!empty($statement) && !preg_match('/^--/', $statement)) {
                    try {
                        $db->exec($statement);
                        echo "<p class='text-sm text-gray-600'>✓ Executed: " . substr($statement, 0, 50) . "...</p>";
                    } catch (Exception $e) {
                        echo "<p class='text-sm text-red-600'>✗ Error: " . $e->getMessage() . "</p>";
                    }
                }
            }
            
            echo "<div class='bg-green-100 p-4 rounded mt-6'>";
            echo "<p class='text-green-800'>✅ Delivery tables created successfully!</p>";
            echo "</div>";
        } else {
            // Create tables manually if SQL file doesn't exist
            $createTables = [
                "CREATE TABLE IF NOT EXISTS pickup_delivery (
                    delivery_id INT AUTO_INCREMENT PRIMARY KEY,
                    order_id INT NOT NULL,
                    customer_id INT NOT NULL,
                    pickup_address TEXT NOT NULL,
                    delivery_address TEXT NOT NULL,
                    pickup_date DATETIME NOT NULL,
                    delivery_date DATETIME NOT NULL,
                    driver_id INT NULL,
                    status ENUM('pending', 'assigned', 'picked_up', 'in_transit', 'delivered', 'cancelled') DEFAULT 'pending',
                    notes TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (order_id) REFERENCES orders(order_id),
                    FOREIGN KEY (customer_id) REFERENCES users(user_id),
                    FOREIGN KEY (driver_id) REFERENCES users(user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
                
                "CREATE TABLE IF NOT EXISTS driver_payments (
                    payment_id INT AUTO_INCREMENT PRIMARY KEY,
                    driver_id INT NOT NULL,
                    delivery_id INT NOT NULL,
                    amount DECIMAL(10,2) NOT NULL,
                    payment_date DATE NOT NULL,
                    status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
                    notes TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (driver_id) REFERENCES users(user_id),
                    FOREIGN KEY (delivery_id) REFERENCES pickup_delivery(delivery_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
            ];
            
            foreach ($createTables as $sql) {
                try {
                    $db->exec($sql);
                    echo "<p class='text-sm text-green-600'>✓ Table created successfully</p>";
                } catch (Exception $e) {
                    echo "<p class='text-sm text-red-600'>✗ Error creating table: " . $e->getMessage() . "</p>";
                }
            }
        }
    }
    
    // Check if driver position exists in users table
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'position'");
    $column = $stmt->fetch();
    
    if ($column) {
        // Check if 'driver' is in the enum values
        $stmt = $db->query("DESCRIBE users position");
        $result = $stmt->fetch();
        $enumValues = $result['Type'];
        
        if (strpos($enumValues, "'driver'") === false) {
            echo "<div class='bg-yellow-100 p-4 rounded mb-6'>";
            echo "<p class='text-yellow-800'>⚠️ Adding 'driver' position to users table...</p>";
            echo "</div>";
            
            try {
                $db->exec("ALTER TABLE users MODIFY COLUMN position ENUM('admin','user','driver') NOT NULL DEFAULT 'user'");
                echo "<p class='text-sm text-green-600'>✓ Driver position added to users table</p>";
            } catch (Exception $e) {
                echo "<p class='text-sm text-red-600'>✗ Error adding driver position: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    // Display current table status
    echo "<div class='mt-8'>";
    echo "<h2 class='text-xl font-bold mb-4'>Current Table Status</h2>";
    
    $allTables = ['pickup_delivery', 'driver_payments'];
    foreach ($allTables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->fetch();
        
        echo "<div class='mb-2'>";
        echo "<span class='font-medium'>$table:</span> ";
        echo $exists ? "<span class='text-green-600'>✅ Exists</span>" : "<span class='text-red-600'>❌ Missing</span>";
        echo "</div>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='bg-red-100 p-4 rounded'>";
    echo "<p class='text-red-800'>❌ Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<div class='mt-8 flex gap-4'>";
echo "<a href='services.php' class='bg-blue-500 text-white px-6 py-3 rounded-lg'>View Services</a>";
echo "<a href='seed_services.php' class='bg-green-500 text-white px-6 py-3 rounded-lg'>Seed Services</a>";
echo "<a href='setup.php' class='bg-gray-500 text-white px-6 py-3 rounded-lg'>Main Setup</a>";
echo "</div>";

echo "</div></body></html>";
?>
