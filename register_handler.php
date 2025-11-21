<?php
require 'config.php';

function isValidUsername($username) {
  return preg_match('/^[a-zA-Z]{5,}$/', $username);
}

function isValidPassword($password) {
  return preg_match('/^[a-zA-Z0-9]{9,}$/', $password);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username']);
  $password = trim($_POST['password']);
  $confirm  = trim($_POST['confirm']);
  $role     = $_POST['role'] ?? 'user'; // รับจาก admin หรือ default เป็น user

  if ($username && $password && $confirm) {
    if (!isValidUsername($username)) {
      $error = "ชื่อผู้ใช้ต้องเป็นภาษาอังกฤษล้วน และมากกว่า 4 ตัวอักษร";
    } elseif (!isValidPassword($password)) {
      $error = "รหัสผ่านต้องมากกว่า 8 ตัวอักษร และประกอบด้วยภาษาอังกฤษหรือตัวเลขเท่านั้น";
    } elseif ($password !== $confirm) {
      $error = "รหัสผ่านไม่ตรงกัน";
    } else {
      $hashed = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $conn->prepare("INSERT INTO user (username, password, role) VALUES (?, ?, ?)");
      if (!$stmt->bind_param("sss", $username, $hashed, $role) || !$stmt->execute()) {
        $error = "ชื่อผู้ใช้นี้มีอยู่แล้ว หรือไม่สามารถเพิ่มได้";
      } else {
        if (isset($_POST['from_admin'])) {
          $message = "✅ เพิ่มผู้ใช้ $username สำเร็จ";
        } else {
          echo "<script>alert('สมัครสมาชิกสำเร็จ!'); window.location='login.php';</script>";
          exit;
        }
      }
    }
  } else {
    $error = "กรุณากรอกข้อมูลให้ครบทุกช่อง";
  }
}
?>