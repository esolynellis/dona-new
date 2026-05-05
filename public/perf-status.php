<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';
$results = [];

// ── 1. OPcache status ──
$results['opcache'] = [
    'loaded' => extension_loaded('Zend OPcache') || extension_loaded('opcache'),
    'enabled' => function_exists('opcache_get_status') ? (opcache_get_status(false)['opcache_enabled'] ?? false) : false,
];
if (function_exists('opcache_get_status')) {
    $s = opcache_get_status(false);
    $results['opcache']['hit_rate'] = round($s['opcache_statistics']['opcache_hit_rate'] ?? 0, 2) . '%';
    $results['opcache']['hits'] = $s['opcache_statistics']['hits'] ?? 0;
    $results['opcache']['misses'] = $s['opcache_statistics']['misses'] ?? 0;
    $results['opcache']['cached_scripts'] = $s['opcache_statistics']['num_cached_scripts'] ?? 0;
    $results['opcache']['memory_used_mb'] = round(($s['memory_usage']['used_memory'] ?? 0) / 1024 / 1024, 1);
}

// ── 2. Cache driver ──
$envLines = @file("$root/.env") ?: [];
$env = [];
foreach ($envLines as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, '"\'');
}
$results['cache_driver'] = $env['CACHE_DRIVER'] ?? 'file';
$results['session_driver'] = $env['SESSION_DRIVER'] ?? 'file';

// ── 3. PHP version & memory ──
$results['php'] = [
    'version' => PHP_VERSION,
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
];

// ── 4. View cache files ──
$viewFiles = glob("$root/storage/framework/views/*.php");
$results['view_cache_count'] = count($viewFiles ?: []);

// ── 5. Laravel cache ping ──
$cacheTest = "$root/storage/framework/cache/data";
$results['cache_dir_writable'] = is_writable($cacheTest);

// ── 6. Active cached keys ──
$cacheFiles = glob("$root/storage/framework/cache/data/*/*");
$results['laravel_cache_files'] = count($cacheFiles ?: []);

// ── 7. Nginx vhost snippet (for BT Panel) ──
$results['nginx_snippet_for_btpanel'] = '
# Paste this into BT Panel → Website → dona-trade.com → Config → (add before last })

    # Browser caching for static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot|webp)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        add_header Vary "Accept-Encoding";
        access_log off;
    }

    # Short cache for JSON API
    location /api/ {
        add_header Cache-Control "no-store, no-cache, must-revalidate";
    }
';

// ── 8. Recommendation ──
$driver = $env['CACHE_DRIVER'] ?? 'file';
$opcacheOn = function_exists('opcache_get_status') && (opcache_get_status(false)['opcache_enabled'] ?? false);
$recommendations = [];
if ($driver === 'file') {
    $recommendations[] = 'Switch CACHE_DRIVER=redis in .env for 10x faster cache (requires Redis install)';
}
if (!$opcacheOn) {
    $recommendations[] = 'OPcache not active — enable in BT Panel → PHP → Extensions → opcache';
}
if (($env['APP_DEBUG'] ?? 'false') === 'true') {
    $recommendations[] = 'APP_DEBUG=true is ON — should be false in production!';
}
$results['recommendations'] = $recommendations ?: ['All good!'];

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
