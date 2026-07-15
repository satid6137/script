<?php
require __DIR__ . '/config.php';
require_once 'lib/GoogleAuthenticator.php';

$uid = intval($_POST['id']);
$otp = trim($_POST['otp']);

$stmt = $conn->prepare("SELECT twofa_secret FROM user WHERE id=?");
$stmt->bind_param('i', $uid);
$stmt->execute();
$stmt->bind_result($secret);
$stmt->fetch();
$stmt->close();

$ga = new PHPGangsta_GoogleAuthenticator();
$check = $ga->verifyCode($secret, $otp, 2);

if ($check) {
    $conn->query("UPDATE user SET twofa_enabled=1 WHERE id=$uid");
    header("Location: admin.php");
    exit;
} else {
    ?>
    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>ยืนยัน 2FA ไม่สำเร็จ | <?= $hospital ?></title>
        <link rel="icon" href="/script/assets/icons/health48.png" type="image/png">
        <link rel="apple-touch-icon" href="/script/assets/icons/health48.png">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap"
            rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="/script/assets/css/theme.css" rel="stylesheet">
    </head>

    <body>
        <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh;">
            <div class="w-100 text-center" style="max-width: 420px;">
                <img src="/script/assets/icons/health48.png" alt="โลโก้โรงพยาบาลห้างฉัตร" width="56" height="56"
                    class="mb-2">
                <div class="hos-brand justify-content-center">
                    <?= $hospital ?>
                </div>

                <div class="hos-auth-card mt-3">
                    <h1 class="hos-page-title text-center mb-3">ยืนยัน 2FA ไม่สำเร็จ</h1>
                    <div class="alert alert-danger" role="alert">OTP ไม่ถูกต้อง</div>
                    <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm">กลับ</a>
                </div>
            </div>
        </div>
    </body>

    </html>
    <?php
    exit;
}
