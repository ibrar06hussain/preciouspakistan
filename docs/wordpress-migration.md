# Precious Pakistan 2.0 — Non-Destructive WordPress Migration Guide

> **Safety Rule**: NEVER connect directly to or modify the live `preciouspakistan.com` WordPress database or filesystem. All imports operate strictly on authorized offline WXR XML or JSON export files.

## 1. Export from Legacy WordPress (Read-Only)
- In WordPress Admin → **Tools → Export → All content (or Pages/Posts)** to download an authorized `.xml` (WXR 1.2) file, OR export a structured `.json` file from a read-only database dump.
- Place the file in a private staging directory (e.g. `storage/app/private/wp-export.xml`).

## 2. Dry-Run Analysis (Zero Database Writes)
```bash
php artisan wp:import storage/app/private/wp-export.xml --dry-run
```
Review the generated counts:
- `Total`: records detected in the export.
- `Imported`: records that will be created.
- `Skipped`: records already imported by a previous batch (idempotent dedup via `source_wp_id`).
- `Needs Review`: records missing a title or content body (imported safely as `draft` so incomplete content is never auto-published).

## 3. Execute Idempotent Import
```bash
php artisan wp:import storage/app/private/wp-export.xml
```
What happens automatically:
- Every rich HTML body is sanitized via `HtmlSanitizerService` (blocks `<script>`, PHP tags, Blade directives, inline XSS).
- Original slugs are preserved when free; legacy paths that differ from the final Laravel path receive automatic **301 Permanent Redirects** in `url_redirects`.
- All created records are linked to a unique `batch_code` (`WP-IMP-YYYYMMDDHHMMSS-XXXX`) in `wordpress_import_batches` & `wordpress_import_items`.

## 4. Clean Batch-Scoped Rollback
If an import batch needs to be undone without disturbing any pre-existing or manually authored pages:
```bash
php artisan wp:import --rollback=WP-IMP-20261120120000-ABCD
```
This removes ONLY the CMS pages and 301 redirects created by that specific batch.
