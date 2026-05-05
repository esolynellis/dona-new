<?php
if (($_GET['key'] ?? '') !== 'dona2025') { http_response_code(403); die(); }

$root = '/www/wwwroot/dona-new';
$smoothPath = "$root/public/smooth.css";
$smooth = file_get_contents($smoothPath);

$addToCartCss = '
/* ═══════════════════════════════════════
   Саглагт нэмэх товч — үргэлж харагдана
   ═══════════════════════════════════════ */

/* Desktop: always show the button-wrap (was hover-only) */
.product-wrap .image .button-wrap {
  bottom: 0 !important;
  opacity: 1 !important;
  transition: background 0.15s ease !important;
}

/* Mobile: override the display:none and show it too */
@media (max-width: 992px) {
  .product-wrap .image .button-wrap {
    display: flex !important;
    bottom: 0 !important;
    opacity: 1 !important;
  }
  /* Smaller button height on mobile so it fits nicely */
  .product-wrap .image .button-wrap .btn-add-cart,
  .product-wrap .image .button-wrap button {
    font-size: 13px !important;
    padding: 9px 10px !important;
    min-height: 38px !important;
  }
  .product-wrap .image .button-wrap .btn-quick-view,
  .product-wrap .image .button-wrap .btn-wish {
    flex: 0 0 38px !important;
    font-size: 13px !important;
    min-height: 38px !important;
  }
}
';

// Remove old add-to-cart override if re-running
$smooth = preg_replace('/\/\* ═+\s*Саглагт нэмэх.*?═+ \*\/[\s\S]*?(?=\/\* ═|$)/u', '', $smooth);
$smooth = rtrim($smooth) . "\n" . $addToCartCss;

@unlink($smoothPath);
file_put_contents($smoothPath, $smooth);

// Clear view caches
$cleared = 0;
foreach (glob("$root/storage/framework/views/*.php") as $f) { if(@unlink($f)) $cleared++; }

header('Content-Type: application/json');
echo json_encode([
    'done' => true,
    'smooth_css_updated' => true,
    'cache_cleared' => $cleared,
    'change' => 'Add-to-cart button always visible (no hover needed)'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
