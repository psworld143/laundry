-- Custom Orders Table for Customer-Specific Orders
-- This table stores orders created by customers using their own fabric inventory
-- Separate from admin-managed orders

CREATE TABLE IF NOT EXISTS `custom_orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `fabric_id` int(11) NOT NULL,
  `service_type` enum('wash','dry_clean') NOT NULL,
  `soap_type` enum('tide','downy','clorox','oxiclean') NOT NULL,
  `ironing` tinyint(1) DEFAULT 0,
  `express` tinyint(1) DEFAULT 0,
  `special_instructions` text DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `payment_method_id` int(11) DEFAULT 1,
  `payment_status` enum('pending','unpaid','paid','refunded') DEFAULT 'pending',
  `laundry_status` enum('pending','in_progress','washing','drying','ironing','ready','delivered','cancelled') DEFAULT 'pending',
  `estimated_completion` datetime DEFAULT NULL,
  `actual_completion` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`order_id`),
  KEY `user_id` (`user_id`),
  KEY `fabric_id` (`fabric_id`),
  KEY `payment_method_id` (`payment_method_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`fabric_id`) REFERENCES `customer_inventory_fabric` (`fabric_id`) ON DELETE CASCADE,
  FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`method_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample custom orders
INSERT INTO `custom_orders` (`order_id`, `user_id`, `fabric_id`, `service_type`, `soap_type`, `ironing`, `express`, `special_instructions`, `subtotal`, `payment_method_id`, `payment_status`, `laundry_status`, `estimated_completion`) VALUES
(1, 1, 1, 'wash', 'tide', 1, 0, 'Gentle cycle, cold water', 80.00, 1, 'pending', 'pending', '2025-01-28 12:00:00'),
(2, 1, 2, 'dry_clean', 'downy', 0, 1, 'Professional dry clean only', 130.00, 1, 'pending', 'pending', '2025-01-29 12:00:00');
