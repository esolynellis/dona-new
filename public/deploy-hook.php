<?php
/**
 * GitHub Webhook → Auto Deploy (PHP-only, no shell exec required)
 *
 * URL:    https://dona-trade.com/deploy-hook.php
 * Method: POST (from GitHub)
 * Secret: stored in ../.deploy-hook-secret
 */

define('REPO_OWNER', 'esolynellis');
define('REPO_NAME', 'dona-new');

// Quick health check
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: text/plain; charset=utf-8');
    $secretFile = __DIR__ . '/../.deploy-hook-secret';
    echo "deploy-hook.php is alive (PHP-only deploy)\n";
    echo "secret file: " . (is_file($secretFile) ? "configured" : "MISSING — run setup") . "\n";
    echo "PHP exec: " . (function_exists('exec') ? 'available' : 'disabled') . "\n";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("method not allowed\n");
}

// 1) Load shared secret
$secretFile = __DIR__ . '/../.deploy-hook-secret';
if (!is_file($secretFile)) {
    http_response_code(500);
    exit("secret not configured on server\n");
}
$secret = trim(file_get_contents($secretFile));
if ($secret === '') {
    http_response_code(500);
    exit("secret is empty\n");
}

// 2) Verify HMAC-SHA256 signature from GitHub
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
if (!hash_equals($expected, $signature)) {
    http_response_code(401);
    exit("invalid signature\n");
}

// 3) Handle event
$event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';
if ($event === 'ping') {
    echo "pong — webhook is configured correctly\n";
    exit;
}
if ($event !== 'push') {
    http_response_code(202);
    exit("ignored event: $event\n");
}

// 4) Only deploy on master/main branch
$data = json_decode($payload, true);
$ref  = $data['ref'] ?? '';
if ($ref !== 'refs/heads/master' && $ref !== 'refs/heads/main') {
    http_response_code(202);
    exit("ignored branch: $ref\n");
}

// 5) Collect all added/modified files from all commits in this push
$sha          = $data['after'] ?? 'master';
$projectRoot  = realpath(__DIR__ . '/..');
$changedFiles = [];
foreach (($data['commits'] ?? []) as $commit) {
    foreach (['added', 'modified'] as $type) {
        foreach (($commit[$type] ?? []) as $file) {
            $changedFiles[$file] = true;
        }
    }
}
$changedFiles = array_keys($changedFiles);

// 6) Download each changed file from GitHub and write to disk
$logFile = $projectRoot . '/storage/logs/auto-deploy.log';
$log = fopen($logFile, 'ab') ?: null;
$logLine = function (string $msg) use ($log) {
    $line = date('[Y-m-d H:i:s] ') . $msg . "\n";
    if ($log) fwrite($log, $line);
};

$logLine("=== Deploy triggered: sha={$sha} ref={$ref} files=" . count($changedFiles));

$ok = 0;
$fail = 0;
foreach ($changedFiles as $filePath) {
    $rawUrl   = "https://raw.githubusercontent.com/" . REPO_OWNER . "/" . REPO_NAME . "/{$sha}/{$filePath}";
    $localPath = $projectRoot . '/' . $filePath;

    // Download via PHP curl (no shell exec needed)
    $ch = curl_init($rawUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $content    = curl_exec($ch);
    $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError  = curl_error($ch);
    curl_close($ch);

    if ($content === false || $httpCode !== 200) {
        $logLine("FAIL [$httpCode] $filePath — curl: $curlError");
        $fail++;
        continue;
    }

    // Ensure directory exists
    $dir = dirname($localPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    if (file_put_contents($localPath, $content) !== false) {
        $logLine("OK   $filePath");
        $ok++;
    } else {
        $logLine("FAIL (write) $filePath");
        $fail++;
    }
}

// 7) Clear Laravel caches by deleting cache files
$bootstrapCache = $projectRoot . '/bootstrap/cache';
foreach (glob($bootstrapCache . '/*.php') as $f) {
    @unlink($f);
}
$logLine("Cleared bootstrap/cache PHP files");

// Also clear storage/framework/cache
$frameworkCache = $projectRoot . '/storage/framework/cache/data';
if (is_dir($frameworkCache)) {
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($frameworkCache));
    foreach ($iter as $f) {
        if ($f->isFile()) @unlink($f->getPathname());
    }
}
$logLine("Cleared framework cache");

$logLine("Done: ok={$ok} fail={$fail}");
if ($log) fclose($log);

http_response_code(202);
echo "deploy done: ok={$ok} fail={$fail} (PHP-only, no exec)\n";
