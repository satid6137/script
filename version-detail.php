<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require __DIR__ . '/config.php';

/* -------------------------
   Helper: load version from URL (version.txt)
-------------------------- */
function loadVersion($url)
{
    $v = @file_get_contents($url);
    return $v ? trim($v) : null;
}

/* -------------------------
   1) โหลดเวอร์ชัน script (ปัจจุบัน)
-------------------------- */
$phpCurrentUrl = "http://" . $_ENV['DB_PHP'] . "/script/version.txt";
$phpCurrent = loadVersion($phpCurrentUrl);

/* -------------------------
   2) โหลดเวอร์ชัน script (ล่าสุด)
-------------------------- */
$phpLastUrl = "https://website.hangchathospital.com/script/version.txt";
$phpLast = loadVersion($phpLastUrl);

/* -------------------------
   3) โหลดเวอร์ชันจาก nodejs-server API (ใช้ curl + header)
-------------------------- */
$nodejs = $_ENV['IP_NODEJS'];
$hosCode = $_ENV['HOS_CODE'];
$apiKey = $_ENV['API_KEY'];

// ✔ URL ต้องเป็นแบบนี้เท่านั้น
$urlAPI = "{$nodejs}/query/version/{$hosCode}";

$ch = curl_init($urlAPI);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// ✔ Header ต้องเป็นตัวเล็กทั้งหมด
// ✔ ต้องมีแค่ตัวเดียว
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "x-api-key: {$apiKey}"
]);

$response = curl_exec($ch);
curl_close($ch);

$apiData = $response ? json_decode($response, true) : null;

if (is_array($apiData) && isset($apiData[0])) {
    $clientVersion = $apiData[0]['client_version'] ?? null;
    $serverVersion = $apiData[0]['server_version'] ?? null;
    $telemedVersion = $apiData[0]['telemed_version'] ?? null;
} else {
    $clientVersion = $serverVersion = $telemedVersion = null;
}

/* -------------------------
   Helper: check version
-------------------------- */
function checkVersion($name, $current, $last)
{
    if ($current === null) {
        return "<span style='color:red;'>$name: โหลดไม่ได้</span>";
    }

    if ($current == $last) {
        return "<span style='color:green;'>$name: $current ✔</span>";
    }

    return "<span style='color:orange;'>$name: $current → อัพเดทเป็น $last</span>";
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Version Status</title>
    <link rel="icon" href="/script/assets/icons/health48.png" type="image/png">
    <link rel="apple-touch-icon" href="/script/assets/icons/health48.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="/script/assets/css/theme.css" rel="stylesheet">

    <style>
        body {
            font-family: 'IBM Plex Sans Thai', sans-serif;
            background: #eef2f7;
            padding: 30px;
        }

        .version-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 18px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        .version-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #333;
        }

        .version-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .version-item:last-child {
            border-bottom: none;
        }

        .version-name {
            font-size: 17px;
            font-weight: 500;
            color: #444;
        }

        .status {
            font-size: 16px;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 6px;
        }

        .ok {
            background: #d4f8d4;
            color: #1a7f1a;
            border: 1px solid #8dd88d;
        }

        .update {
            background: #fff4d4;
            color: #b36b00;
            border: 1px solid #e6c27a;
        }

        .fail {
            background: #ffd4d4;
            color: #b30000;
            border: 1px solid #e67a7a;
        }

        .btn-pink {
            background-color: #ff4fa3;
            color: white;
            font-weight: 600;
            border-radius: 6px;
        }

        .btn-pink:hover {
            background-color: #e63f92;
            color: white;
        }
    </style>
</head>

<body>

    <div class="container" style="max-width: 650px;">

        <div class="version-card">
            <div class="version-title">Version Status</div>

            <div class="mb-3">
                <strong>Script Version ล่าสุด:</strong> <?= $phpLast ?>
            </div>

            <div class="version-item">
                <div class="version-name">PHP Script</div>
                <div
                    class="status <?= ($phpCurrent === null ? 'fail' : ($phpCurrent == $phpLast ? 'ok' : 'update')) ?>">
                    <?= checkVersion("PHP Script", $phpCurrent, $phpLast) ?>
                </div>
            </div>

            <div class="version-item">
                <div class="version-name">Node.js Client</div>
                <div
                    class="status <?= ($clientVersion === null ? 'fail' : ($clientVersion == $phpLast ? 'ok' : 'update')) ?>">
                    <?= checkVersion("Node.js Client", $clientVersion, $phpLast) ?>
                </div>
            </div>

            <div class="version-item">
                <div class="version-name">Node.js Server</div>
                <div
                    class="status <?= ($serverVersion === null ? 'fail' : ($serverVersion == $phpLast ? 'ok' : 'update')) ?>">
                    <?= checkVersion("Node.js Server", $serverVersion, $phpLast) ?>
                </div>
            </div>

            <div class="version-item">
                <div class="version-name">Telemed API Docs</div>
                <div
                    class="status <?= ($telemedVersion === null ? 'fail' : ($telemedVersion == $phpLast ? 'ok' : 'update')) ?>">
                    <?= checkVersion("Telemed API Docs", $telemedVersion, $phpLast) ?>
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-between">
                <a href="index.php" class="btn btn-pink btn-sm">⬅ กลับหน้าแรก</a>
                <a href="version-detail.php" class="btn btn-secondary btn-sm">🔄 ตรวจสอบอีกครั้ง</a>
            </div>
        </div>
    </div>

    <footer class="hos-footer text-center">
        Developed by <strong>นายสาธิต รินคำ</strong> นักวิชาการคอมพิวเตอร์ กลุ่มงานสุขภาพดิจิตอล โรงพยาบาลห้างฉัตร
        · Coder Copilot · เครดิต YuiCity / Vorabodin สสจ.ชม ·
        <?= date('Y') ?>
    </footer>

</body>

</html>