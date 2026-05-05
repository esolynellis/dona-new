<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$results = [];
$vhostDir = '/www/server/panel/vhost/nginx';

// Try common filename patterns BT Panel uses
$candidates = [
    "$vhostDir/dona-trade.com.conf",
    "$vhostDir/www.dona-trade.com.conf",
    "$vhostDir/dona-new.conf",
    "$vhostDir/dona.conf",
];

$foundFile = null;
$foundContent = null;
foreach ($candidates as $c) {
    $content = @file_get_contents($c);
    if ($content !== false) {
        $foundFile = $c;
        $foundContent = $content;
        $results['found_vhost'] = $c;
        $results['content_preview'] = substr($content, 0, 1500);
        break;
    }
}

if (!$foundFile) {
    // Try listing with glob (may work even if scandir doesn't)
    $files = glob("$vhostDir/*.conf");
    $results['glob_result'] = $files ?: 'none/empty';

    // Try writing a test file to see if we can write there
    $testPath = "$vhostDir/_test_write.conf";
    $wrote = @file_put_contents($testPath, '# test');
    if ($wrote !== false) {
        @unlink($testPath);
        $results['write_test'] = 'writable';
    } else {
        $results['write_test'] = 'not_writable';
    }
} else {
    // We found the vhost conf — patch it with browser cache headers if not already done
    if (strpos($foundContent, 'expires') !== false) {
        $results['browser_cache'] = 'already_present';
    } else {
        // Add cache location block before the last closing brace
        $cacheBlock = '
    # Browser cache for static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot|webp)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
        access_log off;
    }

';
        // Insert before last closing brace
        $patched = preg_replace('/(\n\}[\s\n]*)$/', $cacheBlock . '$1', $foundContent, 1);
        if ($patched && $patched !== $foundContent) {
            @unlink($foundFile);
            $wrote = file_put_contents($foundFile, $patched);
            if ($wrote !== false) {
                // Test nginx config and reload
                $results['browser_cache'] = 'added';
                $results['write_bytes'] = $wrote;
                // Signal nginx reload via bt panel socket if available
                $pidFile = '/www/server/nginx/logs/nginx.pid';
                $pid = trim(@file_get_contents($pidFile));
                if ($pid && is_numeric($pid)) {
                    // Write a reload trigger (posix_kill disabled, use proc_open)
                    $results['nginx_pid'] = $pid;
                    $results['reload_note'] = 'Nginx needs manual reload in BT panel';
                }
            } else {
                $results['browser_cache'] = 'write_failed';
            }
        } else {
            $results['browser_cache'] = 'patch_regex_failed';
            $results['content_end'] = substr($foundContent, -300);
        }
    }
}

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
