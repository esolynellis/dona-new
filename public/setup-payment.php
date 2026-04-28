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
$results = [];

// 1. Enable l_offline
$pdo->prepare("UPDATE settings SET value='1' WHERE space='l_offline' AND name='status'")->execute();
$results['l_offline_enabled'] = true;

// 2. Disable QPay in plugins table
$qpay = $pdo->query("SELECT * FROM plugins WHERE code LIKE '%qpay%' OR code LIKE '%QPay%'")->fetchAll(PDO::FETCH_ASSOC);
$results['qpay_plugins'] = $qpay;
if ($qpay) {
    $pdo->query("UPDATE plugins SET status=0 WHERE code LIKE '%qpay%' OR code LIKE '%QPay%'");
    $results['qpay_disabled'] = true;
}

// Also disable in settings
$pdo->query("UPDATE settings SET value='0' WHERE space LIKE '%qpay%' AND name='status'");

// 3. Set bank account content for all locales
$bankInfo = "Банкны данс: MN720005005129104667\nЗахиалга өгсний дараа дансанд мөнгө шилжүүлж, гүйлгээний баримтыг илгээнэ үү.";

foreach (['mn', 'zh_cn', 'en', 'ru'] as $locale) {
    $existing = $pdo->prepare("SELECT id FROM offline_payment_config_descriptions WHERE locale=? LIMIT 1");
    $existing->execute([$locale]);
    $row = $existing->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $pdo->prepare("UPDATE offline_payment_config_descriptions SET content=? WHERE id=?")
            ->execute([$bankInfo, $row['id']]);
        $results['content_' . $locale] = 'updated';
    } else {
        $pdo->prepare("INSERT INTO offline_payment_config_descriptions (locale, content) VALUES (?, ?)")
            ->execute([$locale, $bankInfo]);
        $results['content_' . $locale] = 'inserted';
    }
}

// 4. Set name for l_offline in settings
$nameExists = $pdo->prepare("SELECT id FROM settings WHERE space='l_offline' AND name='name' LIMIT 1");
$nameExists->execute();
if ($nameExists->fetch()) {
    $pdo->prepare("UPDATE settings SET value='Банкны шилжүүлэг' WHERE space='l_offline' AND name='name'")->execute();
} else {
    $pdo->prepare("INSERT INTO settings (type,space,name,value,json) VALUES ('plugin','l_offline','name','Банкны шилжүүлэг',0)")->execute();
}
$results['name_set'] = true;

// Clear cache
foreach (glob("$root/storage/framework/views/*.php") as $f) { @unlink($f); }
foreach (glob("$root/bootstrap/cache/*.php") as $f) { @unlink($f); }

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
