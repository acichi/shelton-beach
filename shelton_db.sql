-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 15, 2025 at 03:16 PM
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
-- Database: `shelton_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_tbl`
--

CREATE TABLE `admin_tbl` (
  `admin_id` int(11) NOT NULL,
  `admin_fname` varchar(100) NOT NULL,
  `admin_lname` varchar(100) NOT NULL,
  `admin_gender` enum('Male','Female','Other') NOT NULL,
  `admin_email` varchar(100) NOT NULL,
  `admin_number` varchar(20) NOT NULL,
  `admin_user` varchar(100) NOT NULL,
  `admin_pass` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_tbl`
--

CREATE TABLE `customer_tbl` (
  `customer_id` int(11) NOT NULL,
  `customer_fname` varchar(100) DEFAULT NULL,
  `customer_lname` varchar(100) DEFAULT NULL,
  `customer_gender` enum('Male','Female','Other') DEFAULT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `customer_number` varchar(20) DEFAULT NULL,
  `customer_user` varchar(100) DEFAULT NULL,
  `customer_pass` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `facility_tbl`
--

CREATE TABLE `facility_tbl` (
  `facility_id` int(11) NOT NULL,
  `facility_name` varchar(100) DEFAULT NULL,
  `facility_pin_x` float DEFAULT NULL,
  `facility_pin_y` float DEFAULT NULL,
  `facility_details` text DEFAULT NULL,
  `facility_status` enum('Available','Unavailable') DEFAULT 'Available',
  `facility_price` float DEFAULT NULL,
  `facility_image` text DEFAULT NULL,
  `facility_added` date DEFAULT NULL,
  `facility_updated` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback_tbl`
--

CREATE TABLE `feedback_tbl` (
  `feedback_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `facility_id` int(11) DEFAULT NULL,
  `feedback_name` int(100) DEFAULT NULL,
  `feedback_facility` int(100) DEFAULT NULL,
  `feedback_message` text DEFAULT NULL,
  `feedback_rate` int(11) DEFAULT NULL,
  `feedback_timestamp` timestamp NULL DEFAULT NULL,
  `feedback_status` enum('hide','show') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gallery_tbl`
--

CREATE TABLE `gallery_tbl` (
  `gallery_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `gallery_description` text DEFAULT NULL,
  `gallery_image` text DEFAULT NULL,
  `gallery_date_added` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_tbl`
--

CREATE TABLE `message_tbl` (
  `message_id` int(11) NOT NULL,
  `message_email` varchar(100) NOT NULL,
  `message_number` varchar(100) NOT NULL,
  `message_text` text NOT NULL,
  `message_status` enum('read','not read') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `receipt_tbl`
--

CREATE TABLE `receipt_tbl` (
  `receipt_id` int(11) NOT NULL,
  `facility_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `receipt_trans_code` text DEFAULT NULL,
  `receipt_reservee` varchar(100) DEFAULT NULL,
  `receipt_facility` varchar(100) DEFAULT NULL,
  `receipt_amount_paid` float DEFAULT NULL,
  `receipt_balance` float DEFAULT NULL,
  `receipt_date_checkin` date DEFAULT NULL,
  `receipt_date_checkout` date DEFAULT NULL,
  `receipt_timestamp` timestamp NULL DEFAULT NULL,
  `receipt_date_booked` date DEFAULT NULL,
  `receipt_payment_type` enum('Gcash','Maya','GrabPay','ShopeePay','Lazada Wallet','Coins.ph','Credit Card','Debit Card','Cash') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservation_tbl`
--

CREATE TABLE `reservation_tbl` (
  `reservation_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `facility_id` int(11) DEFAULT NULL,
  `reservation_reservee` varchar(100) DEFAULT NULL,
  `reservation_facility` varchar(100) DEFAULT NULL,
  `reservation_status` enum('Pending','Confirmed','Cancelled') DEFAULT NULL,
  `reservation_date_booked` date DEFAULT NULL,
  `reservation_date_start` datetime DEFAULT NULL,
  `reservation_date_end` datetime DEFAULT NULL,
  `reservation_payment_type` enum('Gcash','Maya','GrabPay','ShopeePay','Lazada Wallet','Coins.ph','Credit Card','Debit Card','Cash') DEFAULT NULL,
  `reservation_amount` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_tbl`
--
ALTER TABLE `admin_tbl`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `customer_tbl`
--
ALTER TABLE `customer_tbl`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `facility_tbl`
--
ALTER TABLE `facility_tbl`
  ADD PRIMARY KEY (`facility_id`);

--
-- Indexes for table `feedback_tbl`
--
ALTER TABLE `feedback_tbl`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `fk_feedback_customer` (`customer_id`),
  ADD KEY `fk_feedback_facility` (`facility_id`);

--
-- Indexes for table `gallery_tbl`
--
ALTER TABLE `gallery_tbl`
  ADD PRIMARY KEY (`gallery_id`),
  ADD KEY `fk_gallery_admin` (`admin_id`);

--
-- Indexes for table `receipt_tbl`
--
ALTER TABLE `receipt_tbl`
  ADD PRIMARY KEY (`receipt_id`),
  ADD KEY `fk_receipt_customer` (`customer_id`),
  ADD KEY `fk_receipt_facility` (`facility_id`);

--
-- Indexes for table `reservation_tbl`
--
ALTER TABLE `reservation_tbl`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `fk_reservation_customer` (`customer_id`),
  ADD KEY `fk_reservation_facility` (`facility_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_tbl`
--
ALTER TABLE `admin_tbl`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_tbl`
--
ALTER TABLE `customer_tbl`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `facility_tbl`
--
ALTER TABLE `facility_tbl`
  MODIFY `facility_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback_tbl`
--
ALTER TABLE `feedback_tbl`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gallery_tbl`
--
ALTER TABLE `gallery_tbl`
  MODIFY `gallery_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `receipt_tbl`
--
ALTER TABLE `receipt_tbl`
  MODIFY `receipt_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservation_tbl`
--
ALTER TABLE `reservation_tbl`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `feedback_tbl`
--
ALTER TABLE `feedback_tbl`
  ADD CONSTRAINT `fk_feedback_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer_tbl` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_feedback_facility` FOREIGN KEY (`facility_id`) REFERENCES `facility_tbl` (`facility_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `gallery_tbl`
--
ALTER TABLE `gallery_tbl`
  ADD CONSTRAINT `fk_gallery_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin_tbl` (`admin_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `receipt_tbl`
--
ALTER TABLE `receipt_tbl`
  ADD CONSTRAINT `fk_receipt_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer_tbl` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_receipt_facility` FOREIGN KEY (`facility_id`) REFERENCES `facility_tbl` (`facility_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reservation_tbl`
--
ALTER TABLE `reservation_tbl`
  ADD CONSTRAINT `fk_reservation_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer_tbl` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reservation_facility` FOREIGN KEY (`facility_id`) REFERENCES `facility_tbl` (`facility_id`) ON DELETE CASCADE ON UPDATE CASCADE;
-- --------------------------------------------------------
--
-- Additional tables for remember tokens and OTP
-- --------------------------------------------------------

-- Ensure unique email for customers to support FK
ALTER TABLE `customer_tbl`
  ADD UNIQUE KEY `uniq_customer_email` (`customer_email`);

-- Ensure unique phone number for customers to prevent duplicate registrations
ALTER TABLE `customer_tbl`
  ADD UNIQUE KEY `uniq_customer_number` (`customer_number`);

-- Table structure for table `remember_tokens_tbl`
CREATE TABLE `remember_tokens_tbl` (
  `remember_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Indexes for table `remember_tokens_tbl`
ALTER TABLE `remember_tokens_tbl`
  ADD PRIMARY KEY (`remember_id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `expires_at` (`expires_at`);

-- AUTO_INCREMENT for table `remember_tokens_tbl`
ALTER TABLE `remember_tokens_tbl`
  MODIFY `remember_id` int(11) NOT NULL AUTO_INCREMENT;

-- Constraints for table `remember_tokens_tbl`
ALTER TABLE `remember_tokens_tbl`
  ADD CONSTRAINT `fk_remember_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer_tbl` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Table structure for table `otp_tbl`
CREATE TABLE `otp_tbl` (
  `otp_id` int(11) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `status` enum('pending','verified','expired') NOT NULL DEFAULT 'pending',
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Indexes for table `otp_tbl`
ALTER TABLE `otp_tbl`
  ADD PRIMARY KEY (`otp_id`),
  ADD KEY `idx_customer_email` (`customer_email`),
  ADD KEY `idx_status_email` (`status`,`customer_email`);

-- AUTO_INCREMENT for table `otp_tbl`
ALTER TABLE `otp_tbl`
  MODIFY `otp_id` int(11) NOT NULL AUTO_INCREMENT;

-- Foreign key: tie OTP email to a real customer record
-- Note: Do not create a foreign key from OTP to customers, because OTP is generated
-- before the customer exists during registration. Keep email indexed instead.

-- Optional: Cleanup event for expired remember tokens
DELIMITER $$
CREATE EVENT IF NOT EXISTS `cleanup_expired_tokens_tbl` ON SCHEDULE EVERY 1 DAY STARTS CURRENT_TIMESTAMP ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    DELETE FROM remember_tokens_tbl WHERE expires_at < NOW();
END$$
DELIMITER ;

-- Backward-compatible views to match existing application queries
CREATE OR REPLACE VIEW `remember_tokens` AS
  SELECT
    `remember_id` AS `id`,
    `customer_id` AS `user_id`,
    `token`,
    `expires_at`,
    `created_at`
  FROM `remember_tokens_tbl`;

CREATE OR REPLACE VIEW `otp` AS
  SELECT
    `otp_id` AS `id`,
    `otp_code` AS `otp`,
    `customer_email` AS `user_email`,
    `status`,
    `date_created`,
    `date_updated`
  FROM `otp_tbl`;

-- Backward-compatible view for `users` expected by the app
CREATE OR REPLACE VIEW `users` AS
  SELECT
    c.`customer_id` AS `id`,
    CONCAT(COALESCE(c.`customer_fname`, ''), ' ', COALESCE(c.`customer_lname`, '')) AS `fullname`,
    c.`customer_email` AS `email`,
    c.`customer_number` AS `number`,
    c.`customer_user` AS `username`,
    c.`customer_pass` AS `password`,
    c.`customer_gender` AS `gender`,
    'customer' AS `role`,
    NULL AS `address`,
    NULL AS `date_added`,
    NULL AS `date_updated`
  FROM `customer_tbl` c
  UNION ALL
  SELECT
    a.`admin_id` AS `id`,
    CONCAT(COALESCE(a.`admin_fname`, ''), ' ', COALESCE(a.`admin_lname`, '')) AS `fullname`,
    a.`admin_email` AS `email`,
    a.`admin_number` AS `number`,
    a.`admin_user` AS `username`,
    a.`admin_pass` AS `password`,
    a.`admin_gender` AS `gender`,
    'admin' AS `role`,
    NULL AS `address`,
    NULL AS `date_added`,
    NULL AS `date_updated`
  FROM `admin_tbl` a;

-- Normalize incorrect types in feedback_tbl to align with app usage
ALTER TABLE `feedback_tbl`
  MODIFY `feedback_name` varchar(100) DEFAULT NULL,
  MODIFY `feedback_facility` varchar(100) DEFAULT NULL,
  MODIFY `feedback_status` enum('hide','show') DEFAULT 'show';

-- Compatibility views for legacy table names used by the app
CREATE OR REPLACE VIEW `facility` AS
  SELECT
    facility_id AS id,
    facility_name AS name,
    facility_pin_x AS pin_x,
    facility_pin_y AS pin_y,
    facility_details AS details,
    facility_status AS status,
    facility_price AS price,
    facility_image AS image,
    facility_added AS date_added,
    facility_updated AS date_updated
  FROM facility_tbl;

CREATE OR REPLACE VIEW `reservations` AS
  SELECT
    reservation_id AS id,
    reservation_reservee AS reservee,
    reservation_facility AS facility_name,
    reservation_status AS status,
    reservation_date_booked AS date_booked,
    reservation_date_start AS date_start,
    reservation_date_end AS date_end,
    reservation_payment_type AS payment_type,
    reservation_amount AS amount
  FROM reservation_tbl;

CREATE OR REPLACE VIEW `receipt` AS
  SELECT
    receipt_id AS id,
    receipt_trans_code AS transaction_id,
    receipt_reservee AS reservee,
    receipt_facility AS facility_name,
    receipt_amount_paid AS amount_paid,
    receipt_balance AS balance,
    receipt_date_checkin AS date_checkin,
    receipt_date_checkout AS date_checkout,
    receipt_timestamp AS timestamp,
    receipt_date_booked AS date_booked,
    receipt_payment_type AS payment_type
  FROM receipt_tbl;

CREATE OR REPLACE VIEW `gallery` AS
  SELECT
    gallery_id AS id,
    gallery_description AS description,
    gallery_image AS location,
    gallery_date_added AS date_added
  FROM gallery_tbl;

CREATE OR REPLACE VIEW `feedback` AS
  SELECT
    feedback_id AS id,
    feedback_name AS fullname,
    feedback_facility AS facility_name,
    feedback_message AS feedback,
    feedback_rate AS rate,
    feedback_timestamp AS timestamp,
    CASE WHEN feedback_status = 'hide' THEN 1 ELSE 0 END AS is_hidden
  FROM feedback_tbl;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
 