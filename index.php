<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require __DIR__ . '/config.php';
require_once 'log_helper.php';

$username = null;
$userRole = null;

if (isset($_SESSION['user_id'])) {
  $stmt = $conn->prepare("SELECT username, role FROM user WHERE id=?");
  $stmt->bind_param('i', $_SESSION['user_id']);
  $stmt->execute();
  $stmt->bind_result($username, $userRole);
  $stmt->fetch();
  $stmt->close();
}

// ✅ ดึงรายการ Query
$result = $conn->query("
  SELECT 
      sq.id,
      sq.query_name,
      sq.his_type,
      sq.query_text,
      sq.last_post_at,
      u.username AS created_by_name,
      cp.label AS cron_label,

      ns.notify_type,
      ns.line_token,
      ns.moph_client_key,
      ns.moph_secret_key,
      ns.description,

      cp2.label AS notify_label,
      cp2.cron_expr AS notify_cron_expr

  FROM save_query sq
  LEFT JOIN user u 
      ON sq.created_by = u.id
  LEFT JOIN cron_profiles cp 
      ON sq.cron_id = cp.id

  LEFT JOIN notify_settings ns 
      ON ns.query_name COLLATE utf8mb4_unicode_ci 
         = sq.query_name COLLATE utf8mb4_unicode_ci
      AND ns.hos_code COLLATE utf8mb4_unicode_ci
         = sq.hos_code COLLATE utf8mb4_unicode_ci

  LEFT JOIN cron_profiles cp2 
      ON cp2.id = ns.cron_id

  ORDER BY sq.id DESC
");


$totalRows = $result->num_rows;
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Query Dashboard | <?= $hospital ?></title>
  <link rel="icon" href="/script/assets/icons/health48.png" type="image/png">
  <link rel="apple-touch-icon" href="/script/assets/icons/health48.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/script/assets/css/theme.css" rel="stylesheet">
  <style>
    .row-hidden {
      display: none !important;
    }

    pre {
      white-space: pre-wrap;
      word-break: break-word;
    }
  </style>
  <style>
    .query-name-wrap {
      white-space: normal !important;
      word-break: break-word;
      max-width: 300px;
      /* ปรับได้ */
    }
  </style>
  <style>
    .action-buttons {
      white-space: nowrap;
    }

    .action-buttons a {
      display: inline-block;
      margin-right: 4px;
    }
  </style>

</head>

<body>

  <header class="hos-topbar">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
      <a href="index.php" class="hos-brand">
        <img src="/script/assets/icons/health48.png" alt="โลโก้โรงพยาบาลห้างฉัตร">
        <span>
          <?= $hospital ?><small>ระบบจัดการ Query API</small>
        </span>
      </a>
      <div class="d-flex align-items-center flex-wrap gap-2">
        <?php if (isset($_SESSION['user_id'])): ?>
          <span class="hos-user-chip">👤 <?= htmlspecialchars($username) ?> · <?= htmlspecialchars($userRole) ?></span>
          <a href="create.php" class="btn btn-sm btn-success">➕ เพิ่ม Query</a>
          <a href="change_password.php" class="btn btn-sm btn-outline-secondary">เปลี่ยนรหัสผ่าน</a>
          <?php if ($userRole === 'admin'): ?>
            <a href="admin.php" class="btn btn-sm btn-outline-dark">Admin Panel</a>
          <?php endif; ?>
          <a href="logout.php" class="btn btn-sm btn-outline-danger">ออกจากระบบ</a>
        <?php else: ?>
          <a href="login.php" class="btn btn-sm btn-outline-primary">เข้าสู่ระบบ</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <div class="container">

    <div class="hos-page-header">
      <h1 class="hos-page-title">รายการ Query</h1>
      <p class="hos-page-subtitle mb-0">ตั้งค่า ส่งคำสั่ง และติดตามสถานะ Query ทั้งหมดของโรงพยาบาล</p>
    </div>

    <!-- ช่องค้นหา + ล้าง + วันที่ + Export -->
    <div class="hos-toolbar row gy-2">
      <div class="col-12 col-lg-6">
        <div class="input-group">
          <span class="input-group-text bg-white">🔍</span>
          <input type="text" id="querySearch" class="form-control" placeholder="ค้นหา HIS, ชื่อ Query หรือผู้สร้าง...">
          <button class="btn btn-outline-secondary" type="button" id="clearSearch">ล้าง</button>
        </div>
      </div>
      <div class="col-12 col-lg-4 row g-2">
        <div class="col-6"><input type="date" id="startDate" class="form-control" placeholder="เริ่ม"></div>
        <div class="col-6"><input type="date" id="endDate" class="form-control" placeholder="สิ้นสุด"></div>
      </div>
      <div class="col-12 col-lg-2 text-lg-end">
        <button class="btn btn-outline-success w-100" onclick="exportCSV()">📤 Export CSV</button>
      </div>
    </div>

    <div class="table-responsive hos-card p-0">
      <table class="table table-striped align-middle mb-0">
        <thead class="table-dark">
          <tr>
            <th style="width: 80px;">HIS</th>
            <th style="width: 300px;">ชื่อ Query</th>
            <th style="width: 250px;">Query</th>
            <th style="width: 120px;">ผู้สร้าง</th>
            <th style="width: 300px;">POST → client</th>
            <th style="width: 160px;">🕒 ส่งล่าสุด</th>
            <th style="width: 100px;" class="text-center">สถานะ</th>
            <?php if (isset($_SESSION['user_id'])): ?>
              <th style="width: 120px;" class="text-center">การจัดการ</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody id="queryTableBody">
          <?php while ($row = $result->fetch_assoc()):
            $queryNameRaw = $row['query_name'];
            $queryName = rawurlencode($queryNameRaw);
            $hisType = htmlspecialchars($row['his_type']);
            $queryText = htmlspecialchars(substr($row['query_text'], 0, 40));
            $createdBy = htmlspecialchars($row['created_by_name'] ?? '—');
            $rowId = $row['id'];
            $lastPost = $row['last_post_at'] ?? null;
            $urlPost = "{$ipServer}/query/{$queryName}/{$hosCode}";
            $urlAPI = "{$nodejs}/query/{$queryName}/{$hosCode}";
            $postData = [
              'url' => $urlPost,
              'apiKey' => $apiKey,
              'hisType' => $row['his_type']
            ];

            ?>
            <tr data-his="<?= strtolower($hisType) ?>" data-query="<?= strtolower($queryNameRaw) ?>"
              data-user="<?= strtolower($createdBy) ?>">
              <td>
                <?= $hisType ?>
              </td>
              <td class="query-name-wrap">
                <?= htmlspecialchars($queryNameRaw) ?>

                <?php if (!empty($row['cron_label'])): ?>
                  <div class="text-muted small">

                    <?= htmlspecialchars($row['cron_label']) ?>
                  </div>
                <?php endif; ?>

                <?php if (!empty($row['notify_type']) && $row['notify_type'] !== 'none'): ?>
                  <div class="text-muted small">
                    🔔
                    <?php if ($row['notify_type'] === 'line'): ?>
                      LINE
                    <?php elseif ($row['notify_type'] === 'moph'): ?>
                      MOPH
                    <?php endif; ?>

                    <?php if (!empty($row['notify_label'])): ?>
                      (
                      <?= htmlspecialchars($row['notify_label']) ?>)
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </td>

              <td>
                <pre class="text-muted small mb-1"><?= $queryText ?>...</pre>
                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#queryModal"
                  data-query-text="<?= htmlspecialchars($row['query_text'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                  data-query-name="<?= htmlspecialchars($queryNameRaw ?? '', ENT_QUOTES, 'UTF-8') ?>"
                  onclick="showQueryModal(this.dataset.queryText, this.dataset.queryName)">
                  👁️ ดู Query เต็ม
                </button>
              </td>
              <td>
                <?= $createdBy ?>
              </td>
              <td>
                <button type="button" class="btn btn-sm btn-primary"
                  onclick="postToClient('<?= $urlPost ?>', '<?= addslashes($queryNameRaw) ?>', <?= $rowId ?>, '<?= $row['his_type'] ?>')">
                  🚀 ส่งคำสั่ง
                </button>
                <button type="button" class="btn btn-sm btn-success"
                  onclick="sendNotifyNow('<?= addslashes($queryNameRaw) ?>', '<?= $hosCode ?>', <?= $rowId ?>)">
                  🔔 ส่งแจ้งเตือน
                </button>

                <?php if (isset($_SESSION['user_id'])): ?>
                  <!-- กรณี login แล้ว -->
                  <div class="small mt-2">🚀 API URL :<br>
                    <code class="text-wrap d-inline-block" style="max-width: 350px;"><?= $urlAPI ?></code>
                    <button class="btn btn-sm btn-outline-secondary p-0 px-1 ms-1" title="คัดลอก URL"
                      onclick="navigator.clipboard.writeText('<?= addslashes($urlAPI) ?>').then(() => { this.textContent='✅'; setTimeout(() => this.innerHTML='📋', 1500) })">
                      📋
                    </button>
                    <a href="<?= $urlAPI ?>" target="_blank" class="btn btn-sm btn-outline-info p-0 px-1 ms-1"
                      title="เปิดในแท็บใหม่">
                      🔗
                    </a>
                  </div>
                <?php else: ?>
                  <!-- กรณียังไม่ได้ login -->
                  <div class="small mt-2 text-muted fst-italic">
                    🔑 API URL (ping เท่านั้น):<br>

                    <?php
                    $pingUrl = "{$nodejs}/query/{$queryName}/{$hosCode}?ping=true";
                    ?>

                    <code class="text-wrap d-inline-block" style="max-width: 350px;">
                                                                                                                                                                                                                                                                          <?= $pingUrl ?>
                                                                                                                                                                                                                                                                        </code>

                    <!-- ปุ่มคัดลอก -->
                    <button class="btn btn-sm btn-outline-secondary p-0 px-1 ms-1" title="คัดลอก URL" onclick="navigator.clipboard.writeText('<?= addslashes($pingUrl) ?>')
        .then(() => { this.textContent='✅'; setTimeout(() => this.innerHTML='📋', 1500) })">
                      📋
                    </button>

                    <!-- ปุ่มเปิดแท็บใหม่ -->
                    <a href="<?= $pingUrl ?>" target="_blank" class="btn btn-sm btn-outline-info p-0 px-1 ms-1"
                      title="เปิดในแท็บใหม่">
                      🔗
                    </a>

                  </div>
                <?php endif; ?>

              </td>
              <td class="text-muted small" id="last-post-<?= $rowId ?>">
                <?php if ($lastPost): ?>
                  📅
                  <?= date('Y-m-d H:i', strtotime($lastPost)) ?><br>
                  <span class="text-info small ago" data-dt="<?= $lastPost ?>">⏱ คำนวณ...</span>
                <?php else: ?>
                  <span class="text-secondary">—</span>
                <?php endif; ?>
              </td>
              <td class="text-center fw-bold" id="status-<?= $rowId ?>">⏳</td>
              <?php if (isset($_SESSION['user_id'])): ?>
                <td class="action-buttons">

                  <!-- บรรทัดแรก -->
                  <div class="mb-1">
                    <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">
                      ✏️ แก้ไข
                    </a>

                    <a href="javascript:void(0)" onclick="deleteQueryAjax(<?= $row['id'] ?>, this)"
                      class="btn btn-sm btn-danger">
                      🗑️ ลบQuery
                    </a>
                  </div>

                  <!-- บรรทัดที่สอง -->
                  <div>
                    <a href="javascript:void(0)" onclick="deleteTableOnly('<?= $row['query_name'] ?>', this)"
                      class="btn btn-sm btn-secondary">
                      🗂️ ลบTable
                    </a>
                  </div>

                </td>

              <?php endif; ?>
            </tr>
          <?php endwhile ?>
        </tbody>
      </table>
    </div>


    <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
      <div>
        <label>แสดง:</label>
        <select id="rowsPerPage" class="form-select d-inline-block w-auto">
          <option value="10">10</option>
          <option value="20">20</option>
          <option value="50">50</option>
          <option value="100">100</option>
          <option value="200">200</option>
          <option value="all">ทั้งหมด</option>
        </select>
      </div>
      <nav aria-label="Page navigation">
        <ul class="pagination mb-0" id="paginationNav"></ul>
      </nav>
    </div>


    <p class="text-muted small">
      🔢 พบทั้งหมด
      <?= $totalRows ?> รายการ
      <span class="ms-3">📝 สถานะ: ⏳ = รอดำเนินการ, ✅ = สำเร็จ, ❌ = ล้มเหลว</span>
    </p>

    <!-- Modal แสดง Query เต็ม -->
    <div class="modal fade" id="queryModal" tabindex="-1" aria-labelledby="queryModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="queryModalLabel">Query เต็ม</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
          </div>
          <div class="modal-body position-relative">
            <pre id="queryModalBody" class="text-muted small mb-0"></pre>
            <!-- 📋 ปุ่มคัดลอก -->
            <button id="copyBtn" class="btn btn-sm btn-outline-primary position-absolute top-0 end-0 m-2">
              📋 คัดลอก
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Toast แจ้งผลการคัดลอก -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
      <div id="copyToast" class="toast align-items-center text-bg-success border-0" role="alert">
        <div class="d-flex">
          <div class="toast-body">✅ คัดลอก Query เรียบร้อยแล้ว!</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
    </div>

    <!-- Toast แจ้งผลการทำงานทั่วไป -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
      <div id="toastMessage" class="toast align-items-center text-white bg-success border-0" role="alert">
        <div class="d-flex">
          <div class="toast-body" id="toastBody">✅ สำเร็จ!</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
    </div>

    <script>
      // 🚀 POST ไป client พร้อมอัปเดตสถานะ
      function postToClient(url, queryName, rowId, hisType) {
        const statusCell = document.getElementById('status-' + rowId);
        statusCell.textContent = '⏳';

        fetch('post_query.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({
            url: url,
            queryName: queryName,
            hosCode: hosCode,
            hisType: hisType,
            apiKey: apiKey
          })
        })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              statusCell.textContent = '✅';
              showToast(`✅ ส่งคำสั่ง "${queryName}" สำเร็จ`, 'success');
            } else {
              statusCell.textContent = '❌';
              showToast(`❌ ล้มเหลว: ${data.error || 'ไม่ทราบสาเหตุ'}`, 'danger');
            }
          })
          .catch(err => {
            statusCell.textContent = '❌';
            showToast(`❌ ไม่สามารถเชื่อมต่อ server ได้: ${err.message}`, 'danger');
          });
      }

      // 🚀 POST ไป notify พร้อมอัปเดตสถานะ
      function sendNotifyNow(queryName, hosCode, rowId) {
        if (!confirm(`📨 ต้องการส่งแจ้งเตือนของ "${queryName}" ตอนนี้เลยใช่ไหม`)) return;

        fetch('send_notify_now.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `queryName=${encodeURIComponent(queryName)}&hosCode=${encodeURIComponent(hosCode)}`
        })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              showToast('✅ ส่งแจ้งเตือนสำเร็จ', 'success');
            } else {
              showToast(`❌ ส่งแจ้งเตือนไม่สำเร็จ: ${data.error}`, 'danger');
            }
          })
          .catch(err => {
            showToast('❌ ไม่สามารถเชื่อมต่อ server', 'danger');
          });
      }


      // 🔍 ค้นหา + กรอง
      function filterRows(resetPage = true) {
        const search = document.getElementById('querySearch').value.toLowerCase();
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        const rows = document.querySelectorAll('#queryTableBody tr');
        let matchCount = 0;

        rows.forEach(row => {
          const query = row.getAttribute('data-query') || '';
          const his = row.getAttribute('data-his') || '';
          const user = row.getAttribute('data-user') || '';
          let visible = true;

          if (search && !(query.includes(search) || his.includes(search) || user.includes(search))) visible = false;

          if (startDate || endDate) {
            const queryDate = row.getAttribute('data-date') || ''; // หมายเหตุ: ถ้าต้องใช้จริง ให้เติม data-date ตอน render
            if ((startDate && queryDate < startDate) || (endDate && queryDate > endDate)) visible = false;
          }

          if (visible) {
            row.classList.remove('row-hidden');
            matchCount++;
          } else {
            row.classList.add('row-hidden');
          }
        });

        if (resetPage) currentPage = 1;
        paginateRows();

        const total = rows.length;
        const rc = document.getElementById('resultCount');
        if (rc) {
          rc.textContent = `🔢 พบทั้งหมด ${total} รายการ / แสดง ${matchCount} รายการ${search ? 'ที่ตรงกับคำค้น' : ''}`;
        }

      }

      // ⌨️ Event ค้นหา/กรอง/ล้าง
      document.getElementById('querySearch').addEventListener('input', filterRows);
      document.getElementById('startDate').addEventListener('change', filterRows);
      document.getElementById('endDate').addEventListener('change', filterRows);
      document.getElementById('clearSearch').addEventListener('click', () => {
        document.getElementById('querySearch').value = '';
        document.getElementById('startDate').value = '';
        document.getElementById('endDate').value = '';
        filterRows();
      });

      // 📤 Export CSV
      function exportCSV() {
        const rows = Array.from(document.querySelectorAll('#queryTableBody tr'))
          .filter(row => row.style.display !== 'none');

        if (rows.length === 0) {
          alert('ไม่มีข้อมูลให้ส่งออก');
          return;
        }

        const headers = ['HIS', 'ชื่อ Query', 'Query'];
        const lines = [headers.join(',')];

        rows.forEach(row => {
          const his = row.children[0]?.innerText.trim().replace(/,/g, ' ') || '';
          const name = row.children[1]?.innerText.trim().replace(/,/g, ' ') || '';
          const query = row.children[2]?.innerText.trim().replace(/,/g, ' ') || '';
          lines.push([his, name, query].join(','));
        });

        const blob = new Blob(["\uFEFF" + lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'query_list.csv';
        link.click();
      }

      // ⏱ timeAgo + updater
      function timeAgo(datetime) {
        const t = new Date(datetime);
        const now = new Date();
        const diff = Math.floor((now - t) / 1000);

        if (diff < 60) return 'ไม่กี่วินาทีที่แล้ว';
        if (diff < 3600) return `${Math.floor(diff / 60)} นาทีที่แล้ว`;
        if (diff < 86400) return `${Math.floor(diff / 3600)} ชั่วโมงที่แล้ว`;
        if (diff < 604800) return `${Math.floor(diff / 86400)} วันที่แล้ว`;
        return t.toLocaleDateString();
      }

      function updateTimeAgo() {
        document.querySelectorAll('.ago').forEach(span => {
          const dt = span.getAttribute('data-dt');
          if (dt) span.textContent = `⏱ ${timeAgo(dt)}`;
        });
      }
      updateTimeAgo();
      setInterval(updateTimeAgo, 60 * 1000);

      // 👁️ Modal: set content
      window.showQueryModal = (queryText, queryName) => {
        document.getElementById('queryModalLabel').textContent = `Query เต็ม: ${queryName}`;
        document.getElementById('queryModalBody').textContent = queryText;
      };

      // 📋 Copy Query + Toast
      document.addEventListener('DOMContentLoaded', () => {
        const copyBtn = document.getElementById('copyBtn');
        const bodyEl = document.getElementById('queryModalBody');
        const toastEl = document.getElementById('copyToast');

        if (copyBtn) {
          copyBtn.addEventListener('click', async () => {
            const text = bodyEl?.textContent || '';
            if (!text.trim()) {
              alert('❌ ไม่มีข้อความให้คัดลอก');
              return;
            }
            try {
              if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
                new bootstrap.Toast(toastEl).show();
              } else {
                fallbackCopy(text);
              }
            } catch {
              fallbackCopy(text);
            }
          });
        }

        function fallbackCopy(text) {
          const ta = document.createElement('textarea');
          ta.value = text;
          ta.setAttribute('readonly', '');
          ta.style.position = 'fixed';
          ta.style.left = '-9999px';
          document.body.appendChild(ta);
          ta.select();
          try {
            document.execCommand('copy');
            new bootstrap.Toast(toastEl).show();
          } catch (err) {
            alert('❌ คัดลอกไม่สำเร็จ');
          }
          document.body.removeChild(ta);
        }
      });

      // 📄 Pagination
      let currentPage = 1;
      let rowsPerPage = parseInt(document.getElementById('rowsPerPage')?.value) || 10;

      function paginateRows() {
        const rows = document.querySelectorAll('#queryTableBody tr');
        let visibleRows = [];

        rows.forEach(row => {
          if (row.classList.contains('row-hidden')) {
            row.style.display = 'none';
          } else {
            visibleRows.push(row);
          }
        });

        const totalVisible = visibleRows.length;
        const totalPages = rowsPerPage === Infinity ? 1 : Math.ceil(totalVisible / rowsPerPage);
        const start = rowsPerPage === Infinity ? 0 : (currentPage - 1) * rowsPerPage;
        const end = rowsPerPage === Infinity ? totalVisible : start + rowsPerPage;

        visibleRows.forEach((row, idx) => {
          row.style.display = (idx >= start && idx < end) ? '' : 'none';
        });

        renderPagination(totalVisible);
      }

      function renderPagination(totalVisible) {
        const totalPages = rowsPerPage === Infinity ? 1 : Math.ceil(totalVisible / rowsPerPage);
        const nav = document.getElementById('paginationNav');
        nav.innerHTML = '';

        if (rowsPerPage === Infinity || totalPages <= 1) return;

        for (let i = 1; i <= totalPages; i++) {
          const li = document.createElement('li');
          li.className = 'page-item' + (i === currentPage ? ' active' : '');
          const a = document.createElement('a');
          a.className = 'page-link';
          a.href = '#';
          a.textContent = i;

          a.addEventListener('click', (e) => {
            e.preventDefault();
            currentPage = i;
            filterRows(false);
          });

          li.appendChild(a);
          nav.appendChild(li);
        }
      }

      document.getElementById('rowsPerPage')?.addEventListener('change', function () {
        const val = this.value;
        rowsPerPage = val === 'all' ? Infinity : parseInt(val);
        currentPage = 1;
        paginateRows();
        filterRows();
      });

      // เรียกครั้งแรก
      paginateRows();
      filterRows();

      // 🗑️ ลบ Query+table ผ่าน AJAX
      function deleteQueryAjax(id, btn) {
        if (!confirm("⚠️ ยืนยันการลบ?")) return;

        fetch('delete_api.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `id=${encodeURIComponent(id)}`
        })
          .then(res => res.text())
          .then(text => {
            console.log("RAW:", text);
            let data = JSON.parse(text);

            if (data.success) {
              if (btn && btn.closest('tr')) {
                btn.closest('tr').remove();
              }
              showToast('✅ ลบเรียบร้อยแล้ว', 'success');

              if (typeof paginateRows === 'function') paginateRows();
              try {
                if (typeof filterRows === 'function') filterRows(false);
              } catch (e) {
                console.warn("filterRows error:", e);
              }


            } else {
              showToast(`❌ ลบไม่สำเร็จ: ${data.error || 'ไม่ทราบสาเหตุ'}`, 'danger');
            }
          })
          .catch(err => {
            console.error('Fetch/JS error:', err);
            showToast('❌ ไม่สามารถเชื่อมต่อ server', 'danger');
          });

      }

      // 🗑️ ลบ Table
      function deleteTableOnly(queryName, btn) {
        if (!confirm(`⚠️ ต้องการลบเฉพาะ Table: ${queryName} ?`)) return;

        fetch('delete_table_only.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `query_name=${encodeURIComponent(queryName)}`
        })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              showToast(`✅ ลบ Table ${queryName} สำเร็จ`, 'success');
            } else {
              showToast(`❌ ลบ Table ไม่สำเร็จ: ${data.error}`, 'danger');
            }
          })
          .catch(err => {
            console.error(err);
            showToast('❌ ไม่สามารถเชื่อมต่อ server', 'danger');
          });
      }


      // 🔔 Toast ทั่วไป
      function showToast(message, type = 'success') {
        const toastEl = document.getElementById('toastMessage');
        const toastBody = document.getElementById('toastBody');
        toastEl.classList.remove('bg-success', 'bg-danger', 'bg-info');
        toastEl.classList.add(`bg-${type}`);
        toastBody.textContent = message;
        new bootstrap.Toast(toastEl).show();
      }
    </script>

    <script>
      const hosCode = "<?= $hosCode ?>";
      const apiKey = "<?= $apiKey ?>";
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Cookie Consent (วางใน body เพื่อความถูกต้องของ DOM) -->
    <script src="https://cdn.jsdelivr.net/npm/cookieconsent@3/build/cookieconsent.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cookieconsent@3/build/cookieconsent.min.css" />
    <script>
      window.addEventListener("load", function () {
        window.cookieconsent.initialise({
          palette: { popup: { background: "#2e2e2e" }, button: { background: "#f1d600", text: "#000" } },
          theme: "classic",
          type: "opt-in",
          content: {
            message: "เว็บไซต์นี้ใช้ cookies เพื่อปรับปรุงประสบการณ์ของคุณ",
            allow: "ตกลง",
            deny: "ปฏิเสธ",
            link: "อ่านเพิ่มเติม",
            href: "/scrp/privacy-policy.php"
          },
          onInitialise: function (status) {
            const didConsent = this.hasConsented();
            console.log(didConsent ? "✅ ผู้ใช้ยินยอม cookies" : "🚫 ผู้ใช้ปฏิเสธ cookies");
            // ถ้าต้องใช้ analytics ที่ต้องการ consent ให้เริ่มที่นี่เมื่อ didConsent === true
          }
        });
      });
    </script>

    <footer class="hos-footer text-center">
      Developed by <strong>นายสาธิต รินคำ</strong> นักวิชาการคอมพิวเตอร์ กลุ่มงานสุขภาพดิจิตอล โรงพยาบาลห้างฉัตร
      · Coder Copilot · เครดิต YuiCity / Vorabodin สสจ.ชม · <?= date('Y') ?>
    </footer>

  </div> <!-- .container -->
</body>

</html>