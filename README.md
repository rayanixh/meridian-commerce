# Meridian Commerce

A complete, production-ready PHP e-commerce platform built with PHP 8.1+, MySQL/MariaDB, vanilla JavaScript.

## Quick install

1. Upload the contents of this folder to your hosting's `public_html/` (or web root).
2. Open `https://yourdomain.com/` in your browser.
3. The installer will start automatically on first visit.
4. Complete the 7-step wizard (Welcome → Database → Tables → Admin → Config → Review → Complete).
5. Sign in to the admin panel at `/admin/login.php`.

## Requirements

- PHP 8.1+ (8.2+ recommended)
- MySQL 5.7+ or MariaDB 10.3+
- PHP extensions: PDO, PDO_MySQL, mbstring
- Apache with `mod_rewrite` (or nginx with equivalent)
- Apache `AllowOverride All` enabled

## Structure

```
index.php            Public entry (auto-detects install state)
index.html           Static design preview only
.htaccess            Root server config
config/              App config + database connection
includes/            Helpers, header, footer, auth, cart, theme
install/             Installation wizard + migrations
admin/               Admin panel (22 pages)
api/                 JSON endpoints (cart, checkout, search, upload)
assets/              CSS, JS, default images (separated base/components/desktop/tablet/mobile)
uploads/             User uploads (writable)
storage/             Logs (writable)
```

## Features

- Multi-step white/black installation wizard
- Section-based homepage (hero, categories, featured, new, best, intention, promo, testimonials, newsletter)
- Full product management with variants, gallery, brand, tags
- Cart drawer + cart page + full checkout
- bKash, Nagad, Rocket, Cash on Delivery payment methods
- Manual payment with transaction ID + optional screenshot
- **COD hides transaction ID automatically**
- Order tracking with timeline
- Coupons, shipping zones, inventory
- Theme customizer with presets (Default White, Midnight, Ocean, Forest, Rose)
- Logo + favicon upload
- CMS for static pages
- WhatsApp, Telegram, Messenger support
- Mobile bottom nav, cart drawer, search drawer, admin sidebar drawer
- **Fully responsive 320px → 1920px+** with separate base/components/desktop/tablet/mobile CSS
- Secure: CSRF, prepared statements, password hashing, rate-limited admin login

## Security

- All SQL via PDO prepared statements (no string concatenation)
- All forms protected by CSRF tokens
- Passwords stored with `password_hash()` (bcrypt) + `password_verify()`
- Admin login rate-limited (6 attempts / 15 min)
- Session cookies hardened (httponly, samesite)
- Output escaped via `e()` helper on every user-controlled value
- File uploads MIME-validated, size-capped, randomly renamed
- Uploads folder `.htaccess` blocks PHP execution
- `storage/logs/` blocked from web access
- Migrations are idempotent and resumable
- Existing data is never destroyed automatically

## Reset

Delete `config/db_config.php` and visit `/` to start over. Existing data is never destroyed.

## Default admin (created during install)

- URL: `/admin/login.php`
- Use the credentials you set during installation.
