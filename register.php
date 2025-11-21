<?php
require 'config.php';
require_once 'log_helper.php'; // ถ้ามีระบบ log

function isValidUsername($username)
{
  return preg_match('/^[a-zA-Z0-9_.]{5,}$/', $username);
}

function isValidPassword($password)
{
  return preg_match('/^[a-zA-Z0-9@#$%^&*()]{9,}$/', $password);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username']);
  $password = trim($_POST['password']);
  $confirm  = trim($_POST['confirm']);
  $role     = 'user';

  if ($username && $password && $confirm) {
    if (!isValidUsername($username)) {
      $error = "ชื่อผู้ใช้ต้องเป็นภาษาอังกฤษ ตัวเลข _ หรือ . และมากกว่า 4 ตัวอักษร";
    } elseif (!isValidPassword($password)) {
      $error = "รหัสผ่านต้องมากกว่า 8 ตัวอักษร และประกอบด้วยภาษาอังกฤษหรือตัวเลขเท่านั้น";
    } elseif ($password !== $confirm) {
      $error = "รหัสผ่านไม่ตรงกัน";
    } else {
      $hashed = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $conn->prepare("INSERT INTO user (username, password, role) VALUES (?, ?, ?)");
      if (!$stmt->bind_param("sss", $username, $hashed, $role) || !$stmt->execute()) {
        $error = "ชื่อผู้ใช้นี้มีอยู่แล้ว หรือไม่สามารถสมัครได้";
      } else {
        $newUserId = $stmt->insert_id;
        if (isset($_SESSION['user_id'])) {
          logAction($conn, $_SESSION['user_id'], 'register', "user:$newUserId", "เพิ่มผู้ใช้ $username");
        }

        if (isset($_GET['from']) && $_GET['from'] === 'admin') {
          echo "<script>alert('เพิ่มผู้ใช้สำเร็จ!'); window.location='admin.php';</script>";
        } else {
          echo "<script>alert('สมัครสมาชิกสำเร็จ!'); window.location='login.php';</script>";
        }
        exit;
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
  <title>สมัครสมาชิก</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
  <div class="container mt-5" style="max-width:500px">
    <h3 class="mb-3">📝 สมัครสมาชิก</h3>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label>ชื่อผู้ใช้ (ภาษาอังกฤษ ตัวเลข _ หรือ . มากกว่า 4 ตัว)</label>
        <input name="username" class="form-control" 
       required pattern="[a-zA-Z0-9_.]{5,}" 
       title="ชื่อผู้ใช้ต้องเป็นภาษาอังกฤษ ตัวเลข _ หรือ . อย่างน้อย 5 ตัวอักษร">
      </div>
      <div class="mb-3">
        <label>รหัสผ่าน (อย่างน้อย 9 ตัว a-z, A-Z, 0-9, @#$%^&*())</label>
        <input type="password" name="password" class="form-control" id="password" required pattern="[a-zA-Z0-9@#$%^&*!]{9,}">
      </div>
      <div class="mb-3">
        <label>ยืนยันรหัสผ่าน</label>
        <input type="password" name="confirm" class="form-control" id="confirm" required>
      </div>
      <div class="form-check mb-3">
        <input type="checkbox" class="form-check-input" id="togglePassword">
        <label class="form-check-label" id="toggleLabel" for="togglePassword">แสดงรหัสผ่าน</label>
      </div>
      <button type="submit" class="btn btn-success">สมัครสมาชิก</button>
      <a href="<?= isset($_GET['from']) && $_GET['from'] === 'admin' ? 'admin.php' : 'login.php' ?>" class="btn btn-secondary">ย้อนกลับ</a>
    </form>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const toggle = document.getElementById("togglePassword");
      const label = document.getElementById("toggleLabel");

      toggle.addEventListener("change", function() {
        const pw1 = document.getElementById("password");
        const pw2 = document.getElementById("confirm");
        const show = toggle.checked;

        pw1.type = show ? "text" : "password";
        pw2.type = show ? "text" : "password";
        label.textContent = show ? "ซ่อนรหัสผ่าน" : "แสดงรหัสผ่าน";
      });
    });
  </script>
</body>

</html>