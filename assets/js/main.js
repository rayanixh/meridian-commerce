/* main.js — global interactions: drawers, cart, search, toasts, mobile nav */
(function () {
  'use strict';
  const $ = (s, c) => (c || document).querySelector(s);
  const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));
  const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  /* Toast */
  window.toast = function (msg, kind) {
    const t = document.createElement('div');
    t.className = 'toast' + (kind ? ' toast-' + kind : '');
    t.textContent = msg;
    document.body.appendChild(t);
    requestAnimationFrame(() => t.classList.add('show'));
    setTimeout(() => {
      t.classList.remove('show');
      setTimeout(() => t.remove(), 300);
    }, 2400);
  };

  /* Drawers */
  function openDrawer(name) {
    const el = document.querySelector('[data-' + name + '-drawer]');
    if (el) { el.classList.add('open'); document.body.style.overflow = 'hidden'; }
  }
  function closeDrawers() {
    $$('.drawer.open').forEach(d => d.classList.remove('open'));
    document.body.style.overflow = '';
  }
  document.addEventListener('click', (e) => {
    const t = e.target.closest('[data-nav-toggle]');
    if (t) { e.preventDefault(); openDrawer('nav'); return; }
    const s = e.target.closest('[data-search-toggle]');
    if (s) { e.preventDefault(); openDrawer('search'); setTimeout(() => { const i = $('[data-search-input]'); if (i) i.focus(); }, 100); return; }
    const c = e.target.closest('[data-cart-toggle]');
    if (c) { e.preventDefault(); openDrawer('cart'); loadCart(); return; }
    const x = e.target.closest('[data-drawer-close]');
    if (x) { e.preventDefault(); closeDrawers(); return; }
    if (e.target.classList && e.target.classList.contains('drawer-overlay')) closeDrawers();
  });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeDrawers(); });

  /* Cart badge */
  function updateCartBadge(n) {
    $$('.cart-badge, .bottom-badge').forEach(b => b.textContent = n);
    $$('.cart-btn').forEach(b => {
      let badge = b.querySelector('.cart-badge');
      if (n > 0) {
        if (!badge) { badge = document.createElement('span'); badge.className = 'cart-badge'; b.appendChild(badge); }
        badge.textContent = n;
      } else if (badge) { badge.remove(); }
    });
  }

  /* Cart API */
  function cartApi(body) {
    return fetch(MERIDIAN.base + '/api/cart.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': MERIDIAN.csrf, 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify(body)
    }).then(r => r.json());
  }

  function loadCart() {
    cartApi({ action: 'list' }).then(data => {
      if (data.count !== undefined) updateCartBadge(data.count);
      renderCart(data);
    }).catch(() => {});
  }

  function renderCart(data) {
    const body = $('[data-cart-body]');
    const foot = $('[data-cart-foot]');
    if (!body) return;
    if (!data.items || data.items.length === 0) {
      body.innerHTML = '<div class="empty-state"><p>Your bag is empty.</p></div>';
      if (foot) foot.style.display = 'none';
      return;
    }
    if (foot) foot.style.display = '';
    let html = '';
    data.items.forEach(item => {
      const v = item.variant && Object.keys(item.variant).length
        ? '<div class="cart-line-variant">' + Object.values(item.variant).join(' · ') + '</div>' : '';
      html += '<div class="cart-line">';
      html += '<img class="cart-line-img" src="' + esc(item.image) + '" alt="">';
      html += '<div class="cart-line-info">';
      html += '<a href="' + MERIDIAN.base + '/product.php?id=' + item.product_id + '" class="cart-line-name">' + esc(item.name) + '</a>';
      html += v;
      html += '<div class="cart-line-price" style="font-size:13px;color:var(--c-muted)">Unit: ' + esc(item.price_fmt) + '</div>';
      html += '<div class="cart-line-bottom">';
      html += '<div class="qty-stepper" data-key="' + esc(item.key) + '">';
      html += '<button data-dec type="button" aria-label="Decrease">−</button>';
      html += '<input type="text" value="' + item.qty + '" readonly>';
      html += '<button data-inc type="button" aria-label="Increase">+</button>';
      html += '</div>';
      html += '<span style="font-size:14px;font-weight:600">' + esc(item.line_total) + '</span>';
      html += '<button class="cart-line-remove" data-remove="' + esc(item.key) + '" type="button" aria-label="Remove">';
      html += '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>';
      html += '</button></div></div></div>';
    });
    html += '<div style="padding-top:14px;border-top:1px solid var(--c-border);margin-top:6px">';
    html += '<div class="summary-row"><span class="label">Subtotal</span><span class="value">' + esc(data.subtotal_fmt) + '</span></div>';
    if (data.discount > 0) html += '<div class="summary-row" style="color:var(--c-success)"><span class="label">Discount</span><span class="value">−' + esc(data.discount_fmt) + '</span></div>';
    if (data.shipping > 0) html += '<div class="summary-row"><span class="label">Shipping</span><span class="value">' + esc(data.shipping_fmt) + '</span></div>';
    html += '<div class="summary-row total"><span class="label">Total</span><span class="value">' + esc(data.total_fmt) + '</span></div>';
    html += '</div>';
    body.innerHTML = html;
  }

  document.addEventListener('click', (e) => {
    const addBtn = e.target.closest('[data-add-to-cart]');
    if (addBtn) {
      e.preventDefault();
      const pid = addBtn.dataset.addToCart;
      let variant = {};
      try { variant = addBtn.dataset.variant ? JSON.parse(addBtn.dataset.variant) : {}; } catch (err) {}
      addBtn.disabled = true;
      cartApi({ action: 'add', product_id: parseInt(pid, 10), qty: 1, variant })
        .then(data => {
          addBtn.disabled = false;
          if (data.ok) {
            if (data.count !== undefined) updateCartBadge(data.count);
            window.toast('Added to your bag');
            openDrawer('cart');
            renderCart(data);
          } else { window.toast(data.error || 'Could not add to cart'); }
        }).catch(() => { addBtn.disabled = false; window.toast('Network error'); });
      return;
    }

    const removeBtn = e.target.closest('[data-remove]');
    if (removeBtn) {
      const key = removeBtn.dataset.remove;
      cartApi({ action: 'remove', key }).then(d => { if (d.ok) { updateCartBadge(d.count); renderCart(d); if (d.count === 0) location.reload(); } });
      return;
    }

    const stepper = e.target.closest('.qty-stepper');
    if (stepper && stepper.dataset.key) {
      const input = stepper.querySelector('input');
      let qty = parseInt(input.value, 10) || 1;
      if (e.target.matches('[data-inc]')) qty += 1;
      else if (e.target.matches('[data-dec]')) qty = Math.max(1, qty - 1);
      else return;
      cartApi({ action: 'update', key: stepper.dataset.key, qty })
        .then(d => { if (d.ok) { updateCartBadge(d.count); renderCart(d); if (typeof d.qty !== 'undefined') input.value = d.qty; } });
    }
  });

  /* Search */
  let searchTimer = null;
  const searchInput = $('[data-search-input]');
  if (searchInput) {
    searchInput.addEventListener('input', () => {
      clearTimeout(searchTimer);
      const q = searchInput.value.trim();
      const r = $('[data-search-results]');
      if (q.length < 2) { if (r) r.innerHTML = ''; return; }
      searchTimer = setTimeout(() => {
        fetch(MERIDIAN.base + '/api/search.php?q=' + encodeURIComponent(q), { credentials: 'include', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
          .then(r => r.json())
          .then(data => {
            if (!r) return;
            if (!data.results || data.results.length === 0) {
              r.innerHTML = '<div class="empty-state"><p>No results for &ldquo;' + esc(q) + '&rdquo;</p></div>';
              return;
            }
            r.innerHTML = data.results.map(it => (
              '<a class="search-suggestion" href="' + MERIDIAN.base + '/product.php?id=' + it.id + '" style="display:flex;align-items:center;gap:12px;padding:10px;border-radius:10px;min-width:0">' +
              '<img src="' + esc(it.image) + '" alt="" style="width:48px;height:48px;border-radius:8px;object-fit:cover;flex-shrink:0">' +
              '<div class="search-suggestion-info" style="flex:1;min-width:0">' +
              '<div class="search-suggestion-name" style="font-size:14px;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + esc(it.name) + '</div>' +
              '<div class="search-suggestion-price" style="font-size:13px;color:var(--c-muted)">' + esc(it.price_fmt) + '</div>' +
              '</div></a>'
            )).join('');
          });
      }, 200);
    });
  }

  /* Carousels */
  $$('[data-carousel]').forEach(carousel => {
    const track = carousel.querySelector('[data-carousel-track]');
    const prev = carousel.querySelector('[data-carousel-prev]');
    const next = carousel.querySelector('[data-carousel-next]');
    if (!track) return;
    function update() {
      if (!prev || !next) return;
      prev.disabled = track.scrollLeft <= 5;
      next.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 5;
    }
    if (prev) prev.addEventListener('click', () => track.scrollBy({ left: -track.clientWidth * 0.8, behavior: 'smooth' }));
    if (next) next.addEventListener('click', () => track.scrollBy({ left: track.clientWidth * 0.8, behavior: 'smooth' }));
    track.addEventListener('scroll', update);
    window.addEventListener('resize', update);
    update();
  });

  /* Header scroll */
  const header = $('#siteHeader');
  if (header) {
    window.addEventListener('scroll', () => {
      header.classList.toggle('scrolled', window.scrollY > 8);
    }, { passive: true });
  }

  /* Newsletter */
  $$('[data-newsletter]').forEach(f => {
    f.addEventListener('submit', (e) => {
      e.preventDefault();
      const email = f.querySelector('input').value.trim();
      if (!email) return;
      window.toast('Thanks — you’re on the list.');
      f.reset();
    });
  });

  /* Wishlist toggle */
  document.addEventListener('click', (e) => {
    const w = e.target.closest('[data-wishlist]');
    if (w) { e.preventDefault(); w.classList.toggle('active'); window.toast(w.classList.contains('active') ? 'Added to wishlist' : 'Removed from wishlist'); }
  });

  /* Initial cart load */
  loadCart();
})();
