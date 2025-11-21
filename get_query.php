<?php
require 'config.php'; // ใช้การเชื่อมต่อ DB เดิม

header('Content-Type: application/json; charset=utf-8');

$hisType   = $_GET['hisType'] ?? '';
$queryName = $_GET['queryName'] ?? '';

if (!$hisType || !$queryName) {
    echo json_encode(['error' => 'missing params']);
    exit;
}

// ดึง SQL จากตาราง save_query
$stmt = $conn->prepare("SELECT query_text FROM save_query WHERE his_type=? AND query_name=? LIMIT 1");
$stmt->bind_param('ss', $hisType, $queryName);
$stmt->execute();
$stmt->bind_result($queryText);

if ($stmt->fetch()) {
    echo json_encode(['sql' => $queryText]);
} else {
    echo json_encode(['error' => 'not found']);
}

$stmt->close();
$conn->close();