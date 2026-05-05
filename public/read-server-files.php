<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }
$root = '/www/wwwroot/dona-new';

$f = $_GET['f'] ?? 'simple';
switch ($f) {
    case 'simple':
        readfile("$root/beike/Shop/Http/Resources/ProductSimple.php");
        break;
    case 'blade':
        readfile("$root/themes/default/shared/product.blade.php");
        break;
    case 'repo':
        // Just show the brand_id section of ProductRepo.php (lines around brand_id filter in getBuilderWyl)
        $lines = file("$root/beike/Repositories/ProductRepo.php");
        // Find lines containing brand_id near line 760
        $out = [];
        foreach ($lines as $i => $line) {
            if ($i >= 755 && $i <= 785) {
                $out[] = ($i+1) . ': ' . $line;
            }
        }
        echo implode('', $out);
        break;
}
