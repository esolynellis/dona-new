<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';
$results = [];

// 1. Check Redis
$results['redis_extension'] = extension_loaded('redis') ? 'YES' : 'NO';
$results['redis_connect'] = 'NO';
try {
    $redis = new Redis();
    if ($redis->connect('127.0.0.1', 6379, 2)) {
        $results['redis_connect'] = 'YES';
        $redis->set('dona_test', 'ok', 5);
        $results['redis_test'] = $redis->get('dona_test');
    }
} catch (Exception $e) {
    $results['redis_error'] = $e->getMessage();
}

// 2. Read .env cache driver
$env = [];
foreach (file("$root/.env") as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, '"\'');
}
$results['current_cache_driver'] = $env['CACHE_DRIVER'] ?? 'not set';
$results['current_session_driver'] = $env['SESSION_DRIVER'] ?? 'not set';

// 3. Check nginx config location
$nginxConfs = glob('/www/server/panel/vhost/nginx/*.conf');
$results['nginx_conf_count'] = count($nginxConfs);
$results['nginx_confs'] = $nginxConfs;

// 4. Check if gzip already enabled
$donaNginxConf = '';
foreach ($nginxConfs as $conf) {
    if (strpos($conf, 'dona') !== false || strpos(file_get_contents($conf), 'dona-trade.com') !== false) {
        $donaNginxConf = $conf;
        break;
    }
}
$results['dona_nginx_conf'] = $donaNginxConf;
if ($donaNginxConf) {
    $confContent = file_get_contents($donaNginxConf);
    $results['gzip_enabled'] = strpos($confContent, 'gzip on') !== false ? 'YES' : 'NO';
    $results['expires_set'] = strpos($confContent, 'expires') !== false ? 'YES' : 'NO';
}

// 5. Check opcache
$results['opcache'] = extension_loaded('Zend OPcache') ? 'enabled' : 'disabled';
$results['opcache_status'] = function_exists('opcache_get_status') ? (opcache_get_status(false)['opcache_enabled'] ?? false) ? 'ON' : 'OFF' : 'N/A';

// 6. PHP info
$results['php_version'] = PHP_VERSION;
$results['memory_limit'] = ini_get('memory_limit');

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
