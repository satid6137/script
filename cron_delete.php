<?php
require __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?timeout=1");
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM cron_profiles WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: cron_profiles.php");
exit;
