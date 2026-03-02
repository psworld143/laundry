<?php
require_once 'config.php';

echo "Setting up Admin Fabrics table...\n";

try {
    // Create admin_fabrics table
    $sql = "
    CREATE TABLE IF NOT EXISTS `admin_fabrics` (
      `fabric_id` int(11) NOT NULL AUTO_INCREMENT,
      `fabric_name` varchar(100) NOT NULL,
      `fabric_type` enum('cotton','polyester','wool','silk','linen','denim','leather','synthetic','other') NOT NULL,
      `price_multiplier` decimal(3,2) DEFAULT 1.00 COMMENT 'Multiplier for base service price',
      `wash_temperature` enum('cold','warm','hot','hand_wash','dry_clean') DEFAULT 'warm',
      `description` text DEFAULT NULL,
      `care_instructions` text DEFAULT NULL,
      `processing_time` int(11) DEFAULT 24 COMMENT 'Estimated processing time in hours',
      `is_popular` tinyint(1) DEFAULT 0 COMMENT 'Mark as popular fabric type',
      `is_active` tinyint(1) DEFAULT 1,
      `created_at` datetime DEFAULT current_timestamp(),
      `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`fabric_id`),
      KEY `fabric_type` (`fabric_type`),
      KEY `is_popular` (`is_popular`),
      KEY `is_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    
    $db->exec($sql);
    echo "✓ Admin fabrics table created successfully\n";
    
    // Insert sample fabric types
    $sampleFabrics = [
        ['Cotton', 'cotton', 1.00, 'warm', 'Natural fiber, breathable and comfortable', 'Machine wash warm, tumble dry medium heat. Can be ironed on high heat.', 24, 1],
        ['Silk', 'silk', 2.50, 'dry_clean', 'Luxurious natural fiber, delicate and smooth', 'Dry clean only. Do not machine wash. Handle with care.', 48, 1],
        ['Wool', 'wool', 2.00, 'hand_wash', 'Natural fiber, warm and insulating', 'Hand wash cold water only. Lay flat to dry. Do not tumble dry.', 36, 0],
        ['Denim', 'denim', 1.20, 'cold', 'Durable cotton twill fabric', 'Machine wash cold, hang dry to prevent shrinking. Can be tumble dried on low.', 24, 1],
        ['Polyester', 'polyester', 0.80, 'warm', 'Synthetic fiber, wrinkle-resistant', 'Machine wash warm, tumble dry low heat. Quick drying fabric.', 18, 0],
        ['Linen', 'linen', 1.50, 'warm', 'Natural fiber, lightweight and breathable', 'Machine wash warm, hang dry. Iron while damp for best results.', 30, 0],
        ['Leather', 'leather', 3.00, 'dry_clean', 'Animal hide, durable and luxurious', 'Professional leather cleaning only. Do not wet clean.', 72, 0],
        ['Synthetic Blends', 'synthetic', 1.10, 'warm', 'Mixed synthetic materials', 'Follow care label instructions. Usually machine washable.', 20, 0]
    ];
    
    $stmt = $db->prepare("
        INSERT INTO admin_fabrics 
        (fabric_name, fabric_type, price_multiplier, wash_temperature, description, care_instructions, processing_time, is_popular) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($sampleFabrics as $fabric) {
        $stmt->execute($fabric);
    }
    
    echo "✓ Sample fabric types inserted successfully\n";
    echo "✓ Setup completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
