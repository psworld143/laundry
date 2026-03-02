-- Driver Payments Table
-- Stores payments processed by drivers
-- Separate from regular payment processing

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
  KEY `payment_method_id` (`payment_method_id`),
  FOREIGN KEY (`order_id`) REFERENCES `transactions` (`payment_id`) ON DELETE CASCADE,
  FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`method_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample driver payments
INSERT INTO `driver_payments` (`payment_id`, `order_id`, `processed_by`, `payment_method_id`, `amount_received`, `transaction_ref`, `notes`, `status`, `processed_at`) VALUES
(1, 1, 1, 1, 150.00, NULL, 'Cash payment received', 'completed', '2025-01-27 10:30:00'),
(2, 2, 1, 4, 200.00, 'GCASH123456789', 'GCash payment via QR scan', 'completed', '2025-01-27 11:15:00');
