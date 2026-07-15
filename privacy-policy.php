<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>นโยบายความเป็นส่วนตัว | <?= $hospital ?></title>
  <link rel="icon" href="/script/assets/icons/health48.png" type="image/png">
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

  <div class="container pb-5" style="max-width: 760px;">
    <div class="hos-card">
      <h1 class="hos-page-title mb-4">นโยบายความเป็นส่วนตัว</h1>

      <p>เว็บไซต์นี้ให้ความสำคัญกับความเป็นส่วนตัวของผู้ใช้งาน และใช้ cookie
        เพื่อปรับปรุงประสบการณ์การใช้งานของคุณให้ดียิ่งขึ้น</p>

      <h5 class="mt-4">การใช้ Cookies</h5>
      <ul>
        <li>เว็บไซต์นี้ใช้ cookies เฉพาะเพื่อวัตถุประสงค์ด้านความปลอดภัยและการใช้งานพื้นฐานของระบบ</li>
        <li>เราไม่เก็บข้อมูลระบุตัวบุคคล หรือพฤติกรรมของผู้ใช้งาน</li>
        <li>ผู้ใช้งานสามารถเลือกปฏิเสธการใช้ cookies ได้จากป็อปอัปแจ้งเตือน หรือจากการตั้งค่าเบราว์เซอร์ของตนเอง</li>
      </ul>

      <h5 class="mt-4">การส่งข้อมูล</h5>
      <p>หากในอนาคตเว็บไซต์มีการเก็บข้อมูลเพิ่มเติม เช่น แบบฟอร์ม หรือลงทะเบียน
        จะมีการแจ้งเงื่อนไขใหม่ให้ผู้ใช้ทราบก่อนทุกครั้ง</p>

      <h5 class="mt-4">การปรับปรุง</h5>
      <p>เราอาจปรับปรุงนโยบายนี้เป็นระยะ หากมีการเปลี่ยนแปลงที่สำคัญจะแจ้งให้ทราบในหน้านี้</p>

      <hr>
      <p class="text-muted small mb-0">เวอร์ชันล่าสุด: <?= date('Y-m-d') ?></p>
    </div>
  </div>

</body>

</html>