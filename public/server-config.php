<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$results = [];

// List nginx conf directory
$nginxDir = '/www/server/nginx/conf';
$results['nginx_conf_files'] = glob("$nginxDir/*.conf");
$results['nginx_conf_dirs'] = glob("$nginxDir/*/", GLOB_ONLYDIR);

// Read main nginx.conf - find include statements
$mainConf = "$nginxDir/nginx.conf";
if (file_exists($mainConf)) {
    $content = file_get_contents($mainConf);
    preg_match_all('/include\s+([^;]+);/', $content, $matches);
    $results['nginx_includes'] = $matches[1] ?? [];
}

// Check vhost dirs
$vhostPaths = [
    '/www/server/panel/vhost/nginx',
    '/www/server/nginx/conf/vhost',
    '/www/server/nginx/conf/conf.d',
];
foreach ($vhostPaths as $p) {
    $results['dir_' . basename($p)] = is_dir($p) ? glob("$p/*.conf") : 'NOT EXISTS';
}

// Check php.ini for opcache
$phpini = '/www/server/php/81/etc/php.ini';
$iniContent = file_get_contents($phpini);
$results['opcache_in_ini'] = preg_match('/opcache\.enable\s*=\s*1/', $iniContent) ? 'enabled' : 'disabled';
$results['php_ini_writable'] = is_writable($phpini);

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
