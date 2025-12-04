<?php
// สำหรับ /script
session_set_cookie_params(0, '/script', '', false, true);

session_name("script");

// ✅ ป้องกัน session_start() ซ้ำซ้อน
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$db   = 'xxx';
$user = 'xxx';
$pass = 'xxx';

// ค่าคงที่เพิ่มเติม nodjs-server
$ipServer = 'xxx';
$hosCode  = 'xxxxxx';
$apiKey   = 'qqq@qqq'; // 💡 ใส่ API key ไว้ตรงนี้ได้เลย
$summaryKey = 'aaa@aaa'; // 🔐 key สำหรับ summary เท่านั้น
$nodejs = 'http://eee:3000';
$delete = 'http://eee:3000';

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


?>

