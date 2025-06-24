-- phpMyAdmin SQL Dump
-- version 4.8.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 24, 2025 at 02:38 PM
-- Server version: 10.1.34-MariaDB
-- PHP Version: 7.2.7

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wastepay_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `debts`
--

CREATE TABLE `debts` (
  `id` int(6) UNSIGNED NOT NULL,
  `user_id` int(6) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('pending','paid','overdue') DEFAULT 'pending',
  `paid_date` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `debts`
--

INSERT INTO `debts` (`id`, `user_id`, `amount`, `due_date`, `status`, `paid_date`, `created_at`) VALUES
(2, 1, '3000.00', '2025-07-10', 'pending', '', '2025-06-16 11:28:51'),
(3, 3, '3000.00', '2025-06-18', 'pending', '', '2025-06-18 13:45:29'),
(4, 4, '3000.00', '2025-06-21', 'pending', '', '2025-06-18 14:01:54');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `installment_requests`
--

CREATE TABLE `installment_requests` (
  `id` int(6) UNSIGNED NOT NULL,
  `user_id` int(6) UNSIGNED NOT NULL,
  `debt_id` int(6) UNSIGNED NOT NULL,
  `plan` varchar(50) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `installment_requests`
--

INSERT INTO `installment_requests` (`id`, `user_id`, `debt_id`, `plan`, `reason`, `status`, `created_at`) VALUES
(6, 1, 2, '3 Months Plan', 'lating the collectin of waste', 'pending', '2025-06-16 12:17:21'),
(7, 1, 2, '2 Months Plan', 'sshxhjkm', 'pending', '2025-06-16 12:19:13'),
(8, 1, 2, '2 Months Plan', 'sshxhjkm', 'pending', '2025-06-16 12:41:18');

-- --------------------------------------------------------

--
-- Table structure for table `mpesa_transactions`
--

CREATE TABLE `mpesa_transactions` (
  `id` int(11) NOT NULL,
  `payment_id` int(10) UNSIGNED NOT NULL,
  `merchant_request_id` varchar(100) NOT NULL,
  `checkout_request_id` varchar(100) NOT NULL,
  `result_code` int(11) NOT NULL,
  `result_desc` text NOT NULL,
  `amount` decimal(10,2) DEFAULT '0.00',
  `mpesa_receipt_number` varchar(50) DEFAULT NULL,
  `transaction_date` datetime DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(6) UNSIGNED NOT NULL,
  `user_id` int(6) UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `type` enum('debt','payment','info') NOT NULL,
  `status` enum('unread','read') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `type`, `status`, `created_at`) VALUES
(36, 1, 'Payment of Tsh 3,000 received via Credit Card.', 'payment', 'unread', '2025-06-16 10:20:22'),
(37, 1, 'Payment of Tsh 3,000 received via Credit Card.', 'payment', 'unread', '2025-06-16 10:26:39'),
(38, 1, 'Payment of Tsh 3,000 received via M-Pesa.', 'payment', 'unread', '2025-06-16 12:47:30'),
(39, 1, 'Payment of Tsh 3,000 received via M-Pesa.', 'payment', 'unread', '2025-06-16 12:47:39'),
(40, 1, 'Payment of Tsh 3,000 received via M-Pesa.', 'payment', 'unread', '2025-06-16 12:50:32'),
(41, 1, 'Payment of Tsh 3,000 received via M-Pesa.', 'payment', 'unread', '2025-06-16 12:51:11'),
(42, 1, 'Payment of Tsh 3,000 received via M-Pesa.', 'payment', 'unread', '2025-06-16 21:14:19'),
(43, 1, 'Payment of Tsh 3,000 received via M-Pesa.', 'payment', 'unread', '2025-06-16 21:39:45'),
(44, 1, 'Payment of Tsh 3,000 received via M-Pesa.', 'payment', 'unread', '2025-06-17 08:31:18'),
(45, 1, 'Payment of Tsh 3,000 received via M-Pesa.', 'payment', 'unread', '2025-06-17 08:31:26'),
(46, 1, 'Payment of Tsh 3,000 received via M-Pesa.', 'payment', 'unread', '2025-06-17 09:57:16'),
(47, 3, 'pay', 'debt', 'unread', '2025-06-17 14:44:01'),
(48, 1, 'Payment of Tsh 3,000 received via M-Pesa.', 'payment', 'unread', '2025-06-17 15:30:19'),
(49, 1, 'Payment of Tsh 3,000 received via M-Pesa.', 'payment', 'unread', '2025-06-17 18:05:51'),
(50, 1, 'Payment of Tsh 3,000 received via M-Pesa.', 'payment', 'unread', '2025-06-17 18:34:36'),
(51, 3, 'Payment of Tsh 3,000 received via M-Pesa.', 'payment', 'unread', '2025-06-20 08:13:28'),
(52, 3, 'Payment of Tsh 3,000 received via M-Pesa.', 'payment', 'unread', '2025-06-20 08:13:33');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(6) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` varchar(50) NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','completed','failed') NOT NULL DEFAULT 'completed',
  `merchant_request_id` varchar(255) DEFAULT NULL,
  `checkout_request_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `amount`, `method`, `reference`, `payment_date`, `status`, `merchant_request_id`, `checkout_request_id`) VALUES
(42, 1, '3000.00', 'Credit Card', 'WP-1750069095', '2025-06-16 10:20:22', 'completed', NULL, NULL),
(53, 1, '3000.00', 'M-Pesa', 'WP-1750174198', '2025-06-17 15:30:19', 'completed', NULL, NULL),
(54, 1, '3000.00', 'M-Pesa', 'WP-1750183533', '2025-06-17 18:05:51', 'completed', NULL, NULL),
(55, 1, '3000.00', 'M-Pesa', 'WP-1750183533', '2025-06-17 18:34:36', 'completed', NULL, NULL),
(56, 3, '3000.00', 'M-Pesa', 'WP-1750407154', '2025-06-20 08:13:28', 'completed', NULL, NULL),
(57, 3, '3000.00', 'M-Pesa', 'WP-1750407154', '2025-06-20 08:13:33', 'completed', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `suggestions`
--

CREATE TABLE `suggestions` (
  `id` int(6) UNSIGNED NOT NULL,
  `user_id` int(6) UNSIGNED NOT NULL,
  `category` varchar(50) NOT NULL,
  `suggestion` text NOT NULL,
  `status` enum('pending','reviewed','implemented') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `suggestions`
--

INSERT INTO `suggestions` (`id`, `user_id`, `category`, `suggestion`, `status`, `created_at`) VALUES
(7, 1, 'User Interface', 'it long', 'pending', '2025-06-05 10:57:59'),
(8, 1, 'User Interface', 'it long', 'pending', '2025-06-05 10:58:45'),
(9, 1, 'User Interface', 'it long', 'pending', '2025-06-05 11:49:43'),
(10, 1, 'Waste Collection', 'waste must be collected quik', 'pending', '2025-06-16 12:18:19');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(6) UNSIGNED NOT NULL,
  `username` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(50) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `reg_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `fullname`, `email`, `phone`, `reg_date`) VALUES
(1, 'winifrida', 'wini12345@', 'winifrida kantalamba', 'win@example.com', '+255712345678', '2025-06-10 09:29:15'),
(3, 'h001', 'chris123@', 'christian mlay', 'chrisbetuelmlay@gmail.com', '0987654321', '2025-06-17 11:00:32'),
(4, 'h002', 'jane123@', NULL, 'janemtweve911@gmail.com', '0714526782', '2025-06-18 13:25:02'),
(5, 'h003', 'jan1234@', NULL, 'janemtweveo911@gmail.com', '0714526702', '2025-06-18 13:36:04'),
(6, 'h006', 'boni123@', NULL, 'agwess@gmail.com', '0714522789', '2025-06-18 14:06:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `debts`
--
ALTER TABLE `debts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `installment_requests`
--
ALTER TABLE `installment_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `debt_id` (`debt_id`);

--
-- Indexes for table `mpesa_transactions`
--
ALTER TABLE `mpesa_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_payment` (`payment_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `suggestions`
--
ALTER TABLE `suggestions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `debts`
--
ALTER TABLE `debts`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `installment_requests`
--
ALTER TABLE `installment_requests`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `mpesa_transactions`
--
ALTER TABLE `mpesa_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `suggestions`
--
ALTER TABLE `suggestions`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `debts`
--
ALTER TABLE `debts`
  ADD CONSTRAINT `debts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `installment_requests`
--
ALTER TABLE `installment_requests`
  ADD CONSTRAINT `installment_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `installment_requests_ibfk_2` FOREIGN KEY (`debt_id`) REFERENCES `debts` (`id`);

--
-- Constraints for table `mpesa_transactions`
--
ALTER TABLE `mpesa_transactions`
  ADD CONSTRAINT `fk_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `suggestions`
--
ALTER TABLE `suggestions`
  ADD CONSTRAINT `suggestions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
