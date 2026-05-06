/**
 * DONA Brand Grouping v3
 * Non-destructive: adds brand badge to first card per brand without hiding any cards.
 * On detail pages: shows "More from this brand" section.
 */
(function () {
  'use strict';

  var CSS = [
    '.brand-badge{',
      'display:inline-block;',
      'background:rgba(30,30,30,0.82);color:#fff;',
      'font-size:10px;padding:2px 8px;border-radius:12px;',
      'margin-top:4px;max-width:100%;overflow:hidden;',
      'text-overflow:ellipsis;white-space:nowrap;',
      'vertical-align:middle;',
    '}',
    '.brand-more-link{',
      'display:block;text-align:center;',
      'font-size:11px;color:#555;margin-top:4px;',
      'text-decoration:none;',
    '}',
    '.brand-more-link:hover{color:#333;text-decoration:underline;}',
    '.brand-section-title{',
      'font-size:16px;font-weight:600;',
      'margin:28px 0 14px;padding-bottom:8px;',
      'border-bottom:2px solid #eee;',
    '}',
    '.brand-more-grid{',
      'display:flex;flex-wrap:wrap;gap:12px;margin-top:8px;',
    '}',
    '.brand-more-card{',
      'width:calc(25% - 9px);text-decoration:none;color:inherit;',
      'border:1px solid #eee;border-radius:6px;overflow:hidden;',
      'transition:box-shadow .2s;',
    '}',
    '.brand-more-card:hover{box-shadow:0 2px 12px rgba(0,0,0,.12);}',
    '.brand-more-card img{width:100%;height:160px;object-fit:cover;}',
    '.brand-more-card-body{padding:8px;}',
    '.brand-more-card-name{font-size:12px;line-height:1.3;overflow:hidden;',
      'display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;}',
    '@media(max-width:576px){',
      '.brand-more-card{width:calc(50% - 6px);}',
      '.brand-more-card img{height:110px;}',
    '}',
  ].join('');

  function injectCSS() {
    if (document.getElementById('dona-brand-css')) return;
    var s = document.createElement('style');
    s.id = 'dona-brand-css';
    s.textContent = CSS;
    document.head.appendChild(s);
  }

  // Extract product ID from product card link: href="/products/NNN"
  function getProductId(card) {
    var link = card.querySelector('a[href*="/products/"]');
    if (!link) return null;
    var m = link.getAttribute('href').match(/\/products\/(\d+)/);
    return m ? parseInt(m[1]) : null;
  }

  // ── Listing page: add brand badge (NO card hiding) ─────────────
  function initListingGrouping() {
    var cards = document.querySelectorAll('.product-wrap');
    if (!cards.length) return;

    var ids = [];
    var idToCard = {};
    cards.forEach(function (card) {
      var id = getProductId(card);
      if (id && !idToCard[id]) {
        idToCard[id] = card;
        ids.push(id);
      }
    });

    if (!ids.length) return;

    // Fetch brand names for all product IDs on this page
    fetch('/brand-data.php?ids=' + ids.join(','))
      .then(function (r) { return r.json(); })
      .then(function (brandMap) {
        // Track first card per brand and count of brand products on page
        var brandCount = {};
        var brandFace  = {};

        ids.forEach(function (id) {
          var brand = brandMap[id];
          if (!brand) return;
          if (!brandFace[brand]) brandFace[brand] = id;
          brandCount[brand] = (brandCount[brand] || 0) + 1;
        });

        // Add badge only to FIRST card of each brand that has >1 product on page
        Object.keys(brandFace).forEach(function (brand) {
          var faceId = brandFace[brand];
          var count  = brandCount[brand];
          var card   = idToCard[faceId];
          if (!card) return;

          // Add brand label to product-bottom-info section
          var info = card.querySelector('.product-bottom-info');
          if (!info) return;

          // Brand badge
          var badge = document.createElement('span');
          badge.className = 'brand-badge';
          badge.title = brand;
          badge.textContent = brand;
          info.appendChild(badge);

          // "& N more" link if multiple on this page
          if (count > 1) {
            var moreLink = document.createElement('a');
            moreLink.className = 'brand-more-link';
            moreLink.href = '/products?keyword=' + encodeURIComponent(brand);
            moreLink.textContent = 'Энэ брэндийн ' + count + ' бараа';
            info.appendChild(moreLink);
          }
        });
      })
      .catch(function () { /* silent */ });
  }

  // ── Detail page: "More from this brand" ───────────────────────
  function initDetailBrand() {
    var m = window.location.pathname.match(/\/products\/(\d+)/);
    if (!m) return;
    var productId = parseInt(m[1]);

    fetch('/brand-data.php?ids=' + productId)
      .then(function (r) { return r.json(); })
      .then(function (brandMap) {
        var brandName = brandMap[productId];
        if (!brandName) return;
        return fetch('/brand-data.php?brand=' + encodeURIComponent(brandName) + '&exclude=' + productId)
          .then(function (r) { return r.json(); })
          .then(function (items) { return { brand: brandName, items: items }; });
      })
      .then(function (result) {
        if (!result || !result.items || !result.items.length) return;
        var brandName = result.brand;
        var items     = result.items;

        // Find best insertion point after main content
        var anchor = document.querySelector('.similar-wrap, .product-mb-block, footer');
        if (!anchor) anchor = document.querySelector('footer');
        if (!anchor) return;

        var section = document.createElement('div');
        section.className = 'brand-more-section container mt-4 mb-5';

        var title = document.createElement('div');
        title.className = 'brand-section-title';
        title.textContent = '«' + brandName + '» брэндийн бусад бараанууд';
        section.appendChild(title);

        var grid = document.createElement('div');
        grid.className = 'brand-more-grid';
        items.forEach(function (p) {
          var card = document.createElement('a');
          card.className = 'brand-more-card';
          card.href = p.url;
          card.innerHTML =
            '<img src="' + p.image + '" alt="" loading="lazy">' +
            '<div class="brand-more-card-body">' +
              '<div class="brand-more-card-name">' + p.name + '</div>' +
            '</div>';
          grid.appendChild(card);
        });
        section.appendChild(grid);

        // Insert before anchor
        anchor.parentNode.insertBefore(section, anchor);
      })
      .catch(function () { /* silent */ });
  }

  function run() {
    injectCSS();
    var path     = window.location.pathname;
    var isDetail = /\/products\/\d/.test(path);

    if (isDetail) {
      setTimeout(initDetailBrand, 400);
    } else {
      setTimeout(initListingGrouping, 600);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
