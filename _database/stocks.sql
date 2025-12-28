-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Dec 28, 2025 at 11:00 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `stocks`
--

-- --------------------------------------------------------

--
-- Table structure for table `app_settings`
--

DROP TABLE IF EXISTS `app_settings`;
CREATE TABLE IF NOT EXISTS `app_settings` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `tax_payer_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_payer_type` enum('FO','PO') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'FO',
  `document_workflow_id` enum('O','P') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'O',
  `base_currency` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EUR',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_settings_user` (`user_id`),
  KEY `fk_settings_created_by` (`created_by`),
  KEY `fk_settings_updated_by` (`updated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `broker_account`
--

DROP TABLE IF EXISTS `broker_account`;
CREATE TABLE IF NOT EXISTS `broker_account` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `broker_code` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `currency` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EUR',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_broker_user_code` (`user_id`,`broker_code`),
  KEY `fk_broker_created_by` (`created_by`),
  KEY `fk_broker_updated_by` (`updated_by`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `broker_account`
--

INSERT INTO `broker_account` (`id`, `user_id`, `broker_code`, `name`, `currency`, `is_active`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(1, 1, 'BKS_BANK', 'BKS Bank', 'EUR', 1, '2025-12-25 19:30:24', '2025-12-25 19:30:30', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `dividend`
--

DROP TABLE IF EXISTS `dividend`;
CREATE TABLE IF NOT EXISTS `dividend` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `broker_account_id` bigint UNSIGNED DEFAULT NULL,
  `instrument_id` bigint UNSIGNED DEFAULT NULL,
  `dividend_payer_id` bigint UNSIGNED NOT NULL,
  `received_date` date NOT NULL,
  `ex_date` date DEFAULT NULL,
  `pay_date` date DEFAULT NULL,
  `dividend_type_code` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_country_code` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gross_amount_eur` decimal(18,2) NOT NULL,
  `foreign_tax_eur` decimal(18,2) DEFAULT NULL,
  `original_currency` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gross_amount_original` decimal(18,6) DEFAULT NULL,
  `foreign_tax_original` decimal(18,6) DEFAULT NULL,
  `fx_rate_to_eur` decimal(18,8) DEFAULT NULL,
  `payer_ident_for_export` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `treaty_exemption_text` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_voided` tinyint(1) NOT NULL DEFAULT '0',
  `void_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_dividend_dedupe` (`user_id`,`dividend_payer_id`,`received_date`,`gross_amount_eur`,`foreign_tax_eur`),
  KEY `idx_div_user_date` (`user_id`,`received_date`),
  KEY `idx_div_user_payer_date` (`user_id`,`dividend_payer_id`,`received_date`),
  KEY `fk_div_broker` (`broker_account_id`),
  KEY `fk_div_instr` (`instrument_id`),
  KEY `fk_div_payer` (`dividend_payer_id`),
  KEY `fk_div_created_by` (`created_by`),
  KEY `fk_div_updated_by` (`updated_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dividend_payer`
--

DROP TABLE IF EXISTS `dividend_payer`;
CREATE TABLE IF NOT EXISTS `dividend_payer` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `payer_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payer_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payer_country_code` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payer_si_tax_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payer_foreign_tax_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_source_country_code` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_dividend_type_code` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_payer_user_name` (`user_id`,`payer_name`),
  KEY `fk_payer_created_by` (`created_by`),
  KEY `fk_payer_updated_by` (`updated_by`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dividend_payer`
--

INSERT INTO `dividend_payer` (`id`, `user_id`, `payer_name`, `payer_address`, `payer_country_code`, `payer_si_tax_id`, `payer_foreign_tax_id`, `default_source_country_code`, `default_dividend_type_code`, `is_active`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(1, 1, '3M CO', '3M Company 3M Center St. Paul, \r\nMN 55144-1000, \r\nUSA', 'US', NULL, NULL, 'US', 'DIV', 1, '2025-12-27 08:06:48', '2025-12-27 08:08:14', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `instrument`
--

DROP TABLE IF EXISTS `instrument`;
CREATE TABLE IF NOT EXISTS `instrument` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `isin` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ticker` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `instrument_type` enum('STOCK','ETF','ADR','BOND','OTHER') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'STOCK',
  `country_code` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trading_currency` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dividend_payer_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_instrument_isin` (`isin`),
  KEY `idx_instrument_ticker` (`ticker`),
  KEY `fk_instrument_dividend_payer` (`dividend_payer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `instrument`
--

INSERT INTO `instrument` (`id`, `isin`, `ticker`, `name`, `instrument_type`, `country_code`, `trading_currency`, `dividend_payer_id`, `created_at`, `updated_at`) VALUES
(1, 'US0116591092', 'ALK', 'Alaska Air Group, Inc.', 'STOCK', 'US', 'USD', NULL, '2025-12-25 10:14:19', '2025-12-25 10:14:19'),
(2, 'US0079031078', 'AMD', 'Advanced Micro Devices, Inc.', 'STOCK', 'US', 'USD', NULL, '2025-12-25 11:04:46', '2025-12-25 11:04:46'),
(3, 'CA15101Q2071', 'CLS', 'Celestica Inc.', 'STOCK', 'CA', 'USD', NULL, '2025-12-25 11:05:36', '2025-12-25 11:05:36'),
(4, 'US12572Q1058', 'CME', 'CME Group Inc.', 'STOCK', 'US', 'USD', NULL, '2025-12-25 11:06:29', '2025-12-25 11:06:39'),
(5, 'KYG254571055', 'CRDO', 'Credo Technology Group Holding Ltd', 'STOCK', 'KY', 'USD', NULL, '2025-12-25 11:07:25', '2025-12-25 11:07:25'),
(6, 'US22788C1053', 'CRWD', 'CrowdStrike Holdings, Inc.', 'STOCK', 'US', 'USD', NULL, '2025-12-25 11:08:01', '2025-12-25 11:08:01'),
(7, 'US30161Q1040', 'EXEL', 'Exelixis, Inc.', 'STOCK', 'US', 'USD', NULL, '2025-12-25 11:10:33', '2025-12-25 11:10:33'),
(8, 'US3695501086', 'GD', 'General Dynamics Corporation', 'STOCK', 'US', 'USD', NULL, '2025-12-25 11:11:17', '2025-12-25 11:11:17'),
(9, 'US5024311095', 'LHX', 'L3Harris Technologies, Inc.', 'STOCK', 'US', 'USD', NULL, '2025-12-25 11:12:06', '2025-12-25 11:12:16'),
(10, 'US86333M1080', 'LRN', 'Stride, Inc.', 'STOCK', 'US', 'USD', NULL, '2025-12-25 11:12:55', '2025-12-25 11:12:55'),
(11, 'US88579Y1010', 'MMM', '3M Company', 'STOCK', 'US', 'USD', NULL, '2025-12-25 11:13:51', '2025-12-25 11:13:51'),
(12, 'US58933Y1055', 'MRK', 'Merck & Co., Inc.', 'STOCK', 'US', 'USD', NULL, '2025-12-25 11:14:28', '2025-12-25 11:14:28'),
(13, 'US5951121038', 'MU', 'Micron Technology, Inc.', 'STOCK', 'US', 'USD', NULL, '2025-12-25 11:15:27', '2025-12-25 11:15:27'),
(14, 'US67066G1040', 'NVDA', 'NVIDIA Corporation', 'STOCK', 'US', 'USD', NULL, '2025-12-25 11:16:13', '2025-12-25 11:16:13'),
(15, 'US83444M1018', 'SOLV', 'Solventum Corporation', 'STOCK', 'US', 'USD', NULL, '2025-12-25 11:16:59', '2025-12-25 11:16:59'),
(16, 'US91680M1071', 'UPST', 'Upstart Holdings, Inc.', 'STOCK', 'US', 'USD', NULL, '2025-12-25 11:17:38', '2025-12-25 11:17:38'),
(17, 'US0378331005', 'AAPL', 'Apple Inc.', 'STOCK', 'US', 'USD', NULL, '2025-12-25 19:29:28', '2025-12-25 19:29:28'),
(18, 'US64829B1008', 'NEWR', 'New Relic Inc.', 'STOCK', 'US', 'USD', NULL, '2025-12-25 20:01:06', '2025-12-25 20:01:06'),
(19, 'US09609G1004', 'BLUE', 'bluebird bio, Inc.', 'STOCK', 'US', 'USD', NULL, '2025-12-25 20:23:41', '2025-12-25 20:23:41'),
(20, 'US57778K1051', 'MAXR', 'Maxar Technologies Inc.', 'STOCK', 'US', 'USD', NULL, '2025-12-25 20:53:20', '2025-12-25 20:53:20'),
(21, 'US9013841070', 'TSVT', '2Seventy Bio Inc', 'STOCK', 'US', 'USD', NULL, '2025-12-25 21:07:23', '2025-12-25 21:07:23');

-- --------------------------------------------------------

--
-- Table structure for table `instrument_price_daily`
--

DROP TABLE IF EXISTS `instrument_price_daily`;
CREATE TABLE IF NOT EXISTS `instrument_price_daily` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `instrument_id` bigint UNSIGNED NOT NULL,
  `price_date` date NOT NULL,
  `open_price` decimal(18,6) DEFAULT NULL,
  `high_price` decimal(18,6) DEFAULT NULL,
  `low_price` decimal(18,6) DEFAULT NULL,
  `close_price` decimal(18,6) NOT NULL,
  `volume` bigint UNSIGNED DEFAULT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `source` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'twelvedata',
  `fetched_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_price_user_instr_date` (`user_id`,`instrument_id`,`price_date`),
  KEY `idx_price_user_date` (`user_id`,`price_date`),
  KEY `idx_price_user_instr` (`user_id`,`instrument_id`),
  KEY `fk_price_instr` (`instrument_id`)
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `instrument_price_daily`
--

INSERT INTO `instrument_price_daily` (`id`, `user_id`, `instrument_id`, `price_date`, `open_price`, `high_price`, `low_price`, `close_price`, `volume`, `currency`, `source`, `fetched_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2025-12-26', 51.300000, 51.740000, 51.099998, 51.490000, 1001200, 'USD', 'twelvedata', '2025-12-28 09:43:19', '2025-12-28 09:43:19', '2025-12-28 09:43:19'),
(2, 1, 1, '2025-12-24', 51.110000, 51.480000, 50.700000, 51.380000, 1255600, 'USD', 'twelvedata', '2025-12-28 09:43:19', '2025-12-28 09:43:19', '2025-12-28 09:43:19'),
(3, 1, 1, '2025-12-23', 53.360000, 53.360000, 51.110000, 51.180000, 2791900, 'USD', 'twelvedata', '2025-12-28 09:43:19', '2025-12-28 09:43:19', '2025-12-28 09:43:19'),
(4, 1, 1, '2025-12-22', 52.110000, 53.720000, 52.000000, 53.480000, 2178400, 'USD', 'twelvedata', '2025-12-28 09:43:19', '2025-12-28 09:43:19', '2025-12-28 09:43:19'),
(5, 1, 1, '2025-12-19', 52.190000, 52.570000, 50.910000, 52.000000, 3863300, 'USD', 'twelvedata', '2025-12-28 09:43:19', '2025-12-28 09:43:19', '2025-12-28 09:43:19'),
(6, 1, 2, '2025-12-26', 215.429990, 216.830000, 213.029999, 214.990010, 15737700, 'USD', 'twelvedata', '2025-12-28 09:43:27', '2025-12-28 09:43:27', '2025-12-28 09:43:27'),
(7, 1, 2, '2025-12-24', 214.980000, 216.539990, 213.970000, 215.039993, 7956800, 'USD', 'twelvedata', '2025-12-28 09:43:27', '2025-12-28 09:43:27', '2025-12-28 09:43:27'),
(8, 1, 2, '2025-12-23', 212.860000, 217.029999, 212.280000, 214.899990, 20272300, 'USD', 'twelvedata', '2025-12-28 09:43:27', '2025-12-28 09:43:27', '2025-12-28 09:43:27'),
(9, 1, 2, '2025-12-22', 220.000000, 220.170000, 213.310000, 214.950000, 24950700, 'USD', 'twelvedata', '2025-12-28 09:43:27', '2025-12-28 09:43:27', '2025-12-28 09:43:27'),
(10, 1, 2, '2025-12-19', 204.600010, 215.179990, 204.200000, 213.429990, 58445500, 'USD', 'twelvedata', '2025-12-28 09:43:27', '2025-12-28 09:43:27', '2025-12-28 09:43:27'),
(11, 1, 3, '2025-12-26', 311.609990, 311.609990, 302.250000, 303.560000, 998600, 'USD', 'twelvedata', '2025-12-28 09:43:35', '2025-12-28 09:43:35', '2025-12-28 09:43:35'),
(12, 1, 3, '2025-12-24', 304.010010, 312.609990, 302.149990, 308.590000, 649000, 'USD', 'twelvedata', '2025-12-28 09:43:35', '2025-12-28 09:43:35', '2025-12-28 09:43:35'),
(13, 1, 3, '2025-12-23', 302.299990, 309.739990, 301.700010, 303.459990, 1200000, 'USD', 'twelvedata', '2025-12-28 09:43:36', '2025-12-28 09:43:36', '2025-12-28 09:43:36'),
(14, 1, 3, '2025-12-22', 301.620000, 306.859990, 293.600010, 306.859990, 1961000, 'USD', 'twelvedata', '2025-12-28 09:43:36', '2025-12-28 09:43:36', '2025-12-28 09:43:36'),
(15, 1, 3, '2025-12-19', 275.350010, 293.870000, 274.450010, 292.290010, 3770400, 'USD', 'twelvedata', '2025-12-28 09:43:36', '2025-12-28 09:43:36', '2025-12-28 09:43:36'),
(16, 1, 4, '2025-12-26', 276.560000, 277.640010, 276.000000, 276.730010, 541800, 'USD', 'twelvedata', '2025-12-28 09:43:44', '2025-12-28 09:43:44', '2025-12-28 09:43:44'),
(17, 1, 4, '2025-12-24', 274.989990, 277.000000, 274.989990, 276.380000, 580000, 'USD', 'twelvedata', '2025-12-28 09:43:44', '2025-12-28 09:43:44', '2025-12-28 09:43:44'),
(18, 1, 4, '2025-12-23', 272.530000, 275.810000, 271.010010, 275.530000, 766100, 'USD', 'twelvedata', '2025-12-28 09:43:44', '2025-12-28 09:43:44', '2025-12-28 09:43:44'),
(19, 1, 4, '2025-12-22', 268.500000, 273.280000, 267.950010, 273.200010, 1240300, 'USD', 'twelvedata', '2025-12-28 09:43:44', '2025-12-28 09:43:44', '2025-12-28 09:43:44'),
(20, 1, 4, '2025-12-19', 265.359990, 270.440000, 265.170010, 269.089996, 4349900, 'USD', 'twelvedata', '2025-12-28 09:43:44', '2025-12-28 09:43:44', '2025-12-28 09:43:44'),
(21, 1, 5, '2025-12-26', 152.220000, 152.300000, 144.660000, 144.830000, 2722900, 'USD', 'twelvedata', '2025-12-28 09:43:52', '2025-12-28 09:43:52', '2025-12-28 09:43:52'),
(22, 1, 5, '2025-12-24', 147.000000, 151.899990, 146.909000, 150.190000, 1956400, 'USD', 'twelvedata', '2025-12-28 09:43:52', '2025-12-28 09:43:52', '2025-12-28 09:43:52'),
(23, 1, 5, '2025-12-23', 146.690000, 150.190000, 143.120000, 147.810000, 3091900, 'USD', 'twelvedata', '2025-12-28 09:43:52', '2025-12-28 09:43:52', '2025-12-28 09:43:52'),
(24, 1, 5, '2025-12-22', 155.720000, 156.089996, 148.340000, 149.940000, 5094100, 'USD', 'twelvedata', '2025-12-28 09:43:52', '2025-12-28 09:43:52', '2025-12-28 09:43:52'),
(25, 1, 5, '2025-12-19', 142.960010, 151.420000, 141.089996, 150.130000, 9664800, 'USD', 'twelvedata', '2025-12-28 09:43:52', '2025-12-28 09:43:52', '2025-12-28 09:43:52'),
(26, 1, 6, '2025-12-26', 477.000000, 482.155000, 475.149990, 481.190000, 1139800, 'USD', 'twelvedata', '2025-12-28 09:44:00', '2025-12-28 09:44:00', '2025-12-28 09:44:00'),
(27, 1, 6, '2025-12-24', 476.829990, 478.000000, 470.674010, 477.109990, 745100, 'USD', 'twelvedata', '2025-12-28 09:44:00', '2025-12-28 09:44:00', '2025-12-28 09:44:00'),
(28, 1, 6, '2025-12-23', 482.489990, 483.590000, 473.500000, 478.840000, 2053000, 'USD', 'twelvedata', '2025-12-28 09:44:00', '2025-12-28 09:44:00', '2025-12-28 09:44:00'),
(29, 1, 6, '2025-12-22', 479.780000, 485.870000, 474.850010, 483.140010, 2303300, 'USD', 'twelvedata', '2025-12-28 09:44:00', '2025-12-28 09:44:00', '2025-12-28 09:44:00'),
(30, 1, 6, '2025-12-19', 480.000000, 489.204990, 478.630000, 481.280000, 4662700, 'USD', 'twelvedata', '2025-12-28 09:44:00', '2025-12-28 09:44:00', '2025-12-28 09:44:00'),
(31, 1, 7, '2025-12-26', 46.560000, 46.940000, 45.960000, 46.240000, 1537800, 'USD', 'twelvedata', '2025-12-28 09:44:09', '2025-12-28 09:44:09', '2025-12-28 09:44:09'),
(32, 1, 7, '2025-12-24', 46.800000, 47.000000, 46.320000, 46.510000, 1079600, 'USD', 'twelvedata', '2025-12-28 09:44:09', '2025-12-28 09:44:09', '2025-12-28 09:44:09'),
(33, 1, 7, '2025-12-23', 46.760000, 47.240000, 45.960000, 46.610000, 3055600, 'USD', 'twelvedata', '2025-12-28 09:44:09', '2025-12-28 09:44:09', '2025-12-28 09:44:09'),
(34, 1, 7, '2025-12-22', 44.250000, 46.250000, 44.099998, 46.190000, 3017800, 'USD', 'twelvedata', '2025-12-28 09:44:09', '2025-12-28 09:44:09', '2025-12-28 09:44:09'),
(35, 1, 7, '2025-12-19', 42.760000, 44.550000, 42.670000, 44.300000, 5242400, 'USD', 'twelvedata', '2025-12-28 09:44:09', '2025-12-28 09:44:09', '2025-12-28 09:44:09'),
(36, 1, 8, '2025-12-26', 344.870000, 346.070007, 341.429990, 342.200010, 464500, 'USD', 'twelvedata', '2025-12-28 09:44:17', '2025-12-28 09:44:17', '2025-12-28 09:44:17'),
(37, 1, 8, '2025-12-24', 343.530000, 346.269990, 343.510010, 345.390010, 314000, 'USD', 'twelvedata', '2025-12-28 09:44:17', '2025-12-28 09:44:17', '2025-12-28 09:44:17'),
(38, 1, 8, '2025-12-23', 345.670010, 347.440000, 343.179990, 343.840000, 1246800, 'USD', 'twelvedata', '2025-12-28 09:44:17', '2025-12-28 09:44:17', '2025-12-28 09:44:17'),
(39, 1, 8, '2025-12-22', 338.870000, 345.670010, 337.440000, 345.190000, 1197900, 'USD', 'twelvedata', '2025-12-28 09:44:17', '2025-12-28 09:44:17', '2025-12-28 09:44:17'),
(40, 1, 8, '2025-12-19', 337.870000, 341.420010, 337.000000, 339.359990, 2663400, 'USD', 'twelvedata', '2025-12-28 09:44:17', '2025-12-28 09:44:17', '2025-12-28 09:44:17'),
(41, 1, 9, '2025-12-26', 297.810000, 298.970000, 295.320010, 296.769990, 466300, 'USD', 'twelvedata', '2025-12-28 09:44:25', '2025-12-28 09:44:25', '2025-12-28 09:44:25'),
(42, 1, 9, '2025-12-24', 297.820010, 299.829990, 297.820010, 298.140010, 363300, 'USD', 'twelvedata', '2025-12-28 09:44:25', '2025-12-28 09:44:25', '2025-12-28 09:44:25'),
(43, 1, 9, '2025-12-23', 295.480010, 298.780000, 294.440000, 297.829990, 1051500, 'USD', 'twelvedata', '2025-12-28 09:44:25', '2025-12-28 09:44:25', '2025-12-28 09:44:25'),
(44, 1, 9, '2025-12-22', 288.149990, 295.209990, 287.450010, 295.100010, 919100, 'USD', 'twelvedata', '2025-12-28 09:44:25', '2025-12-28 09:44:25', '2025-12-28 09:44:25'),
(45, 1, 9, '2025-12-19', 283.799990, 289.709990, 283.549990, 287.450010, 1487700, 'USD', 'twelvedata', '2025-12-28 09:44:25', '2025-12-28 09:44:25', '2025-12-28 09:44:25'),
(46, 1, 10, '2025-12-26', 65.840000, 66.460000, 65.400000, 66.210000, 483900, 'USD', 'twelvedata', '2025-12-28 09:44:34', '2025-12-28 09:44:34', '2025-12-28 09:44:34'),
(47, 1, 10, '2025-12-24', 65.220000, 66.260000, 65.130000, 65.950000, 402400, 'USD', 'twelvedata', '2025-12-28 09:44:34', '2025-12-28 09:44:34', '2025-12-28 09:44:34'),
(48, 1, 10, '2025-12-23', 66.490000, 66.780000, 65.000000, 65.450000, 1748700, 'USD', 'twelvedata', '2025-12-28 09:44:34', '2025-12-28 09:44:34', '2025-12-28 09:44:34'),
(49, 1, 10, '2025-12-22', 65.840000, 66.410000, 65.240000, 66.200000, 702900, 'USD', 'twelvedata', '2025-12-28 09:44:34', '2025-12-28 09:44:34', '2025-12-28 09:44:34'),
(50, 1, 10, '2025-12-19', 65.650000, 67.810000, 65.490000, 66.080002, 2348100, 'USD', 'twelvedata', '2025-12-28 09:44:34', '2025-12-28 09:44:34', '2025-12-28 09:44:34'),
(51, 1, 11, '2025-12-26', 160.320010, 162.179990, 160.000000, 162.080002, 1245700, 'USD', 'twelvedata', '2025-12-28 09:44:42', '2025-12-28 09:44:42', '2025-12-28 09:44:42'),
(52, 1, 11, '2025-12-24', 160.110000, 160.679990, 159.070007, 160.340000, 854400, 'USD', 'twelvedata', '2025-12-28 09:44:42', '2025-12-28 09:44:42', '2025-12-28 09:44:42'),
(53, 1, 11, '2025-12-23', 159.980000, 160.490010, 158.450000, 160.149990, 1828900, 'USD', 'twelvedata', '2025-12-28 09:44:42', '2025-12-28 09:44:42', '2025-12-28 09:44:42'),
(54, 1, 11, '2025-12-22', 162.179990, 162.640000, 159.070007, 160.000000, 2334600, 'USD', 'twelvedata', '2025-12-28 09:44:42', '2025-12-28 09:44:42', '2025-12-28 09:44:42'),
(55, 1, 11, '2025-12-19', 161.679990, 163.940000, 161.679990, 161.960010, 3715700, 'USD', 'twelvedata', '2025-12-28 09:44:42', '2025-12-28 09:44:42', '2025-12-28 09:44:42'),
(56, 1, 12, '2025-12-26', 106.450000, 107.050003, 106.029999, 106.780000, 6265800, 'USD', 'twelvedata', '2025-12-28 09:44:50', '2025-12-28 09:44:50', '2025-12-28 09:44:50'),
(57, 1, 12, '2025-12-24', 105.370000, 106.950000, 105.280000, 106.450000, 5335000, 'USD', 'twelvedata', '2025-12-28 09:44:50', '2025-12-28 09:44:50', '2025-12-28 09:44:50'),
(58, 1, 12, '2025-12-23', 104.560000, 105.390000, 104.330000, 105.040001, 13587500, 'USD', 'twelvedata', '2025-12-28 09:44:50', '2025-12-28 09:44:50', '2025-12-28 09:44:50'),
(59, 1, 12, '2025-12-22', 100.630000, 104.950000, 100.400000, 104.720000, 17136200, 'USD', 'twelvedata', '2025-12-28 09:44:50', '2025-12-28 09:44:50', '2025-12-28 09:44:50'),
(60, 1, 12, '2025-12-19', 100.600000, 102.200000, 100.110000, 101.089996, 44980200, 'USD', 'twelvedata', '2025-12-28 09:44:50', '2025-12-28 09:44:50', '2025-12-28 09:44:50'),
(61, 1, 13, '2025-12-26', 290.840000, 290.870000, 283.420010, 284.790010, 17793800, 'USD', 'twelvedata', '2025-12-28 09:44:58', '2025-12-28 09:44:58', '2025-12-28 09:44:58'),
(62, 1, 13, '2025-12-24', 278.000000, 289.299990, 277.250000, 286.679990, 18592600, 'USD', 'twelvedata', '2025-12-28 09:44:58', '2025-12-28 09:44:58', '2025-12-28 09:44:58'),
(63, 1, 13, '2025-12-23', 275.920010, 281.859990, 272.320010, 276.269990, 20767600, 'USD', 'twelvedata', '2025-12-28 09:44:58', '2025-12-28 09:44:58', '2025-12-28 09:44:58'),
(64, 1, 13, '2025-12-22', 277.149990, 279.989990, 268.290010, 276.590000, 30961900, 'USD', 'twelvedata', '2025-12-28 09:44:58', '2025-12-28 09:44:58', '2025-12-28 09:44:58'),
(65, 1, 13, '2025-12-19', 251.750000, 268.380000, 251.750000, 265.920010, 62312100, 'USD', 'twelvedata', '2025-12-28 09:44:58', '2025-12-28 09:44:58', '2025-12-28 09:44:58'),
(66, 1, 14, '2025-12-26', 189.920000, 192.690000, 188.000000, 190.530000, 139393400, 'USD', 'twelvedata', '2025-12-28 09:45:07', '2025-12-28 09:45:07', '2025-12-28 09:45:07'),
(67, 1, 14, '2025-12-24', 187.940000, 188.910000, 186.590000, 188.610000, 65528500, 'USD', 'twelvedata', '2025-12-28 09:45:07', '2025-12-28 09:45:07', '2025-12-28 09:45:07'),
(68, 1, 14, '2025-12-23', 182.970000, 189.330000, 182.899990, 189.210010, 174873600, 'USD', 'twelvedata', '2025-12-28 09:45:07', '2025-12-28 09:45:07', '2025-12-28 09:45:07'),
(69, 1, 14, '2025-12-22', 183.920000, 184.160000, 182.350010, 183.690000, 129064400, 'USD', 'twelvedata', '2025-12-28 09:45:07', '2025-12-28 09:45:07', '2025-12-28 09:45:07'),
(70, 1, 14, '2025-12-19', 176.670000, 181.450000, 176.340000, 180.990010, 324925900, 'USD', 'twelvedata', '2025-12-28 09:45:07', '2025-12-28 09:45:07', '2025-12-28 09:45:07'),
(71, 1, 15, '2025-12-26', 80.250000, 80.560000, 79.862000, 80.460000, 502400, 'USD', 'twelvedata', '2025-12-28 09:45:15', '2025-12-28 09:45:15', '2025-12-28 09:45:15'),
(72, 1, 15, '2025-12-24', 80.460000, 80.700000, 80.010002, 80.180000, 398100, 'USD', 'twelvedata', '2025-12-28 09:45:15', '2025-12-28 09:45:15', '2025-12-28 09:45:15'),
(73, 1, 15, '2025-12-23', 80.860000, 80.990000, 80.255000, 80.550000, 752800, 'USD', 'twelvedata', '2025-12-28 09:45:15', '2025-12-28 09:45:15', '2025-12-28 09:45:15'),
(74, 1, 15, '2025-12-22', 81.280000, 81.586000, 80.540000, 80.780000, 801500, 'USD', 'twelvedata', '2025-12-28 09:45:15', '2025-12-28 09:45:15', '2025-12-28 09:45:15'),
(75, 1, 15, '2025-12-19', 80.800000, 81.680000, 80.540000, 81.590000, 3276500, 'USD', 'twelvedata', '2025-12-28 09:45:15', '2025-12-28 09:45:15', '2025-12-28 09:45:15'),
(76, 1, 16, '2025-12-26', 48.000000, 48.870000, 47.120000, 47.470000, 4059800, 'USD', 'twelvedata', '2025-12-28 09:45:24', '2025-12-28 09:45:24', '2025-12-28 09:45:24'),
(77, 1, 16, '2025-12-24', 48.440000, 48.945000, 47.600000, 48.220000, 3063400, 'USD', 'twelvedata', '2025-12-28 09:45:24', '2025-12-28 09:45:24', '2025-12-28 09:45:24'),
(78, 1, 16, '2025-12-23', 48.870000, 49.760000, 47.700000, 48.905000, 5737000, 'USD', 'twelvedata', '2025-12-28 09:45:24', '2025-12-28 09:45:24', '2025-12-28 09:45:24'),
(79, 1, 16, '2025-12-22', 47.900000, 49.300000, 47.590000, 48.770000, 5673800, 'USD', 'twelvedata', '2025-12-28 09:45:24', '2025-12-28 09:45:24', '2025-12-28 09:45:24'),
(80, 1, 16, '2025-12-19', 47.860000, 48.920000, 47.029999, 47.590000, 4279400, 'USD', 'twelvedata', '2025-12-28 09:45:24', '2025-12-28 09:45:24', '2025-12-28 09:45:24');

-- --------------------------------------------------------

--
-- Table structure for table `instrument_price_latest`
--

DROP TABLE IF EXISTS `instrument_price_latest`;
CREATE TABLE IF NOT EXISTS `instrument_price_latest` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `instrument_id` bigint UNSIGNED NOT NULL,
  `price` decimal(18,6) NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `source` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'twelvedata',
  `fetched_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_latest_user_instr` (`user_id`,`instrument_id`),
  KEY `idx_latest_user` (`user_id`),
  KEY `fk_latest_instr` (`instrument_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trade`
--

DROP TABLE IF EXISTS `trade`;
CREATE TABLE IF NOT EXISTS `trade` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `broker_account_id` bigint UNSIGNED DEFAULT NULL,
  `instrument_id` bigint UNSIGNED NOT NULL,
  `trade_type` enum('BUY','SELL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `trade_date` date NOT NULL,
  `quantity` decimal(18,6) NOT NULL,
  `price_per_unit` decimal(18,8) NOT NULL,
  `price_eur` decimal(18,8) NOT NULL,
  `trade_currency` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fee_amount` decimal(18,8) DEFAULT NULL,
  `fee_currency` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fx_rate_to_eur` decimal(18,8) NOT NULL,
  `total_value_eur` decimal(18,2) NOT NULL,
  `fee_eur` decimal(18,2) NOT NULL DEFAULT '0.00',
  `notes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_voided` tinyint(1) NOT NULL DEFAULT '0',
  `void_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_trade_user_date` (`user_id`,`trade_date`),
  KEY `idx_trade_user_instr_date` (`user_id`,`instrument_id`,`trade_date`),
  KEY `fk_trade_broker` (`broker_account_id`),
  KEY `fk_trade_instr` (`instrument_id`),
  KEY `fk_trade_created_by` (`created_by`),
  KEY `fk_trade_updated_by` (`updated_by`)
) ENGINE=MyISAM AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `trade`
--

INSERT INTO `trade` (`id`, `user_id`, `broker_account_id`, `instrument_id`, `trade_type`, `trade_date`, `quantity`, `price_per_unit`, `price_eur`, `trade_currency`, `fee_amount`, `fee_currency`, `fx_rate_to_eur`, `total_value_eur`, `fee_eur`, `notes`, `is_voided`, `void_reason`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(1, 1, 1, 17, 'BUY', '2017-03-14', 11.000000, 139.32000000, 130.00000000, 'USD', NULL, NULL, 0.93848470, 1438.25, 0.00, '', 0, NULL, '2025-12-25 19:33:56', '2025-12-27 08:15:23', NULL, NULL),
(2, 1, 1, 4, 'BUY', '2017-04-26', 14.000000, 120.30000000, 110.00000000, 'USD', NULL, NULL, 0.91802078, 1546.10, 0.00, '', 0, NULL, '2025-12-25 19:38:09', '2025-12-27 08:15:23', NULL, NULL),
(3, 1, 1, 14, 'BUY', '2017-06-09', 13.000000, 150.00000000, 134.00000000, 'USD', NULL, NULL, 0.89477460, 1744.81, 0.00, '', 0, NULL, '2025-12-25 19:43:29', '2025-12-27 08:15:23', NULL, NULL),
(4, 1, 1, 11, 'BUY', '2017-09-25', 10.000000, 209.87000000, 176.85180000, 'USD', NULL, NULL, 0.84267300, 1768.52, 0.00, '', 0, NULL, '2025-12-25 19:58:11', '2025-12-27 08:15:23', NULL, NULL),
(5, 1, 1, 18, 'BUY', '2018-08-17', 11.000000, 77.45360000, 88.22750000, 'USD', NULL, NULL, 0.87788600, 970.50, 0.00, '', 0, NULL, '2025-12-25 20:03:10', '2025-12-27 08:15:23', NULL, NULL),
(6, 1, 1, 1, 'BUY', '2018-08-17', 20.000000, 65.00000000, 57.06260000, 'USD', NULL, NULL, 0.87788600, 1141.25, 0.00, '', 0, NULL, '2025-12-25 20:06:03', '2025-12-27 08:15:23', NULL, NULL),
(8, 1, 1, 17, 'SELL', '2018-08-17', 11.000000, 219.81359000, 188.34170000, 'USD', NULL, NULL, 0.85682400, 2071.76, 0.00, '', 0, NULL, '2025-12-25 20:12:12', '2025-12-27 08:15:23', NULL, NULL),
(9, 1, 1, 19, 'BUY', '2020-05-13', 30.000000, 64.11000000, 51.79770000, 'USD', NULL, NULL, 0.80788400, 1553.80, 0.00, '', 0, NULL, '2025-12-25 20:28:33', '2025-12-27 08:15:23', NULL, NULL),
(10, 1, 1, 6, 'BUY', '2020-07-06', 15.000000, 108.00000000, 95.36420000, 'USD', NULL, NULL, 0.88300180, 1430.46, 0.00, '', 0, NULL, '2025-12-25 20:33:00', '2025-12-27 08:15:23', NULL, NULL),
(11, 1, 1, 7, 'BUY', '2020-09-17', 65.000000, 26.55000000, 22.50570000, 'USD', NULL, NULL, 0.84767230, 1462.87, 0.00, '', 0, NULL, '2025-12-25 20:36:29', '2025-12-27 08:15:23', NULL, NULL),
(12, 1, 1, 13, 'BUY', '2020-12-16', 26.000000, 73.00000000, 59.89010000, 'USD', NULL, NULL, 0.82041230, 1557.14, 0.00, '', 0, NULL, '2025-12-25 20:42:28', '2025-12-27 08:15:23', NULL, NULL),
(13, 1, 1, 20, 'BUY', '2021-04-19', 70.000000, 32.88230000, 30.27840000, 'USD', NULL, NULL, 0.91990000, 2117.39, 0.00, '', 0, NULL, '2025-12-25 20:55:37', '2025-12-27 08:15:23', NULL, NULL),
(14, 1, 1, 18, 'SELL', '2021-04-14', 11.000000, 62.47100000, 56.98350000, 'USD', NULL, NULL, 0.91215900, 626.82, 0.00, '', 0, NULL, '2025-12-25 20:58:10', '2025-12-27 08:15:23', NULL, NULL),
(15, 1, 1, 14, 'SELL', '2021-04-14', 3.000000, 569.10654000, 519.11570000, 'USD', NULL, NULL, 0.91215900, 1557.35, 0.00, '', 0, NULL, '2025-12-25 21:00:24', '2025-12-27 08:15:23', NULL, NULL),
(16, 1, 1, 21, 'BUY', '2021-11-05', 10.000000, 0.00000000, 0.00000000, 'USD', NULL, NULL, 1.00000000, 0.10, 0.00, '', 0, NULL, '2025-12-25 21:08:12', '2025-12-27 08:15:23', NULL, NULL),
(17, 1, 1, 16, 'BUY', '2021-12-14', 12.000000, 143.73000000, 0.00000000, 'USD', NULL, NULL, 0.88425100, 1525.12, 0.00, '', 0, NULL, '2025-12-25 21:17:30', '2025-12-27 08:15:23', NULL, NULL),
(18, 1, 1, 20, 'SELL', '2023-01-05', 70.000000, 51.00000000, 0.00000000, 'USD', NULL, NULL, 0.94330720, 3367.61, 0.00, '', 0, NULL, '2025-12-25 21:25:11', '2025-12-27 08:15:23', NULL, NULL),
(19, 1, 1, 15, 'BUY', '2024-04-04', 2.000000, 0.00000000, 0.00000000, 'USD', NULL, NULL, 1.00000000, 0.00, 0.00, '', 0, NULL, '2025-12-26 11:33:11', '2025-12-27 08:15:23', NULL, NULL),
(20, 1, 1, 14, 'SELL', '2025-01-10', 1.000000, 5436.40000000, 5275.99901800, 'USD', NULL, NULL, 0.97049500, 5276.00, 43.61, '', 0, NULL, '2025-12-26 13:46:05', '2025-12-27 08:15:23', NULL, NULL),
(21, 1, 1, 10, 'BUY', '2025-01-10', 16.000000, 107.00000000, 104.92253080, 'USD', NULL, NULL, 0.98058440, 1678.76, 32.85, '', 0, NULL, '2025-12-26 13:50:42', '2025-12-27 08:15:23', NULL, NULL),
(22, 1, 1, 5, 'BUY', '2025-01-10', 23.000000, 68.50000000, 67.17003140, 'USD', NULL, NULL, 0.98058440, 1544.91, 32.85, '', 0, NULL, '2025-12-26 13:51:37', '2025-12-27 08:15:23', NULL, NULL),
(23, 1, 1, 3, 'BUY', '2025-01-10', 17.000000, 97.08000000, 95.19513355, 'USD', NULL, NULL, 0.98058440, 1618.32, 32.85, '', 0, NULL, '2025-12-26 13:53:36', '2025-12-27 08:15:23', NULL, NULL),
(24, 1, 1, 14, 'SELL', '2025-11-17', 25.000000, 188.50000000, 162.59802650, 'USD', NULL, NULL, 0.86258900, 4064.95, 36.95, '', 0, NULL, '2025-12-26 14:35:12', '2025-12-27 08:15:23', NULL, NULL),
(25, 1, 1, 2, 'BUY', '2025-11-17', 8.000000, 245.41000000, 211.68796649, 'USD', NULL, NULL, 0.86258900, 1693.50, 27.14, '', 0, NULL, '2025-12-27 08:11:43', '2025-12-27 08:16:29', NULL, NULL),
(26, 1, 1, 9, 'BUY', '2025-11-17', 10.000000, 288.00000000, 248.42563200, 'USD', NULL, NULL, 0.86258900, 2484.26, 30.52, '', 0, NULL, '2025-12-27 08:16:23', '2025-12-27 10:12:48', NULL, NULL),
(27, 1, 1, 8, 'BUY', '2025-11-24', 7.000000, 335.50000000, 290.62716353, 'USD', NULL, NULL, 0.86625086, 2034.39, 29.19, '', 0, NULL, '2025-12-27 08:20:13', '2025-12-27 08:21:12', NULL, NULL),
(28, 1, 1, 12, 'BUY', '2025-11-24', 24.000000, 100.94000000, 87.43936180, 'USD', NULL, NULL, 0.86625086, 2098.54, 29.54, '', 0, NULL, '2025-12-27 08:21:04', '2025-12-27 10:27:20', NULL, NULL),
(29, 1, 1, 19, 'SELL', '2025-04-04', 10.000000, 0.07700000, 0.07025739, 'USD', NULL, NULL, 0.91243373, 0.70, 0.00, '', 0, NULL, '2025-12-27 16:03:24', '2025-12-27 16:03:24', NULL, NULL),
(30, 1, 1, 19, 'SELL', '2025-07-21', 20.000000, 0.15000000, 0.12725884, 'USD', NULL, NULL, 0.84839229, 2.55, 0.00, '', 0, NULL, '2025-12-27 16:05:22', '2025-12-27 16:18:09', NULL, NULL),
(31, 1, 1, 21, 'SELL', '2025-05-14', 10.000000, 5.00000000, 4.24196145, 'USD', NULL, NULL, 0.84839229, 42.42, 0.00, '', 0, NULL, '2025-12-28 09:40:22', '2025-12-28 09:40:22', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `trade_lot`
--

DROP TABLE IF EXISTS `trade_lot`;
CREATE TABLE IF NOT EXISTS `trade_lot` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `buy_trade_id` bigint UNSIGNED NOT NULL,
  `instrument_id` bigint UNSIGNED NOT NULL,
  `opened_date` date NOT NULL,
  `quantity_opened` decimal(18,6) NOT NULL,
  `quantity_remaining` decimal(18,6) NOT NULL,
  `cost_basis_eur` decimal(18,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_lot_user_instr_date` (`user_id`,`instrument_id`,`opened_date`),
  KEY `fk_lot_buy_trade` (`buy_trade_id`),
  KEY `fk_lot_instr` (`instrument_id`),
  KEY `fk_lot_created_by` (`created_by`),
  KEY `fk_lot_updated_by` (`updated_by`)
) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `trade_lot`
--

INSERT INTO `trade_lot` (`id`, `user_id`, `buy_trade_id`, `instrument_id`, `opened_date`, `quantity_opened`, `quantity_remaining`, `cost_basis_eur`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(1, 1, 1, 17, '2017-03-14', 11.000000, 0.000000, 1438.25, '2025-12-25 19:33:56', '2025-12-25 20:15:51', NULL, NULL),
(2, 1, 2, 4, '2017-04-26', 14.000000, 14.000000, 1546.10, '2025-12-25 19:38:09', '2025-12-25 20:16:00', NULL, NULL),
(3, 1, 3, 14, '2017-06-09', 520.000000, 335.000000, 1744.81, '2025-12-25 19:43:29', '2025-12-26 14:35:12', NULL, NULL),
(4, 1, 4, 11, '2017-09-25', 10.000000, 10.000000, 1768.52, '2025-12-25 19:58:11', '2025-12-25 19:58:11', NULL, NULL),
(5, 1, 5, 18, '2018-08-17', 11.000000, 0.000000, 970.50, '2025-12-25 20:03:10', '2025-12-25 20:58:10', NULL, NULL),
(7, 1, 6, 1, '2018-08-17', 20.000000, 20.000000, 1141.25, '2025-12-25 20:18:47', '2025-12-25 20:19:12', NULL, NULL),
(8, 1, 9, 19, '2020-05-13', 30.000000, 0.000000, 1553.80, '2025-12-25 20:28:33', '2025-12-27 16:18:09', NULL, NULL),
(9, 1, 10, 6, '2020-07-06', 15.000000, 15.000000, 1430.46, '2025-12-25 20:33:00', '2025-12-25 20:33:00', NULL, NULL),
(10, 1, 11, 7, '2020-09-17', 65.000000, 65.000000, 1462.87, '2025-12-25 20:36:29', '2025-12-25 20:36:29', NULL, NULL),
(11, 1, 12, 13, '2020-12-16', 26.000000, 26.000000, 1557.14, '2025-12-25 20:42:28', '2025-12-25 20:42:28', NULL, NULL),
(12, 1, 13, 20, '2021-04-19', 70.000000, 0.000000, 2117.39, '2025-12-25 20:55:37', '2025-12-25 21:25:11', NULL, NULL),
(13, 1, 16, 21, '2021-11-05', 10.000000, 0.000000, 0.00, '2025-12-25 21:08:12', '2025-12-28 09:40:22', NULL, NULL),
(14, 1, 17, 16, '2021-12-14', 12.000000, 12.000000, 1525.12, '2025-12-25 21:17:30', '2025-12-25 21:17:30', NULL, NULL),
(15, 1, 19, 15, '2024-04-04', 2.000000, 2.000000, 0.00, '2025-12-26 11:33:11', '2025-12-26 11:33:11', NULL, NULL),
(16, 1, 21, 10, '2025-01-10', 16.000000, 16.000000, 1711.61, '2025-12-26 13:50:42', '2025-12-26 14:01:48', NULL, NULL),
(17, 1, 22, 5, '2025-01-10', 23.000000, 23.000000, 1577.76, '2025-12-26 13:51:37', '2025-12-26 14:01:34', NULL, NULL),
(18, 1, 23, 3, '2025-01-10', 17.000000, 17.000000, 1651.17, '2025-12-26 13:53:36', '2025-12-26 14:01:23', NULL, NULL),
(19, 1, 25, 2, '2025-11-17', 8.000000, 8.000000, 1720.64, '2025-12-27 08:11:43', '2025-12-27 08:12:16', NULL, NULL),
(20, 1, 26, 9, '2025-11-17', 10.000000, 10.000000, 2514.78, '2025-12-27 08:16:23', '2025-12-27 08:17:49', NULL, NULL),
(21, 1, 27, 8, '2025-11-24', 7.000000, 7.000000, 2063.58, '2025-12-27 08:20:13', '2025-12-27 08:21:12', NULL, NULL),
(22, 1, 28, 12, '2025-11-24', 24.000000, 24.000000, 2128.08, '2025-12-27 08:21:04', '2025-12-27 10:27:20', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `trade_lot_allocation`
--

DROP TABLE IF EXISTS `trade_lot_allocation`;
CREATE TABLE IF NOT EXISTS `trade_lot_allocation` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `sell_trade_id` bigint UNSIGNED NOT NULL,
  `trade_lot_id` bigint UNSIGNED NOT NULL,
  `quantity_consumed` decimal(18,6) NOT NULL,
  `proceeds_eur` decimal(18,2) NOT NULL,
  `cost_basis_eur` decimal(18,2) NOT NULL,
  `realized_pnl_eur` decimal(18,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_alloc_user_sell` (`user_id`,`sell_trade_id`),
  KEY `idx_alloc_user_lot` (`user_id`,`trade_lot_id`),
  KEY `fk_alloc_sell_trade` (`sell_trade_id`),
  KEY `fk_alloc_lot` (`trade_lot_id`),
  KEY `fk_alloc_created_by` (`created_by`),
  KEY `fk_alloc_updated_by` (`updated_by`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `trade_lot_allocation`
--

INSERT INTO `trade_lot_allocation` (`id`, `user_id`, `sell_trade_id`, `trade_lot_id`, `quantity_consumed`, `proceeds_eur`, `cost_basis_eur`, `realized_pnl_eur`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(2, 1, 8, 1, 11.000000, 2071.76, 1632.90, 438.86, '2025-12-25 20:12:12', '2025-12-25 20:12:12', NULL, NULL),
(3, 1, 14, 5, 11.000000, 626.82, 970.50, -343.68, '2025-12-25 20:58:10', '2025-12-25 20:58:10', NULL, NULL),
(4, 1, 15, 3, 3.000000, 1557.35, 402.65, 1154.70, '2025-12-25 21:00:24', '2025-12-25 21:05:20', NULL, NULL),
(5, 1, 18, 12, 70.000000, 3367.61, 2117.39, 1250.22, '2025-12-25 21:25:11', '2025-12-25 21:25:11', NULL, NULL),
(6, 1, 20, 3, 1.000000, 5232.39, 134.22, 5098.17, '2025-12-26 13:46:05', '2025-12-26 13:46:05', NULL, NULL),
(7, 1, 24, 3, 25.000000, 4028.00, 83.89, 3944.11, '2025-12-26 14:35:12', '2025-12-26 14:35:12', NULL, NULL),
(8, 1, 29, 8, 10.000000, 0.70, 517.93, -517.23, '2025-12-27 16:03:24', '2025-12-27 16:03:24', NULL, NULL),
(10, 1, 30, 8, 20.000000, 2.55, 1035.87, -1033.32, '2025-12-27 16:18:09', '2025-12-27 16:18:09', NULL, NULL),
(11, 1, 31, 13, 10.000000, 42.42, 0.00, 42.42, '2025-12-28 09:40:22', '2025-12-28 09:40:22', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `email`, `password_hash`, `first_name`, `last_name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'eriksmi@gmail.com', '$2y$10$klddWVsftjpLU/6AXjSy0.6CouZNfLAprA3jKYq/1l1yAwaH3ImBS', NULL, NULL, 1, '2025-12-25 09:49:08', '2025-12-25 09:49:08');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `app_settings`
--
ALTER TABLE `app_settings`
  ADD CONSTRAINT `fk_settings_created_by` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `fk_settings_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `fk_settings_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Constraints for table `broker_account`
--
ALTER TABLE `broker_account`
  ADD CONSTRAINT `fk_broker_created_by` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `fk_broker_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `fk_broker_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Constraints for table `dividend_payer`
--
ALTER TABLE `dividend_payer`
  ADD CONSTRAINT `fk_payer_created_by` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `fk_payer_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `fk_payer_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Constraints for table `instrument`
--
ALTER TABLE `instrument`
  ADD CONSTRAINT `fk_instrument_dividend_payer` FOREIGN KEY (`dividend_payer_id`) REFERENCES `dividend_payer` (`id`);

--
-- Constraints for table `instrument_price_daily`
--
ALTER TABLE `instrument_price_daily`
  ADD CONSTRAINT `fk_price_instr` FOREIGN KEY (`instrument_id`) REFERENCES `instrument` (`id`),
  ADD CONSTRAINT `fk_price_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Constraints for table `instrument_price_latest`
--
ALTER TABLE `instrument_price_latest`
  ADD CONSTRAINT `fk_latest_instr` FOREIGN KEY (`instrument_id`) REFERENCES `instrument` (`id`),
  ADD CONSTRAINT `fk_latest_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
