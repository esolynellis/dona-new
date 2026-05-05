<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }
$root = '/www/wwwroot/dona-new';
$results = [];

// ── 1. Patch ProductSimple.php – add brand_name to response ─────
$file1 = "$root/beike/Shop/Http/Resources/ProductSimple.php";
$src1  = file_get_contents($file1);
if (!str_contains($src1, "'brand_name'")) {
    $find    = "'cart_id'         => \$cart_id,";
    $replace = "'cart_id'         => \$cart_id,
            'brand_name'      => \$this->brand_name ?? '',";
    $new1 = str_replace($find, $replace, $src1);
    if ($new1 !== $src1 && file_put_contents($file1, $new1) !== false) {
        $results['ProductSimple'] = 'patched OK';
    } else {
        $results['ProductSimple'] = 'patch FAILED';
    }
} else {
    $results['ProductSimple'] = 'already has brand_name';
}

// ── 2. Patch ProductRepo.php – add brand_name filter to getBuilderWyl ─
$file2 = "$root/beike/Repositories/ProductRepo.php";
$src2  = file_get_contents($file2);
if (!str_contains($src2, 'brand_name filter')) {
    // Find the unique closing of the category filter in getBuilderWyl
    // The double blank line + } before brand_id is unique to getBuilderWyl
    $find2 = "            });\n\n\n        }\n\n        \$brandId = \$filters['brand_id'] ?? 0;\n        if (\$brandId) {\n            \$builder->where('brand_id', \$brandId);\n        }\n\n        \$productIds = \$filters['product_ids'] ?? [];";
    $replace2 = "            });\n\n\n        }\n\n        \$brandId = \$filters['brand_id'] ?? 0;\n        if (\$brandId) {\n            \$builder->where('brand_id', \$brandId);\n        }\n\n        // Filter by brand_name text (brand_name filter)\n        \$brandNameFilter = \$filters['brand_name'] ?? '';\n        if (\$brandNameFilter) {\n            \$builder->where('products.brand_name', \$brandNameFilter);\n        }\n\n        \$productIds = \$filters['product_ids'] ?? [];";
    $new2 = str_replace($find2, $replace2, $src2);
    if ($new2 !== $src2 && file_put_contents($file2, $new2) !== false) {
        $results['ProductRepo'] = 'patched OK';
    } else {
        // Try a simpler find
        $find2b = "        \$brandId = \$filters['brand_id'] ?? 0;\n        if (\$brandId) {\n            \$builder->where('brand_id', \$brandId);\n        }\n\n        \$productIds = \$filters['product_ids'] ?? [];\n        if (\$productIds) {\n            \$builder->whereIn('products.id', \$productIds);\n            \$productIds = implode(',', \$productIds);\n            \$builder->orderByRaw(\"FIELD(products.id, {\$productIds})\");\n        }\n\n        // attr";
        $ct2b = substr_count($src2, $find2b);
        $results['ProductRepo'] = 'patch FAILED (find=' . $ct2b . ')';
    }
} else {
    $results['ProductRepo'] = 'already has brand_name filter';
}

// ── 3. Patch ShopAPI ProductController.php – add brand_name to filter ─
$file3 = "$root/beike/ShopAPI/Controllers/ProductController.php";
$src3  = file_get_contents($file3);
if (!str_contains($src3, "'brand_name'")) {
    $find3    = "'keyword', 'attr', 'price', 'sort', 'order', 'per_page', 'category_id', 'brand_id'";
    $replace3 = "'keyword', 'attr', 'price', 'sort', 'order', 'per_page', 'category_id', 'brand_id', 'brand_name'";
    $new3 = str_replace($find3, $replace3, $src3);
    if ($new3 !== $src3 && file_put_contents($file3, $new3) !== false) {
        $results['ShopAPIController'] = 'patched OK';
    } else {
        $results['ShopAPIController'] = 'patch FAILED';
    }
} else {
    $results['ShopAPIController'] = 'already has brand_name';
}

// ── 4. Patch product.blade.php – add data-brand attribute ──────
$file4 = "$root/themes/default/shared/product.blade.php";
$src4  = file_get_contents($file4);
if (!str_contains($src4, 'data-brand')) {
    $find4    = '<div class="product-wrap {{ request(\'style_list\') ?? \'\' }}">';
    $replace4 = '<div class="product-wrap {{ request(\'style_list\') ?? \'\' }}" data-brand="{{ $product[\'brand_name\'] ?? \'\' }}" data-product-id="{{ $product[\'id\'] ?? \'\' }}">';
    $new4 = str_replace($find4, $replace4, $src4);
    if ($new4 !== $src4 && file_put_contents($file4, $new4) !== false) {
        $results['product_blade'] = 'patched OK';
    } else {
        $results['product_blade'] = 'patch FAILED';
    }
} else {
    $results['product_blade'] = 'already has data-brand';
}

// ── 5. Clear compiled views ─────────────────────────────────────
$viewPath = "$root/storage/framework/views";
$cleared  = 0;
foreach (glob("$viewPath/*.php") as $f) {
    if (@unlink($f)) $cleared++;
}
$results['views_cleared'] = $cleared;

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
