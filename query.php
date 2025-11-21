<?php
require 'config.php';

$hisType = urldecode($_GET['hisType'] ?? '');
$queryName = $_GET['queryName'] ?? '';

if (!$hisType || !$queryName) {
  http_response_code(400);
  echo "Missing parameters.";
  exit;
}

$stmt = $conn->prepare("SELECT query_text FROM save_query WHERE his_type=? AND query_name=?");
$stmt->bind_param('ss', $hisType, $queryName);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
  header('Content-Type: text/plain; charset=utf-8');
  echo "-- HIS: $hisType\n-- Query: $queryName\n\n" . $row['query_text'];
} else {
  http_response_code(404);
  echo "Query not found.";
}