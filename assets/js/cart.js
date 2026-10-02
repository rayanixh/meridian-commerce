/* cart page */
(function () {
  document.addEventListener('click', (e) => {
    const dec = e.target.closest('[data-cart-dec]');
    if (dec) {
      const input = dec.parentElement.querySelector('input');
      const key = dec.parentElement.dataset.key;
      const q = Math.max(1, parseInt(input.value, 10) - 1);
      if (q !== parseInt(input.value, 10)) update(key, q);
    }
    const inc = e.target.closest('[data-cart-inc]');
    if (inc) {
      const input = inc.parentElement.querySelector('input');
      const key = inc.parentElement.dataset.key;
      update(key, parseInt(input.value, 10) + 1);
    }
    const rm = e.target.closest('[data-cart-remove]');
    if (rm) {
      const key = rm.dataset.cartRemove;
      fetch(MERIDIAN.base + '/api/cart.php', {
        method: 'POST', credentials: 'include',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': MERIDIAN.csrf, 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ action: 'remove', key })
      }).then(r => r.json()).then(d => { if (d.ok) location.reload(); });
    }
  });
  function update(key, qty) {
    fetch(MERIDIAN.base + '/api/cart.php', {
      method: 'POST', credentials: 'include',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': MERIDIAN.csrf, 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify({ action: 'update', key, qty })
    }).then(r => r.json()).then(d => { if (d.ok) location.reload(); });
  }
  const couponForm = document.querySelector('[data-coupon-form]');
  if (couponForm) {
    couponForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const code = couponForm.querySelector('input').value.trim();
      if (!code) return;
      fetch(MERIDIAN.base + '/api/cart.php', {
        method: 'POST', credentials: 'include',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': MERIDIAN.csrf, 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ action: 'apply_coupon', code })
      }).then(r => r.json()).then(d => { if (d.ok) location.reload(); else window.toast(d.error || 'Invalid coupon'); });
    });
  }
  const removeCoupon = document.querySelector('[data-remove-coupon]');
  if (removeCoupon) {
    removeCoupon.addEventListener('click', () => {
      fetch(MERIDIAN.base + '/api/cart.php', {
        method: 'POST', credentials: 'include',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': MERIDIAN.csrf, 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ action: 'remove_coupon' })
      }).then(() => location.reload());
    });
  }
})();
