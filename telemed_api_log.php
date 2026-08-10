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

$limit = $_GET['limit'] ?? 10;
$limit = ($limit === 'all') ? 999999 : intval($limit);

$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $limit;

// นับจำนวนทั้งหมด
$totalRows = $conn->query("SELECT COUNT(*) AS c FROM telemed_log WHERE $where")->fetch_assoc()['c'];
$totalPages = ceil($totalRows / $limit);

// ดึงข้อมูลตาม limit + offset
$logs = $conn->query("
    SELECT * FROM telemed_log 
    WHERE $where 
    ORDER BY id DESC 
    LIMIT $limit OFFSET $offset
");
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Telemed Log | <?= $hospital ?></title>

    <link rel="icon" href="/script/assets/icons/health48.png" type="image/png">
    <link rel="apple-touch-icon" href="/script/assets/icons/health48.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/script/assets/css/theme.css" rel="stylesheet">

    <style>
        body {
            font-family: 'IBM Plex Sans Thai', sans-serif;
            background: #eef2f7;
        }

        .card {
            border-radius: 12px;
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        pre {
            white-space: pre-wrap;
            word-break: break-word;
        }
    </style>
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

    <form class="mb-3">
        <label class="fw-bold">แสดงผล:</label>
        <select name="limit" class="form-select w-auto d-inline-block" onchange="this.form.submit()">
            <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
            <option value="20" <?= $limit == 20 ? 'selected' : '' ?>>20</option>
            <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
            <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100</option>
            <option value="all" <?= $limit > 100 ? 'selected' : '' ?>>ทั้งหมด</option>
        </select>

        <!-- คงค่าฟิลเตอร์เดิม -->
        <input type="hidden" name="date" value="<?= $_GET['date'] ?? '' ?>">
        <input type="hidden" name="status" value="<?= $_GET['status'] ?? '' ?>">
    </form>

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
    <nav>
        <ul class="pagination justify-content-center mt-3">

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= ($i == $page ? 'active' : '') ?>">
                    <a class="page-link"
                        href="?page=<?= $i ?>&limit=<?= $_GET['limit'] ?? 10 ?>&date=<?= $_GET['date'] ?? '' ?>&status=<?= $_GET['status'] ?? '' ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>

        </ul>
    </nav>

</body>

</html>