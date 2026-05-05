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
$pdo = new PDO("mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4", $env['DB_USERNAME'], $env['DB_PASSWORD']);

// 1. huipi_goods болон huipi_brands бүтцийг шалгах
$hgCols  = $pdo->query("DESCRIBE huipi_goods")->fetchAll(PDO::FETCH_COLUMN, 0);
$hbCols  = $pdo->query("DESCRIBE huipi_brands")->fetchAll(PDO::FETCH_COLUMN, 0);
$hbSample= $pdo->query("SELECT * FROM huipi_brands LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$hgSample= $pdo->query("SELECT * FROM huipi_goods LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode([
    'huipi_goods_cols'   => $hgCols,
    'huipi_brands_cols'  => $hbCols,
    'huipi_brands_sample'=> $hbSample,
    'huipi_goods_sample' => $hgSample,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
