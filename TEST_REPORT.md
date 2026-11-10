# NOVA MARKET — Technical validation summary

Generated: 2026-11-05

## Static checks performed
- PHP syntax: every `.php` file checked with `php -l` (passed).
- JavaScript syntax: storefront, admin and service worker checked with `node --check` (passed).
- Project files: `index.php`, installer, SQL schema, protected admin, checkout API, order notifications, CSS, images and documentation are present.
- Demo artwork: 12 original SVG product illustrations and 2 generated PNG app icons. The merchant must replace demo catalogue images with actual product photography.
- Password: never hard-coded in distributable PHP. The installer asks the owner to enter their chosen password, then stores a one-way hash.

## Not tested in this build environment
- Live MySQL/MariaDB integration: no PDO MySQL driver or MySQL server was available, so the actual installer, SQL queries, order creation and stock transactions must be tested on XAMPP/hosting.
- Telegram Bot API delivery requires a valid bot token and chat ID.
- WhatsApp Business automated delivery requires Meta API credentials and an approved template.
- End-to-end browser and responsive visual checks: headless browser navigation was restricted by the environment. CSS has mobile/tablet breakpoints, but final device QA remains to be done.

Before public launch, verify at minimum: first install, admin login, all product/category CRUD, photo upload, size/color selection, coupon validation, successful COD order, order decrement and cancellation stock restore, printing, Telegram test, mobile checkout, HTTPS and backups.
