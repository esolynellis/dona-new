<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';

// Read shared/product.blade.php - the product card template
$blade = @file_get_contents("$root/themes/default/shared/product.blade.php");

// Also check other product blade files
$files = [
    'shared/product.blade.php'       => "$root/themes/default/shared/product.blade.php",
    'shared/goods.blade.php'         => "$root/themes/default/shared/goods.blade.php",
    'shared/product_item.blade.php'  => "$root/themes/default/shared/product_item.blade.php",
    'components/product.blade.php'   => "$root/themes/default/components/product.blade.php",
];

$results = [];
foreach ($files as $name => $path) {
    $c = @file_get_contents($path);
    if ($c !== false) {
        $results[$name] = $c;
    }
}

// Also scan shared/ dir
$sharedDir = "$root/themes/default/shared";
$sharedFiles = @scandir($sharedDir);
$results['shared_dir_files'] = $sharedFiles ?: 'unreadable';

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
