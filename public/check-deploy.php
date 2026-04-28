<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$middleware = file_get_contents(__DIR__ . '/../app/Http/Middleware/SetLocaleShopApi.php');
$deployed = strpos($middleware, 'Mongolia-only') !== false;

$logFile = __DIR__ . '/../storage/logs/auto-deploy.log';
$lastLog = '';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLog = implode('', array_slice($lines, -20));
}

header('Content-Type: application/json');
echo json_encode([
    'locale_fix_deployed' => $deployed,
    'last_deploy_log' => $lastLog,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
