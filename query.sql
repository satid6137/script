-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 21, 2025 at 10:33 AM
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
-- Database: `query11153`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_type` varchar(50) DEFAULT NULL,
  `target` varchar(100) DEFAULT NULL,
  `detail` mediumtext DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`log_id`, `user_id`, `action_type`, `target`, `detail`, `timestamp`) VALUES
(1, 7, 'delete_user', 'user:1', 'ลบผู้ใช้', '2025-10-22 15:22:55'),
(2, 7, 'delete_user', 'user:2', 'ลบผู้ใช้', '2025-10-22 15:22:57'),
(3, 7, 'delete_user', 'user:3', 'ลบผู้ใช้', '2025-10-22 15:22:59'),
(7, 7, 'login', NULL, 'เข้าสู่ระบบสำเร็จ', '2025-11-03 14:08:23'),
(8, 7, 'login', NULL, 'เข้าสู่ระบบสำเร็จ', '2025-11-04 09:57:51'),
(9, 7, 'login', NULL, 'เข้าสู่ระบบสำเร็จ', '2025-11-04 14:20:24'),
(10, 7, 'login', NULL, 'เข้าสู่ระบบสำเร็จ', '2025-11-04 14:35:30'),
(11, 7, 'login', NULL, 'เข้าสู่ระบบสำเร็จ', '2025-11-21 10:55:52'),
(12, 7, 'login', NULL, 'เข้าสู่ระบบสำเร็จ', '2025-11-21 14:58:13'),
(13, 7, 'login', NULL, 'เข้าสู่ระบบสำเร็จ', '2025-11-21 14:58:27'),
(14, 7, 'login', NULL, 'เข้าสู่ระบบสำเร็จ', '2025-11-21 15:06:58'),
(15, 7, 'login', NULL, 'เข้าสู่ระบบสำเร็จ', '2025-11-21 15:09:13'),
(16, 7, 'login', NULL, 'เข้าสู่ระบบสำเร็จ', '2025-11-21 15:44:20'),
(17, 7, 'register', 'user:8', 'เพิ่มผู้ใช้ satid', '2025-11-21 15:44:49'),
(18, 7, 'change_role', 'user:8', 'Promote เป็น admin', '2025-11-21 15:44:55'),
(19, 7, 'delete_user', 'user:8', 'ลบผู้ใช้', '2025-11-21 16:07:30'),
(20, 7, 'logout', NULL, 'ออกจากระบบ', '2025-11-21 16:30:32');

-- --------------------------------------------------------

--
-- Table structure for table `cron_profiles`
--

CREATE TABLE `cron_profiles` (
  `id` int(11) NOT NULL,
  `label` varchar(100) DEFAULT NULL,
  `cron_expr` varchar(30) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `notify_mode` enum('FAIL_ONLY','ALL') NOT NULL DEFAULT 'ALL'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cron_profiles`
--

INSERT INTO `cron_profiles` (`id`, `label`, `cron_expr`, `description`, `notify_mode`) VALUES
(11, '⚡ ทุก 10 วินาที', '*/10 * * * * *', 'ใช้สำหรับระบบที่ต้องอัปเดตข้อมูลเร็วมาก', 'FAIL_ONLY'),
(12, '🔁 ทุก 1 นาที', '0 * * * * *', 'ดึงข้อมูลแบบเกือบ real-time', 'FAIL_ONLY'),
(13, '🕐 ทุก 5 นาที', '0 */5 * * * *', 'เหมาะกับข้อมูลที่เปลี่ยนแปลงบ่อย', 'FAIL_ONLY'),
(14, '🕒 ทุก 15 นาที', '0 */15 * * * *', 'ใช้สำหรับ lab, queue หรือ notify', 'FAIL_ONLY'),
(15, '🕓 ทุกชั่วโมง', '0 0 * * * *', 'เหมาะสำหรับ summary report', 'ALL'),
(16, '🌙 เที่ยงคืน', '0 0 0 * * *', 'สรุปรายวัน เช่น admit, discharge', 'ALL'),
(17, '📆 ทุกวันจันทร์', '0 0 0 * * 1', 'รันเฉพาะวันจันทร์', 'ALL'),
(18, '🛠 เฉพาะเวลา 06:30', '0 30 6 * * *', 'กรณีต้องดึงเช้าแบบเฉพาะเจาะจง', 'ALL'),
(19, '📅 ทุก 1 เดือน', '0 0 1 1 * *', 'ทำงานทุกวันที่ 1 ของเดือน', 'ALL'),
(20, '🔕 ไม่ตั้งเวลา (manual)', '-', 'ผู้ใช้จะสั่ง post ด้วยตนเองเท่านั้น', 'ALL');

-- --------------------------------------------------------

--
-- Table structure for table `save_query`
--

CREATE TABLE `save_query` (
  `id` int(11) NOT NULL,
  `his_type` varchar(50) DEFAULT NULL,
  `query_name` varchar(100) DEFAULT NULL,
  `query_text` longtext NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `last_post_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `cron_id` int(11) DEFAULT NULL,
  `hos_code` varchar(5) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `save_query`
--

INSERT INTO `save_query` (`id`, `his_type`, `query_name`, `query_text`, `created_at`, `last_post_at`, `created_by`, `cron_id`, `hos_code`) VALUES
(161, 'hosxpv3', 'DailyAccident-Month', 'SELECT \r\n    DATE(arrive_time) AS Accident_date,\r\n    COUNT(*) AS Accident_count\r\nFROM er_nursing_detail\r\nWHERE arrive_time BETWEEN DATE_FORMAT(CURDATE(), \'%Y-%m-01\')\r\n                      AND CURDATE()\r\nGROUP BY DATE(arrive_time)\r\nORDER BY Accident_date;', '2025-11-04 14:20:41', NULL, 7, 13, '11153'),
(162, 'hosxpv3', 'DailyOPD-Month', 'select  DAY(ov.vstdate) as day,\r\n        count(ov.vn) as chn \r\nfrom vn_stat ov, ovst ovst, patient pt \r\nwhere  ov.vn=ovst.vn and pt.hn=ov.hn and ov.vstdate between DATE_FORMAT(CURDATE() ,\'%Y-%m-01\') AND CURDATE()\r\nand ov.age_y>= 0 \r\nand ov.age_y<= 200\r\ngroup by ov.vstdate', '2025-11-04 14:22:46', NULL, 7, 13, '11153'),
(163, 'hosxpv3', 'test', 'select hn\r\nfrom patient\r\nlimit 10', '2025-11-04 14:36:45', NULL, 7, NULL, '11153');

-- --------------------------------------------------------

--
-- Table structure for table `scheduled_query_jobs`
--

CREATE TABLE `scheduled_query_jobs` (
  `id` int(11) NOT NULL,
  `query_name` varchar(100) DEFAULT NULL,
  `hos_code` varchar(20) DEFAULT NULL,
  `cron_time` varchar(20) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `password`, `role`) VALUES
(7, 'Admin', '$2y$10$FFh0qX1VIfdDCj0gjdowGO4t1vp8Lh32n.JVA5B1R08nGc1EPgFc6', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cron_profiles`
--
ALTER TABLE `cron_profiles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `save_query`
--
ALTER TABLE `save_query`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `query_name` (`query_name`);

--
-- Indexes for table `scheduled_query_jobs`
--
ALTER TABLE `scheduled_query_jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `cron_profiles`
--
ALTER TABLE `cron_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `save_query`
--
ALTER TABLE `save_query`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=165;

--
-- AUTO_INCREMENT for table `scheduled_query_jobs`
--
ALTER TABLE `scheduled_query_jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
