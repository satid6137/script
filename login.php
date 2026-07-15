<?php
require __DIR__ . '/config.php';

if (isset($_GET['timeout'])) {
  echo "<script>alert('เซสชันหมดอายุ กรุณาเข้าสู่ระบบอีกครั้ง');</script>";
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $user = trim($_POST['username']);
  $pass = trim($_POST['password']);

  // ดึงข้อมูล user + 2FA
  $stmt = $conn->prepare("SELECT id, password, twofa_enabled FROM user WHERE username=? AND active=1");
  $stmt->bind_param('s', $user);
  $stmt->execute();
  $stmt->bind_result($uid, $hashed, $twofa);

  if ($stmt->fetch()) {
    $stmt->close();

    if (password_verify($pass, $hashed)) {

      // ⭐ ถ้าเปิด 2FA → ไปหน้า verify_2fa.php
      if ($twofa == 1) {
        $_SESSION['pending_user_id'] = $uid;
        header("Location: verify_2fa.php");
        exit;
      }

      // ⭐ ถ้าไม่เปิด 2FA → login ปกติ
      $_SESSION['user_id'] = $uid;
      $_SESSION['login_time'] = date('Y-m-d H:i:s');
      header("Location: index.php");
      exit;
    }
  }

  $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
}
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>เข้าสู่ระบบ | ระบบจัดการ Query API</title>
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
        <img src="/script/assets/icons/health48.png" alt="โลโก้โรงพยาบาลห้างฉัตร" width="56" height="56" class="mb-2">
        <div class="hos-brand justify-content-center">
          <?= $hospital ?>
        </div>
        <div class="hos-page-subtitle">ระบบจัดการ Query API</div>
      </div>

      <div class="hos-auth-card">
        <h1 class="hos-page-title text-center mb-4">เข้าสู่ระบบ</h1>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger py-2" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
          <div class="mb-3">
            <label class="form-label">ชื่อผู้ใช้</label>
            <input class="form-control" name="username" autocomplete="username" required autofocus>
          </div>
          <div class="mb-4">
            <label class="form-label">รหัสผ่าน</label>
            <input type="password" class="form-control" name="password" autocomplete="current-password" required>
          </div>
          <button class="btn btn-primary w-100 mb-3" type="submit">เข้าสู่ระบบ</button>
          <a href="index.php" class="btn btn-outline-secondary w-100 mt-2">⬅️ หน้าหลัก</a>
        </form>
      </div>

      <p class="text-center hos-page-subtitle mt-3 mb-0">
        <a href="privacy-policy.php">นโยบายความเป็นส่วนตัว</a>
      </p>
    </div>
  </div>
</body>

</html>