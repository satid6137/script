<?php
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

// เริ่มจับเวลา
$startTime = microtime(true);

// ตั้งค่า timeout สำหรับ MySQL
mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 5);

// ===============================
// MODE 1: ดึง Notify Settings
// ===============================
if (isset($_GET['notify']) && $_GET['notify'] == '1') {

    $queryName = $_GET['queryName'] ?? '';
    $hosCode = $_GET['hosCode'] ?? '';

    if (!$queryName || !$hosCode) {
        http_response_code(400);
        echo json_encode(['error' => 'missing params']);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT notify_type, line_token, moph_client_key, moph_secret_key, cron_id, description
        FROM notify_settings
        WHERE query_name = ? AND hos_code = ?
        LIMIT 1
    ");
    $stmt->bind_param('ss', $queryName, $hosCode);
    $stmt->execute();
    $result = $stmt->get_result();
    $notify = $result->fetch_assoc();

    echo json_encode([
        'notify' => $notify ? [
            'notify_type' => $notify['notify_type'],
            'line_token' => $notify['line_token'],
            'moph_client_key' => $notify['moph_client_key'],
            'moph_secret_key' => $notify['moph_secret_key'],
            'cron_id' => $notify['cron_id'],
            'description' => $notify['description']   // ⭐ เพิ่มตรงนี้
        ] : ['notify_type' => 'none']
    ]);

    exit;
}

// ===============================
// MODE 2: ดึง SQL Template (โหมดเดิม)
// ===============================
$hisType = $_GET['hisType'] ?? '';
$queryName = $_GET['queryName'] ?? '';

if (!$hisType || !$queryName) {
    http_response_code(400);
    echo json_encode(['error' => 'missing params']);
    exit;
}

$stmt = $conn->prepare("
    SELECT query_text 
    FROM save_query 
    WHERE his_type=? AND query_name=? 
    LIMIT 1
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'prepare failed']);
    exit;
}

$stmt->bind_param('ss', $hisType, $queryName);
$stmt->execute();
$stmt->bind_result($queryText);

if ($stmt->fetch()) {
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    echo json_encode([
        'sql' => $queryText,
        'duration_ms' => $duration
    ]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'not found']);
}

$stmt->close();
$conn->close();
