<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$results = [];

// Find nginx configs
$searchPaths = [
    '/www/server/nginx/conf/vhost/*.conf',
    '/www/server/panel/vhost/nginx/*.conf',
    '/etc/nginx/sites-enabled/*',
    '/etc/nginx/conf.d/*.conf',
    '/usr/local/nginx/conf/vhost/*.conf',
    '/www/server/nginx/conf/*.conf',
];
foreach ($searchPaths as $pattern) {
    $files = glob($pattern);
    if ($files) {
        $results['nginx_found_at'] = $pattern;
        foreach ($files as $f) {
            $content = file_get_contents($f);
            if (stripos($content, 'dona') !== false || stripos($content, '103.168') !== false) {
                $results['dona_conf_path'] = $f;
                $results['gzip_on'] = strpos($content, 'gzip on') !== false;
                $results['expires'] = strpos($content, 'expires') !== false;
                $results['conf_preview'] = substr($content, 0, 800);
                break;
            }
        }
        break;
    }
}

// Find php.ini
$phpini = php_ini_loaded_file();
$results['php_ini'] = $phpini;
$results['opcache_ini'] = ini_get('opcache.enable');
$results['opcache_writable'] = is_writable($phpini);

// Check BT panel config dir
$results['bt_nginx_dir'] = is_dir('/www/server/nginx') ? 'EXISTS' : 'NO';
$results['bt_panel_dir'] = is_dir('/www/server/panel') ? 'EXISTS' : 'NO';

// Memory & limits
$results['upload_max'] = ini_get('upload_max_filesize');
$results['post_max'] = ini_get('post_max_size');

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
