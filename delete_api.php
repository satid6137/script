<?php
require 'config.php';
header('Content-Type: application/json; charset=utf-8');

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'ไม่ได้รับอนุญาต']);
    exit;
}

$id = $_POST['id'] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ไม่พบ ID']);
    exit;
}

// ดึง query_name
$stmt = $conn->prepare("SELECT query_name FROM save_query WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($queryName);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'ไม่พบรายการ']);
    exit;
}
$stmt->close();

// ยิงไป Node.js
$serverURL = "{$delete}/delete-query/" . urlencode($queryName);
$ch = curl_init($serverURL);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($response === false || $httpCode >= 400) {
    echo json_encode([
        'success' => false,
        'error' => "Node.js server error: $curlErr (HTTP $httpCode)",
    ]);
    exit;
}

// ลบจาก save_query
$stmt = $conn->prepare("DELETE FROM save_query WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();

// ✅ แปลง response จาก Node.js เป็น JSON ถ้าได้
$nodeData = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $nodeData = ['message' => trim($response)];
}

// ✅ ส่ง JSON กลับ browser
echo json_encode([
    'success' => true,
    'node_response' => $nodeData
]);

