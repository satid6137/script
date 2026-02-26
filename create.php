<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) die("กรุณาเข้าสู่ระบบ");

$cronOptions = $conn->query("SELECT id, label, cron_expr FROM cron_profiles ORDER BY id");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $his  = $_POST['his_type'];
    $name = $_POST['query_name'];
    $text = $_POST['query_text'];

    if ($his && $name && $text) {

        // ❌ บังคับให้ใช้เฉพาะ A-Z, a-z, 0-9, _
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            $error = "ชื่อ Query ใช้ได้เฉพาะ A–Z, a–z, 0–9 และ _ เท่านั้น (ห้ามมีช่องว่างหรืออักขระพิเศษ)";
        } else {

            // ตรวจสอบชื่อซ้ำ
            $checkStmt = $conn->prepare("SELECT COUNT(*) FROM save_query WHERE query_name = ?");
            $checkStmt->bind_param('s', $name);
            $checkStmt->execute();
            $checkStmt->bind_result($count);
            $checkStmt->fetch();
            $checkStmt->close();

            if ($count > 0) {
                $error = "มีชื่อ Query นี้อยู่แล้ว กรุณาตั้งชื่อใหม่";
            } else {
                $createdBy = $_SESSION['user_id'];
                $cronId = $_POST['cron_id'] ?: null;

                $stmt = $conn->prepare("INSERT INTO save_query (his_type, query_name, query_text, created_by, cron_id, hos_code) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('sssiss', $his, $name, $text, $createdBy, $cronId, $hosCode);
                $stmt->execute();

                echo "<script>alert('เพิ่มข้อมูลสำเร็จ'); window.location='index.php';</script>";
                exit;
            }
        }

    } else {
        $error = "กรุณากรอกข้อมูลให้ครบทุกช่อง";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>➕ เพิ่ม Query</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4" style="max-width:800px;">
  <h3 class="mb-3">➕ เพิ่ม Query</h3>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="mb-3">
      <label>HIS Type</label>
      <select name="his_type" class="form-select" required>
        <option value="">-- กรุณาเลือก --</option>
        <option value="hosxpv3">hosxpv3</option>
        <option value="hosxpv4">hosxpv4</option>
		<option value="thairefer">thairefer</option>
        <option value="JHCIS">JHCIS</option>
		<option value="IPD">IPD</option>
      </select>
    </div>

    <div class="mb-3">
      <label>Query Name</label>
      <input class="form-control" name="query_name" required>
    </div>

    <div class="mb-3">
      <label>SQL Query</label>
      <textarea class="form-control" name="query_text" rows="6" required></textarea>
    </div>
	
	<div class="mb-3">
  <label>เลือกช่วงเวลาทำงาน</label>
  <select name="cron_id" class="form-select">
    <option value="">-- ไม่ตั้งเวลา --</option>
    <?php while ($cron = $cronOptions->fetch_assoc()): ?>
      <option value="<?= $cron['id'] ?>">
        <?= htmlspecialchars($cron['label']) ?> (<?= $cron['cron_expr'] ?>)
      </option>
    <?php endwhile ?>
  </select>
  <div class="form-text">ระบบจะรัน query นี้ตามช่วงเวลาที่กำหนด</div>
</div>

    <button type="submit" class="btn btn-primary">บันทึก</button>
    <a href="index.php" class="btn btn-secondary">กลับ</a>
  </form>
</div>
</body>

</html>
