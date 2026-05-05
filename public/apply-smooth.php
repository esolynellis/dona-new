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

// Build the head_code inject
$inject = '<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="dns-prefetch" href="//www.dona-trade.com">
<link rel="preload" href="/build/beike/shop/default/js/app.js" as="script">
<link rel="preload" href="/build/beike/shop/default/css/app.css" as="style">
<link rel="stylesheet" href="/smooth.css?v=3">
<div id="dona-loader"></div>
<script>
(function(){
  var l=document.getElementById("dona-loader");
  if(!l)return;
  // Show loader on navigation
  window.addEventListener("beforeunload",function(){l.className="active";});
  // Hide when done
  window.addEventListener("load",function(){l.className="done";setTimeout(function(){l.style.display="none";},600);});
  // Also watch for Vue router changes
  document.addEventListener("DOMContentLoaded",function(){
    var orig=window.history.pushState;
    window.history.pushState=function(){
      l.style.display="block"; l.className="active";
      orig.apply(this,arguments);
      setTimeout(function(){l.className="done";setTimeout(function(){l.style.display="none";},500);},300);
    };
  });
})();
</script>';

// Get current head_code
$row = $pdo->query("SELECT id, value FROM settings WHERE space='base' AND name='head_code' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if ($row) {
    // Remove old smooth.css injection if exists
    $current = preg_replace('/<link[^>]+smooth\.css[^>]*>\n?/', '', $row['value']);
    $current = preg_replace('/<link[^>]+preconnect[^>]*>\n?/i', '', $current);
    $current = preg_replace('/<link[^>]+dns-prefetch[^>]*>\n?/i', '', $current);
    $current = preg_replace('/<div id="dona-loader"[^>]*><\/div>\n?/', '', $current);
    $current = preg_replace('/<script>\s*\(function\(\)\{[\s\S]*?dona-loader[\s\S]*?\}\)\(\);\s*<\/script>\n?/', '', $current);
    $current = trim($current);

    $newValue = $current . "\n" . $inject;
    $pdo->prepare("UPDATE settings SET value=? WHERE id=?")->execute([$newValue, $row['id']]);
    $result = 'updated';
} else {
    $pdo->prepare("INSERT INTO settings (space, name, value) VALUES ('base','head_code',?)")->execute([$inject]);
    $result = 'inserted';
}

// Clear caches
$cleared = 0;
foreach (glob("$root/storage/framework/views/*.php") as $f) { if(@unlink($f)) $cleared++; }
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }

header('Content-Type: application/json');
echo json_encode([
    'done' => true,
    'head_code' => $result,
    'cache_cleared' => $cleared,
    'features' => ['smooth_css', 'preconnect_fonts', 'loading_bar', 'img_fadein', 'touch_fix']
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
