<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }
$out = [];
$out['node'] = shell_exec('node -v 2>&1') ?? exec('node -v 2>&1');
$out['npm']  = shell_exec('npm -v 2>&1')  ?? exec('npm -v 2>&1');
$out['which_node'] = shell_exec('which node 2>&1');
$out['shell_exec_enabled'] = function_exists('shell_exec');
$out['exec_enabled'] = function_exists('exec');
header('Content-Type: application/json');
echo json_encode($out, JSON_PRETTY_PRINT);
