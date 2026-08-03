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

$cfg = $conn->query("SELECT * FROM telemed_config LIMIT 1")->fetch_assoc();
$cronProfiles = $conn->query("SELECT id,label,cron_expr FROM cron_profiles ORDER BY id");
$today = date("Y-m-d");
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Telemed API Docs |
        <?= $hospital ?>
    </title>
    <link rel="icon" href="/script/assets/icons/health48.png" type="image/png">
    <link rel="apple-touch-icon" href="/script/assets/icons/health48.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container" style="max-width: 900px;">

        <div class="hos-page-header mt-4 mb-4">
            <h1 class="hos-page-title">Telemed API Docs</h1>
            <p class="hos-page-subtitle mb-0">ตั้งค่าการส่ง Telemed API โดยใช้ระบบ API-Query</p>
        </div>

        <?php if (isset($_GET['saved'])): ?>
            <div class="alert alert-success">บันทึกข้อมูลเรียบร้อยแล้ว</div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">

                <form method="POST" action="telemed_api_save.php">

                    <!-- วันที่รายงาน -->
                    <div class="mb-3">
                        <label class="fw-bold">วันที่รายงาน</label>
                        <input type="date" name="report_date" class="form-control" value="<?= $today ?>" readonly>
                        <div class="form-text">
                            หมายเหตุ: ระบบ Telemed API Docs จะดึงข้อมูลจาก Query API ตามช่วงเวลาที่กำหนดในระบบ Query
                            เดิม
                        </div>
                    </div>

                    <!-- ช่วงวันที่ HIS -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">ดึงข้อมูล HIS ตั้งแต่วันที่</label>
                            <input type="date" name="his_start_date" class="form-control"
                                value="<?= $cfg['his_start_date'] ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">ถึงวันที่</label>
                            <input type="date" name="his_end_date" class="form-control"
                                value="<?= $cfg['his_end_date'] ?>">
                        </div>
                    </div>

                    <!-- Query Name -->
                    <div class="mb-3">
                        <label class="fw-bold">ชื่อ Query สำหรับดึงข้อมูล HIS</label>
                        <input type="text" name="his_query_name" class="form-control"
                            value="<?= $cfg['his_query_name'] ?>">
                        <div class="form-text">เช่น telemed_api_his หรือชื่อ query ที่โรงพยาบาลคุณใช้จริง</div>
                    </div>

                    <!-- HIS Type -->
                    <div class="mb-3">
                        <label class="fw-bold">ประเภท HIS (hisType)</label>
                        <select name="his_type" class="form-select" required>
                            <option value="">-- กรุณาเลือก --</option>
                            <option value="hosxpv3" <?= $cfg['his_type'] == 'hosxpv3' ? 'selected' : '' ?>>hosxpv3</option>
                            <option value="hosxpv4" <?= $cfg['his_type'] == 'hosxpv4' ? 'selected' : '' ?>>hosxpv4</option>
                            <option value="thairefer" <?= $cfg['his_type'] == 'thairefer' ? 'selected' : '' ?>>thairefer
                            </option>
                            <option value="JHCIS" <?= $cfg['his_type'] == 'JHCIS' ? 'selected' : '' ?>>JHCIS</option>
                            <option value="IPD" <?= $cfg['his_type'] == 'IPD' ? 'selected' : '' ?>>IPD</option>
                        </select>
                    </div>

                    <hr>

                    <!-- Telemed API -->
                    <h5 class="fw-bold">Telemed API</h5>

                    <!-- HOS CODE -->
                    <div class="mb-3">
                        <label class="fw-bold">HOS CODE</label>
                        <input type="text" name="hos_code" class="form-control" value="<?= $cfg['hos_code'] ?>">
                        <div class="form-text">รหัสสถานพยาบาล เช่น 11156</div>
                    </div>

                    <!-- Node.js URL -->
                    <div class="mb-3">
                        <label class="fw-bold">Node.js API URL</label>
                        <input type="text" name="nodejs_url" class="form-control" value="<?= $cfg['nodejs_url'] ?>">
                        <div class="form-text">เช่น https://api.hangchathospital.com/api หรือ
                            http://123.123.123.123:9000</div>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold">Telemed API Key (tm_live_...)</label>
                        <input type="text" name="api_key" class="form-control" value="<?= $cfg['api_key'] ?>">
                    </div>

                    <hr>

                    <!-- MOPH Notify -->
                    <h5 class="fw-bold">MOPH Notify</h5>
                    <div class="mb-3">
                        <label class="fw-bold">Client ID</label>
                        <input type="text" name="client_id" class="form-control" value="<?= $cfg['client_id'] ?>">
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Client Secret</label>
                        <input type="text" name="client_secret" class="form-control"
                            value="<?= $cfg['client_secret'] ?>">
                    </div>

                    <hr>

                    <!-- Cron -->
                    <h5 class="fw-bold">Cron Profiles</h5>

                    <div class="mb-3">
                        <label class="fw-bold">เวลาในการส่ง Telemed API</label>
                        <select name="send_cron_id" class="form-select">
                            <option value="">-- ไม่ตั้งเวลา (manual) --</option>
                            <?php
                            $cronProfiles->data_seek(0);
                            while ($c = $cronProfiles->fetch_assoc()):
                                ?>
                                <option value="<?= $c['id'] ?>" <?= ($cfg['send_cron_id'] == $c['id'] ? 'selected' : '') ?>>
                                    <?= $c['label'] ?> (<?= $c['cron_expr'] ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button class="btn btn-primary">💾 บันทึก</button>
                        <a href="telemed_api_send.php" class="btn btn-success">📤 ส่ง Telemed (Manual)</a>
                        <a href="telemed_api_log.php" class="btn btn-info">📜 ดู Log</a>
                        <a href="index.php" class="btn btn-secondary">⬅️ กลับหน้าแรก</a>
                    </div>

                </form>

            </div>
        </div>

    </div>

</body>

</html>