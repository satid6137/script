<?php
ob_start();
require 'config.php';
require_once 'log_helper.php';
#session_start();

$username = null;
$loginTime = null;

if (isset($_SESSION['user_id'])) {
  $stmt = $conn->prepare("SELECT username FROM user WHERE id=?");
  $stmt->bind_param('i', $_SESSION['user_id']);
  $stmt->execute();
  $stmt->bind_result($username);
  $stmt->fetch();
  $stmt->close();
  $loginTime = $_SESSION['login_time'] ?? null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $user = trim($_POST['username']);
  $pass = trim($_POST['password']);

  $stmt = $conn->prepare("SELECT id, password FROM user WHERE username = ?");
  $stmt->bind_param('s', $user);
  $stmt->execute();
  $stmt->bind_result($uid, $hashed);

  if ($stmt->fetch() && password_verify($pass, $hashed)) {
    $stmt->close();
    $_SESSION['user_id'] = $uid;
    $_SESSION['login_time'] = date('Y-m-d H:i:s');
    logAction($conn, $uid, 'login', null, 'เข้าสู่ระบบสำเร็จ');
    header('Location: index.php');
    exit;
  }

  $stmt->close();
  $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>เข้าสู่ระบบ</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width:500px">
  <h3 class="mb-3">🔐 เข้าสู่ระบบ</h3>

  <?php if (isset($_SESSION['user_id'])): ?>
    <div class="alert alert-success">
      👋 คุณล็อกอินอยู่แล้วในชื่อ <strong><?= htmlspecialchars($username) ?></strong><br>
      ⏰ เข้าสู่ระบบเมื่อ: 
      <?= $loginTime ? date("Y-m-d H:i:s", strtotime($loginTime)) : 'ไม่ทราบเวลา' ?><br>
      🕒 ใช้งานมาแล้ว: 
      <?php
        if ($loginTime) {
          $loginTimestamp = strtotime($loginTime);
          $now = time();
          $diff = $now - $loginTimestamp;
          $days = floor($diff / 86400);
          $hours = floor(($diff % 86400) / 3600);
          $minutes = floor(($diff % 3600) / 60);
          echo ($days > 0 ? "$days วัน " : "") .
               ($hours > 0 ? "$hours ชั่วโมง " : "") .
               "$minutes นาที";
        } else {
          echo "ไม่สามารถคำนวณเวลาได้";
        }
      ?>
    </div>
    <a href="index.php" class="btn btn-primary">➡️ ไปหน้าแรก</a>
    <a href="logout.php" class="btn btn-outline-danger ms-2">🚪 ออกจากระบบ</a>
  <?php else: ?>
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label>Username</label>
        <input class="form-control" name="username" required>
      </div>
      <div class="mb-3">
        <label>Password</label>
        <input type="password" class="form-control" name="password" required>
      </div>
      <button class="btn btn-primary w-100" type="submit">เข้าสู่ระบบ</button>
      <a href="index.php" class="btn btn-secondary w-100 mt-2">⬅️ กลับหน้าแรก</a>
    </form>
  <?php endif; ?>
</div>
</body>
</html>