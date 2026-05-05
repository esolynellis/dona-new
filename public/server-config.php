<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

// Read main nginx.conf
$nginxConf = file_get_contents('/www/server/nginx/conf/nginx.conf');
echo "<pre>" . htmlspecialchars($nginxConf) . "</pre>";
