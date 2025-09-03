-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 01, 2025 at 07:00 PM
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
-- Database: `tronx`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(191) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(191) NOT NULL,
  `owner` varchar(191) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(191) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(191) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(191) NOT NULL,
  `name` varchar(191) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(191) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(16, '0001_01_01_000000_create_users_table', 1),
(17, '0001_01_01_000001_create_cache_table', 1),
(18, '0001_01_01_000002_create_jobs_table', 1),
(19, '2025_08_30_052358_create_referral_relationships_table', 1),
(20, '2025_09_01_073053_add_spillover_fields_to_referral_relationships_table', 1),
(21, '2025_09_01_083944_update_existing_users_with_tree_owner_data', 1),
(22, '2025_09_01_085155_add_sponsor_id_to_users_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(191) NOT NULL,
  `token` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `referral_relationships`
--

CREATE TABLE `referral_relationships` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `user_username` varchar(191) DEFAULT NULL,
  `sponsor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sponsor_username` varchar(191) DEFAULT NULL,
  `upline_id` bigint(20) UNSIGNED DEFAULT NULL,
  `upline_username` varchar(191) DEFAULT NULL,
  `position` enum('L','R') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `tree_owner_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tree_owner_username` varchar(191) DEFAULT NULL,
  `tree_round` int(11) NOT NULL DEFAULT 1,
  `is_spillover_slot` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `referral_relationships`
--

INSERT INTO `referral_relationships` (`id`, `user_id`, `user_username`, `sponsor_id`, `sponsor_username`, `upline_id`, `upline_username`, `position`, `created_at`, `updated_at`, `tree_owner_id`, `tree_owner_username`, `tree_round`, `is_spillover_slot`) VALUES
(1, 2, 'john', NULL, NULL, NULL, NULL, NULL, '2025-09-01 11:15:24', '2025-09-01 11:15:24', 2, 'john', 1, 0),
(2, 3, 'mike', 2, 'john', 2, 'john', 'L', '2025-09-01 11:15:29', '2025-09-01 11:15:29', 2, 'john', 1, 0),
(3, 4, 'lisa', 2, 'john', 2, 'john', 'R', '2025-09-01 11:15:48', '2025-09-01 11:15:48', 2, 'john', 1, 0),
(4, 5, 'emma', 3, 'mike', 3, 'mike', 'L', '2025-09-01 11:17:59', '2025-09-01 11:17:59', 2, 'john', 1, 0),
(5, 6, 'david', 2, 'john', 3, 'mike', 'R', '2025-09-01 11:18:58', '2025-09-01 11:18:58', 2, 'john', 1, 0),
(6, 7, 'olivia', 3, 'mike', 5, 'emma', 'L', '2025-09-01 11:19:20', '2025-09-01 11:19:20', 2, 'john', 1, 0),
(7, 8, 'sarah', 2, 'john', 4, 'lisa', 'L', '2025-09-01 11:19:30', '2025-09-01 11:19:30', 2, 'john', 1, 0),
(8, 9, 'james', 4, 'lisa', 4, 'lisa', 'R', '2025-09-01 11:19:46', '2025-09-01 11:19:46', 2, 'john', 1, 0),
(9, 10, 'daniel', 3, 'mike', 5, 'emma', 'R', '2025-09-01 11:19:56', '2025-09-01 11:19:56', 2, 'john', 1, 0),
(10, 11, 'jorah', 6, 'david', 6, 'david', 'L', '2025-09-01 11:24:05', '2025-09-01 11:24:05', 2, 'john', 1, 0),
(11, 12, 'rose', 2, 'john', 6, 'david', 'R', '2025-09-01 11:24:13', '2025-09-01 11:24:13', 2, 'john', 1, 0),
(12, 13, 'sansa', 8, 'sarah', 8, 'sarah', 'L', '2025-09-01 11:24:33', '2025-09-01 11:24:33', 2, 'john', 1, 0),
(13, 14, 'arya', 8, 'sarah', 8, 'sarah', 'R', '2025-09-01 11:24:41', '2025-09-01 11:24:41', 2, 'john', 1, 0),
(14, 15, 'ned', 2, 'john', 9, 'james', 'L', '2025-09-01 11:24:48', '2025-09-01 11:24:48', 2, 'john', 1, 0),
(15, 16, 'jimi', 9, 'james', 9, 'james', 'R', '2025-09-01 11:25:00', '2025-09-01 11:25:00', 2, 'john', 1, 0),
(16, 17, 'ryan', 2, 'john', 7, 'olivia', 'L', '2025-09-01 11:25:52', '2025-09-01 11:25:52', 2, 'john', 1, 0),
(17, 18, 'jack', 7, 'olivia', 7, 'olivia', 'R', '2025-09-01 11:26:01', '2025-09-01 11:26:01', 2, 'john', 1, 0),
(18, 19, 'mia', 3, 'mike', 10, 'daniel', 'L', '2025-09-01 11:26:15', '2025-09-01 11:26:15', 2, 'john', 1, 0),
(19, 20, 'lily', 5, 'emma', 10, 'daniel', 'R', '2025-09-01 11:26:29', '2025-09-01 11:26:29', 2, 'john', 1, 0),
(20, 21, 'aria', 2, 'john', 11, 'jorah', 'L', '2025-09-01 11:26:43', '2025-09-01 11:26:43', 2, 'john', 1, 0),
(21, 22, 'zoe', 6, 'david', 11, 'jorah', 'R', '2025-09-01 11:26:52', '2025-09-01 11:26:52', 2, 'john', 1, 0),
(22, 23, 'ket', 13, 'sansa', 13, 'sansa', 'L', '2025-09-01 11:27:19', '2025-09-01 11:27:19', 2, 'john', 1, 0),
(23, 24, 'ella', 6, 'david', 12, 'rose', 'L', '2025-09-01 11:27:36', '2025-09-01 11:27:36', 2, 'john', 1, 0),
(24, 25, 'ava', 12, 'rose', 12, 'rose', 'R', '2025-09-01 11:27:55', '2025-09-01 11:27:55', 2, 'john', 1, 0),
(25, 26, 'rob', 8, 'sarah', 13, 'sansa', 'R', '2025-09-01 11:28:06', '2025-09-01 11:28:06', 2, 'john', 1, 0),
(26, 27, 'bran', 4, 'lisa', 14, 'arya', 'L', '2025-09-01 11:28:23', '2025-09-01 11:28:23', 2, 'john', 1, 0),
(27, 28, 'varys', 14, 'arya', 14, 'arya', 'R', '2025-09-01 11:28:34', '2025-09-01 11:28:34', 2, 'john', 1, 0),
(28, 29, 'sam', 15, 'ned', 15, 'ned', 'L', '2025-09-01 11:28:55', '2025-09-01 11:28:55', 2, 'john', 1, 0),
(29, 30, 'davos', 15, 'ned', 15, 'ned', 'R', '2025-09-01 11:29:01', '2025-09-01 11:29:01', 2, 'john', 1, 0),
(30, 31, 'gilly', 16, 'jimi', 16, 'jimi', 'L', '2025-09-01 11:29:08', '2025-09-01 11:29:08', 2, 'john', 1, 0),
(31, 32, 'lena', 16, 'jimi', 16, 'jimi', 'R', '2025-09-01 11:29:14', '2025-09-01 11:29:14', 2, 'john', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(191) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('yVVoXfFZpj8UHtxIFcVMcHI5TKcLBWEAKFwM6s53', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiU3BZeWpLdFNuNVJEa2JwY2RHREhST1huUzR5eVA0Wk1wYXF0ZG1uVCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzU6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZGQtdXNlci1mb3JtIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1756745954);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) DEFAULT NULL,
  `username` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `password` varchar(191) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `sponsor_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `password`, `remember_token`, `created_at`, `updated_at`, `sponsor_id`) VALUES
(1, NULL, 'admin', 'admin@example.com', '$2y$12$3ohhZI8SoiC.E54JIw6qAeeguUVR5PpKH63yPj/qTdBqmLGh4FgEi', NULL, '2025-09-01 11:15:08', '2025-09-01 11:15:08', NULL),
(2, NULL, 'john', 'john@mlm.com', '$2y$12$rKJPXSKFFFOiL/zCIc18vOvMVnCdTWb2XRwQMcuJESbKnS/mLtahq', NULL, '2025-09-01 11:15:24', '2025-09-01 11:15:24', 1),
(3, NULL, 'mike', 'mike@mlm.com', '$2y$12$hmtwqlEyTKUq4Ub/lv7GsuSe9I978qjSyXvoOuEzY9wZAxxJ5/vna', NULL, '2025-09-01 11:15:29', '2025-09-01 11:15:29', 2),
(4, NULL, 'lisa', 'lisa@mlm.com', '$2y$12$X2UEOKLeEwjN6hfrlFePCe0xI4eP1G1l9dhcPpO1ZP6Ol.V0kFK9S', NULL, '2025-09-01 11:15:48', '2025-09-01 11:15:48', 2),
(5, NULL, 'emma', 'emma@mlm.com', '$2y$12$pVj/18S/nqBDyXYk9ixxeeRWjJUQDBn./7nnUDvSDecbdtEQwH7Hi', NULL, '2025-09-01 11:17:59', '2025-09-01 11:17:59', 3),
(6, NULL, 'david', 'david@mlm.com', '$2y$12$aDWR5jBRie1spd7cwrTsN.XpXoiHJRh9EQWJp7sclCOUylLJERzUO', NULL, '2025-09-01 11:18:58', '2025-09-01 11:18:58', 2),
(7, NULL, 'olivia', 'olivia@mlm.com', '$2y$12$WJm5HLg4cysWmD7ta5CEiehAZ/wyHbqWPOaf4GQHN.qFPeHySuBA6', NULL, '2025-09-01 11:19:20', '2025-09-01 11:19:20', 3),
(8, NULL, 'sarah', 'sarah@mlm.com', '$2y$12$4BJzRZJslYpf.DpFpyV7MecSQtnCO3y0ghcpa/4ygy8e0hw7lsPVC', NULL, '2025-09-01 11:19:30', '2025-09-01 11:19:30', 2),
(9, NULL, 'james', 'james@mlm.com', '$2y$12$5Obena9zC7nhN8ruJOq9/u16uqmXfZbGn4MXMpZEuMWZ4LfKC6yuW', NULL, '2025-09-01 11:19:46', '2025-09-01 11:19:46', 4),
(10, NULL, 'daniel', 'daniel@mlm.com', '$2y$12$Lyi8ZRbU9FsqMgLRSCnIr.qx0wsCdjYT3jpVXuW35gUmggZhT9umq', NULL, '2025-09-01 11:19:56', '2025-09-01 11:19:56', 3),
(11, NULL, 'jorah', 'jorah@mlm.com', '$2y$12$aH4A6XNgaLR9KW.p2cQ6Tut3BJf9mdqkbhTR1k2KoFZsKrPYZRSkS', NULL, '2025-09-01 11:24:05', '2025-09-01 11:24:05', 6),
(12, NULL, 'rose', 'rose@mlm.com', '$2y$12$bNQEI55Rh58RsZY.mmsE2uZMtEKnOGfA9BWbxDLofAomWqy4cWNka', NULL, '2025-09-01 11:24:13', '2025-09-01 11:24:13', 2),
(13, NULL, 'sansa', 'sansa@mlm.com', '$2y$12$b.VS.xZ4otO59NP8q7bM/uSki.kq8k05Ky6nu1UktGgcdTuaoowkW', NULL, '2025-09-01 11:24:33', '2025-09-01 11:24:33', 8),
(14, NULL, 'arya', 'arya@mlm.com', '$2y$12$fWbfNwSENcnxN57QAzTByOaoLfGRa1DlorV6KMWpRtTqmJkXNFeWW', NULL, '2025-09-01 11:24:41', '2025-09-01 11:24:41', 8),
(15, NULL, 'ned', 'ned@mlm.com', '$2y$12$nfHe946674TaNWIjtWwvjeFQ5lrPbZFANCZ0S6sgt2trTcUMM0hwi', NULL, '2025-09-01 11:24:48', '2025-09-01 11:24:48', 2),
(16, NULL, 'jimi', 'jimi@mlm.com', '$2y$12$ZrgG1L0iJtg6FdkFfpG8N.EvEBhFZgejg/OBVsSH0TvkLuqXzh9Si', NULL, '2025-09-01 11:25:00', '2025-09-01 11:25:00', 9),
(17, NULL, 'ryan', 'ryan@mlm.com', '$2y$12$hu1UD8gSMp9q80uhd45FyeuNX0DZ4c1lbGd2XVhuJFnSTOjZTSpWS', NULL, '2025-09-01 11:25:52', '2025-09-01 11:25:52', 2),
(18, NULL, 'jack', 'jack@mlm.com', '$2y$12$LtuJdRUp94ffv1VPZgleleDmsH84/BbGPZvN5Y4qZYNAUWro36DUW', NULL, '2025-09-01 11:26:01', '2025-09-01 11:26:01', 7),
(19, NULL, 'mia', 'mia@mlm.com', '$2y$12$BXgMJnJ7pHLHCPUNLwSsSuxl/uv/i2VfyJ39Bi0.lZooBqWMRtwwG', NULL, '2025-09-01 11:26:15', '2025-09-01 11:26:15', 3),
(20, NULL, 'lily', 'lily@mlm.com', '$2y$12$GqqysCNTg/pjqWG6CUB61eDLroJmNgX6ae7rVnrpya0VnFJR8Xba6', NULL, '2025-09-01 11:26:29', '2025-09-01 11:26:29', 5),
(21, NULL, 'aria', 'aria@mlm.com', '$2y$12$vZAkn/.OmjJwScHHPEOKCev28rfIFamjtBnmGAho7H6d4QbrQLdAe', NULL, '2025-09-01 11:26:43', '2025-09-01 11:26:43', 2),
(22, NULL, 'zoe', 'zoe@mlm.com', '$2y$12$LP.EmS52EA1QXTDFSJ8Vzux.1AfT5Udlz7QyWdyZx0b.4E2dtM5bG', NULL, '2025-09-01 11:26:52', '2025-09-01 11:26:52', 6),
(23, NULL, 'ket', 'ket@mlm.com', '$2y$12$qVFRaUXJnhL66/oPTzyfvukZJl1rGj/aw4msGMj4GniYMZfDH1Hre', NULL, '2025-09-01 11:27:19', '2025-09-01 11:27:19', 13),
(24, NULL, 'ella', 'ella@mlm.com', '$2y$12$Iq6/jPy6MZnoNJM0Dgyo0ODRSpiodUTsTIzdbkEM8vv4M8fO/pSWu', NULL, '2025-09-01 11:27:36', '2025-09-01 11:27:36', 6),
(25, NULL, 'ava', 'ava@mlm.com', '$2y$12$rERplfSisFxvhB3OIS2rtOV1FOxQuJ5zNxwIoo3MSGrhas6Ih9Fy.', NULL, '2025-09-01 11:27:55', '2025-09-01 11:27:55', 12),
(26, NULL, 'rob', 'rob@mlm.com', '$2y$12$C9WzGVbZ9IxSqR/WciRuwO58NJdwvJpLtNJ23O/azyhoG6jmShPsm', NULL, '2025-09-01 11:28:06', '2025-09-01 11:28:06', 8),
(27, NULL, 'bran', 'bran@mlm.com', '$2y$12$cwOTCnNVHSj.HYsBOreowOEnSbUNZCOXlu16fWeRcc/W6Sqwk5f2S', NULL, '2025-09-01 11:28:23', '2025-09-01 11:28:23', 4),
(28, NULL, 'varys', 'varys@mlm.com', '$2y$12$4vrjcq3KbMRz1NxF1f1QzOi6W0lEuNRYm6EM4n.IjI2106AOVUP4K', NULL, '2025-09-01 11:28:34', '2025-09-01 11:28:34', 14),
(29, NULL, 'sam', 'sam@mlm.com', '$2y$12$YvyPltcKv7itIxqkNa.ltuANugx8eVgWXcuNlStcumATYRG9xZcs6', NULL, '2025-09-01 11:28:55', '2025-09-01 11:28:55', 15),
(30, NULL, 'davos', 'davos@mlm.com', '$2y$12$61m4x8ubH32U.SHnUKAIHuOmIuXRyjhmFVsm1VcLycZa6fMMc4SnW', NULL, '2025-09-01 11:29:01', '2025-09-01 11:29:01', 15),
(31, NULL, 'gilly', 'gilly@mlm.com', '$2y$12$mvnZsVzn2mt3WsFaRhquo.OrojsETB3FTtU/av3R2np13sbgw8z0u', NULL, '2025-09-01 11:29:08', '2025-09-01 11:29:08', 16),
(32, NULL, 'lena', 'lena@mlm.com', '$2y$12$veCDynlLipyEHZQCPOrF8..CJxi68Jl0U93P.HV1E8sNHSuWJ4Y16', NULL, '2025-09-01 11:29:14', '2025-09-01 11:29:14', 16);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `referral_relationships`
--
ALTER TABLE `referral_relationships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `referral_relationships_user_id_foreign` (`user_id`),
  ADD KEY `referral_relationships_sponsor_id_foreign` (`sponsor_id`),
  ADD KEY `referral_relationships_upline_id_foreign` (`upline_id`),
  ADD KEY `referral_relationships_tree_owner_id_foreign` (`tree_owner_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD KEY `users_sponsor_id_foreign` (`sponsor_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `referral_relationships`
--
ALTER TABLE `referral_relationships`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `referral_relationships`
--
ALTER TABLE `referral_relationships`
  ADD CONSTRAINT `referral_relationships_sponsor_id_foreign` FOREIGN KEY (`sponsor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `referral_relationships_tree_owner_id_foreign` FOREIGN KEY (`tree_owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `referral_relationships_upline_id_foreign` FOREIGN KEY (`upline_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `referral_relationships_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_sponsor_id_foreign` FOREIGN KEY (`sponsor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
