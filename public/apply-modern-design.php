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
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$css = '<link rel="stylesheet" href="https://www.dona-trade.com/dona-modern.css?v='.time().'">';

$check = $pdo->query("SELECT id, value FROM settings WHERE space='base' AND name='head_code' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if ($check) {
    $current = $check['value'];
    // Remove old dona-modern link if exists
    $current = preg_replace('/<link[^>]+dona-modern[^>]+>/', '', $current);
    $newValue = trim($current) . "\n" . $css;
    $pdo->prepare("UPDATE settings SET value=? WHERE id=?")->execute([$newValue, $check['id']]);
} else {
    $pdo->prepare("INSERT INTO settings (type,space,name,value,json) VALUES ('base','base','head_code',?,0)")->execute([$css]);
}

foreach (glob("$root/storage/framework/views/*.php") as $f) { @unlink($f); }
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }

header('Content-Type: application/json');
echo json_encode(['done' => true, 'css_injected' => $css], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
