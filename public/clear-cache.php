<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }
header('Content-Type: text/plain; charset=utf-8');
$root = '/www/wwwroot/dona-new';
$dirs = ["$root/storage/framework/cache","$root/storage/framework/views","$root/storage/framework/sessions","$root/bootstrap/cache"];
$total = 0;
foreach ($dirs as $dir) {
    if (!is_dir($dir)) { echo "skip: $dir\n"; continue; }
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    $count = 0;
    foreach ($iter as $file) {
        if ($file->isFile() && !str_ends_with($file->getFilename(), '.gitignore')) { @unlink($file->getPathname()); $count++; }
    }
    echo "cleared $count from: $dir\n";
    $total += $count;
}
echo "\nTotal: $total files cleared. Refresh site!\n";
