<?php
// ต้องมี url เสมอ
if (!isset($_POST['url'])) {
  echo json_encode(['success' => false, 'error' => 'Missing URL']);
  exit;
}

$url = $_POST['url'];
$apiKey = $_POST['apiKey'] ?? null;   // รับจาก index.php
$hisType = $_POST['hisType'] ?? null;   // รับจาก index.php

// เตรียม Header ส่งไป Node.js
$headers = [];

if ($apiKey) {
  $headers[] = "X-API-Key: {$apiKey}";
}

if ($hisType) {
  $headers[] = "X-HIS-Type: {$hisType}";
}

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);

// ถ้ามี header ให้ส่งไปด้วย
if (!empty($headers)) {
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
}

$resp = curl_exec($ch);
$err = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo json_encode([
  'success' => !$err && $code >= 200 && $code < 300,
  'response' => $resp,
  'error' => $err
]);
