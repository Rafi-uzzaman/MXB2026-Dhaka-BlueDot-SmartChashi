-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 30, 2025 at 06:45 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `smartcashi_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_2fa_backup_codes`
--

CREATE TABLE `admin_2fa_backup_codes` (
  `code_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `code_hash` varchar(255) NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_2fa_tokens`
--

CREATE TABLE `admin_2fa_tokens` (
  `token_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(6) NOT NULL,
  `token_type` enum('email','totp','sms','backup') DEFAULT 'email',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `used` tinyint(1) DEFAULT 0,
  `used_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_activity_logs`
--

CREATE TABLE `admin_activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `action_category` enum('user','security','system','content','settings','data','backup','report') NOT NULL DEFAULT 'system',
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `risk_level` enum('low','medium','high','critical') DEFAULT 'low',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_activity_logs`
--

INSERT INTO `admin_activity_logs` (`log_id`, `user_id`, `action`, `action_category`, `entity_type`, `entity_id`, `old_value`, `new_value`, `ip_address`, `user_agent`, `risk_level`, `created_at`) VALUES
(1, 48, 'admin_login', 'security', 'user', 48, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'low', '2025-12-30 05:57:55'),
(2, 48, 'admin_login', 'security', 'user', 48, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'low', '2025-12-30 06:01:30'),
(3, 48, 'admin_login', 'security', 'user', 48, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'low', '2025-12-30 06:04:39'),
(4, 48, 'admin_login', 'security', 'user', 48, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'low', '2025-12-30 06:06:30'),
(5, 48, 'admin_login', 'security', 'user', 48, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'low', '2025-12-30 06:08:06'),
(6, 48, 'admin_login', 'security', 'user', 48, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'low', '2025-12-30 06:19:49'),
(7, 49, 'admin_login', 'security', 'user', 49, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', 'low', '2025-12-30 08:14:10'),
(8, 49, 'admin_login', 'security', 'user', 49, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', 'low', '2025-12-30 08:48:52'),
(9, 49, 'admin_login', 'security', 'user', 49, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', 'low', '2025-12-30 08:51:50'),
(10, 49, 'admin_login', 'security', 'user', 49, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', 'low', '2025-12-30 08:52:01'),
(11, 49, 'admin_login', 'security', 'user', 49, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', 'low', '2025-12-30 08:52:12'),
(12, 49, 'admin_login', 'security', 'user', 49, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', 'low', '2025-12-30 09:13:38'),
(13, 49, 'admin_login', 'security', 'user', 49, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', 'low', '2025-12-30 09:24:33'),
(14, 49, 'admin_login', 'security', 'user', 49, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', 'low', '2025-12-30 09:33:35'),
(15, 49, 'admin_login', 'security', 'user', 49, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', 'low', '2025-12-30 09:35:55'),
(16, 49, 'admin_login', 'security', 'user', 49, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', 'low', '2025-12-30 09:39:54'),
(17, 49, 'admin_login', 'security', 'user', 49, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', 'low', '2025-12-30 10:02:33'),
(18, 49, 'admin_logout', 'security', 'user', 49, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', 'low', '2025-12-30 10:04:28'),
(19, 49, 'admin_login', 'security', 'user', 49, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'low', '2025-12-30 10:06:07'),
(20, 49, 'generate_report', 'report', 'report', NULL, NULL, '{\"type\":\"user_summary\",\"format\":\"pdf\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'low', '2025-12-30 10:35:51'),
(21, 49, 'generate_report', 'report', 'report', NULL, NULL, '{\"type\":\"security_audit\",\"format\":\"pdf\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'low', '2025-12-30 10:36:13'),
(22, 49, 'update_user', 'user', 'user', 49, '{\"user_id\":\"49\",\"email\":\"admin@smartcashi.com\",\"phone\":\"01700000000\",\"password_hash\":\"$2y$10$xBCPU\\/lz302alc3LrmAzkOm2GRFZxG1jPmbiECj2av5Ki2aI0SZQC\",\"first_name\":\"Admin\",\"last_name\":\"Demo\",\"profile_img_url\":\"uploads\\/profiles\\/admin-default.jpg\",\"role\":\"admin\",\"is_active\":\"1\",\"is_verified\":\"1\",\"last_login\":\"2025-12-30 16:06:07\",\"created_at\":\"2025-12-30 14:12:31\",\"updated_at\":\"2025-12-30 16:06:07\"}', '{\"action\":\"update_user\",\"csrf_token\":\"ae8f17c1b4e59e41fd37396c2778a3935050c5ed32e6e9207f8c3ba6ef22e596\",\"device_fingerprint\":\"TW96aWxsYS81LjAgKFdpbmRvd3MgTlQg\",\"first_name\":\"Admin\",\"last_name\":\"Demo\",\"email\":\"admin@smartcashi.com\",\"phone\":\"01700000000\",\"role\":\"admin\",\"is_active\":\"1\",\"is_verified\":\"1\",\"user_id\":\"49\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 10:57:53'),
(23, 49, 'update_user', 'user', 'user', 49, '{\"user_id\":\"49\",\"email\":\"admin@smartcashi.com\",\"phone\":\"01700000000\",\"password_hash\":\"$2y$10$xBCPU\\/lz302alc3LrmAzkOm2GRFZxG1jPmbiECj2av5Ki2aI0SZQC\",\"first_name\":\"Admin\",\"last_name\":\"Demo\",\"profile_img_url\":\"uploads\\/profiles\\/admin-default.jpg\",\"role\":\"admin\",\"is_active\":\"1\",\"is_verified\":\"1\",\"last_login\":\"2025-12-30 16:06:07\",\"created_at\":\"2025-12-30 14:12:31\",\"updated_at\":\"2025-12-30 16:06:07\"}', '{\"action\":\"update_user\",\"csrf_token\":\"ae8f17c1b4e59e41fd37396c2778a3935050c5ed32e6e9207f8c3ba6ef22e596\",\"device_fingerprint\":\"TW96aWxsYS81LjAgKFdpbmRvd3MgTlQg\",\"first_name\":\"Admin\",\"last_name\":\"Demo\",\"email\":\"admin@smartcashi.com\",\"phone\":\"01700000000\",\"role\":\"admin\",\"is_active\":\"1\",\"is_verified\":\"1\",\"user_id\":\"49\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 10:57:53'),
(24, 49, 'update_user', 'user', 'user', 49, '{\"user_id\":\"49\",\"email\":\"admin@smartcashi.com\",\"phone\":\"01700000000\",\"password_hash\":\"$2y$10$xBCPU\\/lz302alc3LrmAzkOm2GRFZxG1jPmbiECj2av5Ki2aI0SZQC\",\"first_name\":\"Admin\",\"last_name\":\"Demo\",\"profile_img_url\":\"uploads\\/profiles\\/admin-default.jpg\",\"role\":\"admin\",\"is_active\":\"1\",\"is_verified\":\"1\",\"last_login\":\"2025-12-30 16:06:07\",\"created_at\":\"2025-12-30 14:12:31\",\"updated_at\":\"2025-12-30 16:06:07\"}', '{\"action\":\"update_user\",\"csrf_token\":\"ae8f17c1b4e59e41fd37396c2778a3935050c5ed32e6e9207f8c3ba6ef22e596\",\"device_fingerprint\":\"TW96aWxsYS81LjAgKFdpbmRvd3MgTlQg\",\"first_name\":\"Admin\",\"last_name\":\"Demo\",\"email\":\"admin@smartcashi.com\",\"phone\":\"01700000000\",\"role\":\"admin\",\"is_active\":\"1\",\"is_verified\":\"1\",\"user_id\":\"49\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 10:57:53'),
(25, 49, 'admin_login', 'security', 'user', 49, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'low', '2025-12-30 11:08:42'),
(26, 49, 'create_user', 'user', 'user', 50, NULL, '{\"email\":\"mohatmimhaque1234@gmail.com\",\"first_name\":\"Mohatamim\",\"role\":\"farmer\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 11:09:26'),
(27, 49, 'update_user', 'user', 'user', 50, '{\"user_id\":\"50\",\"email\":\"mohatmimhaque1234@gmail.com\",\"phone\":\"1518749114\",\"password_hash\":\"$2y$10$O0t61irPjsYeIa9bdyzVTOkdTTCYgPidwxUgguoAWmqJ52O1l8r.y\",\"first_name\":\"Mohatamim\",\"last_name\":\"\",\"profile_img_url\":\"uploads\\/profiles\\/default-avatar.jpg\",\"role\":\"farmer\",\"is_active\":\"1\",\"is_verified\":\"1\",\"last_login\":null,\"created_at\":\"2025-12-30 17:09:26\",\"updated_at\":\"2025-12-30 17:09:26\"}', '{\"user_id\":\"50\",\"first_name\":\"Mohatamim\",\"last_name\":\"\",\"email\":\"mohatmimhaque1234@gmail.com\",\"phone\":\"1518749114\",\"role\":\"farmer\",\"is_active\":\"1\",\"password\":\"\",\"action\":\"update_user\",\"csrf_token\":\"836651f7a53d32f844aaf5456a564a697231f8409d619d48dba59eadde79d9ef\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 11:15:01'),
(28, 49, 'update_user', 'user', 'user', 50, '{\"user_id\":\"50\",\"email\":\"mohatmimhaque1234@gmail.com\",\"phone\":\"1518749114\",\"password_hash\":\"$2y$10$O0t61irPjsYeIa9bdyzVTOkdTTCYgPidwxUgguoAWmqJ52O1l8r.y\",\"first_name\":\"Mohatamim\",\"last_name\":\"\",\"profile_img_url\":\"uploads\\/profiles\\/default-avatar.jpg\",\"role\":\"farmer\",\"is_active\":\"1\",\"is_verified\":\"1\",\"last_login\":null,\"created_at\":\"2025-12-30 17:09:26\",\"updated_at\":\"2025-12-30 17:09:26\"}', '{\"action\":\"update_user\",\"csrf_token\":\"836651f7a53d32f844aaf5456a564a697231f8409d619d48dba59eadde79d9ef\",\"device_fingerprint\":\"TW96aWxsYS81LjAgKFdpbmRvd3MgTlQg\",\"first_name\":\"Mohatamim\",\"last_name\":\"\",\"email\":\"mohatmimhaque1234@gmail.com\",\"phone\":\"1518749114\",\"role\":\"farmer\",\"is_active\":\"1\",\"is_verified\":\"0\",\"user_id\":\"50\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 11:15:01'),
(29, 49, 'update_user', 'user', 'user', 50, '{\"user_id\":\"50\",\"email\":\"mohatmimhaque1234@gmail.com\",\"phone\":\"1518749114\",\"password_hash\":\"$2y$10$O0t61irPjsYeIa9bdyzVTOkdTTCYgPidwxUgguoAWmqJ52O1l8r.y\",\"first_name\":\"Mohatamim\",\"last_name\":\"\",\"profile_img_url\":\"uploads\\/profiles\\/default-avatar.jpg\",\"role\":\"farmer\",\"is_active\":\"1\",\"is_verified\":\"0\",\"last_login\":null,\"created_at\":\"2025-12-30 17:09:26\",\"updated_at\":\"2025-12-30 17:15:01\"}', '{\"action\":\"update_user\",\"csrf_token\":\"836651f7a53d32f844aaf5456a564a697231f8409d619d48dba59eadde79d9ef\",\"device_fingerprint\":\"TW96aWxsYS81LjAgKFdpbmRvd3MgTlQg\",\"first_name\":\"Mohatamim\",\"last_name\":\"\",\"email\":\"mohatmimhaque1234@gmail.com\",\"phone\":\"1518749114\",\"role\":\"farmer\",\"is_active\":\"1\",\"is_verified\":\"0\",\"user_id\":\"50\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 11:15:01'),
(30, 49, 'update_user', 'user', 'user', 50, '{\"user_id\":\"50\",\"email\":\"mohatmimhaque1234@gmail.com\",\"phone\":\"1518749114\",\"password_hash\":\"$2y$10$O0t61irPjsYeIa9bdyzVTOkdTTCYgPidwxUgguoAWmqJ52O1l8r.y\",\"first_name\":\"Mohatamim\",\"last_name\":\"\",\"profile_img_url\":\"uploads\\/profiles\\/default-avatar.jpg\",\"role\":\"farmer\",\"is_active\":\"1\",\"is_verified\":\"0\",\"last_login\":null,\"created_at\":\"2025-12-30 17:09:26\",\"updated_at\":\"2025-12-30 17:15:01\"}', '{\"action\":\"update_user\",\"csrf_token\":\"836651f7a53d32f844aaf5456a564a697231f8409d619d48dba59eadde79d9ef\",\"device_fingerprint\":\"TW96aWxsYS81LjAgKFdpbmRvd3MgTlQg\",\"first_name\":\"Mohatamim\",\"last_name\":\"\",\"email\":\"mohatmimhaque1234@gmail.com\",\"phone\":\"1518749114\",\"role\":\"farmer\",\"is_active\":\"1\",\"is_verified\":\"0\",\"user_id\":\"50\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 11:15:01'),
(31, 49, 'create_user', 'user', 'user', 57, NULL, '{\"email\":\"mohatamim1@gmail.com\",\"first_name\":\"Mohatamim\",\"role\":\"farmer\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 11:15:32'),
(32, 49, 'create_user', 'user', 'user', 58, NULL, '{\"email\":\"mohatamim123455@gmail.com\",\"first_name\":\"Mohatamim\",\"role\":\"farmer\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 11:16:32'),
(33, 49, 'ban_user', 'user', 'user', 58, NULL, '{\"type\":\"temporary\",\"reason\":\"nn n\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'high', '2025-12-30 11:17:10'),
(34, 49, 'ban_user', 'user', 'user', 58, NULL, '{\"type\":\"temporary\",\"reason\":\"nn n\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'high', '2025-12-30 11:17:10'),
(35, 49, 'ban_user', 'user', 'user', 58, NULL, '{\"type\":\"temporary\",\"reason\":\"nn n\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'high', '2025-12-30 11:17:10'),
(36, 49, 'delete_user', 'user', 'user', 58, '{\"user_id\":\"58\",\"email\":\"mohatamim123455@gmail.com\",\"phone\":\"01518956255\",\"password_hash\":\"$2y$10$lKHN\\/mG1Gsqwya4RicSLWunfbuER7LykVb7PF\\/kzgfZqW\\/6K3ZBP.\",\"first_name\":\"Mohatamim\",\"last_name\":\"\",\"profile_img_url\":\"uploads\\/profiles\\/default-avatar.jpg\",\"role\":\"farmer\",\"is_active\":\"0\",\"is_verified\":\"0\",\"last_login\":null,\"created_at\":\"2025-12-30 17:16:32\",\"updated_at\":\"2025-12-30 17:17:10\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'high', '2025-12-30 11:17:54'),
(37, 49, 'delete_user', 'user', 'user', 47, '{\"user_id\":\"47\",\"email\":\"mecika9949@dubokutv.com\",\"phone\":\"01518946255\",\"password_hash\":\"$2y$10$X8WwmDfJtOlTcG8DSu57humKB5IIqMXx9MwCUAI8f0aJcsbkH5jYu\",\"first_name\":\"Mohatamim\",\"last_name\":\"\",\"profile_img_url\":\"uploads\\/profiles\\/profile_47_1767050471.JPG\",\"role\":\"farmer\",\"is_active\":\"1\",\"is_verified\":\"1\",\"last_login\":\"2025-12-30 05:07:39\",\"created_at\":\"2025-12-29 22:27:41\",\"updated_at\":\"2025-12-30 05:21:11\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'high', '2025-12-30 11:26:13'),
(38, 49, 'delete_user', 'user', 'user', 57, '{\"user_id\":\"57\",\"email\":\"mohatamim1@gmail.com\",\"phone\":\"5454544545\",\"password_hash\":\"$2y$10$TMcOckPoSbRX5pWNsPMnDeS3nGdgv9AuvRg6abr3pfNmfT\\/DwPy3y\",\"first_name\":\"Mohatamim\",\"last_name\":\"\",\"profile_img_url\":\"uploads\\/profiles\\/default-avatar.jpg\",\"role\":\"farmer\",\"is_active\":\"1\",\"is_verified\":\"0\",\"last_login\":null,\"created_at\":\"2025-12-30 17:15:32\",\"updated_at\":\"2025-12-30 17:15:32\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'high', '2025-12-30 11:26:34'),
(39, 49, 'export_users', 'user', 'export', NULL, NULL, '3 users exported', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'low', '2025-12-30 11:27:41'),
(40, 49, 'export_users', 'user', 'export', NULL, NULL, '3 users exported', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'low', '2025-12-30 11:30:05'),
(41, 49, 'admin_login', 'security', 'user', 49, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'low', '2025-12-30 12:08:18'),
(42, 49, 'create_user', 'user', 'user', 59, NULL, '{\"email\":\"dfdfddfd@fdfd.com\",\"first_name\":\"vdddf\",\"role\":\"farmer\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 12:14:14'),
(43, 49, 'update_user', 'user', 'user', 59, '{\"user_id\":\"59\",\"email\":\"dfdfddfd@fdfd.com\",\"phone\":\"4545445454\",\"password_hash\":\"$2y$10$9\\/pibDWsaIpYMSNRxiXFLeCEOrdE.yPgV3A1EtT5k97xgekNGRu\\/u\",\"first_name\":\"vdddf\",\"last_name\":\"\",\"profile_img_url\":\"uploads\\/profiles\\/default-avatar.jpg\",\"role\":\"farmer\",\"is_active\":\"1\",\"is_verified\":\"0\",\"last_login\":null,\"created_at\":\"2025-12-30 18:14:14\",\"updated_at\":\"2025-12-30 18:14:14\"}', '{\"action\":\"update_user\",\"csrf_token\":\"174331bbde8310710e2cdb654f6b7dfe34b48e6f291f51853a0d1ef2caa61197\",\"device_fingerprint\":\"TW96aWxsYS81LjAgKFdpbmRvd3MgTlQg\",\"first_name\":\"vdddf\",\"last_name\":\"\",\"email\":\"dfdfddfd@fdfd.com\",\"phone\":\"4545445454\",\"role\":\"farmer\",\"is_active\":\"0\",\"is_verified\":\"1\",\"user_id\":\"59\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 12:23:24'),
(44, 49, 'create_user', 'user', 'user', 60, NULL, '{\"email\":\"dfdfd@gmail.com\",\"first_name\":\"Mohatamim\",\"role\":\"farmer\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 12:36:18'),
(45, 49, 'update_user', 'user', 'user', 60, '{\"user_id\":\"60\",\"email\":\"dfdfd@gmail.com\",\"phone\":\"54543543545454\",\"password_hash\":\"$2y$10$80AaFvlDWvqigYb\\/cS0Sg.zgQIUrcTtxCWYhKyEoAgNmBPCiMDxOy\",\"first_name\":\"Mohatamim\",\"last_name\":\"dfdfsfsss\",\"profile_img_url\":\"uploads\\/profiles\\/default-avatar.jpg\",\"role\":\"farmer\",\"is_active\":\"1\",\"is_verified\":\"0\",\"last_login\":null,\"created_at\":\"2025-12-30 18:36:18\",\"updated_at\":\"2025-12-30 18:36:18\"}', '{\"action\":\"update_user\",\"csrf_token\":\"174331bbde8310710e2cdb654f6b7dfe34b48e6f291f51853a0d1ef2caa61197\",\"user_id\":\"60\",\"first_name\":\"Mohatamim\",\"last_name\":\"dfdfsfsss\",\"email\":\"dfdfd@gmail.com\",\"phone\":\"54543543545454\",\"role\":\"farmer\",\"is_active\":\"0\",\"is_verified\":\"1\",\"password\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 12:36:56'),
(46, 49, 'update_user', 'user', 'user', 60, '{\"user_id\":\"60\",\"email\":\"dfdfd@gmail.com\",\"phone\":\"54543543545454\",\"password_hash\":\"$2y$10$80AaFvlDWvqigYb\\/cS0Sg.zgQIUrcTtxCWYhKyEoAgNmBPCiMDxOy\",\"first_name\":\"Mohatamim\",\"last_name\":\"dfdfsfsss\",\"profile_img_url\":\"uploads\\/profiles\\/default-avatar.jpg\",\"role\":\"farmer\",\"is_active\":\"0\",\"is_verified\":\"1\",\"last_login\":null,\"created_at\":\"2025-12-30 18:36:18\",\"updated_at\":\"2025-12-30 18:36:56\"}', '{\"action\":\"update_user\",\"csrf_token\":\"174331bbde8310710e2cdb654f6b7dfe34b48e6f291f51853a0d1ef2caa61197\",\"user_id\":\"60\",\"first_name\":\"Mohatamim\",\"last_name\":\"dfdfsfsss\",\"email\":\"dfdfd@gmail.com\",\"phone\":\"54543543545454\",\"role\":\"farmer\",\"is_active\":\"0\",\"is_verified\":\"1\",\"password\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 12:36:56'),
(47, 49, 'update_user', 'user', 'user', 59, '{\"user_id\":\"59\",\"email\":\"dfdfddfd@fdfd.com\",\"phone\":\"4545445454\",\"password_hash\":\"$2y$10$9\\/pibDWsaIpYMSNRxiXFLeCEOrdE.yPgV3A1EtT5k97xgekNGRu\\/u\",\"first_name\":\"vdddf\",\"last_name\":\"\",\"profile_img_url\":\"uploads\\/profiles\\/default-avatar.jpg\",\"role\":\"farmer\",\"is_active\":\"0\",\"is_verified\":\"1\",\"last_login\":null,\"created_at\":\"2025-12-30 18:14:14\",\"updated_at\":\"2025-12-30 18:23:24\"}', '{\"action\":\"update_user\",\"csrf_token\":\"174331bbde8310710e2cdb654f6b7dfe34b48e6f291f51853a0d1ef2caa61197\",\"user_id\":\"59\",\"first_name\":\"vdddf\",\"last_name\":\"\",\"email\":\"dfdfddfd@fdfd.com\",\"phone\":\"4545445454\",\"role\":\"officer\",\"is_active\":\"0\",\"is_verified\":\"1\",\"password\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 12:38:01'),
(48, 49, 'update_user', 'user', 'user', 59, '{\"user_id\":\"59\",\"email\":\"dfdfddfd@fdfd.com\",\"phone\":\"4545445454\",\"password_hash\":\"$2y$10$9\\/pibDWsaIpYMSNRxiXFLeCEOrdE.yPgV3A1EtT5k97xgekNGRu\\/u\",\"first_name\":\"vdddf\",\"last_name\":\"\",\"profile_img_url\":\"uploads\\/profiles\\/default-avatar.jpg\",\"role\":\"officer\",\"is_active\":\"0\",\"is_verified\":\"1\",\"last_login\":null,\"created_at\":\"2025-12-30 18:14:14\",\"updated_at\":\"2025-12-30 18:38:01\"}', '{\"action\":\"update_user\",\"csrf_token\":\"174331bbde8310710e2cdb654f6b7dfe34b48e6f291f51853a0d1ef2caa61197\",\"user_id\":\"59\",\"first_name\":\"vdddf\",\"last_name\":\"\",\"email\":\"dfdfddfd@fdfd.com\",\"phone\":\"4545445454\",\"role\":\"officer\",\"is_active\":\"0\",\"is_verified\":\"1\",\"password\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 12:38:01'),
(49, 49, 'add_ip_rule', 'security', 'ip_rule', NULL, NULL, '{\"ip\":\"192.168.0.1\",\"type\":\"blacklist\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 12:51:50'),
(50, 49, 'delete_ip_rule', 'security', 'ip_rule', 14, '{\"rule_id\":\"14\",\"ip_address\":\"192.168.0.1\",\"ip_range_start\":null,\"ip_range_end\":null,\"rule_type\":\"blacklist\",\"country_code\":null,\"reason\":\"\",\"auto_created\":\"0\",\"created_by\":null,\"created_at\":\"2025-12-30 18:51:50\",\"expires_at\":null}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 12:55:30'),
(51, 49, 'admin_login', 'security', 'user', 49, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'low', '2025-12-30 13:15:02'),
(52, 49, 'add_ip_rule', 'security', 'ip_rule', NULL, NULL, '{\"ip\":\"192.168.1.1\",\"type\":\"blacklist\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 13:31:21'),
(53, 49, 'delete_ip_rule', 'security', 'ip_rule', 15, '{\"rule_id\":\"15\",\"ip_address\":\"192.168.1.1\",\"ip_range_start\":null,\"ip_range_end\":null,\"rule_type\":\"blacklist\",\"country_code\":null,\"reason\":\"\",\"auto_created\":\"0\",\"created_by\":null,\"created_at\":\"2025-12-30 19:31:21\",\"expires_at\":null}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 13:31:31'),
(54, 49, 'add_ip_rule', 'security', 'ip_rule', NULL, NULL, '{\"ip\":\"::1\",\"type\":\"blacklist\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 13:31:41'),
(55, 49, 'delete_ip_rule', 'security', 'ip_rule', 16, '{\"rule_id\":\"16\",\"ip_address\":\"::1\",\"ip_range_start\":null,\"ip_range_end\":null,\"rule_type\":\"blacklist\",\"country_code\":null,\"reason\":\"Blocked due to suspicious activity\",\"auto_created\":\"0\",\"created_by\":null,\"created_at\":\"2025-12-30 19:31:41\",\"expires_at\":null}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 13:31:48'),
(56, 49, 'disable_task', '', 'task', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 14:03:55'),
(57, 49, 'enable_task', '', 'task', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 14:03:57'),
(58, 49, 'disable_task', '', 'task', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 14:12:26'),
(59, 49, 'run_task', '', 'task', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 14:14:00'),
(60, 49, 'disable_task', '', 'task', 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 14:14:05'),
(61, 49, 'admin_login', 'security', 'user', 49, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'low', '2025-12-30 14:15:38'),
(62, 49, 'trigger_backup', 'backup', 'backup', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'high', '2025-12-30 14:18:54'),
(63, 49, 'trigger_backup', 'backup', 'backup', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'high', '2025-12-30 14:19:27'),
(64, 49, 'generate_report', 'report', 'report', NULL, NULL, '{\"type\":\"content_analytics\",\"format\":\"pdf\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'low', '2025-12-30 14:20:55'),
(65, 49, 'delete_report', 'report', 'report', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'low', '2025-12-30 14:21:33'),
(66, 49, 'delete_report', 'report', 'report', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'low', '2025-12-30 14:21:36'),
(67, 49, 'delete_report', 'report', 'report', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'low', '2025-12-30 14:21:40'),
(68, 49, 'trigger_backup', 'backup', 'backup', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'high', '2025-12-30 14:22:00'),
(69, 49, 'create_backup', 'system', 'backup', NULL, NULL, 'full', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 14:23:22'),
(70, 49, 'generate_report', 'report', 'report', NULL, NULL, '{\"type\":\"user_summary\",\"format\":\"pdf\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'low', '2025-12-30 14:32:28'),
(71, 49, 'admin_login', 'security', 'user', 49, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'low', '2025-12-30 17:20:10'),
(72, 49, 'delete_user', 'user', 'user', 61, '{\"user_id\":\"61\",\"email\":\"naxoce1308@cameltok.com\",\"phone\":\"656565655454\",\"password_hash\":\"$2y$10$HwlbTyCv2Y4ZSk2BVLzb1eftnBZyJIbvPiof9G1wAPsRGL5zGPMjG\",\"first_name\":\"cv cvddd\",\"last_name\":\"\",\"profile_img_url\":\"uploads\\/profiles\\/profile_61_1767108114.JPG\",\"role\":\"farmer\",\"is_active\":\"1\",\"is_verified\":\"1\",\"last_login\":null,\"created_at\":\"2025-12-30 21:21:10\",\"updated_at\":\"2025-12-30 21:21:54\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'high', '2025-12-30 17:21:02'),
(73, 49, 'delete_user', 'user', 'user', 60, '{\"user_id\":\"60\",\"email\":\"dfdfd@gmail.com\",\"phone\":\"54543543545454\",\"password_hash\":\"$2y$10$80AaFvlDWvqigYb\\/cS0Sg.zgQIUrcTtxCWYhKyEoAgNmBPCiMDxOy\",\"first_name\":\"Mohatamim\",\"last_name\":\"dfdfsfsss\",\"profile_img_url\":\"uploads\\/profiles\\/default-avatar.jpg\",\"role\":\"farmer\",\"is_active\":\"0\",\"is_verified\":\"1\",\"last_login\":null,\"created_at\":\"2025-12-30 18:36:18\",\"updated_at\":\"2025-12-30 18:36:56\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'high', '2025-12-30 17:21:05'),
(74, 49, 'update_user', 'user', 'user', 59, '{\"user_id\":\"59\",\"email\":\"dfdfddfd@fdfd.com\",\"phone\":\"4545445454\",\"password_hash\":\"$2y$10$9\\/pibDWsaIpYMSNRxiXFLeCEOrdE.yPgV3A1EtT5k97xgekNGRu\\/u\",\"first_name\":\"vdddf\",\"last_name\":\"\",\"profile_img_url\":\"uploads\\/profiles\\/default-avatar.jpg\",\"role\":\"officer\",\"is_active\":\"0\",\"is_verified\":\"1\",\"last_login\":null,\"created_at\":\"2025-12-30 18:14:14\",\"updated_at\":\"2025-12-30 18:38:01\"}', '{\"action\":\"update_user\",\"csrf_token\":\"24aad8141fa4c94f2ea77d8f8608c77487a6f1f21fd27763160dd2bf5a25be4d\",\"user_id\":\"59\",\"first_name\":\"vdddf\",\"last_name\":\"\",\"email\":\"dfdfddfd@fdfd.com\",\"phone\":\"4545445454\",\"role\":\"officer\",\"is_active\":\"1\",\"is_verified\":\"1\",\"password\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 17:21:30'),
(75, 49, 'update_user', 'user', 'user', 59, '{\"user_id\":\"59\",\"email\":\"dfdfddfd@fdfd.com\",\"phone\":\"4545445454\",\"password_hash\":\"$2y$10$9\\/pibDWsaIpYMSNRxiXFLeCEOrdE.yPgV3A1EtT5k97xgekNGRu\\/u\",\"first_name\":\"vdddf\",\"last_name\":\"\",\"profile_img_url\":\"uploads\\/profiles\\/default-avatar.jpg\",\"role\":\"officer\",\"is_active\":\"1\",\"is_verified\":\"1\",\"last_login\":null,\"created_at\":\"2025-12-30 18:14:14\",\"updated_at\":\"2025-12-30 23:21:30\"}', '{\"action\":\"update_user\",\"csrf_token\":\"24aad8141fa4c94f2ea77d8f8608c77487a6f1f21fd27763160dd2bf5a25be4d\",\"user_id\":\"59\",\"first_name\":\"vdddf\",\"last_name\":\"\",\"email\":\"dfdfddfd@fdfd.com\",\"phone\":\"4545445454\",\"role\":\"officer\",\"is_active\":\"1\",\"is_verified\":\"1\",\"password\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 17:21:30'),
(76, 49, 'update_user', 'user', 'user', 46, '{\"user_id\":\"46\",\"email\":\"mohatamim1234@gmail.com\",\"phone\":\"01609036435\",\"password_hash\":\"$2y$10$E6VY9PEQLHLcZtJ8p3zx9.1KXtk2XO0EEtxF2SMtnD7nl8KbRV8pK\",\"first_name\":\"Mohatamim\",\"last_name\":\"Jame\",\"profile_img_url\":\"uploads\\/profiles\\/profile_46_1767024301.JPG\",\"role\":\"admin\",\"is_active\":\"1\",\"is_verified\":\"1\",\"last_login\":\"2025-12-30 12:57:15\",\"created_at\":\"2025-12-29 20:09:36\",\"updated_at\":\"2025-12-30 14:04:15\"}', '{\"action\":\"update_user\",\"csrf_token\":\"24aad8141fa4c94f2ea77d8f8608c77487a6f1f21fd27763160dd2bf5a25be4d\",\"user_id\":\"46\",\"first_name\":\"Mohatamim\",\"last_name\":\"Haque\",\"email\":\"mohatamim1234@gmail.com\",\"phone\":\"01609036435\",\"role\":\"admin\",\"is_active\":\"1\",\"is_verified\":\"1\",\"password\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 17:21:58'),
(77, 49, 'update_user', 'user', 'user', 46, '{\"user_id\":\"46\",\"email\":\"mohatamim1234@gmail.com\",\"phone\":\"01609036435\",\"password_hash\":\"$2y$10$E6VY9PEQLHLcZtJ8p3zx9.1KXtk2XO0EEtxF2SMtnD7nl8KbRV8pK\",\"first_name\":\"Mohatamim\",\"last_name\":\"Haque\",\"profile_img_url\":\"uploads\\/profiles\\/profile_46_1767024301.JPG\",\"role\":\"admin\",\"is_active\":\"1\",\"is_verified\":\"1\",\"last_login\":\"2025-12-30 12:57:15\",\"created_at\":\"2025-12-29 20:09:36\",\"updated_at\":\"2025-12-30 23:21:58\"}', '{\"action\":\"update_user\",\"csrf_token\":\"24aad8141fa4c94f2ea77d8f8608c77487a6f1f21fd27763160dd2bf5a25be4d\",\"user_id\":\"46\",\"first_name\":\"Mohatamim\",\"last_name\":\"Haque\",\"email\":\"mohatamim1234@gmail.com\",\"phone\":\"01609036435\",\"role\":\"admin\",\"is_active\":\"1\",\"is_verified\":\"1\",\"password\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'medium', '2025-12-30 17:21:58');

-- --------------------------------------------------------

--
-- Table structure for table `admin_ip_rules`
--

CREATE TABLE `admin_ip_rules` (
  `rule_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `ip_range_start` varchar(45) DEFAULT NULL,
  `ip_range_end` varchar(45) DEFAULT NULL,
  `rule_type` enum('whitelist','blacklist','geoblock') NOT NULL,
  `country_code` varchar(2) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `auto_created` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_login_attempts`
--

CREATE TABLE `admin_login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `success` tinyint(1) DEFAULT 0,
  `failure_reason` varchar(100) DEFAULT NULL,
  `geo_country` varchar(100) DEFAULT NULL,
  `geo_city` varchar(100) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_login_attempts`
--

INSERT INTO `admin_login_attempts` (`id`, `ip_address`, `email`, `attempted_at`, `success`, `failure_reason`, `geo_country`, `geo_city`, `user_agent`) VALUES
(1, '::1', 'mohatamim1234@gmail.com', '2025-12-30 05:48:14', 0, 'Invalid password', NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36'),
(2, '::1', 'mohatamimhaque1234@gmail.com', '2025-12-30 05:51:46', 0, 'User not found or not admin', NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36'),
(3, '::1', 'mohatamim1234@gmail.com', '2025-12-30 05:52:08', 0, 'Invalid password', NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36'),
(4, '::1', 'admin@smartcashi.com', '2025-12-30 05:57:55', 1, NULL, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36'),
(5, '::1', 'admin@smartcashi.com', '2025-12-30 06:01:30', 1, NULL, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36'),
(6, '::1', 'admin@smartcashi.com', '2025-12-30 06:04:39', 1, NULL, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36'),
(7, '::1', 'admin@smartcashi.com', '2025-12-30 06:06:30', 1, NULL, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36'),
(8, '::1', 'admin@smartcashi.com', '2025-12-30 06:08:06', 1, NULL, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36'),
(9, '::1', 'admin@smartcashi.com', '2025-12-30 06:19:49', 1, NULL, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36'),
(10, '127.0.0.1', 'admin@smartcashi.com', '2025-12-30 08:14:10', 1, NULL, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0'),
(11, '127.0.0.1', 'admin@smartcashi.com', '2025-12-30 08:48:52', 1, NULL, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0'),
(12, '127.0.0.1', 'admin@smartcashi.com', '2025-12-30 08:51:50', 1, NULL, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0'),
(13, '127.0.0.1', 'admin@smartcashi.com', '2025-12-30 08:52:01', 1, NULL, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0'),
(14, '127.0.0.1', 'admin@smartcashi.com', '2025-12-30 08:52:12', 1, NULL, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0'),
(15, '127.0.0.1', 'admin@smartcashi.com', '2025-12-30 09:13:38', 1, NULL, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0'),
(16, '127.0.0.1', 'admin@smartcashi.com', '2025-12-30 09:24:33', 1, NULL, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0'),
(17, '127.0.0.1', 'admin@smartcashi.com', '2025-12-30 09:33:35', 1, NULL, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0'),
(18, '127.0.0.1', 'admin@smartcashi.com', '2025-12-30 09:35:55', 1, NULL, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0'),
(19, '127.0.0.1', 'admin@smartcashi.com', '2025-12-30 09:39:54', 1, NULL, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0'),
(20, '127.0.0.1', 'admin@smartcashi.com', '2025-12-30 10:02:33', 1, NULL, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0'),
(21, '::1', 'admin@smartcashi.com', '2025-12-30 10:06:07', 1, NULL, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36'),
(22, '::1', 'admin@smartcashi.com', '2025-12-30 10:13:27', 0, 'Invalid password', NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36'),
(23, '::1', 'admin@smartcashi.com', '2025-12-30 11:08:42', 1, NULL, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36'),
(24, '::1', 'admin@smartcashi.com', '2025-12-30 12:08:18', 1, NULL, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36'),
(25, '::1', 'admin@smartcashi.com', '2025-12-30 13:15:02', 1, NULL, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36'),
(26, '::1', 'admin@smartcashi.com', '2025-12-30 14:15:38', 1, NULL, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36'),
(27, '::1', 'admin@smartcashi.com', '2025-12-30 17:20:10', 1, NULL, NULL, NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36');

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `notification_type` enum('security','system','report','user','backup','error','warning','info','success') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `action_url` varchar(255) DEFAULT NULL,
  `action_text` varchar(100) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `is_email_sent` tinyint(1) DEFAULT 0,
  `email_sent_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_profiles`
--

CREATE TABLE `admin_profiles` (
  `profile_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `access_level` enum('super_admin','admin','moderator') DEFAULT 'admin',
  `responsibilities` text DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_sessions`
--

CREATE TABLE `admin_sessions` (
  `session_id` varchar(128) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `device_fingerprint` varchar(64) DEFAULT NULL,
  `device_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`device_info`)),
  `geo_location` varchar(255) DEFAULT NULL,
  `login_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `terminated_reason` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_sessions`
--

INSERT INTO `admin_sessions` (`session_id`, `user_id`, `ip_address`, `user_agent`, `device_fingerprint`, `device_info`, `geo_location`, `login_at`, `last_activity`, `expires_at`, `is_active`, `terminated_reason`) VALUES
('1cdebb290796e3a10568827b850883dc9a372675b48725095f6f4a41e02f497c', 49, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '000000003359246a', NULL, NULL, '2025-12-30 09:33:35', '2025-12-30 09:33:35', '2025-12-30 05:03:35', 1, NULL),
('2bf507c3a42f1ee8f55324492e274a8c8ed6d8fd8206e4e23a192d9f2b145ade', 49, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '000000003359246a', NULL, NULL, '2025-12-30 09:24:33', '2025-12-30 09:24:33', '2025-12-30 04:54:33', 1, NULL),
('2dafdb133e695605bfd9e9066d84ac8e76f68b28958e029bb759374170ea06f4', 49, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '000000003359246a', NULL, NULL, '2025-12-30 08:52:12', '2025-12-30 08:52:12', '2025-12-30 04:22:12', 1, NULL),
('37f215f1c3895262e6e24672792fde99899e808513654d2be20cd0d09765687f', 49, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '00000000329b62ec', NULL, NULL, '2025-12-30 17:20:10', '2025-12-30 17:20:10', '2025-12-30 12:50:10', 1, NULL),
('40e8b8590e4899630c65f1906d9947a016bad243b06e697819cca21704a0e085', 49, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '000000003359246a', NULL, NULL, '2025-12-30 08:51:50', '2025-12-30 08:51:50', '2025-12-30 04:21:50', 1, NULL),
('4e151270467bbc086a4103b7ed18ab5145a22918fdf124371249c0228d668c60', 49, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '00000000329b62ec', NULL, NULL, '2025-12-30 10:06:07', '2025-12-30 10:06:07', '2025-12-30 05:36:07', 1, NULL),
('73c13de3146613b160ab51c756ae03c1d1aa66de09aa6502dfc77ce1d5c326c3', 49, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '000000003359246a', NULL, NULL, '2025-12-30 09:39:53', '2025-12-30 09:39:53', '2025-12-30 05:09:53', 1, NULL),
('936c3d91cc08956f66bf18005be53859c06b3d1880451b45137e17c1adf44315', 49, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '000000003359246a', NULL, NULL, '2025-12-30 08:14:10', '2025-12-30 08:14:10', '2025-12-30 03:44:10', 1, NULL),
('951f340ee6570b0fbdb390f617259b972efffe9fc137b38ed1d61191d41d6505', 49, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '000000003359246a', NULL, NULL, '2025-12-30 09:35:55', '2025-12-30 09:35:55', '2025-12-30 05:05:55', 1, NULL),
('b242ed036c4b332c155b1acf5369148a534656ea853c57ab21af038b2e5b31f1', 49, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '000000003359246a', NULL, NULL, '2025-12-30 08:52:01', '2025-12-30 08:52:01', '2025-12-30 04:22:01', 1, NULL),
('ce84e76daf2bf3cf40dad3ca8320acaaf8ec2e828cd794f536962633ed16c50d', 49, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '00000000329b62ec', NULL, NULL, '2025-12-30 14:15:38', '2025-12-30 14:15:38', '2025-12-30 09:45:38', 1, NULL),
('cea69fcf736f376f1ec062edf8b31e1fc43e7d7e0b3e80b8702eb027303e5485', 49, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '000000003359246a', NULL, NULL, '2025-12-30 10:02:33', '2025-12-30 10:04:28', '2025-12-30 05:32:33', 0, 'user_logout'),
('dc63831c76c681a9fbe7a0ee61f061ea80a47e81eadd907a302d9b3bda8b9e38', 49, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '000000003359246a', NULL, NULL, '2025-12-30 09:13:38', '2025-12-30 09:13:38', '2025-12-30 04:43:38', 1, NULL),
('e6fa0ed68159f33e6de1fa04df1771cf6a42b9d73b1f501483b80fa46c6d0dda', 49, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '00000000329b62ec', NULL, NULL, '2025-12-30 12:08:18', '2025-12-30 12:08:18', '2025-12-30 07:38:18', 1, NULL),
('f16e3653c6a47fc95d933287748a2926f86871be5fe56abbafa9dccb8b1c3861', 49, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '00000000329b62ec', NULL, NULL, '2025-12-30 13:15:02', '2025-12-30 13:15:02', '2025-12-30 08:45:02', 1, NULL),
('f6f39b1f073cd04f7340f7e2fbadb08cb5f26315775d209f9169e9de37fa4a16', 49, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', '000000003359246a', NULL, NULL, '2025-12-30 08:48:52', '2025-12-30 08:48:52', '2025-12-30 04:18:52', 1, NULL),
('fa715270513cb34324b950adbb0445ac25848a04fe94080cf9a99f680f26de1e', 49, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '00000000329b62ec', NULL, NULL, '2025-12-30 11:08:42', '2025-12-30 11:08:42', '2025-12-30 06:38:42', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admin_settings`
--

CREATE TABLE `admin_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json','encrypted') DEFAULT 'string',
  `setting_group` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_sensitive` tinyint(1) DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_settings`
--

INSERT INTO `admin_settings` (`setting_id`, `setting_key`, `setting_value`, `setting_type`, `setting_group`, `description`, `is_sensitive`, `updated_by`, `updated_at`) VALUES
(1, 'session_timeout', '1800', 'number', 'security', 'Admin session timeout in seconds', 0, NULL, '2025-12-30 05:45:11'),
(2, 'max_login_attempts', '5', 'number', 'security', 'Maximum failed login attempts before lockout', 0, NULL, '2025-12-30 05:45:11'),
(3, 'lockout_duration', '900', 'number', 'security', 'Lockout duration in seconds after max failed attempts', 0, NULL, '2025-12-30 05:45:11'),
(4, 'require_2fa', '0', 'boolean', 'security', 'Require 2FA for all admin users', 0, NULL, '2025-12-30 05:45:11'),
(5, 'password_min_length', '8', 'number', 'security', 'Minimum password length', 0, NULL, '2025-12-30 05:45:11'),
(6, 'password_require_uppercase', '1', 'boolean', 'security', 'Require uppercase in password', 0, NULL, '2025-12-30 05:45:11'),
(7, 'password_require_lowercase', '1', 'boolean', 'security', 'Require lowercase in password', 0, NULL, '2025-12-30 05:45:11'),
(8, 'password_require_number', '1', 'boolean', 'security', 'Require number in password', 0, NULL, '2025-12-30 05:45:11'),
(9, 'password_require_special', '0', 'boolean', 'security', 'Require special character in password', 0, NULL, '2025-12-30 05:45:11'),
(10, 'password_expiry_days', '0', 'number', 'security', 'Password expiry in days (0 = never)', 0, NULL, '2025-12-30 05:45:11'),
(11, 'password_history_count', '5', 'number', 'security', 'Number of previous passwords to remember', 0, NULL, '2025-12-30 05:45:11'),
(12, 'enable_geo_blocking', '0', 'boolean', 'security', 'Enable geo-location based blocking', 0, NULL, '2025-12-30 05:45:11'),
(13, 'allowed_countries', '[]', 'json', 'security', 'List of allowed country codes for admin access', 0, NULL, '2025-12-30 05:45:11'),
(14, 'enable_ip_whitelist', '0', 'boolean', 'security', 'Enable IP whitelist for admin access', 0, NULL, '2025-12-30 05:45:11'),
(15, 'enable_honeypot', '1', 'boolean', 'security', 'Enable honeypot trap fields', 0, NULL, '2025-12-30 05:45:11'),
(16, 'enable_device_fingerprint', '1', 'boolean', 'security', 'Enable device fingerprinting', 0, NULL, '2025-12-30 05:45:11'),
(17, 'trusted_device_expiry_days', '30', 'number', 'security', 'Trusted device expiry in days', 0, NULL, '2025-12-30 05:45:11'),
(18, 'maintenance_mode', '0', 'boolean', 'system', 'Enable maintenance mode', 0, NULL, '2025-12-30 05:45:11'),
(19, 'maintenance_message', 'System is under maintenance. Please try again later.', 'string', 'system', 'Maintenance mode message', 0, NULL, '2025-12-30 05:45:11'),
(20, 'backup_retention_days', '30', 'number', 'backup', 'Number of days to retain backups', 0, NULL, '2025-12-30 05:45:11'),
(21, 'auto_backup_enabled', '1', 'boolean', 'backup', 'Enable automatic daily backups', 0, NULL, '2025-12-30 05:45:11'),
(22, 'rate_limit_requests', '100', 'number', 'security', 'Maximum requests per minute', 0, NULL, '2025-12-30 05:45:11'),
(23, 'rate_limit_window', '60', 'number', 'security', 'Rate limit window in seconds', 0, NULL, '2025-12-30 05:45:11');

-- --------------------------------------------------------

--
-- Table structure for table `admin_trusted_devices`
--

CREATE TABLE `admin_trusted_devices` (
  `device_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `device_fingerprint` varchar(64) NOT NULL,
  `device_name` varchar(100) DEFAULT NULL,
  `browser` varchar(100) DEFAULT NULL,
  `browser_version` varchar(50) DEFAULT NULL,
  `os` varchar(100) DEFAULT NULL,
  `os_version` varchar(50) DEFAULT NULL,
  `device_type` enum('desktop','mobile','tablet','unknown') DEFAULT 'unknown',
  `last_used` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_ip` varchar(45) DEFAULT NULL,
  `is_trusted` tinyint(1) DEFAULT 1,
  `trust_expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_trusted_devices`
--

INSERT INTO `admin_trusted_devices` (`device_id`, `user_id`, `device_fingerprint`, `device_name`, `browser`, `browser_version`, `os`, `os_version`, `device_type`, `last_used`, `last_ip`, `is_trusted`, `trust_expires_at`, `created_at`) VALUES
(1, 49, '00000000329b62ec', 'Chrome on Windows', 'Chrome', NULL, 'Windows', NULL, 'unknown', '2025-12-30 17:20:10', '::1', 1, '2026-01-29 10:06:07', '2025-12-30 10:06:07');

-- --------------------------------------------------------

--
-- Table structure for table `advisories`
--

CREATE TABLE `advisories` (
  `advisory_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `advisory_type` enum('general','weather','seasonal','pest_control','irrigation','market') DEFAULT 'general',
  `target_crops` varchar(255) DEFAULT NULL,
  `target_region` varchar(100) DEFAULT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `views` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `advisories`
--

INSERT INTO `advisories` (`advisory_id`, `created_by`, `title`, `content`, `advisory_type`, `target_crops`, `target_region`, `priority`, `valid_from`, `valid_to`, `is_active`, `views`, `created_at`, `updated_at`) VALUES
(1, 46, 'wedewrrereerer', 'ererererererere', 'general', NULL, NULL, 'medium', NULL, NULL, 1, 0, '2025-12-29 16:30:36', '2025-12-29 16:30:36');

-- --------------------------------------------------------

--
-- Table structure for table `ai_chat_logs`
--

CREATE TABLE `ai_chat_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_message` text DEFAULT NULL,
  `ai_response` text DEFAULT NULL,
  `message_type` enum('general','crop_advice','disease','weather','market') DEFAULT 'general',
  `language` enum('bangla','english') DEFAULT 'english',
  `sentiment` varchar(50) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL COMMENT '1-5 rating',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_recommendations`
--

CREATE TABLE `ai_recommendations` (
  `recommendation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `crop_id` int(11) DEFAULT NULL,
  `recommendation_type` enum('crop_selection','planting_time','irrigation','fertilizer','pest_control','harvesting') NOT NULL,
  `recommendation` text NOT NULL,
  `confidence_score` decimal(5,2) DEFAULT NULL,
  `based_on` text DEFAULT NULL COMMENT 'weather, soil, history, etc',
  `is_accepted` tinyint(1) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `alerts`
--

CREATE TABLE `alerts` (
  `alert_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `alert_type` enum('weather','disease','market','system','advisory','crop','community') DEFAULT 'system',
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `priority` enum('low','medium','high','critical') DEFAULT 'low',
  `category` varchar(100) DEFAULT NULL,
  `action_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `sent_via` enum('app','email','sms','all') DEFAULT 'app',
  `created_by` int(11) DEFAULT NULL COMMENT 'officer/admin who created alert',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `api_request_logs`
--

CREATE TABLE `api_request_logs` (
  `log_id` int(11) NOT NULL,
  `endpoint` varchar(255) DEFAULT NULL,
  `method` varchar(10) DEFAULT NULL,
  `response_code` int(11) DEFAULT NULL,
  `response_time_ms` int(11) DEFAULT NULL,
  `request_size` int(11) DEFAULT NULL,
  `response_size` int(11) DEFAULT NULL,
  `memory_usage` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `articles`
--

CREATE TABLE `articles` (
  `article_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `title_bn` varchar(255) DEFAULT NULL,
  `content` text NOT NULL,
  `excerpt` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `language` enum('english','bangla','both') DEFAULT 'both',
  `views` int(11) DEFAULT 0,
  `likes` int(11) DEFAULT 0,
  `is_published` tinyint(1) DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_trail`
--

CREATE TABLE `audit_trail` (
  `audit_id` int(11) NOT NULL,
  `table_name` varchar(100) NOT NULL,
  `record_id` int(11) NOT NULL,
  `action` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_data`)),
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_data`)),
  `changed_fields` text DEFAULT NULL,
  `change_summary` varchar(500) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_role` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `backup_records`
--

CREATE TABLE `backup_records` (
  `backup_id` int(11) NOT NULL,
  `backup_type` enum('full','incremental','database','files','config') NOT NULL,
  `backup_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `checksum` varchar(64) DEFAULT NULL,
  `checksum_type` varchar(20) DEFAULT 'sha256',
  `tables_included` text DEFAULT NULL,
  `rows_count` int(11) DEFAULT NULL,
  `compression` enum('none','gzip','zip') DEFAULT 'gzip',
  `encryption` enum('none','aes256') DEFAULT 'none',
  `status` enum('pending','in_progress','completed','failed','deleted','corrupted') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `backup_records`
--

INSERT INTO `backup_records` (`backup_id`, `backup_type`, `backup_name`, `file_path`, `file_size`, `checksum`, `checksum_type`, `tables_included`, `rows_count`, `compression`, `encryption`, `status`, `error_message`, `duration_seconds`, `created_by`, `created_at`, `expires_at`, `deleted_at`) VALUES
(1, 'full', NULL, NULL, NULL, NULL, 'sha256', NULL, NULL, 'gzip', 'none', 'in_progress', NULL, NULL, NULL, '2025-12-30 14:18:54', NULL, NULL),
(2, 'full', NULL, NULL, NULL, NULL, 'sha256', NULL, NULL, 'gzip', 'none', 'in_progress', NULL, NULL, NULL, '2025-12-30 14:19:27', NULL, NULL),
(3, 'full', NULL, NULL, NULL, NULL, 'sha256', NULL, NULL, 'gzip', 'none', 'in_progress', NULL, NULL, NULL, '2025-12-30 14:22:00', NULL, NULL),
(4, 'full', NULL, 'C:\\xampp\\htdocs\\smartcashi/backups/2025-12-30/backup_2025-12-30_15-23-22_full', NULL, NULL, 'sha256', NULL, NULL, 'gzip', 'none', 'completed', NULL, NULL, 49, '2025-12-30 14:23:22', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comment_likes`
--

CREATE TABLE `comment_likes` (
  `like_id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `community_posts`
--

CREATE TABLE `community_posts` (
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `post_type` enum('question','discussion','tip','success_story','problem') DEFAULT 'discussion',
  `image_url` varchar(255) DEFAULT NULL,
  `likes` int(11) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `is_pinned` tinyint(1) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_approved` tinyint(1) DEFAULT 1,
  `approved_by` int(11) DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `community_posts`
--

INSERT INTO `community_posts` (`post_id`, `user_id`, `title`, `content`, `category`, `post_type`, `image_url`, `likes`, `views`, `is_pinned`, `is_featured`, `is_approved`, `approved_by`, `tags`, `created_at`, `updated_at`) VALUES
(60, 47, 'Best Time to Plant Maize in Rainy Season', 'Hello farmers! I want to know the best time to plant maize during the rainy season. In my area, rains start in March. Should I wait for consistent rain or plant early? Any advice would be appreciated!', 'Crop Problems', 'discussion', NULL, 12, 145, 0, 0, 1, NULL, 'maize,planting,rainy-season', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(61, 47, 'Organic Fertilizer Recipe That Works!', 'I have been using this organic fertilizer for 3 years now and the results are amazing! Mix cow manure, ash, and decomposed leaves in 3:1:1 ratio. Leave it for 2 weeks before applying. My tomatoes have never been better!', 'Fertilizer Tips', '', NULL, 28, 312, 0, 1, 1, NULL, 'organic,fertilizer,tips', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(62, 47, 'Dealing with Armyworms - Need Help', 'My maize field has been attacked by armyworms. I have tried some local remedies but they keep coming back. Has anyone dealt with this successfully? What pesticides or natural methods work best?', 'Pest Control', 'question', NULL, 8, 89, 0, 0, 1, NULL, 'armyworms,pest-control,maize', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(63, 47, 'Successful Greenhouse Tomato Harvest!', 'Just harvested 500kg of tomatoes from my small greenhouse! It took 3 months from transplanting. The investment in drip irrigation really paid off. Happy to share my experience with anyone interested in greenhouse farming.', 'Best Practices', 'discussion', NULL, 45, 523, 0, 1, 1, NULL, 'greenhouse,tomatoes,success,harvest', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(64, 47, 'Looking for Buyers - Fresh Cabbages Available', 'I have 2 tons of fresh cabbages ready for sale. They are organic and Grade A quality. Located in Central Region. Contact me if interested or if you know potential buyers. Reasonable prices!', 'Market Updates', 'discussion', NULL, 15, 234, 0, 0, 1, NULL, 'cabbages,market,selling', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(65, 47, 'Rain Water Harvesting Tips for Small Farms', 'With climate change affecting our rainfall patterns, I started harvesting rainwater last year. Built a simple system using old drums and gutters. Collected over 5000 liters during last rainy season. Let me know if you want details!', 'Best Practices', '', NULL, 34, 421, 0, 0, 1, NULL, 'water-harvesting,irrigation,climate', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(66, 47, 'Question About Soil pH for Vegetables', 'I tested my soil and the pH is 5.2. I want to grow vegetables like cabbages, carrots and tomatoes. Do I need to add lime? How much lime per acre? Please advise!', 'Crop Problems', 'question', NULL, 11, 156, 0, 0, 1, NULL, 'soil,pH,vegetables,lime', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(67, 47, 'Banana Farming: My 2-Year Journey', 'Started banana farming 2 years ago with 200 suckers. Today I have over 500 plants and already made my first sale. The key is proper spacing, mulching, and regular weeding. Bananas are very profitable if done right!', 'General Discussion', 'discussion', NULL, 52, 678, 0, 1, 1, NULL, 'bananas,farming,success,profit', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(68, 47, 'Free Agricultural Extension Training Next Week', 'The Ministry of Agriculture is organizing free training on modern farming techniques next Wednesday at the district office. Topics include crop rotation, pest management, and post-harvest handling. Registration is open!', 'General Discussion', 'discussion', NULL, 67, 892, 1, 1, 1, NULL, 'training,agriculture,free,event', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(69, 47, 'Best Chicken Breeds for Egg Production', 'I want to start poultry farming focusing on egg production. Which breeds are best for our climate? I am considering Kuroiler, Rhode Island Red, or local crosses. Looking for advice from experienced poultry farmers.', 'General Discussion', 'question', NULL, 19, 267, 0, 0, 1, NULL, 'poultry,chickens,eggs,breeds', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(70, 47, 'Coffee Farming in High Altitude Areas', 'For farmers in mountainous regions, coffee is a great crop! The cool climate and altitude produce high-quality arabica beans. I have been growing coffee for 5 years and the international market demand is strong.', 'Best Practices', '', NULL, 23, 345, 0, 0, 1, NULL, 'coffee,altitude,arabica,export', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(71, 47, 'Beekeeping: Additional Income for Farmers', 'Started beekeeping as a side project and now I harvest 50kg of honey every 3 months! Bees also help pollinate my fruit trees. Initial investment is low and maintenance is minimal. Highly recommend it!', 'Best Practices', 'discussion', NULL, 41, 534, 0, 1, 1, NULL, 'beekeeping,honey,income,bees', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(72, 47, 'Dealing with Post-Harvest Losses', 'I lost 30% of my tomato harvest due to poor storage. What are the best storage methods for perishable crops? Should I invest in a cold room or are there cheaper alternatives? Need practical solutions.', 'Crop Problems', 'question', NULL, 14, 189, 0, 0, 1, NULL, 'storage,post-harvest,losses,tomatoes', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(73, 47, 'Government Subsidy Programs - How to Apply', 'Many farmers do not know about the government subsidies available for seeds, fertilizers, and equipment. I got 50% subsidy for my tractor. Visit the agriculture office in your district for application forms!', 'General Discussion', '', NULL, 78, 1023, 1, 1, 1, NULL, 'subsidy,government,support,farming', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(74, 47, 'Intercropping Maize with Beans - Results', 'Tried intercropping this season and the results are impressive! Planted beans between maize rows. The beans fix nitrogen in soil, reducing fertilizer costs. Plus I get two crops from one plot. Win-win!', 'Best Practices', 'discussion', NULL, 36, 445, 0, 0, 1, NULL, 'intercropping,maize,beans,techniques', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(75, 47, 'Fish Farming in Small Ponds', 'Converted an unused pond into a fish farm. Stocked 1000 tilapia fingerlings 6 months ago. They are growing well! Fish farming can supplement farm income and provides protein for the family. Anyone else doing aquaculture?', 'General Discussion', 'discussion', NULL, 29, 378, 0, 0, 1, NULL, 'fish-farming,tilapia,aquaculture,pond', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(76, 47, 'Cassava Processing Business Opportunity', 'Raw cassava sells for very little, but processed cassava flour sells for 3x the price! I bought a simple grinding machine and now process cassava into flour. The market demand is huge. Consider value addition!', 'Market Updates', '', NULL, 44, 567, 0, 1, 1, NULL, 'cassava,processing,business,value-addition', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(77, 47, 'Avocado Trees: Long-term Investment', 'Planted 50 avocado trees 4 years ago. They just started bearing fruit this year. Each tree can produce 100-200 fruits. With export market prices, this is a great long-term investment for any farmer!', 'Best Practices', 'discussion', NULL, 38, 456, 0, 0, 1, NULL, 'avocado,trees,investment,export', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(78, 47, 'Weather Patterns Changing - Need Advice', 'The rains used to be predictable but now everything has changed. Last year we had drought, this year too much rain. How are other farmers adapting to these unpredictable weather patterns?', 'Weather Discussion', 'question', NULL, 17, 234, 0, 0, 1, NULL, 'weather,climate-change,adaptation', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(79, 47, 'Farmer Cooperative Benefits', 'Joined our local farmer cooperative last year - best decision ever! We get better prices for inputs through bulk buying, and negotiate better prices when selling. There is strength in numbers!', 'General Discussion', '', NULL, 56, 689, 0, 1, 1, NULL, 'cooperative,farmers,union,benefits', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(80, 47, 'Sweet Potato Vines as Animal Feed', 'Do not throw away sweet potato vines! They make excellent feed for cows, goats, and pigs. I dry them during harvest season and use them throughout the year. Free nutritious feed for livestock!', 'Fertilizer Tips', '', NULL, 25, 312, 0, 0, 1, NULL, 'sweet-potato,animal-feed,livestock,tips', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(81, 47, 'Extreme Weather Warning - Prepare Your Crops', 'The weather forecast is predicting heavy rains and strong winds next week. Fellow farmers, please take precautions! Stake your tall crops, clear drainage channels, and harvest mature produce. Stay safe!', 'Weather Discussion', 'discussion', NULL, 31, 423, 1, 0, 1, NULL, 'weather,warning,preparation,safety', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(82, 47, 'Tomato Blight Prevention Methods', 'Tomato blight has destroyed many farms in our area. I use a combination of crop rotation, proper spacing, and copper-based fungicides. Early detection is key! Check your plants daily for signs of disease.', 'Pest Control', '', NULL, 42, 378, 0, 1, 1, NULL, 'tomato,blight,disease,prevention', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(83, 47, 'Onion Market Prices Rising', 'Good news for onion farmers! Market prices have increased by 40% this month due to shortage. If you have stored onions, now is a good time to sell. Prices expected to remain high for next 2 months.', 'Market Updates', 'discussion', NULL, 68, 523, 0, 1, 1, NULL, 'onion,prices,market,selling', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(84, 47, 'Composting Kitchen Waste for Garden', 'Started composting all kitchen waste 6 months ago. Now I have rich organic fertilizer for my vegetable garden. No smell if done correctly. Layer green waste with brown waste and turn weekly.', 'Fertilizer Tips', '', NULL, 33, 298, 0, 0, 1, NULL, 'composting,organic,kitchen-waste,fertilizer', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(85, 47, 'Cabbage Worm Infestation - Urgent Help', 'My cabbage field is infested with green worms eating all the leaves! They multiply so fast. What is the best organic solution? I do not want to use harsh chemicals as these are for market sale.', 'Pest Control', 'question', NULL, 9, 134, 0, 0, 1, NULL, 'cabbage,worms,pest-control,organic', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(86, 47, 'Drip Irrigation Installation Guide', 'Installed drip irrigation on my 1-acre plot last month. Water consumption reduced by 60%! Initial cost was high but worth it. I can share step-by-step installation process if anyone is interested.', 'Best Practices', 'discussion', NULL, 51, 612, 0, 1, 1, NULL, 'drip-irrigation,water,installation,guide', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(87, 47, 'Heavy Rains Expected This Weekend', 'According to weather forecast, we are expecting 100mm of rain this weekend. Farmers with mature crops should consider early harvest. Those with young seedlings, provide drainage to prevent waterlogging.', 'Weather Discussion', 'discussion', NULL, 73, 845, 1, 1, 1, NULL, 'weather,rain,forecast,alert', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(88, 47, 'Pawpaw Farming: Quick Returns', 'Pawpaw trees start producing in just 9-12 months! They require minimal care and the fruit market is always strong. I planted 100 trees last year and already earning good income. Great for small farms.', 'General Discussion', 'discussion', NULL, 47, 521, 0, 0, 1, NULL, 'pawpaw,quick-returns,farming,profit', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(89, 47, 'Nitrogen Deficiency in Maize Plants', 'My maize plants are showing yellowing of lower leaves. I think it is nitrogen deficiency. Should I apply urea or use organic alternatives like manure? How much per plant? Need quick advice!', 'Fertilizer Tips', 'question', NULL, 16, 187, 0, 0, 1, NULL, 'nitrogen,deficiency,maize,fertilizer', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(90, 47, 'Best Practices for Seed Storage', 'Proper seed storage is crucial for next season. I store my seeds in airtight containers with silica gel packets in a cool, dry place. Seeds remain viable for 2-3 years this way. Never store in plastic bags!', 'Best Practices', '', NULL, 39, 412, 0, 0, 1, NULL, 'seed-storage,preservation,tips', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(91, 47, 'Potato Market Demand Increasing', 'Irish potato demand has increased significantly. Restaurants and food processors are looking for reliable suppliers. If you grow potatoes, consider forming a group to supply in bulk for better prices.', 'Market Updates', 'discussion', NULL, 54, 478, 0, 1, 1, NULL, 'potato,market,demand,supply', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(92, 47, 'Locust Swarm Warning in Northern Region', 'Attention farmers in northern districts! Desert locusts have been spotted crossing the border. Ministry of Agriculture is conducting aerial spraying. Cover your vegetable gardens and report any sightings immediately.', 'Pest Control', 'discussion', NULL, 91, 1234, 1, 1, 1, NULL, 'locust,warning,emergency,pest', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(93, 47, 'Mulching Benefits You Should Know', 'Started mulching my vegetable beds with grass clippings and crop residues. Soil moisture retention improved, weeds reduced by 80%, and soil temperature more stable. Plus, it adds organic matter as it decomposes!', 'Best Practices', '', NULL, 48, 567, 0, 1, 1, NULL, 'mulching,soil,water,conservation', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(94, 47, 'Drought Resistant Crops to Consider', 'With climate change bringing more frequent droughts, we need to think about drought-resistant crops. Sorghum, millet, cassava, and cowpeas perform well even with limited water. Diversify to reduce risk!', 'Weather Discussion', '', NULL, 62, 721, 0, 1, 1, NULL, 'drought,climate,crops,resilience', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(95, 47, 'Organic Pesticide from Neem Leaves', 'Neem leaf extract is an excellent organic pesticide! Crush 1kg neem leaves, soak in 10L water overnight, strain, and spray on crops. Effective against many pests and completely safe for consumption.', 'Pest Control', '', NULL, 71, 812, 0, 1, 1, NULL, 'neem,organic,pesticide,natural', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(96, 47, 'Green Pepper Prices Dropped Suddenly', 'Green pepper prices crashed this week due to oversupply. Many farmers harvested at the same time. This is why market coordination is important. Consider staggered planting to avoid such losses.', 'Market Updates', 'discussion', NULL, 43, 389, 0, 0, 1, NULL, 'pepper,prices,market,oversupply', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(97, 47, 'Yellowing Leaves on Tomato Plants', 'My tomato plants have yellow leaves at the bottom but the top looks healthy. Is this normal aging or a nutrient problem? Plants are 2 months old and flowering has started. Should I be worried?', 'Crop Problems', 'question', NULL, 12, 156, 0, 0, 1, NULL, 'tomato,yellowing,leaves,problem', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(98, 47, 'Foliar Feeding for Quick Nutrient Boost', 'Learned about foliar feeding - spraying liquid fertilizer directly on leaves. Plants absorb nutrients faster this way. Using it every 2 weeks on my vegetables with great results. Much more efficient than soil application!', 'Fertilizer Tips', '', NULL, 36, 401, 0, 0, 1, NULL, 'foliar,feeding,fertilizer,spray', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(99, 47, 'Hot Weather Tips for Livestock', 'With temperatures rising above 35°C, our livestock are suffering. Provide plenty of shade, fresh water multiple times daily, and avoid moving animals during hottest hours. Heat stress can be deadly!', 'Weather Discussion', '', NULL, 58, 634, 0, 1, 1, NULL, 'heat,livestock,weather,care', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(100, 47, 'Vertical Farming for Small Spaces', 'Do not have much land? Try vertical farming! I grow vegetables on stacked shelves using grow bags. Producing 3x more per square meter. Perfect for urban farmers or those with limited space.', 'Best Practices', 'discussion', NULL, 65, 789, 0, 1, 1, NULL, 'vertical-farming,space,urban,innovation', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(101, 47, 'Caterpillar Attack on Young Maize', 'Fall armyworms are destroying my young maize crop. They come out at night and hide during the day. What time is best to spray? Which pesticide works effectively against armyworms?', 'Pest Control', 'question', NULL, 18, 201, 0, 0, 1, NULL, 'caterpillar,armyworm,maize,control', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(102, 47, 'Export Market for Fresh Herbs', 'Just secured a contract to export fresh herbs to Dubai! Basil, mint, and coriander have huge demand in Gulf countries. The packaging and certification requirements are strict but profits are worth it.', 'Market Updates', 'discussion', NULL, 82, 967, 0, 1, 1, NULL, 'export,herbs,international,market', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(103, 47, 'Companion Planting Guide', 'Companion planting really works! Plant marigolds around tomatoes to repel pests. Beans fix nitrogen for maize. Onions and carrots grow well together. This is nature is way - plants helping each other!', 'Best Practices', '', NULL, 55, 623, 0, 1, 1, NULL, 'companion-planting,organic,natural', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(104, 47, 'Stunted Growth in Pepper Plants', 'My pepper plants stopped growing after transplanting. They are 3 weeks in the field but no new growth. Leaves look healthy but plants are not getting bigger. What could be wrong? Soil or pest issue?', 'Crop Problems', 'question', NULL, 14, 178, 0, 0, 1, NULL, 'pepper,stunted-growth,problem', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(105, 47, 'Banana Weevil Control Methods', 'Banana weevils are a serious problem. They bore into the stem causing plants to collapse. Use pheromone traps, remove infected plants immediately, and apply neem cake around the base. Regular monitoring is essential!', 'Pest Control', '', NULL, 44, 512, 0, 0, 1, NULL, 'banana,weevil,control,management', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(106, 47, 'Cold Front Coming Next Week', 'Weather update: Cold front expected next week with temperatures dropping to 10°C at night. Cover sensitive crops, protect seedlings, and move potted plants indoors. Frost may damage exposed crops!', 'Weather Discussion', 'discussion', NULL, 76, 891, 1, 1, 1, NULL, 'cold,frost,weather,alert', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(107, 47, 'Chicken Manure as Fertilizer', 'Chicken manure is gold for farmers! High in nitrogen and other nutrients. But must compost it for 3-4 months before use - fresh manure burns plants. I mix it with sawdust for perfect compost.', 'Fertilizer Tips', '', NULL, 49, 578, 0, 1, 1, NULL, 'chicken-manure,composting,organic', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(108, 47, 'Carrot Market Prices Stable', 'Carrot prices have remained stable for past 2 months at good rates. Demand is steady from both local markets and processing companies. Good time to plant carrots for harvest in 3 months.', 'Market Updates', 'discussion', NULL, 37, 434, 0, 0, 1, NULL, 'carrot,market,prices,stable', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(109, 47, 'Greenhouse Ventilation Important', 'Almost lost my greenhouse crops due to poor ventilation. Temperature shot up to 45°C inside! Now I installed automatic vent openers. Proper air circulation prevents fungal diseases too. Do not neglect ventilation!', 'Best Practices', '', NULL, 52, 601, 0, 1, 1, NULL, 'greenhouse,ventilation,temperature', '2025-12-30 17:24:10', '2025-12-30 17:24:10'),
(110, 47, 'White Flies on Tomato Plants', 'Small white flies all over my tomato plants! They fly up when I touch the plants. Leaves are getting sticky. Are these white flies? How do I control them organically without chemicals?', 'Pest Control', 'question', NULL, 21, 245, 0, 0, 1, NULL, 'whiteflies,tomato,pest-control', '2025-12-30 17:24:10', '2025-12-30 17:24:10');

-- --------------------------------------------------------

--
-- Table structure for table `content_reports`
--

CREATE TABLE `content_reports` (
  `report_id` int(11) NOT NULL,
  `content_type` enum('post','comment','product','user','message','review') NOT NULL,
  `content_id` int(11) NOT NULL,
  `reporter_id` int(11) NOT NULL,
  `report_reason` enum('spam','inappropriate','harassment','fraud','copyright','misinformation','violence','hate_speech','other') NOT NULL,
  `report_details` text DEFAULT NULL,
  `evidence_urls` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`evidence_urls`)),
  `status` enum('pending','reviewing','resolved','dismissed','escalated') DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `assigned_to` int(11) DEFAULT NULL,
  `resolution` text DEFAULT NULL,
  `action_taken` enum('none','warning','content_edited','content_removed','user_warned','user_suspended','user_banned') DEFAULT 'none',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `crop_activities`
--

CREATE TABLE `crop_activities` (
  `activity_id` int(11) NOT NULL,
  `crop_id` int(11) NOT NULL,
  `activity_type` enum('planting','irrigation','fertilization','pesticide','weeding','harvesting','other') NOT NULL,
  `activity_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `quantity` varchar(50) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `crop_data`
--

CREATE TABLE `crop_data` (
  `crop_id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `crop_name` varchar(100) NOT NULL,
  `crop_type` varchar(100) DEFAULT NULL COMMENT 'e.g., grain, vegetable, fruit',
  `variety` varchar(100) DEFAULT NULL,
  `planting_date` date DEFAULT NULL,
  `planted_date` date DEFAULT NULL,
  `expected_harvest` date DEFAULT NULL,
  `actual_harvest_date` date DEFAULT NULL,
  `area` decimal(10,2) DEFAULT NULL COMMENT 'in acres',
  `area_hectares` decimal(10,2) DEFAULT NULL,
  `field_location` varchar(255) DEFAULT NULL,
  `status` enum('planning','growing','harvesting','harvested','completed','failed') DEFAULT 'planning',
  `expected_yield` decimal(10,2) DEFAULT NULL,
  `actual_yield` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dashboard_widgets`
--

CREATE TABLE `dashboard_widgets` (
  `widget_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `widget_type` varchar(50) NOT NULL,
  `widget_name` varchar(100) DEFAULT NULL,
  `position_x` int(11) DEFAULT 0,
  `position_y` int(11) DEFAULT 0,
  `width` int(11) DEFAULT 1,
  `height` int(11) DEFAULT 1,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `is_visible` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disease_library`
--

CREATE TABLE `disease_library` (
  `disease_id` int(11) NOT NULL,
  `disease_name` varchar(100) NOT NULL,
  `disease_name_bn` varchar(100) DEFAULT NULL,
  `common_name` varchar(100) DEFAULT NULL,
  `scientific_name` varchar(255) DEFAULT NULL,
  `affected_crops` text DEFAULT NULL,
  `symptoms` text DEFAULT NULL,
  `causes` text DEFAULT NULL,
  `prevention` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `organic_treatment` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `severity_level` enum('low','medium','high') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `disease_library`
--

INSERT INTO `disease_library` (`disease_id`, `disease_name`, `disease_name_bn`, `common_name`, `scientific_name`, `affected_crops`, `symptoms`, `causes`, `prevention`, `treatment`, `organic_treatment`, `image_url`, `severity_level`, `created_at`, `updated_at`) VALUES
(1, 'Blast', 'ধানের বিস্ট রোগ', 'Rice Blast', 'Magnaporthe grisea', 'Rice', 'Spindle-shaped lesions on leaves, stem, and panicles. Gray-colored lesions with dark borders.', 'Fungal infection. High humidity and nitrogen-rich soil promote disease.', 'Use resistant varieties, proper spacing, avoid excess nitrogen, ensure good drainage.', 'Apply carbendazim or propiconazole fungicide at early stages.', 'Use Bacillus subtilis bioagent, neem oil spray, sulfur dust.', NULL, 'high', '2025-12-18 15:02:20', '2025-12-18 15:02:20'),
(2, 'Brown Spot', 'বাদামী দাগ রোগ', 'Rice Brown Spot', 'Bipolaris oryzae', 'Rice', 'Brown circular lesions on leaves, starting from leaf tips. Concentric rings visible.', 'Fungal infection, poor seed quality, nutrient deficiency, especially zinc.', 'Use quality seeds, apply zinc sulfate, maintain proper spacing, ensure good drainage.', 'Apply mancozeb or carbendazim fungicide. Use zinc sulfate solution.', 'Apply Trichoderma bioagent, neem oil, and increase zinc application.', NULL, 'medium', '2025-12-18 15:02:20', '2025-12-18 15:02:20'),
(3, 'Sheath Blight', 'পাতার আবরণ পচা রোগ', 'Rice Sheath Blight', 'Rhizoctonia solani', 'Rice', 'Elliptical lesions on leaf sheath. White mycelium growth. Causes panicle sterility.', 'Fungal infection promoted by high humidity, excess nitrogen, and poor drainage.', 'Reduce nitrogen fertilizer, improve drainage, use resistant varieties, proper spacing.', 'Apply carbendazim or tricyclazole fungicide. Reduce irrigation frequency.', 'Use Bacillus subtilis, Pseudomonas bioagent. Apply botanical fungicides.', NULL, 'high', '2025-12-18 15:02:20', '2025-12-18 15:02:20'),
(4, 'Powdery Mildew', 'সাদা গুঁড়ো রোগ', 'Powdery Mildew', 'Erysiphe species', 'Wheat, Vegetables', 'White powder-like coating on leaves. Yellowing of affected leaves. Leaf distortion.', 'Fungal infection favored by warm days, cool nights, and low humidity.', 'Ensure good air circulation, avoid overhead irrigation, use resistant varieties.', 'Apply sulfur dust or potassium bicarbonate spray. Use carbendazim.', 'Apply sulfur dust, neem oil, or potassium bicarbonate spray.', NULL, 'low', '2025-12-18 15:02:20', '2025-12-18 15:02:20'),
(5, 'Leaf Spot', 'পাতায় দাগ রোগ', 'Various Leaf Spots', 'Alternaria, Cercospora species', 'Vegetables, Crops', 'Circular or angular spots on leaves. Brown or grayish necrotic tissue.', 'Fungal or bacterial infections. Poor air circulation and excess moisture promote disease.', 'Remove infected leaves, improve air circulation, avoid overhead irrigation, use resistant varieties.', 'Apply copper fungicide or mancozeb. Remove severely infected leaves.', 'Use Bacillus subtilis, remove infected plant parts, apply neem oil.', NULL, 'medium', '2025-12-18 15:02:20', '2025-12-18 15:02:20'),
(6, 'Early Blight', 'প্রাথমিক ধ্বসা রোগ', 'Tomato Early Blight', 'Alternaria solani', 'Tomato', 'Circular lesions with concentric rings on older leaves. Brown spots with yellow halos.', 'Fungal infection spread by spores. Favored by warm, humid weather.', 'Remove lower leaves, ensure good air circulation, mulch soil, use resistant varieties.', 'Apply mancozeb or chlorothalonil fungicide. Remove infected leaves.', 'Use Bacillus subtilis, remove infected parts, apply copper fungicide.', NULL, 'medium', '2025-12-18 15:02:20', '2025-12-18 15:02:20'),
(7, 'Late Blight', 'দেরিতে আসা ধ্বসা রোগ', 'Tomato Late Blight', 'Phytophthora infestans', 'Tomato, Potato', 'Water-soaked lesions on leaves and stems. White mold on undersides. Fruit rot.', 'Fungal infection favored by cool, wet weather with high humidity.', 'Use resistant varieties, avoid overhead irrigation, ensure good drainage, remove infected plants.', 'Apply carbendazim or metalaxyl fungicide immediately upon detection.', 'Use Bacillus subtilis, remove infected plants, apply copper sulfate.', NULL, 'high', '2025-12-18 15:02:20', '2025-12-18 15:02:20'),
(8, 'Bacterial Wilt', 'ব্যাকটেরিয়াল ঢলে পড়া', 'Bacterial Wilt', 'Ralstonia solanacearum', 'Tomato, Potato, Vegetables', 'Sudden wilting of plants without yellowing. Brown discoloration in vascular tissue.', 'Bacterial infection spread through soil and contaminated tools. Survives in soil.', 'Use resistant varieties, practice crop rotation, disinfect tools, avoid waterlogging.', 'No chemical cure. Remove and destroy infected plants. Use resistant varieties.', 'Remove infected plants, disinfect soil, use resistant varieties, practice rotation.', NULL, 'high', '2025-12-18 15:02:20', '2025-12-18 15:02:20'),
(9, 'Mosaic Virus', 'মোজাইক ভাইরাস', 'Plant Mosaic Virus', 'Various Virus species', 'Vegetables, Crops', 'Mottled or mosaic pattern on leaves. Yellowing, distortion, and stunting of plants.', 'Viral infection spread by aphids and other insects. Contaminated tools can spread.', 'Control insect vectors, use resistant varieties, remove infected plants, disinfect tools.', 'Remove infected plants immediately. Control insect vectors with insecticides.', 'Remove infected plants, control aphids with neem oil, plant resistant varieties.', NULL, 'high', '2025-12-18 15:02:20', '2025-12-18 15:02:20'),
(10, 'Rust', 'মরিচা রোগ', 'Crop Rust', 'Puccinia species', 'Wheat, Vegetables', 'Small rust-colored pustules on leaf undersides. Yellow spots on upper surface.', 'Fungal infection favored by cool, humid weather. Spores spread by wind.', 'Use resistant varieties, ensure good air circulation, avoid excess nitrogen.', 'Apply sulfur dust or propiconazole fungicide at early stages.', 'Apply sulfur dust, neem oil, and Bacillus subtilis bioagent.', NULL, 'medium', '2025-12-18 15:02:20', '2025-12-18 15:02:20'),
(11, 'Anthracnose', 'অ্যানথ্রাকনোজ রোগ', 'Anthracnose', 'Colletotrichum species', 'Pepper, Vegetables', 'Circular lesions with dark centers on leaves and fruits. Pink spore masses.', 'Fungal infection favored by warm, wet weather. Spread by water splash.', 'Ensure good drainage, remove infected fruits, avoid overhead watering, use resistant varieties.', 'Apply copper fungicide or thiram. Remove infected plant parts.', 'Use neem oil, copper sulfate, and remove infected parts.', NULL, 'medium', '2025-12-18 15:02:20', '2025-12-18 15:02:20'),
(12, 'Damping Off', 'গাছের শুকিয়ে পড়া', 'Damping Off', 'Pythium, Rhizoctonia', 'All Crops', 'Young seedlings collapse near soil line. Seedlings wilt and die.', 'Fungal infection in nursery beds. Caused by poor drainage and high moisture.', 'Use sterile soil, proper drainage, avoid overwatering, ensure good air circulation.', 'Treat seeds with fungicide. Improve nursery management.', 'Apply Trichoderma treatment, use well-drained soil, reduce watering.', NULL, 'high', '2025-12-18 15:02:20', '2025-12-18 15:02:20'),
(13, 'Leaf Miner', 'পাতার খোদাইকারী', 'Leaf Miner Damage', 'Various Leaf Miner species', 'Vegetables', 'Winding tunnels and blotches on leaves. Transparent patches.', 'Insect pest that mines inside leaves. Reduced photosynthesis capacity.', 'Remove infected leaves, use netting, crop rotation, remove host plants nearby.', 'Spray malathion or acetamiprid insecticide. Remove affected leaves.', 'Use neem oil, remove infected leaves, practice crop rotation.', NULL, 'low', '2025-12-18 15:02:20', '2025-12-18 15:02:20'),
(14, 'Aphids', 'জাসিড', 'Aphid Infestation', 'Aphididae family', 'All Vegetables', 'Curled, yellowing leaves. Sticky honeydew on plants. Stunted growth.', 'Insect pest that sucks plant sap. Spreads viruses.', 'Use resistant varieties, spray with water, use yellow sticky traps, encourage natural predators.', 'Spray insecticide. Use neem oil or pyrethrin.', 'Spray neem oil, insecticidal soap, encourage ladybugs, use reflective mulch.', NULL, 'medium', '2025-12-18 15:02:20', '2025-12-18 15:02:20'),
(15, 'Whitefly', 'সাদা মাছি', 'Whitefly Infestation', 'Bemisia tabaci', 'Tomato, Vegetables', 'Yellowing and wilting of leaves. White insects on leaf undersides. Sooty mold.', 'Insect pest feeding on phloem sap. Rapid multiplication in warm weather.', 'Use yellow sticky traps, introduce beneficial insects, remove weeds, proper ventilation.', 'Spray insecticide. Use neem oil or yellow sticky traps.', 'Use neem oil, yellow sticky traps, insecticidal soap, encourage parasitoids.', NULL, 'medium', '2025-12-18 15:02:20', '2025-12-18 15:02:20'),
(16, 'Fruit Fly', 'ফল উড়াল', 'Fruit Fly', 'Bactrocera species', 'Fruits', 'Infested fruits have small holes. Larval damage inside fruits. Rotting.', 'Insect pest that lays eggs in developing fruits. Larvae feed inside.', 'Use fruit bagging, remove fallen fruits, use pheromone traps, sanitation.', 'Apply spinosad or malathion. Use pheromone traps.', 'Use pheromone traps, remove infested fruits, neem oil spray.', NULL, 'high', '2025-12-18 15:02:20', '2025-12-18 15:02:20'),
(17, 'Stem Borer', 'কান্ড ছেদকারী', 'Stem Borer', 'Chilo partellus', 'Rice, Corn', 'Entry holes in stems. Wilting of tillers. Deadheart formation.', 'Insect pest whose larvae bore inside plant stems. Causes total crop loss if severe.', 'Remove infested tillers, use pheromone traps, plant early, crop rotation.', 'Apply insecticide. Use pheromone traps and biopesticides.', 'Use pheromone traps, Trichogramma parasitoids, neem oil.', NULL, 'high', '2025-12-18 15:02:20', '2025-12-18 15:02:20'),
(18, 'Slug & Snail', 'শামুক ও স্লাগ', 'Slug & Snail Damage', 'Limax, Achatina', 'Vegetables, Seedlings', 'Irregular holes in leaves. Slimy trails on plants. Seedling destruction.', 'Mollusk pests that feed on leaf tissue. Active in wet conditions.', 'Remove debris, reduce moisture, use beer traps, remove by hand, use copper barriers.', 'Metaldehyde pellets or slug bait. Hand picking effective.', 'Hand picking, beer traps, copper barriers, diatomaceous earth.', NULL, 'low', '2025-12-18 15:02:20', '2025-12-18 15:02:20'),
(19, 'Caterpillar', 'শুঁয়োপোকা', 'Caterpillar Damage', 'Noctuidae, Pieridae', 'Vegetables, Cabbage', 'Ragged holes in leaves. Defoliation. Fecal pellets on plants.', 'Lepidopteran insect larvae that feed on leaves. Can cause severe damage.', 'Remove affected leaves, use Bt (Bacillus thuringiensis), crop rotation, hand-picking.', 'Spray spinosad or malathion. Use Bacillus thuringiensis spray.', 'Use Bacillus thuringiensis, neem oil, hand-picking, beneficial insects.', NULL, 'medium', '2025-12-18 15:02:20', '2025-12-18 15:02:20'),
(20, 'Gall Midge', 'গল মিজ', 'Gall Midge', 'Mayetiola species', 'Rice', 'Small gall-like swellings on rice leaves. Discolored patches.', 'Dipteran pest whose larvae feed on leaf tissue causing galls.', 'Use resistant varieties, proper spacing, optimize fertilizer, remove alternate hosts.', 'Apply carbofuran or carbaryl insecticide at boot stage.', 'Use resistant varieties, remove alternate hosts, biological control agents.', NULL, 'medium', '2025-12-18 15:02:20', '2025-12-18 15:02:20'),
(21, 'Grain Moth', 'শস্য পোকা', 'Grain Moth', 'Sitotroga cerealella', 'Rice, Grains', 'Holes in grain. Webbing inside stored grains. Grain discoloration.', 'Post-harvest pest that infests stored grains. Can destroy entire storage.', 'Proper grain drying, airtight storage, temperature control, regular inspection.', 'Use neem oil or insecticide powder on stored grains. Use pheromone traps.', 'Use neem powder, diatomaceous earth, proper drying, cold storage.', NULL, 'medium', '2025-12-18 15:02:20', '2025-12-18 15:02:20'),
(22, 'Spider Mite', 'মাকড়সা মাইট', 'Spider Mite', 'Tetranychus urticae', 'Vegetables, Cotton', 'Fine webbing on leaves. Yellow stippling. Leaf yellowing and dropping.', 'Arachnid pest that feeds on leaf tissue. Favored by hot, dry weather.', 'Increase humidity, spray water, remove weeds, use sulfur, encourage natural enemies.', 'Spray neem oil or sulfur dust. Use miticide if severe.', 'Neem oil spray, sulfur dust, increase humidity, release predatory mites.', NULL, 'medium', '2025-12-18 15:02:20', '2025-12-18 15:02:20');

-- --------------------------------------------------------

--
-- Table structure for table `disease_reports`
--

CREATE TABLE `disease_reports` (
  `detection_id` int(11) NOT NULL,
  `report_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `crop_id` int(11) DEFAULT NULL,
  `disease_name` varchar(100) DEFAULT NULL,
  `disease_type` varchar(100) DEFAULT NULL,
  `severity` enum('low','medium','high') DEFAULT 'low',
  `confidence_score` decimal(5,2) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `symptoms` text DEFAULT NULL,
  `detected_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `treatment_recommended` text DEFAULT NULL,
  `treatment_applied` text DEFAULT NULL,
  `treatment_cost` decimal(10,2) DEFAULT NULL,
  `status` enum('detected','treating','cured','failed') DEFAULT 'detected',
  `verified_by` int(11) DEFAULT NULL COMMENT 'officer user_id who verified',
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_queue`
--

CREATE TABLE `email_queue` (
  `queue_id` int(11) NOT NULL,
  `to_email` varchar(255) NOT NULL,
  `to_name` varchar(255) DEFAULT NULL,
  `from_email` varchar(255) DEFAULT NULL,
  `from_name` varchar(255) DEFAULT NULL,
  `reply_to` varchar(255) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `body_text` text DEFAULT NULL,
  `body_html` text DEFAULT NULL,
  `template` varchar(100) DEFAULT NULL,
  `template_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`template_data`)),
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `category` varchar(50) DEFAULT NULL,
  `status` enum('pending','sending','sent','failed','cancelled') DEFAULT 'pending',
  `attempts` int(11) DEFAULT 0,
  `max_attempts` int(11) DEFAULT 3,
  `last_attempt_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `opened_at` timestamp NULL DEFAULT NULL,
  `clicked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `error_logs`
--

CREATE TABLE `error_logs` (
  `error_id` int(11) NOT NULL,
  `error_hash` varchar(32) DEFAULT NULL,
  `error_type` varchar(100) NOT NULL,
  `error_code` varchar(50) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `error_file` varchar(255) DEFAULT NULL,
  `error_line` int(11) DEFAULT NULL,
  `stack_trace` text DEFAULT NULL,
  `request_url` varchar(500) DEFAULT NULL,
  `request_method` varchar(10) DEFAULT NULL,
  `request_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`request_data`)),
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `severity` enum('debug','info','warning','error','critical') DEFAULT 'error',
  `occurrence_count` int(11) DEFAULT 1,
  `first_seen` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_seen` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_resolved` tinyint(1) DEFAULT 0,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `farmer_profiles`
--

CREATE TABLE `farmer_profiles` (
  `profile_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `farm_size` decimal(10,2) DEFAULT NULL COMMENT 'in acres',
  `land_size_hectares` decimal(10,2) DEFAULT NULL,
  `experience_level` enum('beginner','intermediate','advanced') DEFAULT NULL,
  `primary_crops` varchar(255) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `sub_district` varchar(100) DEFAULT NULL,
  `village` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `location_lat` decimal(10,8) DEFAULT NULL,
  `location_lng` decimal(10,8) DEFAULT NULL,
  `farming_type` enum('organic','conventional','mixed') DEFAULT 'conventional',
  `soil_type` varchar(100) DEFAULT NULL,
  `irrigation_type` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `farmer_profiles`
--

INSERT INTO `farmer_profiles` (`profile_id`, `user_id`, `farm_size`, `land_size_hectares`, `experience_level`, `primary_crops`, `region`, `district`, `sub_district`, `village`, `address`, `location_lat`, `location_lng`, `farming_type`, `soil_type`, `irrigation_type`, `created_at`, `updated_at`) VALUES
(27, 46, NULL, 2.00, 'intermediate', 'rice', 'Rajshahi', NULL, NULL, NULL, NULL, NULL, NULL, 'conventional', NULL, NULL, '2025-12-29 16:05:42', '2025-12-29 16:05:42');

-- --------------------------------------------------------

--
-- Table structure for table `farm_tasks`
--

CREATE TABLE `farm_tasks` (
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `task_type` enum('planting','irrigation','fertilizer','pesticide','harvest','maintenance','other') DEFAULT 'other',
  `task_date` date NOT NULL,
  `task_time` time DEFAULT NULL,
  `crop` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `weather_dependent` tinyint(1) DEFAULT 1,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `farm_tasks`
--

INSERT INTO `farm_tasks` (`task_id`, `user_id`, `task_name`, `task_type`, `task_date`, `task_time`, `crop`, `notes`, `weather_dependent`, `priority`, `status`, `completed_at`, `created_at`, `updated_at`) VALUES
(1, 46, 'efed', 'planting', '2025-12-29', NULL, NULL, NULL, 1, 'medium', 'pending', NULL, '2025-12-29 22:52:52', '2025-12-29 22:52:52');

-- --------------------------------------------------------

--
-- Table structure for table `fertilizer_recommendations`
--

CREATE TABLE `fertilizer_recommendations` (
  `recommendation_id` int(11) NOT NULL,
  `crop_id` int(11) NOT NULL,
  `recommended_by` int(11) DEFAULT NULL COMMENT 'officer user_id',
  `fertilizer_type` varchar(100) DEFAULT NULL,
  `fertilizer_name` varchar(255) DEFAULT NULL,
  `quantity_kg` decimal(10,2) DEFAULT NULL,
  `application_date` date DEFAULT NULL,
  `application_method` varchar(100) DEFAULT NULL,
  `frequency` varchar(50) DEFAULT NULL,
  `duration_days` int(11) DEFAULT NULL,
  `cost_estimate` decimal(10,2) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `benefits` text DEFAULT NULL,
  `precautions` text DEFAULT NULL,
  `is_organic` tinyint(1) DEFAULT 0,
  `status` enum('recommended','applied','declined') DEFAULT 'recommended',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `field_visits`
--

CREATE TABLE `field_visits` (
  `visit_id` int(11) NOT NULL,
  `officer_id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `visit_date` date NOT NULL,
  `visit_time` time DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `observations` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `follow_up_required` tinyint(1) DEFAULT 0,
  `follow_up_date` date DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled','postponed') DEFAULT 'scheduled',
  `report_file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `file_uploads`
--

CREATE TABLE `file_uploads` (
  `file_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `entity_type` varchar(50) DEFAULT NULL COMMENT 'crop, disease, post, etc',
  `entity_id` int(11) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `generated_reports`
--

CREATE TABLE `generated_reports` (
  `report_id` int(11) NOT NULL,
  `report_name` varchar(255) NOT NULL,
  `report_type` enum('users','activity','security','performance','financial','content','custom') NOT NULL,
  `format` enum('pdf','csv','excel','json','html') DEFAULT 'pdf',
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `date_range_start` date DEFAULT NULL,
  `date_range_end` date DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `status` enum('pending','generating','completed','failed') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `generation_time_ms` int(11) DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `download_count` int(11) DEFAULT 0,
  `last_downloaded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `generated_reports`
--

INSERT INTO `generated_reports` (`report_id`, `report_name`, `report_type`, `format`, `parameters`, `date_range_start`, `date_range_end`, `file_path`, `file_size`, `status`, `error_message`, `generation_time_ms`, `generated_by`, `generated_at`, `expires_at`, `download_count`, `last_downloaded_at`, `created_at`) VALUES
(4, '', '', 'pdf', '{\"date_from\":\"2025-11-30\",\"date_to\":\"2025-12-30\"}', '2025-11-30', '2025-12-30', 'C:\\xampp\\htdocs\\smartcashi/reports/2025-12/report_user_summary_2025-12-30_15-32-28.pdf.json', 1547, 'completed', NULL, NULL, 49, '2025-12-30 14:32:28', NULL, 0, NULL, '2025-12-30 14:32:28');

-- --------------------------------------------------------

--
-- Table structure for table `honeypot_logs`
--

CREATE TABLE `honeypot_logs` (
  `log_id` int(11) NOT NULL,
  `field_name` varchar(100) DEFAULT NULL,
  `field_value` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `page_url` varchar(255) DEFAULT NULL,
  `form_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `honeypot_logs`
--

INSERT INTO `honeypot_logs` (`log_id`, `field_name`, `field_value`, `ip_address`, `user_agent`, `page_url`, `form_name`, `created_at`) VALUES
(1, 'website/phone_number', 'mohatamim44|', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost/smartcashi/admin-login', 'admin_login', '2025-12-30 08:01:10'),
(2, 'website/phone_number', 'mohatamim44|', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost/smartcashi/admin-login', 'admin_login', '2025-12-30 08:01:48'),
(3, 'website/phone_number', 'mohatamim44|', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost/smartcashi/admin-login', 'admin_login', '2025-12-30 08:01:52'),
(4, 'website/phone_number', 'mohatamim44|', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost/smartcashi/admin-login', 'admin_login', '2025-12-30 08:02:55'),
(5, 'website/phone_number', 'mohatamim44|', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost/smartcashi/admin-login', 'admin_login', '2025-12-30 08:02:59'),
(6, 'website/phone_number', 'mohatamim44|', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost/smartcashi/admin-login', 'admin_login', '2025-12-30 08:03:45'),
(7, 'website/phone_number', 'mohatamim44|', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost/smartcashi/admin-login', 'admin_login', '2025-12-30 08:03:48'),
(8, 'website/phone_number', 'mohatamim44|', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost/smartcashi/admin-login', 'admin_login', '2025-12-30 08:03:53'),
(9, 'website/phone_number', 'mohatamim44|', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost/smartcashi/admin-login', 'admin_login', '2025-12-30 08:04:21'),
(10, 'website/phone_number', 'mohatamim44|', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost/smartcashi/admin-login', 'admin_login', '2025-12-30 08:04:25'),
(11, 'website/phone_number', 'mohatamim44|', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost/smartcashi/admin-login', 'admin_login', '2025-12-30 08:04:27'),
(12, 'website/phone_number', 'mohatamim44|', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost/smartcashi/admin-login', 'admin_login', '2025-12-30 08:04:32'),
(13, 'website/phone_number', 'mohatamim44|', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'http://localhost/smartcashi/admin-login', 'admin_login', '2025-12-30 08:04:39');

-- --------------------------------------------------------

--
-- Table structure for table `marketplace_orders`
--

CREATE TABLE `marketplace_orders` (
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `delivery_address` text DEFAULT NULL,
  `payment_method` enum('cash','bkash','nagad','bank','other') DEFAULT 'cash',
  `order_status` enum('pending','confirmed','delivered','cancelled') DEFAULT 'pending',
  `payment_status` enum('pending','paid','refunded') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marketplace_products`
--

CREATE TABLE `marketplace_products` (
  `product_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `product_type` enum('crop','seed','fertilizer','equipment','service','other') DEFAULT 'crop',
  `category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `price_unit` varchar(20) DEFAULT 'kg',
  `quantity_available` int(11) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `quality_grade` enum('A','B','C','standard') DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `images` text DEFAULT NULL COMMENT 'JSON array of image URLs',
  `contact_phone` varchar(20) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `status` enum('available','sold','pending','expired') DEFAULT 'available',
  `views` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_verified` tinyint(1) DEFAULT 0,
  `verified_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_negotiable` tinyint(1) DEFAULT 1,
  `min_order_quantity` int(11) DEFAULT 1,
  `bulk_discount_percent` decimal(5,2) DEFAULT NULL,
  `bulk_min_quantity` int(11) DEFAULT NULL,
  `average_rating` decimal(3,2) DEFAULT 0.00,
  `review_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `marketplace_products`
--

INSERT INTO `marketplace_products` (`product_id`, `seller_id`, `product_name`, `product_type`, `category`, `description`, `price`, `price_unit`, `quantity_available`, `unit`, `quality_grade`, `location`, `district`, `region`, `image_url`, `images`, `contact_phone`, `contact_email`, `status`, `views`, `is_featured`, `is_verified`, `verified_by`, `created_at`, `updated_at`, `expires_at`, `is_negotiable`, `min_order_quantity`, `bulk_discount_percent`, `bulk_min_quantity`, `average_rating`, `review_count`) VALUES
(1, 46, 'gfdfdfd', 'fertilizer', 'Fertilizers', 'dfdfddfdf', 222.00, 'kg', 222, 'kg', 'standard', '', '', 'Rajshahi', 'public/uploads/products/product_46_1767029535.jpg', NULL, '01609036435', '', 'available', 15, 0, 0, NULL, '2025-12-29 17:32:15', '2025-12-29 23:10:15', NULL, 1, 1, NULL, NULL, 5.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `market_prices`
--

CREATE TABLE `market_prices` (
  `price_id` int(11) NOT NULL,
  `crop_name` varchar(100) NOT NULL,
  `crop_type` varchar(100) DEFAULT NULL,
  `variety` varchar(100) DEFAULT NULL,
  `market_location` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `price_per_unit` decimal(10,2) NOT NULL,
  `unit_type` varchar(20) DEFAULT 'kg',
  `min_price` decimal(10,2) DEFAULT NULL,
  `max_price` decimal(10,2) DEFAULT NULL,
  `avg_price` decimal(10,2) DEFAULT NULL,
  `quality_grade` enum('A','B','C','standard') DEFAULT 'standard',
  `demand_level` enum('low','medium','high') DEFAULT 'medium',
  `supply_level` enum('low','medium','high') DEFAULT 'medium',
  `recorded_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `price_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `market_prices`
--

INSERT INTO `market_prices` (`price_id`, `crop_name`, `crop_type`, `variety`, `market_location`, `district`, `region`, `price_per_unit`, `unit_type`, `min_price`, `max_price`, `avg_price`, `quality_grade`, `demand_level`, `supply_level`, `recorded_date`, `price_date`) VALUES
(1, 'Rice', 'grain', 'BRRI dhan28', 'Dhaka Wholesale Market', 'Dhaka Sadar', 'Dhaka', 52.00, 'kg', 50.00, 55.00, 52.50, 'standard', 'high', 'medium', '2025-12-18 15:02:20', '2025-12-18'),
(2, 'Wheat', 'grain', 'BARI Gom-33', 'Narayanganj Market', 'Narayanganj', 'Dhaka', 48.00, 'kg', 45.00, 50.00, 47.80, 'standard', 'medium', 'high', '2025-12-18 15:02:20', '2025-12-18'),
(3, 'Onion', 'vegetable', 'Improved Local Red', 'Khulna Market', 'Khulna Sadar', 'Khulna', 45.00, 'kg', 40.00, 50.00, 45.50, 'A', 'high', 'medium', '2025-12-18 15:02:20', '2025-12-18'),
(4, 'Cabbage', 'vegetable', 'Bangladesh Local', 'Rajshahi Market', 'Pabna', 'Rajshahi', 25.00, 'kg', 22.00, 28.00, 25.20, 'A', 'medium', 'high', '2025-12-18 15:02:20', '2025-12-18'),
(5, 'Tomato', 'vegetable', 'Roma', 'Chittagong Market', 'Feni', 'Chittagong', 60.00, 'kg', 55.00, 65.00, 60.50, 'B', 'medium', 'medium', '2025-12-18 15:02:20', '2025-12-18'),
(6, 'Potato', 'vegetable', 'Cardinal', 'Dhaka Wholesale Market', 'Dhaka Sadar', 'Dhaka', 35.00, 'kg', 32.00, 38.00, 35.50, 'standard', 'high', 'high', '2025-12-18 15:02:20', '2025-12-18'),
(7, 'Jute', 'fiber', 'Bangladesh Local Jute', 'Khulna Market', 'Khulna Sadar', 'Khulna', 120.00, 'kg', 110.00, 130.00, 122.00, 'standard', 'medium', 'low', '2025-12-18 15:02:20', '2025-12-18'),
(8, 'Lentil', 'pulse', 'Barisal-2', 'Bogra Market', 'Bogra', 'Rajshahi', 78.00, 'kg', 75.00, 82.00, 78.50, 'standard', 'high', 'medium', '2025-12-18 15:02:20', '2025-12-18'),
(9, 'Chickpea', 'pulse', 'Local White', 'Rajshahi Market', 'Rajshahi', 'Rajshahi', 85.00, 'kg', 82.00, 88.00, 85.50, 'A', 'medium', 'medium', '2025-12-18 15:02:20', '2025-12-18'),
(10, 'Barley', 'grain', 'Local Barley', 'Tangail Market', 'Tangail', 'Dhaka', 42.00, 'kg', 40.00, 45.00, 42.50, 'standard', 'low', 'high', '2025-12-18 15:02:20', '2025-12-18'),
(11, 'Mustard', 'oilseed', 'BARI Sarisha-14', 'Jessore Market', 'Jessore', 'Khulna', 95.00, 'kg', 92.00, 98.00, 95.50, 'standard', 'medium', 'low', '2025-12-18 15:02:20', '2025-12-18'),
(12, 'Cotton', 'fiber', 'Local Cotton', 'Comilla Market', 'Comilla', 'Chittagong', 185.00, 'kg', 180.00, 190.00, 186.00, 'A', 'low', 'medium', '2025-12-18 15:02:20', '2025-12-18'),
(13, 'Chili', 'vegetable', 'Long Chili', 'Nawabganj Market', 'Nawabganj', 'Rajshahi', 120.00, 'kg', 110.00, 130.00, 120.50, 'A', 'high', 'low', '2025-12-18 15:02:20', '2025-12-18'),
(14, 'Eggplant', 'vegetable', 'Local Long', 'Chandpur Market', 'Chandpur', 'Chittagong', 32.00, 'kg', 28.00, 36.00, 32.50, 'A', 'medium', 'medium', '2025-12-18 15:02:20', '2025-12-18'),
(15, 'Spinach', 'vegetable', 'Green Leaf', 'Gazipur Market', 'Gazipur', 'Dhaka', 22.00, 'kg', 18.00, 26.00, 22.50, 'A', 'low', 'high', '2025-12-18 15:02:20', '2025-12-18'),
(16, 'Carrot', 'vegetable', 'Red Long', 'Kushtia Market', 'Kushtia', 'Khulna', 28.00, 'kg', 25.00, 31.00, 28.50, 'A', 'medium', 'medium', '2025-12-18 15:02:20', '2025-12-18'),
(17, 'Broccoli', 'vegetable', 'Hybrid', 'Satkhira Market', 'Satkhira', 'Khulna', 55.00, 'kg', 50.00, 60.00, 55.50, 'A', 'low', 'low', '2025-12-18 15:02:20', '2025-12-18'),
(18, 'Cauliflower', 'vegetable', 'Local White', 'Shibganj Market', 'Shibganj', 'Rajshahi', 35.00, 'kg', 32.00, 38.00, 35.50, 'A', 'medium', 'medium', '2025-12-18 15:02:20', '2025-12-18'),
(19, 'Garlic', 'vegetable', 'Local White', 'Pirojpur Market', 'Pirojpur', 'Khulna', 150.00, 'kg', 140.00, 160.00, 150.50, 'A', 'medium', 'low', '2025-12-18 15:02:20', '2025-12-18'),
(20, 'Turmeric', 'spice', 'Local Yellow', 'Rangamati Market', 'Rangamati', 'Chittagong', 320.00, 'kg', 300.00, 340.00, 320.50, 'standard', 'low', 'low', '2025-12-18 15:02:20', '2025-12-18'),
(21, 'Papaya', 'fruit', 'Local Paw Paw', 'Cox Bazar Market', 'Cox Bazar', 'Chittagong', 25.00, 'kg', 20.00, 30.00, 25.50, 'B', 'medium', 'high', '2025-12-18 15:02:20', '2025-12-18'),
(22, 'Mango', 'fruit', 'Langra', 'Khagrachhari Market', 'Khagrachhari', 'Chittagong', 180.00, 'kg', 160.00, 200.00, 180.50, 'A', 'low', 'medium', '2025-12-18 15:02:20', '2025-12-18'),
(23, 'Banana', 'fruit', 'Sagor', 'Shariatpur Market', 'Shariatpur', 'Dhaka', 45.00, 'dozen', 40.00, 50.00, 45.50, 'A', 'high', 'high', '2025-12-18 15:02:20', '2025-12-18'),
(24, 'Sesame', 'oilseed', 'White Sesame', 'Natore Market', 'Natore', 'Rajshahi', 280.00, 'kg', 270.00, 290.00, 280.50, 'standard', 'low', 'low', '2025-12-18 15:02:20', '2025-12-18'),
(25, 'Sunflower', 'oilseed', 'Local Sunflower', 'Rajbari Market', 'Rajbari', 'Dhaka', 92.00, 'kg', 88.00, 96.00, 92.50, 'standard', 'medium', 'medium', '2025-12-18 15:02:20', '2025-12-18');

-- --------------------------------------------------------

--
-- Table structure for table `notification_preferences`
--

CREATE TABLE `notification_preferences` (
  `preference_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `weather_alerts` tinyint(1) DEFAULT 1,
  `disease_alerts` tinyint(1) DEFAULT 1,
  `market_alerts` tinyint(1) DEFAULT 1,
  `community_alerts` tinyint(1) DEFAULT 1,
  `email_notifications` tinyint(1) DEFAULT 1,
  `sms_notifications` tinyint(1) DEFAULT 0,
  `push_notifications` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notification_preferences`
--

INSERT INTO `notification_preferences` (`preference_id`, `user_id`, `weather_alerts`, `disease_alerts`, `market_alerts`, `community_alerts`, `email_notifications`, `sms_notifications`, `push_notifications`, `created_at`, `updated_at`) VALUES
(29, 46, 0, 1, 1, 1, 0, 1, 0, '2025-12-29 16:04:43', '2025-12-29 16:10:21'),
(31, 49, 1, 1, 1, 1, 1, 0, 1, '2025-12-30 10:46:02', '2025-12-30 10:46:02');

-- --------------------------------------------------------

--
-- Table structure for table `officer_profiles`
--

CREATE TABLE `officer_profiles` (
  `profile_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `office_location` varchar(255) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `expertise_area` text DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `officer_profiles`
--

INSERT INTO `officer_profiles` (`profile_id`, `user_id`, `designation`, `department`, `office_location`, `region`, `district`, `expertise_area`, `license_number`, `joining_date`, `created_at`, `updated_at`) VALUES
(16, 46, NULL, '', NULL, 'Dhaka', NULL, 'crop_management', NULL, NULL, '2025-12-29 16:08:09', '2025-12-29 16:08:09');

-- --------------------------------------------------------

--
-- Table structure for table `password_history`
--

CREATE TABLE `password_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_bookmarks`
--

CREATE TABLE `post_bookmarks` (
  `bookmark_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `post_bookmarks`
--

INSERT INTO `post_bookmarks` (`bookmark_id`, `post_id`, `user_id`, `created_at`) VALUES
(2, 27, 46, '2025-12-30 04:05:42');

-- --------------------------------------------------------

--
-- Table structure for table `post_comments`
--

CREATE TABLE `post_comments` (
  `comment_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parent_comment_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `likes` int(11) DEFAULT 0,
  `is_approved` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_helpfulness`
--

CREATE TABLE `post_helpfulness` (
  `helpfulness_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_helpful` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `post_helpfulness`
--

INSERT INTO `post_helpfulness` (`helpfulness_id`, `post_id`, `user_id`, `is_helpful`, `created_at`) VALUES
(1, 31, 47, 1, '2025-12-29 23:59:35'),
(2, 32, 46, 1, '2025-12-30 04:22:35');

-- --------------------------------------------------------

--
-- Table structure for table `post_likes`
--

CREATE TABLE `post_likes` (
  `like_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_reports`
--

CREATE TABLE `post_reports` (
  `report_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','reviewed','resolved','dismissed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_shares`
--

CREATE TABLE `post_shares` (
  `share_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `platform` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `post_shares`
--

INSERT INTO `post_shares` (`share_id`, `post_id`, `user_id`, `platform`, `created_at`) VALUES
(1, 32, 46, 'internal', '2025-12-30 04:22:39');

-- --------------------------------------------------------

--
-- Table structure for table `product_comparisons`
--

CREATE TABLE `product_comparisons` (
  `comparison_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_inquiries`
--

CREATE TABLE `product_inquiries` (
  `inquiry_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `inquiry_type` enum('price','availability','quality','delivery','general') DEFAULT 'general',
  `status` enum('pending','responded','closed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_offers`
--

CREATE TABLE `product_offers` (
  `offer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `offered_price` decimal(10,2) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `message` text DEFAULT NULL,
  `status` enum('pending','accepted','rejected','countered','expired') DEFAULT 'pending',
  `counter_price` decimal(10,2) DEFAULT NULL,
  `seller_message` text DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_reports`
--

CREATE TABLE `product_reports` (
  `report_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `reporter_id` int(11) NOT NULL,
  `reason` enum('fake','inappropriate','spam','wrong_category','overpriced','scam','other') NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','reviewed','resolved','dismissed') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_reports`
--

INSERT INTO `product_reports` (`report_id`, `product_id`, `reporter_id`, `reason`, `description`, `status`, `admin_notes`, `reviewed_by`, `created_at`, `updated_at`) VALUES
(1, 1, 46, 'inappropriate', 'sdss', 'pending', NULL, NULL, '2025-12-29 18:27:33', '2025-12-29 18:27:33');

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `review_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parent_review_id` int(11) DEFAULT NULL COMMENT 'For replies',
  `rating` int(1) DEFAULT NULL COMMENT '1-5 star rating (NULL for replies)',
  `review_text` text DEFAULT NULL,
  `images` text DEFAULT NULL COMMENT 'JSON array of image URLs',
  `helpful_count` int(11) DEFAULT 0,
  `not_helpful_count` int(11) DEFAULT 0,
  `is_verified_purchase` tinyint(1) DEFAULT 0,
  `status` enum('active','hidden','deleted') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_reviews`
--

INSERT INTO `product_reviews` (`review_id`, `product_id`, `user_id`, `parent_review_id`, `rating`, `review_text`, `images`, `helpful_count`, `not_helpful_count`, `is_verified_purchase`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 46, NULL, 5, 'dssdsds', NULL, 0, 0, 0, 'active', '2025-12-29 18:26:33', '2025-12-29 18:28:43'),
(2, 1, 46, 1, NULL, 'ddsdsdsds', NULL, 0, 0, 0, 'active', '2025-12-29 18:28:26', '2025-12-29 18:28:26');

-- --------------------------------------------------------

--
-- Table structure for table `product_wishlist`
--

CREATE TABLE `product_wishlist` (
  `wishlist_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `notify_price_drop` tinyint(1) DEFAULT 1,
  `notify_back_in_stock` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rate_limits`
--

CREATE TABLE `rate_limits` (
  `id` int(11) NOT NULL,
  `identifier` varchar(255) NOT NULL,
  `identifier_type` enum('ip','user','session','api_key') NOT NULL,
  `endpoint` varchar(255) DEFAULT NULL,
  `request_count` int(11) DEFAULT 1,
  `window_start` timestamp NOT NULL DEFAULT current_timestamp(),
  `blocked_until` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rate_limits`
--

INSERT INTO `rate_limits` (`id`, `identifier`, `identifier_type`, `endpoint`, `request_count`, `window_start`, `blocked_until`) VALUES
(55, '::1', 'ip', 'admin_login', 1, '2025-12-30 17:20:09', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `recently_viewed`
--

CREATE TABLE `recently_viewed` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recently_viewed`
--

INSERT INTO `recently_viewed` (`id`, `user_id`, `product_id`, `viewed_at`) VALUES
(1, 46, 1, '2025-12-29 18:38:20');

-- --------------------------------------------------------

--
-- Table structure for table `restore_logs`
--

CREATE TABLE `restore_logs` (
  `restore_id` int(11) NOT NULL,
  `backup_id` int(11) NOT NULL,
  `restore_type` enum('full','partial','table','data_only') NOT NULL,
  `status` enum('pending','in_progress','completed','failed','rolled_back') DEFAULT 'pending',
  `tables_restored` text DEFAULT NULL,
  `rows_affected` bigint(20) DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `restored_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_helpfulness`
--

CREATE TABLE `review_helpfulness` (
  `vote_id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_helpful` tinyint(1) NOT NULL COMMENT '1 = helpful, 0 = not helpful',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_likes`
--

CREATE TABLE `review_likes` (
  `like_id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `review_likes`
--

INSERT INTO `review_likes` (`like_id`, `review_id`, `user_id`, `created_at`) VALUES
(1, 2, 46, '2025-12-29 18:28:31'),
(2, 1, 46, '2025-12-29 18:28:47');

-- --------------------------------------------------------

--
-- Table structure for table `scheduled_reports`
--

CREATE TABLE `scheduled_reports` (
  `schedule_id` int(11) NOT NULL,
  `report_name` varchar(255) NOT NULL,
  `report_type` enum('users','activity','security','performance','financial','content','custom') NOT NULL,
  `schedule_cron` varchar(50) NOT NULL,
  `schedule_human` varchar(100) DEFAULT NULL,
  `format` enum('pdf','csv','excel') DEFAULT 'pdf',
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `recipients` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recipients`)),
  `include_attachment` tinyint(1) DEFAULT 1,
  `is_enabled` tinyint(1) DEFAULT 1,
  `last_sent` timestamp NULL DEFAULT NULL,
  `last_status` enum('success','failed') DEFAULT NULL,
  `next_send` timestamp NULL DEFAULT NULL,
  `send_count` int(11) DEFAULT 0,
  `fail_count` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scheduled_tasks`
--

CREATE TABLE `scheduled_tasks` (
  `task_id` int(11) NOT NULL,
  `task_name` varchar(100) NOT NULL,
  `task_description` text DEFAULT NULL,
  `task_type` enum('backup','cleanup','report','email','metric','security','custom') DEFAULT 'custom',
  `task_handler` varchar(255) DEFAULT NULL,
  `task_params` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`task_params`)),
  `schedule_cron` varchar(50) DEFAULT NULL,
  `schedule_human` varchar(100) DEFAULT NULL,
  `is_enabled` tinyint(1) DEFAULT 1,
  `is_running` tinyint(1) DEFAULT 0,
  `last_run` timestamp NULL DEFAULT NULL,
  `next_run` timestamp NULL DEFAULT NULL,
  `last_status` enum('pending','running','success','failed','timeout') DEFAULT 'pending',
  `last_result` text DEFAULT NULL,
  `last_duration_ms` int(11) DEFAULT NULL,
  `run_count` int(11) DEFAULT 0,
  `fail_count` int(11) DEFAULT 0,
  `consecutive_fails` int(11) DEFAULT 0,
  `max_retries` int(11) DEFAULT 3,
  `timeout_seconds` int(11) DEFAULT 300,
  `priority` int(11) DEFAULT 5,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `scheduled_tasks`
--

INSERT INTO `scheduled_tasks` (`task_id`, `task_name`, `task_description`, `task_type`, `task_handler`, `task_params`, `schedule_cron`, `schedule_human`, `is_enabled`, `is_running`, `last_run`, `next_run`, `last_status`, `last_result`, `last_duration_ms`, `run_count`, `fail_count`, `consecutive_fails`, `max_retries`, `timeout_seconds`, `priority`, `created_at`, `updated_at`) VALUES
(1, 'Daily Backup', 'Automated daily database backup', 'backup', 'BackupHandler::daily', NULL, '0 2 * * *', 'Every day at 2:00 AM', 1, 0, NULL, NULL, 'pending', NULL, NULL, 0, 0, 0, 3, 300, 5, '2025-12-30 05:45:11', '2025-12-30 14:03:57'),
(2, 'Cleanup Old Sessions', 'Remove expired admin sessions', 'cleanup', 'CleanupHandler::sessions', NULL, '0 * * * *', 'Every hour', 1, 0, NULL, NULL, 'pending', NULL, NULL, 0, 0, 0, 3, 300, 5, '2025-12-30 05:45:11', '2025-12-30 05:45:11'),
(3, 'Cleanup Old Logs', 'Remove logs older than 30 days', 'cleanup', 'CleanupHandler::logs', NULL, '0 3 * * *', 'Every day at 3:00 AM', 0, 0, NULL, NULL, 'pending', NULL, NULL, 0, 0, 0, 3, 300, 5, '2025-12-30 05:45:11', '2025-12-30 14:12:26'),
(4, 'Collect System Metrics', 'Record system performance metrics', 'metric', 'MetricHandler::collect', NULL, '*/5 * * * *', 'Every 5 minutes', 1, 0, NULL, NULL, 'pending', NULL, NULL, 0, 0, 0, 3, 300, 5, '2025-12-30 05:45:11', '2025-12-30 05:45:11'),
(5, 'Security Scan', 'Scan for security anomalies', 'security', 'SecurityHandler::scan', NULL, '0 */6 * * *', 'Every 6 hours', 0, 0, '2025-12-30 14:13:59', '2025-12-30 15:14:00', 'success', NULL, 1005, 0, 0, 0, 3, 300, 5, '2025-12-30 05:45:11', '2025-12-30 14:14:05'),
(6, 'Generate Daily Report', 'Generate daily activity report', 'report', 'ReportHandler::daily', NULL, '0 6 * * *', 'Every day at 6:00 AM', 0, 0, NULL, NULL, 'pending', NULL, NULL, 0, 0, 0, 3, 300, 5, '2025-12-30 05:45:11', '2025-12-30 05:45:11'),
(7, 'Process Email Queue', 'Send pending emails', 'email', 'EmailHandler::process', NULL, '*/5 * * * *', 'Every 5 minutes', 1, 0, NULL, NULL, 'pending', NULL, NULL, 0, 0, 0, 3, 300, 5, '2025-12-30 05:45:11', '2025-12-30 05:45:11');

-- --------------------------------------------------------

--
-- Table structure for table `security_events`
--

CREATE TABLE `security_events` (
  `event_id` int(11) NOT NULL,
  `event_type` enum('brute_force','suspicious_login','session_hijack','privilege_escalation','data_exfiltration','honeypot_trigger','rate_limit','geo_anomaly','time_anomaly','password_attack','sql_injection','xss_attempt') NOT NULL,
  `severity` enum('info','low','medium','high','critical') DEFAULT 'medium',
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `raw_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`raw_data`)),
  `is_acknowledged` tinyint(1) DEFAULT 0,
  `acknowledged_by` int(11) DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `action_taken` text DEFAULT NULL,
  `auto_blocked` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `security_events`
--

INSERT INTO `security_events` (`event_id`, `event_type`, `severity`, `user_id`, `ip_address`, `description`, `raw_data`, `is_acknowledged`, `acknowledged_by`, `acknowledged_at`, `action_taken`, `auto_blocked`, `created_at`) VALUES
(1, 'honeypot_trigger', 'high', NULL, '::1', 'Bot detected via honeypot on admin login', '{\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\"}', 0, NULL, NULL, NULL, 0, '2025-12-30 08:01:10'),
(2, 'honeypot_trigger', 'high', NULL, '::1', 'Bot detected via honeypot on admin login', '{\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\"}', 0, NULL, NULL, NULL, 0, '2025-12-30 08:01:48'),
(3, 'honeypot_trigger', 'high', NULL, '::1', 'Bot detected via honeypot on admin login', '{\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\"}', 0, NULL, NULL, NULL, 0, '2025-12-30 08:01:52'),
(4, 'honeypot_trigger', 'high', NULL, '::1', 'Bot detected via honeypot on admin login', '{\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\"}', 0, NULL, NULL, NULL, 0, '2025-12-30 08:02:55'),
(5, 'honeypot_trigger', 'high', NULL, '::1', 'Bot detected via honeypot on admin login', '{\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\"}', 0, NULL, NULL, NULL, 0, '2025-12-30 08:02:59'),
(6, 'honeypot_trigger', 'high', NULL, '::1', 'Bot detected via honeypot on admin login', '{\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\"}', 0, NULL, NULL, NULL, 0, '2025-12-30 08:03:45'),
(7, 'honeypot_trigger', 'high', NULL, '::1', 'Bot detected via honeypot on admin login', '{\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\"}', 0, NULL, NULL, NULL, 0, '2025-12-30 08:03:48'),
(8, 'honeypot_trigger', 'high', NULL, '::1', 'Bot detected via honeypot on admin login', '{\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\"}', 0, NULL, NULL, NULL, 0, '2025-12-30 08:03:53'),
(9, 'honeypot_trigger', 'high', NULL, '::1', 'Bot detected via honeypot on admin login', '{\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\"}', 0, NULL, NULL, NULL, 0, '2025-12-30 08:04:21'),
(10, 'honeypot_trigger', 'high', NULL, '::1', 'Bot detected via honeypot on admin login', '{\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\"}', 0, NULL, NULL, NULL, 0, '2025-12-30 08:04:25'),
(11, 'honeypot_trigger', 'high', NULL, '::1', 'Bot detected via honeypot on admin login', '{\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\"}', 0, NULL, NULL, NULL, 0, '2025-12-30 08:04:27'),
(12, 'honeypot_trigger', 'high', NULL, '::1', 'Bot detected via honeypot on admin login', '{\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\"}', 0, NULL, NULL, NULL, 0, '2025-12-30 08:04:32'),
(13, 'honeypot_trigger', 'high', NULL, '::1', 'Bot detected via honeypot on admin login', '{\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\"}', 0, NULL, NULL, NULL, 0, '2025-12-30 08:04:39'),
(14, 'brute_force', 'high', NULL, '::1', 'Blocked IP attempted admin login', '{\"email\":\"admin@smartcashi.com\"}', 0, NULL, NULL, NULL, 0, '2025-12-30 10:04:53'),
(15, 'brute_force', 'high', NULL, '::1', 'Blocked IP attempted admin login', '{\"email\":\"admin@smartcashi.com\"}', 0, NULL, NULL, NULL, 0, '2025-12-30 10:04:57'),
(16, '', 'medium', 49, '::1', 'Error creating user: SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'1518749114\' for key \'phone\'', '{\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\"}', 0, NULL, NULL, NULL, 0, '2025-12-30 11:12:40'),
(17, '', 'medium', 49, '::1', 'Error creating user: SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'1518749114\' for key \'phone\'', '{\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\"}', 0, NULL, NULL, NULL, 0, '2025-12-30 11:12:40'),
(18, '', 'medium', 49, '::1', 'Error creating user: SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'1518749114\' for key \'phone\'', '{\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\"}', 0, NULL, NULL, NULL, 0, '2025-12-30 11:12:40'),
(19, '', 'medium', 49, '::1', 'Error creating user: SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'1518749114\' for key \'phone\'', '{\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\"}', 0, NULL, NULL, NULL, 0, '2025-12-30 11:12:52'),
(20, '', 'medium', 49, '::1', 'Error creating user: SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'1518749114\' for key \'phone\'', '{\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\"}', 0, NULL, NULL, NULL, 0, '2025-12-30 11:12:52'),
(21, '', 'medium', 49, '::1', 'Error creating user: SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'1518749114\' for key \'phone\'', '{\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\"}', 0, NULL, NULL, NULL, 0, '2025-12-30 11:12:52');

-- --------------------------------------------------------

--
-- Table structure for table `seller_stats`
--

CREATE TABLE `seller_stats` (
  `seller_id` int(11) NOT NULL,
  `total_products` int(11) DEFAULT 0,
  `total_sold` int(11) DEFAULT 0,
  `total_orders` int(11) DEFAULT 0,
  `completed_orders` int(11) DEFAULT 0,
  `average_rating` decimal(3,2) DEFAULT 0.00,
  `total_reviews` int(11) DEFAULT 0,
  `response_rate` decimal(5,2) DEFAULT 0.00,
  `badge` enum('new','bronze','silver','gold','platinum') DEFAULT 'new',
  `verified` tinyint(1) DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms_settings`
--

CREATE TABLE `sms_settings` (
  `setting_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `rain_alert` tinyint(1) DEFAULT 1,
  `temp_alert` tinyint(1) DEFAULT 1,
  `storm_alert` tinyint(1) DEFAULT 0,
  `pest_alert` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_metrics`
--

CREATE TABLE `system_metrics` (
  `metric_id` int(11) NOT NULL,
  `metric_type` enum('cpu','memory','disk','database','response_time','error_rate','network','php','requests') NOT NULL,
  `metric_name` varchar(100) DEFAULT NULL,
  `metric_value` decimal(15,2) NOT NULL,
  `metric_unit` varchar(20) DEFAULT NULL,
  `server_id` varchar(50) DEFAULT 'main',
  `additional_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_data`)),
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_execution_logs`
--

CREATE TABLE `task_execution_logs` (
  `log_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `status` enum('started','completed','failed','timeout','skipped') NOT NULL,
  `output` text DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `duration_ms` int(11) DEFAULT NULL,
  `memory_peak` int(11) DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `profile_img_url` varchar(200) NOT NULL,
  `role` enum('farmer','officer','admin') DEFAULT 'farmer',
  `is_active` tinyint(1) DEFAULT 1,
  `is_verified` tinyint(1) DEFAULT 0,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `phone`, `password_hash`, `first_name`, `last_name`, `profile_img_url`, `role`, `is_active`, `is_verified`, `last_login`, `created_at`, `updated_at`) VALUES
(46, 'mohatamim1234@gmail.com', '01609036435', '$2y$10$E6VY9PEQLHLcZtJ8p3zx9.1KXtk2XO0EEtxF2SMtnD7nl8KbRV8pK', 'Mohatamim', 'Haque', 'uploads/profiles/profile_46_1767024301.JPG', 'admin', 1, 1, '2025-12-30 06:57:15', '2025-12-29 14:09:36', '2025-12-30 17:21:58'),
(49, 'admin@smartcashi.com', '01700000000', '$2y$10$xBCPU/lz302alc3LrmAzkOm2GRFZxG1jPmbiECj2av5Ki2aI0SZQC', 'Admin', 'Demo', 'uploads/profiles/profile_49_1767107277.JPG', 'admin', 1, 1, '2025-12-30 17:20:10', '2025-12-30 08:12:31', '2025-12-30 17:20:10'),
(50, 'mohatmimhaque1234@gmail.com', '1518749114', '$2y$10$O0t61irPjsYeIa9bdyzVTOkdTTCYgPidwxUgguoAWmqJ52O1l8r.y', 'Mohatamim', '', 'uploads/profiles/default-avatar.jpg', 'farmer', 1, 0, NULL, '2025-12-30 11:09:26', '2025-12-30 11:15:01'),
(59, 'dfdfddfd@fdfd.com', '4545445454', '$2y$10$9/pibDWsaIpYMSNRxiXFLeCEOrdE.yPgV3A1EtT5k97xgekNGRu/u', 'vdddf', '', 'uploads/profiles/default-avatar.jpg', 'officer', 1, 1, NULL, '2025-12-30 12:14:14', '2025-12-30 17:21:30');

-- --------------------------------------------------------

--
-- Table structure for table `user_bans`
--

CREATE TABLE `user_bans` (
  `ban_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ban_type` enum('temporary','permanent','ip_ban','shadow_ban') NOT NULL,
  `reason` text NOT NULL,
  `internal_notes` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `ip_range` varchar(100) DEFAULT NULL,
  `banned_by` int(11) NOT NULL,
  `banned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `unbanned_by` int(11) DEFAULT NULL,
  `unbanned_at` timestamp NULL DEFAULT NULL,
  `unban_reason` text DEFAULT NULL,
  `appeal_submitted` tinyint(1) DEFAULT 0,
  `appeal_text` text DEFAULT NULL,
  `appeal_reviewed_by` int(11) DEFAULT NULL,
  `appeal_status` enum('pending','approved','denied') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_bans`
--

INSERT INTO `user_bans` (`ban_id`, `user_id`, `ban_type`, `reason`, `internal_notes`, `ip_address`, `ip_range`, `banned_by`, `banned_at`, `expires_at`, `is_active`, `unbanned_by`, `unbanned_at`, `unban_reason`, `appeal_submitted`, `appeal_text`, `appeal_reviewed_by`, `appeal_status`) VALUES
(1, 58, 'temporary', 'nn n', NULL, NULL, NULL, 49, '2025-12-30 11:17:10', '2026-01-06 06:17:10', 1, NULL, NULL, NULL, 0, NULL, NULL, NULL),
(2, 58, 'temporary', 'nn n', NULL, NULL, NULL, 49, '2025-12-30 11:17:10', '2026-01-06 06:17:10', 1, NULL, NULL, NULL, 0, NULL, NULL, NULL),
(3, 58, 'temporary', 'nn n', NULL, NULL, NULL, 49, '2025-12-30 11:17:10', '2026-01-06 06:17:10', 1, NULL, NULL, NULL, 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `session_id` varchar(128) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_warnings`
--

CREATE TABLE `user_warnings` (
  `warning_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `warning_type` enum('content','behavior','spam','fraud','terms_violation','harassment') NOT NULL,
  `severity` enum('minor','moderate','severe') DEFAULT 'minor',
  `reason` text NOT NULL,
  `content_type` varchar(50) DEFAULT NULL,
  `content_id` int(11) DEFAULT NULL,
  `points` int(11) DEFAULT 1,
  `expires_at` timestamp NULL DEFAULT NULL,
  `acknowledged` tinyint(1) DEFAULT 0,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `issued_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `video_content`
--

CREATE TABLE `video_content` (
  `video_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `title_bn` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `video_url` varchar(255) NOT NULL,
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `language` enum('english','bangla','both') DEFAULT 'both',
  `target_audience` enum('beginner','intermediate','advanced','all') DEFAULT 'all',
  `views` int(11) DEFAULT 0,
  `likes` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `weather_alerts`
--

CREATE TABLE `weather_alerts` (
  `alert_id` int(11) NOT NULL,
  `region` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `alert_type` enum('storm','flood','drought','heatwave','frost','cyclone','heavy_rain') NOT NULL,
  `severity` enum('low','medium','high','extreme') DEFAULT 'medium',
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `start_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `end_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `weather_alerts`
--

INSERT INTO `weather_alerts` (`alert_id`, `region`, `district`, `alert_type`, `severity`, `title`, `description`, `start_time`, `end_time`, `is_active`, `created_at`) VALUES
(1, 'Dhaka', 'Narayanganj', 'heavy_rain', 'medium', 'Moderate Rainfall Expected', 'Moderate to heavy rainfall expected during next 3 days. Please ensure proper drainage in fields.', '2025-12-18 15:02:20', '2025-12-21 15:02:20', 1, '2025-12-18 15:02:20'),
(2, 'Khulna', 'Khulna Sadar', 'drought', 'high', 'Prolonged Drought Conditions', 'Dry spell expected for 2 weeks. Plan irrigation carefully. Consider drought-resistant crops.', '2025-12-18 15:02:20', '2026-01-01 15:02:20', 1, '2025-12-18 15:02:20'),
(3, 'Rajshahi', 'Pabna', 'heatwave', 'medium', 'Above Normal Temperatures', 'Maximum temperatures 2-3°C above normal. Increase irrigation frequency. Protect crops from heat stress.', '2025-12-18 15:02:20', '2025-12-23 15:02:20', 0, '2025-12-18 15:02:20'),
(4, 'Chittagong', 'Feni', 'heavy_rain', 'high', 'Heavy Downpour Alert', 'Very heavy rainfall and strong winds expected. Secure structures, ensure drainage, protect loose items.', '2025-12-18 15:02:20', '2025-12-20 15:02:20', 1, '2025-12-18 15:02:20'),
(5, 'Dhaka', 'Tangail', 'frost', 'medium', 'Frost Warning', 'Light frost expected in early mornings. Protect sensitive seedlings with mulch or covers.', '2025-12-18 15:02:20', '2025-12-22 15:02:20', 0, '2025-12-18 15:02:20'),
(6, 'Khulna', 'Jessore', 'flood', 'high', 'Flash Flood Risk', 'Rivers rising above normal levels. Avoid low-lying areas. Prepare for evacuation.', '2025-12-18 15:02:20', '2025-12-24 15:02:20', 1, '2025-12-18 15:02:20'),
(7, 'Rajshahi', 'Bogra', 'heatwave', 'high', 'Extreme Heat Expected', 'Temperature exceeding 38°C expected for 5-7 days. Increase water supply, provide shade for livestock.', '2025-12-18 15:02:20', '2025-12-25 15:02:20', 1, '2025-12-18 15:02:20'),
(8, 'Chittagong', 'Comilla', 'cyclone', 'extreme', 'Cyclone Warning', 'Severe cyclone approaching coastal areas. Take immediate precautions. Follow weather updates hourly.', '2025-12-18 15:02:20', '2025-12-21 15:02:20', 1, '2025-12-18 15:02:20'),
(9, 'Dhaka', 'Manikganj', 'heavy_rain', 'medium', 'Scattered Showers', 'Intermittent rainfall expected. Not blocking planting activities but plan accordingly.', '2025-12-18 15:02:20', '2025-12-20 15:02:20', 0, '2025-12-18 15:02:20'),
(10, 'Khulna', 'Barisal', 'flood', 'medium', 'Moderate Flood Alert', 'River levels rising. Monitor closely. Keep evacuation routes clear.', '2025-12-18 15:02:20', '2025-12-23 15:02:20', 1, '2025-12-18 15:02:20'),
(11, 'Rajshahi', 'Sirajganj', 'drought', 'medium', 'Water Scarcity Warning', 'Reduced rainfall forecast. Groundwater levels declining. Promote water conservation.', '2025-12-18 15:02:20', '2025-12-28 15:02:20', 1, '2025-12-18 15:02:20'),
(12, 'Chittagong', 'Cox Bazar', 'cyclone', 'high', 'Severe Weather Condition', 'Unusual wind patterns and pressure changes. Secure all agricultural infrastructure.', '2025-12-18 15:02:20', '2025-12-20 15:02:20', 1, '2025-12-18 15:02:20'),
(13, 'Dhaka', 'Rajbari', 'frost', 'low', 'Light Frost Possible', 'Slight chance of ground frost in some areas. Monitor temperatures.', '2025-12-18 15:02:20', '2025-12-21 15:02:20', 0, '2025-12-18 15:02:20'),
(14, 'Khulna', 'Kushtia', 'heavy_rain', 'high', 'Torrential Rainfall', 'Unprecedented rainfall amounts expected. Serious flooding likely. Evacuate if necessary.', '2025-12-18 15:02:20', '2025-12-20 15:02:20', 1, '2025-12-18 15:02:20'),
(15, 'Rajshahi', 'Natore', 'heatwave', 'medium', 'High Temperature Alert', 'Continuous high temperatures for 4-5 days. Maintain crop hydration.', '2025-12-18 15:02:20', '2025-12-23 15:02:20', 1, '2025-12-18 15:02:20'),
(16, 'Chittagong', 'Rangamati', 'heavy_rain', 'high', 'Landslide Risk Alert', 'Heavy rainfall on hilly terrain. Risk of landslides. Avoid unstable slopes.', '2025-12-18 15:02:20', '2025-12-21 15:02:20', 1, '2025-12-18 15:02:20'),
(17, 'Dhaka', 'Gazipur', 'drought', 'low', 'Below Normal Rainfall', 'Slightly less rainfall than expected. Plan irrigation ahead.', '2025-12-18 15:02:20', '2025-12-26 15:02:20', 0, '2025-12-18 15:02:20'),
(18, 'Khulna', 'Pirojpur', 'flood', 'high', 'Major Flood Warning', 'Significant flooding expected in low-lying areas. Prepare protective measures.', '2025-12-18 15:02:20', '2025-12-25 15:02:20', 1, '2025-12-18 15:02:20'),
(19, 'Rajshahi', 'Nawabganj', 'frost', 'medium', 'Frost Expected', 'Ground frost will affect seedlings. Cover tender crops overnight.', '2025-12-18 15:02:20', '2025-12-21 15:02:20', 1, '2025-12-18 15:02:20'),
(20, 'Chittagong', 'Chandpur', 'heavy_rain', 'medium', 'Seasonal Rainfall', 'Normal seasonal rainfall expected. Good for crops. Manage water levels.', '2025-12-18 15:02:20', '2025-12-22 15:02:20', 0, '2025-12-18 15:02:20'),
(21, 'Dhaka', 'Shariatpur', 'heavy_rain', 'medium', 'Moderate Rain Event', 'Intermittent moderate rainfall. Ensure field drainage, monitor crops.', '2025-12-18 15:02:20', '2025-12-21 15:02:20', 1, '2025-12-18 15:02:20'),
(22, 'Khulna', 'Satkhira', '', 'high', 'Saltwater Intrusion Alert', 'High salinity levels in water sources. Use alternative water for irrigation.', '2025-12-18 15:02:20', '2025-12-28 15:02:20', 1, '2025-12-18 15:02:20'),
(23, 'Rajshahi', 'Shibganj', 'heavy_rain', 'medium', 'Moderate Showers', 'Beneficial rainfall for crops. Light winds. Good farming weather.', '2025-12-18 15:02:20', '2025-12-20 15:02:20', 0, '2025-12-18 15:02:20'),
(24, 'Chittagong', 'Khagrachhari', 'heavy_rain', 'high', 'Monsoon Downpour', 'Strong monsoon rains affecting hilly region. Prepare drainage systems.', '2025-12-18 15:02:20', '2025-12-23 15:02:20', 1, '2025-12-18 15:02:20');

-- --------------------------------------------------------

--
-- Table structure for table `weather_data`
--

CREATE TABLE `weather_data` (
  `weather_id` int(11) NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `temperature` decimal(5,2) DEFAULT NULL,
  `temperature_min` decimal(5,2) DEFAULT NULL,
  `temperature_max` decimal(5,2) DEFAULT NULL,
  `feels_like` decimal(5,2) DEFAULT NULL,
  `humidity` int(11) DEFAULT NULL,
  `pressure` int(11) DEFAULT NULL,
  `rainfall` decimal(10,2) DEFAULT NULL,
  `wind_speed` decimal(5,2) DEFAULT NULL,
  `wind_direction` int(11) DEFAULT NULL,
  `cloud_coverage` int(11) DEFAULT NULL,
  `weather_condition` varchar(100) DEFAULT NULL,
  `weather_description` text DEFAULT NULL,
  `uv_index` int(11) DEFAULT NULL,
  `visibility` int(11) DEFAULT NULL,
  `recorded_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `forecast_date` date DEFAULT NULL,
  `is_forecast` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `weather_data`
--

INSERT INTO `weather_data` (`weather_id`, `location`, `region`, `district`, `latitude`, `longitude`, `temperature`, `temperature_min`, `temperature_max`, `feels_like`, `humidity`, `pressure`, `rainfall`, `wind_speed`, `wind_direction`, `cloud_coverage`, `weather_condition`, `weather_description`, `uv_index`, `visibility`, `recorded_date`, `forecast_date`, `is_forecast`) VALUES
(1, 'Dhaka', 'Dhaka', 'Dhaka Sadar', 23.81030000, 90.41250000, 28.50, 22.00, 34.00, NULL, 65, NULL, 0.00, 12.50, NULL, NULL, 'Partly Cloudy', NULL, NULL, NULL, '2025-12-18 15:02:20', NULL, 0),
(2, 'Narayanganj', 'Dhaka', 'Narayanganj', 23.43450000, 90.48910000, 27.80, 21.50, 33.50, NULL, 68, NULL, 0.00, 10.20, NULL, NULL, 'Clear', NULL, NULL, NULL, '2025-12-18 15:02:20', NULL, 0),
(3, 'Khulna', 'Khulna', 'Khulna Sadar', 22.84560000, 89.56780000, 29.20, 23.50, 35.00, NULL, 62, NULL, 0.00, 15.80, NULL, NULL, 'Sunny', NULL, NULL, NULL, '2025-12-18 15:02:20', NULL, 0),
(4, 'Rajshahi', 'Rajshahi', 'Pabna', 24.00190000, 89.34560000, 26.50, 20.00, 32.00, NULL, 70, NULL, 2.50, 8.50, NULL, NULL, 'Cloudy', NULL, NULL, NULL, '2025-12-18 15:02:20', NULL, 0),
(5, 'Chittagong', 'Chittagong', 'Feni', 23.02190000, 91.39870000, 30.10, 24.00, 35.50, NULL, 75, NULL, 0.50, 16.20, NULL, NULL, 'Rainy', NULL, NULL, NULL, '2025-12-18 15:02:20', NULL, 0),
(6, 'Tangail', 'Dhaka', 'Tangail', 24.24100000, 89.92630000, 27.20, 21.80, 32.50, NULL, 66, NULL, 0.00, 11.00, NULL, NULL, 'Partly Cloudy', NULL, NULL, NULL, '2025-12-18 15:02:20', NULL, 0),
(7, 'Barisal', 'Khulna', 'Barisal', 22.69450000, 90.25670000, 28.80, 23.00, 34.20, NULL, 70, NULL, 1.20, 14.50, NULL, NULL, 'Cloudy', NULL, NULL, NULL, '2025-12-18 15:02:20', NULL, 0),
(8, 'Nawabganj', 'Rajshahi', 'Nawabganj', 24.59450000, 88.27430000, 25.80, 19.50, 31.00, NULL, 72, NULL, 3.00, 9.20, NULL, NULL, 'Rainy', NULL, NULL, NULL, '2025-12-18 15:02:20', NULL, 0),
(9, 'Comilla', 'Chittagong', 'Comilla', 23.19630000, 91.64380000, 29.50, 23.80, 34.80, NULL, 73, NULL, 0.80, 13.80, NULL, NULL, 'Partly Cloudy', NULL, NULL, NULL, '2025-12-18 15:02:20', NULL, 0),
(10, 'Gazipur', 'Dhaka', 'Gazipur', 23.99570000, 90.43040000, 28.10, 22.20, 33.80, NULL, 67, NULL, 0.00, 10.80, NULL, NULL, 'Clear', NULL, NULL, NULL, '2025-12-18 15:02:20', NULL, 0),
(11, 'Jessore', 'Khulna', 'Jessore', 23.18270000, 89.00920000, 29.00, 23.20, 34.50, NULL, 64, NULL, 0.00, 14.20, NULL, NULL, 'Sunny', NULL, NULL, NULL, '2025-12-18 15:02:20', NULL, 0),
(12, 'Bogra', 'Rajshahi', 'Bogra', 24.84650000, 89.37890000, 26.20, 20.50, 31.80, NULL, 71, NULL, 2.80, 8.90, NULL, NULL, 'Cloudy', NULL, NULL, NULL, '2025-12-18 15:02:20', NULL, 0),
(13, 'Rangamati', 'Chittagong', 'Rangamati', 22.44560000, 92.12340000, 30.80, 25.00, 35.20, NULL, 78, NULL, 1.50, 17.50, NULL, NULL, 'Rainy', NULL, NULL, NULL, '2025-12-18 15:02:20', NULL, 0),
(14, 'Manikganj', 'Dhaka', 'Manikganj', 23.88220000, 90.00680000, 27.50, 21.70, 33.20, NULL, 68, NULL, 0.20, 11.50, NULL, NULL, 'Cloudy', NULL, NULL, NULL, '2025-12-18 15:02:20', NULL, 0),
(15, 'Satkhira', 'Khulna', 'Satkhira', 22.72670000, 89.01650000, 28.50, 22.80, 34.00, NULL, 72, NULL, 1.00, 12.80, NULL, NULL, 'Partly Cloudy', NULL, NULL, NULL, '2025-12-18 15:02:20', NULL, 0),
(16, 'Sirajganj', 'Rajshahi', 'Sirajganj', 24.45000000, 89.70000000, 26.80, 20.80, 32.50, NULL, 69, NULL, 1.50, 9.50, NULL, NULL, 'Cloudy', NULL, NULL, NULL, '2025-12-18 15:02:20', NULL, 0),
(17, 'Chandpur', 'Chittagong', 'Chandpur', 23.16500000, 91.68500000, 29.80, 24.20, 35.00, NULL, 76, NULL, 0.60, 15.20, NULL, NULL, 'Partly Cloudy', NULL, NULL, NULL, '2025-12-18 15:02:20', NULL, 0),
(18, 'Shariatpur', 'Dhaka', 'Shariatpur', 23.20090000, 90.53680000, 28.00, 22.10, 33.50, NULL, 69, NULL, 0.00, 10.50, NULL, NULL, 'Clear', NULL, NULL, NULL, '2025-12-18 15:02:20', NULL, 0),
(19, 'Pirojpur', 'Khulna', 'Pirojpur', 22.45670000, 89.72340000, 28.90, 23.10, 34.30, NULL, 71, NULL, 1.80, 13.50, NULL, NULL, 'Cloudy', NULL, NULL, NULL, '2025-12-18 15:02:20', NULL, 0),
(20, 'Natore', 'Rajshahi', 'Natore', 24.41800000, 88.99000000, 26.50, 20.20, 31.50, NULL, 70, NULL, 2.20, 8.80, NULL, NULL, 'Rainy', NULL, NULL, NULL, '2025-12-18 15:02:20', NULL, 0),
(21, 'Cox Bazar', 'Chittagong', 'Cox Bazar', 21.45300000, 92.18950000, 31.20, 25.50, 35.80, NULL, 80, NULL, 2.00, 18.50, NULL, NULL, 'Rainy', NULL, NULL, NULL, '2025-12-18 15:02:20', NULL, 0),
(22, 'Rajbari', 'Dhaka', 'Rajbari', 23.01570000, 89.77180000, 27.80, 21.90, 33.10, NULL, 67, NULL, 0.50, 10.90, NULL, NULL, 'Partly Cloudy', NULL, NULL, NULL, '2025-12-18 15:02:20', NULL, 0),
(23, 'Kushtia', 'Khulna', 'Kushtia', 23.90640000, 89.01170000, 29.10, 23.40, 34.70, NULL, 63, NULL, 0.00, 15.50, NULL, NULL, 'Sunny', NULL, NULL, NULL, '2025-12-18 15:02:20', NULL, 0),
(24, 'Shibganj', 'Rajshahi', 'Shibganj', 24.63390000, 88.26880000, 26.00, 20.00, 31.20, NULL, 73, NULL, 3.20, 9.00, NULL, NULL, 'Rainy', NULL, NULL, NULL, '2025-12-18 15:02:20', NULL, 0),
(25, 'Dighinala', 'Chittagong', 'Khagrachhari', 23.12340000, 92.34560000, 30.00, 24.50, 35.50, NULL, 77, NULL, 1.20, 16.80, NULL, NULL, 'Rainy', NULL, NULL, NULL, '2025-12-18 15:02:20', NULL, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_2fa_backup_codes`
--
ALTER TABLE `admin_2fa_backup_codes`
  ADD PRIMARY KEY (`code_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `admin_2fa_tokens`
--
ALTER TABLE `admin_2fa_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD KEY `idx_user_token` (`user_id`,`token`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `admin_activity_logs`
--
ALTER TABLE `admin_activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_category` (`action_category`),
  ADD KEY `idx_risk` (`risk_level`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `admin_ip_rules`
--
ALTER TABLE `admin_ip_rules`
  ADD PRIMARY KEY (`rule_id`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_type` (`rule_type`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `admin_login_attempts`
--
ALTER TABLE `admin_login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_attempted` (`attempted_at`);

--
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_type` (`notification_type`),
  ADD KEY `idx_read` (`is_read`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `admin_profiles`
--
ALTER TABLE `admin_profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `admin_settings`
--
ALTER TABLE `admin_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_group` (`setting_group`);

--
-- Indexes for table `admin_trusted_devices`
--
ALTER TABLE `admin_trusted_devices`
  ADD PRIMARY KEY (`device_id`),
  ADD UNIQUE KEY `unique_device` (`user_id`,`device_fingerprint`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `advisories`
--
ALTER TABLE `advisories`
  ADD PRIMARY KEY (`advisory_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_advisory_type` (`advisory_type`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `ai_chat_logs`
--
ALTER TABLE `ai_chat_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_message_type` (`message_type`);

--
-- Indexes for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  ADD PRIMARY KEY (`recommendation_id`),
  ADD KEY `crop_id` (`crop_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_recommendation_type` (`recommendation_type`);

--
-- Indexes for table `alerts`
--
ALTER TABLE `alerts`
  ADD PRIMARY KEY (`alert_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_alert_type` (`alert_type`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_priority` (`priority`);

--
-- Indexes for table `api_request_logs`
--
ALTER TABLE `api_request_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_endpoint` (`endpoint`),
  ADD KEY `idx_response_time` (`response_time_ms`),
  ADD KEY `idx_response_code` (`response_code`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`article_id`),
  ADD KEY `idx_author_id` (`author_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_is_published` (`is_published`);

--
-- Indexes for table `audit_trail`
--
ALTER TABLE `audit_trail`
  ADD PRIMARY KEY (`audit_id`),
  ADD KEY `idx_table_record` (`table_name`,`record_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `backup_records`
--
ALTER TABLE `backup_records`
  ADD PRIMARY KEY (`backup_id`),
  ADD KEY `idx_type` (`backup_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `idx_sender_id` (`sender_id`),
  ADD KEY `idx_receiver_id` (`receiver_id`),
  ADD KEY `idx_is_read` (`is_read`);

--
-- Indexes for table `comment_likes`
--
ALTER TABLE `comment_likes`
  ADD PRIMARY KEY (`like_id`),
  ADD UNIQUE KEY `unique_comment_like` (`comment_id`,`user_id`),
  ADD KEY `idx_comment_id` (`comment_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `community_posts`
--
ALTER TABLE `community_posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_is_approved` (`is_approved`);

--
-- Indexes for table `content_reports`
--
ALTER TABLE `content_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `idx_content` (`content_type`,`content_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_reporter` (`reporter_id`);

--
-- Indexes for table `crop_activities`
--
ALTER TABLE `crop_activities`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `idx_crop_id` (`crop_id`),
  ADD KEY `idx_activity_type` (`activity_type`);

--
-- Indexes for table `crop_data`
--
ALTER TABLE `crop_data`
  ADD PRIMARY KEY (`crop_id`),
  ADD KEY `idx_farmer_id` (`farmer_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_crop_name` (`crop_name`);

--
-- Indexes for table `dashboard_widgets`
--
ALTER TABLE `dashboard_widgets`
  ADD PRIMARY KEY (`widget_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `disease_library`
--
ALTER TABLE `disease_library`
  ADD PRIMARY KEY (`disease_id`),
  ADD KEY `idx_disease_name` (`disease_name`);

--
-- Indexes for table `disease_reports`
--
ALTER TABLE `disease_reports`
  ADD PRIMARY KEY (`detection_id`),
  ADD KEY `verified_by` (`verified_by`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_crop_id` (`crop_id`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `email_queue`
--
ALTER TABLE `email_queue`
  ADD PRIMARY KEY (`queue_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_scheduled` (`scheduled_at`),
  ADD KEY `idx_to_email` (`to_email`);

--
-- Indexes for table `error_logs`
--
ALTER TABLE `error_logs`
  ADD PRIMARY KEY (`error_id`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_type` (`error_type`),
  ADD KEY `idx_hash` (`error_hash`),
  ADD KEY `idx_resolved` (`is_resolved`),
  ADD KEY `idx_first_seen` (`first_seen`);

--
-- Indexes for table `farmer_profiles`
--
ALTER TABLE `farmer_profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_region` (`region`),
  ADD KEY `idx_farming_type` (`farming_type`);

--
-- Indexes for table `farm_tasks`
--
ALTER TABLE `farm_tasks`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `task_date` (`task_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `fertilizer_recommendations`
--
ALTER TABLE `fertilizer_recommendations`
  ADD PRIMARY KEY (`recommendation_id`),
  ADD KEY `recommended_by` (`recommended_by`),
  ADD KEY `idx_crop_id` (`crop_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `field_visits`
--
ALTER TABLE `field_visits`
  ADD PRIMARY KEY (`visit_id`),
  ADD KEY `idx_officer_id` (`officer_id`),
  ADD KEY `idx_farmer_id` (`farmer_id`),
  ADD KEY `idx_visit_date` (`visit_date`);

--
-- Indexes for table `file_uploads`
--
ALTER TABLE `file_uploads`
  ADD PRIMARY KEY (`file_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_entity_type` (`entity_type`),
  ADD KEY `idx_entity_id` (`entity_id`);

--
-- Indexes for table `generated_reports`
--
ALTER TABLE `generated_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `idx_type` (`report_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_generated_by` (`generated_by`);

--
-- Indexes for table `honeypot_logs`
--
ALTER TABLE `honeypot_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `marketplace_orders`
--
ALTER TABLE `marketplace_orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_seller_id` (`seller_id`),
  ADD KEY `idx_buyer_id` (`buyer_id`),
  ADD KEY `idx_order_status` (`order_status`);

--
-- Indexes for table `marketplace_products`
--
ALTER TABLE `marketplace_products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `verified_by` (`verified_by`),
  ADD KEY `idx_seller_id` (`seller_id`),
  ADD KEY `idx_product_type` (`product_type`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `market_prices`
--
ALTER TABLE `market_prices`
  ADD PRIMARY KEY (`price_id`),
  ADD KEY `idx_crop_name` (`crop_name`),
  ADD KEY `idx_market_location` (`market_location`),
  ADD KEY `idx_price_date` (`price_date`);

--
-- Indexes for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD PRIMARY KEY (`preference_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `officer_profiles`
--
ALTER TABLE `officer_profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_region` (`region`),
  ADD KEY `idx_department` (`department`);

--
-- Indexes for table `password_history`
--
ALTER TABLE `password_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `post_bookmarks`
--
ALTER TABLE `post_bookmarks`
  ADD PRIMARY KEY (`bookmark_id`),
  ADD UNIQUE KEY `unique_post_user_bookmark` (`post_id`,`user_id`),
  ADD KEY `idx_post_id` (`post_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `post_comments`
--
ALTER TABLE `post_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `parent_comment_id` (`parent_comment_id`),
  ADD KEY `idx_post_id` (`post_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `post_helpfulness`
--
ALTER TABLE `post_helpfulness`
  ADD PRIMARY KEY (`helpfulness_id`),
  ADD UNIQUE KEY `unique_post_user_helpfulness` (`post_id`,`user_id`),
  ADD KEY `idx_post_id` (`post_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD PRIMARY KEY (`like_id`),
  ADD UNIQUE KEY `unique_post_like` (`post_id`,`user_id`),
  ADD KEY `idx_post_id` (`post_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `post_reports`
--
ALTER TABLE `post_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `idx_post_id` (`post_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `post_shares`
--
ALTER TABLE `post_shares`
  ADD PRIMARY KEY (`share_id`),
  ADD KEY `idx_post_id` (`post_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `product_comparisons`
--
ALTER TABLE `product_comparisons`
  ADD PRIMARY KEY (`comparison_id`),
  ADD UNIQUE KEY `unique_comparison` (`user_id`,`product_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_comparison_product` (`product_id`);

--
-- Indexes for table `product_inquiries`
--
ALTER TABLE `product_inquiries`
  ADD PRIMARY KEY (`inquiry_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_buyer_id` (`buyer_id`);

--
-- Indexes for table `product_offers`
--
ALTER TABLE `product_offers`
  ADD PRIMARY KEY (`offer_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `product_reports`
--
ALTER TABLE `product_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `reporter_id` (`reporter_id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `parent_review_id` (`parent_review_id`),
  ADD KEY `rating` (`rating`);

--
-- Indexes for table `product_wishlist`
--
ALTER TABLE `product_wishlist`
  ADD PRIMARY KEY (`wishlist_id`),
  ADD UNIQUE KEY `unique_wishlist` (`user_id`,`product_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `rate_limits`
--
ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_identifier` (`identifier`,`endpoint`),
  ADD KEY `idx_window` (`window_start`);

--
-- Indexes for table `recently_viewed`
--
ALTER TABLE `recently_viewed`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_view` (`user_id`,`product_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `restore_logs`
--
ALTER TABLE `restore_logs`
  ADD PRIMARY KEY (`restore_id`),
  ADD KEY `idx_backup` (`backup_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `review_helpfulness`
--
ALTER TABLE `review_helpfulness`
  ADD PRIMARY KEY (`vote_id`),
  ADD UNIQUE KEY `unique_review_vote` (`review_id`,`user_id`),
  ADD KEY `review_id` (`review_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `review_likes`
--
ALTER TABLE `review_likes`
  ADD PRIMARY KEY (`like_id`),
  ADD UNIQUE KEY `unique_review_like` (`review_id`,`user_id`),
  ADD KEY `review_id` (`review_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `scheduled_reports`
--
ALTER TABLE `scheduled_reports`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `idx_enabled` (`is_enabled`),
  ADD KEY `idx_next_send` (`next_send`);

--
-- Indexes for table `scheduled_tasks`
--
ALTER TABLE `scheduled_tasks`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `idx_enabled` (`is_enabled`),
  ADD KEY `idx_next_run` (`next_run`),
  ADD KEY `idx_type` (`task_type`);

--
-- Indexes for table `security_events`
--
ALTER TABLE `security_events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `idx_type` (`event_type`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_acknowledged` (`is_acknowledged`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `seller_stats`
--
ALTER TABLE `seller_stats`
  ADD PRIMARY KEY (`seller_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_setting_key` (`setting_key`);

--
-- Indexes for table `sms_settings`
--
ALTER TABLE `sms_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `system_metrics`
--
ALTER TABLE `system_metrics`
  ADD PRIMARY KEY (`metric_id`),
  ADD KEY `idx_type_time` (`metric_type`,`recorded_at`),
  ADD KEY `idx_recorded` (`recorded_at`);

--
-- Indexes for table `task_execution_logs`
--
ALTER TABLE `task_execution_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_task` (`task_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_started` (`started_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `user_bans`
--
ALTER TABLE `user_bans`
  ADD PRIMARY KEY (`ban_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_type` (`ban_type`),
  ADD KEY `idx_ip` (`ip_address`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `user_warnings`
--
ALTER TABLE `user_warnings`
  ADD PRIMARY KEY (`warning_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_type` (`warning_type`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `video_content`
--
ALTER TABLE `video_content`
  ADD PRIMARY KEY (`video_id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_is_featured` (`is_featured`);

--
-- Indexes for table `weather_alerts`
--
ALTER TABLE `weather_alerts`
  ADD PRIMARY KEY (`alert_id`),
  ADD KEY `idx_region` (`region`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_severity` (`severity`);

--
-- Indexes for table `weather_data`
--
ALTER TABLE `weather_data`
  ADD PRIMARY KEY (`weather_id`),
  ADD KEY `idx_location` (`location`),
  ADD KEY `idx_recorded_date` (`recorded_date`),
  ADD KEY `idx_forecast_date` (`forecast_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_2fa_backup_codes`
--
ALTER TABLE `admin_2fa_backup_codes`
  MODIFY `code_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_2fa_tokens`
--
ALTER TABLE `admin_2fa_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_activity_logs`
--
ALTER TABLE `admin_activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `admin_ip_rules`
--
ALTER TABLE `admin_ip_rules`
  MODIFY `rule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `admin_login_attempts`
--
ALTER TABLE `admin_login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_profiles`
--
ALTER TABLE `admin_profiles`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_settings`
--
ALTER TABLE `admin_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `admin_trusted_devices`
--
ALTER TABLE `admin_trusted_devices`
  MODIFY `device_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `advisories`
--
ALTER TABLE `advisories`
  MODIFY `advisory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ai_chat_logs`
--
ALTER TABLE `ai_chat_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  MODIFY `recommendation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `alerts`
--
ALTER TABLE `alerts`
  MODIFY `alert_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `api_request_logs`
--
ALTER TABLE `api_request_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `articles`
--
ALTER TABLE `articles`
  MODIFY `article_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_trail`
--
ALTER TABLE `audit_trail`
  MODIFY `audit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `backup_records`
--
ALTER TABLE `backup_records`
  MODIFY `backup_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comment_likes`
--
ALTER TABLE `comment_likes`
  MODIFY `like_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `community_posts`
--
ALTER TABLE `community_posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT for table `content_reports`
--
ALTER TABLE `content_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `crop_activities`
--
ALTER TABLE `crop_activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `crop_data`
--
ALTER TABLE `crop_data`
  MODIFY `crop_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `dashboard_widgets`
--
ALTER TABLE `dashboard_widgets`
  MODIFY `widget_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `disease_library`
--
ALTER TABLE `disease_library`
  MODIFY `disease_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `disease_reports`
--
ALTER TABLE `disease_reports`
  MODIFY `detection_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `email_queue`
--
ALTER TABLE `email_queue`
  MODIFY `queue_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `error_logs`
--
ALTER TABLE `error_logs`
  MODIFY `error_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `farmer_profiles`
--
ALTER TABLE `farmer_profiles`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `farm_tasks`
--
ALTER TABLE `farm_tasks`
  MODIFY `task_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `fertilizer_recommendations`
--
ALTER TABLE `fertilizer_recommendations`
  MODIFY `recommendation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `field_visits`
--
ALTER TABLE `field_visits`
  MODIFY `visit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `file_uploads`
--
ALTER TABLE `file_uploads`
  MODIFY `file_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `generated_reports`
--
ALTER TABLE `generated_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `honeypot_logs`
--
ALTER TABLE `honeypot_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `marketplace_orders`
--
ALTER TABLE `marketplace_orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `marketplace_products`
--
ALTER TABLE `marketplace_products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `market_prices`
--
ALTER TABLE `market_prices`
  MODIFY `price_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  MODIFY `preference_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `officer_profiles`
--
ALTER TABLE `officer_profiles`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `password_history`
--
ALTER TABLE `password_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `post_bookmarks`
--
ALTER TABLE `post_bookmarks`
  MODIFY `bookmark_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `post_comments`
--
ALTER TABLE `post_comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `post_helpfulness`
--
ALTER TABLE `post_helpfulness`
  MODIFY `helpfulness_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `post_likes`
--
ALTER TABLE `post_likes`
  MODIFY `like_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=130;

--
-- AUTO_INCREMENT for table `post_reports`
--
ALTER TABLE `post_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `post_shares`
--
ALTER TABLE `post_shares`
  MODIFY `share_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `product_comparisons`
--
ALTER TABLE `product_comparisons`
  MODIFY `comparison_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_inquiries`
--
ALTER TABLE `product_inquiries`
  MODIFY `inquiry_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_offers`
--
ALTER TABLE `product_offers`
  MODIFY `offer_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_reports`
--
ALTER TABLE `product_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `product_wishlist`
--
ALTER TABLE `product_wishlist`
  MODIFY `wishlist_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `rate_limits`
--
ALTER TABLE `rate_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `recently_viewed`
--
ALTER TABLE `recently_viewed`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `restore_logs`
--
ALTER TABLE `restore_logs`
  MODIFY `restore_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `review_helpfulness`
--
ALTER TABLE `review_helpfulness`
  MODIFY `vote_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `review_likes`
--
ALTER TABLE `review_likes`
  MODIFY `like_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `scheduled_reports`
--
ALTER TABLE `scheduled_reports`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scheduled_tasks`
--
ALTER TABLE `scheduled_tasks`
  MODIFY `task_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `security_events`
--
ALTER TABLE `security_events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_settings`
--
ALTER TABLE `sms_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_metrics`
--
ALTER TABLE `system_metrics`
  MODIFY `metric_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_execution_logs`
--
ALTER TABLE `task_execution_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `user_bans`
--
ALTER TABLE `user_bans`
  MODIFY `ban_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_warnings`
--
ALTER TABLE `user_warnings`
  MODIFY `warning_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `video_content`
--
ALTER TABLE `video_content`
  MODIFY `video_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `weather_alerts`
--
ALTER TABLE `weather_alerts`
  MODIFY `alert_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `weather_data`
--
ALTER TABLE `weather_data`
  MODIFY `weather_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD CONSTRAINT `admin_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `advisories`
--
ALTER TABLE `advisories`
  ADD CONSTRAINT `advisories_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `ai_chat_logs`
--
ALTER TABLE `ai_chat_logs`
  ADD CONSTRAINT `ai_chat_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  ADD CONSTRAINT `ai_recommendations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ai_recommendations_ibfk_2` FOREIGN KEY (`crop_id`) REFERENCES `crop_data` (`crop_id`) ON DELETE CASCADE;

--
-- Constraints for table `alerts`
--
ALTER TABLE `alerts`
  ADD CONSTRAINT `alerts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alerts_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `articles`
--
ALTER TABLE `articles`
  ADD CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `comment_likes`
--
ALTER TABLE `comment_likes`
  ADD CONSTRAINT `comment_likes_ibfk_1` FOREIGN KEY (`comment_id`) REFERENCES `post_comments` (`comment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comment_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `community_posts`
--
ALTER TABLE `community_posts`
  ADD CONSTRAINT `community_posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_posts_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `crop_activities`
--
ALTER TABLE `crop_activities`
  ADD CONSTRAINT `crop_activities_ibfk_1` FOREIGN KEY (`crop_id`) REFERENCES `crop_data` (`crop_id`) ON DELETE CASCADE;

--
-- Constraints for table `crop_data`
--
ALTER TABLE `crop_data`
  ADD CONSTRAINT `crop_data_ibfk_1` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `disease_reports`
--
ALTER TABLE `disease_reports`
  ADD CONSTRAINT `disease_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disease_reports_ibfk_2` FOREIGN KEY (`crop_id`) REFERENCES `crop_data` (`crop_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `disease_reports_ibfk_3` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `farmer_profiles`
--
ALTER TABLE `farmer_profiles`
  ADD CONSTRAINT `farmer_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `farm_tasks`
--
ALTER TABLE `farm_tasks`
  ADD CONSTRAINT `farm_tasks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `fertilizer_recommendations`
--
ALTER TABLE `fertilizer_recommendations`
  ADD CONSTRAINT `fertilizer_recommendations_ibfk_1` FOREIGN KEY (`crop_id`) REFERENCES `crop_data` (`crop_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fertilizer_recommendations_ibfk_2` FOREIGN KEY (`recommended_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `field_visits`
--
ALTER TABLE `field_visits`
  ADD CONSTRAINT `field_visits_ibfk_1` FOREIGN KEY (`officer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `field_visits_ibfk_2` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `file_uploads`
--
ALTER TABLE `file_uploads`
  ADD CONSTRAINT `file_uploads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `marketplace_orders`
--
ALTER TABLE `marketplace_orders`
  ADD CONSTRAINT `marketplace_orders_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `marketplace_products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marketplace_orders_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marketplace_orders_ibfk_3` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `marketplace_products`
--
ALTER TABLE `marketplace_products`
  ADD CONSTRAINT `marketplace_products_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marketplace_products_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD CONSTRAINT `notification_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `officer_profiles`
--
ALTER TABLE `officer_profiles`
  ADD CONSTRAINT `officer_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `post_comments`
--
ALTER TABLE `post_comments`
  ADD CONSTRAINT `post_comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `community_posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_comments_ibfk_3` FOREIGN KEY (`parent_comment_id`) REFERENCES `post_comments` (`comment_id`) ON DELETE CASCADE;

--
-- Constraints for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD CONSTRAINT `post_likes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `community_posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `product_comparisons`
--
ALTER TABLE `product_comparisons`
  ADD CONSTRAINT `fk_comparison_product` FOREIGN KEY (`product_id`) REFERENCES `marketplace_products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_comparison_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `product_inquiries`
--
ALTER TABLE `product_inquiries`
  ADD CONSTRAINT `product_inquiries_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `marketplace_products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_inquiries_ibfk_2` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `product_offers`
--
ALTER TABLE `product_offers`
  ADD CONSTRAINT `fk_offer_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_offer_product` FOREIGN KEY (`product_id`) REFERENCES `marketplace_products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_offer_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reports`
--
ALTER TABLE `product_reports`
  ADD CONSTRAINT `fk_report_product` FOREIGN KEY (`product_id`) REFERENCES `marketplace_products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_report_user` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `fk_review_parent` FOREIGN KEY (`parent_review_id`) REFERENCES `product_reviews` (`review_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_review_product` FOREIGN KEY (`product_id`) REFERENCES `marketplace_products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_review_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `product_wishlist`
--
ALTER TABLE `product_wishlist`
  ADD CONSTRAINT `fk_wishlist_product` FOREIGN KEY (`product_id`) REFERENCES `marketplace_products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `recently_viewed`
--
ALTER TABLE `recently_viewed`
  ADD CONSTRAINT `fk_viewed_product` FOREIGN KEY (`product_id`) REFERENCES `marketplace_products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_viewed_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `review_helpfulness`
--
ALTER TABLE `review_helpfulness`
  ADD CONSTRAINT `fk_vote_review` FOREIGN KEY (`review_id`) REFERENCES `product_reviews` (`review_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_vote_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `review_likes`
--
ALTER TABLE `review_likes`
  ADD CONSTRAINT `fk_like_review` FOREIGN KEY (`review_id`) REFERENCES `product_reviews` (`review_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_like_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_stats`
--
ALTER TABLE `seller_stats`
  ADD CONSTRAINT `fk_stats_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `sms_settings`
--
ALTER TABLE `sms_settings`
  ADD CONSTRAINT `sms_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `task_execution_logs`
--
ALTER TABLE `task_execution_logs`
  ADD CONSTRAINT `task_execution_logs_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `scheduled_tasks` (`task_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `video_content`
--
ALTER TABLE `video_content`
  ADD CONSTRAINT `video_content_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `cleanup_old_data` ON SCHEDULE EVERY 1 DAY STARTS '2025-12-30 05:45:11' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    
    DELETE FROM `admin_login_attempts` WHERE `attempted_at` < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    
    DELETE FROM `rate_limits` WHERE `window_start` < DATE_SUB(NOW(), INTERVAL 1 DAY);
    
    
    DELETE FROM `admin_2fa_tokens` WHERE `expires_at` < NOW() OR (`used` = 1 AND `used_at` < DATE_SUB(NOW(), INTERVAL 7 DAY));
    
    
    DELETE FROM `api_request_logs` WHERE `created_at` < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    
    DELETE FROM `system_metrics` WHERE `recorded_at` < DATE_SUB(NOW(), INTERVAL 90 DAY);
    
    
    DELETE FROM `task_execution_logs` WHERE `started_at` < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    
    UPDATE `backup_records` SET `status` = 'deleted' WHERE `expires_at` < NOW() AND `status` = 'completed';
    
    
    DELETE FROM `honeypot_logs` WHERE `created_at` < DATE_SUB(NOW(), INTERVAL 30 DAY);
END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
