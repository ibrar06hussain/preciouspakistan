# Precious Pakistan 2.0 — Final Acceptance Checklist & Go-Live Risk Log

## 1. Acceptance Matrix (All 14 Phases Verified)

| Area | Business Invariant / Requirement | Implemented In | Automated Test Evidence |
|---|---|---|---|
| **Foundation & Auth (P1)** | Laravel 13 + PHP 8.4, UTC storage / `Asia/Karachi` display, separate Customer/Staff policies, secure Super Admin CLI (no hardcoded password) | `App\Modules\Identity`, `precious:install-super-admin` | `tests/Feature/Phase1/*` |
| **RBAC & Audit (P2)** | 11 company roles, 42 granular permissions, NO Hotel Partner role, privilege-escalation prevention, last Super Admin guard, immutable `audit_logs` | `App\Modules\Rbac`, `RbacCatalog`, `PrivilegeGuard`, `AuditLog` | `tests/Feature/Phase2/*` |
| **Dynamic CMS (P3)** | Safe visual section builder (16 components), draft/publish/schedule/undo, menus, theme presets, strict XSS/PHP/Blade sanitization | `App\Modules\Cms`, `ComponentRegistry`, `HtmlSanitizerService` | `tests/Feature/Phase3/*` |
| **Pakistan Geography (P4)** | All 7 Pakistan regions + 22 destinations + Leaflet maps + GeoJSON + automatic 301 redirects + non-exhaustive disclosure | `App\Modules\Destinations`, `PakistanDestinationsSeeder` | `tests/Feature/Phase4/*` |
| **Tours & Jodit (P5)** | Self-hosted Jodit MIT, chronological itinerary, stays, departures, `Request Quote` never `$0`, publish-readiness guard | `App\Modules\Tours`, `JoditEditor`, `TourCatalogService` | `tests/Feature/Phase5/*` |
| **Tour-Only Hotels (P6)** | No standalone booking, no hotel-partner login, seasonal `bcmath` proration, upgrade-delta math, mandatory external verification gate | `App\Modules\Hotels`, `HotelPricingService`, `HotelVerificationService` | `tests/Feature/Phase6/*` |
| **Company Fleet (P7)** | Driver ALWAYS included (`0.00`), dual `with_fuel`/`without_fuel` seasonal rates, single route adjustment on `with_fuel` only, `lockForUpdate()` holds, private registration/driver phone hidden publicly | `App\Modules\Fleet`, `VehiclePricingService`, `VehicleAvailabilityService` | `tests/Feature/Phase7/*` |
| **Digital Nomads (P8)** | Tour add-on only (standalone blocked by default), hourly/daily seasonal pricing, honest internet/power caveats | `App\Modules\Nomads`, `NomadPricingService` | `tests/Feature/Phase8/*` |
| **Central Booking (P9)** | Authoritative DECIMAL quote snapshot, idempotency keys, separate `booking_status` vs `payment_status`, unverified hotel blocks confirmation, refund-required path | `App\Modules\Booking`, `CentralPricingService`, `BookingWorkflowService` | `tests/Feature/Phase9/*` |
| **Finance & Invoices (P10)** | PKR/USD FX snapshots with source/timestamp, sequential immutable invoices, credit notes, manual payment receipts, DomPDF + non-PII QR verify, disabled payment gateway adapter | `App\Modules\Finance`, `InvoiceService`, `InvoicePdfService`, `DisabledPaymentGatewayAdapter` | `tests/Feature/Phase10/*` |
| **Media & SEO (P11)** | WebP/AVIF + responsive thumbs, SHA-256 dedup, reference-deletion guard, Jodit endpoints, XML sitemap, `robots.txt`, truthful JSON-LD (no fake review stars) | `App\Modules\Media`, `App\Modules\Seo`, `MediaLibraryService`, `SeoService` | `tests/Feature/Phase11/*` |
| **Social & WhatsApp (P12)** | Configurable social links, non-PII contextual `wa.me`, safe `youtube-nocookie.com` helper, consent/retention inquiries, disabled Meta API connector | `App\Modules\Communications`, `WhatsAppLinkService`, `InquiryService`, `DisabledMetaChannelConnector` | `tests/Feature/Phase12/*` |
| **PWA & Dashboards (P13)** | Role-scoped admin KPIs, live customer dashboard, safe SW caching ONLY user-triggered offline itinerary packs (bypasses admin/auth/invoices) | `DashboardMetricsService`, `public/manifest.webmanifest`, `public/sw.js` | `tests/Feature/Phase13/*` |
| **WP Migration & VPS (P14)** | Non-destructive local WXR/JSON importer (`--dry-run`, idempotent, sanitized, 301 redirects, `--rollback=batch`) + Hostinger VPS playbook | `App\Modules\Migration`, `wp:import`, `docs/deployment-hostinger-vps.md` | `tests/Feature/Phase14/*` |

## 2. Go-Live Risk Log & Pre-Launch Prerequisites
1. **Sample Data Cleanup**: Seeded sample tours, hotels, fleet vehicles, and nomad hubs carry `is_sample_content = true`; do not seed sample datasets in production.
2. **External Credentials Explicitly Disabled**:
   - Online payment gateway (`DisabledPaymentGatewayAdapter`) — configure an authorized PSP adapter before enabling card/wallet checkout.
   - Official Meta WhatsApp Cloud API / Messenger / Instagram (`DisabledMetaChannelConnector`) — keep disabled until Meta app review & webhook secrets are provisioned.
   - ClamAV antivirus (`NullVirusScanner`) — wire ClamAV daemon on Hostinger VPS for production uploads.
   - Optional MFA (`NullMfaProvider`) — enable TOTP/passkey provider for staff accounts before opening public admin access.
3. **Real Company Brand Assets**: Upload official Precious Pakistan logos, photography (with alt text & licenses), and official support WhatsApp number via Admin Portal before DNS cutover.
4. **Tax Configuration**: `PRECIOUS_DEFAULT_TAX_PERCENT` defaults to `0.00` (never hardcoded GST); configure verified applicable tax rules with company finance prior to issuing production invoices.
