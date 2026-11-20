# Precious Pakistan 2.0 — Database Documentation

## Compatibility & Integrity Standards
- All timestamps stored in **UTC**; rendered in **`Asia/Karachi`**.
- All migrations are strictly reversible (`down()` drops foreign keys/columns/tables cleanly) and non-destructive.
- Compatible with both **MariaDB 10.4.32** (local XAMPP) and **MySQL 8.4** (Hostinger VPS production), plus **SQLite `:memory:`** for isolated automated tests.

## Phase 1 Tables

| Table | Purpose | Key Columns & Constraints |
|---|---|---|
| `users` | Customers and company staff accounts | `email` (unique), `phone`, `account_type` (`customer` \| `staff`, indexed), `is_active` (indexed), `email_verified_at`, `mfa_enabled`, `mfa_secret` (encrypted), `mfa_recovery_codes` (encrypted), `mfa_confirmed_at`, `last_login_at`, `invited_by` (FK -> `users.id` nullOnDelete) |
| `password_reset_tokens` | Standard Laravel password reset tokens | `email` (PK), `token`, `created_at` |
| `sessions` | Database-backed HTTP sessions | `id` (PK), `user_id` (indexed), `last_activity` (indexed) |
| `cache` / `cache_locks` | Database cache store fallback | `key` (PK), `expiration` |
| `jobs` / `job_batches` / `failed_jobs` | Database queue driver fallback | `queue` (indexed), `uuid` (unique) |
| `admin_invitations` | Secure staff onboarding invitations | `email` (indexed), `role_name`, `token_hash` (SHA-256, unique), `invited_by` (FK -> `users.id`), `expires_at` (indexed), `accepted_at`, `revoked_at` |
| `site_settings` | Database-backed public shell & site configuration | `group` (indexed), `key` (unique), `value`, `value_type`, `is_public` (indexed) |
| `permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions` | Spatie Permission v8 tables seeded with 11 initial roles and granular module permissions | Standard Spatie indexes & composite keys |
| `audit_logs` *(Phase 2)* | Immutable append-only RBAC & account security audit trail | `actor_id` (FK -> `users.id`), `event` (indexed), `auditable_type`+`auditable_id` (composite index), `target_user_id` (FK -> `users.id`), `summary`, `before_state` (JSON), `after_state` (JSON), `ip_address`, `user_agent`, `created_at` (indexed) |
| `cms_pages` *(Phase 3)* | Dynamic CMS pages & homepage pointer | `slug` (unique), `is_homepage` (indexed), `status` (`draft` \| `scheduled` \| `published` \| `archived`), `published_version_id`, `draft_version_id`, `scheduled_publish_at`, SEO metadata, `created_by`/`updated_by` |
| `cms_page_versions` *(Phase 3)* | Immutable & draft page revision history for undo/publish isolation | `page_id` (FK), `version_number` (unique per page), `status`, `theme_preset`, `meta_snapshot` (JSON), `published_by`, `published_at` |
| `cms_page_sections` *(Phase 3)* | Ordered, schema-validated visual builder sections per version | `page_version_id` (FK), `section_key`, `component_type` (indexed), `sort_order` (indexed), `is_visible`, `device_visibility`, `style_preset`, `config` (JSON) |
| `cms_menus` / `cms_menu_items` *(Phase 3)* | Admin-managed navigation menus (`header`, `mobile`, `footer_primary`, `footer_legal`) | `location` (unique), `menu_id` (FK), `parent_id` (FK self), `label`, `url`, `target`, `sort_order`, `is_visible`, `device_visibility` |
| `geo_countries`, `geo_regions`, `geo_districts`, `geo_destinations`, `geo_attractions` *(Phase 4)* | 5-level Pakistan destination hierarchy with DECIMAL(10,7) coordinates, GeoJSON, galleries, and SEO fields | Strict cascading FKs, unique slugs, composite `(latitude, longitude)` indexes, `is_sample_subset`/`is_sample_content` flags |
| `url_redirects` *(Phase 4)* | 301/302 redirect registry for slug changes (and Phase 11/14 SEO & WordPress redirects) | `source_path` (unique), `target_path`, `status_code`, `reason`, `hits`, `is_active` |
| `tours`, `tour_destinations`, `tour_itinerary_days`, `tour_stays`, `tour_departures` *(Phase 5)* | Tour packages, attached destinations, strictly chronological itineraries, overnight stays, and departure windows | Unique `code`/`slug`, DECIMAL(14,2) `base_price_pkr`/`base_price_usd`, unique `(tour_id, day_number)`, unique `(tour_id, destination_id)`, departure capacity & status indexes |
| `hotels`, `hotel_room_types`, `hotel_room_rates`, `tour_stay_hotels`, `hotel_availability_verifications` *(Phase 6)* | Tour-only company-managed hotels, seasonal/yearly PKR & USD nightly rates, stay options, and manual external verification logs | Unique `(hotel_id, code)`, unique `(tour_stay_id, hotel_id)`, DECIMAL(14,2) nightly rates, composite rate lookup index `hrr_lookup_idx`, external verification method/reference/officer columns |
| `vehicles`, `vehicle_driver_assignments`, `vehicle_seasonal_rates`, `vehicle_route_adjustments`, `vehicle_blackouts`, `vehicle_reservations` *(Phase 7)* | Company fleet, private registration/driver assignments, seasonal `with_fuel`/`without_fuel` DECIMAL rates, route adjustments, maintenance blackouts, and concurrency-locked reservations | Unique `code`/`slug`/`registration_number`, `driver_always_included`, DECIMAL(14,2) dual fuel rate columns, `vsr_lookup_idx` and `vres_conflict_idx` conflict indexes |
| `nomad_locations`, `nomad_facilities`, `nomad_seasonal_rates`, `nomad_blackouts` *(Phase 8)* | Destination-linked Digital Nomad coworking hubs, desks/meeting rooms, seasonal hourly/daily/weekly/fixed rates, and blackout dates | Unique `slug`/`code`, `internet_type`/`power_backup_type`/`reliability_notes` honesty fields, DECIMAL(14,2) rates, `nsr_lookup_idx` composite index |
