/* product page */
(function () {
  const main = document.querySelector('[data-gallery-main] img');
  const thumbs = document.querySelectorAll('[data-thumb]');
  thumbs.forEach(t => t.addEventListener('click', () => {
    thumbs.forEach(x => x.classList.remove('active'));
    t.classList.add('active');
    if (main) main.src = t.dataset.thumb;
  }));
  if (main) main.addEventListener('click', () => main.classList.toggle('zoomed'));

  document.querySelectorAll('[data-variant-group]').forEach(group => {
    group.querySelectorAll('[data-variant-value]').forEach(opt => {
      opt.addEventListener('click', () => {
        group.querySelectorAll('[data-variant-value]').forEach(o => o.classList.remove('active'));
        opt.classList.add('active');
        collectVariants();
      });
    });
  });
  function collectVariants() {
    const v = {};
    document.querySelectorAll('[data-variant-group]').forEach(g => {
      const a = g.querySelector('[data-variant-value].active');
      if (a) v[g.dataset.variantGroup] = a.dataset.variantValue;
    });
    const addBtn = document.querySelector('[data-add-to-cart]');
    if (addBtn) addBtn.dataset.variant = JSON.stringify(v);
  }
  collectVariants();

  const tabLinks = document.querySelectorAll('[data-tab]');
  const tabPanes = document.querySelectorAll('[data-tab-pane]');
  tabLinks.forEach(l => l.addEventListener('click', () => {
    tabLinks.forEach(x => x.classList.remove('active'));
    tabPanes.forEach(x => x.classList.remove('active'));
    l.classList.add('active');
    const p = document.querySelector('[data-tab-pane="' + l.dataset.tab + '"]');
    if (p) p.classList.add('active');
  }));

  document.addEventListener('click', (e) => {
    const dec = e.target.closest('[data-qty-dec]');
    const inc = e.target.closest('[data-qty-inc]');
    const input = document.querySelector('[data-qty-input]');
    if (dec && input) input.value = Math.max(1, parseInt(input.value, 10) - 1);
    if (inc && input) input.value = parseInt(input.value, 10) + 1;
  });

  const addBtn = document.querySelector('[data-add-to-cart]');
  if (addBtn) {
    addBtn.addEventListener('click', (e) => {
      e.preventDefault();
      const input = document.querySelector('[data-qty-input]');
      const qty = input ? Math.max(1, parseInt(input.value, 10) || 1) : 1;
      let variant = {};
      try { variant = JSON.parse(addBtn.dataset.variant || '{}'); } catch (err) {}
      addBtn.disabled = true;
      fetch(MERIDIAN.base + '/api/cart.php', {
        method: 'POST', credentials: 'include',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': MERIDIAN.csrf, 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ action: 'add', product_id: parseInt(addBtn.dataset.addToCart, 10), qty, variant })
      }).then(r => r.json()).then(d => {
        addBtn.disabled = false;
        if (d.ok) {
          window.toast('Added to your bag');
          if (d.count !== undefined) {
            document.querySelectorAll('.cart-badge').forEach(b => { b.textContent = d.count; if (b.parentElement && !b.parentElement.querySelector('.cart-badge')) {} });
          }
        } else { window.toast(d.error || 'Could not add'); }
      });
    });
  }
  const buyNow = document.querySelector('[data-buy-now]');
  if (buyNow) {
    buyNow.addEventListener('click', (e) => {
      e.preventDefault();
      const a = document.querySelector('[data-add-to-cart]');
      if (a) a.click();
      setTimeout(() => { window.location.href = MERIDIAN.base + '/checkout.php'; }, 500);
    });
  }
})();
