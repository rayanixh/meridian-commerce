<?php
if (!defined('APP_NAME')) { http_response_code(403); exit; }

$siteName = setting('site_name', 'Meridian');
$footerText = setting('footer_text', '© ' . date('Y') . ' ' . $siteName);
$footerLogo = site_logo('footer');
$waOn = setting('whatsapp_enabled', '0') === '1' && setting('whatsapp_recipient', '');
$msOn = setting('messenger_enabled', '0') === '1' && setting('messenger_url', '');
$waNum = setting('whatsapp_recipient', '');
$msUrl = setting('messenger_url', '');

$footerNav = [];
$bottomNav = [];
try {
    $pdo = db();
    if ($pdo) {
        foreach ([
            ['footer', &$footerNav],
            ['bottom', &$bottomNav],
        ] as [$loc, &$ref]) {
            $stmt = $pdo->prepare("SELECT * FROM navigation_items WHERE location = ? AND status = 'active' ORDER BY position ASC");
            $stmt->execute([$loc]);
            $ref = $stmt->fetchAll();
        }
    }
} catch (Throwable $e) {}

$icons = [
    'home'    => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-8 9 8v9a2 2 0 01-2 2h-4v-7h-6v7H5a2 2 0 01-2-2v-9z"/></svg>',
    'grid'    => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>',
    'search'  => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.5" y2="16.5"/></svg>',
    'bag'     => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 7h14l-1.5 11a2 2 0 01-2 1.7H8.5a2 2 0 01-2-1.7L5 7z"/><path d="M9 7V5a3 3 0 016 0v2"/></svg>',
    'user'    => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-7 8-7s8 3 8 7"/></svg>',
    'heart'   => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 6.6a5.5 5.5 0 00-7.8 0L12 7.6l-1-1a5.5 5.5 0 10-7.8 7.8l1 1L12 23l7.8-7.8 1-1a5.5 5.5 0 000-7.6z"/></svg>',
    'star'    => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.1 8.5 22 9.3 17 14.1 18.2 21 12 17.8 5.8 21 7 14.1 2 9.3 8.9 8.5"/></svg>',
    'sparkle' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3z"/><path d="M19 16l.7 2 2 .7-2 .7-.7 2-.7-2-2-.7 2-.7.7-2z"/></svg>',
    'book'    => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5a2 2 0 012-2h12v18H6a2 2 0 01-2-2V5z"/><line x1="8" y1="7" x2="16" y2="7"/><line x1="8" y1="11" x2="14" y2="11"/></svg>',
];
?>
</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <div class="footer-brand">
                <?php if ($footerLogo): ?>
                    <img src="<?= e($footerLogo) ?>" alt="<?= e($siteName) ?>" class="footer-logo">
                <?php else: ?>
                    <span class="brand-mark">M</span>
                <?php endif; ?>
                <span class="footer-name"><?= e($siteName) ?></span>
            </div>
            <p class="footer-tagline"><?= e(setting('site_tagline', '')) ?></p>
            <div class="footer-contact">
                <?php if ($phone = setting('contact_phone', '')): ?><div><?= e($phone) ?></div><?php endif; ?>
                <?php if ($email = setting('contact_email', '')): ?><div><?= e($email) ?></div><?php endif; ?>
                <?php if ($addr = setting('contact_address', '')): ?><div><?= e($addr) ?></div><?php endif; ?>
            </div>
        </div>

        <div>
            <h4>Shop</h4>
            <ul class="footer-list">
                <?php foreach ($footerNav as $ni): ?>
                <li><a href="<?= e(base_url($ni['url'])) ?>"><?= e($ni['label']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div>
            <h4>Customer</h4>
            <ul class="footer-list">
                <li><a href="<?= base_url('track-order.php') ?>">Track order</a></li>
                <li><a href="<?= base_url('cart.php') ?>">Cart</a></li>
                <li><a href="<?= base_url('checkout.php') ?>">Checkout</a></li>
                <li><a href="<?= base_url('account.php') ?>">Account</a></li>
            </ul>
        </div>

        <div>
            <h4>Stay in the loop</h4>
            <p class="footer-tagline">Quiet updates. No noise.</p>
            <form class="newsletter-form" data-newsletter>
                <input type="email" placeholder="you@example.com" required>
                <button type="submit" class="btn btn-primary">Subscribe</button>
            </form>
            <div class="socials">
                <?php if ($ig = setting('social_instagram', '')): ?><a href="<?= e($ig) ?>" aria-label="Instagram" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg></a><?php endif; ?>
                <?php if ($fb = setting('social_facebook', '')): ?><a href="<?= e($fb) ?>" aria-label="Facebook" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M22 12a10 10 0 10-11.6 9.9V15h-2.5v-3h2.5V9.6c0-2.5 1.5-3.8 3.7-3.8 1 0 2.2.2 2.2.2v2.5h-1.2c-1.2 0-1.6.8-1.6 1.6V12h2.7l-.4 3h-2.3v6.9A10 10 0 0022 12z"/></svg></a><?php endif; ?>
                <?php if ($yt = setting('social_youtube', '')): ?><a href="<?= e($yt) ?>" aria-label="YouTube" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M21.6 7.2a3 3 0 00-2.1-2.1C17.6 4.6 12 4.6 12 4.6s-5.6 0-7.5.5A3 3 0 002.4 7.2 31 31 0 002 12a31 31 0 00.4 4.8 3 3 0 002.1 2.1c1.9.5 7.5.5 7.5.5s5.6 0 7.5-.5a3 3 0 002.1-2.1A31 31 0 0022 12a31 31 0 00-.4-4.8zM10 15.4V8.6L15.8 12 10 15.4z"/></svg></a><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="container">
            <span><?= e($footerText) ?></span>
            <span>Powered by Meridian</span>
        </div>
    </div>
</footer>

<!-- Mobile bottom nav -->
<nav class="bottom-nav mobile-only" aria-label="Mobile">
    <?php foreach ($bottomNav as $bn):
        $icon = $bn['icon'] ?: 'home';
        $isCart = $icon === 'bag';
    ?>
    <a href="<?= e(base_url($bn['url'])) ?>" class="bottom-link">
        <span class="bottom-icon"><?= $icons[$icon] ?? $icons['home'] ?></span>
        <span><?= e($bn['label']) ?></span>
        <?php if ($isCart && cart_count() > 0): ?>
            <span class="bottom-badge"><?= cart_count() ?></span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</nav>

<?php if ($waOn || $msOn): ?>
<div class="support-floats">
    <?php if ($msOn): ?>
    <a href="<?= e($msUrl) ?>" target="_blank" rel="noopener" class="float-btn" aria-label="Messenger">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M12 2C6.5 2 2 6.2 2 11.4c0 2.9 1.3 5.5 3.4 7.3v3.6l3.3-1.8c.9.2 1.8.3 2.8.3h.5c-.2-.6-.3-1.2-.3-1.8 0-4.1 3.7-7.4 8.2-7.4.4 0 .8 0 1.1.1C20.4 5.5 16.6 2 12 2zm-2 8l5 2-2 5-2-1-2 1 .5-3-2-4z"/></svg>
    </a>
    <?php endif; ?>
    <?php if ($waOn): ?>
    <a href="https://wa.me/<?= e(preg_replace('/[^0-9]/', '', $waNum)) ?>" target="_blank" rel="noopener" class="float-btn" aria-label="WhatsApp">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M17.5 14.4c-.3-.1-1.7-.8-2-.9-.3-.1-.5-.1-.7.1-.2.3-.7.9-.9 1.1-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6.1-.1.3-.3.4-.5.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5 0-.1-.6-1.5-.9-2-.2-.5-.4-.4-.6-.5h-.5c-.2 0-.5.1-.7.4-.2.3-.9.9-.9 2.2 0 1.3.9 2.5 1 2.7.1.2 1.8 2.8 4.4 3.9 1.5.7 2.1.7 2.9.6.5-.1 1.4-.6 1.6-1.1.2-.5.2-1 .1-1.1l-.3-.2zM12 2a10 10 0 00-8.5 15.3L2 22l4.8-1.5A10 10 0 1012 2z"/></svg>
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>window.MERIDIAN = { base: '<?= e(APP_URL) ?>', csrf: '<?= e(csrf_token()) ?>', currency: '<?= e(setting('currency_symbol', '৳')) ?>' };</script>
<script src="<?= asset('js/main.js') ?>"></script>
<?php foreach ($pageJs as $js): ?><script src="<?= asset($js) ?>"></script><?php endforeach; ?>
</body>
</html>
