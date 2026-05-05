<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';
$results = [];

// Read shop layout files
$dirs = [
    "$root/beike/Shop/View",
    "$root/beike/themes/default/views",
    "$root/beike/themes/default/views/layouts",
    "$root/resources/views",
];

foreach ($dirs as $dir) {
    $files = @scandir($dir);
    $results['dir_' . str_replace($root.'/', '', $dir)] = $files ?: 'unreadable';
}

// Try reading the main shop layout
$candidates = [
    "$root/beike/themes/default/views/layouts/app.blade.php",
    "$root/beike/themes/default/views/app.blade.php",
    "$root/beike/Shop/View/layouts/app.blade.php",
    "$root/beike/themes/default/views/index.blade.php",
];
foreach ($candidates as $c) {
    $content = @file_get_contents($c);
    if ($content !== false) {
        $results['layout_file'] = str_replace($root.'/', '', $c);
        $results['layout_content'] = $content;
        break;
    }
}

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
