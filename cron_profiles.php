<?php
require __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?timeout=1");
    exit;
}

// เปิด error ชั่วคราว
ini_set('display_errors', 1);
error_reporting(E_ALL);

// โหลด cron profiles
$sql = "SELECT * FROM cron_profiles ORDER BY id ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ตั้งเวลา Cron | <?= $hospital ?></title>
    <link rel="icon" href="/script/assets/icons/health48.png" type="image/png">
    <link rel="apple-touch-icon" href="/script/assets/icons/health48.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
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

    <div class="container mt-4">
        <div class="hos-page-header">
            <h1 class="hos-page-title">ตั้งเวลา Cron Profiles</h1>
            <p class="hos-page-subtitle mb-0">จัดการตารางเวลาอัตโนมัติ (***หลังจากเพิ่ม ลบ แก้ไข Cron ต้อง Restart
                NodeJs-Server ทุกครั้ง***)</p>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-3">
            <a href="admin.php" class="btn btn-secondary">⬅ กลับไปหน้า Admin</a>
            <a href="cron_edit.php" class="btn btn-success">➕ เพิ่ม Cron ใหม่</a>
        </div>

        <div class="table-responsive hos-card p-0 mb-4">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Label</th>
                        <th>Cron Expression</th>
                        <th>Notify Mode</th>
                        <th>Description</th>
                        <th width="150">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($c = $result->fetch_assoc()) { ?>
                        <tr>
                            <td>
                                <?php echo $c['id']; ?>
                            </td>
                            <td>
                                <?php echo $c['label']; ?>
                            </td>
                            <td>
                                <?php echo $c['cron_expr']; ?>
                            </td>
                            <td>
                                <?php echo $c['notify_mode']; ?>
                            </td>
                            <td>
                                <?php echo $c['description']; ?>
                            </td>
                            <td>
                                <a href="cron_edit.php?id=<?php echo $c['id']; ?>" class="btn btn-warning btn-sm">แก้ไข</a>
                                <a href="cron_delete.php?id=<?php echo $c['id']; ?>" class="btn btn-danger btn-sm"
                                    onclick="return confirm('ลบ Cron นี้?');">ลบ</a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>