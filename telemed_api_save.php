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

// เตรียมค่าที่ต้อง bind
$sendCronId = !empty($_POST['send_cron_id']) ? intval($_POST['send_cron_id']) : null;
$notifyCronId = !empty($_POST['notify_cron_id']) ? intval($_POST['notify_cron_id']) : null;

$stmt = $conn->prepare("
UPDATE telemed_config SET
    report_date=?,
    his_start_date=?,
    his_type=?,
    his_end_date=?,
    his_query_name=?,
    api_key=?,
    hos_code=?,
    nodejs_url=?,
    client_id=?,
    client_secret=?,
    send_cron_id=?
WHERE id=1
");

$stmt->bind_param(
    'ssssssssssi',
    $_POST['report_date'],
    $_POST['his_start_date'],
    $_POST['his_type'],
    $_POST['his_end_date'],
    $_POST['his_query_name'],
    $_POST['api_key'],
    $_POST['hos_code'],
    $_POST['nodejs_url'],
    $_POST['client_id'],
    $_POST['client_secret'],
    $sendCronId
);

$stmt->execute();
$stmt->close();

// ⭐ reload-cron ⭐
$reloadUrl = rtrim($_ENV['IP_TELEMED'], '/') . "/reload-cron";

$rc = curl_init($reloadUrl);
curl_setopt($rc, CURLOPT_POST, true);
curl_setopt($rc, CURLOPT_POSTFIELDS, json_encode(["reload" => true]));
curl_setopt($rc, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($rc, CURLOPT_RETURNTRANSFER, true);
curl_exec($rc);
curl_close($rc);

header("Location: telemed_api_docs.php?saved=1");
exit;
?>