/*
 Navicat Premium Dump SQL

 Source Server         : 61.19.35.209-Website
 Source Server Type    : MariaDB
 Source Server Version : 100432 (10.4.32-MariaDB)
 Source Host           : 172.16.0.10:3306
 Source Schema         : query2

 Target Server Type    : MariaDB
 Target Server Version : 100432 (10.4.32-MariaDB)
 File Encoding         : 65001

 Date: 30/06/2026 09:27:02
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for cron_profiles
-- ----------------------------
DROP TABLE IF EXISTS `cron_profiles`;
CREATE TABLE `cron_profiles`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cron_expr` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `notify_mode` enum('FAIL_ONLY','ALL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ALL',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 22 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of cron_profiles
-- ----------------------------
INSERT INTO `cron_profiles` VALUES (11, '⚡ ทุก 5 นาที', '0 */5 * * * *', 'ใช้สำหรับระบบที่ต้องอัปเดตข้อมูลเร็วมาก', 'FAIL_ONLY');
INSERT INTO `cron_profiles` VALUES (12, '🔁 ทุก 10 นาที', '0 */10 * * * *', 'ดึงข้อมูลแบบเกือบ real-time', 'FAIL_ONLY');
INSERT INTO `cron_profiles` VALUES (13, '🕐 ทุก 15 นาที', '0 */15 * * * *', 'เหมาะกับข้อมูลที่เปลี่ยนแปลงบ่อย', 'FAIL_ONLY');
INSERT INTO `cron_profiles` VALUES (14, '🕒 ทุก 30 นาที', '0 */30 * * * *', 'ใช้สำหรับ lab, queue หรือ notify', 'FAIL_ONLY');
INSERT INTO `cron_profiles` VALUES (15, '🕓 ทุกชั่วโมง', '0 0 * * * *', 'เหมาะสำหรับ summary report', 'ALL');
INSERT INTO `cron_profiles` VALUES (16, '🌙 เที่ยงคืน', '0 0 0 * * *', 'สรุปรายวัน เช่น admit, discharge', 'ALL');
INSERT INTO `cron_profiles` VALUES (17, '📆 ทุกวันจันทร์', '0 0 0 * * 1', 'รันเฉพาะวันจันทร์', 'ALL');
INSERT INTO `cron_profiles` VALUES (18, '🛠 เฉพาะเวลา 06:30', '0 30 6 * * *', 'กรณีต้องดึงเช้าแบบเฉพาะเจาะจง', 'ALL');
INSERT INTO `cron_profiles` VALUES (19, '📅 ทุก 1 เดือน', '0 0 1 1 * *', 'ทำงานทุกวันที่ 1 ของเดือน', 'ALL');
INSERT INTO `cron_profiles` VALUES (20, '🔕 ไม่ตั้งเวลา (manual)', '-', 'ผู้ใช้จะสั่ง post ด้วยตนเองเท่านั้น', 'ALL');
INSERT INTO `cron_profiles` VALUES (21, '🕒 ทุก 30 นาที', '0 */30 * * * *', 'ใช้สำหรับ lab, queue', 'FAIL_ONLY');

SET FOREIGN_KEY_CHECKS = 1;
