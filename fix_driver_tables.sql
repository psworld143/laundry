-- Create missing tables for driver functionality
-- Run this SQL script to fix the driver dashboard error

-- Driver Payments Table
CREATE TABLE IF NOT EXISTS `driver_payments` (
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

-- Pickup Delivery Table
CREATE TABLE IF NOT EXISTS `pickup_delivery` (
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

-- Insert sample data
INSERT INTO `driver_payments` (`payment_id`, `order_id`, `processed_by`, `payment_method_id`, `amount_received`, `transaction_ref`, `notes`, `status`, `processed_at`) VALUES
(1, 1, 1, 1, 150.00, NULL, 'Cash payment received', 'completed', NOW()),
(2, 2, 1, 4, 200.00, 'GCASH123456789', 'GCash payment via QR scan', 'completed', NOW());

INSERT INTO `pickup_delivery` (`schedule_id`, `payment_id`, `user_id`, `driver_id`, `scheduled_date`, `status`, `notes`, `created_at`) VALUES
(1, 1, 1, 1, NOW(), 'scheduled', 'Regular pickup', NOW()),
(2, 2, 2, 1, NOW(), 'scheduled', 'Express delivery', NOW());
