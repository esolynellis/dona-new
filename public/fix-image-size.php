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

// First: check what CSS rules exist for image-old
$appCss = file_get_contents("$root/public/build/beike/shop/default/css/app.css");
preg_match_all('/[^{}]*image-old[^{]*\{[^}]+\}/i', $appCss, $m);
$imageOldRules = $m[0];

// Also find the actual CSS class on the img container via app.css
preg_match_all('/[^{}]*\.image[^{]*\{[^}]+\}/i', $appCss, $m2);
$imageRules = array_slice($m2[0], 0, 20);

header('Content-Type: application/json');
echo json_encode([
    'image_old_css_rules' => $imageOldRules,
    'image_css_rules'     => $imageRules,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
