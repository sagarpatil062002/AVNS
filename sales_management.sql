-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 13, 2025 at 10:07 AM
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
(1, 'Computer Accessories'),
(2, 'Audio Accessories'),
(3, 'Storage Devices'),
(4, 'Networking');

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
(1, 'Tech Innovations Pvt. Ltd.', 'contact@techinnovations.com', 'REGISTERED', '27AAAPA1234A1Z5', 'SMALL', '123 Tech Park, Silicon Valley, Bangalore, Karnataka, 560001', 'Priya Patel', '', 'Rahul Sharma', '9876543210', '', '2025-05-01 13:57:02', NULL, '$2y$10$O84tDRVJbhLlY3lIHFx.Qumjz0BPMUqpxSEUGezbhY4w7lS3scrpa', 1, NULL, NULL, NULL, NULL);

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

--
-- Dumping data for table `customer_plan`
--

INSERT INTO `customer_plan` (`id`, `name`, `base_price`, `max_support_calls`, `rewards_points`, `most_popular`, `created_at`, `updated_at`, `sectorName`) VALUES
(1, 'Diamond', 999.00, 40, 10, 1, '2025-05-01 13:21:27', '2025-05-01 13:21:27', '1'),
(2, 'Gold', 699.00, 30, 10, 0, '2025-05-01 13:23:10', '2025-05-01 13:23:10', '1'),
(3, 'Silver', 499.00, 10, 10, 0, '2025-05-01 14:00:11', '2025-05-01 14:00:11', '1');

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
  `amount` varchar(255) DEFAULT NULL,
  `payment_id` int(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `start_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `end_date` timestamp NULL DEFAULT NULL,
  `isexpired` tinyint(1) DEFAULT 0,
  `remaining_calls` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_subscription`
--

INSERT INTO `customer_subscription` (`id`, `user_id`, `plan_id`, `tenure`, `status`, `amount`, `payment_id`, `created_at`, `start_date`, `end_date`, `isexpired`, `remaining_calls`) VALUES
(16, 1, 1, 1, 'Approved', '999', 0, '2025-06-11 07:09:31', '2025-06-11 07:09:31', '2025-07-11 07:09:31', 0, 30);

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

--
-- Dumping data for table `distributor`
--

INSERT INTO `distributor` (`id`, `companyName`, `mailId`, `gstType`, `gstNo`, `size`, `address`, `ownerName`, `mobileNo`, `image`, `createdAt`, `updatedAt`, `password`, `reset_token`, `reset_token_expiry`) VALUES
(1, 'TechLink Distribution', 'contact@techlinkdistributors.com', 'REGISTERED', '27ABCDE1234F1Z5', 'SMALL', '123, Industrial Area, Pune, Maharashtra, 411001, India', 'Priyal Patel', '9876543210', '', '2025-05-01 14:23:48', NULL, '$2y$10$0yRA.YmTRRFFA1nZdsVy4eqEuUpVhIshOKN/sJXcvK1ZpQisRqpTu', NULL, NULL);

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

--
-- Dumping data for table `freelancer`
--

INSERT INTO `freelancer` (`id`, `name`, `email`, `password`, `experience`, `created_at`, `reset_token`, `reset_token_expiry`, `image`, `updated_at`, `is_approved`) VALUES
(1, 'Aarav Sharma', 'aarav.sharma@example.com', '$2y$10$gHDPGOmUX0qc6MTgKvIvOu1t64rTKQ2MA/MbEbYC9VNGsxtV6p1QO', 5, '2025-05-01 09:08:28', NULL, NULL, NULL, NULL, 1);

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

--
-- Dumping data for table `freelancer_skills`
--

INSERT INTO `freelancer_skills` (`id`, `freelancer_id`, `skill_id`, `certificate_path`, `is_approved`, `created_at`) VALUES
(1, 1, 1, 'uploads/Blue Minimalist Certificate Of Achievement.png', 1, '2025-05-01 09:08:28'),
(2, 1, 2, 'uploads/68144ee7dabe8_Blue Minimalist Certificate Of Achievement (1).pdf', 1, '2025-05-02 04:49:43');

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

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `customer_id`, `total_amount`, `total_tax`, `created_at`) VALUES
(1, 1, 4248.00, 648.00, '2025-04-06 06:00:00'),
(2, 1, 7080.00, 1080.00, '2025-04-11 05:30:00'),
(3, 1, 4602.00, 702.00, '2025-05-04 05:30:00'),
(4, 1, 8732.00, 1332.00, '2025-05-16 10:00:00'),
(5, 1, 6891.20, 1051.20, '2025-06-03 06:30:00'),
(6, 1, 10620.00, 1620.00, '2025-06-10 14:15:55'),
(7, 1, 8496.00, 1296.00, '2025-06-11 04:56:21');

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

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_name`, `quantity`, `price`, `total`, `tax`, `tax_name`) VALUES
(1, 1, 'TP-Link TL-WN823N 300Mbps Mini USB WiFi Adapter', 5, 720.00, 3600.00, 648.00, 'GST 18%'),
(2, 2, 'Sandisk Ultra Dual Drive 64GB USB 3.0 Pen Drive', 8, 750.00, 6000.00, 1080.00, 'GST 18%'),
(3, 3, 'Logitech M221 Silent Wireless Mouse', 6, 650.00, 3900.00, 702.00, 'GST 18%'),
(4, 4, 'Sandisk Ultra Dual Drive 64GB USB 3.0 Pen Drive', 10, 740.00, 7400.00, 1332.00, 'GST 18%'),
(5, 5, 'TP-Link TL-WN823N 300Mbps Mini USB WiFi Adapter', 8, 730.00, 5840.00, 1051.20, 'GST 18%'),
(6, 6, ' Sandisk Ultra Dual Drive 64GB USB 3.0 Pen Drive', 12, 750.00, 9000.00, 1620.00, 'GST 18%'),
(7, 7, 'TP-Link TL-WN823N 300Mbps Mini USB WiFi Adapter', 10, 720.00, 7200.00, 1296.00, 'GST 18%');

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
(1, 'Logitech'),
(2, 'boAt'),
(3, 'SanDisk'),
(4, 'TP-Link');

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
  `custom_product_name` varchar(255) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `razorpay_order_id` varchar(255) DEFAULT NULL,
  `razorpay_payment_id` varchar(255) DEFAULT NULL,
  `razorpay_signature` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_details`
--

INSERT INTO `order_details` (`id`, `customerId`, `status`, `createdAt`, `updatedAt`, `productId`, `quantity`, `custom_product_name`, `payment_status`, `razorpay_order_id`, `razorpay_payment_id`, `razorpay_signature`, `amount`) VALUES
(1, 1, 'DELIVERED', '2025-04-06 11:00:00', '2025-04-08 15:00:00', 1, 5, NULL, 'paid', NULL, NULL, NULL, 4248.00),
(2, 1, 'DELIVERED', '2025-04-11 10:30:00', '2025-04-13 14:00:00', 2, 8, NULL, 'paid', NULL, NULL, NULL, 7080.00),
(3, 1, 'SHIPPED', '2025-05-04 10:30:00', '2025-05-06 14:00:00', 3, 6, NULL, 'paid', NULL, NULL, NULL, 4602.00),
(4, 1, 'IN_PROCESS', '2025-05-16 15:00:00', '2025-05-16 16:00:00', 2, 10, NULL, 'paid', NULL, NULL, NULL, 8732.00),
(5, 1, 'IN_PROCESS', '2025-06-03 11:30:00', '2025-06-03 12:30:00', 1, 8, NULL, 'paid', NULL, NULL, NULL, 6891.20),
(6, 1, 'PENDING', '2025-06-11 16:00:00', '2025-06-10 19:45:55', 2, 12, NULL, 'paid', 'order_QfWSyGaXbIV4q4', 'pay_QfWT8XvDwTrRZh', 'ece1ef4c5178d4dbd19082cbbaf956e171c36bb62b04433d3bcc4887f1bdaedb', 10620.00),
(7, 1, 'IN_PROCESS', '2025-06-11 10:25:32', '2025-06-11 10:26:21', 1, 10, NULL, 'paid', 'order_QflSufuaPr2j9A', 'pay_QflT8CbqNsP8lL', '4ffd18bc1b04a410acadf5d2e59de39a33f1a61c821419671007b350554d05b7', 8496.00);

-- --------------------------------------------------------

--
-- Table structure for table `payment_orders`
--

CREATE TABLE `payment_orders` (
  `id` int(11) NOT NULL,
  `order_id` varchar(255) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `tenure` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `payment_id` varchar(255) DEFAULT NULL,
  `status` enum('created','completed','failed') DEFAULT 'created',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
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
  `updatedAt` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `tax_rate_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`id`, `name`, `oemId`, `categoryId`, `subcategories`, `partNo`, `model`, `hsnNo`, `images`, `description`, `datasheet`, `createdAt`, `updatedAt`, `tax_rate_id`) VALUES
(1, 'TP-Link TL-WN823N 300Mbps Mini USB WiFi Adapter', 4, 4, '[\"WiFi Adapter\"]', 'TL-WN823N', 'Nano USB WiFi', '85176290', '[\"uploads\\/images\\/681358178ab91_images__1_.jpeg\",\"uploads\\/images\\/681358178b3ba_images.jpeg\",\"uploads\\/images\\/681358178c09b_download.jpeg\"]', '300Mbps high-speed Wi-Fi (2.4GHz band), Plug-and-play, works with Windows 10/8/7, Compact & portable, WPA/WPA2 encryption for safe browsing.', 'uploads/datasheets/681358178c739_Tp_Link.pdf', '2025-05-01 16:46:39', NULL, 1),
(2, ' Sandisk Ultra Dual Drive 64GB USB 3.0 Pen Drive', 3, 3, '[\"Pen Drive\",\" USB Flash Drive\"]', ' SDDDC2-064G-G46', ' Ultra Dual Drive', '85235100', '[\"uploads\\/images\\/68135c6b60655_image2.jpg\",\"uploads\\/images\\/68135c6b612bd_image1.jpg\",\"uploads\\/images\\/68135c6b61ba1_image.jpg\"]', '64GB storage, USB 3.0 (backward compatible with USB 2.0)\r\n\r\nDual USB Type-C & Type-A for smartphones & PCs\r\n\r\nTransfer speeds up to 150MB/s\r\n\r\nPassword protection with SanDisk SecureAccess software', 'uploads/datasheets/68135c6b625fd_sandisk.pdf', '2025-05-01 17:05:07', NULL, 1),
(3, 'Logitech M221 Silent Wireless Mouse', 1, 1, '[\" Mouse\",\" Wireless Devices\",\" Peripherals\"]', '910-005724', ' M221', '84716070', '[\"uploads\\/images\\/68135e81da524_image3.jpg\",\"uploads\\/images\\/68135e81db2f1_images__1_.jpeg\",\"uploads\\/images\\/68135e81dbd93_images.jpeg\"]', 'Compact wireless mouse with silent clicks (90% noise reduction), 2.4GHz wireless connectivity with USB nano receiver, 18-month battery life, comfortable ambidextrous design with rubber side grips. Compatible with Windows, macOS, Linux, and Chrome OS.', 'uploads/datasheets/68135e81dc574_logitech.pdf', '2025-05-01 17:14:01', NULL, 1);

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `quotation_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_details`
--

INSERT INTO `purchase_details` (`id`, `distributor_id`, `super_admin_id`, `total_amount`, `total_tax`, `created_at`, `quotation_id`) VALUES
(1, 1, 1, 3400.00, 612.00, '2025-04-13 10:30:00', 3),
(2, 1, 1, 3720.00, 669.60, '2025-05-19 07:30:00', 6),
(3, 1, 1, 5520.00, 993.60, '2025-06-16 06:00:00', 9),
(4, 1, 1, 7990.00, 1438.20, '2025-06-11 04:54:50', 12),
(5, 1, 1, 7490.00, 1348.20, '2025-06-11 07:07:15', 14);

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

--
-- Dumping data for table `purchase_items`
--

INSERT INTO `purchase_items` (`id`, `purchase_id`, `product_name`, `quantity`, `price`, `tax`, `total`, `tax_name`) VALUES
(1, 1, 'TP-Link TL-WN823N 300Mbps Mini USB WiFi Adapter', 5, 680.00, 612.00, 4012.00, 'GST 18%'),
(2, 2, 'Logitech M221 Silent Wireless Mouse', 6, 620.00, 669.60, 4389.60, 'GST 18%'),
(3, 3, 'TP-Link TL-WN823N 300Mbps Mini USB WiFi Adapter', 8, 690.00, 993.60, 6513.60, 'GST 18%'),
(4, 4, 'TP-Link TL-WN823N 300Mbps Mini USB WiFi Adapter', 10, 799.00, 1438.20, 9428.20, 'GST 18%'),
(5, 5, 'TP-Link TL-WN823N 300Mbps Mini USB WiFi Adapter', 10, 749.00, 1348.20, 8838.20, 'GST 18%');

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
  `subject` varchar(255) NOT NULL,
  `customer_approval` enum('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING',
  `superadmin_approval` enum('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING',
  `distributor_approval` enum('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quotation_header`
--

INSERT INTO `quotation_header` (`quotation_id`, `customerId`, `status`, `createdAt`, `updatedAt`, `superAdminId`, `distributorId`, `subject`, `customer_approval`, `superadmin_approval`, `distributor_approval`) VALUES
(1, 1, 'APPROVED', '2025-04-05 10:00:00', '2025-04-05 11:00:00', 1, NULL, 'April Quotation for Networking Products', 'APPROVED', 'APPROVED', 'PENDING'),
(2, 1, 'APPROVED', '2025-04-10 09:30:00', '2025-04-10 10:30:00', 1, NULL, 'April Quotation for Storage Devices', 'APPROVED', 'APPROVED', 'PENDING'),
(3, NULL, 'APPROVED', '2025-04-12 14:00:00', '2025-04-12 15:00:00', 1, 1, 'Revised April Quotation for Networking Products', 'PENDING', 'APPROVED', 'APPROVED'),
(4, 1, 'APPROVED', '2025-05-03 09:00:00', '2025-05-03 10:00:00', 1, NULL, 'May Quotation for Computer Accessories', 'APPROVED', 'APPROVED', 'PENDING'),
(5, 1, 'APPROVED', '2025-05-15 14:00:00', '2025-05-15 15:00:00', 1, NULL, 'May Quotation for Storage Devices', 'APPROVED', 'APPROVED', 'PENDING'),
(6, NULL, 'APPROVED', '2025-05-18 11:00:00', '2025-05-18 12:00:00', 1, 1, 'Revised May Quotation for Computer Accessories', 'PENDING', 'APPROVED', 'APPROVED'),
(7, 1, 'APPROVED', '2025-06-02 10:00:00', '2025-06-02 11:00:00', 1, NULL, 'June Quotation for Networking Products', 'APPROVED', 'APPROVED', 'PENDING'),
(8, 1, 'APPROVED', '2025-06-10 14:30:00', '2025-06-10 15:30:00', 1, NULL, 'June Quotation for Storage Devices', 'APPROVED', 'APPROVED', 'PENDING'),
(9, NULL, 'PENDING', '2025-06-15 09:00:00', '2025-06-10 18:24:08', 1, 1, 'Revised June Quotation for Networking Products', 'PENDING', 'PENDING', 'PENDING'),
(10, 1, 'PENDING', '2025-06-10 15:46:06', '2025-06-10 19:27:32', 1, NULL, 'Request for Quotation for Storage Products - Tech Innovations Pvt. Ltd. ', 'PENDING', 'APPROVED', 'PENDING'),
(11, 1, 'APPROVED', '2025-06-11 06:53:10', '2025-06-11 10:25:32', 1, NULL, 'Request for Quotation for Storage Products - Tech Innovations Pvt. Ltd.   (Revised)', 'APPROVED', 'APPROVED', 'PENDING'),
(12, NULL, 'APPROVED', '2025-06-11 10:24:04', '2025-06-11 10:24:50', 1, 1, 'Request for Quotation for Storage Products - Tech Innovations Pvt. Ltd.   (Revised) (Revised)', 'PENDING', 'APPROVED', 'APPROVED'),
(13, 1, 'PENDING', '2025-06-11 09:05:39', NULL, 1, NULL, 'Wifi Adapters', 'PENDING', 'PENDING', 'PENDING'),
(14, NULL, 'APPROVED', '2025-06-11 12:36:26', '2025-06-11 12:37:15', 1, 1, 'Wifi Adapters (Revised)', 'PENDING', 'APPROVED', 'APPROVED');

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
(1, 1, 1, 5, 720, '2025-04-05 10:00:00', 1),
(2, 2, 2, 8, 750, '2025-04-10 09:30:00', 1),
(3, 3, 1, 5, 680, '2025-04-12 14:00:00', 1),
(4, 4, 3, 6, 650, '2025-05-03 09:00:00', 1),
(5, 5, 2, 10, 740, '2025-05-15 14:00:00', 1),
(6, 6, 3, 6, 620, '2025-05-18 11:00:00', 1),
(7, 7, 1, 8, 730, '2025-06-02 10:00:00', 1),
(8, 8, 2, 12, 760, '2025-06-10 14:30:00', 1),
(9, 9, 1, 8, 690, '2025-06-15 09:00:00', 1),
(10, 10, 2, 10, 999, '2025-06-10 15:46:06', 1),
(11, 11, 1, 10, 799, '2025-06-11 06:53:10', 1),
(12, 12, 1, 10, 799, '2025-06-11 10:24:04', NULL),
(13, 13, 1, 10, 799, '2025-06-11 09:05:39', 1),
(14, 14, 1, 10, 749, '2025-06-11 12:36:26', NULL);

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

--
-- Dumping data for table `skills`
--

INSERT INTO `skills` (`id`, `skill_name`) VALUES
(2, 'CCTV Expert'),
(1, 'Network Expert');

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

--
-- Dumping data for table `tax_rates`
--

INSERT INTO `tax_rates` (`id`, `tax_name`, `tax_percentage`) VALUES
(1, 'GST 18%', 18);

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

--
-- Dumping data for table `ticket`
--

INSERT INTO `ticket` (`id`, `customerId`, `freelancerId`, `status`, `description`, `createdAt`, `updatedAt`, `skill_id`, `priority`, `remark`) VALUES
(1, 1, 1, 'RESOLVED', 'The CCTV footage is blurry, distorted, or has poor image quality.\r\n', '2025-05-02 10:14:49', '2025-05-02 11:31:14', 2, 'Medium', 'The issue has been resolved. You should now have a clear image from the CCTV feed.'),
(2, 1, 1, 'RESOLVED', 'cctv not working', '2025-06-09 20:17:26', '2025-06-09 20:19:10', 2, 'Medium', NULL),
(3, 1, 1, 'RESOLVED', 'cctv issue', '2025-06-09 20:39:37', '2025-06-09 20:42:05', 2, 'Medium', NULL),
(4, 1, NULL, 'PENDING', 'eeeses', '2025-06-10 10:54:09', NULL, 2, 'Medium', NULL),
(5, 1, 1, 'ASSIGNED', 'jyiuhi', '2025-06-10 11:12:51', '2025-06-10 14:15:31', 2, 'Medium', NULL),
(6, 1, 1, 'RESOLVED', 'CCTv are not working', '2025-06-11 12:39:53', '2025-06-11 12:40:46', 2, 'Medium', 'cctv are working properly now');

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
(1, 'Sagar Patil ', 'sagarpatil062002@gmail.com', '$2y$10$izfL0uSNCJq7iGILzsfF4.EwN4K0dImhOn/0FlECyzerDc2x6LPnO', '9096471633', '2025-05-01 08:05:20', NULL, NULL);

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
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_orders`
--
ALTER TABLE `payment_orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oemId` (`oemId`),
  ADD KEY `categoryId` (`categoryId`),
  ADD KEY `tax_rate_id` (`tax_rate_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `customerdistributor`
--
ALTER TABLE `customerdistributor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customer_plan`
--
ALTER TABLE `customer_plan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `customer_subscription`
--
ALTER TABLE `customer_subscription`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `distributor`
--
ALTER TABLE `distributor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `freelancer`
--
ALTER TABLE `freelancer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `freelancer_skills`
--
ALTER TABLE `freelancer_skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `oem`
--
ALTER TABLE `oem`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payment_orders`
--
ALTER TABLE `payment_orders`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `quotation`
--
ALTER TABLE `quotation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quotation_header`
--
ALTER TABLE `quotation_header`
  MODIFY `quotation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `quotation_product`
--
ALTER TABLE `quotation_product`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `sectors`
--
ALTER TABLE `sectors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `skills`
--
ALTER TABLE `skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ticket`
--
ALTER TABLE `ticket`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
-- Constraints for table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `product_ibfk_1` FOREIGN KEY (`oemId`) REFERENCES `oem` (`id`),
  ADD CONSTRAINT `product_ibfk_2` FOREIGN KEY (`categoryId`) REFERENCES `category` (`id`),
  ADD CONSTRAINT `product_ibfk_3` FOREIGN KEY (`tax_rate_id`) REFERENCES `tax_rates` (`id`);

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
