/**
 * DONA Mobile UI v5
 * - Single compact chip row (sub-cats of active parent)
 * - Side menu: full category tree, small clean font
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
    '.dona-chips-wrap{display:flex;align-items:center;overflow-x:auto;gap:6px;',
      'padding:8px 12px;background:#fff;border-bottom:1px solid #ebebeb;',
      '-webkit-overflow-scrolling:touch;scrollbar-width:none;}',
    '.dona-chips-wrap::-webkit-scrollbar{display:none;}',
    '.dona-chip{display:inline-flex;align-items:center;justify-content:center;',
      'flex-shrink:0;white-space:nowrap;padding:4px 12px;min-height:28px;',
      'border-radius:999px;font-size:12px;font-weight:500;',
      'text-decoration:none!important;background:#f5f5f7;color:#444!important;',
      'border:1.5px solid transparent;}',
    '.dona-chip.is-active{background:#3B36DB;color:#fff!important;border-color:#3B36DB;}',

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

    /* ── side menu ─────────────────────────────────────── */
    '#offcanvas-mobile-menu{width:80%!important;}',
    '#offcanvas-mobile-menu .offcanvas-header{background:#3B36DB;padding:13px 16px;}',
    '#offcanvas-mobile-menu .offcanvas-title{color:#fff;font-weight:700;font-size:14px;}',
    '#offcanvas-mobile-menu .btn-close{filter:invert(1);opacity:0.8;}',
    '#offcanvas-mobile-menu .mobile-menu-wrap{padding:0;}',

    /* hide original accordion (we replace with our tree) */
    '#offcanvas-mobile-menu #menu-accordion{display:none;}',

    /* category tree container */
    '.dona-cat-tree{padding:0;overflow-y:auto;}',

    /* section label */
    '.dona-cat-tree-label{',
      'font-size:10px;font-weight:700;letter-spacing:.07em;',
      'color:#aaa;text-transform:uppercase;',
      'padding:12px 16px 4px;',
    '}',

    /* parent row */
    '.dona-parent-row{',
      'display:flex;align-items:center;',
      'padding:10px 16px;',
      'font-size:13px;font-weight:600;color:#111;',
      'text-decoration:none;',
      'border-bottom:1px solid #f3f3f3;',
      'cursor:pointer;',
    '}',
    '.dona-parent-row.is-active{color:#3B36DB;}',
    '.dona-parent-row .dona-arr{margin-left:auto;font-size:14px;color:#ccc;transition:transform .2s;}',
    '.dona-parent-row.open .dona-arr{transform:rotate(90deg);color:#3B36DB;}',

    /* children list */
    '.dona-child-list{display:none;background:#fafafa;border-bottom:1px solid #f0f0f0;}',
    '.dona-child-list.open{display:block;}',

    '.dona-child-link{',
      'display:flex;align-items:center;',
      'padding:8px 16px 8px 28px;',
      'font-size:12px;color:#555;',
      'text-decoration:none;',
      'border-bottom:1px solid #f5f5f5;',
    '}',
    '.dona-child-link:last-child{border-bottom:none;}',
    '.dona-child-link.is-active{color:#3B36DB;font-weight:600;}',
    '.dona-child-link::before{content:"·";margin-right:6px;color:#ccc;}',
    '.dona-child-link.is-active::before{color:#3B36DB;}',
  ].join('');

  function injectCSS() {
    if (document.getElementById('dona-mobile-css')) return;
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
        /* top-level only */
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
    if (!menuBody || menuBody.querySelector('.dona-cat-tree')) return;

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
      var hasChildren    = childUl && childUl.querySelectorAll(':scope > li.child-category').length > 0;

      /* parent row */
      var row = document.createElement('a');
      row.className = 'dona-parent-row' + (isActiveParent ? ' is-active' : '') + (isActiveParent && hasChildren ? ' open' : '');
      row.href = hasChildren ? 'javascript:void(0)' : a.href;
      row.innerHTML = name + (hasChildren ? '<span class="dona-arr">›</span>' : '');
      tree.appendChild(row);

      if (!hasChildren) return;

      /* children list */
      var childList = document.createElement('div');
      childList.className = 'dona-child-list' + (isActiveParent ? ' open' : '');

      childUl.querySelectorAll(':scope > li.child-category').forEach(function (cli) {
        var ca = cli.querySelector(':scope > a.category-href');
        if (!ca) return;
        var cl = document.createElement('a');
        cl.className = 'dona-child-link' + (cli === activeChildEl ? ' is-active' : '');
        cl.href = ca.href;
        cl.textContent = ca.textContent.trim();
        childList.appendChild(cl);
      });

      tree.appendChild(childList);

      /* toggle on parent row click */
      row.addEventListener('click', function (e) {
        e.preventDefault();
        var isOpen = childList.classList.contains('open');
        /* close all others */
        tree.querySelectorAll('.dona-child-list.open').forEach(function (el) { el.classList.remove('open'); });
        tree.querySelectorAll('.dona-parent-row.open').forEach(function (el) { el.classList.remove('open'); });
        if (!isOpen) {
          childList.classList.add('open');
          row.classList.add('open');
        }
      });
    });

    /* insert before accordion */
    var accordion = menuBody.querySelector('#menu-accordion');
    menuBody.insertBefore(tree, accordion || menuBody.firstChild);
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
