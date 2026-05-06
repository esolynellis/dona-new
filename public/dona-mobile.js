/**
 * DONA Mobile UI - Category chips + filter bar redesign
 * Only activates on mobile (<992px)
 */
(function () {
  'use strict';

  /* ── CSS ────────────────────────────────────────────── */
  var CSS = [
    '@media (max-width:991.98px){',

    /* ── Category chips bar ── */
    '.dona-chips-wrap{',
      'display:flex;align-items:center;',
      'overflow-x:auto;gap:8px;',
      'padding:10px 14px;',
      'background:#fff;',
      'border-bottom:1px solid #f0f0f0;',
      '-webkit-overflow-scrolling:touch;',
      'scrollbar-width:none;',
    '}',
    '.dona-chips-wrap::-webkit-scrollbar{display:none;}',

    '.dona-chip{',
      'display:inline-flex;align-items:center;justify-content:center;',
      'flex-shrink:0;white-space:nowrap;',
      'padding:5px 16px;min-height:34px;',
      'border-radius:999px;',
      'font-size:13px;font-weight:500;',
      'text-decoration:none!important;',
      'background:#f4f4f5;color:#52525b!important;',
      'border:1.5px solid transparent;',
      'transition:all .15s ease;',
    '}',
    '.dona-chip.is-active{',
      'background:#3B36DB;color:#fff!important;',
      'border-color:#3B36DB;',
    '}',

    /* ── Filter bar ── */
    '.page-categories .product-tool{',
      'padding:8px 0 6px;flex-wrap:nowrap;gap:8px;',
    '}',
    '.page-categories .product-tool .style-wrap{display:none!important;}',
    '.page-categories .product-tool .text-nowrap.text-secondary{',
      'font-size:12px!important;color:#71717a!important;',
      'white-space:nowrap;',
    '}',
    '.page-categories .product-tool .perpage-select{display:none!important;}',
    '.page-categories .product-tool .order-select{',
      'font-size:13px;border-radius:8px;',
      'border-color:#e4e4e7;color:#3f3f46;',
      'padding:5px 10px;min-width:0;',
      'margin-left:8px!important;',
    '}',
    '.page-categories .product-tool .right-per-page{',
      'flex:1;justify-content:space-between!important;',
      'max-width:100%;',
    '}',
    '.page-categories .product-tool .d-flex.align-items-center{',
      'flex-shrink:0;',
    '}',

    /* ── Breadcrumb ── */
    '.breadcrumb-wrap .breadcrumb{font-size:12px;}',
    '.breadcrumb-wrap .breadcrumb-item+.breadcrumb-item::before{',
      'font-size:10px;',
    '}',

    '}', /* end @media */
  ].join('');

  /* ── inject CSS ───────────────────────────────────────── */
  function injectCSS() {
    if (document.getElementById('dona-mobile-css')) return;
    var s = document.createElement('style');
    s.id = 'dona-mobile-css';
    s.textContent = CSS;
    document.head.appendChild(s);
  }

  /* ── build chips from hidden sidebar ────────────────── */
  function buildChips() {
    if (window.innerWidth >= 992) return;
    if (!document.querySelector('.page-categories')) return;
    if (document.querySelector('.dona-chips-wrap')) return;

    var catItems = document.querySelectorAll('#category-one > li');
    if (!catItems.length) return;

    var wrap = document.createElement('div');
    wrap.className = 'dona-chips-wrap';

    catItems.forEach(function (li) {
      var a = li.querySelector(':scope > a.category-href');
      if (!a) return;
      var chip = document.createElement('a');
      chip.className = 'dona-chip' + (li.classList.contains('active') ? ' is-active' : '');
      chip.href = a.href;
      chip.textContent = a.textContent.trim();
      wrap.appendChild(chip);
    });

    if (!wrap.children.length) return;

    /* Insert before .product-tool inside .right-column */
    var rightCol = document.querySelector('.right-column');
    if (!rightCol) return;
    var productTool = rightCol.querySelector('.product-tool');
    rightCol.insertBefore(wrap, productTool || rightCol.firstChild);

    /* Scroll active chip to centre */
    var active = wrap.querySelector('.is-active');
    if (active) {
      setTimeout(function () {
        wrap.scrollLeft =
          active.offsetLeft - wrap.offsetWidth / 2 + active.offsetWidth / 2;
      }, 60);
    }
  }

  /* ── run ─────────────────────────────────────────────── */
  injectCSS();
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', buildChips);
  } else {
    buildChips();
  }
})();
