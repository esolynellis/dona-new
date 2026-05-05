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

$inlineStyle = '<style id="dona-cart-fix">
.product-wrap .image .button-wrap{bottom:0!important;opacity:1!important;visibility:visible!important;transform:none!important;}
.product-list-wrap .product-wrap .image .button-wrap{bottom:0!important;opacity:1!important;}
@media(max-width:992px){
  .product-wrap .image .button-wrap{display:flex!important;bottom:0!important;opacity:1!important;}
  .product-wrap .image .button-wrap .btn-add-cart{font-size:13px!important;padding:9px 8px!important;min-height:38px!important;}
  .product-wrap .image .button-wrap .btn-quick-view,.product-wrap .image .button-wrap .btn-wish{flex:0 0 38px!important;min-height:38px!important;}
}
</style>';

$row = $pdo->query("SELECT id, value FROM settings WHERE space='base' AND name='head_code' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Remove old inline cart fix if any
$current = preg_replace('/<style id="dona-cart-fix">[\s\S]*?<\/style>\n?/', '', $row['value']);
$newValue = trim($current) . "\n" . $inlineStyle;

$pdo->prepare("UPDATE settings SET value=? WHERE id=?")->execute([$newValue, $row['id']]);

// Clear all caches
$cleared = 0;
foreach (glob("$root/storage/framework/views/*.php") as $f) { if(@unlink($f)) $cleared++; }
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }

header('Content-Type: application/json');
echo json_encode(['done'=>true,'cache_cleared'=>$cleared], JSON_PRETTY_PRINT);
