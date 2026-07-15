<?php
require __DIR__ . '/config.php';
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php?timeout=1");
  exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $old = $_POST['old_password'];
  $new = $_POST['new_password'];
  $confirm = $_POST['confirm_password'];

  // ตรวจสอบว่ากรอกครบ
  if ($old && $new && $confirm) {
    if (strlen($new) < 9 || !preg_match('/^[a-zA-Z0-9@#$%^&*()]{9,}$/', $new)) {
      $error = "รหัสผ่านใหม่ต้องมากกว่า 8 ตัว และสามารถใช้ a-z, A-Z, 0-9, @#$%^&*() ได้";

    } elseif ($new !== $confirm) {
      $error = "รหัสผ่านใหม่กับการยืนยันไม่ตรงกัน";
    } else {
      // ดึงรหัสผ่านเดิมจากฐานข้อมูล
      $stmt = $conn->prepare("SELECT password FROM user WHERE id=?");
      $stmt->bind_param('i', $user_id);
      $stmt->execute();
      $stmt->bind_result($hashed);
      if ($stmt->fetch() && password_verify($old, $hashed)) {
        $stmt->close();
        $new_hashed = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE user SET password=? WHERE id=?");
        $stmt->bind_param('si', $new_hashed, $user_id);
        $stmt->execute();
        echo "<script>alert('เปลี่ยนรหัสผ่านสำเร็จ'); window.location='index.php';</script>";
        exit;
      } else {
        $error = "รหัสผ่านเดิมไม่ถูกต้อง";
      }
    }
  } else {
    $error = "กรุณากรอกข้อมูลให้ครบทุกช่อง";
  }
}
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>เปลี่ยนรหัสผ่าน | <?= $hospital ?></title>
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

  <div class="container" style="max-width:520px">
    <div class="hos-page-header">
      <h1 class="hos-page-title">เปลี่ยนรหัสผ่าน</h1>
      <p class="hos-page-subtitle mb-0">ตั้งรหัสผ่านใหม่สำหรับบัญชีของคุณ</p>
    </div>

    <div class="hos-card">
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="mb-3">
          <label class="form-label">รหัสผ่านเดิม</label>
          <input type="password" class="form-control" name="old_password" required>
        </div>
        <div class="mb-3">
          <label class="form-label">รหัสผ่านใหม่ (อย่างน้อย 9 ตัว, a-z, A-Z, 0-9)</label>
          <input type="password" class="form-control" name="new_password" required>
        </div>
        <div class="mb-3">
          <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
          <input type="password" class="form-control" name="confirm_password" required>
        </div>
        <button type="submit" class="btn btn-primary">บันทึกรหัสผ่านใหม่</button>
        <a href="index.php" class="btn btn-outline-secondary">กลับ</a>
      </form>
    </div>
  </div>
</body>

</html>