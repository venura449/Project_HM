-- Boomerang Project Database Backup
-- Generated: 2025-06-29 13:24:11

CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('super_admin','admin') DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admins` VALUES ('1', 'admin', 'admin@boomerang.com', '$2y$10$UL1K5OCIq90mP0ZjUCTY8O9Z.UGI9nUTsX5oLj6Ad033FYVYVKkK.', 'System Administrator', 'super_admin', '1', '2025-06-29 16:53:49', '2025-06-29 10:37:44', '2025-06-29 16:53:49');
INSERT INTO `admins` VALUES ('4', 'venura', 'Venurajayasingha1@gmail.com', '$2y$10$d2JzzoDkVynRGbowM0ZqCuo8NHxbdIn75OI7k6qeiu.AQSroeR20e', 'Venura Jayasingha', 'admin', '1', '2025-06-29 16:51:05', '2025-06-29 10:54:52', '2025-06-29 16:51:05');

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `booking_number` varchar(20) NOT NULL,
  `room_type` varchar(100) NOT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `num_guests` int(11) DEFAULT 1,
  `num_rooms` int(11) DEFAULT 1,
  `status` enum('pending','confirmed','checked_in','checked_out','cancelled','no_show') DEFAULT 'pending',
  `total_amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid','refunded','partial') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `special_requests` text DEFAULT NULL,
  `room_number` varchar(20) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_number` (`booking_number`),
  KEY `customer_id` (`customer_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_check_in_date` (`check_in_date`),
  KEY `idx_check_out_date` (`check_out_date`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_status` (`payment_status`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `bookings` VALUES ('1', '1', 'BK001', 'Deluxe Room', '2024-01-15', '2024-01-17', '2', '1', 'checked_out', '299.00', 'paid', 'credit_card', NULL, NULL, '1', '2025-06-29 14:40:05', '2025-06-29 14:40:05');
INSERT INTO `bookings` VALUES ('2', '2', 'BK002', 'Suite', '2024-01-16', '2024-01-19', '4', '1', 'checked_out', '599.00', 'paid', 'cash', NULL, NULL, '1', '2025-06-29 14:40:05', '2025-06-29 14:40:05');
INSERT INTO `bookings` VALUES ('3', '3', 'BK003', 'Standard Room', '2024-01-17', '2024-01-18', '1', '1', 'checked_out', '149.00', 'paid', 'credit_card', NULL, NULL, '1', '2025-06-29 14:40:05', '2025-06-29 14:40:05');
INSERT INTO `bookings` VALUES ('4', '4', 'BK004', 'Family Room', '2024-01-18', '2024-01-21', '6', '1', 'confirmed', '449.00', 'pending', 'credit_card', NULL, NULL, '1', '2025-06-29 14:40:05', '2025-06-29 14:40:05');
INSERT INTO `bookings` VALUES ('5', '5', 'BK005', 'Deluxe Room', '2024-01-19', '2024-01-20', '2', '1', 'pending', '199.00', 'pending', 'cash', NULL, NULL, '1', '2025-06-29 14:40:05', '2025-06-29 14:40:05');
INSERT INTO `bookings` VALUES ('6', '6', 'BK006', 'Presidential Suite', '2024-01-20', '2024-01-23', '2', '1', 'pending', '899.00', 'pending', 'credit_card', NULL, NULL, '1', '2025-06-29 14:40:05', '2025-06-29 14:40:05');
INSERT INTO `bookings` VALUES ('7', '7', 'BK007', 'Standard Room', '2024-01-21', '2024-01-22', '1', '1', 'pending', '129.00', 'pending', 'cash', NULL, NULL, '1', '2025-06-29 14:40:05', '2025-06-29 14:40:05');
INSERT INTO `bookings` VALUES ('8', '8', 'BK008', 'Deluxe Room', '2024-01-22', '2024-01-24', '2', '1', 'pending', '398.00', 'pending', 'credit_card', NULL, NULL, '1', '2025-06-29 14:40:05', '2025-06-29 14:40:05');
INSERT INTO `bookings` VALUES ('9', '9', 'BK009', 'Suite', '2024-01-23', '2024-01-25', '3', '1', 'pending', '499.00', 'pending', 'cash', NULL, NULL, '1', '2025-06-29 14:40:05', '2025-06-29 14:40:05');
INSERT INTO `bookings` VALUES ('10', '10', 'BK010', 'Standard Room', '2024-01-24', '2024-01-26', '2', '1', 'pending', '258.00', 'pending', 'credit_card', NULL, NULL, '1', '2025-06-29 14:40:05', '2025-06-29 14:40:05');

CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `country` varchar(50) DEFAULT 'USA',
  `customer_type` enum('individual','business','vip') DEFAULT 'individual',
  `status` enum('active','inactive','blocked') DEFAULT 'active',
  `total_spent` decimal(10,2) DEFAULT 0.00,
  `total_bookings` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_customer_type` (`customer_type`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `customers` VALUES ('1', 'John', 'Doe', 'john.doe@email.com', '555-0101', '123 Main St', 'New York', 'NY', '10001', 'USA', 'individual', 'active', '90.00', '1', NULL, '2025-06-29 11:21:58', '2025-06-29 11:21:58');
INSERT INTO `customers` VALUES ('2', 'Jane', 'Smith', 'jane.smith@email.com', '555-0102', '456 Oak Ave', 'Los Angeles', 'CA', '90210', 'USA', 'business', 'active', '120.00', '1', NULL, '2025-06-29 11:21:58', '2025-06-29 11:21:58');
INSERT INTO `customers` VALUES ('3', 'Mike', 'Johnson', 'mike.johnson@email.com', '555-0103', '789 Pine Rd', 'Chicago', 'IL', '60601', 'USA', 'vip', 'active', '190.00', '1', NULL, '2025-06-29 11:21:58', '2025-06-29 11:21:58');
INSERT INTO `customers` VALUES ('4', 'Sarah', 'Williams', 'sarah.williams@email.com', '555-0104', '321 Elm St', 'Houston', 'TX', '77001', 'USA', 'individual', 'active', '195.00', '1', NULL, '2025-06-29 11:21:58', '2025-06-29 11:21:58');
INSERT INTO `customers` VALUES ('5', 'David', 'Brown', 'david.brown@email.com', '555-0105', '654 Maple Dr', 'Phoenix', 'AZ', '85001', 'USA', 'business', 'active', '105.00', '1', NULL, '2025-06-29 11:21:58', '2025-06-29 11:21:58');
INSERT INTO `customers` VALUES ('6', 'Lisa', 'Davis', 'lisa.davis@email.com', '555-0106', '987 Cedar Ln', 'Philadelphia', 'PA', '19101', 'USA', 'vip', 'active', '260.00', '1', NULL, '2025-06-29 11:21:58', '2025-06-29 11:21:58');
INSERT INTO `customers` VALUES ('7', 'Robert', 'Miller', 'robert.miller@email.com', '555-0107', '147 Birch Way', 'San Antonio', 'TX', '78201', 'USA', 'individual', 'active', '55.00', '1', NULL, '2025-06-29 11:21:58', '2025-06-29 11:21:58');
INSERT INTO `customers` VALUES ('8', 'Emily', 'Wilson', 'emily.wilson@email.com', '555-0108', '258 Spruce Ct', 'San Diego', 'CA', '92101', 'USA', 'business', 'active', '86.00', '1', NULL, '2025-06-29 11:21:58', '2025-06-29 11:21:58');
INSERT INTO `customers` VALUES ('9', 'James', 'Taylor', 'james.taylor@email.com', '555-0109', '369 Willow Pl', 'Dallas', 'TX', '75201', 'USA', 'vip', 'active', '127.00', '1', NULL, '2025-06-29 11:21:58', '2025-06-29 11:21:58');
INSERT INTO `customers` VALUES ('10', 'Amanda', 'Anderson', 'amanda.anderson@email.com', '555-0110', '741 Poplar St', 'San Jose', 'CA', '95101', 'USA', 'individual', 'active', '91.00', '1', NULL, '2025-06-29 11:21:58', '2025-06-29 11:21:58');

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


