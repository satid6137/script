<?php
if (!isset($_POST['url'])) {
  echo json_encode(['success' => false, 'error' => 'Missing URL']);
  exit;
}
$url = $_POST['url'];
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
$resp = curl_exec($ch);
$err = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo json_encode([
  'success' => !$err && $code >= 200 && $code < 300,
  'response' => $resp,
  'error' => $err
]);