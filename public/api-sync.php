<?php
/**
 * api-sync.php
 * Sync products from business.btk123.com API → local huipi_goods + products
 * Usage: /api-sync.php?key=dona2025            (preview/count)
 *        /api-sync.php?key=dona2025&run=1       (sync all)
 *        /api-sync.php?key=dona2025&action=batch&b=0  (AJAX batch, called by UI)
 */
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die('Forbidden'); }

define('API_BASE',  'https://business.btk123.com');
define('API_ACCT',  'donaapi');
define('API_PASS',  '123456');
define('PAGE_SIZE', 100);

/* ── DB ── */
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

/* ── Get API token ── */
function getApiToken(): string {
    $ch = curl_init(API_BASE . '/adminapi/login/getApiToken');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => ['account'=>API_ACCT,'password'=>API_PASS],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $body = curl_exec($ch); curl_close($ch);
    $j = json_decode($body, true);
    return $j['data']['api_token'] ?? '';
}

/* ── Fetch one page of products from API ── */
function fetchPage(string $token, int $page): array {
    $url = API_BASE . '/adminapi/external.External/searchlists?page=' . $page . '&limit=' . PAGE_SIZE;
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['apiToken: ' . $token],
        CURLOPT_TIMEOUT        => 30,
    ]);
    $body = curl_exec($ch); curl_close($ch);
    $j = json_decode($body, true);
    return [
        'items' => $j['data']['lists'] ?? [],
        'total' => (int)($j['data']['count'] ?? 0),
    ];
}

/* ── Upsert one product into huipi_goods + products ── */
function syncProduct(PDO $pdo, array $g, array $validCats): array {
    $result = ['hg'=>'skip','p'=>'skip'];

    // 1. Upsert into huipi_goods
    $upsertHG = $pdo->prepare("
        INSERT INTO huipi_goods
            (goods_id,site_id,goods_name,goods_type,sub_title,goods_cover,goods_image,
             goods_category,goods_mall_category,goods_desc,brand_id,unit,stock,
             sale_num,virtual_sale_num,status,audit_status,sort,is_free_shipping,
             delivery_money,gunit_max,gnum_midd,gunit_midd,gnum_min,gunit_min,
             quality,buy_num_min,cash_price_big,cash_price_small,tax_price,
             goods_code,is_synch,snowflake_id)
        VALUES
            (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
            goods_name=VALUES(goods_name), goods_cover=VALUES(goods_cover),
            goods_image=VALUES(goods_image), goods_mall_category=VALUES(goods_mall_category),
            stock=VALUES(stock), cash_price_small=VALUES(cash_price_small),
            cash_price_big=VALUES(cash_price_big), status=VALUES(status),
            update_time=UNIX_TIMESTAMP()
    ");
    $upsertHG->execute([
        $g['goods_id'], $g['site_id']??0, $g['goods_name']??'',
        $g['goods_type']??'real', $g['sub_title']??'',
        $g['goods_cover']??'', $g['goods_image']??'',
        $g['goods_category']??'', (int)($g['goods_mall_category']??0),
        $g['goods_desc']??'', (int)($g['brand_id']??0),
        $g['unit']??'', (int)($g['stock']??0),
        0, 0, (int)($g['status']??1), (int)($g['audit_status']??2),
        0, (int)($g['is_free_shipping']??1),
        $g['delivery_money']??'0.00',
        $g['gunit_max']??'', $g['gnum_midd']??'', $g['gunit_midd']??'',
        $g['gnum_min']??null, $g['gunit_min']??null,
        isset($g['quality']) ? (int)$g['quality'] : null,
        (int)($g['buy_num_min']??1),
        (float)($g['cash_price_big']??0), (float)($g['cash_price_small']??0),
        (float)($g['tax_price']??0),
        $g['goods_code']??'', (int)($g['is_synch']??1),
        $g['snowflake_id']??'',
    ]);
    $result['hg'] = $upsertHG->rowCount() > 0 ? 'upsert' : 'same';

    // 2. Insert into products if not exists
    $existing = $pdo->prepare("SELECT id FROM products WHERE goods_id=? LIMIT 1");
    $existing->execute([$g['goods_id']]);
    $existingId = $existing->fetchColumn();

    if ($existingId) {
        // Update price and stock in existing product
        $pdo->prepare("UPDATE products SET price=?, cash_price_small=?, updated_at=NOW() WHERE goods_id=?")
            ->execute([(float)($g['cash_price_small']??0), (float)($g['cash_price_small']??0), $g['goods_id']]);
        // Update SKU quantity and price
        $pdo->prepare("UPDATE product_skus SET price=?, quantity=?, updated_at=NOW() WHERE product_id=?")
            ->execute([(float)($g['cash_price_small']??0), (int)($g['stock']??0), $existingId]);
        $result['p'] = 'updated';
    } else {
        // Build images JSON
        $imgArr = [];
        $raw = $g['goods_image']??'';
        if ($raw && $raw[0]==='[') { $d=json_decode($raw,true); if(is_array($d)) $imgArr=$d; }
        elseif ($raw && str_contains($raw,',')) $imgArr=array_map('trim',explode(',',$raw));
        elseif ($raw) $imgArr=[$raw];
        elseif (!empty($g['goods_cover'])) $imgArr=[$g['goods_cover']];
        $imgs = json_encode(array_values(array_filter($imgArr)));

        $price = (float)($g['cash_price_small']??0);
        $qty   = (int)($g['stock']??0);

        $pdo->prepare("INSERT IGNORE INTO products
            (goods_id,brand_id,goods_name,goods_code,goods_mall_category,cash_price_small,
             gunit_max,gnum_min,gunit_min,quality,images,price,video,position,active,
             variables,tax_class_id,created_at,updated_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'',0,1,'[]',0,NOW(),NOW())"
        )->execute([
            $g['goods_id'], (int)($g['brand_id']??0), $g['goods_name']??'',
            $g['goods_code']??'', (int)($g['goods_mall_category']??0), $price,
            $g['gunit_max']??'', $g['gnum_min']??0, $g['gunit_min']??'',
            isset($g['quality'])?(int)$g['quality']:null,
            $imgs, $price,
        ]);
        $productId = (int)$pdo->lastInsertId();

        if ($productId) {
            $pdo->prepare("INSERT IGNORE INTO product_descriptions
                (product_id,locale,name,content,meta_title,meta_description,meta_keyword,created_at,updated_at)
                VALUES (?,'mn',?,'','','','',NOW(),NOW())"
            )->execute([$productId, $g['goods_name']??'']);

            $pdo->prepare("INSERT IGNORE INTO product_skus
                (product_id,variants,position,images,model,sku,price,origin_price,cost_price,quantity,is_default,created_at,updated_at)
                VALUES (?,'\"\"',0,?,'default',?,?,?,?,1,NOW(),NOW())"
            )->execute([$productId,$imgs,$g['goods_code']??'',$price,$price,$qty]);

            $catId = (int)($g['goods_mall_category']??0);
            if ($catId && isset($validCats[$catId])) {
                $pdo->prepare("INSERT IGNORE INTO product_categories (product_id,category_id) VALUES (?,?)")
                    ->execute([$productId, $catId]);
            }
            $result['p'] = 'inserted';
        }
    }
    return $result;
}

/* ── AJAX batch ── */
if (($_GET['action']??'') === 'batch') {
    header('Content-Type: application/json');
    set_time_limit(90);
    $page  = (int)($_GET['b'] ?? 1);
    $token = getApiToken();
    if (!$token) { echo json_encode(['error'=>'Cannot get API token']); exit; }

    $data  = fetchPage($token, $page);
    $items = $data['items'];
    $total = $data['total'];
    $totalPages = (int)ceil($total / PAGE_SIZE);

    if (!$items) {
        echo json_encode(['done'=>true,'inserted'=>0,'updated'=>0,'remaining'=>0,'page'=>$page,'totalPages'=>$totalPages]);
        exit;
    }

    $validCats = array_flip($pdo->query("SELECT id FROM categories")->fetchAll(PDO::FETCH_COLUMN));
    $inserted = $updated = 0;

    foreach ($items as $g) {
        $r = syncProduct($pdo, $g, $validCats);
        if ($r['p']==='inserted') $inserted++;
        elseif ($r['p']==='updated') $updated++;
    }

    $done = ($page >= $totalPages);
    if ($done) {
        // Clear cache
        foreach ([
            "$root/storage/framework/cache/data",
            "$root/storage/framework/views",
            "$root/bootstrap/cache"
        ] as $dir) {
            if (!is_dir($dir)) continue;
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS)) as $f)
                if ($f->isFile()) @unlink($f->getPathname());
        }
    }

    $remaining = max(0, $totalPages - $page);
    echo json_encode(['done'=>$done,'inserted'=>$inserted,'updated'=>$updated,
        'remaining'=>$remaining,'page'=>$page,'totalPages'=>$totalPages,'total'=>$total]);
    exit;
}

/* ── Stats for page ── */
$token    = getApiToken();
$first    = $token ? fetchPage($token, 1) : ['items'=>[],'total'=>0];
$apiTotal = $first['total'];
$dbTotal  = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE deleted_at IS NULL")->fetchColumn();
$hgTotal  = (int)$pdo->query("SELECT COUNT(*) FROM huipi_goods WHERE status=1")->fetchColumn();
$totalPages = (int)ceil($apiTotal / PAGE_SIZE);
?>
<!DOCTYPE html>
<html lang="mn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>DONA – API Sync</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#f0f4ff;font-family:'Segoe UI',sans-serif}
.top{background:#0f172a;color:#fff;padding:14px 28px;display:flex;align-items:center;gap:12px}
.top h5{margin:0;font-weight:700}
.card{border-radius:14px;border:none;box-shadow:0 2px 14px rgba(0,0,0,.07)}
.stat-box{background:#fff;border-radius:12px;padding:20px;text-align:center;box-shadow:0 1px 8px rgba(0,0,0,.06)}
.stat-num{font-size:2rem;font-weight:800;color:#0f172a}
.stat-diff{font-size:1.1rem;font-weight:700;color:#d97706}
#log{background:#0f172a;color:#94a3b8;border-radius:10px;padding:14px;font-family:monospace;font-size:.77rem;height:240px;overflow-y:auto}
.ok{color:#4ade80}.err{color:#f87171}.inf{color:#60a5fa}.warn{color:#fbbf24}
.btn-go{font-size:1rem;padding:12px 36px;border-radius:10px;font-weight:700}
</style>
</head>
<body>
<div class="top">
  <svg width="30" height="30" viewBox="0 0 24 24" fill="none"><rect width="24" height="24" rx="6" fill="white" fill-opacity=".15"/><path d="M4 12h16M12 4l8 8-8 8" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
  <h5>DONA — API Sync &nbsp;<small style="opacity:.6;font-size:.8rem">business.btk123.com → dona-trade.com</small></h5>
</div>

<div class="container py-4" style="max-width:720px">

  <div class="row g-3 mb-4">
    <div class="col-4"><div class="stat-box">
      <div class="stat-num"><?= number_format($apiTotal) ?></div>
      <div class="text-muted small mt-1">API дээрх бараа</div>
    </div></div>
    <div class="col-4"><div class="stat-box">
      <div class="stat-num"><?= number_format($dbTotal) ?></div>
      <div class="text-muted small mt-1">Сайт дахь бараа</div>
    </div></div>
    <div class="col-4"><div class="stat-box">
      <div class="stat-diff">+<?= number_format(max(0,$apiTotal-$dbTotal)) ?></div>
      <div class="text-muted small mt-1">Нэмэгдэх тоо</div>
    </div></div>
  </div>

  <div class="card p-4 mb-3">
    <p class="text-muted mb-3">
      API-аас <strong><?= number_format($apiTotal) ?></strong> бараа татаж,
      <strong><?= $totalPages ?></strong> хуудсаар sync хийнэ.
      Шинэ барааг нэмж, байгаа барааны үнэ болон нөөцийг шинэчилнэ.
    </p>

    <div id="prog-wrap" style="display:none" class="mb-3">
      <div class="d-flex justify-content-between mb-1">
        <small class="fw-semibold text-muted">Sync хийж байна...</small>
        <small id="pct-lbl">0%</small>
      </div>
      <div class="progress mb-1" style="height:10px">
        <div id="pbar" class="progress-bar progress-bar-striped progress-bar-animated"
             style="width:0%;background:#0f172a"></div>
      </div>
      <small id="stat-lbl" class="text-muted"></small>
    </div>

    <div class="d-flex gap-2">
      <button id="btn" class="btn btn-dark btn-go" onclick="startSync()">
        🔄&nbsp; API Sync эхлүүлэх
      </button>
      <button class="btn btn-outline-secondary" onclick="location.reload()">↺ Шинэчлэх</button>
    </div>
  </div>

  <div class="card p-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <small class="fw-semibold text-muted">Лог</small>
      <button class="btn btn-sm btn-outline-secondary py-0" onclick="document.getElementById('log').innerHTML=''">Цэвэрлэх</button>
    </div>
    <div id="log"><span class="inf">— Бэлэн байна. "API Sync эхлүүлэх" дарна уу. —</span></div>
  </div>

  <p class="text-center text-muted mt-3" style="font-size:.72rem">
    API: business.btk123.com &nbsp;|&nbsp; <?= $totalPages ?> хуудас × <?= PAGE_SIZE ?> бараа
  </p>
</div>

<script>
let running=false, totalIns=0, totalUpd=0, currentPage=1;
const totalPages=<?= $totalPages ?>;

function addLog(t,c){
  const el=document.getElementById('log');
  const d=document.createElement('div');
  if(c)d.className=c;
  d.textContent=new Date().toLocaleTimeString()+' '+t;
  el.appendChild(d);el.scrollTop=el.scrollHeight;
}
function setBar(page){
  const pct=Math.round(page/totalPages*100);
  document.getElementById('pbar').style.width=pct+'%';
  document.getElementById('pct-lbl').textContent=pct+'%';
  document.getElementById('stat-lbl').textContent=
    'Хуудас '+page+'/'+totalPages+' | Нэмсэн: +'+totalIns+' | Шинэчилсэн: '+totalUpd;
}

async function startSync(){
  if(running)return;
  running=true;
  currentPage=1; totalIns=0; totalUpd=0;
  document.getElementById('btn').disabled=true;
  document.getElementById('btn').innerHTML='⏳ Sync хийж байна...';
  document.getElementById('prog-wrap').style.display='block';
  document.getElementById('log').innerHTML='';
  addLog('API Sync эхэллээ... (Нийт '+totalPages+' хуудас)','inf');

  try{
    while(currentPage<=totalPages){
      const r=await fetch('/api-sync.php?key=dona2025&action=batch&b='+currentPage+'&_='+Date.now());
      if(!r.ok)throw new Error('HTTP '+r.status);
      const d=await r.json();
      if(d.error)throw new Error(d.error);

      totalIns+=d.inserted||0;
      totalUpd+=d.updated||0;

      const msg='Хуудас '+d.page+'/'+d.totalPages+
        ': +'+d.inserted+' нэмэгдлээ, '+d.updated+' шинэчлэгдлээ';
      addLog(msg, d.inserted>0?'ok':'warn');
      setBar(currentPage);
      currentPage++;

      if(d.done){
        addLog('✅ Бүгд дууслаа! Нийт нэмсэн: +'+totalIns+' | Шинэчилсэн: '+totalUpd,'ok');
        document.getElementById('pbar').classList.remove('progress-bar-animated');
        document.getElementById('pbar').style.background='#16a34a';
        document.getElementById('btn').innerHTML='✅ Дуусчилаа — хуудсыг шинэчлэх';
        document.getElementById('btn').className='btn btn-success btn-go';
        document.getElementById('btn').disabled=false;
        document.getElementById('btn').onclick=()=>location.reload();
        break;
      }
      await new Promise(r=>setTimeout(r,150));
    }
  }catch(e){
    addLog('❌ Алдаа: '+e.message,'err');
    document.getElementById('btn').disabled=false;
    document.getElementById('btn').innerHTML='🔄 Дахин оролдох';
    document.getElementById('btn').onclick=()=>startSync();
  }
  running=false;
}
</script>
</body>
</html>
