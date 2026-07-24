<?php
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'ไม่ได้รับอนุญาต']);
    exit;
}

$queryName = $_POST['query_name'] ?? null;
if (!$queryName) {
    echo json_encode(['success' => false, 'error' => 'ไม่พบ query_name']);
    exit;
}

$nodeUrl = rtrim($ipServer, '/') . "/delete-query/" . urlencode($queryName);

$ch = curl_init($nodeUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

if ($response === false || $httpCode >= 400) {
    echo json_encode([
        'success' => false,
        'error' => "Node.js error: $curlErr (HTTP $httpCode)"
    ]);
    exit;
}

echo json_encode(['success' => true, 'node_response' => json_decode($response, true)]);
