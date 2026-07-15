<?php
require __DIR__ . '/config.php';
require_once 'log_helper.php';

// ❌ ห้ามมี session_start() เพราะ config.php จัดการให้แล้ว

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php?timeout=1");
  exit;
}

// ตรวจสอบสิทธิ์ admin
$stmt = $conn->prepare("SELECT role FROM user WHERE id=?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();

if ($role !== 'admin')
  die("เฉพาะ admin เท่านั้นที่เข้าถึงหน้านี้ได้");

// จัดการ promote/demote/delete/2FA
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

    $conn->query("UPDATE user SET active = 0 WHERE id = $targetId");
    logAction($conn, $_SESSION['user_id'], 'deactivate_user', "user:$targetId", "ปิดการใช้งานผู้ใช้");

  } elseif ($_GET['action'] === 'activate') {

    $conn->query("UPDATE user SET active = 1 WHERE id = $targetId");
    logAction($conn, $_SESSION['user_id'], 'activate_user', "user:$targetId", "เปิดการใช้งานผู้ใช้");

  } elseif ($_GET['action'] === 'reset2fa') {

    // ลบ secret → ต้องตั้งค่าใหม่
    $conn->query("UPDATE user SET twofa_secret=NULL, twofa_enabled=0 WHERE id=$targetId");
    logAction($conn, $_SESSION['user_id'], 'reset_2fa', "user:$targetId", "รีเซ็ต 2FA");

  } elseif ($_GET['action'] === 'disable2fa') {

    // ปิด 2FA แต่ไม่ลบ secret
    $conn->query("UPDATE user SET twofa_enabled=0 WHERE id=$targetId");
    logAction($conn, $_SESSION['user_id'], 'disable_2fa', "user:$targetId", "ปิดการใช้งาน 2FA");

  }

  header("Location: admin.php");
  exit;
}

// ดึงข้อมูล user + สถานะ 2FA
$users = $conn->query("SELECT id, username, role, twofa_enabled FROM user WHERE active = 1 ORDER BY id");
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Panel | <?= $hospital ?></title>
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

  <div class="container" style="max-width: 1000px;">
    <div class="hos-page-header">
      <h1 class="hos-page-title">จัดการผู้ใช้ (Admin Panel)</h1>
      <p class="hos-page-subtitle mb-0">กำหนดสิทธิ์ผู้ใช้ ตั้งเวลา Cron และตรวจสอบ Log ของระบบ</p>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-4">
      <a href="register.php?from=admin" class="btn btn-success">➕ สร้าง Username</a>
      <a href="cron_profiles.php" class="btn btn-primary">⏱ ตั้งเวลา Cron</a>
      <a href="log.php" class="btn btn-outline-info">📜 ดู Log</a>
      <a href="inactive_users.php" class="btn btn-outline-warning">🚫 ผู้ใช้ที่ถูกปิดการใช้งาน</a>
    </div>

    <div class="table-responsive hos-card p-0 mb-4">
      <table class="table table-striped align-middle mb-0">
        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Role</th>
            <th>2FA</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($u = $users->fetch_assoc()): ?>
            <tr>
              <td>
                <?= $u['id'] ?>
              </td>
              <td>
                <?= htmlspecialchars($u['username']) ?>
              </td>
              <td>
                <?= $u['role'] ?>
              </td>

              <!-- แสดงสถานะ 2FA -->
              <td>
                <?php if ($u['twofa_enabled'] == 1): ?>
                  <span class="badge bg-success">เปิดใช้งาน</span>
                <?php else: ?>
                  <span class="badge bg-secondary">ปิดอยู่</span>
                <?php endif; ?>
              </td>

              <td>
                <?php if ($u['id'] !== $_SESSION['user_id']): ?>

                  <!-- Promote / Demote -->
                  <?php if ($u['role'] === 'user'): ?>
                    <a href="?action=promote&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary">💼 เป็น Admin</a>
                  <?php else: ?>
                    <a href="?action=demote&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-warning">↩️ ยกเลิกสิทธิ์</a>
                  <?php endif; ?>

                  <!-- Disable user -->
                  <a href="?action=delete&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger"
                    onclick="return confirm('ปิดการใช้งานผู้ใช้นี้?')">🗑️ ปิดการใช้งาน</a>

                <?php endif; ?>

                <!-- Reset password -->
                <a href="reset_password.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-dark">🔑 เปลี่ยนรหัส</a>

                <!-- ปุ่มเกี่ยวกับ 2FA -->
                <?php if ($u['twofa_enabled'] == 0): ?>

                  <!-- ยังไม่เปิด 2FA -->
                  <a href="twofa_setup.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-success">
                    🔐 ตั้งค่า 2FA
                  </a>

                <?php else: ?>

                  <!-- เปิด 2FA แล้ว → ใช้ Dropdown -->
                  <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-success dropdown-toggle" data-bs-toggle="dropdown"
                      aria-expanded="false">
                      🔐 2FA
                    </button>
                    <ul class="dropdown-menu">

                      <li>
                        <a class="dropdown-item text-warning" href="?action=reset2fa&id=<?= $u['id'] ?>"
                          onclick="return confirm('รีเซ็ต 2FA ของผู้ใช้นี้? ผู้ใช้ต้องตั้งค่าใหม่');">
                          ♻️ Reset 2FA
                        </a>
                      </li>

                      <li>
                        <a class="dropdown-item text-danger" href="?action=disable2fa&id=<?= $u['id'] ?>"
                          onclick="return confirm('ปิดการใช้งาน 2FA ของผู้ใช้นี้?');">
                          ❌ ปิด 2FA
                        </a>
                      </li>

                    </ul>
                  </div>

                <?php endif; ?>

              </td>

            </tr>
          <?php endwhile ?>
        </tbody>
      </table>
    </div>

    <a href="index.php" class="btn btn-secondary mb-4">⬅️ กลับหน้าแรก</a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </div>
</body>

</html>