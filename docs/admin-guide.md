# Precious Pakistan 2.0 — Admin Operations Guide

## 1. Roles & Least-Privilege Separation (11 Company Roles)
- **Super Admin**: Full platform control, role/permission management, audit logs, invoice voiding. (Last active Super Admin cannot be deleted or deactivated.)
- **Administrator**: Broad operational management (cannot delete users/roles or void issued invoices).
- **Tour Manager**: Manages Pakistan destinations, tour packages, Jodit itineraries, overnight stays, and departures.
- **Booking Officer**: Reviews customer booking requests, logs external phone/email/WhatsApp hotel availability verifications, adjusts pre-invoice discounts, confirms or cancels bookings.
- **Hotel/Accommodation Manager**: Manages tour-only hotels, room types, seasonal/yearly PKR & USD nightly rates, and copies rates to next year. *(No hotel-partner login exists.)*
- **Fleet Manager**: Manages company vehicles, internal driver assignments (driver is ALWAYS included at zero extra charge), dual `with_fuel`/`without_fuel` seasonal rates, route adjustments, blackouts, and reservations.
- **Digital Nomad Manager**: Manages work-friendly coworking locations, desks/meeting rooms, seasonal rates, blackouts, and honest connectivity/power backup disclosures.
- **Finance Officer**: Records USD/PKR FX snapshots (with source & timestamp), creates & issues immutable proforma/final invoices, downloads PDFs with QR verification, records manual bank transfer receipts, issues credit notes, and voids invoices with reason.
- **Content/SEO Manager**: Manages CMS pages (draft/publish/schedule/undo), navigation menus, theme presets, WebP/AVIF media library, 301 redirects, SEO audit, and social/WhatsApp links.
- **Customer Support**: Views bookings and responds to customer inquiries.
- **Customer**: Verified traveler account accessing `/dashboard`, booking requests, issued PDF invoices, and offline itinerary packs.

## 2. Core Operational Workflows
1. **Booking Confirmation Gate**: A booking containing any tour-only hotel selection CANNOT be confirmed until a Booking Officer logs external verification (`verified_available` or `confirmed_alternative`) in **Hotel Verifications**.
2. **Immutable Finance Flow**: Draft invoice → **Issue** (assigns sequential `PP-INV-YYYY-00001` & locks financial fields permanently) → **Record Payment** (issues `PP-RCT-YYYY-00001` & syncs `booking.payment_status` independently of `booking_status`) → **Credit Note** (`PP-CN-YYYY-00001`) for any post-issue adjustment.
3. **Public QR Invoice Verification**: Anyone scanning the invoice PDF QR code visits `/verify/invoice/{token}`, which confirms document authenticity, status, issue date, and totals while hiding all customer PII.
4. **Online Payment Gateway & Meta Messaging APIs**: Both remain intentionally **Disabled** until official authorized merchant/Meta credentials are configured. Never simulate production payments or fake API status.
