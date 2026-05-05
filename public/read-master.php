<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }
$root = '/www/wwwroot/dona-new';
$content = file_get_contents("$root/themes/default/layout/master.blade.php");
header('Content-Type: text/plain; charset=utf-8');
echo $content;
