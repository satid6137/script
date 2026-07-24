<?php
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
if (ob_get_length())
    ob_clean();

http_response_code(200);

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

// 📦 ดึง query_name + hos_code
$stmt = $conn->prepare("SELECT query_name, hos_code FROM save_query WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($queryName, $hosCode);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'ไม่พบรายการ']);
    exit;
}
$stmt->close();

// ❗ ใช้ชื่อจริง ไม่ sanitize
$cleanQuery = $queryName;

// 🔥 ยิงไป Node.js เพื่อลบตาราง
$nodeUrl = rtrim($ipServer, '/') . "/delete-query/" . urlencode($cleanQuery);

$ch = curl_init($nodeUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$response = curl_exec($ch);

$response = mb_convert_encoding($response, 'UTF-8', 'UTF-8');
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

// ❌ Node.js error
if ($response === false || $httpCode >= 400) {
    echo json_encode([
        'success' => false,
        'error' => "Node.js server error: $curlErr (HTTP $httpCode)",
        'node_response' => $response
    ]);
    exit;
}

// แปลง JSON จาก Node.js
$nodeData = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $nodeData = ['message' => trim($response)];
}

// 🧹 ลบ notify_settings
$stmt = $conn->prepare("DELETE FROM notify_settings WHERE query_name = ? AND hos_code = ?");
$stmt->bind_param('ss', $queryName, $hosCode);
$stmt->execute();
$stmt->close();

// 🗑️ ลบจาก save_query
$stmt = $conn->prepare("DELETE FROM save_query WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->close();

// ส่งผลลัพธ์กลับ browser
echo json_encode([
    'success' => true,
    'message' => $nodeData['message'] ?? 'ลบตารางสำเร็จ'
], JSON_UNESCAPED_UNICODE);
exit;
