<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';
$results = ['replaced' => [], 'not_found' => []];

function replaceInDir($dir, $find, $replace, $root, &$results, $depth = 0) {
    if ($depth > 6) return;
    $files = @scandir($dir);
    if (!$files) return;
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        $path = "$dir/$f";
        if (is_dir($path)) {
            replaceInDir($path, $find, $replace, $root, $results, $depth + 1);
        } elseif (preg_match('/\.(php|json)$/', $f)) {
            $c = @file_get_contents($path);
            if ($c === false) continue;
            if (mb_strpos($c, $find) !== false) {
                $new = str_replace($find, $replace, $c);
                @unlink($path);
                file_put_contents($path, $new);
                $results['replaced'][] = str_replace("$root/", '', $path);
            }
        }
    }
}

$dirs = ["$root/lang", "$root/resources/lang", "$root/beike/lang", "$root/themes/default", "$root/plugins"];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        replaceInDir($dir, 'Савт нэмэх', 'Сагсанд нэмэх', $root, $results);
        replaceInDir($dir, 'савт нэмэх', 'сагсанд нэмэх', $root, $results);
    } else {
        $results['not_found'][] = $dir;
    }
}

$cleared = 0;
foreach (glob("$root/storage/framework/views/*.php") as $f) { if(@unlink($f)) $cleared++; }
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }
foreach (glob("$root/storage/framework/cache/data/*/*") as $f) { if(is_file($f)) @unlink($f); }

$results['cache_cleared'] = $cleared;
header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
