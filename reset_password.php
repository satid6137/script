<?php
require __DIR__ . '/config.php';
require_once 'log_helper.php';
session_start();

if (!isset($_SESSION['user_id']))
  die("กรุณาเข้าสู่ระบบ");

// ตรวจสอบว่าเป็น admin
$stmt = $conn->prepare("SELECT role FROM user WHERE id=?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();

if ($role !== 'admin')
  die("เฉพาะ admin เท่านั้นที่เข้าถึงหน้านี้ได้");

// รับ user id ที่จะเปลี่ยนรหัส
$targetId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($targetId <= 0)
  die("ไม่พบผู้ใช้ที่ต้องการเปลี่ยนรหัส");

// ดึงชื่อผู้ใช้มาแสดง
$stmt = $conn->prepare("SELECT username FROM user WHERE id=?");
$stmt->bind_param('i', $targetId);
$stmt->execute();
$stmt->bind_result($targetUsername);
if (!$stmt->fetch()) {
  die("ไม่พบผู้ใช้ในระบบ");
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $newPassword = trim($_POST['new_password']);
  $confirm = trim($_POST['confirm_password']);

  if (!$newPassword || !$confirm) {
    $error = "กรุณากรอกรหัสผ่านให้ครบ";
  } elseif ($newPassword !== $confirm) {
    $error = "รหัสผ่านไม่ตรงกัน";
  } elseif (!preg_match('/^[a-zA-Z0-9@#$%^&*()]{9,}$/', $newPassword)) {
    $error = "รหัสผ่านต้องมีอย่างน้อย 9 ตัวอักษร และใช้ a-z, A-Z, 0-9 หรือ @#$%^&*() ได้";
  } else {
    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE user SET password=? WHERE id=?");
    $stmt->bind_param("si", $hashed, $targetId);
    if ($stmt->execute()) {
      logAction($conn, $_SESSION['user_id'], 'reset_password', "user:$targetId", "เปลี่ยนรหัสผ่านให้ $targetUsername");
      echo "<script>alert('เปลี่ยนรหัสผ่านสำเร็จ!'); window.location='admin.php';</script>";
      exit;
    } else {
      $error = "ไม่สามารถเปลี่ยนรหัสผ่านได้";
    }
  }
}
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>เปลี่ยนรหัสผ่านผู้ใช้ | <?= $hospital ?></title>
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
      <h1 class="hos-page-title">เปลี่ยนรหัสผ่านผู้ใช้</h1>
      <p class="hos-page-subtitle mb-0">สำหรับ <strong><?= htmlspecialchars($targetUsername) ?></strong></p>
    </div>

    <div class="hos-card">
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="mb-3">
          <label class="form-label">รหัสผ่านใหม่</label>
          <input type="password" name="new_password" id="new_password" class="form-control" required
            pattern="[a-zA-Z0-9@#$%^&*()]{9,}">
        </div>
        <div class="mb-3">
          <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
          <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
        </div>
        <div class="form-check mb-3">
          <input type="checkbox" class="form-check-input" id="togglePassword">
          <label class="form-check-label" id="toggleLabel" for="togglePassword">แสดงรหัสผ่าน</label>
        </div>
        <button type="submit" class="btn btn-primary">บันทึก</button>
        <a href="admin.php" class="btn btn-outline-secondary">ย้อนกลับ</a>
      </form>
    </div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const toggle = document.getElementById("togglePassword");
      const label = document.getElementById("toggleLabel");

      toggle.addEventListener("change", function () {
        const pw1 = document.getElementById("new_password");
        const pw2 = document.getElementById("confirm_password");
        const show = toggle.checked;

        pw1.type = show ? "text" : "password";
        pw2.type = show ? "text" : "password";
        label.textContent = show ? "ซ่อนรหัสผ่าน" : "แสดงรหัสผ่าน";
      });
    });
  </script>
</body>

</html>