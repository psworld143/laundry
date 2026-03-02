<?php
/**
 * Create Users Table Script
 */

require_once 'config.php';

echo "=== CREATING USERS TABLE ===\n\n";

$sql = "CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `position` enum('admin','user') NOT NULL DEFAULT 'user',
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

try {
    $db->exec($sql);
    echo "✓ Users table created successfully\n";
    
    // Add primary key
    $db->exec("ALTER TABLE `users` ADD PRIMARY KEY (`user_id`)");
    echo "✓ Primary key added\n";
    
    // Add auto increment
    $db->exec("ALTER TABLE `users` MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT");
    echo "✓ Auto increment added\n";
    
    // Add unique constraints
    $db->exec("ALTER TABLE `users` ADD UNIQUE KEY `username` (`username`)");
    $db->exec("ALTER TABLE `users` ADD UNIQUE KEY `email` (`email`)");
    echo "✓ Unique constraints added\n";
    
    echo "\n=== USERS TABLE READY ===\n";
    
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "⚠ Users table already exists\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
