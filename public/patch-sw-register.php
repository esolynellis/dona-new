<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';
$env = [];
foreach (file("$root/.env") as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, '"\'');
}
$pdo = new PDO(
    "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
    $env['DB_USERNAME'], $env['DB_PASSWORD']
);

$swScript = '<script>
if("serviceWorker"in navigator){
  window.addEventListener("load",function(){
    navigator.serviceWorker.register("/sw.js",{scope:"/"}).catch(function(){});
  });
}
</script>';

$row = $pdo->query("SELECT id, value FROM settings WHERE space='base' AND name='head_code' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if ($row && strpos($row['value'], 'serviceWorker') === false) {
    $newValue = trim($row['value']) . "\n" . $swScript;
    $pdo->prepare("UPDATE settings SET value=? WHERE id=?")->execute([$newValue, $row['id']]);
    $result = 'sw_registered';
} else {
    $result = 'already_present_or_no_row';
}

// Also find and patch the main blade layout to add <link rel="preload"> for JS chunks
// Check what JS/CSS files exist in public
$jsFiles = glob("$root/public/js/chunk-*.js");
$cssFiles = glob("$root/public/css/*.css");
$preloads = '';
foreach (array_slice($jsFiles ?? [], 0, 3) as $f) {
    $base = basename($f);
    $preloads .= '<link rel="modulepreload" href="/js/'.$base.'">' . "\n";
}

// Clear caches
$cleared = 0;
foreach (glob("$root/storage/framework/views/*.php") as $f) { if(@unlink($f)) $cleared++; }
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }

header('Content-Type: application/json');
echo json_encode([
    'sw' => $result,
    'js_chunks_found' => count($jsFiles ?? []),
    'css_files_found' => count($cssFiles ?? []),
    'cache_cleared' => $cleared,
], JSON_PRETTY_PRINT);
