/**
 * DONA Brand Grouping
 * On listing pages: collapses duplicate brand products, shows "& N more" badge
 * On detail pages: shows "More from this brand" section
 */
(function () {
  'use strict';

  var CSS = [
    '.brand-more-badge{',
      'position:absolute;bottom:46px;left:50%;transform:translateX(-50%);',
      'background:rgba(0,0,0,0.72);color:#fff;',
      'padding:4px 12px;border-radius:20px;',
      'font-size:11px;white-space:nowrap;cursor:pointer;',
      'z-index:10;border:none;transition:background .2s;',
      'text-decoration:none;display:inline-block;',
    '}',
    '.brand-more-badge:hover{background:rgba(0,0,0,0.9);color:#fff;}',
    '.brand-section-title{',
      'font-size:15px;font-weight:600;',
      'margin:24px 0 12px;padding-bottom:8px;',
      'border-bottom:2px solid #eee;',
    '}',
  ].join('');

  function injectCSS() {
    if (document.getElementById('dona-brand-css')) return;
    var s = document.createElement('style');
    s.id = 'dona-brand-css';
    s.textContent = CSS;
    document.head.appendChild(s);
  }

  // ── Listing page grouping ───────────────────────────────────────
  function initListingGrouping() {
    var cards = document.querySelectorAll('.product-wrap[data-brand]');
    if (!cards.length) return;

    var seen = {}; // brand → first card

    cards.forEach(function (card) {
      var brand = (card.getAttribute('data-brand') || '').trim();
      if (!brand) return;

      if (!seen[brand]) {
        seen[brand] = { face: card, count: 0 };
      } else {
        seen[brand].count++;
        card.style.display = 'none';
        card.setAttribute('data-brand-hidden', '1');
      }
    });

    // Add badge to face cards that have hidden siblings
    Object.keys(seen).forEach(function (brand) {
      var g = seen[brand];
      if (g.count === 0) return;

      var imageDiv = g.face.querySelector('.image');
      if (!imageDiv) return;
      imageDiv.style.position = 'relative';

      var badge = document.createElement('a');
      badge.className = 'brand-more-badge';
      badge.href = '/products?brand_name=' + encodeURIComponent(brand);
      badge.textContent = '+ ' + g.count + ' дэлгэрэнгүй';
      badge.title = brand + ' брэндийн бусад бараанууд';
      imageDiv.appendChild(badge);
    });
  }

  // ── Detail page "More from brand" ──────────────────────────────
  function initDetailBrand() {
    // Look for brand_name in page's bk.product variable
    if (!window.bk || !window.bk.product) return;
    var brandName = window.bk.product.brand_name || '';
    if (!brandName) return;

    // Find the similar-products section or main container
    var anchor = document.querySelector('.similar-products, .product-similar, #similar, .container.product-page, .product-detail');
    if (!anchor) anchor = document.querySelector('.product-wrap') ? document.querySelector('.product-wrap').closest('.container') : null;
    if (!anchor) return;

    // Create section
    var section = document.createElement('div');
    section.className = 'brand-more-section mt-4';

    var title = document.createElement('div');
    title.className = 'brand-section-title';
    title.textContent = '«' + brandName + '» брэндийн бусад бараанууд';
    section.appendChild(title);

    var grid = document.createElement('div');
    grid.className = 'row g-2';
    grid.innerHTML = '<div class="col-12 text-muted small py-3">Уншиж байна...</div>';
    section.appendChild(grid);

    anchor.appendChild(section);

    // Fetch brand products from API
    var currentId = window.bk.product.id || 0;
    fetch('/api/products?brand_name=' + encodeURIComponent(brandName) + '&per_page=8&active=1')
      .then(function (r) { return r.json(); })
      .then(function (res) {
        var items = (res.data || []).filter(function (p) { return p.id != currentId; });
        if (!items.length) {
          section.remove();
          return;
        }
        grid.innerHTML = items.map(function (p) {
          var img = (p.images && p.images[0]) ? p.images[0] : '/default.jpg';
          return [
            '<div class="col-6 col-md-3">',
              '<a href="' + p.url + '" class="d-block text-decoration-none text-dark">',
                '<div style="height:160px;overflow:hidden;border-radius:6px;background:#f5f5f5;margin-bottom:6px;">',
                  '<img src="' + img + '" style="width:100%;height:100%;object-fit:cover;" loading="lazy">',
                '</div>',
                '<div style="font-size:12px;line-height:1.3;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">' + p.name_format + '</div>',
                '<div style="font-size:13px;font-weight:600;color:#d32f2f;margin-top:3px;">' + p.price_format + '</div>',
              '</a>',
            '</div>',
          ].join('');
        }).join('');
      })
      .catch(function () { section.remove(); });
  }

  function run() {
    injectCSS();

    // Only run grouping on non-detail listing pages
    var path = window.location.pathname;
    var isDetail = /\/product\/\d/.test(path) || /\/products\/\d/.test(path);

    if (!isDetail) {
      initListingGrouping();
    } else {
      // Wait a moment for bk.product to be set by page scripts
      setTimeout(initDetailBrand, 600);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
