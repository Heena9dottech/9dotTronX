-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 04, 2025 at 10:00 AM
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
-- Database: `1_tree_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `level_plans`
--

DROP TABLE IF EXISTS `level_plans`;
CREATE TABLE IF NOT EXISTS `level_plans` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `level_number` int NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `level_plans_level_number_unique` (`level_number`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `level_plans`
--

INSERT INTO `level_plans` (`id`, `name`, `price`, `level_number`, `description`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, '200 TRX', 200.00, 1, 'Entry level plan - 200 TRX', 1, 0, '2025-09-04 04:08:47', '2025-09-04 04:08:47'),
(2, '400 TRX', 400.00, 2, 'Level 2 plan - 400 TRX', 1, 0, '2025-09-04 04:08:47', '2025-09-04 04:08:47'),
(3, '800 TRX', 800.00, 3, 'Level 3 plan - 800 TRX', 1, 0, '2025-09-04 04:08:47', '2025-09-04 04:08:47'),
(4, '1600 TRX', 1600.00, 4, 'Level 4 plan - 1,600 TRX', 1, 0, '2025-09-04 04:08:47', '2025-09-04 04:08:47'),
(5, '3200 TRX', 3200.00, 5, 'Level 5 plan - 3,200 TRX', 1, 0, '2025-09-04 04:08:47', '2025-09-04 04:08:47'),
(6, '6400 TRX', 6400.00, 6, 'Level 6 plan - 6,400 TRX', 1, 0, '2025-09-04 04:08:47', '2025-09-04 04:08:47'),
(7, '12800 TRX', 12800.00, 7, 'Level 7 plan - 12,800 TRX', 1, 0, '2025-09-04 04:08:47', '2025-09-04 04:08:47'),
(8, '25000 TRX', 25000.00, 8, 'Level 8 plan - 25,000 TRX', 1, 0, '2025-09-04 04:08:47', '2025-09-04 04:08:47'),
(9, '50000 TRX', 50000.00, 9, 'Level 9 plan - 50,000 TRX', 1, 0, '2025-09-04 04:08:47', '2025-09-04 04:08:47'),
(10, '100000 TRX', 100000.00, 10, 'Level 10 plan - 100,000 TRX', 1, 0, '2025-09-04 04:08:47', '2025-09-04 04:08:47'),
(11, '200000 TRX', 200000.00, 11, 'Level 11 plan - 200,000 TRX', 1, 0, '2025-09-04 04:08:47', '2025-09-04 04:08:47'),
(12, '400000 TRX', 400000.00, 12, 'Level 12 plan - 400,000 TRX', 1, 0, '2025-09-04 04:08:47', '2025-09-04 04:08:47'),
(13, '800000 TRX', 800000.00, 13, 'Level 13 plan - 800,000 TRX', 1, 0, '2025-09-04 04:08:47', '2025-09-04 04:08:47'),
(14, '1500000 TRX', 1500000.00, 14, 'Level 14 plan - 1,500,000 TRX', 1, 0, '2025-09-04 04:08:47', '2025-09-04 04:08:47'),
(15, '2000000 TRX', 2000000.00, 15, 'Level 15 plan - 2,000,000 TRX', 1, 0, '2025-09-04 04:08:47', '2025-09-04 04:08:47');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_09_02_093348_create_level_plans_table', 1),
(5, '2025_09_02_094359_create_referral_relationships_with_levels_table', 1),
(6, '2025_09_02_122309_add_tree_round_count_to_users_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `referral_relationships`
--

DROP TABLE IF EXISTS `referral_relationships`;
CREATE TABLE IF NOT EXISTS `referral_relationships` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `user_username` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sponsor_id` bigint UNSIGNED DEFAULT NULL,
  `sponsor_username` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `upline_id` bigint UNSIGNED DEFAULT NULL,
  `upline_username` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` enum('L','R') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tree_owner_id` bigint UNSIGNED DEFAULT NULL,
  `tree_owner_username` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tree_round` int NOT NULL DEFAULT '1',
  `is_spillover_slot` tinyint(1) NOT NULL DEFAULT '0',
  `level_number` int NOT NULL DEFAULT '1',
  `slot_price` decimal(15,2) DEFAULT NULL,
  `level_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `referral_relationships_user_id_foreign` (`user_id`),
  KEY `referral_relationships_sponsor_id_foreign` (`sponsor_id`),
  KEY `referral_relationships_upline_id_foreign` (`upline_id`),
  KEY `referral_relationships_tree_owner_id_foreign` (`tree_owner_id`),
  KEY `referral_relationships_level_id_foreign` (`level_id`)
) ENGINE=MyISAM AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `referral_relationships`
--

INSERT INTO `referral_relationships` (`id`, `user_id`, `user_username`, `sponsor_id`, `sponsor_username`, `upline_id`, `upline_username`, `position`, `tree_owner_id`, `tree_owner_username`, `tree_round`, `is_spillover_slot`, `level_number`, `slot_price`, `level_id`, `created_at`, `updated_at`) VALUES
(1, 2, 'john', NULL, NULL, NULL, NULL, NULL, 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:08:53', '2025-09-04 04:08:53'),
(2, 3, 'mike', 2, 'john', 2, 'john', 'L', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:09:54', '2025-09-04 04:09:54'),
(3, 4, 'lisa', 2, 'john', 2, 'john', 'R', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:11:49', '2025-09-04 04:11:49'),
(4, 5, 'emma', 3, 'mike', 3, 'mike', 'L', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:12:06', '2025-09-04 04:12:06'),
(5, 6, 'david', 2, 'john', 3, 'mike', 'R', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:20:24', '2025-09-04 04:20:24'),
(6, 7, 'olivia', 3, 'mike', 5, 'emma', 'L', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:20:57', '2025-09-04 04:20:57'),
(7, 8, 'daniel', 3, 'mike', 5, 'emma', 'R', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:22:00', '2025-09-04 04:22:00'),
(8, 9, 'sarah', 2, 'john', 4, 'lisa', 'L', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:22:31', '2025-09-04 04:22:31'),
(9, 10, 'james', 4, 'lisa', 4, 'lisa', 'R', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:22:47', '2025-09-04 04:22:47'),
(10, 11, 'jorah', 6, 'david', 6, 'david', 'L', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:23:00', '2025-09-04 04:23:00'),
(11, 12, 'rose', 2, 'john', 6, 'david', 'R', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:23:15', '2025-09-04 04:23:15'),
(12, 13, 'sansa', 9, 'sarah', 9, 'sarah', 'L', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:23:25', '2025-09-04 04:23:25'),
(13, 14, 'arya', 9, 'sarah', 9, 'sarah', 'R', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:23:29', '2025-09-04 04:23:29'),
(14, 15, 'ned', 2, 'john', 10, 'james', 'L', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:23:35', '2025-09-04 04:23:35'),
(15, 16, 'jimi', 2, 'john', 10, 'james', 'R', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:23:40', '2025-09-04 04:23:40'),
(16, 17, 'ryan', 2, 'john', 7, 'olivia', 'L', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:24:43', '2025-09-04 04:24:43'),
(17, 18, 'jack', 7, 'olivia', 7, 'olivia', 'R', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:24:58', '2025-09-04 04:24:58'),
(18, 19, 'mia', 8, 'daniel', 8, 'daniel', 'L', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:25:11', '2025-09-04 04:25:11'),
(19, 20, 'lily', 8, 'daniel', 8, 'daniel', 'R', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:25:16', '2025-09-04 04:25:16'),
(20, 21, 'Aria', 2, 'john', 11, 'jorah', 'L', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:25:24', '2025-09-04 04:25:24'),
(21, 22, 'zoe', 11, 'jorah', 11, 'jorah', 'R', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:25:36', '2025-09-04 04:25:36'),
(22, 23, 'ella', 12, 'rose', 12, 'rose', 'L', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:25:43', '2025-09-04 04:25:43'),
(23, 24, 'ava', 12, 'rose', 12, 'rose', 'R', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:25:47', '2025-09-04 04:25:47'),
(24, 25, 'ket', 4, 'lisa', 13, 'sansa', 'L', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:25:54', '2025-09-04 04:25:54'),
(25, 26, 'rob', 13, 'sansa', 13, 'sansa', 'R', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:29:16', '2025-09-04 04:29:16'),
(26, 27, 'bran', 14, 'arya', 14, 'arya', 'L', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:29:25', '2025-09-04 04:29:25'),
(27, 28, 'varys', 14, 'arya', 14, 'arya', 'R', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:29:36', '2025-09-04 04:29:36'),
(28, 29, 'sam', 15, 'ned', 15, 'ned', 'L', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:29:43', '2025-09-04 04:29:43'),
(29, 30, 'davos', 15, 'ned', 15, 'ned', 'R', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:29:49', '2025-09-04 04:29:49'),
(30, 31, 'gilly', 16, 'jimi', 16, 'jimi', 'L', 1, 'admin', 1, 0, 1, 200.00, 1, '2025-09-04 04:29:58', '2025-09-04 04:29:58');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('klXKChDAiasmXOifcjW12dGoWZEUsrd5I5LAjXXg', NULL, '127.0.0.1', 'PostmanRuntime/7.45.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWG4zRXBzbGhRaDJ6QmRSUlVsY0x4QmRkbG5nU3FJT1FXdHNSQjVMRyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9kYXNoYm9hcmQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1756979489);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `sponsor_id` bigint UNSIGNED DEFAULT NULL,
  `tree_round_count` int NOT NULL DEFAULT '1',
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  KEY `users_sponsor_id_foreign` (`sponsor_id`)
) ENGINE=MyISAM AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `sponsor_id`, `tree_round_count`, `name`, `username`, `email`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, NULL, 1, NULL, 'admin', 'admin@example.com', '$2y$12$7UcfVjT8/4lXLJvoanZdx.Y11mb6kayfCqnV4Jej65EKXFuon4BhO', NULL, '2025-09-04 04:08:47', '2025-09-04 04:08:47'),
(2, 1, 1, NULL, 'john', 'john@mlm.com', '$2y$12$36.2Ak42266FPxU/LHwgN.ytb1PhrGm1S4IzX0i0uHT7e.HOlXdda', NULL, '2025-09-04 04:08:53', '2025-09-04 04:08:53'),
(3, 2, 1, NULL, 'mike', 'mike@mlm.com', '$2y$12$kg2tdxtXjA4snqOfJ3twG.fN7p0XS1H7TiO7b4DUJfl2ToUIJqbem', NULL, '2025-09-04 04:09:54', '2025-09-04 04:09:54'),
(4, 2, 1, NULL, 'lisa', 'lisa@mlm.com', '$2y$12$eCo1S0MtSo6bhdqUQWAYa.voFEkSeOuTGc2329fCpWZWt.K48FBRa', NULL, '2025-09-04 04:11:49', '2025-09-04 04:11:49'),
(5, 3, 1, NULL, 'emma', 'emma@mlm.com', '$2y$12$wM6vhAmZjQvVEypt72oWKOwZ/esWpZlw3etkyj.hjWzRGrJdHhSfu', NULL, '2025-09-04 04:12:06', '2025-09-04 04:12:06'),
(6, 2, 1, NULL, 'david', 'david@mlm.com', '$2y$12$dGmkI6Lg7MdrPSoYUcUvuusrpYKcdkpK7KwZwkZqgnTVxoURG7Uru', NULL, '2025-09-04 04:20:24', '2025-09-04 04:20:24'),
(7, 3, 1, NULL, 'olivia', 'olivia@mlm.com', '$2y$12$5yL7zshZBLpJ9fmtBW9TfuXXamiAEdN88RncPPRJtJwkpCMIam7ry', NULL, '2025-09-04 04:20:57', '2025-09-04 04:20:57'),
(8, 3, 1, NULL, 'daniel', 'daniel@mlm.com', '$2y$12$pVK6bMZL3aInQ6frCnM6U.thj/YAWbnmE/j9pcbUh1IpglBq3L6ya', NULL, '2025-09-04 04:22:00', '2025-09-04 04:22:00'),
(9, 2, 1, NULL, 'sarah', 'sarah@mlm.com', '$2y$12$nDnyor0bMTipfS5Oku/udOk7r2WbRSU5cMBSZsxhh8pHf9vsxOitG', NULL, '2025-09-04 04:22:31', '2025-09-04 04:22:31'),
(10, 4, 1, NULL, 'james', 'james@mlm.com', '$2y$12$GufZfCcJCqg4G3Ozm5SUMuWDOFiJnD2Mi9fFYEj0y2oFPw21rauaS', NULL, '2025-09-04 04:22:47', '2025-09-04 04:22:47'),
(11, 6, 1, NULL, 'jorah', 'jorah@mlm.com', '$2y$12$Lq/7Eov0lGx/cSIBJJbuUOrGWUhaaARes/jvAx0wazhMejiGHF6T2', NULL, '2025-09-04 04:23:00', '2025-09-04 04:23:00'),
(12, 2, 1, NULL, 'rose', 'rose@mlm.com', '$2y$12$Glrt/jqcrfVGjnBLZBHdDO2w0GuBX7VKbR6IVm7jJB5iJeUrb2Tsq', NULL, '2025-09-04 04:23:15', '2025-09-04 04:23:15'),
(13, 9, 1, NULL, 'sansa', 'sansa@mlm.com', '$2y$12$Glyba8VaUpLWAtWfjFmD1uN6jEnl/YYTgcVD68dOI7lunMzeZHoJe', NULL, '2025-09-04 04:23:25', '2025-09-04 04:23:25'),
(14, 9, 1, NULL, 'arya', 'arya@mlm.com', '$2y$12$/ClcHvVPNBRAb6itDxTKHec9YnV5F5T/UkzUjNU09S3DEZAfO9nr.', NULL, '2025-09-04 04:23:29', '2025-09-04 04:23:29'),
(15, 2, 1, NULL, 'ned', 'ned@mlm.com', '$2y$12$YmAArHFHOv.yfhrOpCDyTuNVpWC4mnPEoZau7oOllOFVW2uzG4cu2', NULL, '2025-09-04 04:23:35', '2025-09-04 04:23:35'),
(16, 2, 1, NULL, 'jimi', 'jimi@mlm.com', '$2y$12$1QsCM8dGNt4kyDvaY5r02uJs3usDKyr6cbDZwRtKlNfu25QE7YmGy', NULL, '2025-09-04 04:23:40', '2025-09-04 04:23:40'),
(17, 2, 1, NULL, 'ryan', 'ryan@mlm.com', '$2y$12$YsLArMrocAqkkUArxWOWM.0GjCkxNuDlE/B8sSiQ6XcOFMQNz4j7K', NULL, '2025-09-04 04:24:43', '2025-09-04 04:24:43'),
(18, 7, 1, NULL, 'jack', 'jack@mlm.com', '$2y$12$9yYkI1aRsi.eLrGguDY9K.RVGz7y6lBuUG9KYL/toxUrj9T2Goloy', NULL, '2025-09-04 04:24:58', '2025-09-04 04:24:58'),
(19, 8, 1, NULL, 'mia', 'mia@mlm.com', '$2y$12$EM2qwc9HODdjskbg.FRbYek0PEpPu7zYKq9HpBzSfkI/gx6lyvgpy', NULL, '2025-09-04 04:25:11', '2025-09-04 04:25:11'),
(20, 8, 1, NULL, 'lily', 'lily@mlm.com', '$2y$12$rv.nWuYhipkyAP01Zx9EEuVdnYNn4Kd.eLUHu6XqnoH.vW3U3HxMC', NULL, '2025-09-04 04:25:16', '2025-09-04 04:25:16'),
(21, 2, 1, NULL, 'Aria', 'Aria@mlm.com', '$2y$12$jhoingNlyf4ofPxZ4gsbSO.4ieQFU/S5R13y7XSzKgxW876b4ejey', NULL, '2025-09-04 04:25:24', '2025-09-04 04:25:24'),
(22, 11, 1, NULL, 'zoe', 'zoe@mlm.com', '$2y$12$eOIMimuV6xs0c.gZsAE/uO/VvEDGZF.NrU8Xub02d5d5w82b.6Q9y', NULL, '2025-09-04 04:25:36', '2025-09-04 04:25:36'),
(23, 12, 1, NULL, 'ella', 'ella@mlm.com', '$2y$12$rtKRZGa61w6UVwfxJMyPseN7TBv1mqqQOXqr6H0TC1U18RBKaXBhe', NULL, '2025-09-04 04:25:43', '2025-09-04 04:25:43'),
(24, 12, 1, NULL, 'ava', 'ava@mlm.com', '$2y$12$Bl.loeYyhcB7rn1MCrYHxeD54syw9dgQ7W/Vlp8AvRQfrYNlIh6HG', NULL, '2025-09-04 04:25:47', '2025-09-04 04:25:47'),
(25, 4, 1, NULL, 'ket', 'ket@mlm.com', '$2y$12$HpzV4N0du9coPuVhSbFn3exxRtaVZVOCyK0lzaOwPsbum3gVTr/AG', NULL, '2025-09-04 04:25:54', '2025-09-04 04:25:54'),
(26, 13, 1, NULL, 'rob', 'rob@mlm.com', '$2y$12$np5nDZoOPVvOzBLF/yQgI.9aLDdjAHcXRuxWKO5LP8fd6quwQyo2u', NULL, '2025-09-04 04:29:16', '2025-09-04 04:29:16'),
(27, 14, 1, NULL, 'bran', 'bran@mlm.com', '$2y$12$lldMiS9UbtkbHUczAA2DdOcpqEMrlD0iye7vAlPDqujCq5QCb2ZVW', NULL, '2025-09-04 04:29:25', '2025-09-04 04:29:25'),
(28, 14, 1, NULL, 'varys', 'varys@mlm.com', '$2y$12$./1nGrr/xQ2McoJUghrhpesBX9l3d9EIsFudYxhfJWzaxoxns9.jK', NULL, '2025-09-04 04:29:36', '2025-09-04 04:29:36'),
(29, 15, 1, NULL, 'sam', 'sam@mlm.com', '$2y$12$hgcQVU.vNwvt8YREkU5vU.GBcg/tjYxgggpU0kAm4u.KkdfE8THy6', NULL, '2025-09-04 04:29:43', '2025-09-04 04:29:43'),
(30, 15, 1, NULL, 'davos', 'davos@mlm.com', '$2y$12$1loJSWb6tiiVmIdq5vJAVOyplOfflYkHG3Ejp9y7wZvsp8VUZFO/2', NULL, '2025-09-04 04:29:49', '2025-09-04 04:29:49'),
(31, 16, 1, NULL, 'gilly', 'gilly@mlm.com', '$2y$12$OmJFOZyo2ggqQ6DRM3ACkOOtUnFSbcorojUEiELDu5PQ3Sceub7BK', NULL, '2025-09-04 04:29:58', '2025-09-04 04:29:58');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
