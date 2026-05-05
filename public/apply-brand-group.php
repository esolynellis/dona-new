<?php
set_exception_handler(null);
set_error_handler(null);
restore_exception_handler();
restore_error_handler();
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

try {
    $pdo = new PDO(
        "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']}",
        $env['DB_USERNAME'], $env['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    die(json_encode(['error' => $e->getMessage()]));
}

// Get current head_code
$row = $pdo->query("SELECT value FROM settings WHERE space='base' AND name='head_code' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$current = $row['value'] ?? '';

$tag = '<script src="/brand-group.js?v=1" defer></script>';

if (str_contains($current, 'brand-group.js')) {
    // Already injected - update version
    $new = preg_replace('/brand-group\.js\?v=\d+/', 'brand-group.js?v=1', $current);
    $msg = 'updated version';
} else {
    $new = $current . "\n" . $tag;
    $msg = 'injected new';
}

$stmt = $pdo->prepare("UPDATE settings SET value=? WHERE space='base' AND name='head_code'");
$stmt->execute([$new]);

ob_clean();
header('Content-Type: application/json');
echo json_encode([
    'status' => $msg,
    'tag_added' => $tag,
    'affected_rows' => $stmt->rowCount(),
], JSON_PRETTY_PRINT);
