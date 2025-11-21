<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) die("กรุณาเข้าสู่ระบบ");

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM save_query WHERE id=?");
$stmt->bind_param('i', $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$cronList = $conn->query("SELECT id, label, cron_expr FROM cron_profiles ORDER BY id");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $his = $_POST['his_type'];
  $name = $_POST['query_name'];
  $text = $_POST['query_text'];

  if ($his && $name && $text) {
	$cronId = $_POST['cron_id'] ?: null;
    $stmt = $conn->prepare("UPDATE save_query SET his_type=?, query_name=?, query_text=?, cron_id=? WHERE id=?");
    $stmt->bind_param('sssii', $his, $name, $text, $cronId, $id);
    $stmt->execute();
    echo "<script>alert('บันทึกการแก้ไขแล้ว'); window.location='index.php';</script>";
    exit;
  }
  $error = "กรุณากรอกข้อมูลให้ครบทุกช่อง";
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>✏️ แก้ไข Query</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4" style="max-width:800px;">
  <h3 class="mb-3">✏️ แก้ไข Query</h3>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="mb-3">
      <label>HIS Type</label>
      <input class="form-control" name="his_type" value="<?= htmlspecialchars($data['his_type']) ?>" readonly>
    </div>
    <div class="mb-3">
      <label>Query Name</label>
      <input class="form-control" name="query_name" value="<?= htmlspecialchars($data['query_name']) ?>" readonly>
    </div>
    <div class="mb-3">
      <label>SQL Query</label>
      <textarea class="form-control" name="query_text" rows="6" required><?= htmlspecialchars($data['query_text']) ?></textarea>
    </div>
	<div class="mb-3">
  <label>เลือกช่วงเวลาการทำงาน</label>
  <select class="form-select" name="cron_id">
    <option value="">-- ไม่ตั้งเวลา --</option>
    <?php while ($cron = $cronList->fetch_assoc()): ?>
      <option value="<?= $cron['id'] ?>" <?= ($cron['id'] == $data['cron_id'] ? 'selected' : '') ?>>
        <?= htmlspecialchars($cron['label']) ?> (<?= $cron['cron_expr'] ?>)
      </option>
    <?php endwhile ?>
  </select>
  <div class="form-text">ระบุรอบเวลาที่ต้องการให้ query นี้ทำงานอัตโนมัติ</div>
</div>
    <button type="submit" class="btn btn-primary">บันทึก</button>
    <a href="index.php" class="btn btn-secondary">กลับ</a>
  </form>
</div>
</body>
</html>