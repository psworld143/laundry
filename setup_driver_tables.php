<?php
require_once 'config.php';

echo "Checking and creating driver tables...\n\n";

try {
    // Check existing tables
    $stmt = $db->query("SHOW TABLES LIKE 'driver_payments'");
    $driverPaymentsExists = $stmt->fetch();
    
    $stmt = $db->query("SHOW TABLES LIKE 'pickup_delivery'");
    $pickupDeliveryExists = $stmt->fetch();
    
    echo "Current table status:\n";
    echo "- driver_payments: " . ($driverPaymentsExists ? "✓ EXISTS" : "❌ MISSING") . "\n";
    echo "- pickup_delivery: " . ($pickupDeliveryExists ? "✓ EXISTS" : "❌ MISSING") . "\n\n";
    
    // Create driver_payments table if it doesn't exist
    if (!$driverPaymentsExists) {
        echo "Creating driver_payments table...\n";
        $sql = "
        CREATE TABLE `driver_payments` (
          `payment_id` int(11) NOT NULL AUTO_INCREMENT,
          `order_id` int(11) NOT NULL,
          `processed_by` int(11) NOT NULL,
          `payment_method_id` int(11) NOT NULL,
          `amount_received` decimal(10,2) NOT NULL,
          `transaction_ref` varchar(100) DEFAULT NULL,
          `notes` text DEFAULT NULL,
          `status` enum('completed','cancelled','refunded') DEFAULT 'completed',
          `processed_at` datetime DEFAULT current_timestamp(),
          `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`payment_id`),
          KEY `order_id` (`order_id`),
          KEY `processed_by` (`processed_by`),
          KEY `payment_method_id` (`payment_method_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ";
        $db->exec($sql);
        echo "✓ driver_payments table created successfully!\n";
    } else {
        echo "✓ driver_payments table already exists\n";
    }
    
    // Create pickup_delivery table if it doesn't exist
    if (!$pickupDeliveryExists) {
        echo "Creating pickup_delivery table...\n";
        $sql = "
        CREATE TABLE `pickup_delivery` (
          `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
          `payment_id` int(11) NOT NULL,
          `user_id` int(11) NOT NULL,
          `address_id` int(11) DEFAULT NULL,
          `driver_id` int(11) DEFAULT NULL,
          `scheduled_date` datetime NOT NULL,
          `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
          `notes` text DEFAULT NULL,
          `created_at` datetime DEFAULT current_timestamp(),
          PRIMARY KEY (`schedule_id`),
          KEY `payment_id` (`payment_id`),
          KEY `user_id` (`user_id`),
          KEY `driver_id` (`driver_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ";
        $db->exec($sql);
        echo "✓ pickup_delivery table created successfully!\n";
    } else {
        echo "✓ pickup_delivery table already exists\n";
    }
    
    // Check if we need to insert sample data
    if (!$driverPaymentsExists || !$pickupDeliveryExists) {
        echo "\nInserting sample data...\n";
        
        // Insert sample driver payments (only if table was just created)
        if (!$driverPaymentsExists) {
            $stmt = $db->prepare("INSERT INTO `driver_payments` (`order_id`, `processed_by`, `payment_method_id`, `amount_received`, `transaction_ref`, `notes`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            // Get first user ID for sample data
            $userStmt = $db->query("SELECT user_id FROM users WHERE position = 'driver' LIMIT 1");
            $driverUserId = $userStmt->fetchColumn() ?: 1;
            
            // Get first payment method ID
            $pmStmt = $db->query("SELECT method_id FROM payment_methods LIMIT 1");
            $paymentMethodId = $pmStmt->fetchColumn() ?: 1;
            
            $stmt->execute([1, $driverUserId, $paymentMethodId, 150.00, NULL, 'Cash payment received', 'completed']);
            $stmt->execute([2, $driverUserId, $paymentMethodId, 200.00, 'GCASH123456789', 'GCash payment via QR scan', 'completed']);
            echo "✓ Sample driver payments inserted\n";
        }
        
        // Insert sample pickup deliveries (only if table was just created)
        if (!$pickupDeliveryExists) {
            $stmt = $db->prepare("INSERT INTO `pickup_delivery` (`payment_id`, `user_id`, `driver_id`, `scheduled_date`, `status`, `notes`) VALUES (?, ?, ?, ?, ?, ?)");
            
            // Get first user ID for sample data
            $userStmt = $db->query("SELECT user_id FROM users WHERE position = 'driver' LIMIT 1");
            $driverUserId = $userStmt->fetchColumn() ?: 1;
            
            $stmt->execute([1, 1, $driverUserId, date('Y-m-d H:i:s'), 'scheduled', 'Regular pickup']);
            $stmt->execute([2, 2, $driverUserId, date('Y-m-d H:i:s'), 'scheduled', 'Express delivery']);
            echo "✓ Sample pickup deliveries inserted\n";
        }
    }
    
    echo "\n🎉 Driver tables setup completed successfully!\n";
    echo "\nYou can now:\n";
    echo "1. Refresh the driver dashboard\n";
    echo "2. Test the payment scanner\n";
    echo "3. View delivery statistics\n";
    echo "4. Process customer payments\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Check database connection\n";
    echo "2. Verify database permissions\n";
    echo "3. Check if tables already exist with different structure\n";
}
?>
