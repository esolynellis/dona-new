/**
 * DONA Mobile UI v7
 * - Chips: sub-cats of active parent (category pages only, from #category-one)
 * - Side menu: built from #menu-accordion (exists on ALL pages)
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

    /* ── side menu (no media query – works in offcanvas on all pages) ── */
    '#offcanvas-mobile-menu{width:82%!important;}',
    '#offcanvas-mobile-menu .offcanvas-header{background:#3B36DB!important;padding:14px 16px!important;}',
    '#offcanvas-mobile-menu .offcanvas-title{color:#fff!important;font-weight:700;font-size:15px;}',
    '#offcanvas-mobile-menu .btn-close{filter:invert(1);opacity:.85;}',
    '#offcanvas-mobile-menu .mobile-menu-wrap{padding:0!important;}',

    /* hide original accordion – we replace it */
    '#offcanvas-mobile-menu #menu-accordion{display:none!important;}',

    /* tree wrapper */
    '.dona-cat-tree{display:block;background:#fff;}',

    /* section label */
    '.dona-cat-tree-label{display:block!important;font-size:10px!important;font-weight:700!important;',
      'letter-spacing:.08em;color:#aaa!important;text-transform:uppercase;',
      'padding:14px 16px 5px!important;text-decoration:none!important;}',

    /* top-level row (div, not a, to avoid Bootstrap link colour) */
    '.dona-nav-row{display:flex!important;align-items:center;',
      'padding:11px 16px!important;font-size:13px!important;font-weight:600!important;',
      'color:#111!important;text-decoration:none!important;',
      'border-bottom:1px solid #f0f0f0!important;cursor:pointer;background:#fff!important;}',
    '.dona-nav-row.is-active{color:#3B36DB!important;background:#f4f3ff!important;}',
    '.dona-nav-row .dona-arr{margin-left:auto;font-size:18px;line-height:1;',
      'color:#ccc!important;transition:transform .2s;display:inline-block;flex-shrink:0;}',
    '.dona-nav-row.open .dona-arr{transform:rotate(90deg);color:#3B36DB!important;}',

    /* child list */
    '.dona-child-list{display:none!important;background:#f8f8f8;}',
    '.dona-child-list.open{display:block!important;}',

    /* child link (a tag needs aggressive override) */
    '.dona-child-link{display:flex!important;align-items:center;',
      'padding:9px 16px 9px 26px!important;font-size:12px!important;',
      'color:#555!important;text-decoration:none!important;',
      'border-bottom:1px solid #efefef!important;background:#f8f8f8!important;}',
    '.dona-child-link.is-active{color:#3B36DB!important;font-weight:600!important;background:#eeeeff!important;}',
    '.dona-child-link::before{content:"›";margin-right:6px;color:#bbb!important;font-size:14px;}',
    '.dona-child-link.is-active::before{color:#3B36DB!important;}',
  ].join('');

  function injectCSS() {
    var old = document.getElementById('dona-mobile-css');
    if (old) old.remove();
    var s = document.createElement('style');
    s.id = 'dona-mobile-css';
    s.textContent = CSS;
    document.head.appendChild(s);
  }

  /* ── detect current active URL ───────────────────────── */
  var CUR = window.location.pathname;

  function isCurrentHref(href) {
    if (!href || href === '#' || href.startsWith('#')) return false;
    try {
      var u = new URL(href, window.location.origin);
      return u.pathname === CUR;
    } catch (e) { return false; }
  }

  /* ── find active top-level li (category pages) ───────── */
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

  /* ── chips (category pages only) ────────────────────── */
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

  /* ── side menu tree from #menu-accordion (all pages) ─── */
  function buildMenuCats() {
    var menuBody = document.querySelector('#offcanvas-mobile-menu .mobile-menu-wrap');
    if (!menuBody) return;
    if (menuBody.querySelector('.dona-cat-tree')) return; /* already built */

    /* source: the accordion items already in the offcanvas */
    var accordionItems = menuBody.querySelectorAll('#menu-accordion > .accordion-item');
    if (!accordionItems.length) return;

    var tree = document.createElement('div');
    tree.className = 'dona-cat-tree';

    accordionItems.forEach(function (item) {
      var topLink = item.querySelector(':scope > .nav-item-text > a.nav-link');
      if (!topLink) return;

      var name = topLink.textContent.trim();
      if (!name) return;

      var href = topLink.getAttribute('href') || '';
      var isHashOnly = !href || href === '#' || href.charAt(0) === '#';

      /* gather children from ul.ul-children */
      var childAnchors = item.querySelectorAll('.ul-children .nav-item a.nav-link');
      var hasChildren  = childAnchors.length > 0;

      /* determine active state */
      var isActive = false;
      if (!isHashOnly && isCurrentHref(href)) isActive = true;
      if (!isActive && hasChildren) {
        childAnchors.forEach(function (ca) {
          if (isCurrentHref(ca.getAttribute('href'))) isActive = true;
        });
      }

      /* ── parent row (div to avoid Bootstrap a-tag colour override) ── */
      var row = document.createElement('div');
      row.className = 'dona-nav-row' + (isActive ? ' is-active' : '') + (isActive && hasChildren ? ' open' : '');

      var nameSpan = document.createElement('span');
      nameSpan.textContent = name;
      row.appendChild(nameSpan);

      if (hasChildren) {
        var arr = document.createElement('span');
        arr.className = 'dona-arr';
        arr.textContent = '›';
        row.appendChild(arr);
      }

      tree.appendChild(row);

      /* ── children list ── */
      if (hasChildren) {
        var childList = document.createElement('div');
        childList.className = 'dona-child-list' + (isActive ? ' open' : '');

        childAnchors.forEach(function (ca) {
          var childHref = ca.getAttribute('href') || '#';
          var childName = ca.textContent.trim();
          if (!childName || shouldSkip(childHref, childName)) return;

          var isChildActive = isCurrentHref(childHref);
          var cl = document.createElement('a');
          cl.className = 'dona-child-link' + (isChildActive ? ' is-active' : '');
          cl.href = childHref;
          cl.textContent = childName;
          childList.appendChild(cl);
        });

        tree.appendChild(childList);

        /* toggle: click parent row to expand/collapse children */
        row.addEventListener('click', function () {
          var open = childList.classList.contains('open');
          /* close all */
          tree.querySelectorAll('.dona-child-list.open').forEach(function (el) { el.classList.remove('open'); });
          tree.querySelectorAll('.dona-nav-row.open').forEach(function (el) { el.classList.remove('open'); });
          if (!open) {
            childList.classList.add('open');
            row.classList.add('open');
          }
        });
      } else {
        /* no children – navigate directly */
        if (!isHashOnly) {
          row.addEventListener('click', function () { window.location.href = href; });
          row.style.cursor = 'pointer';
        }
      }
    });

    /* insert the tree before (and hiding) the original accordion */
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

  /* re-run when offcanvas is shown (catches cases where DOM wasn't ready) */
  document.addEventListener('show.bs.offcanvas', function (e) {
    if (e.target && e.target.id === 'offcanvas-mobile-menu') {
      setTimeout(buildMenuCats, 30);
    }
  });
})();
