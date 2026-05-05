<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$results = [];
$vhostDir = '/www/server/panel/vhost/nginx';

// 1. List vhost directory
$handle = @opendir($vhostDir);
$vhostFiles = [];
if ($handle) {
    while (($f = readdir($handle)) !== false) {
        if ($f !== '.' && $f !== '..') $vhostFiles[] = $f;
    }
    closedir($handle);
}
$results['vhost_files'] = $vhostFiles;

// 2. Find dona conf
$donaConf = '';
foreach ($vhostFiles as $f) {
    $path = "$vhostDir/$f";
    $c = @file_get_contents($path);
    if ($c && (stripos($c, 'dona') !== false || stripos($c, 'dona-trade') !== false)) {
        $donaConf = $path;
        $results['dona_conf'] = $path;
        $results['dona_conf_preview'] = substr($c, 0, 1000);
        $results['gzip_already'] = strpos($c, 'gzip on') !== false;
        $results['expires_already'] = strpos($c, 'expires') !== false;
        break;
    }
}

if (!$donaConf) {
    $results['error'] = 'dona conf not found';
}

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
