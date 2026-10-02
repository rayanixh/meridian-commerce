<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/header.php';
$totals = cart_totals();
$pageTitle = 'Your bag';
$pageJs = ['js/cart.js'];
?>
<div class="page-header"><div class="container" style="padding-top:24px">
    <div class="eyebrow">Bag</div>
    <h1 class="section-title" style="font-size:30px;margin-top:8px">Your selection</h1>
</div></div>

<div class="cart-page">
    <div class="container">
        <?php if (empty($totals['items'])): ?>
        <div class="card" style="text-align:center;padding:60px 20px;max-width:560px;margin:0 auto">
            <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.2" style="color:var(--c-muted);margin:0 auto 16px;display:block"><path d="M5 7h14l-1.5 11a2 2 0 01-2 1.7H8.5a2 2 0 01-2-1.7L5 7z"/><path d="M9 7V5a3 3 0 016 0v2"/></svg>
            <h2 style="font-size:22px;font-weight:600;margin-bottom:8px">Your bag is empty</h2>
            <p style="color:var(--c-muted);margin-bottom:24px">Add something to your bag to get started.</p>
            <a href="<?= base_url('index.php') ?>" class="btn btn-primary">Browse products →</a>
        </div>
        <?php else: ?>
        <div class="cart-grid">
            <div class="cart-items">
                <?php foreach ($totals['items'] as $key => $item):
                    $img = $item['image'] ?: 'assets/images/defaults/product.svg';
                ?>
                <div class="cart-line">
                    <img class="cart-line-img" src="<?= e(APP_URL . '/' . $img) ?>" alt="">
                    <div class="cart-line-info">
                        <a href="<?= base_url('product.php?id=' . (int)$item['product_id']) ?>" class="cart-line-name"><?= e($item['name']) ?></a>
                        <?php if (!empty($item['variant'])): ?>
                        <div class="cart-line-variant"><?= e(implode(' · ', array_map(fn($k,$v) => "$k: $v", array_keys($item['variant']), $item['variant']))) ?></div>
                        <?php endif; ?>
                        <div class="cart-line-price" style="font-size:13px;color:var(--c-muted)">Unit: <?= e(format_money($item['price'])) ?></div>
                        <div class="cart-line-bottom">
                            <div class="qty-stepper" data-key="<?= e($key) ?>">
                                <button data-cart-dec type="button" aria-label="Decrease">−</button>
                                <input type="text" value="<?= (int)$item['qty'] ?>" readonly>
                                <button data-cart-inc type="button" aria-label="Increase">+</button>
                            </div>
                            <span style="font-size:15px;font-weight:600"><?= e(format_money($item['subtotal'])) ?></span>
                            <button class="cart-line-remove" data-cart-remove="<?= e($key) ?>" type="button" aria-label="Remove">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <aside class="cart-summary">
                <h3 class="cart-summary-title">Summary</h3>
                <form data-coupon-form class="coupon-row">
                    <input type="text" placeholder="Coupon code" value="<?= e($totals['coupon']['code'] ?? '') ?>" <?= !empty($totals['coupon']) ? 'readonly' : '' ?>>
                    <?php if (!empty($totals['coupon'])): ?>
                    <button type="button" class="btn btn-ghost" data-remove-coupon style="min-height:38px;padding:8px 12px">Remove</button>
                    <?php else: ?>
                    <button type="submit" class="btn btn-ghost" style="min-height:38px;padding:8px 12px">Apply</button>
                    <?php endif; ?>
                </form>
                <div class="summary-row"><span class="label">Subtotal</span><span class="value"><?= e(format_money($totals['subtotal'])) ?></span></div>
                <?php if ($totals['discount'] > 0): ?>
                <div class="summary-row" style="color:var(--c-success)"><span class="label">Discount<?= !empty($totals['coupon']) ? ' (' . e($totals['coupon']['code']) . ')' : '' ?></span><span class="value">−<?= e(format_money($totals['discount'])) ?></span></div>
                <?php endif; ?>
                <div class="summary-row"><span class="label">Shipping</span><span class="value"><?= $totals['shipping'] > 0 ? e(format_money($totals['shipping'])) : '—' ?></span></div>
                <div class="summary-row total"><span class="label">Total</span><span class="value"><?= e(format_money($totals['total'])) ?></span></div>
                <a href="<?= base_url('checkout.php') ?>" class="btn btn-primary btn-block" style="margin-top:18px">Continue to checkout →</a>
                <a href="<?= base_url('index.php') ?>" class="btn btn-ghost btn-block" style="margin-top:8px">Continue shopping</a>
            </aside>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
