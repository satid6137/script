<?php
require __DIR__ . '/config.php';
require_once 'lib/GoogleAuthenticator.php';

if (!isset($_SESSION['pending_user_id']))
    die("ผิดพลาด");

$uid = $_SESSION['pending_user_id'];

$stmt = $conn->prepare("SELECT twofa_secret FROM user WHERE id=?");
$stmt->bind_param('i', $uid);
$stmt->execute();
$stmt->bind_result($secret);
$stmt->fetch();
$stmt->close();

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp']);
    $ga = new PHPGangsta_GoogleAuthenticator();

    if ($ga->verifyCode($secret, $otp, 2)) {
        $_SESSION['user_id'] = $uid;
        unset($_SESSION['pending_user_id']);
        header("Location: index.php");
        exit;
    } else {
        $error = "OTP ไม่ถูกต้อง";
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ยืนยัน 2FA | ระบบจัดการ Query API</title>
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
        <div class="w-100" style="max-width: 420px;">

            <div class="text-center mb-4">
                <img src="/script/assets/icons/health48.png" alt="โลโก้โรงพยาบาลห้างฉัตร" width="56" height="56"
                    class="mb-2">
                <div class="hos-brand justify-content-center"><?= $hospital ?></div>
                <div class="hos-page-subtitle">ระบบจัดการ Query API</div>
            </div>

            <div class="hos-auth-card">
                <h1 class="hos-page-title text-center mb-2">ยืนยันตัวตนสองขั้นตอน</h1>
                <p class="hos-page-subtitle text-center mb-4">กรอกรหัส 6 หลักจากแอปยืนยันตัวตนของคุณ</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2">
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label">รหัส 6 หลัก</label>
                        <input class="form-control" name="otp" inputmode="numeric" autocomplete="one-time-code" required
                            autofocus>
                    </div>
                    <button class="btn btn-primary w-100">ยืนยัน</button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>