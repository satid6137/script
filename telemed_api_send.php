<?php
require __DIR__ . '/config.php';
require_once 'log_helper.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?timeout=1");
    exit;
}

// ตรวจสอบสิทธิ์ admin
$stmt = $conn->prepare("SELECT role FROM user WHERE id=?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();

if ($role !== 'admin')
    die("เฉพาะ admin เท่านั้นที่เข้าถึงหน้านี้ได้");

// โหลด config จาก DB
$cfg = $conn->query("SELECT * FROM telemed_config LIMIT 1")->fetch_assoc();

// โหลดค่าจาก .env
$hosCode = $cfg['hos_code'];
$nodejs = $cfg['nodejs_url'];
$nodeApiKey = $_ENV['API_KEY'];       // API KEY ของ Node.js
$apiKey = $cfg['api_key'];            // API KEY จังหวัด

// 1) URL Node.js
$queryName = $cfg['his_query_name'];
$urlAPI = "{$nodejs}/query/{$queryName}/{$hosCode}";

// 2) GET + Header ไป Node.js
$ch = curl_init($urlAPI);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-API-Key: {$nodeApiKey}",
    "X-HIS-Type: {$cfg['his_type']}"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$his_json = curl_exec($ch);
$curlErr = curl_error($ch);
curl_close($ch);

// ------------------------------
// ตรวจสอบ HIS response
// ------------------------------
if ($his_json === false || $curlErr) {
    $msg = "Curl error: " . ($curlErr ?: 'unknown');
    log_write("❌ Node.js error: {$msg}");
    die("❌ ดึงข้อมูล HIS ไม่สำเร็จ: {$msg}");
}

$his = json_decode($his_json, true);

if (!$his || !is_array($his) || count($his) === 0) {
    log_write("❌ HIS ไม่มีข้อมูลจาก {$queryName}");
    die("❌ ไม่มีข้อมูล HIS จาก {$queryName}");
}

// ------------------------------
// ส่งรายวัน
// ------------------------------

$results = [];

foreach ($his as $row) {

    // payload รายวัน
    $payload = [
        'hcode' => $hosCode,
        'report_date' => $row['visit_date'],
        'telemed_total' => intval($row['telemed'] ?? 0),
        'telemed_b2b_count' => intval($row['b2b'] ?? 0),
        'telemed_b2c_count' => intval($row['b2c'] ?? 0),
        'health_rider_total' => intval($row['healthrider'] ?? 0),
        'details' => [
            ['opd_id' => '00', 'walkin_count' => intval($row['รพ.สต.'] ?? 0)],
            ['opd_id' => '01', 'walkin_count' => intval($row['อายุรกรรม'] ?? 0)],
            ['opd_id' => '02', 'walkin_count' => intval($row['ศัลยกรรม'] ?? 0)],
            ['opd_id' => '03', 'walkin_count' => intval($row['สูติกรรม'] ?? 0)],
            ['opd_id' => '04', 'walkin_count' => intval($row['นรีเวชกรรม'] ?? 0)],
            ['opd_id' => '05', 'walkin_count' => intval($row['กุมารเวชกรรม'] ?? 0)],
            ['opd_id' => '06', 'walkin_count' => intval($row['โสตศอนาสิก'] ?? 0)],
            ['opd_id' => '07', 'walkin_count' => intval($row['จักษุวิทยา'] ?? 0)],
            ['opd_id' => '08', 'walkin_count' => intval($row['ศัลยกรรมออร์โธปิดิกส์'] ?? 0)],
            ['opd_id' => '09', 'walkin_count' => intval($row['จิตเวช'] ?? 0)],
            ['opd_id' => '10', 'walkin_count' => intval($row['รังสีวิทยา'] ?? 0)],
            ['opd_id' => '11', 'walkin_count' => intval($row['ทันตกรรม'] ?? 0)],
            ['opd_id' => '12', 'walkin_count' => intval($row['เวชศาสตร์ฉุกเฉินและนิติเวช'] ?? 0)],
            ['opd_id' => '13', 'walkin_count' => intval($row['เวชกรรมฟื้นฟู'] ?? 0)],
            ['opd_id' => '14', 'walkin_count' => intval($row['แพทย์แผนไทย'] ?? 0)],
            ['opd_id' => '15', 'walkin_count' => intval($row['PCU'] ?? 0)],
            ['opd_id' => '16', 'walkin_count' => intval($row['เวชกรรมปฎิบัติทั่วไป'] ?? 0)],
            ['opd_id' => '17', 'walkin_count' => intval($row['เวชศาสสตร์ครอบครัวและชุมชน'] ?? 0)],
            ['opd_id' => '18', 'walkin_count' => intval($row['อาชีวคลินิก'] ?? 0)],
            ['opd_id' => '19', 'walkin_count' => intval($row['วิสัญญีวิทยา'] ?? 0)],
            ['opd_id' => '20', 'walkin_count' => intval($row['ศัลยกรรมประสาท'] ?? 0)],
            ['opd_id' => '21', 'walkin_count' => intval($row['อาชีวเวชรกรรม'] ?? 0)],
            ['opd_id' => '22', 'walkin_count' => intval($row['เวชกรรมสังคม'] ?? 0)],
            ['opd_id' => '23', 'walkin_count' => intval($row['พยาธิวิทยากายวิภาค'] ?? 0)],
            ['opd_id' => '24', 'walkin_count' => intval($row['พยาธิวิทยาคลินิค'] ?? 0)],
            ['opd_id' => '25', 'walkin_count' => intval($row['แพทย์ทางเลือก'] ?? 0)],
            ['opd_id' => '26', 'walkin_count' => intval($row['ตจวิทยาคลินิก'] ?? 0)],
            ['opd_id' => '88', 'walkin_count' => intval($row['แพทย์แผนจีน'] ?? 0)],
            ['opd_id' => '99', 'walkin_count' => intval($row['อื่นๆ'] ?? 0)]
        ]
    ];

    // ส่งไปจังหวัด
    $ch = curl_init("https://telemed.rh1.go.th/api/v1/save_report_full.php");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "X-API-KEY: {$apiKey}"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $curlErr = curl_error($ch);
    curl_close($ch);

    // ------------------------------
    // ตรวจสอบ response จังหวัด
    // ------------------------------
    if ($response === false || $curlErr) {
        $status = 'fail';
        $msg = "Curl error: " . ($curlErr ?: 'unknown');
    } else {
        $res = json_decode($response, true);

        if (!$res || !isset($res['status'])) {
            $status = 'fail';
            $msg = $response ?: 'ไม่มีการตอบกลับจากจังหวัด';
        } else {
            $status = ($res['status'] === 'success') ? 'success' : 'fail';
            $msg = $res['message'] ?? '';
        }
    }

    // บันทึก log เสมอ
    $stmt = $conn->prepare("
        INSERT INTO telemed_log (log_date, report_date, status, message, hos_code)
        VALUES (NOW(), ?, ?, ?, ?)
    ");
    $stmt->bind_param('ssss', $row['visit_date'], $status, $msg, $hosCode);
    $stmt->execute();
    $stmt->close();

    // เก็บผลลัพธ์
    $results[] = [
        'date' => $row['visit_date'],
        'status' => $status,
        'message' => $msg
    ];
}

// ------------------------------
// UI เดิม
// ------------------------------
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Telemed Send | <?= htmlspecialchars($hospital) ?></title>
    <link rel="icon" href="/script/assets/icons/health48.png" type="image/png">
    <link rel="apple-touch-icon" href="/script/assets/icons/health48.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <div class="container mt-5" style="max-width: 600px;">
        <div class="card shadow-lg border-0">
            <div class="card-header bg-primary text-white text-center">
                <h4 class="mb-0">สรุปผลการส่ง Telemed API</h4>
            </div>

            <div class="card-body text-center">
                <h1 class="display-5 fw-bold text-success"><?= count($results) ?> วัน</h1>
                <p class="text-muted">ส่งข้อมูลไปจังหวัดเรียบร้อยแล้ว</p>

                <hr>

                <a href="telemed_api_docs.php" class="btn btn-primary btn-lg mt-3">
                    ⬅️ กลับไปหน้า Docs
                </a>
            </div>
        </div>
    </div>
</body>

</html>