<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';
$dirs = ['app/Http/Middleware', 'app/Http', 'app', 'beike', 'config', 'routes', 'bootstrap', 'resources'];

$results = [];
foreach ($dirs as $dir) {
    $path = "$root/$dir";
    $results[$dir] = [
        'exists'   => is_dir($path),
        'writable' => is_writable($path),
        'owner'    => function_exists('posix_getpwuid') ? (posix_getpwuid(fileowner($path))['name'] ?? '?') : decoct(fileperms($path) & 0777),
    ];
}

// Also check specific file
$file = "$root/app/Http/Middleware/SetLocaleShopApi.php";
$results['file:SetLocaleShopApi'] = [
    'exists'   => file_exists($file),
    'writable' => is_writable($file),
    'owner'    => decoct(fileperms($file) & 0777),
];

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);
