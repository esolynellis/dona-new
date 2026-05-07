<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die('Forbidden'); }

$root = '/www/wwwroot/dona-new';
$env  = [];
foreach (file("$root/.env") as $ln) {
    $ln = trim($ln); if (!$ln || $ln[0]==='#' || !str_contains($ln,'=')) continue;
    [$k,$v] = explode('=',$ln,2); $env[trim($k)] = trim($v,'"\'');
}
$pdo = new PDO(
    "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
    $env['DB_USERNAME'], $env['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]
);
$pdo->exec("SET NAMES utf8mb4");

/* ── AJAX: run import ── */
if (($_GET['action'] ?? '') === 'import') {
    header('Content-Type: application/json');
    set_time_limit(120);

    // Find missing via LEFT JOIN (faster than NOT EXISTS)
    $missing = $pdo->query(
        "SELECT hg.* FROM huipi_goods hg
         LEFT JOIN products p ON p.goods_id = hg.goods_id
         WHERE hg.status = 1 AND p.goods_id IS NULL
         LIMIT 200"
    )->fetchAll(PDO::FETCH_ASSOC);

    if (!$missing) {
        // clear cache
        foreach ([
            "$root/storage/framework/cache/data",
            "$root/storage/framework/views",
            "$root/storage/framework/sessions",
            "$root/bootstrap/cache"
        ] as $dir) {
            if (!is_dir($dir)) continue;
            foreach (new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
            ) as $f) { if ($f->isFile()) @unlink($f->getPathname()); }
        }
        echo json_encode(['done'=>true, 'inserted'=>0, 'remaining'=>0]);
        exit;
    }

    $validCats = array_flip($pdo->query("SELECT id FROM categories")->fetchAll(PDO::FETCH_COLUMN));

    // Direct insert reusing all huipi_goods columns that exist in products
    $ins = $pdo->prepare(
        "INSERT IGNORE INTO products
         (goods_id, brand_id, goods_name, goods_code, goods_mall_category,
          cash_price_small, gunit_max, gnum_min, gunit_min, quality,
          images, price, video, position, active, variables, tax_class_id,
          created_at, updated_at)
         VALUES
         (?,?,?,?,?,?,?,?,?,?,?,?,  '',0,1,'[]',0, NOW(),NOW())"
    );

    $insD = $pdo->prepare(
        "INSERT IGNORE INTO product_descriptions
         (product_id, locale, name, content, meta_title, meta_description, meta_keyword, created_at, updated_at)
         VALUES (?, 'mn', ?, '', '', '', '', NOW(), NOW())"
    );
    $insS = $pdo->prepare(
        "INSERT IGNORE INTO product_skus
         (product_id, variants, position, images, model, sku, price, origin_price, cost_price, quantity, is_default, created_at, updated_at)
         VALUES (?, '\"\"', 0, ?, 'default', ?, ?, ?, ?, 1, NOW(), NOW())"
    );
    $insC = $pdo->prepare(
        "INSERT IGNORE INTO product_categories (product_id, category_id) VALUES (?, ?)"
    );

    $inserted = 0;
    foreach ($missing as $g) {
        // Build images JSON from goods_image field
        $imgArr = [];
        $raw = $g['goods_image'] ?? '';
        if ($raw && $raw[0]==='[') { $d=json_decode($raw,true); if(is_array($d)) $imgArr=$d; }
        elseif ($raw && str_contains($raw,',')) { $imgArr=array_map('trim',explode(',',$raw)); }
        elseif ($raw) { $imgArr=[$raw]; }
        elseif (!empty($g['goods_cover'])) { $imgArr=[$g['goods_cover']]; }
        $imgs = json_encode(array_values(array_filter($imgArr)));

        $price = (float)($g['cash_price_small'] ?? 0);
        $qty   = (int)($g['stock'] ?? 0);

        $ins->execute([
            $g['goods_id'],
            (int)($g['brand_id']??0),
            $g['goods_name'] ?? '',
            $g['goods_code'] ?? '',
            (int)($g['goods_mall_category']??0),
            $price,
            $g['gunit_max'] ?? '',
            $g['gnum_min']  ?? 0,
            $g['gunit_min'] ?? '',
            (int)($g['quality']??0),
            $imgs,
            $price,
        ]);
        if (!$ins->rowCount()) continue;
        $pid = (int)$pdo->lastInsertId();
        $inserted++;

        $insD->execute([$pid, $g['goods_name'] ?? '']);
        $insS->execute([$pid, $imgs, $g['goods_code']??'', $price, $price, $qty]);

        $catId = (int)($g['goods_mall_category']??0);
        if ($catId && isset($validCats[$catId])) $insC->execute([$pid, $catId]);
    }

    $remaining = (int)$pdo->query(
        "SELECT COUNT(*) FROM huipi_goods hg
         LEFT JOIN products p ON p.goods_id = hg.goods_id
         WHERE hg.status=1 AND p.goods_id IS NULL"
    )->fetchColumn();

    echo json_encode(['done'=>$remaining===0, 'inserted'=>$inserted, 'remaining'=>$remaining]);
    exit;
}

/* ── Stats ── */
$totalHuipi    = (int)$pdo->query("SELECT COUNT(*) FROM huipi_goods WHERE status=1")->fetchColumn();
$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE deleted_at IS NULL")->fetchColumn();
$toImport      = (int)$pdo->query(
    "SELECT COUNT(*) FROM huipi_goods hg
     LEFT JOIN products p ON p.goods_id = hg.goods_id
     WHERE hg.status=1 AND p.goods_id IS NULL"
)->fetchColumn();
?>
<!DOCTYPE html>
<html lang="mn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>DONA – Барааны импорт</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#f0f4ff;font-family:'Segoe UI',sans-serif}
.top-bar{background:#1a237e;color:#fff;padding:14px 28px;display:flex;align-items:center;gap:14px}
.top-bar h5{margin:0;font-weight:700;letter-spacing:.3px}
.card{border-radius:14px;border:none;box-shadow:0 2px 14px rgba(0,0,0,.07)}
.stat-box{background:#fff;border-radius:12px;padding:22px 16px;text-align:center;box-shadow:0 1px 8px rgba(0,0,0,.06)}
.stat-num{font-size:2rem;font-weight:800;color:#1a237e}
.stat-lbl{font-size:.8rem;color:#64748b;margin-top:6px}
#log{background:#0f172a;color:#94a3b8;border-radius:10px;padding:14px;font-family:monospace;font-size:.78rem;height:220px;overflow-y:auto}
.ok{color:#4ade80}.err{color:#f87171}.inf{color:#60a5fa}
.btn-go{font-size:1rem;padding:13px 36px;border-radius:10px;font-weight:700;background:#1a237e;border-color:#1a237e}
.btn-go:hover{background:#283593;border-color:#283593}
</style>
</head>
<body>

<div class="top-bar">
  <svg width="32" height="32" viewBox="0 0 24 24" fill="none"><rect width="24" height="24" rx="6" fill="white" fill-opacity=".15"/><path d="M12 4L4 8l8 4 8-4-8-4zM4 12l8 4 8-4M4 16l8 4 8-4" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
  <h5>DONA — Барааны импорт хяналт</h5>
</div>

<div class="container py-4" style="max-width:700px">

  <div class="row g-3 mb-4">
    <div class="col-4"><div class="stat-box">
      <div class="stat-num"><?= number_format($totalHuipi) ?></div>
      <div class="stat-lbl">Нийт эх бараа</div>
    </div></div>
    <div class="col-4"><div class="stat-box">
      <div class="stat-num"><?= number_format($totalProducts) ?></div>
      <div class="stat-lbl">Сайт дахь бараа</div>
    </div></div>
    <div class="col-4"><div class="stat-box">
      <div class="stat-num" style="color:<?= $toImport>0?'#d97706':'#16a34a' ?>"><?= number_format($toImport) ?></div>
      <div class="stat-lbl">Нэмэх дутуу бараа</div>
    </div></div>
  </div>

  <div class="card p-4 mb-3">
    <?php if ($toImport === 0): ?>
    <div class="alert alert-success mb-3 fw-semibold">✅ Бүх бараа нэмэгдсэн байна!</div>
    <?php else: ?>
    <p class="text-muted mb-3">
      <strong><?= number_format($toImport) ?></strong> бараа нэмэгдээгүй байна.
      Доорх товч дарахад автоматаар нэмнэ.
    </p>
    <?php endif; ?>

    <div id="prog-wrap" style="display:none" class="mb-3">
      <div class="d-flex justify-content-between mb-1">
        <small class="text-muted fw-semibold">Нэмэж байна...</small>
        <small id="pct-lbl" class="text-muted">0%</small>
      </div>
      <div class="progress mb-1" style="height:12px">
        <div id="pbar" class="progress-bar progress-bar-striped progress-bar-animated"
             style="width:0%;background:#1a237e"></div>
      </div>
      <small id="stat-lbl" class="text-muted"></small>
    </div>

    <div class="d-flex gap-2">
      <button id="btn" class="btn btn-go btn-primary text-white"
              <?= $toImport===0?'disabled':'' ?>
              onclick="startImport()">
        ➕&nbsp; Бүгдийг нэмэх &nbsp;<span class="badge bg-white text-primary"><?= number_format($toImport) ?></span>
      </button>
      <button class="btn btn-outline-secondary" onclick="location.reload()">↺ Шинэчлэх</button>
    </div>
  </div>

  <div class="card p-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <small class="fw-semibold text-muted">Лог</small>
      <button class="btn btn-sm btn-outline-secondary py-0" onclick="document.getElementById('log').innerHTML=''">Цэвэрлэх</button>
    </div>
    <div id="log"><span class="inf">— Бэлэн байна —</span></div>
  </div>

  <p class="text-muted text-center mt-3" style="font-size:.75rem">
    🔒 Зөвхөн admin хандалт &nbsp;|&nbsp; dona-trade.com
  </p>
</div>

<script>
let running=false, totalNew=<?= $toImport ?>, totalInserted=0;

function addLog(msg,cls=''){
  const el=document.getElementById('log');
  const d=document.createElement('div');
  if(cls)d.className=cls;
  d.textContent=new Date().toLocaleTimeString()+' '+msg;
  el.appendChild(d); el.scrollTop=el.scrollHeight;
}

function setBar(done,remaining){
  const total=totalNew;
  const pct=total>0?Math.min(100,Math.round(done/total*100)):100;
  document.getElementById('pbar').style.width=pct+'%';
  document.getElementById('pct-lbl').textContent=pct+'%';
  document.getElementById('stat-lbl').textContent=
    'Нэмсэн: '+totalInserted+' | Үлдсэн: '+remaining;
}

async function startImport(){
  if(running)return;
  running=true;
  totalInserted=0;
  document.getElementById('btn').disabled=true;
  document.getElementById('btn').innerHTML='⏳ Нэмж байна...';
  document.getElementById('prog-wrap').style.display='block';
  document.getElementById('log').innerHTML='';
  addLog('Эхэллээ...','inf');

  try{
    let round=0;
    while(true){
      const r=await fetch('/dona-import.php?key=dona2025&action=import&r='+Date.now());
      if(!r.ok) throw new Error('HTTP '+r.status);
      const d=await r.json();
      totalInserted+=d.inserted||0;
      if(d.inserted>0) addLog('+'+d.inserted+' бараа нэмэгдлээ  | үлдсэн: '+(d.remaining||0),'ok');
      setBar(totalInserted, d.remaining||0);
      if(d.done||d.remaining===0){
        addLog('✅ Бүгд дууслаа! Нийт нэмсэн: '+totalInserted,'ok');
        document.getElementById('pbar').classList.remove('progress-bar-animated');
        document.getElementById('pbar').style.background='#16a34a';
        document.getElementById('btn').textContent='✅ Дууслаа — хуудсыг шинэчлэх';
        document.getElementById('btn').className='btn btn-success btn-go';
        document.getElementById('btn').onclick=()=>location.reload();
        document.getElementById('btn').disabled=false;
        break;
      }
      round++;
      await new Promise(r=>setTimeout(r,300));
    }
  }catch(e){
    addLog('❌ Алдаа: '+e.message,'err');
    document.getElementById('btn').disabled=false;
    document.getElementById('btn').textContent='➕ Дахин оролдох';
  }
  running=false;
}
</script>
</body>
</html>
