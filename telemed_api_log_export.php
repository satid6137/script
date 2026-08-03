<?php
require __DIR__ . '/config.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=telemed_log.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['log_date', 'hos_code', 'report_date', 'status', 'message']);

$logs = $conn->query("SELECT * FROM telemed_log ORDER BY id DESC");

while ($l = $logs->fetch_assoc()) {
    fputcsv($output, $l);
}

fclose($output);
exit;
