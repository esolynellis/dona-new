/* DONA Admin Inject - adds "Бараа нэмэх" button to admin products page */
(function () {
  if (!location.pathname.includes('/admin/products')) return;

  const API = '/dona-bookmarklet.php?key=dona2025&action=import_ids';
  let running = false;
  let totalAdded = parseInt(sessionStorage.getItem('__huipi_added') || 0);

  function getProductIds() {
    const ids = [];
    document.querySelectorAll('table tbody tr').forEach(row => {
      const tds = row.querySelectorAll('td');
      for (let i = 0; i < Math.min(4, tds.length); i++) {
        const n = parseInt((tds[i].innerText || '').trim());
        if (n > 100 && n < 10000000) { ids.push(n); break; }
      }
    });
    return ids;
  }

  function getMaxPage() {
    let max = 1;
    document.querySelectorAll('.pagination a[href*="page="]').forEach(a => {
      const m = a.href.match(/[?&]page=(\d+)/);
      if (m && parseInt(m[1]) > max) max = parseInt(m[1]);
    });
    return max;
  }

  function getCurrentPage() {
    const m = location.search.match(/[?&]page=(\d+)/);
    return m ? parseInt(m[1]) : 1;
  }

  async function doImport(btn) {
    if (running) return;
    running = true;
    btn.disabled = true;
    btn.style.background = '#92400e';

    const ids = getProductIds();
    const currentPage = getCurrentPage();
    const maxPage = getMaxPage();
    btn.textContent = '⏳ Хуудас ' + currentPage + '/' + maxPage + '...';

    try {
      const res = await fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ids })
      });
      const data = await res.json();
      const added = (data.inserted || 0) + (data.activated || 0);
      totalAdded += added;
      sessionStorage.setItem('__huipi_added', totalAdded);

      if (currentPage < maxPage) {
        sessionStorage.setItem('__huipi_pending', '1');
        btn.textContent = '✅ +' + totalAdded + ' | дараагийн хуудас...';
        // Build next page URL preserving existing params
        const url = new URL(location.href);
        url.searchParams.set('page', currentPage + 1);
        setTimeout(() => { location.href = url.toString(); }, 700);
      } else {
        sessionStorage.removeItem('__huipi_pending');
        sessionStorage.removeItem('__huipi_added');
        btn.textContent = '✅ Дууслаа! +' + totalAdded;
        btn.style.background = '#15803d';
        btn.disabled = false;
        running = false;
        totalAdded = 0;
        setTimeout(() => { btn.textContent = '➕ Бараа нэмэх'; btn.style.background = ''; }, 4000);
      }
    } catch (e) {
      sessionStorage.removeItem('__huipi_pending');
      sessionStorage.removeItem('__huipi_added');
      btn.textContent = '❌ Алдаа';
      btn.disabled = false;
      btn.style.background = '#dc2626';
      running = false;
      setTimeout(() => { btn.textContent = '➕ Бараа нэмэх'; btn.style.background = ''; }, 3000);
    }
  }

  function injectButton() {
    if (document.getElementById('__dona-import-btn')) return;

    // Find the "Үүсгэх" create button link
    const createLink = document.querySelector('a[href*="products/create"]');
    if (!createLink) return;

    const btn = document.createElement('button');
    btn.id = '__dona-import-btn';
    btn.textContent = '➕ Бараа нэмэх';
    btn.style.cssText = [
      'display:inline-block',
      'margin-left:8px',
      'padding:6px 14px',
      'background:#d97706',
      'color:#fff',
      'border:none',
      'border-radius:6px',
      'font-weight:600',
      'font-size:14px',
      'cursor:pointer',
      'vertical-align:middle',
      'transition:background .2s',
    ].join(';');

    btn.onmouseenter = () => { if (!running) btn.style.background = '#b45309'; };
    btn.onmouseleave = () => { if (!running) btn.style.background = '#d97706'; };
    btn.onclick = () => doImport(btn);

    createLink.insertAdjacentElement('afterend', btn);

    // Auto-continue if navigating between pages
    if (sessionStorage.getItem('__huipi_pending')) {
      setTimeout(() => doImport(btn), 400);
    }
  }

  // Try injecting immediately, then watch for Vue to render the button
  function tryInject() {
    injectButton();
    if (!document.getElementById('__dona-import-btn')) {
      const observer = new MutationObserver(() => {
        injectButton();
        if (document.getElementById('__dona-import-btn')) observer.disconnect();
      });
      observer.observe(document.body, { childList: true, subtree: true });
      // Stop watching after 10s
      setTimeout(() => observer.disconnect(), 10000);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', tryInject);
  } else {
    tryInject();
  }
})();
