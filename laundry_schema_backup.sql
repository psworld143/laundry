-- Updated Laundry Management System Database Schema
-- Enhanced with missing tables and improved structure
-- Generated: 2025-01-27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `laundry`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_addresses`
--

CREATE TABLE `customer_addresses` (
  `address_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_type` enum('home','work','other') DEFAULT 'home',
  `address` text NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `position` enum('manager','operator','driver','cashier') NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `hire_date` date NOT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `service_type` enum('wash_fold','dry_clean','ironing','express','pickup_delivery') NOT NULL,
  `estimated_duration` int(11) DEFAULT NULL COMMENT 'Duration in hours',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_items`
--

CREATE TABLE `service_items` (
  `item_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `service_id` int(11) NOT NULL,
  `price_multiplier` decimal(3,2) DEFAULT 1.00,
  `special_instructions` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `machines`
--

CREATE TABLE `machines` (
  `machine_id` int(11) NOT NULL,
  `machine_name` varchar(100) NOT NULL,
  `machine_type` enum('washer','dryer','iron','dry_cleaner','steam_cleaner') NOT NULL,
  `capacity` decimal(5,2) DEFAULT NULL COMMENT 'Capacity in kg',
  `status` enum('active','maintenance','broken','idle') DEFAULT 'active',
  `location` varchar(100) DEFAULT NULL,
  `last_maintenance` date DEFAULT NULL,
  `next_maintenance` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `method_id` int(11) NOT NULL,
  `method_name` varchar(50) NOT NULL,
  `is_online` tinyint(1) DEFAULT 0,
  `processing_fee` decimal(5,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `promotion_id` int(11) NOT NULL,
  `promotion_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage','fixed_amount','free_service') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `min_order_amount` decimal(10,2) DEFAULT NULL,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `inventory_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `item_type` enum('detergent','fabric_softener','bleach','stain_remover','other') NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `quantity` int(11) DEFAULT 0,
  `min_stock_level` int(11) DEFAULT 10,
  `unit` varchar(20) DEFAULT 'piece',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pricing`
--

CREATE TABLE `pricing` (
  `pricing_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `price_per_unit` decimal(10,2) NOT NULL,
  `basket_price` decimal(10,2) NOT NULL,
  `package_a` decimal(10,2) DEFAULT 0.00,
  `package_b` decimal(10,2) DEFAULT 0.00,
  `bulk_discount` decimal(5,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `payment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `basket_count` int(11) DEFAULT 0,
  `package` enum('none','package a','package b') DEFAULT 'none',
  `detergent_qty` int(11) DEFAULT 0,
  `softener_qty` int(11) DEFAULT 0,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `total_price` decimal(10,2) DEFAULT 0.00,
  `payment_method_id` int(11) NOT NULL,
  `payment_status` enum('pending','unpaid','paid','refunded') DEFAULT 'pending',
  `laundry_status` enum('pending','in_progress','washing','drying','ironing','ready','delivered','cancelled') DEFAULT 'pending',
  `customer_number` varchar(50) NOT NULL DEFAULT 'none',
  `account_name` varchar(100) NOT NULL DEFAULT 'none',
  `remarks` text DEFAULT NULL,
  `promotion_id` int(11) DEFAULT NULL,
  `estimated_completion` datetime DEFAULT NULL,
  `actual_completion` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `clothing_type` varchar(50) DEFAULT 'regular'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transaction_items`
--

CREATE TABLE `transaction_items` (
  `item_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `special_instructions` text DEFAULT NULL,
  `status` enum('pending','in_progress','completed') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pickup_delivery`
--

CREATE TABLE `pickup_delivery` (
  `schedule_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_id` int(11) NOT NULL,
  `type` enum('pickup','delivery') NOT NULL,
  `scheduled_date` datetime NOT NULL,
  `actual_date` datetime DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled','failed') DEFAULT 'scheduled',
  `driver_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `fee` decimal(10,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `machine_usage`
--

CREATE TABLE `machine_usage` (
  `usage_id` int(11) NOT NULL,
  `machine_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `cycle_type` varchar(50) DEFAULT NULL,
  `load_weight` decimal(5,2) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `rating_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `issue` enum('Lost Item','Delayed Service','Quality Issue','Billing Problem','Other') NOT NULL,
  `message` text DEFAULT NULL,
  `admin_reply` text DEFAULT NULL,
  `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `seen` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_messages`
--

CREATE TABLE `report_messages` (
  `message_id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_type` enum('user','admin','staff') NOT NULL,
  `message` text NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages` (Legacy - keeping for compatibility)
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Insert sample data for new tables
--

-- Services
INSERT INTO `services` (`service_id`, `service_name`, `description`, `base_price`, `service_type`, `estimated_duration`, `is_active`) VALUES
(1, 'Wash & Fold', 'Regular wash and fold service', 50.00, 'wash_fold', 24, 1),
(2, 'Dry Cleaning', 'Professional dry cleaning service', 100.00, 'dry_clean', 48, 1),
(3, 'Ironing Only', 'Ironing service for clean clothes', 30.00, 'ironing', 12, 1),
(4, 'Express Service', 'Same day wash and fold', 80.00, 'express', 6, 1),
(5, 'Pickup & Delivery', 'Door-to-door service', 25.00, 'pickup_delivery', 1, 1);

-- Service Items
INSERT INTO `service_items` (`item_id`, `item_name`, `category`, `service_id`, `price_multiplier`, `special_instructions`) VALUES
(1, 'T-Shirt', 'shirts', 1, 1.00, 'Regular wash'),
(2, 'Dress Shirt', 'shirts', 1, 1.50, 'Delicate cycle'),
(3, 'Jeans', 'pants', 1, 1.20, 'Heavy duty cycle'),
(4, 'Dress', 'dresses', 2, 2.00, 'Dry clean only'),
(5, 'Suit', 'formal', 2, 3.00, 'Professional dry clean'),
(6, 'Blanket', 'bedding', 1, 2.50, 'Large load');

-- Payment Methods
INSERT INTO `payment_methods` (`method_id`, `method_name`, `is_online`, `processing_fee`, `is_active`) VALUES
(1, 'Cash', 0, 0.00, 1),
(2, 'Credit Card', 1, 3.00, 1),
(3, 'Debit Card', 1, 2.00, 1),
(4, 'GCash', 1, 5.00, 1),
(5, 'PayMaya', 1, 5.00, 1),
(6, 'Bank Transfer', 1, 0.00, 1);

-- Staff
INSERT INTO `staff` (`staff_id`, `name`, `position`, `contact_number`, `email`, `hire_date`, `salary`, `is_active`) VALUES
(1, 'Juan Dela Cruz', 'manager', '09123456789', 'juan@laundry.com', '2024-01-15', 25000.00, 1),
(2, 'Maria Santos', 'operator', '09123456788', 'maria@laundry.com', '2024-02-01', 18000.00, 1),
(3, 'Pedro Garcia', 'driver', '09123456787', 'pedro@laundry.com', '2024-02-15', 15000.00, 1),
(4, 'Ana Rodriguez', 'cashier', '09123456786', 'ana@laundry.com', '2024-03-01', 16000.00, 1);

-- Machines
INSERT INTO `machines` (`machine_id`, `machine_name`, `machine_type`, `capacity`, `status`, `location`, `last_maintenance`, `next_maintenance`) VALUES
(1, 'Washer-001', 'washer', 15.00, 'active', 'Wash Area A', '2024-12-01', '2025-03-01'),
(2, 'Washer-002', 'washer', 20.00, 'active', 'Wash Area A', '2024-12-15', '2025-03-15'),
(3, 'Dryer-001', 'dryer', 15.00, 'active', 'Dry Area A', '2024-11-20', '2025-02-20'),
(4, 'Dryer-002', 'dryer', 20.00, 'active', 'Dry Area A', '2024-12-10', '2025-03-10'),
(5, 'Iron-001', 'iron', 5.00, 'active', 'Ironing Station', '2024-12-05', '2025-01-05'),
(6, 'DryClean-001', 'dry_cleaner', 10.00, 'active', 'Dry Clean Area', '2024-11-30', '2025-02-28');

-- Updated Inventory
INSERT INTO `inventory` (`inventory_id`, `item_name`, `item_type`, `brand`, `price`, `quantity`, `min_stock_level`, `unit`) VALUES
(1, 'Tide Detergent', 'detergent', 'Tide', 20.00, 12323, 50, 'bottle'),
(2, 'Downy Fabric Softener', 'fabric_softener', 'Downy', 20.00, 0, 30, 'bottle'),
(3, 'Clorox Bleach', 'bleach', 'Clorox', 15.00, 25, 20, 'bottle'),
(4, 'OxiClean Stain Remover', 'stain_remover', 'OxiClean', 25.00, 15, 10, 'bottle');

-- Promotions
INSERT INTO `promotions` (`promotion_id`, `promotion_name`, `description`, `discount_type`, `discount_value`, `start_date`, `end_date`, `min_order_amount`, `max_discount`, `usage_limit`, `is_active`) VALUES
(1, 'New Customer Discount', '20% off first order', 'percentage', 20.00, '2025-01-01', '2025-12-31', 100.00, 50.00, 1, 1),
(2, 'Bulk Order Discount', '10% off orders over 500', 'percentage', 10.00, '2025-01-01', '2025-12-31', 500.00, 100.00, NULL, 1),
(3, 'Free Delivery Weekend', 'Free delivery on weekends', 'fixed_amount', 25.00, '2025-01-01', '2025-12-31', 200.00, 25.00, NULL, 1);

-- --------------------------------------------------------

--
-- Indexes for dumped tables
--

-- Users
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

-- Customer Addresses
ALTER TABLE `customer_addresses`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `user_id` (`user_id`);

-- Staff
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `email` (`email`);

-- Services
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`);

-- Service Items
ALTER TABLE `service_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `service_id` (`service_id`);

-- Machines
ALTER TABLE `machines`
  ADD PRIMARY KEY (`machine_id`);

-- Payment Methods
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`method_id`);

-- Promotions
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`promotion_id`);

-- Inventory
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`inventory_id`);

-- Pricing
ALTER TABLE `pricing`
  ADD PRIMARY KEY (`pricing_id`),
  ADD KEY `service_id` (`service_id`);

-- Transactions
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `payment_method_id` (`payment_method_id`),
  ADD KEY `promotion_id` (`promotion_id`);

-- Transaction Items
ALTER TABLE `transaction_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `payment_id` (`payment_id`),
  ADD KEY `service_id` (`service_id`);

-- Pickup Delivery
ALTER TABLE `pickup_delivery`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `payment_id` (`payment_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `address_id` (`address_id`),
  ADD KEY `driver_id` (`driver_id`);

-- Machine Usage
ALTER TABLE `machine_usage`
  ADD PRIMARY KEY (`usage_id`),
  ADD KEY `machine_id` (`machine_id`),
  ADD KEY `payment_id` (`payment_id`),
  ADD KEY `staff_id` (`staff_id`);

-- Ratings
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`rating_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `payment_id` (`payment_id`);

-- Reports
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `payment_id` (`payment_id`);

-- Report Messages
ALTER TABLE `report_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `report_id` (`report_id`),
  ADD KEY `sender_id` (`sender_id`);

-- Messages (Legacy)
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `report_id` (`report_id`),
  ADD KEY `user_id` (`user_id`);

-- --------------------------------------------------------

--
-- AUTO_INCREMENT for dumped tables
--

ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

ALTER TABLE `customer_addresses`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `service_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

ALTER TABLE `machines`
  MODIFY `machine_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

ALTER TABLE `payment_methods`
  MODIFY `method_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

ALTER TABLE `promotions`
  MODIFY `promotion_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `inventory`
  MODIFY `inventory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `pricing`
  MODIFY `pricing_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `transactions`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

ALTER TABLE `transaction_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `pickup_delivery`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `machine_usage`
  MODIFY `usage_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `ratings`
  MODIFY `rating_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

ALTER TABLE `report_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

-- --------------------------------------------------------

--
-- Constraints for dumped tables
--

-- Customer Addresses
ALTER TABLE `customer_addresses`
  ADD CONSTRAINT `customer_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

-- Service Items
ALTER TABLE `service_items`
  ADD CONSTRAINT `service_items_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE;

-- Pricing
ALTER TABLE `pricing`
  ADD CONSTRAINT `pricing_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE;

-- Transactions
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`method_id`),
  ADD CONSTRAINT `transactions_ibfk_4` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`promotion_id`) ON DELETE SET NULL;

-- Transaction Items
ALTER TABLE `transaction_items`
  ADD CONSTRAINT `transaction_items_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `transactions` (`payment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaction_items_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`);

-- Pickup Delivery
ALTER TABLE `pickup_delivery`
  ADD CONSTRAINT `pickup_delivery_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `transactions` (`payment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pickup_delivery_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pickup_delivery_ibfk_3` FOREIGN KEY (`address_id`) REFERENCES `customer_addresses` (`address_id`),
  ADD CONSTRAINT `pickup_delivery_ibfk_4` FOREIGN KEY (`driver_id`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL;

-- Machine Usage
ALTER TABLE `machine_usage`
  ADD CONSTRAINT `machine_usage_ibfk_1` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`machine_id`),
  ADD CONSTRAINT `machine_usage_ibfk_2` FOREIGN KEY (`payment_id`) REFERENCES `transactions` (`payment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `machine_usage_ibfk_3` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`);

-- Ratings
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`payment_id`) REFERENCES `transactions` (`payment_id`) ON DELETE SET NULL;

-- Reports
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`payment_id`) REFERENCES `transactions` (`payment_id`) ON DELETE SET NULL;

-- Report Messages
ALTER TABLE `report_messages`
  ADD CONSTRAINT `report_messages_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reports` (`report_id`) ON DELETE CASCADE;

-- Messages (Legacy)
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reports` (`report_id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
