-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 30, 2025 at 12:01 PM
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
-- Database: `mlm_tree_db`
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
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(9, '0001_01_01_000000_create_users_table', 1),
(10, '0001_01_01_000001_create_cache_table', 1),
(11, '0001_01_01_000002_create_jobs_table', 1),
(12, '2025_08_30_052358_create_referral_relationships_table', 1);

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
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `referral_relationships_user_id_foreign` (`user_id`),
  KEY `referral_relationships_sponsor_id_foreign` (`sponsor_id`),
  KEY `referral_relationships_upline_id_foreign` (`upline_id`)
) ENGINE=MyISAM AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `referral_relationships`
--

INSERT INTO `referral_relationships` (`id`, `user_id`, `user_username`, `sponsor_id`, `sponsor_username`, `upline_id`, `upline_username`, `position`, `created_at`, `updated_at`) VALUES
(1, 2, 'john', NULL, NULL, NULL, NULL, NULL, '2025-08-30 05:30:39', '2025-08-30 05:30:39'),
(2, 3, 'mike', 2, 'john', 2, 'john', 'L', '2025-08-30 05:30:44', '2025-08-30 05:30:44'),
(3, 4, 'lisa', 2, 'john', 2, 'john', 'R', '2025-08-30 05:30:48', '2025-08-30 05:30:48'),
(4, 5, 'emma', 3, 'mike', 3, 'mike', 'L', '2025-08-30 05:31:33', '2025-08-30 05:31:33'),
(5, 6, 'david', 2, 'john', 3, 'mike', 'R', '2025-08-30 05:31:41', '2025-08-30 05:31:41'),
(6, 7, 'sarah', 2, 'john', 4, 'lisa', 'L', '2025-08-30 05:31:47', '2025-08-30 05:31:47'),
(7, 8, 'james', 4, 'lisa', 4, 'lisa', 'R', '2025-08-30 05:31:53', '2025-08-30 05:31:53'),
(8, 9, 'olivia', 3, 'mike', 5, 'emma', 'L', '2025-08-30 05:32:19', '2025-08-30 05:32:19'),
(9, 10, 'daniel', 3, 'mike', 5, 'emma', 'R', '2025-08-30 05:32:42', '2025-08-30 05:32:42'),
(10, 11, 'jorah', 6, 'david', 6, 'david', 'L', '2025-08-30 05:32:54', '2025-08-30 05:32:54'),
(11, 12, 'rose', 2, 'john', 6, 'david', 'R', '2025-08-30 05:32:59', '2025-08-30 05:32:59'),
(12, 13, 'sansa', 7, 'sarah', 7, 'sarah', 'L', '2025-08-30 05:33:11', '2025-08-30 05:33:11'),
(13, 14, 'arya', 7, 'sarah', 7, 'sarah', 'R', '2025-08-30 05:33:16', '2025-08-30 05:33:16'),
(14, 15, 'ned', 2, 'john', 8, 'james', 'L', '2025-08-30 05:33:35', '2025-08-30 05:33:35'),
(15, 16, 'jimi', 2, 'john', 8, 'james', 'R', '2025-08-30 05:33:40', '2025-08-30 05:33:40'),
(16, 17, 'ryan', 2, 'john', 9, 'olivia', 'L', '2025-08-30 05:33:50', '2025-08-30 05:33:50'),
(17, 18, 'jack', 9, 'olivia', 9, 'olivia', 'R', '2025-08-30 05:33:59', '2025-08-30 05:33:59'),
(18, 19, 'ket', 13, 'sansa', 13, 'sansa', 'L', '2025-08-30 05:34:12', '2025-08-30 05:34:12'),
(19, 20, 'mia', 10, 'daniel', 10, 'daniel', 'L', '2025-08-30 05:34:27', '2025-08-30 05:34:27'),
(20, 21, 'lily', 3, 'mike', 10, 'daniel', 'R', '2025-08-30 05:34:38', '2025-08-30 05:34:38'),
(21, 22, 'aria', 2, 'john', 11, 'jorah', 'L', '2025-08-30 05:34:50', '2025-08-30 05:34:50'),
(22, 23, 'zoe', 11, 'jorah', 11, 'jorah', 'R', '2025-08-30 05:35:06', '2025-08-30 05:35:06'),
(23, 24, 'rob', 4, 'lisa', 13, 'sansa', 'R', '2025-08-30 05:35:17', '2025-08-30 05:35:17'),
(24, 25, 'ella', 12, 'rose', 12, 'rose', 'L', '2025-08-30 05:35:29', '2025-08-30 05:35:29'),
(25, 26, 'ava', 12, 'rose', 12, 'rose', 'R', '2025-08-30 05:35:34', '2025-08-30 05:35:34'),
(26, 27, 'bran', 7, 'sarah', 14, 'arya', 'L', '2025-08-30 05:35:41', '2025-08-30 05:35:41'),
(27, 28, 'varys', 14, 'arya', 14, 'arya', 'R', '2025-08-30 05:35:57', '2025-08-30 05:35:57'),
(28, 29, 'sam', 2, 'john', 15, 'ned', 'L', '2025-08-30 05:36:05', '2025-08-30 05:36:05'),
(29, 30, 'davos', 4, 'lisa', 15, 'ned', 'R', '2025-08-30 05:36:12', '2025-08-30 05:36:12'),
(30, 31, 'gilly', 16, 'jimi', 16, 'jimi', 'L', '2025-08-30 05:36:30', '2025-08-30 05:36:30'),
(31, 32, 'lena', 16, 'jimi', 16, 'jimi', 'R', '2025-08-30 05:36:36', '2025-08-30 05:36:36');

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
('gVDu6lVZ22Q5uIoVi1lJgWYQIZOIYU7CgPoysCO1', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoibzdWcDZ5cXJLMVZXbWJiYld3TTl0WXd4TkU4eEl4TWl6c2ZsR0EyZyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC91c2Vycy9saXNhL3RyZWUiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1756555201);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, NULL, 'admin', 'admin@example.com', '$2y$12$dfSL2Nw8.7CT9CN3lCG6nuiCBFIveKmJ6CpM3YX8bA88uhZ7v1wh2', NULL, '2025-08-30 05:30:28', '2025-08-30 05:30:28'),
(2, NULL, 'john', 'john@mlm.com', '$2y$12$2fiPNjlcEvQ3tjtpBlWQdOnIDK.NiwDQxefWd6SMEOGz54q7Px9E.', NULL, '2025-08-30 05:30:39', '2025-08-30 05:30:39'),
(3, NULL, 'mike', 'mike@mlm.com', '$2y$12$jAXZKZWnjQpfVEVj3JfG9.7bqq7qP9kmxVHEKlTIp3zNUWYZM86yq', NULL, '2025-08-30 05:30:44', '2025-08-30 05:30:44'),
(4, NULL, 'lisa', 'lisa@mlm.com', '$2y$12$vFkTD8EF7MNyhmDGO1uaY.jvYWpD6zA6ujNMBbPM6vUkTJk0yTU5a', NULL, '2025-08-30 05:30:48', '2025-08-30 05:30:48'),
(5, NULL, 'emma', 'emma@mlm.com', '$2y$12$7lH2jOGDiF1hWUtpdiTELeZ164osWQAysynU.wXGh.KTdA8LcDKGC', NULL, '2025-08-30 05:31:33', '2025-08-30 05:31:33'),
(6, NULL, 'david', 'david@mlm.com', '$2y$12$2T1t0D.5VI7RFuisc1k.kONd36cxN8Qt1EmJ.Feu.E9oKd0hMS/Qm', NULL, '2025-08-30 05:31:41', '2025-08-30 05:31:41'),
(7, NULL, 'sarah', 'sarah@mlm.com', '$2y$12$ozFYV5B8R9RFKhSH3U07vO4z4c1SkJzyAtE5cUSqQ6GewN5IfkEJC', NULL, '2025-08-30 05:31:47', '2025-08-30 05:31:47'),
(8, NULL, 'james', 'james@mlm.com', '$2y$12$9XjGgN6Uh7BhYNTVV5zF7e4U2WMVpLsuCmb2cuHJF3Yqv2sd4a7Cu', NULL, '2025-08-30 05:31:53', '2025-08-30 05:31:53'),
(9, NULL, 'olivia', 'olivia@mlm.com', '$2y$12$qr0w4uapVhpjEuJEMkqUl.P3WxHFC9fvpocATOS3F.eStW4O7wuEG', NULL, '2025-08-30 05:32:19', '2025-08-30 05:32:19'),
(10, NULL, 'daniel', 'daniel@mlm.com', '$2y$12$WE/ww87vkeZIh9ij36xNj.T0A7CRrExKuoMAlydD6m/as1RHZxdsO', NULL, '2025-08-30 05:32:42', '2025-08-30 05:32:42'),
(11, NULL, 'jorah', 'jorah@mlm.com', '$2y$12$3WNR.at8WT0/z9L/pvl4GuD2Y2kzUNCpzVHwgQVLwDXLQ52dgMLla', NULL, '2025-08-30 05:32:54', '2025-08-30 05:32:54'),
(12, NULL, 'rose', 'rose@mlm.com', '$2y$12$CAwIj36jXvxDl886xVRQnOkCjtK9mKY4LNEbFrz.o.SUdF0gIYUg.', NULL, '2025-08-30 05:32:59', '2025-08-30 05:32:59'),
(13, NULL, 'sansa', 'sansa@mlm.com', '$2y$12$HT4fi1DZQNhLjdCDH6loxukoLCprbQufzZKH/sfAM9jaxcc6kElQG', NULL, '2025-08-30 05:33:11', '2025-08-30 05:33:11'),
(14, NULL, 'arya', 'arya@mlm.com', '$2y$12$KQL1ZpXM2dY7ftCW6U3Faep2QPR5u470N681wVk0ODFRf.PYIpAPS', NULL, '2025-08-30 05:33:16', '2025-08-30 05:33:16'),
(15, NULL, 'ned', 'ned@mlm.com', '$2y$12$2hwn6CWytjqXy9H02mnoc.Cx69JpcyF4.Py.0pPQcgqnOX.cxt4p.', NULL, '2025-08-30 05:33:35', '2025-08-30 05:33:35'),
(16, NULL, 'jimi', 'jimi@mlm.com', '$2y$12$JZNF7/VoT3BI4XNE0efMG.J62VQzIPMwsW.TTRdPwQT1PUfe04cOe', NULL, '2025-08-30 05:33:40', '2025-08-30 05:33:40'),
(17, NULL, 'ryan', 'ryan@mlm.com', '$2y$12$em.FeAiJtDZzPjz1znFdEeyyYK6k5dYgRjKkmaLAqbZBhD6dAjKh6', NULL, '2025-08-30 05:33:50', '2025-08-30 05:33:50'),
(18, NULL, 'jack', 'jack@mlm.com', '$2y$12$NXtT.L6OTGsRckN7u/OepuOcUEtUfcUkJ8lgInF1MJiKT8G/dTG/K', NULL, '2025-08-30 05:33:59', '2025-08-30 05:33:59'),
(19, NULL, 'ket', 'ket@mlm.com', '$2y$12$OsQuTQwJj1htJBkJ0/GSAedZem5f9TzFyzMzr8o7pZoAFZx6PbKZu', NULL, '2025-08-30 05:34:12', '2025-08-30 05:34:12'),
(20, NULL, 'mia', 'mia@mlm.com', '$2y$12$oQDztobrZ37AdhfI7DpzQulpPOrmAcaY7QPNvG56ge5UwKOTXmrjy', NULL, '2025-08-30 05:34:27', '2025-08-30 05:34:27'),
(21, NULL, 'lily', 'lily@mlm.com', '$2y$12$3rkEU9BEmaVqoacLfvJIaOgPz990dG1Gk0RO/D.8xkz/VWFKC4EI2', NULL, '2025-08-30 05:34:38', '2025-08-30 05:34:38'),
(22, NULL, 'aria', 'aria@mlm.com', '$2y$12$tZMdLgpYwN/sav4qBXgtiOKsnGF1NtzFvpvKB1CfMMEas05ezWdJm', NULL, '2025-08-30 05:34:50', '2025-08-30 05:34:50'),
(23, NULL, 'zoe', 'zoe@mlm.com', '$2y$12$dAs2y0xc7fpmkfxnAhRsg.ZeN1tXeZfifMdgPUtS.SzJHOX1YxDGe', NULL, '2025-08-30 05:35:06', '2025-08-30 05:35:06'),
(24, NULL, 'rob', 'rob@mlm.com', '$2y$12$xnPM.p7ytdyYi4qAnRrZl.NygCJvDT1ehJiiDmznhOVvTLjZGkCpu', NULL, '2025-08-30 05:35:17', '2025-08-30 05:35:17'),
(25, NULL, 'ella', 'ella@mlm.com', '$2y$12$t/6xpyCTzYNTyl7KZru3b.fEQ3JQAhTK8lMJtgpRuGzSzHhLm3JG2', NULL, '2025-08-30 05:35:29', '2025-08-30 05:35:29'),
(26, NULL, 'ava', 'ava@mlm.com', '$2y$12$Trb57d4XNZ7Kr44PH7aEmOKKjOMHBEaRv8RoWzuNDzwsYTFWUBsPe', NULL, '2025-08-30 05:35:34', '2025-08-30 05:35:34'),
(27, NULL, 'bran', 'bran@mlm.com', '$2y$12$pWzlpAEtLP/Xum/OpEL/AeF9I42Mzv1tDfY3TQtnDsxUwOLLPDTCW', NULL, '2025-08-30 05:35:41', '2025-08-30 05:35:41'),
(28, NULL, 'varys', 'varys@mlm.com', '$2y$12$sB3EeNmalCwvFnAP6H/3PuwETGgoPsCdfStEilaF.UAvzFWYnJQ.K', NULL, '2025-08-30 05:35:57', '2025-08-30 05:35:57'),
(29, NULL, 'sam', 'sam@mlm.com', '$2y$12$TzVjYGT98uxE/cCr7rpX3.8GztQM6WzIfZyls9XnPTlw7.2vgDhAC', NULL, '2025-08-30 05:36:05', '2025-08-30 05:36:05'),
(30, NULL, 'davos', 'davos@mlm.com', '$2y$12$cLi0yNa9eHiCO1RtZMdl7uAR/LC4Sgk3spFRIPDkNgu46Q4pJ.nZi', NULL, '2025-08-30 05:36:12', '2025-08-30 05:36:12'),
(31, NULL, 'gilly', 'gilly@mlm.com', '$2y$12$5udruCW4NP4jdyXc2QaB7elYnAIscaJdgNJ3IQGgaqd1dYEtc2rj.', NULL, '2025-08-30 05:36:30', '2025-08-30 05:36:30'),
(32, NULL, 'lena', 'lena@mlm.com', '$2y$12$hQr3OgiZOiQwtHBi10KozO2OZ2kC8H8Q8DwSRuIJFTgpnka2RYEzi', NULL, '2025-08-30 05:36:36', '2025-08-30 05:36:36');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
