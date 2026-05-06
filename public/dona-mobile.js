/**
 * DONA Mobile UI v2
 * - Category chips bar (excludes "Ангилаагүй")
 * - Clean filter bar
 * - Modern side menu
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
      'flex:1;justify-content:space-between!important;max-width:100%;',
    '}',
    '.page-categories .product-tool .d-flex.align-items-center{flex-shrink:0;}',

    /* ── Breadcrumb ── */
    '.breadcrumb-wrap .breadcrumb{font-size:12px;}',

    '}', /* end @media */

    /* ── Side menu (offcanvas) – all screen sizes ── */
    '#offcanvas-mobile-menu{width:82%!important;}',

    '#offcanvas-mobile-menu .offcanvas-header{',
      'background:#3B36DB;',
      'padding:14px 16px;',
    '}',
    '#offcanvas-mobile-menu .offcanvas-title{',
      'color:#fff;font-weight:700;font-size:15px;',
    '}',
    '#offcanvas-mobile-menu .btn-close{',
      'filter:invert(1);opacity:0.85;',
    '}',

    '#offcanvas-mobile-menu .mobile-menu-wrap{padding:0;}',

    '#offcanvas-mobile-menu .accordion-item{',
      'border:none!important;',
      'border-bottom:1px solid #f4f4f5!important;',
    '}',

    '#offcanvas-mobile-menu .nav-item-text>a{',
      'font-size:14px;font-weight:500;',
      'color:#18181b;',
      'height:48px;padding-left:16px;',
    '}',
    '#offcanvas-mobile-menu .nav-item-text>span{',
      'border-left:none!important;',
      'width:40px;height:48px;',
      'color:#a1a1aa;',
    '}',
    '#offcanvas-mobile-menu .nav-item-text>span[aria-expanded="true"]{',
      'background:#f4f4f5;color:#3B36DB;',
    '}',

    /* Expanded accordion content area */
    '#offcanvas-mobile-menu .accordion-collapse{',
      'background:#fafafa;',
      'border-top:1px solid #f0f0f0!important;',
      'padding:4px 0!important;',
    '}',

    /* Child group items */
    '#offcanvas-mobile-menu .children-group .children-title{',
      'height:42px;padding:0 16px;',
      'font-size:13px;font-weight:500;color:#3f3f46;',
    '}',
    '#offcanvas-mobile-menu .ul-children .nav-link{',
      'font-size:13px;color:#71717a;',
      'padding:8px 24px!important;',
    '}',
    '#offcanvas-mobile-menu .ul-children .nav-link:hover{color:#3B36DB;}',

    /* Category section inside side menu */
    '.dona-menu-cats{padding:12px 16px 4px;}',
    '.dona-menu-cats-title{',
      'font-size:11px;font-weight:600;',
      'color:#a1a1aa;letter-spacing:.05em;',
      'text-transform:uppercase;',
      'margin-bottom:8px;',
    '}',
    '.dona-menu-cat-link{',
      'display:flex;align-items:center;',
      'padding:9px 0;',
      'font-size:14px;color:#18181b;',
      'text-decoration:none;',
      'border-bottom:1px solid #f4f4f5;',
    '}',
    '.dona-menu-cat-link:last-child{border-bottom:none;}',
    '.dona-menu-cat-link.is-active{color:#3B36DB;font-weight:600;}',
    '.dona-menu-cat-link .dona-cat-arrow{',
      'margin-left:auto;color:#d4d4d8;font-size:12px;',
    '}',
  ].join('');

  /* ── inject CSS ───────────────────────────────────────── */
  function injectCSS() {
    if (document.getElementById('dona-mobile-css')) return;
    var s = document.createElement('style');
    s.id = 'dona-mobile-css';
    s.textContent = CSS;
    document.head.appendChild(s);
  }

  /* ── Skip list for chips & side menu ─────────────────── */
  // IDs or name fragments to hide (Ангилаагүй = uncategorized)
  var SKIP_SLUGS = ['1000101'];
  var SKIP_NAMES = ['Ангилаагүй'];

  function shouldSkip(href, name) {
    for (var i = 0; i < SKIP_SLUGS.length; i++) {
      if (href && href.indexOf(SKIP_SLUGS[i]) !== -1) return true;
    }
    for (var j = 0; j < SKIP_NAMES.length; j++) {
      if (name && name.trim() === SKIP_NAMES[j]) return true;
    }
    return false;
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
      var name = a.textContent.trim();
      if (shouldSkip(a.href, name)) return;   // ← skip Ангилаагүй

      var chip = document.createElement('a');
      chip.className = 'dona-chip' + (li.classList.contains('active') ? ' is-active' : '');
      chip.href = a.href;
      chip.textContent = name;
      wrap.appendChild(chip);
    });

    if (!wrap.children.length) return;

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

  /* ── inject category list inside side menu ─────────── */
  function buildMenuCats() {
    var menuBody = document.querySelector('#offcanvas-mobile-menu .mobile-menu-wrap');
    if (!menuBody) return;
    if (menuBody.querySelector('.dona-menu-cats')) return;

    var catItems = document.querySelectorAll('#category-one > li');
    if (!catItems.length) return;

    var section = document.createElement('div');
    section.className = 'dona-menu-cats';

    var title = document.createElement('div');
    title.className = 'dona-menu-cats-title';
    title.textContent = 'Ангилал';
    section.appendChild(title);

    catItems.forEach(function (li) {
      var a = li.querySelector(':scope > a.category-href');
      if (!a) return;
      var name = a.textContent.trim();
      if (shouldSkip(a.href, name)) return;   // ← skip Ангилаагүй

      var link = document.createElement('a');
      link.className = 'dona-menu-cat-link' + (li.classList.contains('active') ? ' is-active' : '');
      link.href = a.href;
      link.innerHTML = name + '<span class="dona-cat-arrow">›</span>';
      section.appendChild(link);
    });

    /* Prepend category section before accordion menu */
    var accordion = menuBody.querySelector('#menu-accordion');
    menuBody.insertBefore(section, accordion);
  }

  /* ── run ─────────────────────────────────────────────── */
  injectCSS();
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      buildChips();
      buildMenuCats();
    });
  } else {
    buildChips();
    buildMenuCats();
  }
})();
