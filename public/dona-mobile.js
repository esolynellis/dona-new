/**
 * DONA Mobile UI v4
 * - Single compact chip row (sub-cats only, or top-level if no parent)
 * - Clean filter bar
 * - Modern side menu with category list
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

  /* ── CSS ────────────────────────────────────────────── */
  var CSS = [
    '@media (max-width:991.98px){',

    /* ── single chip bar ── */
    '.dona-chips-wrap{',
      'display:flex;align-items:center;overflow-x:auto;gap:6px;',
      'padding:8px 12px;background:#fff;',
      'border-bottom:1px solid #ebebeb;',
      '-webkit-overflow-scrolling:touch;scrollbar-width:none;',
    '}',
    '.dona-chips-wrap::-webkit-scrollbar{display:none;}',

    '.dona-chip{',
      'display:inline-flex;align-items:center;justify-content:center;',
      'flex-shrink:0;white-space:nowrap;',
      'padding:4px 12px;min-height:28px;border-radius:999px;',
      'font-size:12px;font-weight:500;text-decoration:none!important;',
      'background:#f5f5f7;color:#444!important;',
      'border:1.5px solid transparent;',
    '}',
    '.dona-chip.is-active{',
      'background:#3B36DB;color:#fff!important;border-color:#3B36DB;',
    '}',

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
    '.breadcrumb-wrap .breadcrumb-item+.breadcrumb-item::before{font-size:10px;}',

    '}', /* end @media */

    /* ── Side menu ── */
    '#offcanvas-mobile-menu{width:80%!important;}',
    '#offcanvas-mobile-menu .offcanvas-header{background:#3B36DB;padding:14px 16px;}',
    '#offcanvas-mobile-menu .offcanvas-title{color:#fff;font-weight:700;font-size:15px;}',
    '#offcanvas-mobile-menu .btn-close{filter:invert(1);opacity:0.85;}',
    '#offcanvas-mobile-menu .mobile-menu-wrap{padding:0;}',
    '#offcanvas-mobile-menu .accordion-item{border:none!important;border-bottom:1px solid #f4f4f5!important;}',
    '#offcanvas-mobile-menu .nav-item-text>a{font-size:14px;font-weight:500;color:#18181b;height:46px;padding-left:16px;}',
    '#offcanvas-mobile-menu .nav-item-text>span{border-left:none!important;width:40px;height:46px;color:#bbb;}',
    '#offcanvas-mobile-menu .nav-item-text>span[aria-expanded="true"]{background:#f4f4f5;color:#3B36DB;}',
    '#offcanvas-mobile-menu .accordion-collapse{background:#fafafa;border-top:1px solid #f0f0f0!important;padding:4px 0!important;}',
    '#offcanvas-mobile-menu .ul-children .nav-link{font-size:13px;color:#71717a;padding:8px 24px!important;}',
    /* category section */
    '.dona-menu-cats{padding:10px 16px 2px;}',
    '.dona-menu-cats-title{font-size:10px;font-weight:700;color:#bbb;letter-spacing:.06em;text-transform:uppercase;margin-bottom:6px;}',
    '.dona-menu-cat-link{display:flex;align-items:center;padding:9px 0;font-size:14px;color:#18181b;text-decoration:none;border-bottom:1px solid #f4f4f5;}',
    '.dona-menu-cat-link:last-child{border-bottom:none;}',
    '.dona-menu-cat-link.is-active{color:#3B36DB;font-weight:600;}',
    '.dona-cat-arrow{margin-left:auto;color:#d4d4d8;font-size:13px;}',
  ].join('');

  function injectCSS() {
    if (document.getElementById('dona-mobile-css')) return;
    var s = document.createElement('style');
    s.id = 'dona-mobile-css';
    s.textContent = CSS;
    document.head.appendChild(s);
  }

  /* ── Find active top-level li ───────────────────────── */
  function findActiveLi() {
    // Active child → walk up to top-level li
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

  /* ── Build single smart chip row ────────────────────── */
  function buildChips() {
    if (window.innerWidth >= 992) return;
    if (!document.querySelector('.page-categories')) return;
    if (document.querySelector('.dona-chips-wrap')) return;

    var activeLi   = findActiveLi();
    var rightCol   = document.querySelector('.right-column');
    if (!rightCol) return;

    var wrap = document.createElement('div');
    wrap.className = 'dona-chips-wrap';
    var hasActive  = false;

    /* Case A: active top-level category HAS children → show them */
    if (activeLi) {
      var childUl  = activeLi.querySelector(':scope > ul.accordion-collapse');
      var children = childUl ? childUl.querySelectorAll(':scope > li.child-category') : [];

      if (children.length) {
        /* "Бүгд" chip → parent */
        var parentA = activeLi.querySelector(':scope > a.category-href');
        var hasActiveChild = !!childUl.querySelector('li.active');
        if (parentA) {
          var allChip = document.createElement('a');
          allChip.className = 'dona-chip' + (!hasActiveChild ? ' is-active' : '');
          allChip.href = parentA.href;
          allChip.textContent = 'Бүгд';
          wrap.appendChild(allChip);
          if (!hasActiveChild) hasActive = true;
        }
        children.forEach(function (li) {
          var a = li.querySelector(':scope > a.category-href');
          if (!a) return;
          var chip = document.createElement('a');
          var active = li.classList.contains('active');
          chip.className = 'dona-chip' + (active ? ' is-active' : '');
          chip.href = a.href;
          chip.textContent = a.textContent.trim();
          wrap.appendChild(chip);
          if (active) hasActive = true;
        });
      }
    }

    /* Case B: no children found → show all top-level categories */
    if (!wrap.children.length) {
      var catItems = document.querySelectorAll('#category-one > li');
      catItems.forEach(function (li) {
        var a = li.querySelector(':scope > a.category-href');
        if (!a) return;
        var name = a.textContent.trim();
        if (shouldSkip(a.href, name)) return;
        var chip = document.createElement('a');
        var active = (li === activeLi);
        chip.className = 'dona-chip' + (active ? ' is-active' : '');
        chip.href = a.href;
        chip.textContent = name;
        wrap.appendChild(chip);
        if (active) hasActive = true;
      });
    }

    if (!wrap.children.length) return;

    var productTool = rightCol.querySelector('.product-tool');
    rightCol.insertBefore(wrap, productTool || rightCol.firstChild);

    /* Scroll active chip to center */
    var active = wrap.querySelector('.is-active');
    if (active) {
      setTimeout(function () {
        wrap.scrollLeft = active.offsetLeft - wrap.offsetWidth / 2 + active.offsetWidth / 2;
      }, 60);
    }
  }

  /* ── Side menu category section ─────────────────────── */
  function buildMenuCats() {
    var menuBody = document.querySelector('#offcanvas-mobile-menu .mobile-menu-wrap');
    if (!menuBody || menuBody.querySelector('.dona-menu-cats')) return;

    var catItems = document.querySelectorAll('#category-one > li');
    if (!catItems.length) return;

    var activeLi = findActiveLi();
    var section  = document.createElement('div');
    section.className = 'dona-menu-cats';

    var title = document.createElement('div');
    title.className = 'dona-menu-cats-title';
    title.textContent = 'Ангилал';
    section.appendChild(title);

    catItems.forEach(function (li) {
      var a = li.querySelector(':scope > a.category-href');
      if (!a) return;
      var name = a.textContent.trim();
      if (shouldSkip(a.href, name)) return;
      var link = document.createElement('a');
      link.className = 'dona-menu-cat-link' + (li === activeLi ? ' is-active' : '');
      link.href = a.href;
      link.innerHTML = name + '<span class="dona-cat-arrow">›</span>';
      section.appendChild(link);
    });

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
