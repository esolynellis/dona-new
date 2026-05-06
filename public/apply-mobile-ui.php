<?php
set_exception_handler(null); restore_exception_handler();
ob_start();
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';
$env  = [];
foreach (file("$root/.env") as $ln) {
    $ln = trim($ln);
    if (!$ln || $ln[0] === '#' || !str_contains($ln, '=')) continue;
    [$k, $v] = explode('=', $ln, 2);
    $env[trim($k)] = trim($v, '"\'');
}
$pdo = new PDO(
    "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']}",
    $env['DB_USERNAME'], $env['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

$tag = '<script src="/dona-mobile.js?v=1" defer></script>';

$row = $pdo->query("SELECT value FROM settings WHERE space='base' AND name='head_code' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$current = $row['value'] ?? '';

if (strpos($current, 'dona-mobile.js') !== false) {
    $current = preg_replace('/dona-mobile\.js\?v=\d+/', 'dona-mobile.js?v=1', $current);
    $msg = 'updated version';
} else {
    $current = trim($current) . "\n" . $tag;
    $msg = 'injected';
}

$stmt = $pdo->prepare("UPDATE settings SET value=? WHERE space='base' AND name='head_code'");
$stmt->execute([$current]);

ob_clean();
header('Content-Type: application/json');
echo json_encode(['status' => $msg, 'rows' => $stmt->rowCount()], JSON_PRETTY_PRINT);
