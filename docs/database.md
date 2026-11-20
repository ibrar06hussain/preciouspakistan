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
