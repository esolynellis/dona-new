<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';
$results = [];

// Clear compiled Blade views
$viewPath = "$root/storage/framework/views";
$files = glob("$viewPath/*.php");
$count = 0;
foreach ($files as $f) {
    if (@unlink($f)) $count++;
}
$results['views_cleared'] = $count;

// Clear bootstrap cache
$bsFiles = glob("$root/bootstrap/cache/*.php");
foreach ($bsFiles as $f) { @unlink($f); }
$results['bootstrap_cache_cleared'] = count($bsFiles);

// Clear config/route cache files
foreach (['config.php','routes.php','packages.php','services.php'] as $cf) {
    $p = "$root/bootstrap/cache/$cf";
    if (file_exists($p)) { @unlink($p); $results["deleted_$cf"] = true; }
}

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
