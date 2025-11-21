<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) die("กรุณาเข้าสู่ระบบ");

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $old = $_POST['old_password'];
  $new = $_POST['new_password'];
  $confirm = $_POST['confirm_password'];

  // ตรวจสอบว่ากรอกครบ
  if ($old && $new && $confirm) {
    if (strlen($new) < 9 || !preg_match('/^[a-zA-Z0-9]+$/', $new)) {
      $error = "รหัสผ่านใหม่ต้องมากกว่า 8 ตัว และประกอบด้วย a-z, A-Z, 0-9 เท่านั้น";
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
  <title>เปลี่ยนรหัสผ่าน</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width:500px">
  <h3 class="mb-3">🔒 เปลี่ยนรหัสผ่าน</h3>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="mb-3">
      <label>รหัสผ่านเดิม</label>
      <input type="password" class="form-control" name="old_password" required>
    </div>
    <div class="mb-3">
      <label>รหัสผ่านใหม่ (อย่างน้อย 9 ตัว, a-z, A-Z, 0-9)</label>
      <input type="password" class="form-control" name="new_password" required>
    </div>
    <div class="mb-3">
      <label>ยืนยันรหัสผ่านใหม่</label>
      <input type="password" class="form-control" name="confirm_password" required>
    </div>
    <button type="submit" class="btn btn-primary">บันทึกรหัสผ่านใหม่</button>
    <a href="index.php" class="btn btn-secondary">กลับ</a>
  </form>
</div>
</body>
</html>