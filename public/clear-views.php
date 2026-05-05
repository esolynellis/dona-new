<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$viewPath = '/www/wwwroot/dona-new/storage/framework/views';
$files    = glob("$viewPath/*.php");
$deleted  = 0;
foreach ($files as $f) {
    if (@unlink($f)) $deleted++;
}

header('Content-Type: application/json');
echo json_encode(['views_cleared' => $deleted]);
