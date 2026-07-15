<?php
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

$queryName = $_POST['queryName'] ?? '';
$hosCode = $_POST['hosCode'] ?? '';

if (!$queryName || !$hosCode) {
    echo json_encode(['success' => false, 'error' => 'ข้อมูลไม่ครบ']);
    exit;
}

// ดึง notify_settings
$stmt = $conn->prepare("
    SELECT 
        notify_type,
        line_token,
        moph_client_key,
        moph_secret_key,
        description
    FROM notify_settings 
    WHERE query_name = ? AND hos_code = ?
");
$stmt->bind_param('ss', $queryName, $hosCode);
$stmt->execute();
$notify = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$notify) {
    echo json_encode(['success' => false, 'error' => 'ยังไม่ได้กำหนดการแจ้งเตือน']);
    exit;
}

// ส่งไป NodeJS
$nodeUrl = rtrim($ipServer, '/') . "/send-notify-now";

$payload = [
    'queryName' => $queryName,
    'hosCode' => $hosCode,
    'notify' => [
        'notify_type' => $notify['notify_type'],
        'line_token' => $notify['line_token'],
        'moph_client_key' => $notify['moph_client_key'],
        'moph_secret_key' => $notify['moph_secret_key'],
        'description' => $notify['description']
    ]
];

$ch = curl_init($nodeUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);

echo json_encode(['success' => true, 'node_response' => $response]);
