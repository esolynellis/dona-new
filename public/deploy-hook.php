<?php
/**
 * GitHub Webhook → Auto Deploy
 *
 * URL:        https://dona-trade.com/deploy-hook.php
 * Method:     POST (from GitHub)
 * Validation: HMAC-SHA256 with shared secret
 *
 * Setup:
 *   cd /www/wwwroot/dona-new
 *   openssl rand -hex 32 > .deploy-hook-secret
 *   chmod 600 .deploy-hook-secret
 *   chown www:www .deploy-hook-secret
 *   chmod +x bin/auto-deploy.sh
 *
 * Then on GitHub repo → Settings → Webhooks → Add webhook:
 *   Payload URL:  https://dona-trade.com/deploy-hook.php
 *   Content type: application/json
 *   Secret:       (paste contents of .deploy-hook-secret)
 *   Events:       Just the push event
 */

// Quick health check (browser visit shows status, no secrets leaked)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: text/plain; charset=utf-8');
    $secretFile = __DIR__ . '/../.deploy-hook-secret';
    echo "deploy-hook.php is alive\n";
    echo "secret file: " . (is_file($secretFile) ? "configured" : "MISSING — run setup") . "\n";
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
$ref = $data['ref'] ?? '';
if ($ref !== 'refs/heads/master' && $ref !== 'refs/heads/main') {
    http_response_code(202);
    exit("ignored branch: $ref\n");
}

// 5) Trigger deploy in background (return fast so GitHub doesn't time out)
$projectRoot = realpath(__DIR__ . '/..');
$script = $projectRoot . '/bin/auto-deploy.sh';
$logFile = $projectRoot . '/storage/logs/auto-deploy.log';

if (!is_file($script)) {
    http_response_code(500);
    exit("auto-deploy.sh not found at $script\n");
}

// Run in background, detached
$cmd = sprintf(
    'cd %s && bash %s >> %s 2>&1 &',
    escapeshellarg($projectRoot),
    escapeshellarg($script),
    escapeshellarg($logFile)
);
exec($cmd);

http_response_code(202);
echo "deploy triggered for $ref\n";
echo "log: storage/logs/auto-deploy.log\n";
