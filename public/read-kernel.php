<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }
$kernel = file_get_contents('/www/wwwroot/dona-new/app/Http/Kernel.php');
// Show the section around 'api' with hex chars
$pos = strpos($kernel, "'api'");
$snippet = substr($kernel, $pos, 300);
header('Content-Type: application/json');
echo json_encode([
    'api_pos' => $pos,
    'snippet' => $snippet,
    'hex' => bin2hex(substr($snippet, 0, 80)),
    'already_patched' => strpos($kernel, 'ApiCacheHeaders') !== false,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
