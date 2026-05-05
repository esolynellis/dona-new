<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>DONA – Device Test</title>
<?php
// Inject same head_code as main site
$root = '/www/wwwroot/dona-new';
$env = [];
foreach (file("$root/.env") as $line) {
    $line = trim($line);
    if (!$line || $line[0] === '#' || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, '"\'');
}
$pdo = new PDO(
    "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
    $env['DB_USERNAME'], $env['DB_PASSWORD']
);
$hc = $pdo->query("SELECT value FROM settings WHERE space='base' AND name='head_code' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
echo $hc['value'] ?? '';
?>
<style>
body { font-family: sans-serif; padding: 20px; max-width: 500px; margin: 0 auto; }
.ok  { color: green; font-weight: bold; }
.bad { color: red; font-weight: bold; }
.row { padding: 10px 0; border-bottom: 1px solid #eee; }
</style>
</head>
<body>
<h2>🔍 DONA Device Test</h2>
<div class="row"><b>Browser:</b> <span id="ua"></span></div>
<div class="row"><b>smooth.css:</b> <span id="css-check">checking...</span></div>
<div class="row"><b>Service Worker:</b> <span id="sw-check">checking...</span></div>
<div class="row"><b>Loading bar:</b> <span id="loader-check">checking...</span></div>
<div class="row"><b>Preload hints:</b> <span id="preload-check">checking...</span></div>
<div class="row"><b>Screen:</b> <span id="screen"></span></div>
<br>
<div id="dona-loader" style="position:fixed;top:0;left:0;height:3px;width:70%;background:#e8002d;z-index:9999;border-radius:0 2px 2px 0;"></div>

<script>
document.getElementById('ua').textContent = navigator.userAgent;
document.getElementById('screen').textContent = window.innerWidth + 'x' + window.innerHeight;

// Check smooth.css loaded
var sheets = Array.from(document.styleSheets);
var smooth = sheets.some(function(s){ return s.href && s.href.includes('smooth.css'); });
document.getElementById('css-check').textContent = smooth ? '✅ Loaded' : '❌ NOT loaded';
document.getElementById('css-check').className = smooth ? 'ok' : 'bad';

// Check service worker
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.getRegistrations().then(function(regs){
    var active = regs.some(function(r){ return r.active; });
    document.getElementById('sw-check').textContent = active ? '✅ Registered & active' : '⏳ Registered, not yet active';
    document.getElementById('sw-check').className = active ? 'ok' : '';
  });
} else {
  document.getElementById('sw-check').textContent = '❌ Not supported';
  document.getElementById('sw-check').className = 'bad';
}

// Check loading bar
var loader = document.getElementById('dona-loader');
document.getElementById('loader-check').textContent = loader ? '✅ Present' : '❌ Missing';
document.getElementById('loader-check').className = loader ? 'ok' : 'bad';

// Check preload
var preloads = document.querySelectorAll('link[rel="preload"]');
document.getElementById('preload-check').textContent = preloads.length > 0 ? '✅ ' + preloads.length + ' hints' : '❌ None';
document.getElementById('preload-check').className = preloads.length > 0 ? 'ok' : 'bad';
</script>
</body>
</html>
