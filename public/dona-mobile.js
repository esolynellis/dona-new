/**
 * DONA Mobile UI v6
 * - Single compact chip row (sub-cats of active parent)
 * - Side menu: full category tree, small clean font, !important fixes
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

  /* ── CSS ─────────────────────────────────────────────── */
  var CSS = [
    '@media (max-width:991.98px){',

    /* chip bar */
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

    /* filter bar */
    '.page-categories .product-tool{padding:6px 0;flex-wrap:nowrap;gap:6px;}',
    '.page-categories .product-tool .style-wrap{display:none!important;}',
    '.page-categories .product-tool .text-nowrap.text-secondary{font-size:11px!important;color:#888!important;white-space:nowrap;}',
    '.page-categories .product-tool .perpage-select{display:none!important;}',
    '.page-categories .product-tool .order-select{font-size:12px;border-radius:7px;border-color:#e0e0e0;color:#444;padding:4px 8px;min-width:0;margin-left:6px!important;}',
    '.page-categories .product-tool .right-per-page{flex:1;justify-content:space-between!important;max-width:100%;}',
    '.page-categories .product-tool .d-flex.align-items-center{flex-shrink:0;}',

    /* breadcrumb */
    '.breadcrumb-wrap{margin-bottom:4px!important;}',
    '.breadcrumb-wrap .breadcrumb{font-size:11px;margin-bottom:0;}',

    '}',

    /* ── side menu – outside media query so offcanvas works everywhere ── */
    '#offcanvas-mobile-menu{width:80%!important;}',
    '#offcanvas-mobile-menu .offcanvas-header{background:#3B36DB!important;padding:13px 16px!important;}',
    '#offcanvas-mobile-menu .offcanvas-title{color:#fff!important;font-weight:700;font-size:14px;}',
    '#offcanvas-mobile-menu .btn-close{filter:invert(1);opacity:0.8;}',
    '#offcanvas-mobile-menu .mobile-menu-wrap{padding:0!important;}',

    /* hide original accordion */
    '#offcanvas-mobile-menu #menu-accordion{display:none!important;}',

    /* category tree container */
    '.dona-cat-tree{padding:0;overflow-y:auto;background:#fff;}',

    /* section label */
    '.dona-cat-tree .dona-cat-tree-label{',
      'display:block!important;',
      'font-size:10px!important;font-weight:700!important;letter-spacing:.07em;',
      'color:#aaa!important;text-transform:uppercase;',
      'padding:12px 16px 4px!important;',
      'text-decoration:none!important;',
    '}',

    /* parent row – use !important to beat Bootstrap a{color:...} */
    '.dona-cat-tree .dona-parent-row{',
      'display:flex!important;align-items:center;',
      'padding:10px 16px!important;',
      'font-size:13px!important;font-weight:600!important;color:#111!important;',
      'text-decoration:none!important;',
      'border-bottom:1px solid #f0f0f0!important;',
      'cursor:pointer;background:#fff!important;',
    '}',
    '.dona-cat-tree .dona-parent-row.is-active{color:#3B36DB!important;}',
    '.dona-cat-tree .dona-parent-row .dona-arr{margin-left:auto;font-size:16px;color:#ccc!important;transition:transform .2s;display:inline-block;}',
    '.dona-cat-tree .dona-parent-row.open .dona-arr{transform:rotate(90deg);color:#3B36DB!important;}',

    /* children list */
    '.dona-cat-tree .dona-child-list{display:none!important;background:#f8f8f8;}',
    '.dona-cat-tree .dona-child-list.open{display:block!important;}',

    /* child link */
    '.dona-cat-tree .dona-child-link{',
      'display:flex!important;align-items:center;',
      'padding:9px 16px 9px 28px!important;',
      'font-size:12px!important;color:#555!important;',
      'text-decoration:none!important;',
      'border-bottom:1px solid #efefef!important;',
      'background:#f8f8f8!important;',
    '}',
    '.dona-cat-tree .dona-child-link:last-child{border-bottom:none!important;}',
    '.dona-cat-tree .dona-child-link.is-active{color:#3B36DB!important;font-weight:600!important;}',
    '.dona-cat-tree .dona-child-link::before{content:"·";margin-right:6px;color:#bbb!important;}',
    '.dona-cat-tree .dona-child-link.is-active::before{color:#3B36DB!important;}',
  ].join('');

  function injectCSS() {
    var existing = document.getElementById('dona-mobile-css');
    if (existing) existing.remove();
    var s = document.createElement('style');
    s.id = 'dona-mobile-css';
    s.textContent = CSS;
    document.head.appendChild(s);
  }

  /* ── find active top-level li ───────────────────────── */
  function findActiveLi() {
    var activeChild = document.querySelector('#category-one li.child-category.active');
    if (activeChild) {
      var el = activeChild.parentElement;
      while (el && el.parentElement && el.parentElement.id !== 'category-one') {
        el = el.parentElement;
      }
      if (el && el.parentElement && el.parentElement.id === 'category-one') return el;
    }
    return document.querySelector('#category-one > li.active') || null;
  }

  /* ── chips (sub-cats of active parent) ─────────────── */
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
        var parentA = activeLi.querySelector(':scope > a.category-href');
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
        /* top-level only – no children */
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

  /* ── full category tree in side menu ────────────────── */
  function buildMenuCats() {
    var menuBody = document.querySelector('#offcanvas-mobile-menu .mobile-menu-wrap');
    if (!menuBody) return;

    /* remove old tree if any (allow rebuild) */
    var oldTree = menuBody.querySelector('.dona-cat-tree');
    if (oldTree) return; /* already built */

    var topItems = document.querySelectorAll('#category-one > li');
    if (!topItems.length) return;

    var activeLi      = findActiveLi();
    var activeChildEl = document.querySelector('#category-one li.child-category.active');

    var tree = document.createElement('div');
    tree.className = 'dona-cat-tree';

    var label = document.createElement('div');
    label.className = 'dona-cat-tree-label';
    label.textContent = 'Ангилал';
    tree.appendChild(label);

    topItems.forEach(function (li) {
      var a = li.querySelector(':scope > a.category-href');
      if (!a) return;
      var name = a.textContent.trim();
      if (shouldSkip(a.href, name)) return;

      var isActiveParent = (li === activeLi);
      var childUl        = li.querySelector(':scope > ul.accordion-collapse');
      var children       = childUl ? childUl.querySelectorAll(':scope > li.child-category') : [];
      var hasChildren    = children.length > 0;

      /* parent row */
      var row = document.createElement('div');
      row.className = 'dona-parent-row' + (isActiveParent ? ' is-active' : '') + (isActiveParent && hasChildren ? ' open' : '');
      row.style.cssText = 'display:flex!important;align-items:center;padding:10px 16px;font-size:13px;font-weight:600;color:' + (isActiveParent ? '#3B36DB' : '#111') + ';text-decoration:none;border-bottom:1px solid #f0f0f0;cursor:pointer;background:#fff;';

      var nameSpan = document.createElement('span');
      nameSpan.textContent = name;
      nameSpan.style.cssText = 'color:inherit;flex:1;';
      row.appendChild(nameSpan);

      if (hasChildren) {
        var arr = document.createElement('span');
        arr.className = 'dona-arr';
        arr.textContent = '›';
        arr.style.cssText = 'margin-left:auto;font-size:18px;color:' + (isActiveParent ? '#3B36DB' : '#ccc') + ';transition:transform .2s;' + (isActiveParent ? 'transform:rotate(90deg);' : '');
        row.appendChild(arr);
        /* clicking parent row navigates to its page */
        row.addEventListener('click', function (e) {
          window.location.href = a.href;
        });
      } else {
        row.addEventListener('click', function () {
          window.location.href = a.href;
        });
      }

      tree.appendChild(row);

      if (!hasChildren) return;

      /* children list – open if this is the active parent */
      var childList = document.createElement('div');
      childList.className = 'dona-child-list' + (isActiveParent ? ' open' : '');

      children.forEach(function (cli) {
        var ca = cli.querySelector(':scope > a.category-href');
        if (!ca) return;
        var isActive = (cli === activeChildEl);
        var cl = document.createElement('a');
        cl.className = 'dona-child-link' + (isActive ? ' is-active' : '');
        cl.href = ca.href;
        cl.style.cssText = 'display:flex!important;align-items:center;padding:9px 16px 9px 28px;font-size:12px;color:' + (isActive ? '#3B36DB' : '#555') + '!important;text-decoration:none!important;border-bottom:1px solid #efefef;background:#f8f8f8;font-weight:' + (isActive ? '600' : '400') + ';';
        var dot = document.createElement('span');
        dot.textContent = '· ';
        dot.style.cssText = 'color:' + (isActive ? '#3B36DB' : '#bbb') + ';margin-right:4px;';
        cl.prepend(dot);
        cl.appendChild(document.createTextNode(ca.textContent.trim()));
        childList.appendChild(cl);
      });

      tree.appendChild(childList);
    });

    /* insert before accordion */
    var accordion = menuBody.querySelector('#menu-accordion');
    menuBody.insertBefore(tree, accordion || menuBody.firstChild);
  }

  /* ── run ─────────────────────────────────────────────── */
  injectCSS();

  function init() {
    buildChips();
    buildMenuCats();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  /* also build when offcanvas menu is shown (Bootstrap event) */
  document.addEventListener('show.bs.offcanvas', function (e) {
    if (e.target && e.target.id === 'offcanvas-mobile-menu') {
      setTimeout(buildMenuCats, 50);
    }
  });
})();
