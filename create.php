<?php
require __DIR__ . '/config.php';
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php?timeout=1");
  exit;
}

$cronOptions = $conn->query("SELECT id, label, cron_expr FROM cron_profiles ORDER BY id");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $his = $_POST['his_type'];
  $name = $_POST['query_name'];
  $text = $_POST['query_text'];

  if ($his && $name && $text) {

    // ❌ บังคับให้ใช้เฉพาะ A-Z, a-z, 0-9, _
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
      $error = "ชื่อ Query ใช้ได้เฉพาะ A–Z, a–z, 0–9 หรือ _ และ - เท่านั้น (ห้ามมีช่องว่างหรืออักขระพิเศษ)";
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

        // ===============================
// บันทึก Notify Settings
// ===============================
        if (!empty($_POST['enable_notify'])) {

          $notifyType = $_POST['notify_type'];
          $lineToken = $_POST['line_token'] ?? null;
          $mophClient = $_POST['moph_client_key'] ?? null;
          $mophSecret = $_POST['moph_secret_key'] ?? null;
          $notifyCron = $_POST['notify_cron_id'] ?: null;
          $description = $_POST['notify_description'] ?? null;

          $stmt2 = $conn->prepare("
    INSERT INTO notify_settings 
    (query_name, hos_code, notify_type, line_token, moph_client_key, moph_secret_key, cron_id, description)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
          $stmt2->bind_param(
            'ssssssis',
            $name,
            $hosCode,
            $notifyType,
            $lineToken,
            $mophClient,
            $mophSecret,
            $notifyCron,
            $description
          );
          $stmt2->execute();
        }


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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>เพิ่ม Query | <?= $hospital ?></title>
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

  <div class="container" style="max-width:800px;">
    <div class="hos-page-header">
      <h1 class="hos-page-title">เพิ่ม Query</h1>
      <p class="hos-page-subtitle mb-0">สร้าง Query ใหม่ พร้อมตั้งค่าเวลาทำงานและการแจ้งเตือน</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="hos-card">
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

        <hr class="my-4">

        <h5>ตั้งค่าการแจ้งเตือน</h5>

        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" id="enableNotify" name="enable_notify" value="1">
          <label class="form-check-label" for="enableNotify">เปิดการส่งแจ้งเตือน</label>
        </div>

        <div id="notifyOptions" style="display:none;">

          <div class="mb-3">
            <label>คำอธิบายสำหรับข้อความแจ้งเตือน</label>
            <textarea name="notify_description" class="form-control" rows="2"
              placeholder="เช่น รายชื่อผู้ป่วยที่มีข้อมูลผิดปกติ..."></textarea>
            <div class="form-text">ข้อความนี้จะแสดงบนสุดของ LINE Notify</div>
          </div>


          <div class="mb-3">
            <label>ประเภทการแจ้งเตือน</label>
            <select name="notify_type" class="form-select">
              <option value="line">LINE Notify</option>
              <option value="moph">MOPH Notify</option>
            </select>
          </div>

          <div id="lineBox" class="mb-3" style="display:none;">
            <label>LINE Token</label>
            <input type="text" name="line_token" class="form-control">
          </div>

          <div id="mophBox" style="display:none;">
            <div class="mb-3">
              <label>MOPH_CLIENT_KEY</label>
              <input type="text" name="moph_client_key" class="form-control">
            </div>
            <div class="mb-3">
              <label>MOPH_SECRET_KEY</label>
              <input type="text" name="moph_secret_key" class="form-control">
            </div>
          </div>

          <div class="mb-3">
            <label>เลือกเวลาส่งแจ้งเตือน</label>
            <select name="notify_cron_id" class="form-select">
              <option value="">-- ไม่ตั้งเวลา --</option>
              <?php
              $cronOptions2 = $conn->query("SELECT id, label, cron_expr FROM cron_profiles ORDER BY id");
              while ($cron = $cronOptions2->fetch_assoc()):
                ?>
                <option value="<?= $cron['id'] ?>">
                  <?= htmlspecialchars($cron['label']) ?> (
                  <?= $cron['cron_expr'] ?>)
                </option>
              <?php endwhile ?>
            </select>
            <div class="form-text">เวลานี้ใช้สำหรับแจ้งเตือนเท่านั้น ไม่เกี่ยวกับเวลารัน query</div>
          </div>

        </div>

        <script>
          document.getElementById('enableNotify').addEventListener('change', function () {
            document.getElementById('notifyOptions').style.display = this.checked ? 'block' : 'none';
          });

          document.querySelector('select[name="notify_type"]').addEventListener('change', function () {
            document.getElementById('lineBox').style.display = this.value === 'line' ? 'block' : 'none';
            document.getElementById('mophBox').style.display = this.value === 'moph' ? 'block' : 'none';
          });
        </script>

        <button type="submit" class="btn btn-primary">💾 บันทึก</button>
        <a href="index.php" class="btn btn-outline-secondary">⬅️ กลับ</a>
      </form>
    </div>
  </div>
</body>

</html>