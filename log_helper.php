<?php
// log_helper.php
function logAction($conn, $userId, $actionType, $target = null, $detail = null) {
  if (!$userId || !$actionType) return;

  $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action_type, target, detail) VALUES (?, ?, ?, ?)");
  $stmt->bind_param("isss", $userId, $actionType, $target, $detail);
  $stmt->execute();
  $stmt->close();
}
?>