-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 29, 2025 at 03:27 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `boomerang_admin`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('super_admin','admin') DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `full_name`, `role`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@boomerang.com', '$2y$10$UL1K5OCIq90mP0ZjUCTY8O9Z.UGI9nUTsX5oLj6Ad033FYVYVKkK.', 'System Administrator', 'super_admin', 1, '2025-06-29 18:49:49', '2025-06-29 05:07:44', '2025-06-29 13:19:49'),
(4, 'venura', 'Venurajayasingha1@gmail.com', '$2y$10$d2JzzoDkVynRGbowM0ZqCuo8NHxbdIn75OI7k6qeiu.AQSroeR20e', 'Venura Jayasingha', 'admin', 1, '2025-06-29 18:35:25', '2025-06-29 05:24:52', '2025-06-29 13:05:25');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `customer_id`, `booking_number`, `room_type`, `check_in_date`, `check_out_date`, `num_guests`, `num_rooms`, `status`, `total_amount`, `payment_status`, `payment_method`, `special_requests`, `room_number`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'BK001', 'Deluxe Room', '2024-01-15', '2024-01-17', 2, 1, 'checked_out', 299.00, 'paid', 'credit_card', NULL, NULL, 1, '2025-06-29 09:10:05', '2025-06-29 09:10:05'),
(2, 2, 'BK002', 'Suite', '2024-01-16', '2024-01-19', 4, 1, 'checked_out', 599.00, 'paid', 'cash', NULL, NULL, 1, '2025-06-29 09:10:05', '2025-06-29 09:10:05'),
(3, 3, 'BK003', 'Standard Room', '2024-01-17', '2024-01-18', 1, 1, 'checked_out', 149.00, 'paid', 'credit_card', NULL, NULL, 1, '2025-06-29 09:10:05', '2025-06-29 09:10:05'),
(4, 4, 'BK004', 'Family Room', '2024-01-18', '2024-01-21', 6, 1, 'confirmed', 449.00, 'pending', 'credit_card', NULL, NULL, 1, '2025-06-29 09:10:05', '2025-06-29 09:10:05'),
(5, 5, 'BK005', 'Deluxe Room', '2024-01-19', '2024-01-20', 2, 1, 'pending', 199.00, 'pending', 'cash', NULL, NULL, 1, '2025-06-29 09:10:05', '2025-06-29 09:10:05'),
(6, 6, 'BK006', 'Presidential Suite', '2024-01-20', '2024-01-23', 2, 1, 'pending', 899.00, 'pending', 'credit_card', NULL, NULL, 1, '2025-06-29 09:10:05', '2025-06-29 09:10:05'),
(7, 7, 'BK007', 'Standard Room', '2024-01-21', '2024-01-22', 1, 1, 'pending', 129.00, 'pending', 'cash', NULL, NULL, 1, '2025-06-29 09:10:05', '2025-06-29 09:10:05'),
(8, 8, 'BK008', 'Deluxe Room', '2024-01-22', '2024-01-24', 2, 1, 'pending', 398.00, 'pending', 'credit_card', NULL, NULL, 1, '2025-06-29 09:10:05', '2025-06-29 09:10:05'),
(9, 9, 'BK009', 'Suite', '2024-01-23', '2024-01-25', 3, 1, 'pending', 499.00, 'pending', 'cash', NULL, NULL, 1, '2025-06-29 09:10:05', '2025-06-29 09:10:05'),
(10, 10, 'BK010', 'Standard Room', '2024-01-24', '2024-01-26', 2, 1, 'pending', 258.00, 'pending', 'credit_card', NULL, NULL, 1, '2025-06-29 09:10:05', '2025-06-29 09:10:05');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `first_name`, `last_name`, `email`, `phone`, `address`, `city`, `state`, `zip_code`, `country`, `customer_type`, `status`, `total_spent`, `total_bookings`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'John', 'Doe', 'john.doe@email.com', '555-0101', '123 Main St', 'New York', 'NY', '10001', 'USA', 'individual', 'active', 90.00, 1, NULL, '2025-06-29 05:51:58', '2025-06-29 05:51:58'),
(2, 'Jane', 'Smith', 'jane.smith@email.com', '555-0102', '456 Oak Ave', 'Los Angeles', 'CA', '90210', 'USA', 'business', 'active', 120.00, 1, NULL, '2025-06-29 05:51:58', '2025-06-29 05:51:58'),
(3, 'Mike', 'Johnson', 'mike.johnson@email.com', '555-0103', '789 Pine Rd', 'Chicago', 'IL', '60601', 'USA', 'vip', 'active', 190.00, 1, NULL, '2025-06-29 05:51:58', '2025-06-29 05:51:58'),
(4, 'Sarah', 'Williams', 'sarah.williams@email.com', '555-0104', '321 Elm St', 'Houston', 'TX', '77001', 'USA', 'individual', 'active', 195.00, 1, NULL, '2025-06-29 05:51:58', '2025-06-29 05:51:58'),
(5, 'David', 'Brown', 'david.brown@email.com', '555-0105', '654 Maple Dr', 'Phoenix', 'AZ', '85001', 'USA', 'business', 'active', 105.00, 1, NULL, '2025-06-29 05:51:58', '2025-06-29 05:51:58'),
(6, 'Lisa', 'Davis', 'lisa.davis@email.com', '555-0106', '987 Cedar Ln', 'Philadelphia', 'PA', '19101', 'USA', 'vip', 'active', 260.00, 1, NULL, '2025-06-29 05:51:58', '2025-06-29 05:51:58'),
(7, 'Robert', 'Miller', 'robert.miller@email.com', '555-0107', '147 Birch Way', 'San Antonio', 'TX', '78201', 'USA', 'individual', 'active', 55.00, 1, NULL, '2025-06-29 05:51:58', '2025-06-29 05:51:58'),
(8, 'Emily', 'Wilson', 'emily.wilson@email.com', '555-0108', '258 Spruce Ct', 'San Diego', 'CA', '92101', 'USA', 'business', 'active', 86.00, 1, NULL, '2025-06-29 05:51:58', '2025-06-29 05:51:58'),
(9, 'James', 'Taylor', 'james.taylor@email.com', '555-0109', '369 Willow Pl', 'Dallas', 'TX', '75201', 'USA', 'vip', 'active', 127.00, 1, NULL, '2025-06-29 05:51:58', '2025-06-29 05:51:58'),
(10, 'Amanda', 'Anderson', 'amanda.anderson@email.com', '555-0110', '741 Poplar St', 'San Jose', 'CA', '95101', 'USA', 'individual', 'active', 91.00, 1, NULL, '2025-06-29 05:51:58', '2025-06-29 05:51:58');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'site_name', 'Boomerang Project', '2025-06-29 13:05:15'),
(2, 'admin_email', 'admin@boomerang.com', '2025-06-29 13:05:15'),
(3, 'session_timeout', '30', '2025-06-29 13:05:15'),
(4, 'max_login_attempts', '5', '2025-06-29 13:05:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_number` (`booking_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_check_in_date` (`check_in_date`),
  ADD KEY `idx_check_out_date` (`check_out_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payment_status` (`payment_status`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_customer_type` (`customer_type`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
