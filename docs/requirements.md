# Precious Pakistan 2.0 — Confirmed Business Requirements

## 1. Business Model & Operating Invariants
- **Company-operated tourism booking platform**, not an open multi-vendor marketplace.
- **Admin-created staff users** with granular RBAC permissions via Spatie Permission + Laravel Policies.
- **No Hotel Partner login, no standalone hotel checkout, no hotel channel manager or housekeeping system.** Hotels exist solely as tour-only accommodation suggestions whose availability is externally verified by a company booking officer before final confirmation.
- **Company-owned/managed fleet** selected as part of tours. **Driver is always included** (shown `INCLUDED`, zero extra price). Vehicles have two daily rate columns per seasonal period/year: `with_fuel_rate` and `without_fuel_rate`, plus optional route-specific adjustments without double-charging driver or fuel.
- **Digital Nomad offerings** (coworking/desks/meeting rooms, internet & backup power disclosures) attach as tour add-ons only; independent workspace checkout remains disabled unless explicitly authorized later.
- **All-Pakistan geographic coverage**: Punjab, Sindh, Khyber Pakhtunkhwa, Balochistan, Gilgit-Baltistan, Azad Jammu & Kashmir, and Islamabad Capital Territory. Never fabricate official administrative data.
- **Dynamic, safe component-based CMS**: Header, footer, navigation, pages, visual presets, SEO, social icons, and media editable by authorized admins without arbitrary PHP/JS/Blade execution.
- **Rich-text editing**: Self-hosted Jodit MIT community edition with server-side HTML sanitization.
- **Multi-currency PKR/USD pricing & immutable financial records**: DECIMAL money only (never floats), complete immutable price snapshots on accepted quotes and issued invoices, FX rate source/timestamp tracking, credit notes instead of silent edits.
- **Non-destructive WordPress migration**: Existing `preciouspakistan.com` WordPress site is read-only reference/source; never alter its live database or files.
- **Hosting & Timezone**: UTC storage in database, `Asia/Karachi` display timezone. Local dev on Windows/XAMPP (MariaDB 10.4+) and production target on Hostinger VPS (MySQL 8.4 + Nginx + PHP-FPM + optional Redis).
