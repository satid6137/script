<?php
require 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['error' => 'ไม่ได้รับอนุญาต']);
  exit;
}

$id = $_POST['id'] ?? null;
if (!$id) {
  http_response_code(400);
  echo json_encode(['error' => 'ไม่พบ ID']);
  exit;
}

// ดึงชื่อ query_name
$stmt = $conn->prepare("SELECT query_name FROM save_query WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($queryName);
if (!$stmt->fetch()) {
  echo json_encode(['error' => 'ไม่พบรายการ']);
  exit;
}
$stmt->close();

// 🔁 ส่ง POST ไปหา server
$serverURL = "http://{$ipServer}:3000/delete-query/" . urlencode($queryName);
@file_get_contents($serverURL, false, stream_context_create([
  'http' => ['method' => 'POST']
]));

// 🧹 ลบจาก save_query
$stmt = $conn->prepare("DELETE FROM save_query WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();

echo json_encode(['success' => true]);