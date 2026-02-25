<?php
// สำหรับ /script
session_set_cookie_params(0, '/script', '', false, true);

session_name("script");

// ✅ ป้องกัน session_start() ซ้ำซ้อน
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
//script
$host = 'xxx'; //ตัวอย่าง 127.0.0.1
$db   = 'xxx';
$user = 'xxx';
$pass = 'xxx';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ⭐ ต้องอยู่ตรงนี้ และต้องมีครั้งเดียว
$conn->set_charset("utf8mb4");

// nodjs-server
$ipServer = 'xxx';
$hosCode  = 'xxxxxx';
$apiKey   = 'qqq@qqq'; // 💡 ใส่ API key ไว้ตรงนี้ได้เลย
$summaryKey = 'aaa@aaa'; // 🔐 key สำหรับ summary เท่านั้น
$nodejs = 'http://eee:3000'; //IP Public
$delete = 'http://eee:3000'; //ip nodejs-server

?>

