<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }
$root = '/www/wwwroot/dona-new';
$results = [];

// ── 1. Patch ProductSimple.php – add brand_name after cart_id line ─
$file1 = "$root/beike/Shop/Http/Resources/ProductSimple.php";
$src1  = file_get_contents($file1);
if (!str_contains($src1, "'brand_name'")) {
    // Line-by-line: find the 'cart_id' => $cart_id, line in the data array and insert brand_name after it
    $lines1   = explode("\n", $src1);
    $patched1 = false;
    $out1     = [];
    foreach ($lines1 as $line) {
        $out1[] = $line;
        // Match the data array cart_id line (has '=>' and 'cart_id' and '$cart_id')
        if (!$patched1 && preg_match("/['\"]cart_id['\"\s]*=>\s*\\\$cart_id/", $line)) {
            // Detect indentation from this line
            preg_match('/^(\s+)/', $line, $indent);
            $pad = $indent[1] ?? '            ';
            $out1[] = $pad . "'brand_name'      => \$this->brand_name ?? '',";
            $patched1 = true;
        }
    }
    if ($patched1) {
        $new1 = implode("\n", $out1);
        if (file_put_contents($file1, $new1) !== false) {
            $results['ProductSimple'] = 'patched OK';
        } else {
            $results['ProductSimple'] = 'write FAILED';
        }
    } else {
        $results['ProductSimple'] = 'cart_id line not found';
    }
} else {
    $results['ProductSimple'] = 'already has brand_name';
}

// ── 2. Patch ProductRepo.php getBuilderWyl – brand_name filter ──
$file2 = "$root/beike/Repositories/ProductRepo.php";
$src2  = file_get_contents($file2);
if (!str_contains($src2, 'brand_name filter')) {
    // Find brand_id block in getBuilderWyl (the one preceded by the nested category closing)
    // Strategy: find ALL occurrences of the brand_id block and add brand_name after the LAST one
    // (getBuilderWyl is the last function that has it)
    $pattern = '/(\$brandId = \$filters\[.brand_id.\] \?\? 0;\s+if \(\$brandId\) \{\s+\$builder->where\(.brand_id., \$brandId\);\s+\})/';
    $matches = [];
    preg_match_all($pattern, $src2, $matches, PREG_OFFSET_CAPTURE);
    if (!empty($matches[0])) {
        // Patch the LAST occurrence (which is in getBuilderWyl)
        $lastMatch  = end($matches[0]);
        $matchStr   = $lastMatch[0];
        $matchPos   = $lastMatch[1];
        $insertion  = "\n\n        // Filter by brand_name text (brand_name filter)\n        \$brandNameFilter = \$filters['brand_name'] ?? '';\n        if (\$brandNameFilter) {\n            \$builder->where('products.brand_name', \$brandNameFilter);\n        }";
        $new2 = substr($src2, 0, $matchPos + strlen($matchStr)) . $insertion . substr($src2, $matchPos + strlen($matchStr));
        if (file_put_contents($file2, $new2) !== false) {
            $results['ProductRepo'] = 'patched OK (last occurrence)';
        } else {
            $results['ProductRepo'] = 'write FAILED';
        }
    } else {
        $results['ProductRepo'] = 'pattern not found';
    }
} else {
    $results['ProductRepo'] = 'already has brand_name filter';
}

// ── 3. ShopAPI ProductController – already handled ─────────────
$file3 = "$root/beike/ShopAPI/Controllers/ProductController.php";
$src3  = file_get_contents($file3);
$results['ShopAPIController'] = str_contains($src3, "'brand_name'") ? 'OK' : 'MISSING - adding now';
if (!str_contains($src3, "'brand_name'")) {
    $new3 = str_replace(
        "'keyword', 'attr', 'price', 'sort', 'order', 'per_page', 'category_id', 'brand_id'",
        "'keyword', 'attr', 'price', 'sort', 'order', 'per_page', 'category_id', 'brand_id', 'brand_name'",
        $src3
    );
    if ($new3 !== $src3) file_put_contents($file3, $new3);
}

// ── 4. Patch product.blade.php – add data-brand attribute ───────
$file4 = "$root/themes/default/shared/product.blade.php";
$src4  = file_get_contents($file4);
if (!str_contains($src4, 'data-brand')) {
    // Find exactly the product-wrap div line - it ends with '">'
    // The exact match from server is: <div class="product-wrap {{ request('style_list') ?? '' }}">
    $lines4   = explode("\n", $src4);
    $patched4 = false;
    $out4     = [];
    foreach ($lines4 as $line) {
        if (!$patched4 && str_contains($line, 'product-wrap') && str_contains($line, '<div')) {
            // Replace the closing "> with data attributes + ">
            $new_line = rtrim($line);
            // Remove the trailing ">
            if (str_ends_with($new_line, '">')) {
                $new_line = substr($new_line, 0, -2);
                $new_line .= '" data-brand="{{ $product[\'brand_name\'] ?? \'\' }}" data-product-id="{{ $product[\'id\'] ?? \'\' }}">';
                $out4[]    = $new_line;
                $patched4  = true;
                continue;
            }
        }
        $out4[] = $line;
    }
    if ($patched4) {
        if (file_put_contents($file4, implode("\n", $out4)) !== false) {
            $results['product_blade'] = 'patched OK';
        } else {
            $results['product_blade'] = 'write FAILED';
        }
    } else {
        preg_match('/<div class="product-wrap[^>]*>/', $src4, $m);
        $results['product_blade'] = 'FAILED – found: ' . ($m[0] ?? 'no product-wrap div');
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
