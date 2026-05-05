<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$results = [];

// BT Panel rewrite directory
$rewriteDir = '/www/server/panel/vhost/rewrite';
$rewriteFile = "$rewriteDir/dona-trade.com.conf";

// Check if dir exists and is writable
$results['rewrite_dir_exists'] = is_dir($rewriteDir);
$results['rewrite_dir_readable'] = is_readable($rewriteDir);
$results['rewrite_dir_writable'] = is_writable($rewriteDir);

// Try to read existing rewrite file
$existing = @file_get_contents($rewriteFile);
if ($existing !== false) {
    $results['existing_rewrite'] = $existing;
}

// Check other possible locations
$others = [
    '/www/server/panel/vhost/nginx_proxy/dona-trade.com.conf',
    '/www/server/panel/vhost/site/dona-trade.com.conf',
    '/www/server/nginx/conf/vhost/dona-trade.com.conf',
];
foreach ($others as $p) {
    if (file_exists($p)) {
        $results['found_' . basename($p)] = file_get_contents($p);
    }
}

// Try writing browser cache nginx snippet to rewrite dir
if (is_writable($rewriteDir)) {
    $cacheConf = 'location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff2?|ttf|webp)$ {
    expires 30d;
    add_header Cache-Control "public, no-transform, immutable";
    access_log off;
}
';
    $wrote = @file_put_contents($rewriteFile, $cacheConf);
    $results['wrote_rewrite'] = $wrote !== false ? 'OK' : 'failed';
}

// Also check proxy conf dir
$proxyDir = '/www/server/panel/vhost/nginx_proxy';
$results['proxy_dir'] = is_dir($proxyDir) ? (@scandir($proxyDir) ?: 'unreadable') : 'not_found';

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
