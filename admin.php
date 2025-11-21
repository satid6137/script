<?php
require 'config.php';
require_once 'log_helper.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) die("กรุณาเข้าสู่ระบบ");

// ตรวจสอบสิทธิ์ admin
$stmt = $conn->prepare("SELECT role FROM user WHERE id=?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();

if ($role !== 'admin') die("เฉพาะ admin เท่านั้นที่เข้าถึงหน้านี้ได้");

// จัดการ promote/demote/delete
if (isset($_GET['action'], $_GET['id'])) {
  $targetId = intval($_GET['id']);
  if ($targetId === $_SESSION['user_id']) {
    die("ไม่สามารถจัดการสิทธิ์ของตัวเองได้");
  }

  if ($_GET['action'] === 'promote') {
    $conn->query("UPDATE user SET role='admin' WHERE id=$targetId");
    logAction($conn, $_SESSION['user_id'], 'change_role', "user:$targetId", "Promote เป็น admin");
  } elseif ($_GET['action'] === 'demote') {
    $conn->query("UPDATE user SET role='user' WHERE id=$targetId");
    logAction($conn, $_SESSION['user_id'], 'change_role', "user:$targetId", "Demote เป็น user");
  } elseif ($_GET['action'] === 'delete') {
    $conn->query("DELETE FROM user WHERE id=$targetId");
    logAction($conn, $_SESSION['user_id'], 'delete_user', "user:$targetId", "ลบผู้ใช้");
  }

  header("Location: admin.php");
  exit;
}

$users = $conn->query("SELECT id, username, role FROM user ORDER BY id");
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Admin Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4" style="max-width: 900px;">
  <h3 class="mb-4">🛠️ จัดการผู้ใช้ (Admin Panel)</h3>

  <a href="register.php?from=admin" class="btn btn-success mb-4">➕ ไปสมัครสมาชิก</a>
  <a href="log.php" class="btn btn-outline-info mb-4">📜 ดู Log</a>

  <table class="table table-bordered table-striped bg-white">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Username</th>
        <th>Role</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($u = $users->fetch_assoc()): ?>
        <tr>
          <td><?= $u['id'] ?></td>
          <td><?= htmlspecialchars($u['username']) ?></td>
          <td><?= $u['role'] ?></td>
          <td>
            <?php if ($u['id'] !== $_SESSION['user_id']): ?>
              <?php if ($u['role'] === 'user'): ?>
                <a href="?action=promote&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary">💼 เป็น Admin</a>
              <?php else: ?>
                <a href="?action=demote&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-warning">↩️ ยกเลิกสิทธิ์</a>
              <?php endif; ?>
              <a href="?action=delete&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('ลบผู้ใช้นี้?')">🗑️ ลบ</a>
            <?php endif; ?>
            <a href="reset_password.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-dark">🔑 เปลี่ยนรหัส</a>
          </td>
        </tr>
      <?php endwhile ?>
    </tbody>
  </table>

  <a href="index.php" class="btn btn-secondary mt-4">⬅️ กลับหน้าแรก</a>
</div>
</body>
</html>