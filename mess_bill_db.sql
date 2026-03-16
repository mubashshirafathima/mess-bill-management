-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 14, 2026 at 04:41 PM
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
-- Database: `mess_bill_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `billing_settings`
--

CREATE TABLE `billing_settings` (
  `id` tinyint(4) NOT NULL,
  `rate_per_day` decimal(10,2) NOT NULL DEFAULT 100.00,
  `gst_percent` decimal(5,2) NOT NULL DEFAULT 5.00,
  `maintenance_fee` decimal(10,2) NOT NULL DEFAULT 1000.00,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billing_settings`
--

INSERT INTO `billing_settings` (`id`, `rate_per_day`, `gst_percent`, `maintenance_fee`, `updated_at`) VALUES
(1, 86.50, 5.00, 1000.00, '2026-03-09 10:38:47');

-- --------------------------------------------------------

--
-- Table structure for table `registered_students`
--

CREATE TABLE `registered_students` (
  `id` int(11) NOT NULL,
  `hall_ticket` varchar(50) NOT NULL,
  `student_name` varchar(150) NOT NULL,
  `joining_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `branch` varchar(100) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `join_academic_year` int(11) DEFAULT NULL,
  `current_year_at_join` tinyint(4) DEFAULT NULL,
  `current_academic_year` int(11) NOT NULL DEFAULT 1,
  `hostel_category` enum('boys','girls') NOT NULL DEFAULT 'boys',
  `expected_end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registered_students`
--

INSERT INTO `registered_students` (`id`, `hall_ticket`, `student_name`, `joining_date`, `end_date`, `branch`, `phone_number`, `created_at`, `join_academic_year`, `current_year_at_join`, `current_academic_year`, `hostel_category`, `expected_end_date`) VALUES
(12, '23XW5A0510', 'fathima', '2024-06-15', NULL, 'CSE', '6305068127', '2026-03-13 11:32:56', NULL, NULL, 4, 'girls', '2026-05-01'),
(13, '25XW5A0129', 'mani', '2025-07-09', NULL, 'CSE', '7386730817', '2026-03-13 11:33:37', NULL, NULL, 3, 'boys', '2027-06-08');

-- --------------------------------------------------------

--
-- Table structure for table `student_bills`
--

CREATE TABLE `student_bills` (
  `hall_ticket` varchar(10) NOT NULL,
  `student_name` text NOT NULL,
  `days_attended` int(11) NOT NULL,
  `rate_per_day` int(11) NOT NULL,
  `gst_percent` int(11) NOT NULL,
  `total_amount` int(11) NOT NULL,
  `maintenance_fee` decimal(10,2) NOT NULL DEFAULT 1000.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `billing_month` varchar(7) NOT NULL DEFAULT '2026-03',
  `payment_status` enum('paid','unpaid') NOT NULL DEFAULT 'unpaid',
  `student_id` int(11) DEFAULT NULL,
  `hostel_category` enum('boys','girls') NOT NULL DEFAULT 'boys'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_bills`
--

INSERT INTO `student_bills` (`hall_ticket`, `student_name`, `days_attended`, `rate_per_day`, `gst_percent`, `total_amount`, `maintenance_fee`, `created_at`, `billing_month`, `payment_status`, `student_id`, `hostel_category`) VALUES
('23XW5A0510', 'fathima', 10, 86, 5, 1908, 1000.00, '2026-03-13 11:51:31', '2026-02', 'unpaid', 12, 'girls'),
('23XW5A0510', 'fathima', 25, 86, 5, 3271, 1000.00, '2026-03-13 11:47:28', '2026-03', 'unpaid', 12, 'girls'),
('25XW5A0129', 'mani', 26, 86, 5, 3361, 1000.00, '2026-03-13 11:51:31', '2026-02', 'unpaid', 13, 'boys'),
('25XW5A0129', 'mani', 20, 86, 5, 2816, 1000.00, '2026-03-13 11:47:28', '2026-03', 'unpaid', 13, 'boys');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `billing_settings`
--
ALTER TABLE `billing_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `registered_students`
--
ALTER TABLE `registered_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hall_ticket` (`hall_ticket`);

--
-- Indexes for table `student_bills`
--
ALTER TABLE `student_bills`
  ADD UNIQUE KEY `uniq_hall_month` (`hall_ticket`,`billing_month`),
  ADD UNIQUE KEY `uniq_student_month` (`student_id`,`billing_month`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `registered_students`
--
ALTER TABLE `registered_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `student_bills`
--
ALTER TABLE `student_bills`
  ADD CONSTRAINT `fk_student_bills_student` FOREIGN KEY (`student_id`) REFERENCES `registered_students` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
