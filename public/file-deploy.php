<?php
// File-based deploy: fetch files from GitHub raw and write to server
$token = 'file-deploy-dona2025';
if (($_GET['token'] ?? '') !== $token) {
    die('Unauthorized');
}

$repo   = 'esolynellis/dona-new';
$branch = 'master';
$base   = "https://raw.githubusercontent.com/{$repo}/{$branch}";

$files = [
    'public/build/beike/shop/default/css/app.css',
];

$results = [];
foreach ($files as $file) {
    $url     = $base . '/' . $file;
    $content = @file_get_contents($url);
    if ($content === false) {
        $results[] = "FAIL fetch: $file";
        continue;
    }
    $dest = __DIR__ . '/../' . $file;
    // For files inside public/ dir, __DIR__ IS public/
    if (str_starts_with($file, 'public/')) {
        $dest = __DIR__ . '/' . substr($file, strlen('public/'));
    }
    $dir = dirname($dest);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (file_put_contents($dest, $content) !== false) {
        $results[] = "OK: $file";
    } else {
        $results[] = "FAIL write: $file";
    }
}

echo implode("\n", $results) . "\n";
echo "Done.\n";
