<?php
require __DIR__ . '/config.php';
require_once 'log_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?timeout=1");
    exit;
}

$users = $conn->query("SELECT id, username, role FROM user WHERE active = 0 ORDER BY id");
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ผู้ใช้ที่ถูกปิดการใช้งาน | <?= $hospital ?></title>
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

    <div class="container mt-4" style="max-width: 900px;">
        <div class="hos-page-header">
            <h1 class="hos-page-title">ผู้ใช้ที่ถูกปิดการใช้งาน</h1>
            <p class="hos-page-subtitle mb-0">รายชื่อผู้ใช้ที่ถูกระงับสิทธิ์การใช้งานระบบ</p>
        </div>

        <div class="table-responsive hos-card p-0 mb-4">
            <table class="table table-striped align-middle mb-0">
                <thead>
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
                            <td>
                                <?= $u['id'] ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($u['username']) ?>
                            </td>
                            <td>
                                <?= $u['role'] ?>
                            </td>
                            <td>
                                <a href="admin.php?action=activate&id=<?= $u['id'] ?>" class="btn btn-sm btn-success">
                                    ✔ เปิดการใช้งาน
                                </a>
                            </td>
                        </tr>
                    <?php endwhile ?>
                </tbody>
            </table>
        </div>

        <a href="admin.php" class="btn btn-secondary mb-4">⬅️ กลับหน้า Admin</a>
    </div>
</body>

</html>