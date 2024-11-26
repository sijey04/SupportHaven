-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 23, 2024 at 07:17 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `haven`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `technician_id` int(11) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `location` varchar(255) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','accepted','completed','cancelled') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `service_id`, `technician_id`, `booking_date`, `booking_time`, `location`, `payment_method`, `total_cost`, `created_at`, `status`) VALUES
(1, 3, NULL, NULL, '0000-00-00', '00:00:00', '', '', 0.00, '2024-09-10 18:11:25', 'pending'),
(2, 3, NULL, NULL, '0000-00-00', '00:00:00', '', '', 0.00, '2024-09-10 18:12:53', 'pending'),
(3, 3, NULL, NULL, '0000-00-00', '00:00:00', '', '', 0.00, '2024-09-11 12:28:49', 'pending'),
(4, 3, NULL, NULL, '0000-00-00', '00:00:00', '', '', 0.00, '2024-09-12 00:54:13', 'pending'),
(5, 3, NULL, NULL, '0000-00-00', '00:00:00', '', '', 0.00, '2024-09-12 01:06:30', 'pending'),
(6, 3, NULL, NULL, '2024-09-18', '00:00:00', '2002', 'paypal', 0.00, '2024-09-14 16:38:05', 'pending'),
(8, 3, 2, 42, '2024-09-26', '00:00:11', 'Zamboanga City, 7000', 'credit_card', 100.00, '2024-09-21 15:05:20', 'accepted'),
(9, 3, 2, 42, '2024-09-30', '00:00:01', 'Zamboanga City, 7000', 'card', 0.00, '2024-09-27 16:17:57', 'accepted'),
(10, 3, 4, 42, '2024-09-30', '00:00:01', 'Zamboanga City, 7000', 'paypal', 250.00, '2024-09-27 16:26:02', 'pending'),
(11, 3, 1, 42, '2024-10-18', '00:00:11', '7000', 'cash', 250.00, '2024-10-11 18:09:34', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Computer Repair'),
(2, 'Network Setup'),
(3, 'Software Installation');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `category_id`, `name`, `price`, `description`) VALUES
(1, 1, 'Virus Removal', 250.00, NULL),
(2, 1, 'Data Recovery', 1000.00, NULL),
(3, 1, 'System Optimization', 500.00, NULL),
(4, 2, 'WiFi Setup', 250.00, NULL),
(5, 2, 'Network Troubleshooting', 250.00, NULL),
(6, 3, 'OS Installation', 150.00, NULL),
(7, 3, 'Software Update', 150.00, NULL),
(8, NULL, 'Computer Repair', 2500.00, NULL),
(9, NULL, 'Computer Repair', 3000.00, NULL),
(10, NULL, 'Computer Repair', 3500.00, NULL),
(11, NULL, 'Network Setup', 5000.00, NULL),
(12, NULL, 'Network Setup', 7500.00, NULL),
(13, NULL, 'Software Installation', 1500.00, NULL),
(14, NULL, 'Software Installation', 2000.00, NULL),
(15, 1, 'Basic Computer Repair', 2500.00, 'Basic computer repair service'),
(16, 1, 'Advanced Computer Repair', 3000.00, 'Advanced computer repair service'),
(17, 1, 'Premium Computer Repair', 3350.00, 'Premium computer repair service'),
(18, 2, 'Basic Network Setup', 5000.00, 'Basic network setup service'),
(19, 2, 'Advanced Network Setup', 7500.00, 'Advanced network setup service'),
(20, 3, 'Basic Software Installation', 1500.00, 'Basic software installation service'),
(21, 3, 'Advanced Software Installation', 2000.00, 'Advanced software installation service'),
(22, 2, 'WiFi Setup', 600.00, NULL),
(23, 2, 'Network Troubleshooting', 350.00, NULL),
(24, 3, 'OS Installation', 150.00, NULL),
(25, 2, 'Network Troubleshooting', 250.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `technicians`
--

CREATE TABLE `technicians` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `expertise` varchar(100) NOT NULL,
  `experience` int(11) NOT NULL,
  `photo` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','declined') NOT NULL DEFAULT 'pending',
  `phone` varchar(15) NOT NULL,
  `skill_rating` decimal(3,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `technicians`
--

INSERT INTO `technicians` (`id`, `user_id`, `expertise`, `experience`, `photo`, `created_at`, `status`, `phone`, `skill_rating`) VALUES
(1, 2, 'Virus Removal', 2, '', '2024-09-16 13:51:07', 'pending', '', NULL),
(3, 42, 'Computer Repair', 1, '434184476_1064497137977843_3663982541057038331_n.jpg', '2024-09-20 18:57:05', 'approved', '0986786778', NULL),
(4, 45, 'Computer Repair', 2, '01.jpg', '2024-10-12 16:23:20', 'pending', '098380203', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_role_id` int(11) NOT NULL,
  `auth_provider` enum('local','google','facebook') DEFAULT 'local',
  `oauth_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `created_at`, `user_role_id`, `auth_provider`, `oauth_id`) VALUES
(1, 'Rezier ', 'Magno', 'putli737@yahoo.com', '$2y$10$y4wE9BsXsmC58Kgx9I0RaOlwWaXs1191.bB90Bt29LCc82jwM8mFi', '2024-09-10 17:07:33', 2, 'local', NULL),
(2, 'sijey', 'Magno', 'sijeycutie12@gmail.com', '$2y$10$4HvN2NSehe5KiSrOm9M2wuTJ5rbhVczkrBGHkqC9MsahGvUWQSsx2', '2024-09-10 17:17:17', 1, 'local', NULL),
(3, 'sijey', 'sss', 'meow@gmail.com', '$2y$10$9MwWdRz2iJ4Nw33nZR9M.u4QJ75UqRoPqY0I1n0rowHFAkFkg514e', '2024-09-10 17:24:31', 1, 'local', NULL),
(4, 'Benoit', 'Montefalco', 'benoit@gmail.com', '$2y$10$u0WKzt8WBbs2Z83UhpdrM.rhBeDGK/kfk2oZjlnZ57wNzspEKsVRW', '2024-09-14 16:06:13', 2, 'local', NULL),
(5, 'Christian Jude', 'Faminiano', 'faminianochristianjude@gmail.com', '$2y$10$AHyU99P5IwB6RijFoPsyoe.GIw30masHGwAJ5hRMkJO6oDpeL26.m', '2024-09-15 14:27:06', 3, 'local', NULL),
(6, 'John', 'Camagaling', 'john@gmail.com', '$2y$10$ZnWvYZKZQ2WCLOErdB9QNOKoT9H3lePBgzQZ15VV6dlr5mUhbptcK', '2024-09-16 15:10:17', 1, 'local', NULL),
(42, 'Mike', 'Enriquez', 'mike@gmail.com', '$2y$10$FG73v1g4i54CzPk1472BNeB0m.D9u2RWcrhUWpPErK4vZ0rHsdCuK', '2024-09-20 18:57:05', 2, 'local', NULL),
(43, 'Condom', 'Faminianoo', 'Dragonss@gmail.com', '$2y$10$d.TDmbzx8ypVfeBofH/KEuvMjpXjWMRqJzm3/DEfn3b3g7oqSMQh2', '2024-10-11 18:21:28', 1, 'local', NULL),
(44, 'Ror', 'momo', 'meow1@gmail.com', '$2y$10$7lDPmgP.5tARQxNgjswaU.FZridBYCyUNIqNR0B24W5tFfcMAQ7D.', '2024-10-12 16:21:09', 1, 'local', NULL),
(45, 'low', 'mal', 'mal@gmail.com', '$2y$10$6C88w9fjjGLXfcXZdkZo9Ow.fG7yWi3LFGulF.nI4ArjnBkpP1fDm', '2024-10-12 16:23:20', 2, 'local', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`id`, `role_name`) VALUES
(3, 'admin'),
(1, 'customer'),
(2, 'technician');

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `service_id` (`service_id`);

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`);

-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_technician_id` (`technician_id`),
  ADD KEY `fk_service_id` (`service_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_service_category` (`category_id`);

--
-- Indexes for table `technicians`
--
ALTER TABLE `technicians`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `unique_oauth` (`auth_provider`,`oauth_id`),
  ADD KEY `user_role_id` (`user_role_id`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `technicians`
--
ALTER TABLE `technicians`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_service_id` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  ADD CONSTRAINT `fk_technician_id` FOREIGN KEY (`technician_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `fk_service_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `technicians`
--
ALTER TABLE `technicians`
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`user_role_id`) REFERENCES `user_roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
