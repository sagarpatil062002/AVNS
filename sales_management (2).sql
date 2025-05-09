-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 29, 2025 at 07:02 AM
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
-- Database: `sales_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_data`
--

CREATE TABLE `admin_data` (
  `id` int(11) NOT NULL,
  `subscription_id` int(11) NOT NULL,
  `qr_code_path` varchar(255) NOT NULL,
  `bank_details` text DEFAULT NULL,
  `approval_status` enum('approved','pending','rejected') DEFAULT 'pending',
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `plan_name` varchar(255) DEFAULT NULL,
  `tenure` varchar(255) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `isexpired` tinyint(1) NOT NULL DEFAULT 0,
  `remaining_calls` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `productId` int(11) NOT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`id`, `name`) VALUES
(1, 'Desktops'),
(2, 'Peripherals'),
(3, 'Networking'),
(4, 'Laptops');

-- --------------------------------------------------------

--
-- Table structure for table `customerdistributor`
--

CREATE TABLE `customerdistributor` (
  `id` int(11) NOT NULL,
  `companyName` varchar(255) NOT NULL,
  `mailId` varchar(255) NOT NULL,
  `gstType` enum('REGISTERED','UNREGISTERED') NOT NULL,
  `gstNo` varchar(50) DEFAULT NULL,
  `size` enum('SMALL','MEDIUM','LARGE') NOT NULL,
  `address` varchar(255) NOT NULL,
  `itAdmin` varchar(255) NOT NULL,
  `purchase` varchar(255) NOT NULL,
  `ownerName` varchar(255) NOT NULL,
  `mobileNo` varchar(20) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `createdAt` datetime DEFAULT current_timestamp(),
  `updatedAt` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `password` varchar(255) NOT NULL,
  `sectorId` int(11) NOT NULL,
  `otp` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customerdistributor`
--

INSERT INTO `customerdistributor` (`id`, `companyName`, `mailId`, `gstType`, `gstNo`, `size`, `address`, `itAdmin`, `purchase`, `ownerName`, `mobileNo`, `image`, `createdAt`, `updatedAt`, `password`, `sectorId`, `otp`, `otp_expiry`, `reset_token`, `reset_token_expiry`) VALUES
(1, 'Tech Innovations Pvt. Ltd.', 'contact@techinnovations.com', 'REGISTERED', '27AAAPA1234A1Z5', 'SMALL', '123 Tech Park, Silicon Valley, Bangalore, Karnataka, 560001', 'Priya Patel', '', 'Rahul Sharma', '9876543210', '', '2025-04-29 08:17:53', NULL, '$2y$10$FWTpC773DPvXnPamp6743ONcMSaW4ebaBqhdVPSYEd5dStLOFWof6', 1, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customer_plan`
--

CREATE TABLE `customer_plan` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `max_support_calls` int(11) NOT NULL,
  `rewards_points` int(11) NOT NULL,
  `most_popular` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `sectorName` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_subscription`
--

CREATE TABLE `customer_subscription` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `tenure` int(11) NOT NULL,
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `start_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `end_date` timestamp NULL DEFAULT NULL,
  `isexpired` tinyint(1) DEFAULT 0,
  `remaining_calls` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `distributor`
--

CREATE TABLE `distributor` (
  `id` int(11) NOT NULL,
  `companyName` varchar(255) NOT NULL,
  `mailId` varchar(255) NOT NULL,
  `gstType` enum('REGISTERED','UNREGISTERED') NOT NULL,
  `gstNo` varchar(50) DEFAULT NULL,
  `size` enum('SMALL','MEDIUM','LARGE') NOT NULL,
  `address` varchar(255) NOT NULL,
  `ownerName` varchar(255) NOT NULL,
  `mobileNo` varchar(20) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `createdAt` datetime DEFAULT current_timestamp(),
  `updatedAt` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `password` varchar(255) NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `mailId` varchar(255) NOT NULL,
  `mobileNo` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `experience` int(11) NOT NULL,
  `documents` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `designation` varchar(255) NOT NULL,
  `createdAt` datetime DEFAULT current_timestamp(),
  `updatedAt` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `freelancer`
--

CREATE TABLE `freelancer` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `experience` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `freelancer_skills`
--

CREATE TABLE `freelancer_skills` (
  `id` int(11) NOT NULL,
  `freelancer_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `certificate_path` varchar(255) DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `total_tax` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `tax` decimal(10,2) DEFAULT NULL,
  `tax_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `oem`
--

CREATE TABLE `oem` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `oem`
--

INSERT INTO `oem` (`id`, `name`) VALUES
(1, 'Dell Technologies'),
(2, 'TP-Link');

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `id` int(11) NOT NULL,
  `customerId` int(11) NOT NULL,
  `status` enum('PENDING','IN_PROCESS','SHIPPED','DELIVERED') NOT NULL,
  `createdAt` datetime DEFAULT current_timestamp(),
  `updatedAt` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `productId` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `custom_product_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `oemId` int(11) NOT NULL,
  `categoryId` int(11) NOT NULL,
  `subcategories` text NOT NULL,
  `partNo` varchar(50) NOT NULL,
  `model` varchar(50) NOT NULL,
  `hsnNo` varchar(50) NOT NULL,
  `images` text NOT NULL,
  `description` text NOT NULL,
  `datasheet` varchar(255) DEFAULT NULL,
  `createdAt` datetime DEFAULT current_timestamp(),
  `updatedAt` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`id`, `name`, `oemId`, `categoryId`, `subcategories`, `partNo`, `model`, `hsnNo`, `images`, `description`, `datasheet`, `createdAt`, `updatedAt`) VALUES
(1, 'Dell Alienware Aurora R13', 1, 1, '[\"Gaming\",\" Workstation\",\" High-Performance\"]', 'AW-AURORA-R13', 'Alienware Aurora R13', '8471', '[\"uploads\\/images\\/6810447ed7c6a_download__1_.jpeg\",\"uploads\\/images\\/6810447ed8bb7_download.jpeg\"]', 'A powerful gaming desktop featuring Intel\'s latest processors and NVIDIA GeForce RTX graphics for an immersive gaming experience.', 'uploads/images/qgxPbAXYmi.pdf', '2025-04-29 08:46:14', NULL),
(2, 'TP-Link Archer AX6000', 2, 3, '[\"Routers\",\" Wireless\",\" Networking Equipment\"]', 'AX6000', 'Archer AX6000', '8517', '[\"uploads\\/images\\/6810542b6850d_download__4_.jpeg\",\"uploads\\/images\\/6810542b68f38_download__3_.jpeg\",\"uploads\\/images\\/6810542b6a4e8_download__2_.jpeg\"]', 'A dual-band Wi-Fi 6 router that delivers ultra-fast speeds and extensive coverage for all your devices.', 'uploads/images/61VlUXHyDc.pdf', '2025-04-29 09:53:07', NULL),
(3, 'Dell XPS 13 (9310)', 1, 4, '[\"Ultrabooks\",\" Business\",\" Portable\"]', 'XPS13-9310', 'XPS 13 (9310)', '8471', '[\"uploads\\/images\\/6810559a71a4a_download__7_.jpeg\",\"uploads\\/images\\/6810559a723d3_download__6_.jpeg\",\"uploads\\/images\\/6810559a72c01_download__5_.jpeg\"]', 'A premium ultrabook featuring a 13.4-inch InfinityEdge display, Intel\'s 11th Gen processors, and a sleek, lightweight design, perfect for professionals and students on the go.', 'uploads/images/OokTKkfDrw.pdf', '2025-04-29 09:59:14', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_details`
--

CREATE TABLE `purchase_details` (
  `id` int(11) NOT NULL,
  `distributor_id` int(11) NOT NULL,
  `super_admin_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `total_tax` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_items`
--

CREATE TABLE `purchase_items` (
  `id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `tax` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `tax_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quotation`
--

CREATE TABLE `quotation` (
  `id` int(11) NOT NULL,
  `productId` int(11) NOT NULL,
  `customerId` int(11) NOT NULL,
  `priceOffered` float NOT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') NOT NULL,
  `createdAt` datetime DEFAULT current_timestamp(),
  `updatedAt` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `quantity` int(11) NOT NULL,
  `distributorId` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quotation_header`
--

CREATE TABLE `quotation_header` (
  `quotation_id` int(11) NOT NULL,
  `customerId` int(11) DEFAULT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') NOT NULL,
  `createdAt` datetime DEFAULT current_timestamp(),
  `updatedAt` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `superAdminId` int(11) NOT NULL,
  `distributorId` int(11) DEFAULT NULL,
  `subject` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quotation_header`
--

INSERT INTO `quotation_header` (`quotation_id`, `customerId`, `status`, `createdAt`, `updatedAt`, `superAdminId`, `distributorId`, `subject`) VALUES
(1, 1, 'PENDING', '2025-04-29 06:48:15', NULL, 1, NULL, 'Request for Quotation for Dell Products - Tech Innovations Pvt. Ltd.  ');

-- --------------------------------------------------------

--
-- Table structure for table `quotation_product`
--

CREATE TABLE `quotation_product` (
  `id` int(11) NOT NULL,
  `quotation_id` int(11) NOT NULL,
  `productId` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `priceOffered` float NOT NULL,
  `createdAt` datetime DEFAULT current_timestamp(),
  `tax_rate_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quotation_product`
--

INSERT INTO `quotation_product` (`id`, `quotation_id`, `productId`, `quantity`, `priceOffered`, `createdAt`, `tax_rate_id`) VALUES
(1, 1, 3, 15, 96000, '2025-04-29 06:48:15', NULL),
(2, 1, 1, 15, 120000, '2025-04-29 06:48:15', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sectors`
--

CREATE TABLE `sectors` (
  `id` int(11) NOT NULL,
  `sectorName` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sectors`
--

INSERT INTO `sectors` (`id`, `sectorName`) VALUES
(1, 'IT');

-- --------------------------------------------------------

--
-- Table structure for table `skills`
--

CREATE TABLE `skills` (
  `id` int(11) NOT NULL,
  `skill_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `skills_pending_approval`
--

CREATE TABLE `skills_pending_approval` (
  `id` int(11) NOT NULL,
  `freelancer_id` int(11) NOT NULL,
  `skill_name` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `most_popular` tinyint(1) NOT NULL DEFAULT 0,
  `max_support_calls` int(11) NOT NULL DEFAULT 0,
  `rewards_points` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tax_rates`
--

CREATE TABLE `tax_rates` (
  `id` int(11) NOT NULL,
  `tax_name` varchar(50) NOT NULL,
  `tax_percentage` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket`
--

CREATE TABLE `ticket` (
  `id` int(11) NOT NULL,
  `customerId` int(11) NOT NULL,
  `freelancerId` int(11) DEFAULT NULL,
  `status` enum('PENDING','ASSIGNED','IN_PROGRESS','RESOLVED','REJECTED','CLOSED') NOT NULL,
  `description` text NOT NULL,
  `createdAt` datetime DEFAULT current_timestamp(),
  `updatedAt` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `skill_id` int(11) NOT NULL,
  `priority` varchar(20) NOT NULL,
  `remark` varchar(1000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `mobileNo` varchar(15) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `mobileNo`, `created_at`, `reset_token`, `reset_token_expiry`) VALUES
(1, 'Sagar Patil ', 'sagarpatil062002@gmail.com', '$2y$10$9OES0xc09vyXaSE987psJuCsIjH2RIC8oIaccVnjq6j.6aYppUMQ6', '9096471633', '2025-04-28 13:13:53', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_data`
--
ALTER TABLE `admin_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subscription_id` (`subscription_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_Cart_User` (`userId`),
  ADD KEY `FK_Cart_Product` (`productId`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customerdistributor`
--
ALTER TABLE `customerdistributor`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customerdistributor_ibfk_1` (`sectorId`);

--
-- Indexes for table `customer_plan`
--
ALTER TABLE `customer_plan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sector_name_idx` (`sectorName`);

--
-- Indexes for table `customer_subscription`
--
ALTER TABLE `customer_subscription`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id_idx` (`user_id`),
  ADD KEY `plan_id_idx` (`plan_id`);

--
-- Indexes for table `distributor`
--
ALTER TABLE `distributor`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `freelancer`
--
ALTER TABLE `freelancer`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `freelancer_skills`
--
ALTER TABLE `freelancer_skills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `freelancer_id` (`freelancer_id`),
  ADD KEY `skill_id` (`skill_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `oem`
--
ALTER TABLE `oem`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customerId` (`customerId`),
  ADD KEY `productId` (`productId`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oemId` (`oemId`),
  ADD KEY `categoryId` (`categoryId`);

--
-- Indexes for table `purchase_details`
--
ALTER TABLE `purchase_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `distributor_id` (`distributor_id`),
  ADD KEY `super_admin_id` (`super_admin_id`);

--
-- Indexes for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_id` (`purchase_id`);

--
-- Indexes for table `quotation`
--
ALTER TABLE `quotation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `productId` (`productId`),
  ADD KEY `fk_quotation_customer` (`customerId`);

--
-- Indexes for table `quotation_header`
--
ALTER TABLE `quotation_header`
  ADD PRIMARY KEY (`quotation_id`),
  ADD KEY `customerId` (`customerId`),
  ADD KEY `distributorId` (`distributorId`),
  ADD KEY `superAdminId` (`superAdminId`);

--
-- Indexes for table `quotation_product`
--
ALTER TABLE `quotation_product`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quotation_id` (`quotation_id`),
  ADD KEY `productId` (`productId`),
  ADD KEY `fk_tax_rate` (`tax_rate_id`);

--
-- Indexes for table `sectors`
--
ALTER TABLE `sectors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sectorName` (`sectorName`),
  ADD KEY `sector_name_idx` (`sectorName`);

--
-- Indexes for table `skills`
--
ALTER TABLE `skills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `skill_name` (`skill_name`);

--
-- Indexes for table `skills_pending_approval`
--
ALTER TABLE `skills_pending_approval`
  ADD PRIMARY KEY (`id`),
  ADD KEY `freelancer_id` (`freelancer_id`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tax_rates`
--
ALTER TABLE `tax_rates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ticket`
--
ALTER TABLE `ticket`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customerId` (`customerId`),
  ADD KEY `freelancerId` (`freelancerId`),
  ADD KEY `fk_skill` (`skill_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_data`
--
ALTER TABLE `admin_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `customerdistributor`
--
ALTER TABLE `customerdistributor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customer_plan`
--
ALTER TABLE `customer_plan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_subscription`
--
ALTER TABLE `customer_subscription`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `distributor`
--
ALTER TABLE `distributor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `freelancer`
--
ALTER TABLE `freelancer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `freelancer_skills`
--
ALTER TABLE `freelancer_skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `oem`
--
ALTER TABLE `oem`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `purchase_details`
--
ALTER TABLE `purchase_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quotation`
--
ALTER TABLE `quotation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quotation_header`
--
ALTER TABLE `quotation_header`
  MODIFY `quotation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `quotation_product`
--
ALTER TABLE `quotation_product`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sectors`
--
ALTER TABLE `sectors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `skills`
--
ALTER TABLE `skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skills_pending_approval`
--
ALTER TABLE `skills_pending_approval`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tax_rates`
--
ALTER TABLE `tax_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ticket`
--
ALTER TABLE `ticket`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_data`
--
ALTER TABLE `admin_data`
  ADD CONSTRAINT `admin_data_ibfk_1` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`);

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `FK_Cart_Product` FOREIGN KEY (`productId`) REFERENCES `product` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_Cart_User` FOREIGN KEY (`userId`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customerdistributor`
--
ALTER TABLE `customerdistributor`
  ADD CONSTRAINT `customerdistributor_ibfk_1` FOREIGN KEY (`sectorId`) REFERENCES `sectors` (`id`);

--
-- Constraints for table `customer_subscription`
--
ALTER TABLE `customer_subscription`
  ADD CONSTRAINT `customer_subscription_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `customerdistributor` (`id`),
  ADD CONSTRAINT `fk_plan_id` FOREIGN KEY (`plan_id`) REFERENCES `customer_plan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `freelancer_skills`
--
ALTER TABLE `freelancer_skills`
  ADD CONSTRAINT `freelancer_skills_ibfk_1` FOREIGN KEY (`freelancer_id`) REFERENCES `freelancer` (`id`),
  ADD CONSTRAINT `freelancer_skills_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`);

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customerdistributor` (`id`);

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`);

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `combined_order_details_ibfk_1` FOREIGN KEY (`productId`) REFERENCES `product` (`id`),
  ADD CONSTRAINT `combined_order_details_ibfk_2` FOREIGN KEY (`customerId`) REFERENCES `customerdistributor` (`id`);

--
-- Constraints for table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `product_ibfk_1` FOREIGN KEY (`oemId`) REFERENCES `oem` (`id`),
  ADD CONSTRAINT `product_ibfk_2` FOREIGN KEY (`categoryId`) REFERENCES `category` (`id`);

--
-- Constraints for table `purchase_details`
--
ALTER TABLE `purchase_details`
  ADD CONSTRAINT `purchase_details_ibfk_1` FOREIGN KEY (`distributor_id`) REFERENCES `distributor` (`id`),
  ADD CONSTRAINT `purchase_details_ibfk_2` FOREIGN KEY (`super_admin_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD CONSTRAINT `purchase_items_ibfk_1` FOREIGN KEY (`purchase_id`) REFERENCES `purchase_details` (`id`);

--
-- Constraints for table `quotation`
--
ALTER TABLE `quotation`
  ADD CONSTRAINT `fk_quotation_customer` FOREIGN KEY (`customerId`) REFERENCES `customerdistributor` (`id`),
  ADD CONSTRAINT `quotation_ibfk_1` FOREIGN KEY (`productId`) REFERENCES `product` (`id`);

--
-- Constraints for table `quotation_header`
--
ALTER TABLE `quotation_header`
  ADD CONSTRAINT `quotation_header_ibfk_1` FOREIGN KEY (`customerId`) REFERENCES `customerdistributor` (`id`),
  ADD CONSTRAINT `quotation_header_ibfk_2` FOREIGN KEY (`distributorId`) REFERENCES `distributor` (`id`),
  ADD CONSTRAINT `quotation_header_ibfk_3` FOREIGN KEY (`superAdminId`) REFERENCES `users` (`id`);

--
-- Constraints for table `quotation_product`
--
ALTER TABLE `quotation_product`
  ADD CONSTRAINT `fk_tax_rate` FOREIGN KEY (`tax_rate_id`) REFERENCES `tax_rates` (`id`),
  ADD CONSTRAINT `quotation_product_ibfk_1` FOREIGN KEY (`quotation_id`) REFERENCES `quotation_header` (`quotation_id`),
  ADD CONSTRAINT `quotation_product_ibfk_2` FOREIGN KEY (`productId`) REFERENCES `product` (`id`);

--
-- Constraints for table `skills_pending_approval`
--
ALTER TABLE `skills_pending_approval`
  ADD CONSTRAINT `skills_pending_approval_ibfk_1` FOREIGN KEY (`freelancer_id`) REFERENCES `freelancer` (`id`);

--
-- Constraints for table `ticket`
--
ALTER TABLE `ticket`
  ADD CONSTRAINT `fk_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`),
  ADD CONSTRAINT `ticket_ibfk_1` FOREIGN KEY (`customerId`) REFERENCES `customerdistributor` (`id`),
  ADD CONSTRAINT `ticket_ibfk_2` FOREIGN KEY (`freelancerId`) REFERENCES `freelancer` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
