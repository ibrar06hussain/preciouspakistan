# Precious Pakistan 2.0 — Hostinger VPS Production Deployment Playbook

> **Target Stack**: Ubuntu 24.04 LTS on Hostinger KVM VPS · Nginx · PHP 8.4-FPM · MySQL 8.4 · Redis (or Database queue fallback) · Supervisor · Certbot TLS.

## 1. Server Prerequisites
- **PHP 8.4** with extensions: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `gd` (with WebP & AVIF), `intl`, `mbstring`, `mysql`, `openssl`, `pdo`, `tokenizer`, `xml`, `zip`, `redis` (optional).
- **MySQL 8.4** with `utf8mb4` character set, `utf8mb4_unicode_ci` collation, InnoDB default engine, and UTC server timezone (`default-time-zone='+00:00'`).
- **Node.js 20+ / npm** for Vite asset compilation (or build assets in CI and deploy `public/build/`).
- **Supervisor** for queue workers and **Cron** for Laravel Scheduler (`* * * * * cd /var/www/preciouspakistan && php artisan schedule:run >> /dev/null 2>&1`).

## 2. Directory & Private Storage Permissions
```bash
sudo mkdir -p /var/www/preciouspakistan
sudo chown -R deploy:www-data /var/www/preciouspakistan
chmod -R 775 /var/www/preciouspakistan/storage /var/www/preciouspakistan/bootstrap/cache
```
- Public uploads live in `storage/app/public/uploads` (exposed via `php artisan storage:link`).
- Sensitive exports, backups, and private invoices live in `storage/app/private` (`private_uploads` disk) — NEVER symlinked to `public/`.

## 3. Production `.env` Secrets Checklist
Never commit `.env` to Git. Configure:
- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://preciouspakistan.com`
- `APP_TIMEZONE=UTC`, `APP_DISPLAY_TIMEZONE=Asia/Karachi`
- `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=preciouspakistan`, `DB_USERNAME=pp_app`, strong unique `DB_PASSWORD`
- `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax`
- `QUEUE_CONNECTION=redis` (or `database` fallback if Redis is disabled)
- `PRECIOUS_NOMADS_ALLOW_STANDALONE=false`
- `PRECIOUS_DEFAULT_TAX_PERCENT=0.00` (configure verified tax rate only when authorized by accounting)

## 4. Nginx Virtual Host (Hardened)
- Document root: `/var/www/preciouspakistan/public`
- Deny direct access to `.env`, `.git`, `/storage/app/private`, and PHP execution inside `/storage/uploads`.
- Security headers: `Strict-Transport-Security`, `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`.
- Health check endpoint: `GET /up` (returns HTTP 200 when Laravel boots cleanly).
- Provision Let's Encrypt TLS with `certbot --nginx -d preciouspakistan.com -d www.preciouspakistan.com`.

## 5. First-Time Production Release Steps
```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --class=RbacSeeder --force
php artisan db:seed --class=PakistanDestinationsSeeder --force
php artisan db:seed --class=CmsDefaultPagesSeeder --force
php artisan precious:install-super-admin
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
> **IMPORTANT**: Do NOT run `Sample*Seeder` classes in production unless setting up a staging/demo server.

## 6. Automated Backups & Verification
- Daily logical MySQL dump (`mysqldump --single-transaction --routines --triggers`) encrypted and replicated off-VPS with 30-day retention.
- Daily rsync of `storage/app/public/uploads` and `storage/app/private`.
- Monthly restore drill onto a disposable staging DB to verify backup integrity.

## 7. DNS Cutover & Rollback Playbook
1. Lower TTL on `preciouspakistan.com` DNS A/AAAA records to 300s 24 hours prior to cutover.
2. Freeze content edits on the legacy WordPress site (do NOT delete or modify the live WordPress database!).
3. Export authorized WordPress WXR/JSON snapshot, run `php artisan wp:import export.xml --dry-run`, review mapping & redirects, then run `php artisan wp:import export.xml`.
4. Verify `/up`, `/`, `/tours`, `/destinations`, `/sitemap.xml`, `/robots.txt`, and sample 301 redirects against the VPS IP via `/etc/hosts`.
5. Switch DNS A record to Hostinger VPS IP and monitor Nginx/Laravel logs.
6. **Emergency Rollback**: Point DNS A record back to legacy WordPress host (untouched!) and/or run `php artisan wp:import --rollback={batch_code}` for batch-scoped data rollback.
