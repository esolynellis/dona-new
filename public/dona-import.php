<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die('Forbidden'); }

/* ── DB ── */
$root = '/www/wwwroot/dona-new';
$env  = [];
foreach (file("$root/.env") as $ln) {
    $ln = trim($ln);
    if (!$ln || $ln[0] === '#' || !str_contains($ln, '=')) continue;
    [$k, $v] = explode('=', $ln, 2);
    $env[trim($k)] = trim($v, '"\'');
}
$pdo = new PDO(
    "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
    $env['DB_USERNAME'], $env['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$pdo->exec("SET NAMES utf8mb4");

/* ── AJAX batch handler ── */
if (isset($_GET['action']) && $_GET['action'] === 'batch') {
    header('Content-Type: application/json');
    $batchNum = (int)($_GET['b'] ?? 0);
    $BATCH = 80;

    // Get missing IDs
    $missing = $pdo->query(
        "SELECT hg.goods_id FROM huipi_goods hg
         WHERE hg.status = 1
           AND NOT EXISTS (SELECT 1 FROM products p WHERE p.goods_id = hg.goods_id)
         ORDER BY hg.goods_id
         LIMIT $BATCH OFFSET " . ($batchNum * $BATCH)
    )->fetchAll(PDO::FETCH_COLUMN);

    if (!$missing) {
        // Clear cache
        $dirs = ["$root/storage/framework/cache/data","$root/storage/framework/views","$root/storage/framework/sessions","$root/bootstrap/cache"];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) continue;
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $f) {
                if ($f->isFile()) @unlink($f->getPathname());
            }
        }
        echo json_encode(['done' => true, 'inserted' => 0]);
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($missing), '?'));
    $rows = $pdo->prepare(
        "SELECT goods_id, goods_name, goods_cover, goods_image,
                goods_mall_category, cash_price_small, goods_code,
                stock, brand_id
         FROM huipi_goods WHERE goods_id IN ($placeholders)"
    );
    $rows->execute($missing);
    $goods = $rows->fetchAll(PDO::FETCH_ASSOC);

    $validCats = array_flip($pdo->query("SELECT id FROM categories")->fetchAll(PDO::FETCH_COLUMN));

    $insP = $pdo->prepare("INSERT IGNORE INTO products (goods_id,brand_id,images,price,video,position,active,variables,tax_class_id,created_at,updated_at) VALUES (?,?,?,?,'',0,1,'[]',0,NOW(),NOW())");
    $insD = $pdo->prepare("INSERT IGNORE INTO product_descriptions (product_id,locale,name,content,meta_title,meta_description,meta_keyword,created_at,updated_at) VALUES (?,'mn',?,'','','','',NOW(),NOW())");
    $insS = $pdo->prepare("INSERT IGNORE INTO product_skus (product_id,variants,position,images,model,sku,price,origin_price,cost_price,quantity,is_default,created_at,updated_at) VALUES (?,'\"\"',0,?,'default',?,?,?,?,1,NOW(),NOW())");
    $insC = $pdo->prepare("INSERT IGNORE INTO product_categories (product_id,category_id) VALUES (?,?)");

    $inserted = 0;
    foreach ($goods as $g) {
        $imgArr = [];
        $raw = $g['goods_image'] ?? '';
        if ($raw && $raw[0] === '[') { $decoded = json_decode($raw,true); if(is_array($decoded)) $imgArr=$decoded; }
        elseif ($raw && str_contains($raw,',')) { $imgArr = array_map('trim',explode(',',$raw)); }
        elseif ($raw) { $imgArr = [$raw]; }
        elseif ($g['goods_cover']) { $imgArr = [$g['goods_cover']]; }
        $imgs = json_encode(array_values(array_filter($imgArr)));
        $price = (float)($g['cash_price_small'] ?? 0);
        $qty   = (int)($g['stock'] ?? 0);

        $insP->execute([$g['goods_id'], (int)($g['brand_id']??0), $imgs, $price]);
        if (!$insP->rowCount()) continue;
        $pid = (int)$pdo->lastInsertId();
        $inserted++;
        $insD->execute([$pid, $g['goods_name'] ?? '']);
        $insS->execute([$pid, $imgs, $g['goods_code']??'', $price, $price, $qty]);
        $catId = (int)($g['goods_mall_category'] ?? 0);
        if ($catId && isset($validCats[$catId])) $insC->execute([$pid, $catId]);
    }

    $remaining = $pdo->query("SELECT COUNT(*) FROM huipi_goods hg WHERE hg.status=1 AND NOT EXISTS (SELECT 1 FROM products p WHERE p.goods_id=hg.goods_id)")->fetchColumn();
    echo json_encode(['done' => false, 'inserted' => $inserted, 'remaining' => (int)$remaining]);
    exit;
}

/* ── Stats for page load ── */
$totalHuipi   = (int)$pdo->query("SELECT COUNT(*) FROM huipi_goods WHERE status=1")->fetchColumn();
$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE deleted_at IS NULL")->fetchColumn();
$toImport     = (int)$pdo->query("SELECT COUNT(*) FROM huipi_goods hg WHERE hg.status=1 AND NOT EXISTS (SELECT 1 FROM products p WHERE p.goods_id=hg.goods_id)")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="mn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>DONA – Барааны импорт</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { background:#f4f6fb; font-family:'Segoe UI',sans-serif; }
  .card { border-radius:16px; border:none; box-shadow:0 2px 16px rgba(0,0,0,.08); }
  .stat-box { background:#fff; border-radius:12px; padding:20px 24px; text-align:center; box-shadow:0 1px 8px rgba(0,0,0,.06); }
  .stat-num { font-size:2.2rem; font-weight:700; color:#2563eb; }
  .stat-lbl { font-size:.85rem; color:#64748b; margin-top:4px; }
  #log { background:#1e293b; color:#94a3b8; border-radius:10px; padding:16px; font-family:monospace; font-size:.82rem; height:260px; overflow-y:auto; }
  #log .ok  { color:#4ade80; }
  #log .err { color:#f87171; }
  #log .inf { color:#60a5fa; }
  .btn-import { font-size:1.1rem; padding:14px 40px; border-radius:10px; font-weight:600; }
</style>
</head>
<body>
<div class="container py-5" style="max-width:760px">

  <div class="d-flex align-items-center mb-4 gap-3">
    <img src="/images/logo.png" height="44" onerror="this.style.display='none'">
    <div>
      <h4 class="mb-0 fw-bold">DONA — Барааны импорт</h4>
      <small class="text-muted">huipi_goods → products</small>
    </div>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-4">
      <div class="stat-box">
        <div class="stat-num"><?= number_format($totalHuipi) ?></div>
        <div class="stat-lbl">Нийт эх бараа</div>
      </div>
    </div>
    <div class="col-4">
      <div class="stat-box">
        <div class="stat-num"><?= number_format($totalProducts) ?></div>
        <div class="stat-lbl">Сайт дахь бараа</div>
      </div>
    </div>
    <div class="col-4">
      <div class="stat-box">
        <div class="stat-num text-<?= $toImport > 0 ? 'warning' : 'success' ?>"><?= number_format($toImport) ?></div>
        <div class="stat-lbl">Нэмэх бараа</div>
      </div>
    </div>
  </div>

  <div class="card p-4 mb-4">
    <?php if ($toImport === 0): ?>
    <div class="alert alert-success mb-3">✅ Бүх бараа нэмэгдсэн байна. Нэмэх зүйл алга.</div>
    <?php else: ?>
    <p class="text-muted mb-3">
      <strong><?= number_format($toImport) ?></strong> бараа нэмэгдээгүй байна.
      Доорх товч дарахад автоматаар нэмнэ.
    </p>
    <?php endif; ?>

    <!-- Progress -->
    <div id="progress-wrap" class="mb-3" style="display:none">
      <div class="d-flex justify-content-between mb-1">
        <small class="text-muted">Нэмэж байна...</small>
        <small id="pct">0%</small>
      </div>
      <div class="progress" style="height:10px">
        <div id="pbar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width:0%"></div>
      </div>
      <div class="mt-1"><small id="status-text" class="text-muted"></small></div>
    </div>

    <!-- Button -->
    <div class="d-flex gap-2 align-items-center">
      <button id="btn-import" class="btn btn-primary btn-import" <?= $toImport===0?'disabled':'' ?> onclick="startImport()">
        ➕ Бүгдийг нэмэх (<?= number_format($toImport) ?>)
      </button>
      <button id="btn-reload" class="btn btn-outline-secondary" onclick="location.reload()">↺ Шинэчлэх</button>
    </div>
  </div>

  <!-- Log -->
  <div class="card p-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <small class="fw-semibold text-muted">Лог</small>
      <button class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('log').innerHTML=''">Цэвэрлэх</button>
    </div>
    <div id="log"><span class="inf">— Бэлэн байна. Товч дарна уу. —</span></div>
  </div>

</div>

<script>
const KEY = 'dona2025';
let totalToImport = <?= $toImport ?>;
let totalInserted = 0;
let batchNum = 0;
let running = false;

function log(msg, cls='') {
  const el = document.getElementById('log');
  const line = document.createElement('div');
  if (cls) line.className = cls;
  line.textContent = (new Date().toLocaleTimeString()) + '  ' + msg;
  el.appendChild(line);
  el.scrollTop = el.scrollHeight;
}

function setProgress(inserted, remaining) {
  const total = totalToImport;
  const done  = total - remaining;
  const pct   = total > 0 ? Math.min(100, Math.round(done / total * 100)) : 100;
  document.getElementById('pbar').style.width = pct + '%';
  document.getElementById('pct').textContent  = pct + '%';
  document.getElementById('status-text').textContent =
    `Нэмсэн: ${totalInserted} | Үлдсэн: ${remaining}`;
}

async function runBatch() {
  const url = `/dona-import.php?key=${KEY}&action=batch&b=${batchNum}&_=${Date.now()}`;
  const resp = await fetch(url);
  if (!resp.ok) throw new Error('HTTP ' + resp.status);
  return await resp.json();
}

async function startImport() {
  if (running) return;
  running = true;
  batchNum = 0;
  totalInserted = 0;

  document.getElementById('btn-import').disabled = true;
  document.getElementById('btn-import').textContent = '⏳ Нэмж байна...';
  document.getElementById('progress-wrap').style.display = 'block';
  document.getElementById('log').innerHTML = '';
  log('Импорт эхэлж байна...', 'inf');

  try {
    while (true) {
      const data = await runBatch();
      totalInserted += data.inserted || 0;

      if (data.done) {
        log('✅ Бүгд дууслаа! Нийт нэмсэн: ' + totalInserted, 'ok');
        setProgress(totalInserted, 0);
        document.getElementById('pbar').classList.remove('progress-bar-animated');
        document.getElementById('pbar').classList.add('bg-success');
        document.getElementById('btn-import').textContent = '✅ Дууслаа';
        document.getElementById('btn-import').classList.replace('btn-primary','btn-success');
        break;
      }

      const remaining = data.remaining ?? 0;
      log(`Batch ${batchNum}: +${data.inserted} барааны нэмэгдсэн | үлдсэн: ${remaining}`, 'ok');
      setProgress(totalInserted, remaining);
      batchNum++;

      // Small pause to avoid overwhelming server
      await new Promise(r => setTimeout(r, 200));
    }
  } catch(e) {
    log('❌ Алдаа: ' + e.message, 'err');
    log('Дахин оролдоно уу.', 'err');
    document.getElementById('btn-import').disabled = false;
    document.getElementById('btn-import').textContent = '➕ Дахин оролдох';
  }
  running = false;
}
</script>
</body>
</html>
