<?php
require __DIR__ . '/config.php';
require_once 'log_helper.php'; // ถ้ามีระบบ log
if (session_status() === PHP_SESSION_NONE)
  session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php?timeout=1");
  exit;
}

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
  $confirm = trim($_POST['confirm']);
  $role = 'user';

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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>สร้าง Username | <?= $hospital ?></title>
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
      <h1 class="hos-page-title">สร้าง Username</h1>
      <p class="hos-page-subtitle mb-0">เพิ่มผู้ใช้ใหม่เข้าสู่ระบบ</p>
    </div>

    <div class="hos-card">
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="mb-3">
          <label class="form-label">ชื่อผู้ใช้ (ภาษาอังกฤษ ตัวเลข _ หรือ . มากกว่า 4 ตัว)</label>
          <input name="username" class="form-control" required pattern="[a-zA-Z0-9_.]{5,}"
            title="ชื่อผู้ใช้ต้องเป็นภาษาอังกฤษ ตัวเลข _ หรือ . อย่างน้อย 5 ตัวอักษร">
        </div>
        <div class="mb-3">
          <label class="form-label">รหัสผ่าน (อย่างน้อย 9 ตัว a-z, A-Z, 0-9, @#$%^&*())</label>
          <input type="password" name="password" class="form-control" id="password" required
            pattern="[a-zA-Z0-9@#$%^&*!]{9,}">
        </div>
        <div class="mb-3">
          <label class="form-label">ยืนยันรหัสผ่าน</label>
          <input type="password" name="confirm" class="form-control" id="confirm" required>
        </div>
        <div class="form-check mb-3">
          <input type="checkbox" class="form-check-input" id="togglePassword">
          <label class="form-check-label" id="toggleLabel" for="togglePassword">แสดงรหัสผ่าน</label>
        </div>
        <button type="submit" class="btn btn-success">สมัครสมาชิก</button>
        <a href="<?= isset($_GET['from']) && $_GET['from'] === 'admin' ? 'admin.php' : 'login.php' ?>"
          class="btn btn-outline-secondary">ย้อนกลับ</a>
      </form>
    </div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const toggle = document.getElementById("togglePassword");
      const label = document.getElementById("toggleLabel");

      toggle.addEventListener("change", function () {
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