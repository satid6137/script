<?php
require __DIR__ . '/config.php';
require_once 'log_helper.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

// ตรวจสอบสิทธิ์ admin
$stmt = $conn->prepare("SELECT role FROM user WHERE id=?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();

if ($role !== 'admin')
  die("เฉพาะ admin เท่านั้นที่เข้าถึงหน้านี้ได้");

// -----------------------------
// ตัวเลือกจำนวนแถว
// -----------------------------
$limitOptions = [10, 20, 50, 100, 'all'];
$limit = $_GET['limit'] ?? 10;

// -----------------------------
// Search / Filter
// -----------------------------
$search_user = $_GET['user'] ?? '';
$search_action = $_GET['action'] ?? '';
$search_date_from = $_GET['date_from'] ?? '';
$search_date_to = $_GET['date_to'] ?? '';
$period = $_GET['period'] ?? ''; // daily / monthly

$where = "WHERE 1=1";

if ($search_user !== '') {
  $where .= " AND u.username = '" . $conn->real_escape_string($search_user) . "'";
}

if ($search_action !== '') {
  $where .= " AND l.action_type = '" . $conn->real_escape_string($search_action) . "'";
}

if ($search_date_from !== '') {
  $where .= " AND DATE(l.timestamp) >= '" . $conn->real_escape_string($search_date_from) . "'";
}

if ($search_date_to !== '') {
  $where .= " AND DATE(l.timestamp) <= '" . $conn->real_escape_string($search_date_to) . "'";
}

if ($period === 'today') {
  $where .= " AND DATE(l.timestamp) = CURDATE()";
} elseif ($period === 'month') {
  $where .= " AND YEAR(l.timestamp) = YEAR(CURDATE()) AND MONTH(l.timestamp) = MONTH(CURDATE())";
}

// -----------------------------
// ดึง username ทั้งหมด (dropdown)
// -----------------------------
$userList = $conn->query("SELECT username FROM user ORDER BY username ASC");

// -----------------------------
// ดึง action_type ทั้งหมด (dynamic)
// -----------------------------
$actionList = $conn->query("SELECT DISTINCT action_type FROM activity_log ORDER BY action_type ASC");

// -----------------------------
// นับจำนวนทั้งหมด
// -----------------------------
$countSql = "SELECT COUNT(*) AS total 
             FROM activity_log l 
             JOIN user u ON l.user_id = u.id 
             $where";

$countResult = $conn->query($countSql);
$totalRows = $countResult->fetch_assoc()['total'];

// -----------------------------
// Pagination
// -----------------------------
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;

if ($limit === 'all') {
  $totalPages = 1;
  $limitSql = "";
} else {
  $limit = intval($limit);
  $totalPages = max(1, ceil($totalRows / $limit));
  $page = max(1, min($page, $totalPages));
  $offset = ($page - 1) * $limit;
  $limitSql = "LIMIT $limit OFFSET $offset";
}

// -----------------------------
// ดึงข้อมูลตามหน้า
// -----------------------------
$sql = "SELECT l.*, u.username 
        FROM activity_log l 
        JOIN user u ON l.user_id = u.id 
        $where
        ORDER BY l.timestamp DESC
        $limitSql";

$result = $conn->query($sql);

// -----------------------------
// Export เฉพาะผลค้นหา / เฉพาะหน้า
// -----------------------------
if (isset($_GET['export']) && $_GET['export'] === 'excel') {

  header("Content-Type: application/vnd.ms-excel");
  header("Content-Disposition: attachment; filename=activity_log_filtered.xls");

  echo "เวลา\tผู้ใช้\tประเภท\tเป้าหมาย\tรายละเอียด\n";

  $exportSql = "SELECT l.*, u.username 
                  FROM activity_log l 
                  JOIN user u ON l.user_id = u.id 
                  $where
                  ORDER BY l.timestamp DESC";

  if ($limit !== 'all') {
    $exportSql .= " LIMIT $limit OFFSET $offset";
  }

  $exportResult = $conn->query($exportSql);

  while ($log = $exportResult->fetch_assoc()) {
    echo "{$log['timestamp']}\t{$log['username']}\t{$log['action_type']}\t{$log['target']}\t{$log['detail']}\n";
  }
  exit;
}

// -----------------------------
// Dashboard: กราฟรายวัน
// -----------------------------
$dailySql = "SELECT DATE(l.timestamp) AS d, COUNT(*) AS c
             FROM activity_log l
             JOIN user u ON l.user_id = u.id
             $where
             GROUP BY DATE(l.timestamp)
             ORDER BY DATE(l.timestamp) ASC";
$dailyRes = $conn->query($dailySql);
$dailyLabels = [];
$dailyData = [];
while ($row = $dailyRes->fetch_assoc()) {
  $dailyLabels[] = $row['d'];
  $dailyData[] = (int) $row['c'];
}

// -----------------------------
// Dashboard: กราฟตาม action_type
// -----------------------------
$actSql = "SELECT l.action_type AS a, COUNT(*) AS c
           FROM activity_log l
           JOIN user u ON l.user_id = u.id
           $where
           GROUP BY l.action_type
           ORDER BY l.action_type ASC";
$actRes = $conn->query($actSql);
$actLabels = [];
$actData = [];
while ($row = $actRes->fetch_assoc()) {
  $actLabels[] = $row['a'];
  $actData[] = (int) $row['c'];
}
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ประวัติกิจกรรม |
    <?= $hospital ?>
  </title>
  <link rel="icon" href="/script/assets/icons/health48.png" type="image/png">
  <link rel="apple-touch-icon" href="/script/assets/icons/health48.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/script/assets/css/theme.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    .table-fixed-header {
      height: 600px;
      overflow-y: auto;
      display: table;
      width: 100%;
      table-layout: fixed;
    }

    .table-fixed-header thead th {
      position: sticky;
      top: 0;
      background: var(--hos-blue-900, #0a3d63);
      color: white;
      z-index: 10;
    }

    .truncate {
      max-width: 250px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .pagination {
      flex-wrap: wrap;
      gap: 5px;
    }

    .action-buttons {
      display: flex;
      flex-wrap: nowrap;
      gap: 4px;
    }

    .action-buttons .btn {
      white-space: nowrap;
      padding: 2px 6px;
      font-size: 12px;
    }
  </style>
</head>

<body>
  <header class="hos-topbar">
    <div class="container">
      <a href="index.php" class="hos-brand">
        <img src="/script/assets/icons/health48.png" alt="โลโก้โรงพยาบาลห้างฉัตร">
        <span><?= $hospital ?><small>ระบบจัดการ Query API</small></span>
      </a>
    </div>
  </header>

  <div class="container mt-4">
    <div class="hos-page-header">
      <h1 class="hos-page-title">ประวัติกิจกรรม (Activity Log)</h1>
      <p class="hos-page-subtitle mb-0">ตรวจสอบและค้นหาประวัติการใช้งานระบบของผู้ใช้ทั้งหมด</p>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
      <a href="?export=excel&limit=<?= $limit ?>&page=<?= $page ?>&user=<?= $search_user ?>&action=<?= $search_action ?>&date_from=<?= $search_date_from ?>&date_to=<?= $search_date_to ?>&period=<?= $period ?>"
        class="btn btn-success">📥 Export เฉพาะผลค้นหา / เฉพาะหน้า</a>
      <a href="admin.php" class="btn btn-secondary">⬅ กลับไปหน้า Admin</a>
    </div>

    <!-- Dashboard -->
    <div class="row mb-4">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">📊 จำนวน Log รายวัน</div>
          <div class="card-body">
            <canvas id="dailyChart"></canvas>
          </div>
        </div>
      </div>
      <div class="col-md-6 mt-3 mt-md-0">
        <div class="card">
          <div class="card-header">📊 จำนวน Log ตามประเภทกิจกรรม</div>
          <div class="card-body">
            <canvas id="actionChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Search / Filter -->
    <form method="GET" class="row g-3 mb-4">

      <div class="col-md-3">
        <label class="form-label">ค้นหา Username</label>
        <select name="user" class="form-select">
          <option value="">-- ทั้งหมด --</option>
          <?php while ($u = $userList->fetch_assoc()): ?>
            <option value="<?= $u['username'] ?>" <?= ($search_user == $u['username']) ? 'selected' : '' ?>>
              <?= $u['username'] ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label">ประเภทกิจกรรม</label>
        <select name="action" class="form-select">
          <option value="">-- ทั้งหมด --</option>
          <?php while ($a = $actionList->fetch_assoc()): ?>
            <option value="<?= $a['action_type'] ?>" <?= ($search_action == $a['action_type']) ? 'selected' : '' ?>>
              <?= $a['action_type'] ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label">วันที่เริ่ม</label>
        <input type="date" name="date_from" value="<?= $search_date_from ?>" class="form-control">
      </div>

      <div class="col-md-3">
        <label class="form-label">วันที่สิ้นสุด</label>
        <input type="date" name="date_to" value="<?= $search_date_to ?>" class="form-control">
      </div>

      <div class="col-md-2">
        <label class="form-label">ช่วงเวลา</label>
        <select name="period" class="form-select">
          <option value="">-- ปกติ --</option>
          <option value="today" <?= $period == "today" ? "selected" : ""; ?>>วันนี้</option>
          <option value="month" <?= $period == "month" ? "selected" : ""; ?>>เดือนนี้</option>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label">แสดงจำนวนแถว</label>
        <select name="limit" class="form-select">
          <?php foreach ($limitOptions as $opt): ?>
            <option value="<?= $opt ?>" <?= ($opt == $limit) ? 'selected' : '' ?>>
              <?= $opt === 'all' ? 'ทั้งหมด' : $opt ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2 align-self-end">
        <button class="btn btn-primary w-100">ค้นหา</button>
      </div>

      <div class="col-md-2 align-self-end">
        <a href="log.php" class="btn btn-warning w-100">🔄 แสดงทั้งหมด</a>
      </div>

    </form>

    <div class="table-responsive hos-card p-0 mb-4">
      <table class="table table-striped align-middle mb-0 table-fixed-header">
        <thead>
          <tr>
            <th>เวลา</th>
            <th>ผู้ใช้</th>
            <th>ประเภท</th>
            <th>เป้าหมาย</th>
            <th>รายละเอียด</th>
            <th>เครื่องมือ</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($log = $result->fetch_assoc()): ?>
            <tr>
              <td>
                <?= $log['timestamp'] ?>
              </td>
              <td>
                <?= htmlspecialchars($log['username']) ?>
              </td>
              <td>
                <?= $log['action_type'] ?>
              </td>
              <td>
                <?= $log['target'] ?>
              </td>
              <td class="truncate" title="<?= htmlspecialchars($log['detail']) ?>">
                <?= htmlspecialchars($log['detail']) ?>
              </td>
              <td class="action-buttons">
                <!-- ดูรายละเอียดเต็ม -->
                <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal"
                  data-bs-target="#detailModal" data-detail="<?= htmlspecialchars($log['detail']) ?>"
                  data-time="<?= $log['timestamp'] ?>" data-user="<?= htmlspecialchars($log['username']) ?>"
                  data-action="<?= $log['action_type'] ?>" data-target="<?= $log['target'] ?>">
                  🔍 ดูเต็ม
                </button>

                <a href="?user=<?= urlencode($log['username']) ?>&action=&date_from=&date_to=&period=&limit=<?= $limit ?>&page=1"
                  class="btn btn-sm btn-outline-primary mt-1">
                  👤 user นี้
                </a>

                <!-- ดู log ราย action_type -->
                <a href="?user=&action=<?= $log['action_type'] ?>&date_from=&date_to=&limit=<?= $limit ?>&page=1"
                  class="btn btn-sm btn-outline-secondary mt-1">
                  ⚙ action นี้
                </a>
              </td>
            </tr>
          <?php endwhile ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($limit !== 'all' && $totalPages > 1): ?>
      <nav class="mt-3">
        <ul class="pagination d-flex flex-wrap">

          <!-- หน้าแรก -->
          <li class="page-item <?= ($page == 1) ? 'disabled' : '' ?>">
            <a class="page-link"
              href="?limit=<?= $limit ?>&page=1&user=<?= $search_user ?>&action=<?= $search_action ?>&date_from=<?= $search_date_from ?>&date_to=<?= $search_date_to ?>&period=<?= $period ?>">หน้าแรก</a>
          </li>

          <!-- ก่อนหน้า -->
          <li class="page-item <?= ($page == 1) ? 'disabled' : '' ?>">
            <a class="page-link"
              href="?limit=<?= $limit ?>&page=<?= max(1, $page - 1) ?>&user=<?= $search_user ?>&action=<?= $search_action ?>&date_from=<?= $search_date_from ?>&date_to=<?= $search_date_to ?>&period=<?= $period ?>">ก่อนหน้า</a>
          </li>

          <!-- หน้าต่าง ๆ -->
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
              <a class="page-link"
                href="?limit=<?= $limit ?>&page=<?= $i ?>&user=<?= $search_user ?>&action=<?= $search_action ?>&date_from=<?= $search_date_from ?>&date_to=<?= $search_date_to ?>&period=<?= $period ?>">
                <?= $i ?>
              </a>
            </li>
          <?php endfor; ?>

          <!-- ถัดไป -->
          <li class="page-item <?= ($page == $totalPages) ? 'disabled' : '' ?>">
            <a class="page-link"
              href="?limit=<?= $limit ?>&page=<?= min($totalPages, $page + 1) ?>&user=<?= $search_user ?>&action=<?= $search_action ?>&date_from=<?= $search_date_from ?>&date_to=<?= $search_date_to ?>&period=<?= $period ?>">ถัดไป</a>
          </li>

          <!-- หน้าสุดท้าย -->
          <li class="page-item <?= ($page == $totalPages) ? 'disabled' : '' ?>">
            <a class="page-link"
              href="?limit=<?= $limit ?>&page=<?= $totalPages ?>&user=<?= $search_user ?>&action=<?= $search_action ?>&date_from=<?= $search_date_from ?>&date_to=<?= $search_date_to ?>&period=<?= $period ?>">หน้าสุดท้าย</a>
          </li>

        </ul>
      </nav>
    <?php endif; ?>

  </div>

  <!-- Modal ดูรายละเอียดเต็ม -->
  <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">รายละเอียดกิจกรรม</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p><strong>เวลา:</strong> <span id="modalTime"></span></p>
          <p><strong>ผู้ใช้:</strong> <span id="modalUser"></span></p>
          <p><strong>ประเภท:</strong> <span id="modalAction"></span></p>
          <p><strong>เป้าหมาย:</strong> <span id="modalTarget"></span></p>
          <hr>
          <p><strong>รายละเอียดเต็ม:</strong></p>
          <pre id="modalDetail" style="white-space: pre-wrap;"></pre>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Modal fill
    var detailModal = document.getElementById('detailModal');
    detailModal.addEventListener('show.bs.modal', function (event) {
      var button = event.relatedTarget;
      document.getElementById('modalDetail').textContent = button.getAttribute('data-detail');
      document.getElementById('modalTime').textContent = button.getAttribute('data-time');
      document.getElementById('modalUser').textContent = button.getAttribute('data-user');
      document.getElementById('modalAction').textContent = button.getAttribute('data-action');
      document.getElementById('modalTarget').textContent = button.getAttribute('data-target');
    });

    // Chart.js data
    const dailyLabels = <?= json_encode($dailyLabels) ?>;
    const dailyData = <?= json_encode($dailyData) ?>;
    const actLabels = <?= json_encode($actLabels) ?>;
    const actData = <?= json_encode($actData) ?>;

    // Daily chart
    const ctxDaily = document.getElementById('dailyChart').getContext('2d');
    new Chart(ctxDaily, {
      type: 'line',
      data: {
        labels: dailyLabels,
        datasets: [{
          label: 'จำนวน log ต่อวัน',
          data: dailyData,
          borderColor: 'rgba(54, 162, 235, 1)',
          backgroundColor: 'rgba(54, 162, 235, 0.2)',
          tension: 0.2
        }]
      }
    });

    // Action chart
    const ctxAction = document.getElementById('actionChart').getContext('2d');
    new Chart(ctxAction, {
      type: 'bar',
      data: {
        labels: actLabels,
        datasets: [{
          label: 'จำนวน log ตามประเภท',
          data: actData,
          backgroundColor: 'rgba(255, 159, 64, 0.7)'
        }]
      }
    });
  </script>
</body>

</html>