<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die('Forbidden'); }

/* ── Handle AJAX: import specific IDs ── */
if (($_GET['action'] ?? '') === 'import_ids') {
    header('Content-Type: application/json');
    set_time_limit(120);
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

    $body = json_decode(file_get_contents('php://input'), true);
    $ids  = array_map('intval', $body['ids'] ?? []);
    if (!$ids) { echo json_encode(['inserted'=>0,'activated'=>0,'skipped'=>0]); exit; }

    $ph = implode(',', array_fill(0, count($ids), '?'));

    // 1. Find which IDs are in huipi_goods but NOT yet in products → insert them
    $missing = $pdo->prepare(
        "SELECT hg.* FROM huipi_goods hg
         LEFT JOIN products p ON p.goods_id = hg.goods_id
         WHERE hg.goods_id IN ($ph) AND hg.status=1 AND p.goods_id IS NULL"
    );
    $missing->execute($ids);
    $toInsert = $missing->fetchAll(PDO::FETCH_ASSOC);

    $validCats = array_flip($pdo->query("SELECT id FROM categories")->fetchAll(PDO::FETCH_COLUMN));
    $ins  = $pdo->prepare("INSERT IGNORE INTO products (goods_id,brand_id,goods_name,goods_code,goods_mall_category,cash_price_small,gunit_max,gnum_min,gunit_min,quality,images,price,video,position,active,variables,tax_class_id,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'',0,1,'[]',0,NOW(),NOW())");
    $insD = $pdo->prepare("INSERT IGNORE INTO product_descriptions (product_id,locale,name,content,meta_title,meta_description,meta_keyword,created_at,updated_at) VALUES (?,'mn',?,'','','','',NOW(),NOW())");
    $insS = $pdo->prepare("INSERT IGNORE INTO product_skus (product_id,variants,position,images,model,sku,price,origin_price,cost_price,quantity,is_default,created_at,updated_at) VALUES (?,'\"\"',0,?,'default',?,?,?,?,1,NOW(),NOW())");
    $insC = $pdo->prepare("INSERT IGNORE INTO product_categories (product_id,category_id) VALUES (?,?)");

    $inserted = 0;
    foreach ($toInsert as $g) {
        $imgArr=[];
        $raw=$g['goods_image']??'';
        if($raw&&$raw[0]==='['){$d=json_decode($raw,true);if(is_array($d))$imgArr=$d;}
        elseif($raw&&str_contains($raw,','))$imgArr=array_map('trim',explode(',',$raw));
        elseif($raw)$imgArr=[$raw];
        elseif(!empty($g['goods_cover']))$imgArr=[$g['goods_cover']];
        $imgs=json_encode(array_values(array_filter($imgArr)));
        $price=(float)($g['cash_price_small']??0);
        $ins->execute([$g['goods_id'],(int)($g['brand_id']??0),$g['goods_name']??'',$g['goods_code']??'',(int)($g['goods_mall_category']??0),$price,$g['gunit_max']??'',$g['gnum_min']??0,$g['gunit_min']??'',(int)($g['quality']??0),$imgs,$price]);
        if(!$ins->rowCount()) continue;
        $pid=(int)$pdo->lastInsertId(); $inserted++;
        $insD->execute([$pid,$g['goods_name']??'']);
        $insS->execute([$pid,$imgs,$g['goods_code']??'',$price,$price,(int)($g['stock']??0)]);
        $catId=(int)($g['goods_mall_category']??0);
        if($catId&&isset($validCats[$catId]))$insC->execute([$pid,$catId]);
    }

    // 2. Activate any of those IDs that already exist but are inactive
    $activated = 0;
    if ($ids) {
        $act = $pdo->prepare("UPDATE products SET active=1 WHERE id IN ($ph) AND active=0");
        $act->execute($ids);
        $activated = $act->rowCount();
    }

    $remaining = (int)$pdo->query(
        "SELECT COUNT(*) FROM huipi_goods hg LEFT JOIN products p ON p.goods_id=hg.goods_id WHERE hg.status=1 AND p.goods_id IS NULL"
    )->fetchColumn();

    echo json_encode(['inserted'=>$inserted,'activated'=>$activated,'remaining'=>$remaining,'skipped'=>count($ids)-$inserted-$activated]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="mn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>DONA Bookmarklet</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#f0f4ff;font-family:'Segoe UI',sans-serif}
.top{background:#1a237e;color:#fff;padding:14px 28px;display:flex;align-items:center;gap:12px}
.top h5{margin:0;font-weight:700}
.card{border-radius:14px;border:none;box-shadow:0 2px 14px rgba(0,0,0,.07)}
.bm-box{background:#1e293b;border-radius:10px;padding:14px 16px;word-break:break-all;font-size:.72rem;color:#94a3b8;font-family:monospace;max-height:120px;overflow-y:auto;cursor:pointer;transition:background .2s}
.bm-box:hover{background:#263248}
.step{display:flex;gap:12px;align-items:flex-start;margin-bottom:14px}
.step-num{background:#1a237e;color:#fff;border-radius:50%;width:26px;height:26px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;flex-shrink:0;margin-top:2px}
</style>
</head>
<body>
<div class="top">
  <svg width="30" height="30" viewBox="0 0 24 24" fill="none"><rect width="24" height="24" rx="6" fill="white" fill-opacity=".15"/><path d="M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z" stroke="white" stroke-width="1.5"/><path d="M8 12h8M12 8v8" stroke="white" stroke-width="2" stroke-linecap="round"/></svg>
  <h5>DONA — Admin Bookmarklet тохиргоо</h5>
</div>

<div class="container py-4" style="max-width:680px">

  <div class="card p-4 mb-3">
    <h6 class="fw-bold mb-3">📌 Хэрхэн тохируулах</h6>

    <div class="step">
      <div class="step-num">1</div>
      <div>
        <strong>Доорх кодыг хуулах</strong> — кодон дээр дарвал хуулагдана
      </div>
    </div>

    <div class="bm-box mb-3" id="bm-code" title="Дарж хуулах" onclick="copyBm()">
      <!-- Filled by JS below -->
    </div>
    <button class="btn btn-sm btn-outline-primary mb-3" onclick="copyBm()">📋 Хуулах</button>
    <div id="copy-msg" class="text-success small" style="display:none">✅ Хуулагдлаа!</div>

    <div class="step">
      <div class="step-num">2</div>
      <div>
        Chrome-д <strong>bookmark</strong> нэмэх:<br>
        <small class="text-muted">Ctrl+D → "Нэр" хэсэгт <code>DONA Import</code> бичих → URL хэсгийн <strong>бүх текстийг устгаад</strong> хуулсан кодоо буулгах → Хадгалах</small>
      </div>
    </div>

    <div class="step">
      <div class="step-num">3</div>
      <div>
        Admin дээрх <strong>Бүтээгдэхүүн хуудас</strong> руу очих:<br>
        <a href="/admin/products" target="_blank" class="text-primary small">dona-trade.com/admin/products</a>
      </div>
    </div>

    <div class="step">
      <div class="step-num">4</div>
      <div>
        Хадгалсан <strong>"DONA Import"</strong> bookmark-оо дарах →<br>
        <small class="text-muted">Баруун дээд буланд хяналтын цонх гарч ирнэ — <strong>"▶ Бүгдийг нэмэх"</strong> товч дарахад автоматаар хуудас хуудсаар явж бүх бараа нэмнэ.</small>
      </div>
    </div>
  </div>

  <div class="card p-4 mb-3">
    <h6 class="fw-bold mb-2">🔍 Яг юу хийдэг вэ?</h6>
    <ul class="text-muted small mb-0">
      <li>Одоо байгаа admin хуудсан дээрх бүх барааны <strong>ID-г уншина</strong></li>
      <li>Тэдгээр ID-г server-д илгээж <strong>huipi_goods-с products рүү нэмнэ</strong></li>
      <li>Inactive байгаа барааг <strong>идэвхижүүлнэ</strong> (active=1)</li>
      <li>Дараагийн хуудас руу <strong>автоматаар шилжинэ</strong></li>
      <li>Бүх хуудас дуусахад зогсоно</li>
    </ul>
  </div>

  <div class="card p-4">
    <h6 class="fw-bold mb-2">💡 Шууд ажиллуулах (Console хувилбар)</h6>
    <p class="text-muted small mb-2">Bookmark хийхийн оронд Chrome DevTools (F12) → Console дээр paste хийж ажиллуулж болно:</p>
    <div class="bm-box" id="console-code" onclick="copyConsole()"><!-- filled by JS --></div>
    <button class="btn btn-sm btn-outline-secondary mt-2" onclick="copyConsole()">📋 Console код хуулах</button>
  </div>

</div>

<script>
const API = '/dona-bookmarklet.php?key=dona2025&action=import_ids';

// The actual bookmarklet code (minified)
const bm = `javascript:(function(){
if(document.getElementById('__dp'))return;
const css=document.createElement('style');
css.textContent='#__dp{position:fixed;top:16px;right:16px;z-index:2147483647;background:#fff;border-radius:14px;box-shadow:0 6px 30px rgba(0,0,0,.22);padding:18px 20px;width:290px;font-family:system-ui,sans-serif;font-size:13px;line-height:1.5}#__dp h4{margin:0 0 10px;font-size:14px;font-weight:800;color:#1a237e}#__dp-bar-bg{height:8px;border-radius:8px;background:#e5e7eb;margin:8px 0}#__dp-bar{height:8px;border-radius:8px;background:#1a237e;width:0%;transition:width .5s}#__dp-log{max-height:140px;overflow-y:auto;font-size:11px;margin:6px 0;padding:6px;background:#f8fafc;border-radius:6px}';
document.head.appendChild(css);
const el=document.createElement('div');
el.id='__dp';
el.innerHTML='<h4>📦 DONA Импорт</h4><div id="__dp-st" style="color:#64748b;font-size:12px">Бэлэн байна...</div><div id="__dp-bar-bg"><div id="__dp-bar"></div></div><div id="__dp-pct" style="font-size:11px;color:#64748b;text-align:right;margin-top:-4px">0%</div><div id="__dp-log"></div><button id="__dp-go" style="width:100%;padding:9px;background:#1a237e;color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:700;font-size:13px;margin-top:6px">▶ Бүгдийг нэмэх</button><button onclick="document.getElementById(\'__dp\').remove()" style="width:100%;padding:5px;background:#f1f5f9;border:none;border-radius:6px;cursor:pointer;font-size:11px;margin-top:5px;color:#666">✕ Хаах</button>';
document.body.appendChild(el);

function log(t,c){const d=document.getElementById('__dp-log');d.innerHTML+='<div style="color:'+(c||'#555')+'">'+new Date().toLocaleTimeString()+' '+t+'</div>';d.scrollTop=d.scrollHeight;}
function setBar(p){document.getElementById('__dp-bar').style.width=p+'%';document.getElementById('__dp-pct').textContent=Math.round(p)+'%';}
function setStatus(t){document.getElementById('__dp-st').textContent=t;}

function getIds(){
  const ids=new Set();
  document.querySelectorAll('table tbody tr,tbody tr').forEach(r=>{
    const tds=r.querySelectorAll('td');
    for(let i=0;i<Math.min(4,tds.length);i++){
      const n=parseInt((tds[i].innerText||'').trim());
      if(n>100&&n<10000000){ids.add(n);break;}
    }
  });
  return [...ids];
}

function getMaxPage(){
  let m=1;
  document.querySelectorAll('[href*="page="]').forEach(a=>{
    const x=parseInt((a.href||'').match(/page=(\d+)/)?.[1]||0);
    if(x>m)m=x;
  });
  return m;
}

function getCurrentPage(){
  return parseInt(new URL(location.href).searchParams.get('page')||1);
}

let totalAdded=0;
let maxPage=getMaxPage();
let currentPage=getCurrentPage();

document.getElementById('__dp-go').onclick=async function(){
  this.disabled=true;
  this.textContent='⏳ Ажиллаж байна...';
  document.getElementById('__dp-log').innerHTML='';
  totalAdded=parseInt(sessionStorage.getItem('__dp_added')||0);
  maxPage=getMaxPage();
  currentPage=getCurrentPage();
  setStatus('Хуудас '+currentPage+'/'+maxPage);

  const ids=getIds();
  log('Хуудас '+currentPage+': '+ids.length+' ID олдлоо','#2563eb');

  if(ids.length){
    try{
      const r=await fetch('${API}',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({ids})});
      const d=await r.json();
      const added=(d.inserted||0)+(d.activated||0);
      totalAdded+=added;
      sessionStorage.setItem('__dp_added',totalAdded);
      if(added>0)log('+'+added+' бараа нэмэгдлээ  (үлдсэн:'+d.remaining+')','#16a34a');
      else log('Энэ хуудсан дээр нэмэх зүйл алга','#94a3b8');
    }catch(e){log('Алдаа: '+e.message,'#ef4444');}
  }

  setBar(Math.round(currentPage/maxPage*100));

  if(currentPage<maxPage){
    log('→ Дараагийн хуудас руу шилжиж байна...','#2563eb');
    setTimeout(()=>{
      sessionStorage.setItem('__dp_auto',1);
      const u=new URL(location.href);u.searchParams.set('page',currentPage+1);location.href=u.toString();
    },700);
  }else{
    sessionStorage.removeItem('__dp_auto');
    sessionStorage.removeItem('__dp_added');
    log('🎉 Бүгд дууслаа! Нийт нэмсэн: '+totalAdded,'#16a34a');
    setStatus('✅ Дуусчилаа!');
    setBar(100);
    document.getElementById('__dp-go').textContent='✅ Дуусчилаа';
    document.getElementById('__dp-go').style.background='#16a34a';
    document.getElementById('__dp-go').disabled=false;
    document.getElementById('__dp-go').onclick=()=>location.reload();
  }
};

// Auto-continue across page navigations
if(sessionStorage.getItem('__dp_auto')){
  document.getElementById('__dp-go').click();
}
})();`.replace(/\n\s*/g,' ').replace(/\s{2,}/g,' ');

document.getElementById('bm-code').textContent = bm;
document.getElementById('console-code').textContent = bm.replace(/^javascript:/,'');

function copyBm(){
  navigator.clipboard.writeText(bm).then(()=>{
    document.getElementById('copy-msg').style.display='block';
    setTimeout(()=>document.getElementById('copy-msg').style.display='none',2000);
  });
}
function copyConsole(){
  const code = bm.replace(/^javascript:/,'');
  navigator.clipboard.writeText(code).then(()=>alert('Console код хуулагдлаа! F12 → Console дээр paste хийнэ үү.'));
}
</script>
</body>
</html>
