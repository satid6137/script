<?php
require __DIR__ . '/config.php';
require_once 'log_helper.php';
require_once 'lib/GoogleAuthenticator.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?timeout=1");
    exit;
}

// ตรวจสอบ admin
$stmt = $conn->prepare("SELECT role FROM user WHERE id=?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();
if ($role !== 'admin')
    die("เฉพาะ admin เท่านั้น");

// รับ user id
$uid = intval($_GET['id']);

// ดึงข้อมูล user
$stmt = $conn->prepare("SELECT username, twofa_secret, twofa_enabled FROM user WHERE id=?");
$stmt->bind_param('i', $uid);
$stmt->execute();
$stmt->bind_result($username, $secret, $enabled);
$stmt->fetch();
$stmt->close();

$ga = new PHPGangsta_GoogleAuthenticator();

// ถ้ายังไม่มี secret → สร้างใหม่
if (!$secret) {
    $secret = $ga->createSecret();
    $stmt = $conn->prepare("UPDATE user SET twofa_secret=? WHERE id=?");
    $stmt->bind_param('si', $secret, $uid);
    $stmt->execute();
}

$qr = $ga->getQRCodeGoogleUrl("HisToApiSystem:$username", $secret);
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ตั้งค่า 2FA | <?= $hospital ?></title>
    <link rel="icon" href="/script/assets/icons/health48.png" type="image/png">
    <link rel="apple-touch-icon" href="/script/assets/icons/health48.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/script/assets/css/theme.css" rel="stylesheet">
</head>

<body>
    <header class="hos-topbar">
        <div class="container">
            <a href="index.php" class="hos-brand">
                <img src="/script/assets/icons/health48.png" alt="โลโก้โรงพยาบาลห้างฉัตร">
                <span>
                    <?= $hospital ?><small>ระบบจัดการ Query API</small>
                </span>
            </a>
        </div>
    </header>

    <div class="container" style="max-width:520px;">
        <a href="admin.php" class="btn btn-outline-secondary btn-sm mb-3">⬅ กลับไปหน้า Admin</a>

        <div class="hos-page-header">
            <h1 class="hos-page-title">ตั้งค่า 2FA สำหรับ <?= htmlspecialchars($username) ?></h1>
            <p class="hos-page-subtitle mb-0">สแกน QR Code ด้วย Google Authenticator หรือ Microsoft Authenticator</p>
        </div>

        <div class="hos-card text-center">
            <img src="<?= $qr ?>" class="img-fluid mb-3">

            <form method="POST" action="twofa_verify.php" class="text-start">
                <input type="hidden" name="id" value="<?= $uid ?>">
                <div class="mb-3">
                    <label class="form-label">กรอกรหัส 6 หลักจากแอป</label>
                    <input class="form-control" name="otp" required>
                </div>
                <button class="btn btn-primary w-100">ยืนยันและเปิดใช้งาน 2FA</button>
            </form>
        </div>
    </div>
</body>

</html>