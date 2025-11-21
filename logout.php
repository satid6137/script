<?php
require 'config.php';
require_once 'log_helper.php';
#session_start();

if (isset($_SESSION['user_id'])) {
  logAction($conn, $_SESSION['user_id'], 'logout', null, 'ออกจากระบบ');
}

session_unset();      // เคลียร์ตัวแปร session
session_destroy();    // ทำลาย session

header("Location: login.php"); // หรือเปลี่ยนเป็น index.php ได้
exit;