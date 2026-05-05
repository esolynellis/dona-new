<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';
$pub  = "$root/public";

function listDir($dir, $ext = null) {
    $files = @scandir($dir);
    if (!$files) return [];
    $out = [];
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        if ($ext && !str_ends_with($f, $ext)) continue;
        $path = "$dir/$f";
        if (is_dir($path)) {
            foreach (listDir($path, $ext) as $sub) $out[] = $sub;
        } else {
            $out[] = str_replace("$root/public", '', $path) . ' (' . round(filesize($path)/1024) . 'KB)';
        }
    }
    return $out;
}

$results = [
    'js_files'  => listDir("$pub/js"),
    'css_files' => listDir("$pub/css"),
    'mix_manifest' => json_decode(@file_get_contents("$pub/mix-manifest.json") ?: '{}', true),
];

// Read main blade layout to find how assets are included
$layouts = glob("$root/beike/themes/default/views/layouts/*.blade.php");
foreach ($layouts as $layout) {
    $content = file_get_contents($layout);
    if (strpos($content, '<link') !== false || strpos($content, '<script') !== false) {
        // Extract head section
        preg_match('/<head>(.*?)<\/head>/si', $content, $m);
        $results['layout_head_' . basename($layout)] = $m[1] ?? substr($content, 0, 800);
    }
}

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
