<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';
$env = [];
foreach (file("$root/.env") as $line) {
    $line = trim($line);
    if (!$line || $line[0] === '#' || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, '"\'');
}
$pdo = new PDO(
    "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
    $env['DB_USERNAME'], $env['DB_PASSWORD']
);

// JS-ээр DOM бэлэн болсны дараа style inject хийх — хамгийн найдвартай арга
$jsStyle = '<script id="dona-image-fix-js">
(function(){
  var css = [
    ".product-wrap .image{width:100%!important;height:100px!important;padding-top:0!important;position:relative!important;overflow:hidden!important;background:#f8f8f8!important;}",
    ".product-wrap .image img,.product-wrap .image .lazyload,.product-wrap .image .lazyloaded{position:absolute!important;top:0!important;left:0!important;width:100%!important;height:100%!important;object-fit:cover!important;object-position:center!important;}",
    ".product-wrap .image .button-wrap{position:absolute!important;bottom:0!important;left:0!important;right:0!important;width:100%!important;z-index:10!important;}"
  ].join("");
  function inject(){
    var old = document.getElementById("dona-img-style");
    if(old) old.remove();
    var s = document.createElement("style");
    s.id = "dona-img-style";
    s.textContent = css;
    document.head.appendChild(s);
  }
  if(document.readyState === "loading"){
    document.addEventListener("DOMContentLoaded", inject);
  } else {
    inject();
  }
  // Vue router дахин render хийхэд мөн ажиллуулах
  window.addEventListener("load", inject);
})();
</script>';

$row = $pdo->query("SELECT id, value FROM settings WHERE space='base' AND name='head_code' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Хуучин image fix-үүдийг цэвэрлэх
$current = preg_replace('/<style id="dona-image-fix">[\s\S]*?<\/style>\n?/', '', $row['value']);
$current = preg_replace('/<script id="dona-image-fix-js">[\s\S]*?<\/script>\n?/', '', $current);
$newValue = trim($current) . "\n" . $jsStyle;

$pdo->prepare("UPDATE settings SET value=? WHERE id=?")->execute([$newValue, $row['id']]);

$cleared = 0;
foreach (glob("$root/storage/framework/views/*.php") as $f) { if(@unlink($f)) $cleared++; }
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }

header('Content-Type: application/json');
echo json_encode(['done' => true, 'cache_cleared' => $cleared, 'method' => 'JS inject after DOMContentLoaded'], JSON_PRETTY_PRINT);
