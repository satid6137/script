<?php
require __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?timeout=1");
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$data = [
    'label' => '',
    'cron_expr' => '',
    'description' => '',
    'notify_mode' => 'ALL'
];

if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM cron_profiles WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $label = $_POST['label'];
    $cron_expr = $_POST['cron_expr'];
    $description = $_POST['description'];
    $notify_mode = $_POST['notify_mode'];

    if ($id > 0) {
        $stmt = $conn->prepare("
            UPDATE cron_profiles 
            SET label=?, cron_expr=?, description=?, notify_mode=? 
            WHERE id=?
        ");
        $stmt->bind_param("ssssi", $label, $cron_expr, $description, $notify_mode, $id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("
            INSERT INTO cron_profiles (label, cron_expr, description, notify_mode)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("ssss", $label, $cron_expr, $description, $notify_mode);
        $stmt->execute();
    }

    header("Location: cron_profiles.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $id ? "แก้ไข Cron" : "เพิ่ม Cron ใหม่"; ?> | <?= $hospital ?></title>
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

    <div class="container mt-4" style="max-width: 700px;">
        <div class="hos-page-header">
            <h1 class="hos-page-title">
                <?php echo $id ? "แก้ไข Cron" : "เพิ่ม Cron ใหม่"; ?>
            </h1>
            <p class="hos-page-subtitle mb-0">กำหนดรายละเอียดตารางเวลาอัตโนมัติของระบบ</p>
        </div>

        <div class="hos-card">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Label</label>
                    <input type="text" name="label" class="form-control" value="<?php echo $data['label']; ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Cron Expression</label>
                    <input type="text" name="cron_expr" class="form-control" value="<?php echo $data['cron_expr']; ?>"
                        required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notify Mode</label>
                    <select name="notify_mode" class="form-select">
                        <option value="ALL" <?php echo $data['notify_mode'] == "ALL" ? "selected" : ""; ?>>ALL</option>
                        <option value="FAIL_ONLY" <?php echo $data['notify_mode'] == "FAIL_ONLY" ? "selected" : ""; ?>>
                            FAIL_ONLY
                        </option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control"
                        rows="3"><?php echo $data['description']; ?></textarea>
                </div>

                <button class="btn btn-primary">บันทึก</button>
                <a href="cron_profiles.php" class="btn btn-secondary">กลับ</a>
            </form>
        </div>
    </div>
</body>

</html>