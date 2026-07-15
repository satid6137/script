<?php
require __DIR__ . '/config.php';
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php?timeout=1");
  exit;
}

$id = $_GET['id'];

// โหลดข้อมูล save_query
$stmt = $conn->prepare("SELECT * FROM save_query WHERE id=?");
$stmt->bind_param('i', $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// โหลด cron list
$cronList = $conn->query("SELECT id, label, cron_expr FROM cron_profiles ORDER BY id");

// โหลด notify_settings
$stmt2 = $conn->prepare("SELECT * FROM notify_settings WHERE query_name=? AND hos_code=?");
$stmt2->bind_param('ss', $data['query_name'], $data['hos_code']);
$stmt2->execute();
$notify = $stmt2->get_result()->fetch_assoc();
$stmt2->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $his = $_POST['his_type'];
  $name = $_POST['query_name'];
  $text = $_POST['query_text'];
  $cronId = $_POST['cron_id'] ?: null;

  if ($his && $name && $text) {

    // อัปเดต save_query
    $stmt = $conn->prepare("UPDATE save_query SET his_type=?, query_name=?, query_text=?, cron_id=? WHERE id=?");
    $stmt->bind_param('sssii', $his, $name, $text, $cronId, $id);
    $stmt->execute();

    // ===============================
    // อัปเดต Notify Settings
    // ===============================
    if (!empty($_POST['enable_notify'])) {

      $notifyType = $_POST['notify_type'];
      $lineToken = $_POST['line_token'] ?? null;
      $mophClient = $_POST['moph_client_key'] ?? null;
      $mophSecret = $_POST['moph_secret_key'] ?? null;
      $notifyCron = $_POST['notify_cron_id'] ?: null;
      $description = $_POST['notify_description'] ?? null;
      if ($notify) {
        // UPDATE
        $stmt2 = $conn->prepare("
          UPDATE notify_settings 
SET notify_type=?, line_token=?, moph_client_key=?, moph_secret_key=?, cron_id=?, description=?
WHERE query_name=? AND hos_code=?
        ");
        $stmt2->bind_param(
          'ssssisss',
          $notifyType,
          $lineToken,
          $mophClient,
          $mophSecret,
          $notifyCron,
          $_POST['notify_description'],
          $name,
          $data['hos_code']
        );
        $stmt2->execute();

      } else {
        // INSERT
        $stmt2 = $conn->prepare("
          INSERT INTO notify_settings 
(query_name, hos_code, notify_type, line_token, moph_client_key, moph_secret_key, cron_id, description)
VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt2->bind_param(
          'ssssssis',
          $name,
          $data['hos_code'],
          $notifyType,
          $lineToken,
          $mophClient,
          $mophSecret,
          $notifyCron,
          $_POST['notify_description']
        );
        $stmt2->execute();
      }

    } else {
      // ปิด notify → ลบ notify_settings
      $stmt3 = $conn->prepare("DELETE FROM notify_settings WHERE query_name=? AND hos_code=?");
      $stmt3->bind_param('ss', $name, $data['hos_code']);
      $stmt3->execute();
    }

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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>แก้ไข Query | <?= $hospital ?></title>
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
      <h1 class="hos-page-title">แก้ไข Query</h1>
      <p class="hos-page-subtitle mb-0">ปรับปรุงการตั้งค่า Query เวลาทำงาน และการแจ้งเตือน</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger">
        <?= $error ?>
      </div>
    <?php endif; ?>

    <div class="hos-card">
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
          <textarea class="form-control" name="query_text" rows="6"
            required><?= htmlspecialchars($data['query_text']) ?></textarea>
        </div>

        <div class="mb-3">
          <label>เลือกช่วงเวลาการทำงาน</label>
          <select class="form-select" name="cron_id">
            <option value="">-- ไม่ตั้งเวลา --</option>
            <?php while ($cron = $cronList->fetch_assoc()): ?>
              <option value="<?= $cron['id'] ?>" <?= ($cron['id'] == $data['cron_id'] ? 'selected' : '') ?>>
                <?= htmlspecialchars($cron['label']) ?> (
                <?= $cron['cron_expr'] ?>)
              </option>
            <?php endwhile ?>
          </select>
          <div class="form-text">ระบุรอบเวลาที่ต้องการให้ query นี้ทำงานอัตโนมัติ</div>
        </div>

        <hr class="my-4">

        <h5>ตั้งค่าการแจ้งเตือน</h5>

        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" id="enableNotify" name="enable_notify" value="1" <?= $notify ? 'checked' : '' ?>>
          <label class="form-check-label" for="enableNotify">เปิดการส่งแจ้งเตือน</label>
        </div>

        <div id="notifyOptions" style="display: <?= $notify ? 'block' : 'none' ?>;">

          <div class="mb-3">
            <label>คำอธิบายสำหรับข้อความแจ้งเตือน</label>
            <textarea name="notify_description" class="form-control" rows="2"
              placeholder="เช่น รายชื่อผู้ป่วยที่มีข้อมูลผิดปกติ..."><?= $notify['description'] ?? '' ?></textarea>
            <div class="form-text">ข้อความนี้จะแสดงบนสุดของ LINE Notify</div>
          </div>


          <div class="mb-3">
            <label>ประเภทการแจ้งเตือน</label>
            <select name="notify_type" class="form-select">
              <option value="line" <?= ($notify && $notify['notify_type'] == 'line') ? 'selected' : '' ?>>LINE Notify
              </option>
              <option value="moph" <?= ($notify && $notify['notify_type'] == 'moph') ? 'selected' : '' ?>>MOPH Notify
              </option>
            </select>
          </div>

          <div id="lineBox" class="mb-3"
            style="display: <?= ($notify && $notify['notify_type'] == 'line') ? 'block' : 'none' ?>;">
            <label>LINE Token</label>
            <input type="text" name="line_token" class="form-control" value="<?= $notify['line_token'] ?? '' ?>">
          </div>

          <div id="mophBox" style="display: <?= ($notify && $notify['notify_type'] == 'moph') ? 'block' : 'none' ?>;">
            <div class="mb-3">
              <label>MOPH_CLIENT_KEY</label>
              <input type="text" name="moph_client_key" class="form-control"
                value="<?= $notify['moph_client_key'] ?? '' ?>">
            </div>
            <div class="mb-3">
              <label>MOPH_SECRET_KEY</label>
              <input type="text" name="moph_secret_key" class="form-control"
                value="<?= $notify['moph_secret_key'] ?? '' ?>">
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
                <option value="<?= $cron['id'] ?>" <?= ($notify && $notify['cron_id'] == $cron['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cron['label']) ?> (
                  <?= $cron['cron_expr'] ?>)
                </option>
              <?php endwhile ?>
            </select>
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