<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$results = [];

// Search all possible locations
$searchDirs = [
    '/www/server/nginx/conf/vhost',
    '/www/server/panel/vhost/nginx',
    '/www/server/nginx/conf',
];

foreach ($searchDirs as $dir) {
    $handle = @opendir($dir);
    if (!$handle) { $results[$dir] = 'cannot open'; continue; }
    $files = [];
    while (($f = readdir($handle)) !== false) {
        if ($f === '.' || $f === '..') continue;
        $path = "$dir/$f";
        $c = @file_get_contents($path);
        if ($c && (stripos($c, 'dona') !== false || stripos($c, '103.168') !== false)) {
            $results['FOUND'] = $path;
            $results['gzip'] = strpos($c, 'gzip on') !== false ? 'YES' : 'NO';
            $results['expires'] = strpos($c, 'expires') !== false ? 'YES' : 'NO';
            $results['writable'] = is_writable($path) ? 'YES' : 'NO';
            $results['preview'] = substr($c, 0, 1500);
        }
        $files[] = $f;
    }
    closedir($handle);
    $results[$dir] = $files;
}

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
