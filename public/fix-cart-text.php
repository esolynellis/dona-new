<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';
$results = [];

function searchAndReplace($dir, $find, $replace, $root, &$results, $depth = 0) {
    if ($depth > 6) return;
    $files = @scandir($dir);
    if (!$files) return;
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        $path = "$dir/$f";
        if (is_dir($path)) {
            searchAndReplace($path, $find, $replace, $root, $results, $depth + 1);
        } elseif (preg_match('/\.(php|json|blade\.php)$/', $f)) {
            $c = @file_get_contents($path);
            if ($c && mb_stripos($c, $find) !== false) {
                $rel = str_replace("$root/", '', $path);
                $count = mb_substr_count(mb_strtolower($c), mb_strtolower($find));
                $new = str_ireplace($find, $replace, $c);
                @unlink($path);
                file_put_contents($path, $new);
                $results['replaced'][] = ['file' => $rel, 'count' => $count];
            }
        }
    }
}

// Хэл файлууд болон theme файлуудад "Савт нэмэх" -> "Сагсанд нэмэх" болгох
$searchDirs = [
    "$root/lang",
    "$root/resources/lang",
    "$root/beike/lang",
    "$root/themes/default",
    "$root/plugins",
];

foreach ($searchDirs as $dir) {
    if (is_dir($dir)) {
        searchAndReplace($dir, 'Савт нэмэх', 'Сагсанд нэмэх', $root, $results);
        searchAndReplace($dir, 'савт нэмэх', 'сагсанд нэмэх', $root, $results);
    }
}

// View cache цэвэрлэх
$cleared = 0;
foreach (glob("$root/storage/framework/views/*.php") as $f) { if(@unlink($f)) $cleared++; }
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }
foreach (glob("$root/storage/framework/cache/data/*/*") as $f) { if(is_file($f)) @unlink($f); }

$results['cache_cleared'] = $cleared;

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
