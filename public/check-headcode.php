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

// Get all head_code related settings
$rows = $pdo->query("SELECT id, space, name, value FROM settings WHERE name LIKE '%head%' OR name LIKE '%code%' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

// Also get the exact head_code value
$hc = $pdo->query("SELECT value FROM settings WHERE space='base' AND name='head_code' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Read master.blade.php looking for isApp / app mode condition
$master = file_get_contents("/www/wwwroot/dona-new/themes/default/layout/master.blade.php");
// Extract the full head section
preg_match('/<head[^>]*>([\s\S]*?)<\/head>/i', $master, $m);

header('Content-Type: application/json');
echo json_encode([
    'all_head_settings' => $rows,
    'head_code_value'   => $hc['value'] ?? null,
    'master_head_raw'   => $m[1] ?? 'not found',
    'master_isapp_check'=> substr($master, max(0, strpos($master, 'isApp') ?: strpos($master, 'app_mode') ?: 0), 300),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
