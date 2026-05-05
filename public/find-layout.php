<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';
$results = [];

// Search recursively for blade files containing <head>
function findBladeWithHead($dir, $root, &$results, $depth = 0) {
    if ($depth > 4) return;
    $files = @scandir($dir);
    if (!$files) return;
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        $path = "$dir/$f";
        if (is_dir($path)) {
            findBladeWithHead($path, $root, $results, $depth + 1);
        } elseif (str_ends_with($f, '.blade.php')) {
            $content = @file_get_contents($path);
            if ($content && strpos($content, '<head') !== false) {
                $rel = str_replace($root . '/', '', $path);
                $results['found'][] = $rel;
                if (!isset($results['first_content'])) {
                    $results['first_content'] = substr($content, 0, 3000);
                    $results['first_file'] = $rel;
                }
            }
        }
    }
}

// Try accessible theme dirs
$searchDirs = [
    "$root/beike/Shop/View",
    "$root/beike/Admin/View",
    "$root/themes/default",
    "$root/resources",
    "$root/beike/Install",
];

foreach ($searchDirs as $dir) {
    if (is_dir($dir)) findBladeWithHead($dir, $root, $results);
}

// Also try to read specific known files
$known = [
    "$root/beike/Shop/View/app.blade.php",
    "$root/beike/Shop/View/Components/app.blade.php",
    "$root/beike/Shop/View/index.blade.php",
];
foreach ($known as $f) {
    $c = @file_get_contents($f);
    if ($c !== false) {
        $results['known_' . basename($f)] = substr($c, 0, 2000);
    }
}

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
