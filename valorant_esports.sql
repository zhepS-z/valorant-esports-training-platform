-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 09, 2026 at 04:46 PM
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
-- Database: `valorant_esports`
--

-- --------------------------------------------------------

--
-- Table structure for table `ban_history`
--

CREATE TABLE `ban_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `banned_by` int(11) DEFAULT NULL,
  `ban_from` datetime NOT NULL,
  `ban_until` datetime DEFAULT NULL,
  `reason` varchar(512) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ban_history`
--

INSERT INTO `ban_history` (`id`, `user_id`, `banned_by`, `ban_from`, `ban_until`, `reason`, `created_at`) VALUES
(1, 157, NULL, '2025-09-13 11:10:05', '2025-10-13 11:10:05', NULL, '2025-09-13 09:10:05'),
(2, 157, NULL, '2025-09-13 11:10:13', '2025-09-13 11:10:13', 'Unbanned by admin', '2025-09-13 09:10:13'),
(3, 164, NULL, '2025-09-23 15:06:05', '2025-10-23 15:06:05', NULL, '2025-09-23 13:06:05'),
(4, 164, NULL, '2025-09-23 15:06:13', '2025-09-23 15:06:13', 'Unbanned by admin', '2025-09-23 13:06:13'),
(5, 167, NULL, '2026-02-04 14:40:15', '2026-03-06 14:40:15', NULL, '2026-02-04 13:40:15'),
(6, 167, NULL, '2026-02-04 14:41:23', '2026-02-04 14:41:23', 'Unbanned by admin', '2026-02-04 13:41:23');

-- --------------------------------------------------------

--
-- Table structure for table `lfp_applications`
--

CREATE TABLE `lfp_applications` (
  `app_id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','accepted','declined') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lfp_applications`
--

INSERT INTO `lfp_applications` (`app_id`, `post_id`, `user_id`, `status`, `created_at`) VALUES
(2, 35, 152, 'pending', '2025-09-11 21:15:45'),
(3, 36, 152, 'pending', '2025-09-22 17:12:57'),
(4, 35, 165, 'pending', '2025-09-27 19:33:30'),
(5, 36, 166, 'pending', '2025-09-27 19:33:30'),
(6, 36, 167, 'pending', '2026-02-04 20:46:21'),
(7, 35, 167, 'pending', '2026-02-04 20:46:24'),
(8, 37, 167, 'pending', '2026-02-04 20:54:38');

-- --------------------------------------------------------

--
-- Table structure for table `lfp_posts`
--

CREATE TABLE `lfp_posts` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `position` varchar(20) NOT NULL,
  `rank` enum('Unranked','Iron','Bronze','Silver','Gold','Platinum','Diamond','Ascendant','Immortal','Radiant') DEFAULT NULL,
  `experience` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lfp_posts`
--

INSERT INTO `lfp_posts` (`id`, `user_id`, `position`, `rank`, `experience`, `created_at`) VALUES
(35, 164, 'Duelist', '', 'ดดด', '2025-09-10 23:08:34'),
(36, 158, 'Duelist', 'Iron', 'มา', '2025-09-22 17:12:38'),
(37, 169, 'Flex', 'Radiant', 'หาทำไมไอโง่', '2026-02-04 20:49:37');

-- --------------------------------------------------------

--
-- Table structure for table `lft_posts`
--

CREATE TABLE `lft_posts` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lft_posts`
--

INSERT INTO `lft_posts` (`id`, `user_id`, `description`, `created_at`) VALUES
(21, 152, 'เทสโพสหา player', '2025-09-11 12:39:21'),
(22, 165, 'Immortal player looking for competitive team. Flexible roles, available for practice evenings.', '2025-09-27 19:33:30'),
(23, 166, 'Radiant player seeking professional team. Main duelist, experienced in tournaments.', '2025-09-27 19:33:30');

-- --------------------------------------------------------

--
-- Table structure for table `lineups`
--

CREATE TABLE `lineups` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `youtube_url` varchar(255) NOT NULL,
  `map` varchar(50) NOT NULL,
  `agent` varchar(50) NOT NULL,
  `skill` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lineups`
--

INSERT INTO `lineups` (`id`, `user_id`, `youtube_url`, `map`, `agent`, `skill`, `description`, `created_at`) VALUES
(1, 152, 'https://www.youtube.com/watch?v=5qfkHX02Hwc', 'Bind', 'Brimstone', 'Q - Ability 1', 'ลอง test', '2025-10-08 17:27:48'),
(2, 169, 'https://www.youtube.com/watch?v=ju7V3--ZJSM', 'Pearl', 'Sova', 'E - Ability 2', '...', '2026-02-04 21:09:49'),
(3, 167, 'https://www.youtube.com/watch?v=E0X_I_0rycs', 'Bind', 'Sova', 'X - Ultimate', 'ให้ไอพวกโง่ๆ ไม่มีความรู้ศึกษา', '2026-02-04 21:10:33');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `receiver_id` int(10) UNSIGNED DEFAULT NULL,
  `team_id` int(10) UNSIGNED DEFAULT NULL,
  `message` text NOT NULL,
  `room` varchar(50) DEFAULT NULL,
  `is_private` tinyint(1) DEFAULT 0,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `message_type` enum('team','private') NOT NULL DEFAULT 'team'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `team_id`, `message`, `room`, `is_private`, `is_read`, `created_at`, `message_type`) VALUES
(92, 157, 152, NULL, 'ไง', NULL, 0, 0, '2025-11-06 12:42:26', 'team'),
(93, 152, 157, NULL, 'พวก', NULL, 0, 0, '2025-11-06 12:42:29', 'team'),
(94, 158, 157, NULL, 'หกฟหก', NULL, 0, 0, '2025-11-06 12:43:32', 'team'),
(95, 152, 157, NULL, 'ฟหกไฟฟหกฟหก', NULL, 0, 0, '2025-11-06 12:43:40', 'team'),
(96, 152, 157, NULL, 'ไงพวก', NULL, 0, 0, '2025-11-06 12:45:50', 'team'),
(97, 157, 152, NULL, 'ว่าไง', NULL, 0, 0, '2025-11-06 12:45:55', 'team'),
(98, 157, 152, NULL, 'แช่งไหม', NULL, 0, 0, '2025-11-06 12:45:59', 'team'),
(99, 157, 152, NULL, 'ไง', NULL, 0, 0, '2025-11-06 12:46:36', 'team'),
(100, 157, 152, NULL, 'ไง', NULL, 0, 0, '2025-11-06 12:49:01', 'team'),
(101, 157, 152, NULL, 'ไง', NULL, 0, 0, '2025-11-06 12:49:16', 'team'),
(102, 157, 152, NULL, 'ว่าพื้อ', NULL, 0, 0, '2025-11-06 12:49:22', 'team'),
(103, 152, 157, NULL, 'เอ้อ', NULL, 0, 0, '2025-11-06 12:49:36', 'team'),
(104, 152, 157, NULL, 'ไง', NULL, 0, 0, '2025-11-06 12:49:38', 'team'),
(105, 152, 166, NULL, 'โหล', NULL, 0, 0, '2025-11-06 12:56:38', 'team'),
(106, 166, 152, NULL, 'test', NULL, 0, 0, '2025-11-06 12:56:42', 'team'),
(107, 152, 166, NULL, 'ฟหกฟหก', NULL, 0, 0, '2025-11-06 12:56:54', 'team'),
(108, 152, 166, NULL, 'ฟหกา่ฟสวหก', NULL, 0, 0, '2025-11-06 12:56:55', 'team'),
(109, 166, 152, NULL, 'เฟี้ยวๆ', NULL, 0, 0, '2025-11-06 12:57:02', 'team'),
(110, 157, 152, NULL, 'ไงพวก', NULL, 0, 0, '2025-11-08 18:07:03', 'team'),
(111, 152, 157, NULL, 'ฟหกฟหกฟหก', NULL, 0, 0, '2025-11-08 18:07:09', 'team'),
(112, 152, 157, NULL, 'ห', NULL, 0, 0, '2025-11-08 18:07:09', 'team'),
(113, 152, 157, NULL, 'ห', NULL, 0, 0, '2025-11-08 18:07:10', 'team'),
(114, 152, 157, NULL, 'ห', NULL, 0, 0, '2025-11-08 18:07:10', 'team'),
(115, 152, 157, NULL, 'ห', NULL, 0, 0, '2025-11-08 18:07:10', 'team'),
(116, 152, 157, NULL, 'ห', NULL, 0, 0, '2025-11-08 18:07:10', 'team'),
(117, 152, 157, NULL, 'ห', NULL, 0, 0, '2025-11-08 18:07:10', 'team'),
(118, 152, 157, NULL, 'ห', NULL, 0, 0, '2025-11-08 18:07:10', 'team'),
(119, 152, 157, NULL, 'sasd', NULL, 0, 0, '2025-11-08 18:07:28', 'team'),
(120, 157, 152, NULL, 'asdasd', NULL, 0, 0, '2025-11-08 18:07:31', 'team'),
(121, 152, 167, NULL, 'โง่', NULL, 0, 0, '2026-02-04 20:42:32', 'private'),
(122, 167, 152, NULL, 'เรื่อน', NULL, 0, 0, '2026-02-04 20:42:46', 'private'),
(123, 152, 167, NULL, 'ควยไรสัส', NULL, 0, 0, '2026-02-04 20:42:50', 'private'),
(124, 167, 152, NULL, 'แจ้งแบนนะ', NULL, 0, 0, '2026-02-04 20:42:50', 'private'),
(125, 152, 167, NULL, '1-1 ได้', NULL, 0, 0, '2026-02-04 20:42:55', 'private'),
(126, 152, 167, NULL, 'ไอกระบือ', NULL, 0, 0, '2026-02-04 20:42:56', 'private'),
(127, 167, 152, NULL, 'มาดิ', NULL, 0, 0, '2026-02-04 20:42:58', 'private'),
(128, 167, 152, NULL, 'ให้ 10', NULL, 0, 0, '2026-02-04 20:43:01', 'private'),
(129, 152, 167, NULL, 'ควยไรอะ', NULL, 0, 0, '2026-02-04 20:43:07', 'private'),
(130, 152, 167, NULL, 'ได้หมดอะ', NULL, 0, 0, '2026-02-04 20:43:11', 'private'),
(131, 152, 169, NULL, 'ควย', NULL, 0, 0, '2026-02-04 21:20:59', 'private'),
(132, 152, 167, NULL, 'โสด', NULL, 0, 0, '2026-02-04 21:21:12', 'private'),
(133, 167, NULL, 50, 'นัดซ้อมพวก', NULL, 0, 0, '2026-02-04 21:21:25', 'team'),
(134, 169, NULL, 51, 'halo', NULL, 0, 0, '2026-02-04 21:22:32', 'team'),
(135, 152, 164, NULL, 'ว', NULL, 0, 0, '2026-02-07 13:46:39', 'private'),
(136, 152, 158, NULL, 'ร', NULL, 0, 0, '2026-02-07 13:51:47', 'private'),
(137, 152, 169, NULL, 'สวัสดี ฉันสนใจเข้าร่วมทีม', NULL, 0, 0, '2026-02-07 13:54:24', 'private'),
(138, 152, 158, NULL, 'สวัสดี ฉันสนใจเข้าร่วมทีม', NULL, 0, 0, '2026-02-07 13:54:29', 'private'),
(139, 152, 164, NULL, 'สวัสดี ฉันสนใจเข้าร่วมทีม', NULL, 0, 0, '2026-02-07 13:54:35', 'private'),
(140, 152, 169, NULL, 'สวัสดี สนใจเข้าร่วมทีมมั้ย', NULL, 0, 0, '2026-02-07 13:55:25', 'private'),
(141, 152, 170, NULL, 'สวัสดี ต้องการคุยเรื่องการเข้าร่วมทีม', NULL, 0, 0, '2026-02-07 14:11:13', 'private');

-- --------------------------------------------------------

--
-- Table structure for table `pending_users`
--

CREATE TABLE `pending_users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `riot_id` varchar(255) DEFAULT NULL,
  `region` varchar(50) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `otp_code` varchar(10) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `player_rank_cache`
--

CREATE TABLE `player_rank_cache` (
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `riot_id` varchar(100) NOT NULL,
  `region` varchar(10) NOT NULL,
  `tier` varchar(50) DEFAULT NULL,
  `elo` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `player_rank_cache`
--

INSERT INTO `player_rank_cache` (`user_id`, `riot_id`, `region`, `tier`, `elo`, `image_url`, `last_updated`) VALUES
(166, 'P0LAND#MCYT', 'ap', 'Radiant', 550, 'https://media.valorant-api.com/competitivetiers/03621f52-342b-cf4e-4f86-9350a49c6d04/24/largeicon.png', '2025-09-27 19:33:30'),
(165, 'Shadowz#ttv', 'ap', 'Immortal', 450, 'https://media.valorant-api.com/competitivetiers/03621f52-342b-cf4e-4f86-9350a49c6d04/24/largeicon.png', '2025-09-27 19:33:30');

-- --------------------------------------------------------

--
-- Table structure for table `premier_teams`
--

CREATE TABLE `premier_teams` (
  `id` int(11) NOT NULL,
  `team_name` varchar(100) NOT NULL,
  `team_tag` varchar(10) NOT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `premier_teams`
--

INSERT INTO `premier_teams` (`id`, `team_name`, `team_tag`, `created_by`, `created_at`) VALUES
(1, 'TALL GUYS', 'TALL', 152, '2025-09-25 17:39:54'),
(2, 'เปเปอเรก', 'เปป', 152, '2026-02-07 14:15:20');

-- --------------------------------------------------------

--
-- Table structure for table `premier_team_members`
--

CREATE TABLE `premier_team_members` (
  `id` int(11) NOT NULL,
  `premier_team_id` int(11) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `role_in_team` varchar(20) DEFAULT NULL,
  `joined_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `premier_team_members`
--

INSERT INTO `premier_team_members` (`id`, `premier_team_id`, `user_id`, `role_in_team`, `joined_at`) VALUES
(1, 1, 152, 'Manager', '2025-09-25 17:39:54'),
(2, 1, 164, 'Player', '2025-09-25 17:39:54'),
(3, 1, 158, 'Player', '2025-09-25 17:39:54'),
(4, 1, 165, 'Player', '2025-09-27 19:33:29'),
(5, 1, 166, 'Player', '2025-09-27 19:33:29'),
(6, 2, 152, 'Manager', '2026-02-07 14:15:20'),
(7, 2, 164, 'Player', '2026-02-07 14:15:20'),
(8, 2, 158, 'Player', '2026-02-07 14:15:20'),
(9, 2, 165, 'Player', '2026-02-07 14:15:20'),
(10, 2, 166, 'Player', '2026-02-07 14:15:20'),
(11, 2, 170, 'Player', '2026-02-07 14:15:20');

-- --------------------------------------------------------

--
-- Table structure for table `private_messages`
--

CREATE TABLE `private_messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `receiver_id` int(10) UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `room_id` varchar(100) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `private_messages`
--

INSERT INTO `private_messages` (`message_id`, `sender_id`, `receiver_id`, `message`, `room_id`, `is_read`, `created_at`) VALUES
(1, 157, 152, 'ghffgh', NULL, 0, '2025-10-11 15:43:42'),
(2, 152, 157, 'fghfgh', NULL, 0, '2025-10-11 15:43:45'),
(3, 152, 157, 'asdasd', NULL, 0, '2025-10-11 15:44:08'),
(4, 157, 152, 'ฟหกฟหก', NULL, 0, '2025-10-11 15:46:25'),
(5, 152, 157, 'ฟหกฟหก', NULL, 0, '2025-10-11 15:46:39'),
(6, 157, 152, 'ฟหกฟหก', NULL, 0, '2025-10-11 15:46:45'),
(7, 157, 152, 'ฟหก', NULL, 0, '2025-10-11 15:47:06'),
(8, 157, 152, 'ฟหกฟหก', NULL, 0, '2025-10-11 15:47:16'),
(9, 152, 157, 'ฟหกฟหก', NULL, 0, '2025-10-11 15:47:21'),
(10, 152, 157, 'ฟหกฟหก', NULL, 0, '2025-10-11 15:49:17'),
(11, 157, 152, 'ฟฟหกฟหก', NULL, 0, '2025-10-11 15:49:21'),
(12, 157, 152, 'หกดหกดกหด', NULL, 0, '2025-10-11 15:49:30'),
(13, 157, 152, 'ล่าสุด', NULL, 0, '2025-10-11 15:49:35'),
(14, 157, 152, 'ฟหกฟหก', NULL, 0, '2025-10-11 15:51:08'),
(15, 157, 152, 'ฟหกฟหกฟหก', NULL, 0, '2025-10-11 15:51:14'),
(16, 157, 152, '111111111111111111111111', NULL, 0, '2025-10-11 15:51:24'),
(17, 152, 157, '11111111111', NULL, 0, '2025-10-11 15:51:26'),
(18, 157, 152, '11111111111111111', NULL, 0, '2025-10-11 15:51:54'),
(19, 152, 157, '1111111111', NULL, 0, '2025-10-11 15:52:08'),
(20, 157, 152, '111111111111', NULL, 0, '2025-10-11 15:52:11'),
(21, 157, 152, 'asdasd', NULL, 0, '2025-10-11 15:54:15'),
(22, 152, 157, 'dd', NULL, 0, '2025-10-11 15:54:20');

-- --------------------------------------------------------

--
-- Table structure for table `scrims`
--

CREATE TABLE `scrims` (
  `scrim_id` int(10) UNSIGNED NOT NULL,
  `team_id` int(10) UNSIGNED DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `scrim_start` datetime NOT NULL,
  `format` enum('Single','BO3','MR24','MR12') DEFAULT 'Single',
  `map` varchar(50) DEFAULT 'Any',
  `desired_rank` enum('Unranked','Iron','Bronze','Silver','Gold','Platinum','Diamond','Ascendant','Immortal','Radiant') DEFAULT 'Unranked',
  `slots` tinyint(3) UNSIGNED DEFAULT 1,
  `reserved_count` tinyint(3) UNSIGNED DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `scrims`
--

INSERT INTO `scrims` (`scrim_id`, `team_id`, `created_by`, `scrim_start`, `format`, `map`, `desired_rank`, `slots`, `reserved_count`, `is_published`, `created_at`) VALUES
(3, 49, 152, '2025-09-17 16:55:00', 'Single', 'Abyss', 'Immortal', 5, 0, 1, '2025-09-17 14:55:44'),
(4, 49, 152, '2025-09-17 14:58:00', 'Single', 'Ascent', 'Silver', 5, 0, 1, '2025-09-17 14:56:01'),
(5, 49, 164, '2025-09-24 15:23:00', 'Single', '', 'Radiant', 5, 1, 1, '2025-09-17 15:24:02'),
(6, 36, 157, '2025-09-17 17:28:00', 'MR24', 'Ascent', 'Ascendant', 5, 0, 1, '2025-09-17 15:28:35'),
(7, 36, 157, '2025-09-19 21:23:00', 'Single', '', 'Unranked', 5, 1, 1, '2025-09-19 17:23:25'),
(8, 49, NULL, '2025-09-24 19:47:00', 'Single', 'Breeze', 'Radiant', 5, 1, 1, '2025-09-24 19:45:22'),
(9, 36, 157, '2025-10-01 13:02:00', 'MR24', 'Sunset', 'Platinum', 5, 2, 1, '2025-09-24 19:57:32'),
(10, 50, NULL, '2024-07-10 20:51:00', 'BO3', 'Corrode', 'Iron', 5, 0, 1, '2026-02-04 20:52:56'),
(11, 49, NULL, '2026-02-04 20:53:00', 'Single', '', 'Unranked', 5, 0, 1, '2026-02-04 20:53:18'),
(12, 50, NULL, '2026-02-25 20:53:00', 'BO3', 'Bind', 'Immortal', 5, 0, 1, '2026-02-04 20:53:30'),
(13, 49, NULL, '2026-02-04 20:53:00', 'Single', '', 'Unranked', 5, 0, 1, '2026-02-04 20:53:41'),
(14, 49, NULL, '2026-02-04 20:54:00', 'MR12', 'Breeze', 'Platinum', 5, 0, 1, '2026-02-04 20:54:01'),
(15, 50, NULL, '2050-01-04 20:54:00', 'MR12', 'Bind', 'Ascendant', 5, 0, 1, '2026-02-04 20:55:17'),
(16, 50, NULL, '2026-02-19 20:55:00', 'Single', 'Lotus', 'Radiant', 5, 1, 1, '2026-02-04 20:55:36'),
(17, 49, NULL, '2026-02-04 22:56:00', 'Single', '', 'Bronze', 5, 2, 1, '2026-02-04 20:56:04');

-- --------------------------------------------------------

--
-- Table structure for table `scrim_reservations`
--

CREATE TABLE `scrim_reservations` (
  `reservation_id` int(10) UNSIGNED NOT NULL,
  `scrim_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `status` enum('reserved','cancelled') DEFAULT 'reserved',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `scrim_reservations`
--

INSERT INTO `scrim_reservations` (`reservation_id`, `scrim_id`, `user_id`, `status`, `created_at`) VALUES
(23, 5, 157, '', '2025-09-19 18:08:59'),
(24, 8, 157, '', '2025-09-24 19:45:33'),
(26, 9, 152, '', '2025-09-24 19:59:03'),
(27, 16, 152, '', '2026-02-04 20:56:17'),
(28, 17, 167, 'reserved', '2026-02-04 20:56:27'),
(29, 17, 169, 'reserved', '2026-02-04 21:12:08');

-- --------------------------------------------------------

--
-- Table structure for table `scrim_reservation_notifications`
--

CREATE TABLE `scrim_reservation_notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `reservation_id` int(10) UNSIGNED NOT NULL,
  `manager_id` int(10) UNSIGNED NOT NULL,
  `team_id` int(10) UNSIGNED NOT NULL,
  `scrim_id` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','accepted','declined') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `scrim_reservation_notifications`
--

INSERT INTO `scrim_reservation_notifications` (`id`, `reservation_id`, `manager_id`, `team_id`, `scrim_id`, `status`, `created_at`) VALUES
(1, 3, 152, 49, 5, 'declined', '2025-09-19 17:40:20'),
(2, 8, 157, 36, 7, 'declined', '2025-09-19 17:49:58'),
(3, 21, 157, 36, 7, 'declined', '2025-09-19 17:59:38'),
(4, 22, 152, 49, 5, 'accepted', '2025-09-19 18:07:33'),
(5, 23, 152, 49, 5, 'declined', '2025-09-19 18:08:59'),
(6, 24, 152, 49, 8, 'accepted', '2025-09-24 19:45:33'),
(7, 25, 157, 36, 9, 'accepted', '2025-09-24 19:57:38'),
(8, 26, 157, 36, 9, 'accepted', '2025-09-24 19:59:03'),
(9, 27, 167, 50, 16, 'accepted', '2026-02-04 20:56:17'),
(10, 28, 152, 49, 17, 'pending', '2026-02-04 20:56:27'),
(11, 29, 152, 49, 17, 'pending', '2026-02-04 21:12:08');

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `team_id` int(10) UNSIGNED NOT NULL,
  `team_name` varchar(100) NOT NULL,
  `abbreviation` varchar(6) DEFAULT NULL,
  `manager_id` int(10) UNSIGNED DEFAULT NULL,
  `region` enum('na','eu','ap','kr','latam','br') DEFAULT 'ap',
  `rank` enum('Unranked','Iron','Bronze','Silver','Gold','Platinum','Diamond','Immortal','Radiant') DEFAULT 'Unranked',
  `current_size` int(11) DEFAULT 0,
  `max_size` int(11) DEFAULT 5,
  `description` text DEFAULT NULL,
  `team_logo` varchar(255) DEFAULT NULL,
  `practice_schedule` varchar(255) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `ban_until` datetime DEFAULT NULL,
  `ban_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`team_id`, `team_name`, `abbreviation`, `manager_id`, `region`, `rank`, `current_size`, `max_size`, `description`, `team_logo`, `practice_schedule`, `is_published`, `ban_until`, `ban_reason`) VALUES
(36, 'Made In Thailand', 'MITH', 157, '', 'Iron', 1, 5, 'asdasd', 'uploads/team_logos/11111.png', NULL, 1, NULL, NULL),
(49, 'Talon Esport', 'TLN', 152, 'ap', 'Immortal', 6, 7, 'เทสโพสหา player', 'uploads/team_logos/0cb690ae15af82fe.png', NULL, 1, NULL, NULL),
(50, 'ไร้สมอง', 'RSM', 167, 'ap', 'Unranked', 1, 7, 'ไอพวกโง่ๆไม่ต้องสมัคร', NULL, NULL, 0, NULL, NULL),
(51, 'อนุบาลหนองจอก', 'ABJ', 169, 'kr', 'Unranked', 1, 7, '', NULL, NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `team_join_requests`
--

CREATE TABLE `team_join_requests` (
  `request_id` int(10) UNSIGNED NOT NULL,
  `team_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','accepted','declined') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team_join_requests`
--

INSERT INTO `team_join_requests` (`request_id`, `team_id`, `user_id`, `status`, `created_at`) VALUES
(14, 49, 164, 'accepted', '2025-09-15 21:08:56'),
(15, 49, 158, 'accepted', '2025-09-22 17:12:01'),
(16, 49, 165, 'accepted', '2025-09-27 19:33:30'),
(17, 49, 166, 'accepted', '2025-09-27 19:33:30'),
(18, 49, 167, 'declined', '2026-02-04 20:40:15'),
(19, 36, 167, 'pending', '2026-02-04 20:44:01'),
(20, 49, 167, 'accepted', '2026-02-04 20:44:43'),
(21, 49, 169, 'declined', '2026-02-04 20:47:58'),
(22, 49, 170, 'accepted', '2026-02-07 14:08:34');

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `id` int(10) UNSIGNED NOT NULL,
  `team_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `role_in_team` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team_members`
--

INSERT INTO `team_members` (`id`, `team_id`, `user_id`, `role_in_team`) VALUES
(160, 36, 157, 'Manager'),
(174, 49, 152, 'Manager'),
(179, 49, 164, 'Player'),
(180, 49, 158, 'Player'),
(181, 49, 165, 'Player'),
(182, 49, 166, 'Player'),
(184, 50, 167, 'Manager'),
(185, 51, 169, 'Manager'),
(186, 49, 170, 'Player');

-- --------------------------------------------------------

--
-- Table structure for table `team_open_roles_backup`
--

CREATE TABLE `team_open_roles_backup` (
  `id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `team_id` int(10) UNSIGNED NOT NULL,
  `role` enum('Duelist','Initiator','Controller','Sentinel','Flex') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team_open_roles_backup`
--

INSERT INTO `team_open_roles_backup` (`id`, `team_id`, `role`) VALUES
(39, 36, 'Flex'),
(40, 36, ''),
(41, 36, ''),
(42, 36, ''),
(52, 46, ''),
(53, 46, ''),
(54, 47, '');

-- --------------------------------------------------------

--
-- Table structure for table `team_rank_cache`
--

CREATE TABLE `team_rank_cache` (
  `team_id` int(10) UNSIGNED NOT NULL,
  `avg_score` double DEFAULT NULL,
  `avg_tier` varchar(50) DEFAULT NULL,
  `member_count` int(11) DEFAULT NULL,
  `last_updated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team_rank_cache`
--

INSERT INTO `team_rank_cache` (`team_id`, `avg_score`, `avg_tier`, `member_count`, `last_updated`) VALUES
(36, 0, '0', 1, '2025-09-06 14:53:44'),
(39, 0, '0', 1, '2025-09-06 14:54:17'),
(40, 0, '0', 1, '2025-09-06 14:54:56'),
(49, 500, 'Immortal', 5, '2025-09-27 19:33:30');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `riot_id` varchar(100) DEFAULT NULL,
  `role` enum('player','coach','admin','manager') DEFAULT NULL,
  `team_id` int(10) UNSIGNED DEFAULT NULL,
  `region` enum('na','eu','ap','kr','latam','br') DEFAULT NULL,
  `profile_img` varchar(255) DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `otp_code` varchar(10) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `otp_verified` tinyint(1) NOT NULL DEFAULT 0,
  `ban_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `password`, `email`, `riot_id`, `role`, `team_id`, `region`, `profile_img`, `reset_token`, `reset_token_expiry`, `email_verified`, `otp_code`, `otp_expiry`, `otp_verified`, `ban_until`) VALUES
(152, 'set', 'test', '$2y$10$i8Xs8Fczz/HLnX3V7HQBoOYBM9CNWhwaR31071i7Wv1j2mYXF5BEK', 'settapong.janajina@gmail.com', 'sorex#god', 'admin', 49, 'na', '../img/profile/profile_68bc26822e0679.11174791.jpg', NULL, NULL, 1, NULL, NULL, 1, NULL),
(157, 'zhep', 'createteam', '$2y$10$bkLtZ4q/TQsuB5tAbdWnH.KnI9UsZEj8BtAhP2eINIlg/wkVSFGa2', 'stangchickid@gmail.com', 'ciggraball#love', 'manager', 36, 'ap', '../img/agents/41fb69c1-4189-7b37-f117-bcaf1e96f1bf.png', NULL, NULL, 1, NULL, NULL, 1, NULL),
(158, 'ssss', 'ssss', '$2y$10$2d/eWeQD.HyRaQ1soG77UeTZ7KqxxRzEJAuXbVnIEkveoVJ0yz8/i', 'stangchickid1@gmail.com', 'strongtallguy62#225', 'player', 49, 'na', '../img/profile/profile_6893124a2ab0f9.53386051.jpg', NULL, NULL, 1, NULL, NULL, 1, NULL),
(164, 'test', 'lfp', '$2y$10$GorlG7SNwqTJGDAIRcXlXuXhBCSjKj.gvpIY6j9pLWjAm6yhNim0W', 'stangps3@gmail.com', 'ANDYYOSHI#3680', 'manager', 49, 'na', '../img/agents/bb2a4828-46eb-8cd1-e765-15848195d751.png', NULL, NULL, 1, NULL, NULL, 1, NULL),
(165, 'Shadowz', 'Player', '$2y$10$i8Xs8Fczz/HLnX3V7HQBoOYBM9CNWhwaR31071i7Wv1j2mYXF5BEK', 'shadowz.player@example.com', 'Shadowz#ttv', 'player', 49, 'na', '../img/profile/sup.jpg', NULL, NULL, 1, NULL, NULL, 1, NULL),
(166, 'P0LAND', 'Twitch', '$2y$10$i8Xs8Fczz/HLnX3V7HQBoOYBM9CNWhwaR31071i7Wv1j2mYXF5BEK', 'poland.twitch@example.com', 'twitch P0LAND#MCYT', 'player', 49, 'na', '../img/profile/spi.png', NULL, NULL, 1, NULL, NULL, 1, NULL),
(167, 'Jastin', 'Morawietz', '$2y$10$PPG1KTgPyU81AaaVh6kMM.SOGMXNJ8p714Xr8my3rGc6KxQxYMVlu', 'jastin.w2006@gmail.com', 'Tent#OvO', 'player', 50, 'ap', '../img/profile/profile_698356b4a02036.91919032.png', NULL, NULL, 1, NULL, NULL, 1, NULL),
(168, 'nantawat', 'utama', '$2y$10$Rf.PdQOB6EyO2vshhteGve3oDHSbMWn4sn7QetQoLl/OKXHk0giEi', 'filmzalnw1@gmail.com', 'Green Lantern #1st', 'player', NULL, 'kr', '../img/agents/0e38b510-41a8-5780-5e8f-568b2a4f2d6c.png', NULL, NULL, 1, NULL, NULL, 1, NULL),
(169, 'เจ๋ง', 'สุดหล่อ', '$2y$10$Fjs1g5YQpXf7k7UCLpZfPu9ybpwowhhVeqgC2AZl.lsl5BSltaORu', 'Catter7TH@gmail.com', 'ราชามายคราฟ', 'player', 51, 'kr', '../img/agents/e370fa57-4757-3604-3648-499e1f642d3f.png', NULL, NULL, 1, NULL, NULL, 1, NULL),
(170, 'ห่อหมก', 'ฮวก', '$2y$10$wD/8lOblwcLKgXmBKMZhJOOl.7.n014AbQ.dFy81SUT5iW6VcDCMq', 'stangchickid03@gmail.com', 'xxx#xxxx', 'player', 49, 'eu', NULL, NULL, NULL, 0, NULL, NULL, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_notifications`
--

CREATE TABLE `user_notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_notifications`
--

INSERT INTO `user_notifications` (`id`, `user_id`, `type`, `title`, `body`, `meta`, `is_read`, `created_at`) VALUES
(1, 152, 'scrim_reservation_created', 'Scrim reservation: Talon Esport', 'Made In Thailand reserved a slot vs your team — scheduled at 2025-09-24 15:23.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":36,\"reserver_team_name\":\"Made In Thailand\",\"scrim_id\":5,\"reservation_id\":\"22\",\"scrim_start\":\"2025-09-24 15:23:00\"}', 0, '2025-09-19 18:07:33'),
(2, 157, 'scrim_reservation_created', 'Scrim reservation: Talon Esport', 'You reserved a slot vs Talon Esport — scheduled at 2025-09-24 15:23.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":36,\"reserver_team_name\":\"Made In Thailand\",\"scrim_id\":5,\"reservation_id\":\"22\",\"scrim_start\":\"2025-09-24 15:23:00\"}', 0, '2025-09-19 18:07:33'),
(3, 164, 'scrim_reservation_created', 'Scrim reservation: Talon Esport', 'Made In Thailand reserved a slot vs your team — scheduled at 2025-09-24 15:23.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":36,\"reserver_team_name\":\"Made In Thailand\",\"scrim_id\":5,\"reservation_id\":\"22\",\"scrim_start\":\"2025-09-24 15:23:00\"}', 0, '2025-09-19 18:07:34'),
(4, 157, 'scrim_accepted', 'Scrim accepted', 'Your reservation vs Talon Esport was accepted. Scheduled at 2025-09-24 15:23.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":36,\"reserver_team_name\":\"Made In Thailand\",\"scrim_id\":5,\"reservation_id\":22,\"scrim_start\":\"2025-09-24 15:23:00\"}', 0, '2025-09-19 18:07:47'),
(5, 152, 'scrim_accepted', 'Scrim accepted', 'You accepted the scrim with Made In Thailand. Scheduled at 2025-09-24 15:23.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":36,\"reserver_team_name\":\"Made In Thailand\",\"scrim_id\":5,\"reservation_id\":22,\"scrim_start\":\"2025-09-24 15:23:00\"}', 0, '2025-09-19 18:07:47'),
(6, 164, 'scrim_accepted', 'Scrim accepted', 'You accepted the scrim with Made In Thailand. Scheduled at 2025-09-24 15:23.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":36,\"reserver_team_name\":\"Made In Thailand\",\"scrim_id\":5,\"reservation_id\":22,\"scrim_start\":\"2025-09-24 15:23:00\"}', 0, '2025-09-19 18:07:47'),
(7, 152, 'scrim_reservation_created', 'Scrim reservation: Talon Esport', 'Made In Thailand reserved a slot vs your team — scheduled at 2025-09-24 15:23.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":36,\"reserver_team_name\":\"Made In Thailand\",\"scrim_id\":5,\"reservation_id\":\"23\",\"scrim_start\":\"2025-09-24 15:23:00\"}', 0, '2025-09-19 18:08:59'),
(8, 157, 'scrim_reservation_created', 'Scrim reservation: Talon Esport', 'You reserved a slot vs Talon Esport — scheduled at 2025-09-24 15:23.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":36,\"reserver_team_name\":\"Made In Thailand\",\"scrim_id\":5,\"reservation_id\":\"23\",\"scrim_start\":\"2025-09-24 15:23:00\"}', 0, '2025-09-19 18:08:59'),
(9, 164, 'scrim_reservation_created', 'Scrim reservation: Talon Esport', 'Made In Thailand reserved a slot vs your team — scheduled at 2025-09-24 15:23.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":36,\"reserver_team_name\":\"Made In Thailand\",\"scrim_id\":5,\"reservation_id\":\"23\",\"scrim_start\":\"2025-09-24 15:23:00\"}', 0, '2025-09-19 18:08:59'),
(10, 152, 'scrim_reservation_created', 'Scrim reservation: Talon Esport', 'Made In Thailand reserved a slot vs your team — scheduled at 2025-09-24 19:47.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":36,\"reserver_team_name\":\"Made In Thailand\",\"scrim_id\":8,\"reservation_id\":\"24\",\"scrim_start\":\"2025-09-24 19:47:00\"}', 0, '2025-09-24 19:45:33'),
(11, 157, 'scrim_reservation_created', 'Scrim reservation: Talon Esport', 'You reserved a slot vs Talon Esport — scheduled at 2025-09-24 19:47.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":36,\"reserver_team_name\":\"Made In Thailand\",\"scrim_id\":8,\"reservation_id\":\"24\",\"scrim_start\":\"2025-09-24 19:47:00\"}', 0, '2025-09-24 19:45:33'),
(12, 158, 'scrim_reservation_created', 'Scrim reservation: Talon Esport', 'Made In Thailand reserved a slot vs your team — scheduled at 2025-09-24 19:47.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":36,\"reserver_team_name\":\"Made In Thailand\",\"scrim_id\":8,\"reservation_id\":\"24\",\"scrim_start\":\"2025-09-24 19:47:00\"}', 0, '2025-09-24 19:45:33'),
(13, 164, 'scrim_reservation_created', 'Scrim reservation: Talon Esport', 'Made In Thailand reserved a slot vs your team — scheduled at 2025-09-24 19:47.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":36,\"reserver_team_name\":\"Made In Thailand\",\"scrim_id\":8,\"reservation_id\":\"24\",\"scrim_start\":\"2025-09-24 19:47:00\"}', 0, '2025-09-24 19:45:33'),
(14, 157, 'scrim_accepted', 'Scrim accepted', 'Your reservation vs Talon Esport was accepted. Scheduled at 2025-09-24 19:47.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":36,\"reserver_team_name\":\"Made In Thailand\",\"scrim_id\":8,\"reservation_id\":24,\"scrim_start\":\"2025-09-24 19:47:00\"}', 0, '2025-09-24 19:45:46'),
(15, 152, 'scrim_accepted', 'Scrim accepted', 'You accepted the scrim with Made In Thailand. Scheduled at 2025-09-24 19:47.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":36,\"reserver_team_name\":\"Made In Thailand\",\"scrim_id\":8,\"reservation_id\":24,\"scrim_start\":\"2025-09-24 19:47:00\"}', 0, '2025-09-24 19:45:46'),
(16, 164, 'scrim_accepted', 'Scrim accepted', 'You accepted the scrim with Made In Thailand. Scheduled at 2025-09-24 19:47.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":36,\"reserver_team_name\":\"Made In Thailand\",\"scrim_id\":8,\"reservation_id\":24,\"scrim_start\":\"2025-09-24 19:47:00\"}', 0, '2025-09-24 19:45:46'),
(17, 158, 'scrim_accepted', 'Scrim accepted', 'You accepted the scrim with Made In Thailand. Scheduled at 2025-09-24 19:47.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":36,\"reserver_team_name\":\"Made In Thailand\",\"scrim_id\":8,\"reservation_id\":24,\"scrim_start\":\"2025-09-24 19:47:00\"}', 0, '2025-09-24 19:45:46'),
(18, 152, 'scrim_reservation_created', 'Scrim reservation: Made In Thailand', 'You reserved a slot vs Made In Thailand — scheduled at 2025-10-01 13:02.', '{\"owner_team_id\":36,\"owner_team_name\":\"Made In Thailand\",\"reserver_team_id\":49,\"reserver_team_name\":\"Talon Esport\",\"scrim_id\":9,\"reservation_id\":\"25\",\"scrim_start\":\"2025-10-01 13:02:00\"}', 0, '2025-09-24 19:57:39'),
(19, 157, 'scrim_reservation_created', 'Scrim reservation: Made In Thailand', 'Talon Esport reserved a slot vs your team — scheduled at 2025-10-01 13:02.', '{\"owner_team_id\":36,\"owner_team_name\":\"Made In Thailand\",\"reserver_team_id\":49,\"reserver_team_name\":\"Talon Esport\",\"scrim_id\":9,\"reservation_id\":\"25\",\"scrim_start\":\"2025-10-01 13:02:00\"}', 0, '2025-09-24 19:57:39'),
(20, 158, 'scrim_reservation_created', 'Scrim reservation: Made In Thailand', 'You reserved a slot vs Made In Thailand — scheduled at 2025-10-01 13:02.', '{\"owner_team_id\":36,\"owner_team_name\":\"Made In Thailand\",\"reserver_team_id\":49,\"reserver_team_name\":\"Talon Esport\",\"scrim_id\":9,\"reservation_id\":\"25\",\"scrim_start\":\"2025-10-01 13:02:00\"}', 0, '2025-09-24 19:57:39'),
(21, 164, 'scrim_reservation_created', 'Scrim reservation: Made In Thailand', 'You reserved a slot vs Made In Thailand — scheduled at 2025-10-01 13:02.', '{\"owner_team_id\":36,\"owner_team_name\":\"Made In Thailand\",\"reserver_team_id\":49,\"reserver_team_name\":\"Talon Esport\",\"scrim_id\":9,\"reservation_id\":\"25\",\"scrim_start\":\"2025-10-01 13:02:00\"}', 0, '2025-09-24 19:57:39'),
(22, 157, 'scrim_accepted', 'Scrim accepted', 'You accepted the scrim with Talon Esport. Scheduled at 2025-10-01 13:02.', '{\"owner_team_id\":36,\"owner_team_name\":\"Made In Thailand\",\"reserver_team_id\":49,\"reserver_team_name\":\"Talon Esport\",\"scrim_id\":9,\"reservation_id\":25,\"scrim_start\":\"2025-10-01 13:02:00\"}', 0, '2025-09-24 19:57:45'),
(23, 152, 'scrim_accepted', 'Scrim accepted', 'Your reservation vs Made In Thailand was accepted. Scheduled at 2025-10-01 13:02.', '{\"owner_team_id\":36,\"owner_team_name\":\"Made In Thailand\",\"reserver_team_id\":49,\"reserver_team_name\":\"Talon Esport\",\"scrim_id\":9,\"reservation_id\":25,\"scrim_start\":\"2025-10-01 13:02:00\"}', 0, '2025-09-24 19:57:45'),
(24, 164, 'scrim_accepted', 'Scrim accepted', 'Your reservation vs Made In Thailand was accepted. Scheduled at 2025-10-01 13:02.', '{\"owner_team_id\":36,\"owner_team_name\":\"Made In Thailand\",\"reserver_team_id\":49,\"reserver_team_name\":\"Talon Esport\",\"scrim_id\":9,\"reservation_id\":25,\"scrim_start\":\"2025-10-01 13:02:00\"}', 0, '2025-09-24 19:57:45'),
(25, 158, 'scrim_accepted', 'Scrim accepted', 'Your reservation vs Made In Thailand was accepted. Scheduled at 2025-10-01 13:02.', '{\"owner_team_id\":36,\"owner_team_name\":\"Made In Thailand\",\"reserver_team_id\":49,\"reserver_team_name\":\"Talon Esport\",\"scrim_id\":9,\"reservation_id\":25,\"scrim_start\":\"2025-10-01 13:02:00\"}', 0, '2025-09-24 19:57:45'),
(26, 157, 'scrim_declined', 'Scrim declined', 'Your reservation vs Talon Esport was declined. Scheduled at 2025-09-24 15:23.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":36,\"reserver_team_name\":\"Made In Thailand\",\"scrim_id\":5,\"reservation_id\":23,\"scrim_start\":\"2025-09-24 15:23:00\"}', 0, '2025-09-24 19:58:03'),
(27, 152, 'scrim_declined', 'Scrim declined', 'You declined the scrim with Made In Thailand. Scheduled at 2025-09-24 15:23.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":36,\"reserver_team_name\":\"Made In Thailand\",\"scrim_id\":5,\"reservation_id\":23,\"scrim_start\":\"2025-09-24 15:23:00\"}', 0, '2025-09-24 19:58:03'),
(28, 164, 'scrim_declined', 'Scrim declined', 'You declined the scrim with Made In Thailand. Scheduled at 2025-09-24 15:23.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":36,\"reserver_team_name\":\"Made In Thailand\",\"scrim_id\":5,\"reservation_id\":23,\"scrim_start\":\"2025-09-24 15:23:00\"}', 0, '2025-09-24 19:58:03'),
(29, 158, 'scrim_declined', 'Scrim declined', 'You declined the scrim with Made In Thailand. Scheduled at 2025-09-24 15:23.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":36,\"reserver_team_name\":\"Made In Thailand\",\"scrim_id\":5,\"reservation_id\":23,\"scrim_start\":\"2025-09-24 15:23:00\"}', 0, '2025-09-24 19:58:03'),
(30, 152, 'scrim_reservation_created', 'Scrim reservation: Made In Thailand', 'You reserved a slot vs Made In Thailand — scheduled at 2025-10-01 13:02.', '{\"owner_team_id\":36,\"owner_team_name\":\"Made In Thailand\",\"reserver_team_id\":49,\"reserver_team_name\":\"Talon Esport\",\"scrim_id\":9,\"reservation_id\":\"26\",\"scrim_start\":\"2025-10-01 13:02:00\"}', 0, '2025-09-24 19:59:03'),
(31, 157, 'scrim_reservation_created', 'Scrim reservation: Made In Thailand', 'Talon Esport reserved a slot vs your team — scheduled at 2025-10-01 13:02.', '{\"owner_team_id\":36,\"owner_team_name\":\"Made In Thailand\",\"reserver_team_id\":49,\"reserver_team_name\":\"Talon Esport\",\"scrim_id\":9,\"reservation_id\":\"26\",\"scrim_start\":\"2025-10-01 13:02:00\"}', 0, '2025-09-24 19:59:03'),
(32, 158, 'scrim_reservation_created', 'Scrim reservation: Made In Thailand', 'You reserved a slot vs Made In Thailand — scheduled at 2025-10-01 13:02.', '{\"owner_team_id\":36,\"owner_team_name\":\"Made In Thailand\",\"reserver_team_id\":49,\"reserver_team_name\":\"Talon Esport\",\"scrim_id\":9,\"reservation_id\":\"26\",\"scrim_start\":\"2025-10-01 13:02:00\"}', 0, '2025-09-24 19:59:03'),
(33, 164, 'scrim_reservation_created', 'Scrim reservation: Made In Thailand', 'You reserved a slot vs Made In Thailand — scheduled at 2025-10-01 13:02.', '{\"owner_team_id\":36,\"owner_team_name\":\"Made In Thailand\",\"reserver_team_id\":49,\"reserver_team_name\":\"Talon Esport\",\"scrim_id\":9,\"reservation_id\":\"26\",\"scrim_start\":\"2025-10-01 13:02:00\"}', 0, '2025-09-24 19:59:03'),
(34, 157, 'scrim_accepted', 'Scrim accepted', 'You accepted the scrim with Talon Esport. Scheduled at 2025-10-01 13:02.', '{\"owner_team_id\":36,\"owner_team_name\":\"Made In Thailand\",\"reserver_team_id\":49,\"reserver_team_name\":\"Talon Esport\",\"scrim_id\":9,\"reservation_id\":26,\"scrim_start\":\"2025-10-01 13:02:00\"}', 0, '2025-09-24 19:59:09'),
(35, 152, 'scrim_accepted', 'Scrim accepted', 'Your reservation vs Made In Thailand was accepted. Scheduled at 2025-10-01 13:02.', '{\"owner_team_id\":36,\"owner_team_name\":\"Made In Thailand\",\"reserver_team_id\":49,\"reserver_team_name\":\"Talon Esport\",\"scrim_id\":9,\"reservation_id\":26,\"scrim_start\":\"2025-10-01 13:02:00\"}', 0, '2025-09-24 19:59:09'),
(36, 164, 'scrim_accepted', 'Scrim accepted', 'Your reservation vs Made In Thailand was accepted. Scheduled at 2025-10-01 13:02.', '{\"owner_team_id\":36,\"owner_team_name\":\"Made In Thailand\",\"reserver_team_id\":49,\"reserver_team_name\":\"Talon Esport\",\"scrim_id\":9,\"reservation_id\":26,\"scrim_start\":\"2025-10-01 13:02:00\"}', 0, '2025-09-24 19:59:09'),
(37, 158, 'scrim_accepted', 'Scrim accepted', 'Your reservation vs Made In Thailand was accepted. Scheduled at 2025-10-01 13:02.', '{\"owner_team_id\":36,\"owner_team_name\":\"Made In Thailand\",\"reserver_team_id\":49,\"reserver_team_name\":\"Talon Esport\",\"scrim_id\":9,\"reservation_id\":26,\"scrim_start\":\"2025-10-01 13:02:00\"}', 0, '2025-09-24 19:59:09'),
(38, 152, 'scrim_reservation_created', 'Scrim reservation: ไร้สมอง', 'You reserved a slot vs ไร้สมอง — scheduled at 2026-02-19 20:55.', '{\"owner_team_id\":50,\"owner_team_name\":\"\\u0e44\\u0e23\\u0e49\\u0e2a\\u0e21\\u0e2d\\u0e07\",\"reserver_team_id\":49,\"reserver_team_name\":\"Talon Esport\",\"scrim_id\":16,\"reservation_id\":\"27\",\"scrim_start\":\"2026-02-19 20:55:00\"}', 0, '2026-02-04 20:56:17'),
(39, 158, 'scrim_reservation_created', 'Scrim reservation: ไร้สมอง', 'You reserved a slot vs ไร้สมอง — scheduled at 2026-02-19 20:55.', '{\"owner_team_id\":50,\"owner_team_name\":\"\\u0e44\\u0e23\\u0e49\\u0e2a\\u0e21\\u0e2d\\u0e07\",\"reserver_team_id\":49,\"reserver_team_name\":\"Talon Esport\",\"scrim_id\":16,\"reservation_id\":\"27\",\"scrim_start\":\"2026-02-19 20:55:00\"}', 0, '2026-02-04 20:56:17'),
(40, 164, 'scrim_reservation_created', 'Scrim reservation: ไร้สมอง', 'You reserved a slot vs ไร้สมอง — scheduled at 2026-02-19 20:55.', '{\"owner_team_id\":50,\"owner_team_name\":\"\\u0e44\\u0e23\\u0e49\\u0e2a\\u0e21\\u0e2d\\u0e07\",\"reserver_team_id\":49,\"reserver_team_name\":\"Talon Esport\",\"scrim_id\":16,\"reservation_id\":\"27\",\"scrim_start\":\"2026-02-19 20:55:00\"}', 0, '2026-02-04 20:56:17'),
(41, 165, 'scrim_reservation_created', 'Scrim reservation: ไร้สมอง', 'You reserved a slot vs ไร้สมอง — scheduled at 2026-02-19 20:55.', '{\"owner_team_id\":50,\"owner_team_name\":\"\\u0e44\\u0e23\\u0e49\\u0e2a\\u0e21\\u0e2d\\u0e07\",\"reserver_team_id\":49,\"reserver_team_name\":\"Talon Esport\",\"scrim_id\":16,\"reservation_id\":\"27\",\"scrim_start\":\"2026-02-19 20:55:00\"}', 0, '2026-02-04 20:56:17'),
(42, 166, 'scrim_reservation_created', 'Scrim reservation: ไร้สมอง', 'You reserved a slot vs ไร้สมอง — scheduled at 2026-02-19 20:55.', '{\"owner_team_id\":50,\"owner_team_name\":\"\\u0e44\\u0e23\\u0e49\\u0e2a\\u0e21\\u0e2d\\u0e07\",\"reserver_team_id\":49,\"reserver_team_name\":\"Talon Esport\",\"scrim_id\":16,\"reservation_id\":\"27\",\"scrim_start\":\"2026-02-19 20:55:00\"}', 0, '2026-02-04 20:56:17'),
(43, 167, 'scrim_reservation_created', 'Scrim reservation: ไร้สมอง', 'Talon Esport reserved a slot vs your team — scheduled at 2026-02-19 20:55.', '{\"owner_team_id\":50,\"owner_team_name\":\"\\u0e44\\u0e23\\u0e49\\u0e2a\\u0e21\\u0e2d\\u0e07\",\"reserver_team_id\":49,\"reserver_team_name\":\"Talon Esport\",\"scrim_id\":16,\"reservation_id\":\"27\",\"scrim_start\":\"2026-02-19 20:55:00\"}', 0, '2026-02-04 20:56:17'),
(44, 152, 'scrim_reservation_created', 'Scrim reservation: Talon Esport', 'ไร้สมอง reserved a slot vs your team — scheduled at 2026-02-04 22:56.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":50,\"reserver_team_name\":\"\\u0e44\\u0e23\\u0e49\\u0e2a\\u0e21\\u0e2d\\u0e07\",\"scrim_id\":17,\"reservation_id\":\"28\",\"scrim_start\":\"2026-02-04 22:56:00\"}', 0, '2026-02-04 20:56:27'),
(45, 158, 'scrim_reservation_created', 'Scrim reservation: Talon Esport', 'ไร้สมอง reserved a slot vs your team — scheduled at 2026-02-04 22:56.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":50,\"reserver_team_name\":\"\\u0e44\\u0e23\\u0e49\\u0e2a\\u0e21\\u0e2d\\u0e07\",\"scrim_id\":17,\"reservation_id\":\"28\",\"scrim_start\":\"2026-02-04 22:56:00\"}', 0, '2026-02-04 20:56:27'),
(46, 164, 'scrim_reservation_created', 'Scrim reservation: Talon Esport', 'ไร้สมอง reserved a slot vs your team — scheduled at 2026-02-04 22:56.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":50,\"reserver_team_name\":\"\\u0e44\\u0e23\\u0e49\\u0e2a\\u0e21\\u0e2d\\u0e07\",\"scrim_id\":17,\"reservation_id\":\"28\",\"scrim_start\":\"2026-02-04 22:56:00\"}', 0, '2026-02-04 20:56:27'),
(47, 165, 'scrim_reservation_created', 'Scrim reservation: Talon Esport', 'ไร้สมอง reserved a slot vs your team — scheduled at 2026-02-04 22:56.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":50,\"reserver_team_name\":\"\\u0e44\\u0e23\\u0e49\\u0e2a\\u0e21\\u0e2d\\u0e07\",\"scrim_id\":17,\"reservation_id\":\"28\",\"scrim_start\":\"2026-02-04 22:56:00\"}', 0, '2026-02-04 20:56:27'),
(48, 166, 'scrim_reservation_created', 'Scrim reservation: Talon Esport', 'ไร้สมอง reserved a slot vs your team — scheduled at 2026-02-04 22:56.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":50,\"reserver_team_name\":\"\\u0e44\\u0e23\\u0e49\\u0e2a\\u0e21\\u0e2d\\u0e07\",\"scrim_id\":17,\"reservation_id\":\"28\",\"scrim_start\":\"2026-02-04 22:56:00\"}', 0, '2026-02-04 20:56:27'),
(49, 167, 'scrim_reservation_created', 'Scrim reservation: Talon Esport', 'You reserved a slot vs Talon Esport — scheduled at 2026-02-04 22:56.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":50,\"reserver_team_name\":\"\\u0e44\\u0e23\\u0e49\\u0e2a\\u0e21\\u0e2d\\u0e07\",\"scrim_id\":17,\"reservation_id\":\"28\",\"scrim_start\":\"2026-02-04 22:56:00\"}', 0, '2026-02-04 20:56:27'),
(50, 152, 'scrim_accepted', 'Scrim accepted', 'Your reservation vs ไร้สมอง was accepted. Scheduled at 2026-02-19 20:55.', '{\"owner_team_id\":50,\"owner_team_name\":\"\\u0e44\\u0e23\\u0e49\\u0e2a\\u0e21\\u0e2d\\u0e07\",\"reserver_team_id\":49,\"reserver_team_name\":\"Talon Esport\",\"scrim_id\":16,\"reservation_id\":27,\"scrim_start\":\"2026-02-19 20:55:00\"}', 0, '2026-02-04 20:56:32'),
(51, 164, 'scrim_accepted', 'Scrim accepted', 'Your reservation vs ไร้สมอง was accepted. Scheduled at 2026-02-19 20:55.', '{\"owner_team_id\":50,\"owner_team_name\":\"\\u0e44\\u0e23\\u0e49\\u0e2a\\u0e21\\u0e2d\\u0e07\",\"reserver_team_id\":49,\"reserver_team_name\":\"Talon Esport\",\"scrim_id\":16,\"reservation_id\":27,\"scrim_start\":\"2026-02-19 20:55:00\"}', 0, '2026-02-04 20:56:32'),
(52, 158, 'scrim_accepted', 'Scrim accepted', 'Your reservation vs ไร้สมอง was accepted. Scheduled at 2026-02-19 20:55.', '{\"owner_team_id\":50,\"owner_team_name\":\"\\u0e44\\u0e23\\u0e49\\u0e2a\\u0e21\\u0e2d\\u0e07\",\"reserver_team_id\":49,\"reserver_team_name\":\"Talon Esport\",\"scrim_id\":16,\"reservation_id\":27,\"scrim_start\":\"2026-02-19 20:55:00\"}', 0, '2026-02-04 20:56:32'),
(53, 165, 'scrim_accepted', 'Scrim accepted', 'Your reservation vs ไร้สมอง was accepted. Scheduled at 2026-02-19 20:55.', '{\"owner_team_id\":50,\"owner_team_name\":\"\\u0e44\\u0e23\\u0e49\\u0e2a\\u0e21\\u0e2d\\u0e07\",\"reserver_team_id\":49,\"reserver_team_name\":\"Talon Esport\",\"scrim_id\":16,\"reservation_id\":27,\"scrim_start\":\"2026-02-19 20:55:00\"}', 0, '2026-02-04 20:56:32'),
(54, 166, 'scrim_accepted', 'Scrim accepted', 'Your reservation vs ไร้สมอง was accepted. Scheduled at 2026-02-19 20:55.', '{\"owner_team_id\":50,\"owner_team_name\":\"\\u0e44\\u0e23\\u0e49\\u0e2a\\u0e21\\u0e2d\\u0e07\",\"reserver_team_id\":49,\"reserver_team_name\":\"Talon Esport\",\"scrim_id\":16,\"reservation_id\":27,\"scrim_start\":\"2026-02-19 20:55:00\"}', 0, '2026-02-04 20:56:32'),
(55, 167, 'scrim_accepted', 'Scrim accepted', 'You accepted the scrim with Talon Esport. Scheduled at 2026-02-19 20:55.', '{\"owner_team_id\":50,\"owner_team_name\":\"\\u0e44\\u0e23\\u0e49\\u0e2a\\u0e21\\u0e2d\\u0e07\",\"reserver_team_id\":49,\"reserver_team_name\":\"Talon Esport\",\"scrim_id\":16,\"reservation_id\":27,\"scrim_start\":\"2026-02-19 20:55:00\"}', 0, '2026-02-04 20:56:32'),
(56, 152, 'scrim_reservation_created', 'Scrim reservation: Talon Esport', 'อนุบาลหนองจอก reserved a slot vs your team — scheduled at 2026-02-04 22:56.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":51,\"reserver_team_name\":\"\\u0e2d\\u0e19\\u0e38\\u0e1a\\u0e32\\u0e25\\u0e2b\\u0e19\\u0e2d\\u0e07\\u0e08\\u0e2d\\u0e01\",\"scrim_id\":17,\"reservation_id\":\"29\",\"scrim_start\":\"2026-02-04 22:56:00\"}', 0, '2026-02-04 21:12:08'),
(57, 158, 'scrim_reservation_created', 'Scrim reservation: Talon Esport', 'อนุบาลหนองจอก reserved a slot vs your team — scheduled at 2026-02-04 22:56.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":51,\"reserver_team_name\":\"\\u0e2d\\u0e19\\u0e38\\u0e1a\\u0e32\\u0e25\\u0e2b\\u0e19\\u0e2d\\u0e07\\u0e08\\u0e2d\\u0e01\",\"scrim_id\":17,\"reservation_id\":\"29\",\"scrim_start\":\"2026-02-04 22:56:00\"}', 0, '2026-02-04 21:12:08'),
(58, 164, 'scrim_reservation_created', 'Scrim reservation: Talon Esport', 'อนุบาลหนองจอก reserved a slot vs your team — scheduled at 2026-02-04 22:56.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":51,\"reserver_team_name\":\"\\u0e2d\\u0e19\\u0e38\\u0e1a\\u0e32\\u0e25\\u0e2b\\u0e19\\u0e2d\\u0e07\\u0e08\\u0e2d\\u0e01\",\"scrim_id\":17,\"reservation_id\":\"29\",\"scrim_start\":\"2026-02-04 22:56:00\"}', 0, '2026-02-04 21:12:08'),
(59, 165, 'scrim_reservation_created', 'Scrim reservation: Talon Esport', 'อนุบาลหนองจอก reserved a slot vs your team — scheduled at 2026-02-04 22:56.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":51,\"reserver_team_name\":\"\\u0e2d\\u0e19\\u0e38\\u0e1a\\u0e32\\u0e25\\u0e2b\\u0e19\\u0e2d\\u0e07\\u0e08\\u0e2d\\u0e01\",\"scrim_id\":17,\"reservation_id\":\"29\",\"scrim_start\":\"2026-02-04 22:56:00\"}', 0, '2026-02-04 21:12:08'),
(60, 166, 'scrim_reservation_created', 'Scrim reservation: Talon Esport', 'อนุบาลหนองจอก reserved a slot vs your team — scheduled at 2026-02-04 22:56.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":51,\"reserver_team_name\":\"\\u0e2d\\u0e19\\u0e38\\u0e1a\\u0e32\\u0e25\\u0e2b\\u0e19\\u0e2d\\u0e07\\u0e08\\u0e2d\\u0e01\",\"scrim_id\":17,\"reservation_id\":\"29\",\"scrim_start\":\"2026-02-04 22:56:00\"}', 0, '2026-02-04 21:12:08'),
(61, 169, 'scrim_reservation_created', 'Scrim reservation: Talon Esport', 'You reserved a slot vs Talon Esport — scheduled at 2026-02-04 22:56.', '{\"owner_team_id\":49,\"owner_team_name\":\"Talon Esport\",\"reserver_team_id\":51,\"reserver_team_name\":\"\\u0e2d\\u0e19\\u0e38\\u0e1a\\u0e32\\u0e25\\u0e2b\\u0e19\\u0e2d\\u0e07\\u0e08\\u0e2d\\u0e01\",\"scrim_id\":17,\"reservation_id\":\"29\",\"scrim_start\":\"2026-02-04 22:56:00\"}', 0, '2026-02-04 21:12:08');

-- --------------------------------------------------------

--
-- Table structure for table `valorant_agents`
--

CREATE TABLE `valorant_agents` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `role` enum('Controller','Sentinel','Initiator','Duelist') NOT NULL,
  `image_url` varchar(500) NOT NULL COMMENT 'URL or path: valorant-api URL or img/agents/filename',
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `valorant_agents`
--

INSERT INTO `valorant_agents` (`id`, `name`, `role`, `image_url`, `display_order`, `is_active`, `created_at`) VALUES
(1, 'Jett', 'Duelist', 'https://media.valorant-api.com/agents/add6443a-41bd-e414-f6ad-e58d267f4e95/displayicon.png', 1, 1, '2026-02-07 04:58:22'),
(2, 'Raze', 'Duelist', 'https://media.valorant-api.com/agents/f94c3b30-42be-e959-889c-5aa313dba261/displayicon.png', 2, 1, '2026-02-07 04:58:22'),
(3, 'Breach', 'Initiator', 'https://media.valorant-api.com/agents/5f8d3a7f-467b-97f3-062c-13acf203c006/displayicon.png', 3, 1, '2026-02-07 04:58:22'),
(4, 'Omen', 'Controller', 'https://media.valorant-api.com/agents/8e253930-4c05-31dd-1b6c-968525494517/displayicon.png', 4, 1, '2026-02-07 04:58:22'),
(5, 'Brimstone', 'Controller', 'https://media.valorant-api.com/agents/9f0d8ba9-4140-b941-57d3-a7ad57c6b417/displayicon.png', 5, 1, '2026-02-07 04:58:22'),
(6, 'Phoenix', 'Duelist', 'https://media.valorant-api.com/agents/eb93336a-449b-9c1b-0a54-a891f7921d69/displayicon.png', 6, 1, '2026-02-07 04:58:22'),
(7, 'Sage', 'Sentinel', 'https://media.valorant-api.com/agents/569fdd95-4d10-43ab-ca70-79becc718b46/displayicon.png', 7, 1, '2026-02-07 04:58:22'),
(8, 'Sova', 'Initiator', 'https://media.valorant-api.com/agents/320b2a48-4d9b-a075-30f1-1f93a9b638fa/displayicon.png', 8, 1, '2026-02-07 04:58:22'),
(9, 'Viper', 'Controller', 'https://media.valorant-api.com/agents/707eab51-4836-f488-046a-cda6bf494859/displayicon.png', 9, 1, '2026-02-07 04:58:22'),
(10, 'Cypher', 'Sentinel', 'https://media.valorant-api.com/agents/117ed9e3-49f3-6512-3ccf-0cada7e3823b/displayicon.png', 10, 1, '2026-02-07 04:58:22'),
(11, 'Reyna', 'Duelist', 'https://media.valorant-api.com/agents/a3bfb853-43b2-7238-a4f1-ad90e9e46bcc/displayicon.png', 11, 1, '2026-02-07 04:58:22'),
(12, 'Killjoy', 'Sentinel', 'https://media.valorant-api.com/agents/1e58de9c-4950-5125-93e9-a0aee9f98746/displayicon.png', 12, 1, '2026-02-07 04:58:22'),
(13, 'Skye', 'Initiator', 'https://media.valorant-api.com/agents/6f2a04ca-43e0-be17-7f36-b3908627744d/displayicon.png', 13, 1, '2026-02-07 04:58:22'),
(14, 'Yoru', 'Duelist', 'https://media.valorant-api.com/agents/7f94d92c-4234-0a36-9646-3a87eb8b5c89/displayicon.png', 14, 1, '2026-02-07 04:58:22'),
(15, 'Astra', 'Controller', 'https://media.valorant-api.com/agents/41fb69c1-4189-7b37-f117-bcaf1e96f1bf/displayicon.png', 15, 1, '2026-02-07 04:58:22'),
(16, 'KAY/O', 'Initiator', 'https://media.valorant-api.com/agents/601dbbe7-43ce-be57-2a40-4abd24953621/displayicon.png', 16, 1, '2026-02-07 04:58:22'),
(17, 'Chamber', 'Sentinel', 'https://media.valorant-api.com/agents/22697a3d-45bf-8dd7-4fec-84a9e28c69d7/displayicon.png', 17, 1, '2026-02-07 04:58:22'),
(18, 'Neon', 'Duelist', 'https://media.valorant-api.com/agents/bb2a4828-46eb-8cd1-e765-15848195d751/displayicon.png', 18, 1, '2026-02-07 04:58:22'),
(19, 'Fade', 'Initiator', 'https://media.valorant-api.com/agents/dade69b4-4f5a-8528-247b-219e5a1facd6/displayicon.png', 19, 1, '2026-02-07 04:58:22'),
(20, 'Harbor', 'Controller', 'https://media.valorant-api.com/agents/95b78ed7-4637-86d9-7e41-71ba8c293152/displayicon.png', 20, 1, '2026-02-07 04:58:22'),
(21, 'Gekko', 'Initiator', 'https://media.valorant-api.com/agents/e370fa57-4757-3604-3648-499e1f642d3f/displayicon.png', 21, 1, '2026-02-07 04:58:22'),
(22, 'Deadlock', 'Sentinel', 'https://media.valorant-api.com/agents/cc8b64c8-4b25-4ff9-6e7f-37b4da43d235/displayicon.png', 22, 1, '2026-02-07 04:58:22'),
(23, 'Iso', 'Duelist', 'https://media.valorant-api.com/agents/0e38b510-41a8-5780-5e8f-568b2a4f2d6c/displayicon.png', 23, 1, '2026-02-07 04:58:22'),
(24, 'Clove', 'Controller', 'https://media.valorant-api.com/agents/1dbf2edd-4729-0984-3115-daa5eed44993/displayicon.png', 24, 1, '2026-02-07 04:58:22');

-- --------------------------------------------------------

--
-- Table structure for table `valorant_maps`
--

CREATE TABLE `valorant_maps` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `image_filename` varchar(100) NOT NULL COMMENT 'e.g. ascent.png - used in img/maps/ and img/maps_button/',
  `button_image_filename` varchar(255) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `valorant_maps`
--

INSERT INTO `valorant_maps` (`id`, `name`, `image_filename`, `button_image_filename`, `display_order`, `is_active`, `created_at`) VALUES
(1, 'Ascent', 'ascent.png', 'ascent.png', 1, 1, '2026-02-07 04:58:22'),
(2, 'Bind', 'bind.png', 'bind.png', 2, 1, '2026-02-07 04:58:22'),
(3, 'Haven', 'haven.png', 'haven.png', 3, 1, '2026-02-07 04:58:22'),
(4, 'Split', 'split.png', 'split.png', 4, 1, '2026-02-07 04:58:22'),
(5, 'Icebox', 'icebox.png', 'icebox.png', 5, 1, '2026-02-07 04:58:22'),
(6, 'Breeze', 'breeze.png', 'breeze.png', 6, 1, '2026-02-07 04:58:22'),
(7, 'Fracture', 'fracture.png', 'fracture.png', 7, 1, '2026-02-07 04:58:22'),
(8, 'Pearl', 'pearl.png', 'pearl.png', 8, 1, '2026-02-07 04:58:22'),
(9, 'Lotus', 'lotus.png', 'lotus.png', 9, 1, '2026-02-07 04:58:22'),
(10, 'Sunset', 'sunset.png', 'sunset.png', 10, 1, '2026-02-07 04:58:22'),
(11, 'Abyss', 'abyss.png', 'abyss.png', 11, 1, '2026-02-07 04:58:22'),
(13, 'Kasbah', 'kasbah.webp', 'kasbah.png', 0, 1, '2026-02-07 05:42:53');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ban_history`
--
ALTER TABLE `ban_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `lfp_applications`
--
ALTER TABLE `lfp_applications`
  ADD PRIMARY KEY (`app_id`),
  ADD KEY `post_id_idx` (`post_id`),
  ADD KEY `user_id_idx` (`user_id`);

--
-- Indexes for table `lfp_posts`
--
ALTER TABLE `lfp_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `lft_posts`
--
ALTER TABLE `lft_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user` (`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `lineups`
--
ALTER TABLE `lineups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `pending_users`
--
ALTER TABLE `pending_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `player_rank_cache`
--
ALTER TABLE `player_rank_cache`
  ADD PRIMARY KEY (`riot_id`,`region`);

--
-- Indexes for table `premier_teams`
--
ALTER TABLE `premier_teams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_team_tag` (`team_tag`),
  ADD KEY `fk_premier_teams_created_by` (`created_by`);

--
-- Indexes for table `premier_team_members`
--
ALTER TABLE `premier_team_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_member` (`premier_team_id`,`user_id`),
  ADD KEY `fk_premier_team_members_user` (`user_id`);

--
-- Indexes for table `private_messages`
--
ALTER TABLE `private_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `idx_sender` (`sender_id`),
  ADD KEY `idx_receiver` (`receiver_id`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_room` (`room_id`);

--
-- Indexes for table `scrims`
--
ALTER TABLE `scrims`
  ADD PRIMARY KEY (`scrim_id`),
  ADD KEY `team_id` (`team_id`),
  ADD KEY `scrim_start` (`scrim_start`),
  ADD KEY `fk_scrims_created_by` (`created_by`);

--
-- Indexes for table `scrim_reservations`
--
ALTER TABLE `scrim_reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD UNIQUE KEY `uniq_scrim_user` (`scrim_id`,`user_id`),
  ADD KEY `scrim_id` (`scrim_id`),
  ADD KEY `sr_user_fk` (`user_id`);

--
-- Indexes for table `scrim_reservation_notifications`
--
ALTER TABLE `scrim_reservation_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `manager_id` (`manager_id`),
  ADD KEY `scrim_id` (`scrim_id`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`team_id`),
  ADD UNIQUE KEY `uniq_abbr` (`abbreviation`),
  ADD KEY `fk_manager` (`manager_id`);

--
-- Indexes for table `team_join_requests`
--
ALTER TABLE `team_join_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `team_id` (`team_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `team_id` (`team_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `team_rank_cache`
--
ALTER TABLE `team_rank_cache`
  ADD PRIMARY KEY (`team_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id_idx` (`user_id`);

--
-- Indexes for table `valorant_agents`
--
ALTER TABLE `valorant_agents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `valorant_maps`
--
ALTER TABLE `valorant_maps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ban_history`
--
ALTER TABLE `ban_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `lfp_applications`
--
ALTER TABLE `lfp_applications`
  MODIFY `app_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `lfp_posts`
--
ALTER TABLE `lfp_posts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `lft_posts`
--
ALTER TABLE `lft_posts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `lineups`
--
ALTER TABLE `lineups`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;

--
-- AUTO_INCREMENT for table `pending_users`
--
ALTER TABLE `pending_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `premier_teams`
--
ALTER TABLE `premier_teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `premier_team_members`
--
ALTER TABLE `premier_team_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `private_messages`
--
ALTER TABLE `private_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `scrims`
--
ALTER TABLE `scrims`
  MODIFY `scrim_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `scrim_reservations`
--
ALTER TABLE `scrim_reservations`
  MODIFY `reservation_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `scrim_reservation_notifications`
--
ALTER TABLE `scrim_reservation_notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `team_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `team_join_requests`
--
ALTER TABLE `team_join_requests`
  MODIFY `request_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=187;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;

--
-- AUTO_INCREMENT for table `user_notifications`
--
ALTER TABLE `user_notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `valorant_agents`
--
ALTER TABLE `valorant_agents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `valorant_maps`
--
ALTER TABLE `valorant_maps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `lfp_posts`
--
ALTER TABLE `lfp_posts`
  ADD CONSTRAINT `lfp_posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `lft_posts`
--
ALTER TABLE `lft_posts`
  ADD CONSTRAINT `lft_posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `lineups`
--
ALTER TABLE `lineups`
  ADD CONSTRAINT `fk_lineups_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_messages_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_messages_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE;

--
-- Constraints for table `premier_teams`
--
ALTER TABLE `premier_teams`
  ADD CONSTRAINT `fk_premier_teams_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `premier_team_members`
--
ALTER TABLE `premier_team_members`
  ADD CONSTRAINT `fk_premier_team_members_team` FOREIGN KEY (`premier_team_id`) REFERENCES `premier_teams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_premier_team_members_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `private_messages`
--
ALTER TABLE `private_messages`
  ADD CONSTRAINT `fk_pm_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pm_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `scrims`
--
ALTER TABLE `scrims`
  ADD CONSTRAINT `fk_scrims_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_scrims_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`) ON DELETE SET NULL;

--
-- Constraints for table `scrim_reservations`
--
ALTER TABLE `scrim_reservations`
  ADD CONSTRAINT `sr_scrim_fk` FOREIGN KEY (`scrim_id`) REFERENCES `scrims` (`scrim_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sr_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `teams`
--
ALTER TABLE `teams`
  ADD CONSTRAINT `fk_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `team_join_requests`
--
ALTER TABLE `team_join_requests`
  ADD CONSTRAINT `team_join_requests_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_join_requests_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `team_members`
--
ALTER TABLE `team_members`
  ADD CONSTRAINT `team_members_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
