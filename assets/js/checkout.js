/* checkout */
(function () {
  const form = document.querySelector('[data-checkout-form]');
  if (!form) return;

  const txFields = form.querySelector('[data-tx-fields]');
  const paymentOpts = form.querySelectorAll('[name="payment_method"]');
  const codNote = form.querySelector('[data-cod-note]');
  function refresh() {
    const sel = form.querySelector('[name="payment_method"]:checked');
    if (!sel) return;
    if (sel.value === 'cod') {
      if (txFields) txFields.style.display = 'none';
      if (codNote) codNote.style.display = '';
      txFields && txFields.querySelectorAll('input,textarea').forEach(i => i.disabled = true);
    } else {
      if (txFields) txFields.style.display = '';
      if (codNote) codNote.style.display = 'none';
      txFields && txFields.querySelectorAll('input,textarea').forEach(i => i.disabled = false);
    }
  }
  paymentOpts.forEach(o => o.addEventListener('change', refresh));
  refresh();

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(form).entries());
    const btn = form.querySelector('[data-submit-order]');
    if (btn) { btn.disabled = true; btn.textContent = 'Placing order…'; }
    fetch(MERIDIAN.base + '/api/checkout.php', {
      method: 'POST', credentials: 'include',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': MERIDIAN.csrf, 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify(data)
    }).then(r => r.json()).then(d => {
      if (d.ok && d.redirect) { window.location.href = d.redirect; }
      else {
        if (btn) { btn.disabled = false; btn.textContent = 'Place order →'; }
        window.toast(d.error || 'Could not place order.');
        if (d.errors) {
          Object.entries(d.errors).forEach(([k, msg]) => {
            const f = form.querySelector(`[name="${k}"]`);
            if (f) {
              let errEl = f.parentElement.querySelector('.form-error, .error-text');
              if (!errEl) { errEl = document.createElement('div'); errEl.className = 'error-text'; f.parentElement.appendChild(errEl); }
              errEl.textContent = msg;
            }
          });
        }
      }
    }).catch(() => { if (btn) { btn.disabled = false; btn.textContent = 'Place order →'; } window.toast('Network error.'); });
  });
})();
