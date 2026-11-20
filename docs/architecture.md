# Precious Pakistan 2.0 — System Architecture

## 1. Runtime & Framework Stack (Verified)
- **PHP**: `8.4.27` (required: `^8.3`) with `bcmath`, `gd` (JPEG/PNG/WebP/AVIF), `intl`, `mbstring`, `openssl`, `pdo_mysql`, `pdo_sqlite`, `sodium`, `zip`.
- **Framework**: Laravel `13.43.0` (`laravel/framework ^13.17`)
- **Admin Shell**: Filament `5.11.4` (`filament/filament ^5.11`)
- **Reactive Frontend**: Livewire `4.4.8` (bundles Alpine.js), Tailwind CSS `v4`, Vite `8`
- **RBAC**: Spatie Laravel Permission `8.5.0`
- **API Auth**: Laravel Sanctum `4.5.0`
- **Database Compatibility**: Dual-compatible migrations for local MariaDB `10.4.32` and production MySQL `8.4` (`DECIMAL` money/coordinates, InnoDB transactions + `lockForUpdate()`, indexed strings <= 191 chars where indexed).

## 2. Modular Monolith Layout (`app/Modules/`)
Domain rules, services, DTOs, policies, validation requests, events, and jobs live inside domain modules. Controllers and Filament resources stay thin and delegate mutations to module services.

- `App\Shared`: Cross-cutting helpers (`DisplayTimezone` UTC -> `Asia/Karachi`, future `Money` value objects).
- `App\Modules\Identity` *(Phase 1)*: Authentication, email verification, password resets, optional MFA contract (`MfaProvider` / `NullMfaProvider`), admin invitations (`AdminInvitationService`), secure Super Admin CLI installer (`SuperAdminInstallerService`), and separated `AdminAccessPolicy` / `CustomerAccessPolicy`.
- `App\Modules\Rbac` *(Phase 2)*: Canonical 11-role catalog (`RbacCatalog`), server-side anti-escalation guard (`PrivilegeGuard`), transactional `UserManagementService` & `RoleManagementService`, immutable append-only `AuditLog` + `AuditLogger`, Laravel Model Policies (`UserPolicy`, `RolePolicy`, `AdminInvitationPolicy`, `AuditLogPolicy`), and thin Filament 5 CRUD resources (`UserResource`, `RoleResource`, `AdminInvitationResource`, `AuditLogResource`).
- `App\Modules\Cms` *(Phase 3)*: Safe schema-validated visual page builder (`ComponentRegistry` with 16 section types, `HtmlSanitizerService` via Symfony HTML Sanitizer blocking PHP/Blade/JS/unsafe URLs, `ThemePresets`), draft-isolated `PageBuilderService` with version history undo & scheduled publication (`cms:publish-scheduled`), cached server-side `PageRendererService`, `MenuService`, `SiteSettingsService`, `CmsPagePolicy`/`CmsMenuPolicy`, and Filament 5 CMS builder (`CmsPageResource`, `CmsMenuResource`, `SiteSettingsPage`).
- `App\Modules\Destinations` *(Phase 4)*: Five-level geographic hierarchy (`Country > Region > District > Destination > Attraction`), `DestinationHierarchyService` (strict parent validation, HTML sanitization, automatic 301 redirect registration with chain collapsing), `GeoSearchService` (multi-filter search, Haversine nearby attractions, RFC 7946 GeoJSON `FeatureCollection`), `HandleUrlRedirects` middleware, Leaflet.js interactive public maps (`/destinations`, `/regions/{slug}`, `/destinations/{slug}`, `/api/geo/features`), and Filament 5 CRUD (`RegionResource`, `DistrictResource`, `DestinationResource`, `AttractionResource`).
- `App\Modules\Tours` *(Phase 5)*: Full tour catalog (`Tour`, `TourItineraryDay`, `TourStay`, `TourDeparture`), self-hosted Jodit MIT custom Filament field (`JoditEditor`), `TourCatalogService` (passenger cap, chronological itinerary, stay nights, departure capacity, DECIMAL pricing, server-side HTML sanitization, strict publish-readiness guard, draft/publish/archive lifecycle, 301 slug redirects), `TourSearchService` (multi-facet search & date/guest/departure booking entry validation), `TourPolicy`, public catalog (`/tours`, `/tours/{slug}`), live related-tours integration on destination pages, and Filament 5 `TourResource`.
- Upcoming modules: `Hotels` (Phase 6), `Fleet` (Phase 7), `Nomads` (Phase 8), `Booking` (Phase 9), `Finance` (Phase 10), `Media` (Phase 11), `Seo` (Phase 11), `Communications` (Phase 12), `Migration` (Phase 14).

## 3. Security Boundaries (Phase 1)
- Separate account types (`AccountType::Customer`, `AccountType::Staff`) with no hotel-partner account type.
- Filament `/admin` panel strictly requires `is_active === true`, `account_type === Staff`, and verified email via `User::canAccessPanel()` plus `AdminAccessPolicy`.
- Deactivated accounts are immediately invalidated by `EnsureActiveUser` middleware.
- Rate limiters protect login, registration, password reset, and API routes.
- No hardcoded Super Admin password; initial Super Admin is provisioned interactively or via explicit `--from-env` secrets with strict password complexity validation.
