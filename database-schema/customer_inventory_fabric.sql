-- Customer Inventory Fabric Table
-- Allows customers to manage their own fabric inventory

CREATE TABLE `customer_inventory_fabric` (
  `fabric_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `fabric_name` varchar(100) NOT NULL,
  `fabric_type` enum('cotton','polyester','wool','silk','linen','denim','other') NOT NULL,
  `color` varchar(50) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit` varchar(20) DEFAULT 'piece',
  `condition_status` enum('new','good','fair','poor','damaged') DEFAULT 'good',
  `special_instructions` text DEFAULT NULL,
  `last_wash_date` date DEFAULT NULL,
  `next_wash_reminder` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add primary key and indexes
ALTER TABLE `customer_inventory_fabric`
  ADD PRIMARY KEY (`fabric_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fabric_type` (`fabric_type`),
  ADD KEY `condition_status` (`condition_status`);

-- Add auto increment
ALTER TABLE `customer_inventory_fabric`
  MODIFY `fabric_id` int(11) NOT NULL AUTO_INCREMENT;

-- Add foreign key constraint
ALTER TABLE `customer_inventory_fabric`
  ADD CONSTRAINT `customer_inventory_fabric_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

-- Insert sample data for testing
INSERT INTO `customer_inventory_fabric` (`user_id`, `fabric_name`, `fabric_type`, `color`, `quantity`, `unit`, `condition_status`, `special_instructions`, `last_wash_date`, `next_wash_reminder`) VALUES
(1, 'Cotton T-Shirt', 'cotton', 'White', 5, 'piece', 'good', 'Regular wash, no bleach', '2025-01-20', '2025-01-27'),
(1, 'Denim Jeans', 'denim', 'Blue', 3, 'piece', 'good', 'Cold wash, hang dry', '2025-01-18', '2025-01-25'),
(1, 'Silk Blouse', 'silk', 'Red', 2, 'piece', 'good', 'Dry clean only', NULL, NULL),
(1, 'Wool Sweater', 'wool', 'Gray', 1, 'piece', 'fair', 'Hand wash, cold water', '2025-01-15', '2025-01-22'),
(1, 'Polyester Dress', 'polyester', 'Black', 1, 'piece', 'good', 'Machine wash cold', '2025-01-19', '2025-01-26');

