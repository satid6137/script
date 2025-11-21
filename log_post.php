<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require 'config.php';
require_once 'log_helper.php';
#session_start();

// ✅ รับข้อมูล JSON จาก client
$data = json_decode(file_get_contents("php://input"), true);
$queryName = $data['query_name'] ?? null;
$url       = $data['url'] ?? '';
$userId    = $_SESSION['user_id'] ?? null;

if ($queryName) {

  // 📝 บันทึก log (ถ้ามีผู้ใช้งาน)
  if ($userId) {
    $detail = "POST ไปยัง client URL: $url";
    logAction($conn, $userId, 'post_query', "query:$queryName", $detail);
  }

  // 📆 อัปเดตวัน post ล่าสุด
  $stmt = $conn->prepare("UPDATE save_query SET last_post_at = NOW() WHERE query_name = ? LIMIT 1");
  $stmt->bind_param("s", $queryName);
  $stmt->execute();

  // ✅ ตอบกลับ OK
  http_response_code(200);
  echo "ok";

} else {
  http_response_code(400);
  echo "missing query_name";
}