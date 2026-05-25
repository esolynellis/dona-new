<?php
/**
 * fix-build-perms.php
 * One-time fix: makes public/build writable by the web user
 * Delete this file after running it once.
 */
$secret = 'dona2026fix';
if (($_GET['token'] ?? '') !== $secret) {
    http_response_code(403);
    die("forbidden\n");
}

$buildDir = __DIR__ . '/build';
$results  = [];

// Try chmod on the entire build directory tree
$iter = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($buildDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$ok = 0; $fail = 0;
foreach ($iter as $item) {
    $mode = $item->isDir() ? 0775 : 0664;
    if (@chmod($item->getPathname(), $mode)) {
        $ok++;
    } else {
        $fail++;
        $results[] = 'FAIL: ' . $item->getPathname();
    }
}

// Also chmod the build dir itself
@chmod($buildDir, 0775);

echo "Done: ok={$ok} fail={$fail}\n";
if ($fail > 0) {
    echo "\nFailed paths (first 20):\n";
    foreach (array_slice($results, 0, 20) as $r) { echo "  $r\n"; }
    echo "\nNote: failures mean web user doesn't own those files.\n";
    echo "Fix via SSH: chown -R www-data:www-data " . __DIR__ . "\n";
} else {
    echo "All permissions fixed! Deploy hook should work now.\n";
}

echo "\nCSS file info:\n";
$css = __DIR__ . '/build/beike/shop/default/css/app.css';
echo "  Exists: " . (file_exists($css) ? 'yes' : 'no') . "\n";
echo "  Writable: " . (is_writable($css) ? 'YES' : 'no') . "\n";
echo "  Owner uid: " . fileowner($css) . "\n";
echo "  Process uid: " . posix_geteuid() . "\n";
