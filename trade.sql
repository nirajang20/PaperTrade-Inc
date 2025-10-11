-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 11, 2025 at 05:03 AM
-- Server version: 10.11.13-MariaDB-0ubuntu0.24.04.1
-- PHP Version: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `trade`
--

-- --------------------------------------------------------

--
-- Table structure for table `available_trade`
--

CREATE TABLE `available_trade` (
  `id` int(11) NOT NULL,
  `symbol` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `available_trade`
--

INSERT INTO `available_trade` (`id`, `symbol`, `created_at`) VALUES
(1, 'NVDA', '2025-10-08 19:40:51'),
(2, 'MSFT', '2025-10-08 19:40:51'),
(3, 'AAPL', '2025-10-08 19:40:51'),
(4, 'GOOGL', '2025-10-08 19:40:51'),
(5, 'AMZN', '2025-10-08 19:40:51'),
(6, 'META', '2025-10-08 19:40:51'),
(7, 'TSLA', '2025-10-08 19:40:51'),
(8, 'JPM', '2025-10-08 19:40:51'),
(9, '^GSPC', '2025-10-08 19:40:51'),
(10, 'BTC-USD', '2025-10-08 19:40:51'),
(11, 'ETH-USD', '2025-10-08 19:40:51');

-- --------------------------------------------------------

--
-- Table structure for table `holding`
--

CREATE TABLE `holding` (
  `holding_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `symbol_id` int(11) NOT NULL,
  `quantity` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `avg_cost` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `note` text DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pending_users`
--

CREATE TABLE `pending_users` (
  `id` int(11) NOT NULL,
  `user_name` text NOT NULL,
  `user_email` text NOT NULL,
  `avatar` varchar(100) NOT NULL,
  `dob` date NOT NULL,
  `address` text NOT NULL,
  `password` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transaction`
--

CREATE TABLE `transaction` (
  `transaction_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `symbol_id` int(11) NOT NULL,
  `side` enum('BUY','SELL') NOT NULL,
  `quantity` decimal(12,4) NOT NULL,
  `price` decimal(12,4) NOT NULL,
  `fees` decimal(10,2) DEFAULT 0.00,
  `executed_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `dob` date NOT NULL,
  `user_phone` bigint(250) NOT NULL,
  `address` text NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `user_password` varchar(255) NOT NULL,
  `avatar` varchar(100) DEFAULT NULL,
  `user_balance` int(11) NOT NULL,
  `role` text NOT NULL,
  `last_login` date NOT NULL,
  `last_updated` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` date NOT NULL DEFAULT current_timestamp(),
  `status` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `user_email`, `dob`, `user_phone`, `address`, `user_name`, `user_password`, `avatar`, `user_balance`, `role`, `last_login`, `last_updated`, `created_at`, `status`) VALUES
(1, 'john@doe.com', '1995-10-01', 6051111111, '82364, Innovation Drive, Brookings, SD, 57006', 'John Doe', '$2y$10$.YSFEjrdGtIEYAK1kRa4lOsz6tz7g22K5qF29Ol9mXP676S1ANQAS', '5001026263.jpg', 78500, 'Trader', '0000-00-00', '2025-10-10 23:34:42', '2021-08-08', 1),
(2, 'rikeshhsekir048@gmail.com', '2000-10-02', 123456789, '123, Innovation Drive, Brookings, SD, 57006', 'Rikesh B.', '$2y$10$AilCX.6PJX.knjeWeXqV/ukf2UZURgtqeAafaDKDkcEuV4KF.Q.ry', NULL, 100000, 'Trader', '0000-00-00', '2024-10-08 00:00:00', '2019-10-16', 1),
(3, 'prabinluitel12@gmail.com', '2000-10-23', 987654321, '123, Innovation Drive, Brookings, SD, 57006', 'Prabin L.', '$2y$10$vZJy7y4uqQQTRN3zdi2RE.5ZJJzGEEPnzEjFXm4nEOx023XQ2Qe..', NULL, 100000, 'Trader', '0000-00-00', '2025-08-01 00:00:00', '2021-05-04', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `available_trade`
--
ALTER TABLE `available_trade`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `holding`
--
ALTER TABLE `holding`
  ADD PRIMARY KEY (`holding_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `symbol_id` (`symbol_id`);

--
-- Indexes for table `pending_users`
--
ALTER TABLE `pending_users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transaction`
--
ALTER TABLE `transaction`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `symbol_id` (`symbol_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `user_email` (`user_email`),
  ADD KEY `user_name` (`user_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `available_trade`
--
ALTER TABLE `available_trade`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `holding`
--
ALTER TABLE `holding`
  MODIFY `holding_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pending_users`
--
ALTER TABLE `pending_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `transaction`
--
ALTER TABLE `transaction`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `holding`
--
ALTER TABLE `holding`
  ADD CONSTRAINT `holding_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `holding_ibfk_2` FOREIGN KEY (`symbol_id`) REFERENCES `available_trade` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transaction`
--
ALTER TABLE `transaction`
  ADD CONSTRAINT `transaction_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaction_ibfk_2` FOREIGN KEY (`symbol_id`) REFERENCES `available_trade` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
