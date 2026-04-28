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

$stmt = $pdo->prepare("SELECT id, value FROM settings WHERE space='base' AND name='footer_setting' LIMIT 1");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) { echo json_encode(['error' => 'not found']); exit; }

$data = json_decode($row['value'], true);
$replaced = 0;

foreach (['link1','link2','link3'] as $linkKey) {
    if (!isset($data['content'][$linkKey]['links'])) continue;
    foreach ($data['content'][$linkKey]['links'] as &$item) {
        if (($item['type'] ?? '') === 'custom' && isset($item['value']) && strpos($item['value'], '/app/') !== false) {
            $item['value'] = 'https://www.dona-trade.com/app/';
            $replaced++;
        }
    }
    unset($item);
}

$newValue = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$upd = $pdo->prepare("UPDATE settings SET value=? WHERE id=?");
$upd->execute([$newValue, $row['id']]);

foreach (glob("$root/storage/framework/views/*.php") as $f) { @unlink($f); }
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }

header('Content-Type: application/json');
echo json_encode(['replaced' => $replaced, 'done' => true], JSON_PRETTY_PRINT);
