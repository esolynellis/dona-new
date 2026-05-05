<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$results = [];
if (function_exists('opcache_reset')) {
    $results['opcache_reset'] = opcache_reset();
} else {
    $results['opcache_available'] = false;
}

// Also try to invalidate specific files
$root = '/www/wwwroot/dona-new';
$files = [
    "$root/beike/Shop/Http/Resources/ProductSimple.php",
    "$root/beike/Repositories/ProductRepo.php",
    "$root/beike/ShopAPI/Controllers/ProductController.php",
];
foreach ($files as $f) {
    if (function_exists('opcache_invalidate')) {
        $results['invalidated'][$f] = opcache_invalidate($f, true);
    }
}

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);
