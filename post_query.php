<?php
// ต้องมี url เสมอ
if (!isset($_POST['url'])) {
  echo json_encode(['success' => false, 'error' => 'Missing URL']);
  exit;
}

$url = $_POST['url'];
$apiKey = $_POST['apiKey'] ?? null;
$hisType = $_POST['hisType'] ?? null;
$queryName = $_POST['queryName'] ?? null;
$hosCode = $_POST['hosCode'] ?? null;

// เตรียม Header ส่งไป Node.js
$headers = [];

if ($apiKey) {
  $headers[] = "X-API-Key: {$apiKey}";
}

if ($hisType) {
  $headers[] = "X-HIS-Type: {$hisType}";
}

if ($queryName) {
  $headers[] = "X-Query-Name: {$queryName}";
}

if ($hosCode) {
  $headers[] = "X-Hos-Code: {$hosCode}";
}

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);

// ส่ง header ไป NodeJS
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
