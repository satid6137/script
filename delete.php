<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) die("ไม่ได้รับอนุญาตให้ลบ");

$id = $_GET['id'] ?? null;
if (!$id) die("ไม่พบ ID");

// 📦 ดึงชื่อ query_name ก่อนลบทิ้ง
$stmt = $conn->prepare("SELECT query_name FROM save_query WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($queryName);
if (!$stmt->fetch()) die("ไม่พบรายการนี้");
$stmt->close();

// 🧹 ลบตารางที่ชื่อเดียวกับ query_name (ถ้ามี)
// ลบตาราง
$cleanTable = preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', trim($queryName))); // ป้องกันชื่อผิดปกติ

// ✅ ลอง echo ดูชื่อจริงก่อน
echo "<pre>";
var_dump($queryName);
var_dump($cleanTable);
echo "</pre>";
exit;


// ❌ จะยังไม่ลบตาราง เพราะเราออกด้วย exit เพื่อดูค่าเฉย ๆ
if ($cleanTable) {
  $conn->query("DROP TABLE IF EXISTS `$cleanTable`");
}

// 🗑️ ลบ row จาก save_query
$stmt = $conn->prepare("DELETE FROM save_query WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();

header("Location: index.php");
exit;