<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';

// Search for QPay in all PHP/blade files on server
$results = [];
$dirs = ["$root/beike", "$root/themes", "$root/plugins", "$root/app"];
foreach ($dirs as $dir) {
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($iter as $file) {
        if (!in_array($file->getExtension(), ['php', 'vue', 'js'])) continue;
        $content = file_get_contents($file->getPathname());
        if (stripos($content, 'qpay') !== false) {
            $results[] = str_replace($root.'/', '', $file->getPathname());
        }
    }
}

header('Content-Type: application/json');
echo json_encode(['qpay_files' => $results], JSON_PRETTY_PRINT);
