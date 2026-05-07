<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die('Forbidden'); }
header('Content-Type: text/plain; charset=utf-8');
$root = '/www/wwwroot/dona-new';
$env  = [];
foreach (file("$root/.env") as $ln) {
    $ln = trim($ln); if (!$ln || $ln[0]==='#' || !str_contains($ln,'=')) continue;
    [$k,$v] = explode('=',$ln,2); $env[trim($k)] = trim($v,'"\'');
}
$pdo = new PDO("mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
    $env['DB_USERNAME'],$env['DB_PASSWORD'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$pdo->exec("SET NAMES utf8mb4");

// Check products columns
echo "=== products columns ===\n";
foreach ($pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_ASSOC) as $c)
    echo "  {$c['Field']} ({$c['Type']})\n";

echo "\n=== huipi_goods columns ===\n";
try {
    foreach ($pdo->query("SHOW COLUMNS FROM huipi_goods")->fetchAll(PDO::FETCH_ASSOC) as $c)
        echo "  {$c['Field']} ({$c['Type']})\n";
} catch(Exception $e) { echo "  ERROR: ".$e->getMessage()."\n"; }

echo "\n=== products count ===\n";
echo "  " . $pdo->query("SELECT COUNT(*) FROM products WHERE deleted_at IS NULL")->fetchColumn() . "\n";

echo "\n=== huipi_goods count ===\n";
try {
    echo "  " . $pdo->query("SELECT COUNT(*) FROM huipi_goods WHERE status=1")->fetchColumn() . "\n";
} catch(Exception $e) { echo "  ERROR: ".$e->getMessage()."\n"; }
