<?php
require 'config.php';
require_once 'log_helper.php';
#session_start();

// ตรวจสอบสิทธิ์ admin
$stmt = $conn->prepare("SELECT role FROM user WHERE id=?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();

if ($role !== 'admin') die("เฉพาะ admin เท่านั้นที่เข้าถึงหน้านี้ได้");

// Export Excel
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
  header("Content-Type: application/vnd.ms-excel");
  header("Content-Disposition: attachment; filename=activity_log.xls");
  echo "เวลา\tผู้ใช้\tประเภท\tเป้าหมาย\tรายละเอียด\n";

  $sql = "SELECT l.*, u.username FROM activity_log l 
          JOIN user u ON l.user_id = u.id 
          ORDER BY l.timestamp DESC";
  $result = $conn->query($sql);
  while ($log = $result->fetch_assoc()) {
    echo "{$log['timestamp']}\t{$log['username']}\t{$log['action_type']}\t{$log['target']}\t{$log['detail']}\n";
  }
  exit;
}

// ดึง log สำหรับแสดงหน้าเว็บ
$sql = "SELECT l.*, u.username FROM activity_log l 
        JOIN user u ON l.user_id = u.id 
        ORDER BY l.timestamp DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>ประวัติกิจกรรม</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
  <h3 class="mb-4">📜 ประวัติกิจกรรม (Activity Log)</h3>

  <a href="?export=excel" class="btn btn-success mb-3">📥 Export Excel</a>

  <table class="table table-bordered table-striped bg-white">
    <thead class="table-dark">
      <tr>
        <th>เวลา</th>
        <th>ผู้ใช้</th>
        <th>ประเภท</th>
        <th>เป้าหมาย</th>
        <th>รายละเอียด</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($log = $result->fetch_assoc()): ?>
        <tr>
          <td><?= $log['timestamp'] ?></td>
          <td><?= htmlspecialchars($log['username']) ?></td>
          <td><?= $log['action_type'] ?></td>
          <td><?= $log['target'] ?></td>
          <td><?= htmlspecialchars($log['detail']) ?></td>
        </tr>
      <?php endwhile ?>
    </tbody>
  </table>

  <a href="admin.php" class="btn btn-secondary mt-3">⬅️ กลับ Admin</a>
</div>
</body>
</html>