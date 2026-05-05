<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }
$kernel = file_get_contents('/www/wwwroot/dona-new/app/Http/Kernel.php');
header('Content-Type: text/plain');
echo $kernel;
