<?php

session_start();
$_SESSION['user_role'] = 'admin';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/helpers/helpers.php';
require_once __DIR__ . '/../app/services/reports/FinancialReportService.php';

$projectId = (int)($argv[1] ?? 1);
$userId = 1;

try {
    $service = new FinancialReportService();
    $csv = $service->exportCsv($projectId, $userId);
    echo 'CSV OK: ' . strlen($csv['content']) . ' bytes, file: ' . $csv['filename'] . PHP_EOL;

    $pdf = $service->exportPdf($projectId, $userId);
    echo 'PDF OK: ' . strlen($pdf['content']) . ' bytes, file: ' . $pdf['filename'] . PHP_EOL;
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit(1);
}
