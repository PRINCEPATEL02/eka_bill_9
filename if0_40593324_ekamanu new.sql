-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 27, 2026 at 04:57 AM
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
-- Database: `if0_40593324_ekamanu`
--

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(100) NOT NULL,
  `invoice_date` date NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `buyer_name` varchar(255) NOT NULL,
  `buyer_address` text NOT NULL,
  `buyer_mobile` varchar(20) NOT NULL,
  `buyer_gst` varchar(50) NOT NULL,
  `buyer_state` varchar(100) NOT NULL,
  `consignee_name` varchar(255) NOT NULL,
  `consignee_address` text NOT NULL,
  `consignee_mobile` varchar(20) NOT NULL,
  `consignee_gst` varchar(50) NOT NULL,
  `consignee_state` varchar(100) NOT NULL,
  `tax_type` varchar(20) NOT NULL,
  `cgst_rate` decimal(5,2) DEFAULT 0.00,
  `sgst_rate` decimal(5,2) DEFAULT 0.00,
  `igst_rate` decimal(5,2) DEFAULT 0.00,
  `freight` decimal(10,2) DEFAULT 0.00,
  `round_off` decimal(10,2) DEFAULT 0.00,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account` varchar(50) DEFAULT NULL,
  `bank_ifsc` varchar(20) DEFAULT NULL,
  `bank_branch` varchar(255) DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit_price` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `cgst_amount` decimal(12,2) DEFAULT 0.00,
  `sgst_amount` decimal(12,2) DEFAULT 0.00,
  `igst_amount` decimal(12,2) DEFAULT 0.00,
  `grand_total` decimal(12,2) DEFAULT 0.00,
  `amount_in_words` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `rounding_adjustment` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bills`
--

INSERT INTO `bills` (`id`, `invoice_number`, `invoice_date`, `company_id`, `buyer_name`, `buyer_address`, `buyer_mobile`, `buyer_gst`, `buyer_state`, `consignee_name`, `consignee_address`, `consignee_mobile`, `consignee_gst`, `consignee_state`, `tax_type`, `cgst_rate`, `sgst_rate`, `igst_rate`, `freight`, `round_off`, `bank_name`, `bank_account`, `bank_ifsc`, `bank_branch`, `terms`, `items`, `quantity`, `unit_price`, `total_amount`, `cgst_amount`, `sgst_amount`, `igst_amount`, `grand_total`, `amount_in_words`, `created_at`, `rounding_adjustment`) VALUES
(138, '33/2025-26', '2026-01-26', 30, 'SARVATMAN ROAD EQUIPMENTS PVT LTD', 'NAVIN BLOCK NO 211 , KHATA NO 843, AT IYASARA , TA - VISNAGAR  , MAHESANA , GUJARAT - 384315', '+91 9428919894', '24ABACS4005R1Z8', 'GUJARAT', 'SARVATMAN ROAD EQUIPMENTS PVT LTD', 'NAVIN BLOCK NO 211 , KHATA NO 843, AT IYASARA , TA - VISNAGAR  , MAHESANA , GUJARAT - 384315', '+91 9428919894', '24ABACS4005R1Z8', 'GUJARAT', 'SGST/CGST', 9.00, 9.00, 0.00, 0.00, 0.00, 'Bank of Baroda', '01540200001092', 'BARB0VISNAG', 'VISNAGAR - 384315', 'Goods will be dispatched after 100% payment.\r\nGoods once sold will not be taken back.\r\nInterest @ 18% p.a. will be charged if the payment is not made within the stipulated time.', '[{\"description\":\"Side Brush\",\"hsn\":\"9603\",\"quantity\":6,\"unit\":\"NOS\",\"rate\":3300,\"amount\":19800}]', 6.00, 3300.0000, 19800.00, 1782.00, 1782.00, 0.00, 23364.00, 'RUPEES TWENTY THREE THOUSAND THREE HUNDRED SIXTY FOUR ONLY', '2026-01-26 22:44:30', 0.00),
(142, '24/2025-26', '2026-01-27', 31, 'HARSIDDH CONSTRUCTION EQUIPMENT', 'PLOT NO:66/2 G.I.D.C VISNAGAR,\r\nNR.KOSA X,VISNAGAR 384315,MEHSANA', '+91 8238939906', '24AIIPT7106Q1ZN', 'GUJARAT', 'HARSIDDH CONSTRUCTION EQUIPMENT', 'PLOT NO:66/2 G.I.D.C VISNAGAR,\r\nNR.KOSA X,VISNAGAR 384315,MEHSANA', '+91 8238939906', '24AIIPT7106Q1ZN', 'GUJARAT', 'SGST/CGST', 9.00, 9.00, 0.00, 0.00, 0.00, 'Bank of Baroda', '01540200001092', 'BARB0VISNAG', 'VISNAGAR - 384315', 'Goods will be dispatched after 100% payment.\r\nGoods once sold will not be taken back.\r\nInterest @ 18% p.a. will be charged if the payment is not made within the stipulated time.', '[{\"description\":\"Side Brush\",\"hsn\":\"9603\",\"quantity\":2,\"unit\":\"NOS\",\"rate\":3600,\"amount\":7200}]', 2.00, 3600.0000, 7200.00, 648.00, 648.00, 0.00, 8496.00, 'RUPEES EIGHT THOUSAND FOUR HUNDRED NINETY SIX ONLY', '2026-01-27 08:57:54', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `gst_number` varchar(50) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `state` varchar(100) NOT NULL,
  `tax_type` varchar(20) NOT NULL DEFAULT 'SGST/CGST',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `company_name`, `address`, `gst_number`, `mobile`, `state`, `tax_type`, `created_at`) VALUES
(30, 'SARVATMAN ROAD EQUIPMENTS PVT LTD', 'NAVIN BLOCK NO 211 , KHATA NO 843, AT IYASARA , TA - VISNAGAR  , MAHESANA , GUJARAT - 384315', '24ABACS4005R1Z8', '+91 9428919894', 'GUJARAT', 'SGST/CGST', '2026-01-26 05:39:24'),
(31, 'HARSIDDH CONSTRUCTION EQUIPMENT', 'PLOT NO:66/2 G.I.D.C VISNAGAR,\r\nNR.KOSA X,VISNAGAR 384315,MEHSANA', '24AIIPT7106Q1ZN', '+91 8238939906', 'GUJARAT', 'SGST/CGST', '2026-01-27 03:27:28');

-- --------------------------------------------------------

--
-- Table structure for table `estimates`
--

CREATE TABLE `estimates` (
  `id` int(11) NOT NULL,
  `estimate_number` varchar(50) NOT NULL,
  `estimate_date` date NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `buyer_name` varchar(255) NOT NULL,
  `buyer_address` text NOT NULL,
  `buyer_mobile` varchar(20) NOT NULL,
  `buyer_gst` varchar(20) NOT NULL,
  `buyer_state` varchar(100) NOT NULL,
  `consignee_name` varchar(255) NOT NULL,
  `consignee_address` text NOT NULL,
  `consignee_mobile` varchar(20) NOT NULL,
  `consignee_gst` varchar(20) NOT NULL,
  `consignee_state` varchar(100) NOT NULL,
  `tax_type` varchar(20) NOT NULL DEFAULT 'SGST/CGST',
  `cgst_rate` decimal(5,2) DEFAULT 0.00,
  `sgst_rate` decimal(5,2) DEFAULT 0.00,
  `igst_rate` decimal(5,2) DEFAULT 0.00,
  `freight` decimal(10,2) DEFAULT 0.00,
  `round_off` decimal(10,2) DEFAULT 0.00,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account` varchar(50) DEFAULT NULL,
  `bank_ifsc` varchar(20) DEFAULT NULL,
  `bank_branch` varchar(255) DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `cgst_amount` decimal(10,2) DEFAULT 0.00,
  `sgst_amount` decimal(10,2) DEFAULT 0.00,
  `igst_amount` decimal(10,2) DEFAULT 0.00,
  `grand_total` decimal(10,2) DEFAULT 0.00,
  `amount_in_words` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `estimates`
--

INSERT INTO `estimates` (`id`, `estimate_number`, `estimate_date`, `company_id`, `buyer_name`, `buyer_address`, `buyer_mobile`, `buyer_gst`, `buyer_state`, `consignee_name`, `consignee_address`, `consignee_mobile`, `consignee_gst`, `consignee_state`, `tax_type`, `cgst_rate`, `sgst_rate`, `igst_rate`, `freight`, `round_off`, `bank_name`, `bank_account`, `bank_ifsc`, `bank_branch`, `terms`, `items`, `total_amount`, `cgst_amount`, `sgst_amount`, `igst_amount`, `grand_total`, `amount_in_words`, `created_at`, `updated_at`) VALUES
(1, 'XX/2025-26', '2025-12-04', NULL, 'PATEL ENTERPRISE', '13/OFFICE NO.6, NIRAJ APARTMENT, GHOD\r\nDOD ROAD, NEAR MOHAN PARK, GHOD\r\nDOD ROAD, Surat, Gujarat, 395007', '9537311188', '24ACVPP3853B1ZG', 'Gujarat (24)', 'PATEL ENTERPRISE', '13/OFFICE NO.6, NIRAJ APARTMENT, GHOD\r\nDOD ROAD, NEAR MOHAN PARK, GHOD\r\nDOD ROAD, Surat, Gujarat, 395007', '9537311188', '24ACVPP3853B1ZG', 'Gujarat (24)', 'SGST/CGST', 9.00, 9.00, 0.00, 500.00, 0.00, 'Bank of Baroda', '01540200001092', 'BARB0VISNAG', 'VISNAGAR - 384315', 'Goods will be dispatched after 100% payment.\r\nGoods once sold will not be taken back.\r\nInterest @ 18% p.a. will be charged if the payment is not made within the stipulated time.', '[{\"description\":\"Main Brush\",\"hsn\":\"9603\",\"quantity\":51.98,\"unit\":\"NOS\",\"rate\":800,\"amount\":41584}]', 41584.00, 3742.56, 3742.56, 0.00, 49569.12, 'RUPEES FORTY NINE THOUSAND FIVE HUNDRED SIXTY NINE AND TWELVE PAISE ONLY', '2025-12-03 12:45:58', '2025-12-03 12:45:58'),
(2, 'XX/2025-26', '2025-12-04', NULL, 'PATEL ENTERPRISE', '13/OFFICE NO.6, NIRAJ APARTMENT, GHOD\r\nDOD ROAD, NEAR MOHAN PARK, GHOD\r\nDOD ROAD, Surat, Gujarat, 395007', '9537311188', '24ACVPP3853B1ZG', 'Gujarat (24)', 'PATEL ENTERPRISE', '13/OFFICE NO.6, NIRAJ APARTMENT, GHOD\r\nDOD ROAD, NEAR MOHAN PARK, GHOD\r\nDOD ROAD, Surat, Gujarat, 395007', '9537311188', '24ACVPP3853B1ZG', 'Gujarat (24)', 'SGST/CGST', 9.00, 9.00, 0.00, 500.00, 0.00, 'Bank of Baroda', '01540200001092', 'BARB0VISNAG', 'VISNAGAR - 384315', 'Goods will be dispatched after 100% payment.\r\nGoods once sold will not be taken back.\r\nInterest @ 18% p.a. will be charged if the payment is not made within the stipulated time.', '[{\"description\":\"Main Brush\",\"hsn\":\"9603\",\"quantity\":51.98,\"unit\":\"NOS\",\"rate\":800,\"amount\":41584}]', 41584.00, 3742.56, 3742.56, 0.00, 49569.12, 'RUPEES FORTY NINE THOUSAND FIVE HUNDRED SIXTY NINE AND TWELVE PAISE ONLY', '2025-12-03 12:46:08', '2025-12-03 12:46:08'),
(3, 'XX/2025-26', '2025-12-04', NULL, 'PATEL ENTERPRISE', '13/OFFICE NO.6, NIRAJ APARTMENT, GHOD\r\nDOD ROAD, NEAR MOHAN PARK, GHOD\r\nDOD ROAD, Surat, Gujarat, 395007', '9537311188', '24ACVPP3853B1ZG', 'Gujarat (24)', 'PATEL ENTERPRISE', '13/OFFICE NO.6, NIRAJ APARTMENT, GHOD\r\nDOD ROAD, NEAR MOHAN PARK, GHOD\r\nDOD ROAD, Surat, Gujarat, 395007', '9537311188', '24ACVPP3853B1ZG', 'Gujarat (24)', 'SGST/CGST', 9.00, 9.00, 0.00, 500.00, 0.00, 'Bank of Baroda', '01540200001092', 'BARB0VISNAG', 'VISNAGAR - 384315', 'Goods will be dispatched after 100% payment.\r\nGoods once sold will not be taken back.\r\nInterest @ 18% p.a. will be charged if the payment is not made within the stipulated time.', '[{\"description\":\"Main Brush\",\"hsn\":\"9603\",\"quantity\":52,\"unit\":\"NOS\",\"rate\":800,\"amount\":41600}]', 41600.00, 3744.00, 3744.00, 0.00, 49588.00, 'RUPEES FORTY NINE THOUSAND FIVE HUNDRED EIGHTY EIGHT ONLY', '2025-12-03 12:47:14', '2025-12-03 12:47:14'),
(4, 'XX/2025-26', '2025-12-05', NULL, 'PATEL ENTERPRISE', '13/OFFICE NO.6, NIRAJ APARTMENT, GHOD\r\nDOD ROAD, NEAR MOHAN PARK, GHOD\r\nDOD ROAD, Surat, Gujarat, 395007', '9537311188', '24ACVPP3853B1ZG', 'Gujarat (24)', 'PATEL ENTERPRISE', '13/OFFICE NO.6, NIRAJ APARTMENT, GHOD\r\nDOD ROAD, NEAR MOHAN PARK, GHOD\r\nDOD ROAD, Surat, Gujarat, 395007', '9537311188', '24ACVPP3853B1ZG', 'Gujarat (24)', 'SGST/CGST', 9.00, 9.00, 0.00, 0.00, 0.00, 'Bank of Baroda', '01540200001092', 'BARB0VISNAG', 'VISNAGAR - 384315', 'Goods will be dispatched after 100% payment.\r\nGoods once sold will not be taken back.\r\nInterest @ 18% p.a. will be charged if the payment is not made within the stipulated time.', '[{\"description\":\"Main Brush\",\"hsn\":\"9603\",\"quantity\":3,\"unit\":\"NOS\",\"rate\":300,\"amount\":900}]', 900.00, 81.00, 81.00, 0.00, 1062.00, 'RUPEES ONE THOUSAND SIXTY TWO ONLY', '2025-12-05 07:53:07', '2025-12-05 07:53:07'),
(5, 'XX/2025-26', '2025-12-08', NULL, 'PATEL ENTERPRISE', '13/OFFICE NO.6, NIRAJ APARTMENT, GHOD\r\nDOD ROAD, NEAR MOHAN PARK, GHOD\r\nDOD ROAD, Surat, Gujarat, 395007', '9537311188', '24ACVPP3853B1ZG', 'Gujarat (24)', 'PATEL ENTERPRISE', '13/OFFICE NO.6, NIRAJ APARTMENT, GHOD\r\nDOD ROAD, NEAR MOHAN PARK, GHOD\r\nDOD ROAD, Surat, Gujarat, 395007', '9537311188', '24ACVPP3853B1ZG', 'Gujarat (24)', 'SGST/CGST', 9.00, 9.00, 0.00, 0.00, 0.00, 'Bank of Baroda', '01540200001092', 'BARB0VISNAG', 'VISNAGAR - 384315', 'Goods will be dispatched after 100% payment.\r\nGoods once sold will not be taken back.\r\nInterest @ 18% p.a. will be charged if the payment is not made within the stipulated time.', '[{\"description\":\"Side Brush\",\"hsn\":\"9603\",\"quantity\":5,\"unit\":\"NOS\",\"rate\":600,\"amount\":3000}]', 3000.00, 270.00, 270.00, 0.00, 3540.00, 'RUPEES THREE THOUSAND FIVE HUNDRED FORTY ONLY', '2025-12-08 06:27:50', '2025-12-08 06:27:50'),
(6, 'XX/2025-26', '2025-12-08', NULL, 'SARVATMAN ROAD EQUIPMENTS PVT LTD', 'NAVIN BLOCK NO 211,KHATA NO 843,\r\nAT IYASARA, TA-VISNAGAR, MEHSANA,\r\nGUJARAT -384315', '24ABACS4005R1Z8', '9428919894', 'GUJARAT(24)', 'SARVATMAN ROAD EQUIPMENTS PVT LTD', 'NAVIN BLOCK NO 211,KHATA NO 843,\r\nAT IYASARA, TA-VISNAGAR, MEHSANA,\r\nGUJARAT -384315', '24ABACS4005R1Z8', '9428919894', 'GUJARAT(24)', 'SGST/CGST', 9.00, 9.00, 0.00, 0.00, 0.00, 'Bank of Baroda', '01540200001092', 'BARB0VISNAG', 'VISNAGAR - 384315', 'Goods will be dispatched after 100% payment.\r\nGoods once sold will not be taken back.\r\nInterest @ 18% p.a. will be charged if the payment is not made within the stipulated time.', '[{\"description\":\"Side Brush\",\"hsn\":\"9603\",\"quantity\":5,\"unit\":\"NOS\",\"rate\":600,\"amount\":3000}]', 3000.00, 270.00, 270.00, 0.00, 3540.00, 'RUPEES THREE THOUSAND FIVE HUNDRED FORTY ONLY', '2025-12-08 06:39:51', '2025-12-08 06:39:51'),
(7, 'XX/2025-26', '2025-12-08', NULL, 'SARVATMAN ROAD EQUIPMENTS PVT LTD', 'NAVIN BLOCK NO 211,KHATA NO 843,\r\nAT IYASARA, TA-VISNAGAR, MEHSANA,\r\nGUJARAT -384315', '24ABACS4005R1Z8', '9428919894', 'GUJARAT(24)', 'SARVATMAN ROAD EQUIPMENTS PVT LTD', 'NAVIN BLOCK NO 211,KHATA NO 843,\r\nAT IYASARA, TA-VISNAGAR, MEHSANA,\r\nGUJARAT -384315', '24ABACS4005R1Z8', '9428919894', 'GUJARAT(24)', 'SGST/CGST', 9.00, 9.00, 0.00, 0.00, 0.00, 'Bank of Baroda', '01540200001092', 'BARB0VISNAG', 'VISNAGAR - 384315', 'Goods will be dispatched after 100% payment.\r\nGoods once sold will not be taken back.\r\nInterest @ 18% p.a. will be charged if the payment is not made within the stipulated time.', '[{\"description\":\"Main Brush\",\"hsn\":\"9603\",\"quantity\":5,\"unit\":\"NOS\",\"rate\":200,\"amount\":1000}]', 1000.00, 90.00, 90.00, 0.00, 1180.00, 'RUPEES ONE THOUSAND ONE HUNDRED EIGHTY ONLY', '2025-12-08 08:48:01', '2025-12-08 08:48:01'),
(8, 'XX/2025-26', '2025-12-13', 28, 'bcjd', 'cnbcn s', '1221212121', '12121542124512', 'Gujarat', 'bcjd', 'cnbcn s', '1221212121', '12121542124512', 'Gujarat', 'SGST/CGST', 9.00, 9.00, 0.00, 0.00, 0.00, 'Bank of Baroda', '01540200001092', 'BARB0VISNAG', 'VISNAGAR - 384315', 'Goods will be dispatched after 100% payment.\r\nGoods once sold will not be taken back.\r\nInterest @ 18% p.a. will be charged if the payment is not made within the stipulated time.', '[{\"description\":\"Side Brush\",\"hsn\":\"9603\",\"quantity\":5,\"unit\":\"NOS\",\"rate\":40,\"amount\":200}]', 200.00, 18.00, 18.00, 0.00, 236.00, 'RUPEES TWO HUNDRED THIRTY SIX ONLY', '2025-12-13 11:35:32', '2025-12-13 11:35:32'),
(9, 'XX/2025-26', '2025-12-13', 28, 'bcjd', 'cnbcn s', '1221212121', '12121542124512', 'Gujarat', 'bcjd', 'cnbcn s', '1221212121', '12121542124512', 'Gujarat', 'SGST/CGST', 9.00, 9.00, 0.00, 0.00, 0.00, 'Bank of Baroda', '01540200001092', 'BARB0VISNAG', 'VISNAGAR - 384315', 'Goods will be dispatched after 100% payment.\r\nGoods once sold will not be taken back.\r\nInterest @ 18% p.a. will be charged if the payment is not made within the stipulated time.', '[{\"description\":\"Side Brush\",\"hsn\":\"9603\",\"quantity\":8,\"unit\":\"NOS\",\"rate\":44,\"amount\":352}]', 352.00, 31.68, 31.68, 0.00, 415.36, 'RUPEES FOUR HUNDRED FIFTEEN AND THIRTY SIX PAISE ONLY', '2025-12-13 12:34:11', '2025-12-13 12:34:11');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT 'Cash',
  `reference_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` int(11) NOT NULL,
  `bill_number` varchar(255) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `items` text DEFAULT NULL,
  `quantity` decimal(10,3) DEFAULT NULL,
  `amount_per_unit` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `gst_amount` decimal(10,2) DEFAULT NULL,
  `gst_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `total_with_gst` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `rounding_adjustment` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_type` enum('SGST/CGST','IGST') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`id`, `bill_number`, `company_name`, `purchase_date`, `items`, `quantity`, `amount_per_unit`, `gst_amount`, `gst_percent`, `total_with_gst`, `total_amount`, `pdf_path`, `created_at`, `rounding_adjustment`, `tax_type`) VALUES
(72, 'HKF/25-26/221', 'Hare Krishna Filaments', '2026-01-24', 'PP', 100.400, 210.0000, 3795.12, 18.00, 24879.00, 21084.00, NULL, '2026-01-26 17:20:23', -0.12, 'IGST');

-- --------------------------------------------------------

--
-- Table structure for table `recipes`
--

CREATE TABLE `recipes` (
  `id` int(11) NOT NULL,
  `pp` decimal(10,2) NOT NULL DEFAULT 0.00,
  `hdpe` decimal(10,2) NOT NULL DEFAULT 0.00,
  `ms_wire` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recipes`
--

INSERT INTO `recipes` (`id`, `pp`, `hdpe`, `ms_wire`, `created_at`) VALUES
(1, 1.80, 1.00, 1.80, '2026-01-22 15:15:05');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `bill_number` varchar(255) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `sale_date` date DEFAULT NULL,
  `items` text DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_per_unit` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `gst_amount` decimal(10,2) DEFAULT NULL,
  `total_with_gst` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `rounding_adjustment` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_type` enum('SGST/CGST','IGST') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `bill_number`, `company_name`, `sale_date`, `items`, `quantity`, `amount_per_unit`, `gst_amount`, `total_with_gst`, `total_amount`, `pdf_path`, `created_at`, `rounding_adjustment`, `tax_type`) VALUES
(2, 'SAL002', 'Customer Y', '2023-10-06', 'Product3, Product4', 0.00, 0.0000, NULL, NULL, 6000.00, NULL, '2025-12-03 05:50:36', 0.00, ''),
(17, '45', 'vxvdvd', '2025-12-13', 'Main Brush', 4.00, 22.0000, 15.84, 103.84, 88.00, NULL, '2025-12-13 11:37:19', 0.00, ''),
(18, '488', 'sdv', '2025-12-13', 'Side Brush', 45.00, 36.0000, 291.60, 1911.60, 1620.00, NULL, '2025-12-13 11:37:37', 0.00, ''),
(19, '488', 'sdv', '2025-12-13', 'Side Brush', 45.00, 36.0000, 291.60, 1911.60, 1620.00, NULL, '2025-12-13 11:37:42', 0.00, ''),
(20, '45', 'sdv', '2025-12-13', 'Main Brush', 5.00, 54.0000, 48.60, 318.60, 270.00, NULL, '2025-12-13 11:37:58', 0.00, ''),
(22, '54', 'sdf', '2025-12-13', 'Side Brush', 4.00, 58.0000, 41.76, 273.76, 232.00, NULL, '2025-12-13 11:38:45', 0.00, ''),
(23, '45', 'vxvdvd', '2025-12-13', 'Main Brush', 4.00, 22.0000, 15.84, 103.84, 88.00, NULL, '2025-12-13 11:38:54', 0.00, ''),
(24, '45', 'bcjd', '2025-12-13', 'Side Brush', 45.00, 55.0000, 445.50, 2920.50, 2475.00, NULL, '2025-12-13 11:45:21', 0.00, ''),
(25, '45', 'bcjd', '2025-12-13', 'Side Brush', 45.00, 55.0000, 445.50, 2920.50, 2475.00, NULL, '2025-12-13 11:45:25', 0.00, ''),
(26, '45', 'bcjd', '2025-12-13', 'Side Brush', 45.00, 55.0000, 445.50, 2920.50, 2475.00, NULL, '2025-12-13 11:45:44', 0.00, ''),
(27, '4545454', 'sdfpsdfsv', '2025-12-13', 'Side Brush', 54.00, 4.0000, 38.88, 254.88, 216.00, NULL, '2025-12-13 11:45:58', 0.00, ''),
(28, '4545454', 'sdfpsdfsv', '2025-12-13', 'Side Brush', 54.00, 4.0000, 38.88, 254.88, 216.00, NULL, '2025-12-13 11:50:20', 0.00, ''),
(29, '4545454', 'sdfpsdfsv', '2025-12-13', 'Side Brush', 54.00, 4.0000, 38.88, 254.88, 216.00, NULL, '2025-12-13 11:51:19', 0.00, ''),
(30, '4545454', 'sdfpsdfsv', '2025-12-13', 'Side Brush', 54.00, 4.0000, 38.88, 254.88, 216.00, NULL, '2025-12-13 11:51:26', 0.00, ''),
(31, '45', 'vxvdvd', '2025-12-13', 'Main Brush', 4.00, 22.0000, 15.84, 103.84, 88.00, NULL, '2025-12-13 11:55:11', 0.00, ''),
(32, '45', 'vxvdvd', '2025-12-13', 'Main Brush', 4.00, 22.0000, 15.84, 103.84, 88.00, NULL, '2025-12-13 11:56:58', 0.00, ''),
(33, '45', 'vxvdvd', '2025-12-13', 'Main Brush', 4.00, 22.0000, 15.84, 103.84, 88.00, NULL, '2025-12-13 11:57:02', 0.00, ''),
(34, '45', 'vxvdvd', '2025-12-13', 'Main Brush', 4.00, 22.0000, 15.84, 103.84, 88.00, NULL, '2025-12-13 11:58:03', 0.00, ''),
(35, '45', 'vxvdvd', '2025-12-13', 'Main Brush', 4.00, 22.0000, 15.84, 103.84, 88.00, NULL, '2025-12-13 11:58:34', 0.00, ''),
(36, '45', 'vxvdvd', '2025-12-13', 'Main Brush', 4.00, 22.0000, 15.84, 103.84, 88.00, NULL, '2025-12-13 11:58:43', 0.00, ''),
(37, '45', 'vxvdvd', '2025-12-13', 'Main Brush', 4.00, 22.0000, 15.84, 103.84, 88.00, NULL, '2025-12-13 11:59:31', 0.00, ''),
(38, '45', 'vxvdvd', '2025-12-13', 'Main Brush', 4.00, 22.0000, 15.84, 103.84, 88.00, NULL, '2025-12-13 12:00:24', 0.00, ''),
(39, '45', 'vxvdvd', '2025-12-13', 'Main Brush', 4.00, 22.0000, 15.84, 103.84, 88.00, NULL, '2025-12-13 12:00:27', 0.00, ''),
(40, '45', 'vxvdvd', '2025-12-13', 'Main Brush', 4.00, 22.0000, 15.84, 103.84, 88.00, NULL, '2025-12-13 12:00:58', 0.00, ''),
(41, '45', 'vxvdvd', '2025-12-13', 'Main Brush', 4.00, 22.0000, 15.84, 103.84, 88.00, NULL, '2025-12-13 12:01:04', 0.00, ''),
(42, '25544', 'nnkjnkj', '2025-12-13', 'Main Brush', 545.00, 8.0000, 784.80, 5144.80, 4360.00, NULL, '2025-12-13 12:35:15', 0.00, ''),
(43, '45454545454', 'bcjd', '2026-01-20', 'Side Brush', 655655.00, 999999.9999, 99999999.99, 99999999.99, 99999999.99, NULL, '2026-01-20 11:22:56', 0.00, ''),
(44, '45454544545454545455', 'bcjd', '2026-01-21', 'Side Brush', 450.00, 450.0000, 36450.00, 238950.00, 202500.00, NULL, '2026-01-21 03:56:35', 0.00, ''),
(45, '45454546566565656665656566565', 'bcjd', '2026-01-25', 'Main Brush', 54.00, 4545.0000, 44177.40, 289607.40, 245430.00, NULL, '2026-01-21 09:08:14', 0.00, ''),
(46, '5245', 'SARVATMAN ROAD EQUIPMENTS PVT LTD', '2026-01-22', 'Side Brush', 1500.00, 2.0000, 540.00, 3540.00, 3000.00, NULL, '2026-01-22 07:50:22', 0.00, ''),
(47, '455', 'SARVATMAN ROAD EQUIPMENTS PVT LTD', '2026-01-26', 'Side Brush', 6.00, 600.0000, 648.00, 4248.00, 3600.00, NULL, '2026-01-26 13:28:19', -0.20, 'SGST/CGST'),
(48, '454545445454545', 'SARVATMAN ROAD EQUIPMENTS PVT LTD', '2026-01-26', 'Main Brush', 60.00, 6.0000, 64.80, 424.80, 360.00, NULL, '2026-01-26 13:29:08', 0.00, 'SGST/CGST'),
(49, '26', 'shreesaktiman', '2026-01-26', 'Main Brush', 545.00, 47.9700, 4705.86, 30849.51, 26143.65, NULL, '2026-01-26 16:38:40', 0.00, 'SGST/CGST');

-- --------------------------------------------------------

--
-- Table structure for table `stock_levels`
--

CREATE TABLE `stock_levels` (
  `id` int(11) NOT NULL,
  `pp_stock_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `hdpe_stock_sheets` int(11) NOT NULL DEFAULT 0,
  `ms_wire_stock_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_levels`
--

INSERT INTO `stock_levels` (`id`, `pp_stock_kg`, `hdpe_stock_sheets`, `ms_wire_stock_kg`, `updated_at`) VALUES
(1, 159.00, 65, 149.00, '2026-01-27 03:27:54');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'user1', '$2y$10$nvmGrbwmbKyjXmzkbqILYOL/jHpXLUW2q1GGLFUi/CMOy0VhbNIdm', '2025-12-13 10:17:59'),
(2, 'user2', '$2y$10$nvmGrbwmbKyjXmzkbqILYOL/jHpXLUW2q1GGLFUi/CMOy0VhbNIdm', '2025-12-13 10:17:59'),
(3, 'user3', '$2y$10$nvmGrbwmbKyjXmzkbqILYOL/jHpXLUW2q1GGLFUi/CMOy0VhbNIdm', '2025-12-13 10:17:59'),
(4, 'PrincePatel', '$2y$10$LhlHRgP1frje7jtSfK7VYeWutIAabLCAQ6HB4hB1LWM1QcUVL3mLa', '2026-01-05 13:02:52');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `estimates`
--
ALTER TABLE `estimates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payments_company_id` (`company_id`),
  ADD KEY `idx_payments_date` (`payment_date`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `recipes`
--
ALTER TABLE `recipes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_levels`
--
ALTER TABLE `stock_levels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=143;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `estimates`
--
ALTER TABLE `estimates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `recipes`
--
ALTER TABLE `recipes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
