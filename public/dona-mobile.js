/**
 * DONA Mobile UI v8
 * - Bottom nav bar: Нүүр | Ангилал | Сагс
 * - Dedicated category slide-in drawer (all pages)
 * - Chips on category pages (unchanged)
 * - Hamburger shows original nav (no longer overridden)
 */
(function () {
  'use strict';

  var SKIP_IDS   = ['1000101'];
  var SKIP_NAMES = ['Ангилаагүй'];

  function shouldSkip(href, name) {
    for (var i = 0; i < SKIP_IDS.length; i++) {
      if (href && href.indexOf(SKIP_IDS[i]) !== -1) return true;
    }
    for (var j = 0; j < SKIP_NAMES.length; j++) {
      if (name && name.trim() === SKIP_NAMES[j]) return true;
    }
    return false;
  }

  var CUR = window.location.pathname;

  function isCurrentHref(href) {
    if (!href || href === '#' || href.charAt(0) === '#') return false;
    try { return new URL(href, window.location.origin).pathname === CUR; }
    catch (e) { return false; }
  }

  /* ════════════════════════════════════════════════════════
     CSS
  ════════════════════════════════════════════════════════ */
  var CSS = [
    '@media (max-width:991.98px){',

    /* body padding so content isn't hidden under bottom nav */
    'body{padding-bottom:58px!important;}',

    /* ── chip bar ── */
    '.dona-chips-wrap{display:flex!important;align-items:center;overflow-x:auto;gap:6px;',
      'padding:8px 12px;background:#fff;border-bottom:1px solid #ebebeb;',
      '-webkit-overflow-scrolling:touch;scrollbar-width:none;}',
    '.dona-chips-wrap::-webkit-scrollbar{display:none;}',
    '.dona-chip{display:inline-flex!important;align-items:center;justify-content:center;',
      'flex-shrink:0;white-space:nowrap;padding:4px 12px;min-height:28px;',
      'border-radius:999px;font-size:12px;font-weight:500;',
      'text-decoration:none!important;background:#f5f5f7;color:#444!important;',
      'border:1.5px solid transparent;}',
    '.dona-chip.is-active{background:#3B36DB!important;color:#fff!important;border-color:#3B36DB;}',

    /* ── filter bar ── */
    '.page-categories .product-tool{padding:6px 0;flex-wrap:nowrap;gap:6px;}',
    '.page-categories .product-tool .style-wrap{display:none!important;}',
    '.page-categories .product-tool .text-nowrap.text-secondary{font-size:11px!important;color:#888!important;white-space:nowrap;}',
    '.page-categories .product-tool .perpage-select{display:none!important;}',
    '.page-categories .product-tool .order-select{font-size:12px;border-radius:7px;border-color:#e0e0e0;color:#444;padding:4px 8px;min-width:0;margin-left:6px!important;}',
    '.page-categories .product-tool .right-per-page{flex:1;justify-content:space-between!important;max-width:100%;}',
    '.page-categories .product-tool .d-flex.align-items-center{flex-shrink:0;}',

    /* ── breadcrumb ── */
    '.breadcrumb-wrap{margin-bottom:4px!important;}',
    '.breadcrumb-wrap .breadcrumb{font-size:11px;margin-bottom:0;}',

    /* ── bottom nav ── */
    '.dona-bottom-nav{',
      'position:fixed;bottom:0;left:0;right:0;height:56px;',
      'background:#fff;border-top:1px solid #e8e8e8;',
      'display:flex;align-items:stretch;',
      'z-index:1040;',
      'box-shadow:0 -2px 10px rgba(0,0,0,.08);',
    '}',
    '.dona-bn-item{',
      'flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;',
      'font-size:10px;color:#888!important;cursor:pointer;',
      'border:none;background:none;padding:6px 0 4px;text-decoration:none!important;',
      'gap:2px;-webkit-tap-highlight-color:transparent;',
    '}',
    '.dona-bn-item.active{color:#3B36DB!important;}',
    '.dona-bn-item svg{display:block;}',
    '.dona-bn-label{font-size:10px;font-weight:500;line-height:1;}',
    '.dona-bn-badge{',
      'position:absolute;top:-4px;right:-5px;',
      'background:#e53;color:#fff;font-size:9px;font-weight:700;',
      'min-width:15px;height:15px;border-radius:99px;',
      'display:flex;align-items:center;justify-content:center;padding:0 3px;',
      'line-height:1;',
    '}',
    '.dona-bn-icon-wrap{position:relative;width:24px;height:24px;display:flex;align-items:center;justify-content:center;}',

    /* ── category drawer overlay ── */
    '.dona-drawer{',
      'position:fixed;inset:0;z-index:1050;',
      'pointer-events:none;',
    '}',
    '.dona-drawer.open{pointer-events:all;}',
    '.dona-drawer-backdrop{',
      'position:absolute;inset:0;background:rgba(0,0,0,.45);',
      'opacity:0;transition:opacity .25s ease;',
    '}',
    '.dona-drawer.open .dona-drawer-backdrop{opacity:1;}',
    '.dona-drawer-panel{',
      'position:absolute;top:0;bottom:0;left:0;',
      'width:82%;max-width:320px;',
      'background:#fff;display:flex;flex-direction:column;',
      'transform:translateX(-100%);transition:transform .25s ease;',
      'overflow:hidden;',
    '}',
    '.dona-drawer.open .dona-drawer-panel{transform:translateX(0);}',

    /* drawer header */
    '.dona-drawer-header{',
      'display:flex;align-items:center;justify-content:space-between;',
      'background:#3B36DB;padding:14px 16px;flex-shrink:0;',
    '}',
    '.dona-drawer-title{color:#fff!important;font-size:15px;font-weight:700;text-decoration:none!important;}',
    '.dona-drawer-close{',
      'background:none;border:none;color:#fff;font-size:20px;line-height:1;',
      'padding:0;cursor:pointer;opacity:.85;',
    '}',

    /* drawer body – scrollable */
    '.dona-drawer-body{flex:1;overflow-y:auto;-webkit-overflow-scrolling:touch;}',

    /* category rows inside drawer */
    '.dona-dcat-parent{',
      'display:flex!important;align-items:center;',
      'padding:12px 16px!important;',
      'font-size:13px!important;font-weight:600!important;color:#111!important;',
      'border-bottom:1px solid #f0f0f0!important;',
      'cursor:pointer;background:#fff!important;text-decoration:none!important;',
    '}',
    '.dona-dcat-parent.active{color:#3B36DB!important;background:#f5f4ff!important;}',
    '.dona-dcat-parent .dcat-arr{',
      'margin-left:auto;font-size:18px;color:#ccc!important;',
      'transition:transform .2s;display:inline-block;flex-shrink:0;',
    '}',
    '.dona-dcat-parent.open .dcat-arr{transform:rotate(90deg);color:#3B36DB!important;}',

    '.dona-dcat-children{display:none!important;background:#f8f8f8;}',
    '.dona-dcat-children.open{display:block!important;}',

    '.dona-dcat-child{',
      'display:flex!important;align-items:center;',
      'padding:9px 16px 9px 28px!important;',
      'font-size:12px!important;color:#555!important;',
      'text-decoration:none!important;',
      'border-bottom:1px solid #efefef!important;',
      'background:#f8f8f8!important;',
    '}',
    '.dona-dcat-child.active{color:#3B36DB!important;font-weight:600!important;background:#eeeeff!important;}',
    '.dona-dcat-child::before{content:"›";margin-right:8px;color:#bbb!important;}',
    '.dona-dcat-child.active::before{color:#3B36DB!important;}',

    '}', /* end @media */
  ].join('');

  function injectCSS() {
    var old = document.getElementById('dona-mobile-css');
    if (old) old.remove();
    var s = document.createElement('style');
    s.id = 'dona-mobile-css';
    s.textContent = CSS;
    document.head.appendChild(s);
  }

  /* ════════════════════════════════════════════════════════
     SVG ICONS
  ════════════════════════════════════════════════════════ */
  function svgHome(active) {
    var c = active ? '#3B36DB' : '#888';
    return '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="' + c + '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5L12 3l9 6.5V20a1 1 0 01-1 1H4a1 1 0 01-1-1V9.5z"/><path d="M9 21V12h6v9"/></svg>';
  }
  function svgGrid(active) {
    var c = active ? '#3B36DB' : '#888';
    return '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="' + c + '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>';
  }
  function svgCart(active) {
    var c = active ? '#3B36DB' : '#888';
    return '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="' + c + '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>';
  }

  /* ════════════════════════════════════════════════════════
     CATEGORY DATA
  ════════════════════════════════════════════════════════ */
  function getCategoryData() {
    var items = [];

    /* ── source 1: #category-one (category pages – richest data) ── */
    var catOne = document.querySelectorAll('#category-one > li');
    if (catOne.length) {
      catOne.forEach(function (li) {
        var a = li.querySelector(':scope > a.category-href');
        if (!a) return;
        var name = a.textContent.trim();
        var href = a.href;
        if (shouldSkip(href, name)) return;

        var childUl  = li.querySelector(':scope > ul.accordion-collapse');
        var children = [];
        if (childUl) {
          childUl.querySelectorAll(':scope > li.child-category').forEach(function (cli) {
            var ca = cli.querySelector(':scope > a.category-href');
            if (!ca) return;
            children.push({ name: ca.textContent.trim(), href: ca.href, active: cli.classList.contains('active') });
          });
        }

        var isActive = li.classList.contains('active') || !!li.querySelector('li.active');
        items.push({ name: name, href: href, active: isActive, children: children });
      });
      return items;
    }

    /* ── source 2: #menu-accordion (all pages) ── */
    document.querySelectorAll('#menu-accordion > .accordion-item').forEach(function (item) {
      var topLink = item.querySelector(':scope > .nav-item-text > a.nav-link');
      if (!topLink) return;
      var name = topLink.textContent.trim();
      var href = topLink.getAttribute('href') || '';
      if (!name) return;
      /* skip hash-only items */
      if (!href || href === '#' || (href.charAt(0) === '#' && href.length > 1 && href.indexOf('flush') !== -1)) {
        /* has children sub-menu – still show if name is useful */
      }
      if (shouldSkip(href, name)) return;

      var children = [];
      item.querySelectorAll('.ul-children .nav-item a.nav-link').forEach(function (ca) {
        var cn = ca.textContent.trim();
        var ch = ca.getAttribute('href') || '#';
        if (!cn || shouldSkip(ch, cn)) return;
        children.push({ name: cn, href: ch, active: isCurrentHref(ch) });
      });

      var isActive = isCurrentHref(href) || children.some(function (c) { return c.active; });
      items.push({ name: name, href: href.charAt(0) === '#' ? null : href, active: isActive, children: children });
    });

    return items;
  }

  /* ════════════════════════════════════════════════════════
     CATEGORY DRAWER
  ════════════════════════════════════════════════════════ */
  var drawerEl = null;

  function buildDrawer() {
    if (drawerEl) return;

    /* overlay */
    drawerEl = document.createElement('div');
    drawerEl.className = 'dona-drawer';
    drawerEl.id = 'dona-drawer';

    var backdrop = document.createElement('div');
    backdrop.className = 'dona-drawer-backdrop';
    backdrop.addEventListener('click', closeDrawer);
    drawerEl.appendChild(backdrop);

    /* panel */
    var panel = document.createElement('div');
    panel.className = 'dona-drawer-panel';

    /* header */
    var hdr = document.createElement('div');
    hdr.className = 'dona-drawer-header';
    var title = document.createElement('span');
    title.className = 'dona-drawer-title';
    title.textContent = 'Ангилал';
    var closeBtn = document.createElement('button');
    closeBtn.className = 'dona-drawer-close';
    closeBtn.innerHTML = '&times;';
    closeBtn.addEventListener('click', closeDrawer);
    hdr.appendChild(title);
    hdr.appendChild(closeBtn);
    panel.appendChild(hdr);

    /* body */
    var body = document.createElement('div');
    body.className = 'dona-drawer-body';
    body.id = 'dona-drawer-body';
    panel.appendChild(body);

    drawerEl.appendChild(panel);
    document.body.appendChild(drawerEl);

    populateDrawer(body);
  }

  function populateDrawer(body) {
    var cats = getCategoryData();
    if (!cats.length) {
      body.innerHTML = '<div style="padding:24px 16px;color:#aaa;font-size:13px;">Ангилал олдсонгүй</div>';
      return;
    }

    cats.forEach(function (cat) {
      var hasChildren = cat.children && cat.children.length > 0;

      /* parent row */
      var row = document.createElement('div');
      row.className = 'dona-dcat-parent' + (cat.active ? ' active' : '') + (cat.active && hasChildren ? ' open' : '');

      var nameSpan = document.createElement('span');
      nameSpan.textContent = cat.name;
      nameSpan.style.flex = '1';
      row.appendChild(nameSpan);

      if (hasChildren) {
        var arr = document.createElement('span');
        arr.className = 'dcat-arr';
        arr.textContent = '›';
        row.appendChild(arr);
      }

      body.appendChild(row);

      if (!hasChildren) {
        /* navigate directly */
        if (cat.href) {
          row.addEventListener('click', function () { window.location.href = cat.href; });
        }
        return;
      }

      /* children list */
      var childList = document.createElement('div');
      childList.className = 'dona-dcat-children' + (cat.active ? ' open' : '');

      cat.children.forEach(function (child) {
        var cl = document.createElement('a');
        cl.className = 'dona-dcat-child' + (child.active ? ' active' : '');
        cl.href = child.href;
        cl.textContent = child.name;
        childList.appendChild(cl);
      });

      body.appendChild(childList);

      /* toggle */
      row.addEventListener('click', function () {
        var isOpen = childList.classList.contains('open');
        /* close all siblings */
        body.querySelectorAll('.dona-dcat-children.open').forEach(function (el) { el.classList.remove('open'); });
        body.querySelectorAll('.dona-dcat-parent.open').forEach(function (el) { el.classList.remove('open'); });
        if (!isOpen) {
          childList.classList.add('open');
          row.classList.add('open');
        }
      });
    });
  }

  function openDrawer() {
    buildDrawer();
    requestAnimationFrame(function () {
      drawerEl.classList.add('open');
    });
    document.body.style.overflow = 'hidden';
  }

  function closeDrawer() {
    if (!drawerEl) return;
    drawerEl.classList.remove('open');
    document.body.style.overflow = '';
  }

  /* ════════════════════════════════════════════════════════
     BOTTOM NAV BAR
  ════════════════════════════════════════════════════════ */
  function buildBottomNav() {
    if (document.querySelector('.dona-bottom-nav')) return;
    if (window.innerWidth >= 992) return;

    /* detect active section */
    var isHome = (CUR === '/' || CUR === '');
    var isCat  = !!document.querySelector('.page-categories');

    /* find cart link from existing header */
    var cartHref = '/cart';
    var headerCartLink = document.querySelector('a[href*="cart"]');
    if (headerCartLink) cartHref = headerCartLink.getAttribute('href');

    var nav = document.createElement('nav');
    nav.className = 'dona-bottom-nav';

    /* ── Home ── */
    var homeBtn = document.createElement('a');
    homeBtn.className = 'dona-bn-item' + (isHome ? ' active' : '');
    homeBtn.href = '/';
    homeBtn.innerHTML =
      '<div class="dona-bn-icon-wrap">' + svgHome(isHome) + '</div>' +
      '<span class="dona-bn-label">Нүүр</span>';
    nav.appendChild(homeBtn);

    /* ── Categories ── */
    var catBtn = document.createElement('button');
    catBtn.className = 'dona-bn-item' + (isCat ? ' active' : '');
    catBtn.type = 'button';
    catBtn.innerHTML =
      '<div class="dona-bn-icon-wrap">' + svgGrid(isCat) + '</div>' +
      '<span class="dona-bn-label">Ангилал</span>';
    catBtn.addEventListener('click', openDrawer);
    nav.appendChild(catBtn);

    /* ── Cart ── */
    var cartBtn = document.createElement('a');
    cartBtn.className = 'dona-bn-item';
    cartBtn.href = cartHref;

    /* cart count badge */
    var cartBadge = document.createElement('span');
    cartBadge.className = 'dona-bn-badge';
    cartBadge.id = 'dona-cart-count';
    cartBadge.style.display = 'none';

    var cartIconWrap = document.createElement('div');
    cartIconWrap.className = 'dona-bn-icon-wrap';
    cartIconWrap.innerHTML = svgCart(false);
    cartIconWrap.appendChild(cartBadge);

    cartBtn.appendChild(cartIconWrap);
    cartBtn.innerHTML += '<span class="dona-bn-label">Сагс</span>';
    cartBtn.insertBefore(cartIconWrap, cartBtn.firstChild);
    nav.appendChild(cartBtn);

    document.body.appendChild(nav);

    /* sync cart badge from existing header cart count */
    syncCartBadge();
    /* watch for header cart count changes */
    var headerBadge = document.querySelector('.cart-count, .header-cart-number, [class*="cart-num"], .badge-cart');
    if (headerBadge) {
      var obs = new MutationObserver(syncCartBadge);
      obs.observe(headerBadge, { childList: true, subtree: true, characterData: true });
    }
  }

  function syncCartBadge() {
    var badge = document.getElementById('dona-cart-count');
    if (!badge) return;
    /* try common BeikeShop cart count selectors */
    var selectors = ['.cart-count', '.header-cart-number', '[class*="cart-num"]', '.badge-cart', '.cart-badge'];
    var count = 0;
    for (var i = 0; i < selectors.length; i++) {
      var el = document.querySelector(selectors[i]);
      if (el) {
        var n = parseInt(el.textContent.trim(), 10);
        if (!isNaN(n) && n > 0) { count = n; break; }
      }
    }
    if (count > 0) {
      badge.textContent = count > 99 ? '99+' : count;
      badge.style.display = 'flex';
    } else {
      badge.style.display = 'none';
    }
  }

  /* ════════════════════════════════════════════════════════
     CHIPS (category pages only)
  ════════════════════════════════════════════════════════ */
  function findActiveLi() {
    var activeChild = document.querySelector('#category-one li.child-category.active');
    if (activeChild) {
      var el = activeChild.parentElement;
      while (el && el.parentElement && el.parentElement.id !== 'category-one') el = el.parentElement;
      if (el && el.parentElement && el.parentElement.id === 'category-one') return el;
    }
    return document.querySelector('#category-one > li.active') || null;
  }

  function buildChips() {
    if (window.innerWidth >= 992) return;
    if (!document.querySelector('.page-categories')) return;
    if (document.querySelector('.dona-chips-wrap')) return;

    var activeLi = findActiveLi();
    var rightCol = document.querySelector('.right-column');
    if (!rightCol) return;

    var wrap = document.createElement('div');
    wrap.className = 'dona-chips-wrap';

    if (activeLi) {
      var childUl  = activeLi.querySelector(':scope > ul.accordion-collapse');
      var children = childUl ? childUl.querySelectorAll(':scope > li.child-category') : [];

      if (children.length) {
        var parentA      = activeLi.querySelector(':scope > a.category-href');
        var hasActiveChild = !!childUl.querySelector('li.active');
        if (parentA) {
          var allChip = document.createElement('a');
          allChip.className = 'dona-chip' + (!hasActiveChild ? ' is-active' : '');
          allChip.href = parentA.href;
          allChip.textContent = 'Бүгд';
          wrap.appendChild(allChip);
        }
        children.forEach(function (li) {
          var a = li.querySelector(':scope > a.category-href');
          if (!a) return;
          var chip = document.createElement('a');
          chip.className = 'dona-chip' + (li.classList.contains('active') ? ' is-active' : '');
          chip.href = a.href;
          chip.textContent = a.textContent.trim();
          wrap.appendChild(chip);
        });
      } else {
        document.querySelectorAll('#category-one > li').forEach(function (li) {
          var a = li.querySelector(':scope > a.category-href');
          if (!a) return;
          var name = a.textContent.trim();
          if (shouldSkip(a.href, name)) return;
          var chip = document.createElement('a');
          chip.className = 'dona-chip' + (li === activeLi ? ' is-active' : '');
          chip.href = a.href;
          chip.textContent = name;
          wrap.appendChild(chip);
        });
      }
    }

    if (!wrap.children.length) return;
    var productTool = rightCol.querySelector('.product-tool');
    rightCol.insertBefore(wrap, productTool || rightCol.firstChild);

    var active = wrap.querySelector('.is-active');
    if (active) {
      setTimeout(function () {
        wrap.scrollLeft = active.offsetLeft - wrap.offsetWidth / 2 + active.offsetWidth / 2;
      }, 60);
    }
  }

  /* ════════════════════════════════════════════════════════
     INIT
  ════════════════════════════════════════════════════════ */
  injectCSS();

  function init() {
    buildBottomNav();
    buildChips();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
