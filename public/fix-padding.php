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

$style = '<style id="dona-padding-fix">
@media(max-width:576px){
  /* Container хажуугийн padding багасгах */
  .container, .container-fluid{padding-left:6px!important;padding-right:6px!important;}
  /* Row margin арилгах */
  .row{margin-left:-4px!important;margin-right:-4px!important;}
  /* Col хажуугийн padding багасгах */
  .row>[class*="col"]{padding-left:4px!important;padding-right:4px!important;}
  /* Module section padding */
  .module-item .container,.module-info .container{padding-left:6px!important;padding-right:6px!important;}
  /* Product wrap margin */
  .product-wrap{margin-bottom:8px!important;}
}
</style>';

$row = $pdo->query("SELECT id, value FROM settings WHERE space='base' AND name='head_code' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$current = preg_replace('/<style id="dona-padding-fix">[\s\S]*?<\/style>\n?/', '', $row['value']);
$newValue = trim($current) . "\n" . $style;
$pdo->prepare("UPDATE settings SET value=? WHERE id=?")->execute([$newValue, $row['id']]);

foreach (glob("$root/storage/framework/views/*.php") as $f) { @unlink($f); }
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }

echo json_encode(['done'=>true,'fix'=>'mobile side padding reduced','ts'=>time()]);
