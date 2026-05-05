<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$results = [];

// Search for nginx conf files mentioning dona-trade.com
$searchDirs = [
    '/www/server/nginx/conf',
    '/www/server/panel/vhost/nginx',
    '/etc/nginx',
    '/etc/nginx/sites-enabled',
    '/etc/nginx/conf.d',
    '/usr/local/nginx/conf',
    '/www/server/nginx/conf/vhost',
];

foreach ($searchDirs as $dir) {
    if (!is_dir($dir)) { $results['dir_' . basename($dir)] = 'not_found'; continue; }
    $files = @scandir($dir);
    if ($files === false) { $results['dir_' . basename($dir)] = 'unreadable'; continue; }
    $results['dir_' . $dir] = $files;
}

// Try glob patterns
$patterns = [
    '/www/server/nginx/conf/vhost/*.conf',
    '/www/server/panel/vhost/nginx/*.conf',
    '/etc/nginx/sites-enabled/*',
    '/etc/nginx/conf.d/*.conf',
    '/www/server/nginx/conf/*.conf',
];

foreach ($patterns as $pattern) {
    $found = glob($pattern);
    if ($found) {
        foreach ($found as $f) {
            $content = @file_get_contents($f);
            if ($content && (strpos($content, 'dona') !== false || strpos($content, 'dona-trade') !== false)) {
                $results['MATCH'] = $f;
                $results['MATCH_CONTENT'] = substr($content, 0, 3000);
            }
        }
        $results['glob_' . $pattern] = $found ?: 'none';
    }
}

// Try bt panel api or config
$btConf = @file_get_contents('/www/server/panel/config/config.json');
if ($btConf) $results['bt_config'] = substr($btConf, 0, 500);

// Try reading known vhost include paths from main nginx.conf
$mainConf = @file_get_contents('/www/server/nginx/conf/nginx.conf');
if ($mainConf) {
    preg_match_all('/include\s+([^\s;]+)/m', $mainConf, $m);
    $results['nginx_includes'] = $m[1] ?? [];
    // Try to find dona-related entries
    if (preg_match('/server_name[^;]*dona[^;]*;/', $mainConf, $sm)) {
        $results['nginx_dona_server'] = $sm[0];
    }
}

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
