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

if (isset($_GET['inspect'])) {
    $stmt = $pdo->prepare("SELECT id, space, name, value FROM settings WHERE space='base' AND name='footer_setting' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode(['row' => $row], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $pdo->prepare("SELECT id, value FROM settings WHERE space='base' AND name='footer_setting' LIMIT 1");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['error' => 'footer_setting not found']);
    exit;
}

$data = json_decode($row['value'], true);
$replaced = 0;

// Replace any URL containing gitbook.io in any link field
array_walk_recursive($data, function(&$val) use (&$replaced) {
    if (is_string($val) && strpos($val, 'gitbook.io') !== false) {
        $val = '/app/';
        $replaced++;
    }
});

$newValue = json_encode($data, JSON_UNESCAPED_UNICODE);
$upd = $pdo->prepare("UPDATE settings SET value=? WHERE id=?");
$upd->execute([$newValue, $row['id']]);

foreach (glob("$root/storage/framework/views/*.php") as $f) { @unlink($f); }
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }

header('Content-Type: application/json');
echo json_encode(['replaced' => $replaced, 'done' => true], JSON_PRETTY_PRINT);
