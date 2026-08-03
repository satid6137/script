<?php
require __DIR__ . '/config.php';
require_once 'log_helper.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

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

// ฟิลเตอร์
$where = "1=1";

if (!empty($_GET['date'])) {
    $date = $conn->real_escape_string($_GET['date']);
    $where .= " AND report_date = '$date'";
}

if (!empty($_GET['status'])) {
    $status = $conn->real_escape_string($_GET['status']);
    $where .= " AND status = '$status'";
}

$logs = $conn->query("SELECT * FROM telemed_log WHERE $where ORDER BY id DESC LIMIT 500");

// สรุปผล
$summary = $conn->query("
    SELECT 
        SUM(status='success') AS success_count,
        SUM(status!='success') AS fail_count
    FROM telemed_log
")->fetch_assoc();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Telemed Log | <?= $hospital ?></title>
    <link rel="icon" href="/script/assets/icons/health48.png" type="image/png">
    <link rel="apple-touch-icon" href="/script/assets/icons/health48.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="p-4 bg-light">

    <h2 class="mb-3">📜 Log การส่ง Telemed API</h2>

    <div class="mb-3">
        <a href="telemed_api_docs.php" class="btn btn-secondary">⬅️ ย้อนกลับ</a>
    </div>

    <!-- Summary -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h5 class="text-success">✔ ส่งสำเร็จ</h5>
                    <h2><?= $summary['success_count'] ?></h2>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <h5 class="text-danger">❌ ล้มเหลว</h5>
                    <h2><?= $summary['fail_count'] ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <form class="row g-3 mb-4">
        <div class="col-md-3">
            <label class="form-label fw-bold">ค้นหาตามวันที่</label>
            <input type="date" name="date" class="form-control" value="<?= $_GET['date'] ?? '' ?>">
        </div>

        <div class="col-md-3">
            <label class="form-label fw-bold">สถานะ</label>
            <select name="status" class="form-select">
                <option value="">-- ทั้งหมด --</option>
                <option value="success" <?= ($_GET['status'] ?? '') == 'success' ? 'selected' : '' ?>>สำเร็จ</option>
                <option value="fail" <?= ($_GET['status'] ?? '') == 'fail' ? 'selected' : '' ?>>ล้มเหลว</option>
            </select>
        </div>

        <div class="col-md-3 d-flex align-items-end">
            <button class="btn btn-primary w-100">🔍 ค้นหา</button>
        </div>

        <div class="col-md-3 d-flex align-items-end">
            <a href="telemed_api_log.php" class="btn btn-outline-secondary w-100">รีเซ็ต</a>
        </div>
    </form>

    <!-- Export CSV -->
    <div class="mb-3">
        <a href="telemed_api_log_export.php" class="btn btn-success">📤 Export CSV</a>
    </div>

    <!-- Log Table -->
    <table class="table table-bordered table-sm bg-white">
        <thead class="table-dark">
            <tr>
                <th>log_date</th>
                <th>hos_code</th>
                <th>report_date</th>
                <th>Status</th>
                <th>Message</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($l = $logs->fetch_assoc()): ?>
                <tr>
                    <td><?= $l['log_date'] ?></td>
                    <td><?= $l['hos_code'] ?></td>
                    <td><?= $l['report_date'] ?></td>
                    <td>
                        <?php if ($l['status'] === 'success'): ?>
                            <span class="badge bg-success">สำเร็จ</span>
                        <?php else: ?>
                            <span class="badge bg-danger">ล้มเหลว</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <pre><?= htmlspecialchars($l['message']) ?></pre>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</body>

</html>