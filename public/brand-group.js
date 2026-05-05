/**
 * DONA Brand Grouping v2
 * - Reads product IDs from DOM (product card links)
 * - Fetches brand names from /brand-data.php
 * - Collapses duplicate brands on listing pages
 * - Shows "More from this brand" on detail pages
 */
(function () {
  'use strict';

  var CSS = [
    '.brand-more-badge{',
      'position:absolute;bottom:44px;left:50%;transform:translateX(-50%);',
      'background:rgba(0,0,0,0.75);color:#fff;',
      'padding:4px 12px;border-radius:20px;',
      'font-size:11px;white-space:nowrap;cursor:pointer;',
      'z-index:10;border:none;transition:background .2s;',
      'text-decoration:none;display:inline-block;line-height:1.4;',
    '}',
    '.brand-more-badge:hover{background:rgba(0,0,0,0.92);color:#fff;}',
    '.brand-section-title{',
      'font-size:16px;font-weight:600;',
      'margin:28px 0 14px;padding-bottom:8px;',
      'border-bottom:2px solid #eee;',
    '}',
    '.brand-more-grid{display:flex;flex-wrap:wrap;gap:12px;margin-top:8px;}',
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
    '.brand-more-card-price{font-size:13px;font-weight:600;color:#d32f2f;margin-top:4px;}',
    '@media(max-width:576px){',
      '.brand-more-card{width:calc(50% - 6px);}',
      '.brand-more-card img{height:120px;}',
    '}',
  ].join('');

  function injectCSS() {
    if (document.getElementById('dona-brand-css')) return;
    var s = document.createElement('style');
    s.id = 'dona-brand-css';
    s.textContent = CSS;
    document.head.appendChild(s);
  }

  // Extract product ID from product card: look for href="/products/NNN"
  function getProductIdFromCard(card) {
    var link = card.querySelector('a[href*="/products/"]');
    if (!link) return null;
    var m = link.getAttribute('href').match(/\/products\/(\d+)/);
    return m ? parseInt(m[1]) : null;
  }

  // ── Listing page grouping ──────────────────────────────────────
  function initListingGrouping() {
    var cards = document.querySelectorAll('.product-wrap');
    if (!cards.length) return;

    // Collect product IDs
    var idToCard = {};
    var ids = [];
    cards.forEach(function (card) {
      var id = getProductIdFromCard(card);
      if (id) {
        idToCard[id] = card;
        ids.push(id);
      }
    });

    if (!ids.length) return;

    // Fetch brand names in one request
    fetch('/brand-data.php?ids=' + ids.join(','))
      .then(function (r) { return r.json(); })
      .then(function (brandMap) {
        // Attach brand info to cards
        ids.forEach(function (id) {
          if (idToCard[id] && brandMap[id]) {
            idToCard[id].setAttribute('data-brand', brandMap[id]);
          }
        });

        // Group by brand
        var seen = {};
        cards.forEach(function (card) {
          var brand = (card.getAttribute('data-brand') || '').trim();
          if (!brand) return;
          if (!seen[brand]) {
            seen[brand] = { face: card, count: 0 };
          } else {
            seen[brand].count++;
            card.style.display = 'none';
          }
        });

        // Add "& N more" badge to face cards
        Object.keys(seen).forEach(function (brand) {
          var g = seen[brand];
          if (g.count === 0) return;
          var imageDiv = g.face.querySelector('.image');
          if (!imageDiv) return;
          if (imageDiv.style.position !== 'relative') {
            imageDiv.style.position = 'relative';
          }
          var badge = document.createElement('a');
          badge.className = 'brand-more-badge';
          badge.href = '/products?keyword=' + encodeURIComponent(brand);
          badge.title = brand + ' брэндийн бусад бараанууд';
          badge.innerHTML = '&#43;&nbsp;' + g.count + ' дэлгэрэнгүй';
          imageDiv.appendChild(badge);
        });
      })
      .catch(function () { /* silent fail */ });
  }

  // ── Detail page "More from brand" ─────────────────────────────
  function initDetailBrand() {
    // Try to get current product ID and brand from page
    var productId = 0;
    var brandName = '';

    // From URL: /products/12345
    var m = window.location.pathname.match(/\/products\/(\d+)/);
    if (m) productId = parseInt(m[1]);
    if (!productId) return;

    // Fetch brand for this product
    fetch('/brand-data.php?ids=' + productId)
      .then(function (r) { return r.json(); })
      .then(function (brandMap) {
        brandName = brandMap[productId] || '';
        if (!brandName) return;

        // Now fetch other products from same brand
        return fetch('/brand-data.php?brand=' + encodeURIComponent(brandName) + '&exclude=' + productId);
      })
      .then(function (r) { return r && r.json(); })
      .then(function (items) {
        if (!items || !items.length) return;

        // Find insertion point: after .similar-products or at end of main content
        var anchor = document.querySelector('.container.product-detail, .container.mt-3, main .container');
        if (!anchor) anchor = document.querySelector('.container');
        if (!anchor) return;

        var section = document.createElement('div');
        section.className = 'brand-more-section mt-4 mb-5';

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
          card.innerHTML = '<img src="' + p.image + '" alt="" loading="lazy">' +
            '<div class="brand-more-card-body">' +
            '<div class="brand-more-card-name">' + p.name + '</div>' +
            '</div>';
          grid.appendChild(card);
        });
        section.appendChild(grid);
        anchor.appendChild(section);
      })
      .catch(function () { /* silent */ });
  }

  function run() {
    injectCSS();
    var path = window.location.pathname;
    var isDetail = /\/products\/\d/.test(path);

    if (isDetail) {
      setTimeout(initDetailBrand, 300);
    } else {
      // Run after a short delay to let page fully render
      setTimeout(initListingGrouping, 400);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
