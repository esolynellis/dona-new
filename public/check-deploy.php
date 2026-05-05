<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

// Check if ProductSimple.php on server has brand_name
$resource = file_get_contents(__DIR__ . '/../beike/Shop/Http/Resources/ProductSimple.php');
$hasBrandName = str_contains($resource, "'brand_name'");

// Check ProductRepo has brand_name filter
$repo = file_get_contents(__DIR__ . '/../beike/Repositories/ProductRepo.php');
$hasBrandFilter = str_contains($repo, 'brand_name filter');

// Check last deploy log
$logFile = __DIR__ . '/../storage/logs/auto-deploy.log';
$lastLog = '';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLog = implode('', array_slice($lines, -10));
}

header('Content-Type: application/json');
echo json_encode([
    'resource_has_brand_name'  => $hasBrandName,
    'repo_has_brand_filter'    => $hasBrandFilter,
    'resource_modified'        => date('Y-m-d H:i:s', filemtime(__DIR__ . '/../beike/Shop/Http/Resources/ProductSimple.php')),
    'last_deploy_log'          => $lastLog,
    'full_log_lines'           => file_exists($logFile) ? count(file($logFile)) : 0,
    'recent_50'                => file_exists($logFile) ? implode('', array_slice(file($logFile), -50)) : '',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
