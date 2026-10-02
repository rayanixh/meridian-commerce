<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/header.php';
$totals = cart_totals();
if (empty($totals['items'])) redirect(base_url('cart.php'));
$pageTitle = 'Checkout';
$pageJs = ['js/checkout.js'];

$pdo = db();
$methods = $pdo->query("SELECT * FROM payment_methods WHERE status = 'active' ORDER BY position ASC")->fetchAll();
?>
<div class="page-header"><div class="container" style="padding-top:24px">
    <div class="eyebrow">Checkout</div>
    <h1 class="section-title" style="font-size:30px;margin-top:8px">Complete your order</h1>
</div></div>

<div class="checkout-page">
    <div class="container">
        <div class="checkout-grid">
            <form data-checkout-form>
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

                <section class="checkout-step">
                    <h3 class="checkout-step-title"><span class="checkout-step-num">1</span> Customer details</h3>
                    <div class="form-row">
                        <div class="form-field"><label for="full_name">Full name *</label><input type="text" id="full_name" name="full_name" required value="<?= e(old('full_name')) ?>"></div>
                        <div class="form-field"><label for="phone">Phone *</label><input type="tel" id="phone" name="phone" required value="<?= e(old('phone')) ?>"></div>
                    </div>
                    <div class="form-field" style="margin-bottom:14px"><label for="email">Email (optional)</label><input type="email" id="email" name="email" value="<?= e(old('email')) ?>"></div>
                    <div class="form-field" style="margin-bottom:14px"><label for="address">Address *</label><textarea id="address" name="address" required><?= e(old('address')) ?></textarea></div>
                    <div class="form-row">
                        <div class="form-field"><label for="city">City *</label><input type="text" id="city" name="city" required value="<?= e(old('city')) ?>"></div>
                        <div class="form-field"><label for="area">Area</label><input type="text" id="area" name="area" value="<?= e(old('area')) ?>"></div>
                    </div>
                    <div class="form-field" style="margin-bottom:0"><label for="postal_code">Postal code</label><input type="text" id="postal_code" name="postal_code" value="<?= e(old('postal_code')) ?>"></div>
                </section>

                <section class="checkout-step">
                    <h3 class="checkout-step-title"><span class="checkout-step-num">2</span> Delivery</h3>
                    <div class="form-row">
                        <label class="form-radio">
                            <input type="radio" name="shipping" value="inside" required>
                            <div class="form-radio-content">
                                <span class="form-radio-title">Inside Dhaka</span>
                                <span class="form-radio-desc">Delivery in 1–2 days</span>
                            </div>
                            <span style="font-size:14px;font-weight:600;flex-shrink:0"><?= e(format_money((float)setting('shipping_inside', 60))) ?></span>
                        </label>
                        <label class="form-radio">
                            <input type="radio" name="shipping" value="outside" required>
                            <div class="form-radio-content">
                                <span class="form-radio-title">Outside Dhaka</span>
                                <span class="form-radio-desc">Delivery in 2–4 days</span>
                            </div>
                            <span style="font-size:14px;font-weight:600;flex-shrink:0"><?= e(format_money((float)setting('shipping_outside', 120))) ?></span>
                        </label>
                    </div>
                </section>

                <section class="checkout-step">
                    <h3 class="checkout-step-title"><span class="checkout-step-num">3</span> Payment</h3>
                    <div class="form-row">
                        <?php foreach ($methods as $m): ?>
                        <label class="form-radio" style="grid-column:span 1;flex:1 1 100%">
                            <input type="radio" name="payment_method" value="<?= e($m['code']) ?>" required <?= $m['code'] === 'cod' ? 'checked' : '' ?>>
                            <div class="payment-logo">
                                <?php if (!empty($m['logo']) && file_exists(APP_ROOT . '/' . $m['logo'])): ?>
                                    <img src="<?= e(APP_URL . '/' . $m['logo']) ?>" alt="<?= e($m['name']) ?>">
                                <?php else: ?>
                                    <span class="payment-logo-fallback"><?= payment_logo_svg($m['code']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="form-radio-content">
                                <span class="form-radio-title"><?= e($m['name']) ?></span>
                                <span class="form-radio-desc"><?= e($m['instructions'] ?: 'Pay securely with ' . $m['name']) ?></span>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <div data-tx-fields class="transaction-fields" style="margin-top:18px;display:flex;flex-direction:column;gap:14px">
                        <div class="form-field" style="margin-bottom:0">
                            <label for="transaction_id">Transaction ID *</label>
                            <input type="text" id="transaction_id" name="transaction_id" placeholder="e.g. 8N4KQ9X3">
                        </div>
                        <div class="form-field" style="margin-bottom:0">
                            <label for="payment_proof">Payment screenshot (optional)</label>
                            <input type="file" id="payment_proof" name="payment_proof" accept="image/*" style="padding:10px;border:1px solid var(--c-border);border-radius:8px">
                        </div>
                    </div>

                    <div data-cod-note style="display:none;background:#FAFAFA;border:1px dashed var(--c-border);border-radius:10px;padding:14px 16px;font-size:13px;color:var(--c-muted);margin-top:14px;line-height:1.5">
                        Pay with cash when your order is delivered. No transaction ID required.
                    </div>
                </section>

                <button type="submit" class="btn btn-primary btn-block" data-submit-order style="margin-top:8px">Place order →</button>
            </form>

            <aside class="order-summary">
                <h3>Order summary</h3>
                <?php foreach ($totals['items'] as $item):
                    $img = $item['image'] ?: 'assets/images/defaults/product.svg';
                ?>
                <div class="cart-line">
                    <img class="cart-line-img" style="width:56px;height:56px" src="<?= e(APP_URL . '/' . $img) ?>" alt="">
                    <div class="cart-line-info">
                        <a href="<?= base_url('product.php?id=' . (int)$item['product_id']) ?>" class="cart-line-name" style="font-size:13px"><?= e($item['name']) ?></a>
                        <div class="cart-line-price" style="font-size:12px;color:var(--c-muted)">Qty <?= (int)$item['qty'] ?> × <?= e(format_money($item['price'])) ?></div>
                        <div style="font-size:14px;font-weight:600"><?= e(format_money($item['subtotal'])) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <div style="padding-top:14px;border-top:1px solid var(--c-border);margin-top:6px">
                    <div class="summary-row"><span class="label">Subtotal</span><span class="value"><?= e(format_money($totals['subtotal'])) ?></span></div>
                    <?php if ($totals['discount'] > 0): ?>
                    <div class="summary-row" style="color:var(--c-success)"><span class="label">Discount</span><span class="value">−<?= e(format_money($totals['discount'])) ?></span></div>
                    <?php endif; ?>
                    <div class="summary-row"><span class="label">Shipping</span><span class="value" data-summary-shipping>—</span></div>
                    <div class="summary-row total"><span class="label">Total</span><span class="value" data-summary-total><?= e(format_money($totals['total'])) ?></span></div>
                </div>
            </aside>
        </div>
    </div>
</div>

<script>
(function () {
  const sym = <?= json_encode(setting('currency_symbol', '৳')) ?>;
  const subtotal = <?= json_encode((float)$totals['subtotal']) ?>;
  const discount = <?= json_encode((float)$totals['discount']) ?>;
  const free = <?= json_encode((float)setting('shipping_free_threshold', 0)) ?>;
  const inside = <?= json_encode((float)setting('shipping_inside', 60)) ?>;
  const outside = <?= json_encode((float)setting('shipping_outside', 120)) ?>;
  const fmt = (n) => sym + (Math.round(n * 100) / 100).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');

  const shipDisplay = document.querySelector('[data-summary-shipping]');
  const totalDisplay = document.querySelector('[data-summary-total]');
  function update() {
    const sel = document.querySelector('[name="shipping"]:checked');
    if (!sel) { if (shipDisplay) shipDisplay.textContent = '—'; return; }
    let fee = sel.value === 'inside' ? inside : outside;
    if (free > 0 && subtotal >= free) fee = 0;
    if (shipDisplay) shipDisplay.textContent = fee > 0 ? fmt(fee) : 'Free';
    if (totalDisplay) totalDisplay.textContent = fmt(subtotal - discount + fee);
  }
  document.querySelectorAll('[name="shipping"]').forEach(o => o.addEventListener('change', update));
  update();
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
