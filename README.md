# Panna Wild Tour Core 2.0

Core WordPress plugin for the Panna Wild Tour travel, safari, accommodation, restaurant and local-trip business.

## Responsibilities

- Business/content post types and taxonomies
- SCF field groups
- Customers
- Bookings and booking items
- Availability/capacity
- Pricing/rates
- Inventory checks
- Payments
- REST API
- Integrations

Themes remain presentation-only.

## Compatibility

Existing `pwt_booking` posts are retained. The new normalized booking tables are additive and linked to legacy booking records.

See `docs/ARCHITECTURE.md` for the target architecture and migration roadmap.


## Version 2.1.0 modules

- Accommodation room types and room units
- Safari schedules and vehicle assignments
- Seasonal/date/quantity pricing
- Customer portal shortcode: `[pwt_customer_portal]`
- Operations dashboard with availability, pricing and customer views


## Version 2.2.0 — Phases 4, 5, 6 and Reporting

### Phase 4 — Booking orchestration
- Multi-service booking items
- Availability validation before booking
- Pricing quote integration
- Booking totals
- Confirmation and cancellation orchestration

### Phase 5 — Inventory holds
- Temporary inventory holds
- Hold expiry
- Booking-level hold release
- Scheduled cleanup

### Phase 6 — Payments
- Payment repository
- Idempotency keys
- Successful payment recording
- Booking confirmation after payment
- Signed webhook verification hook
- Provider-neutral payment processing foundation

### Reporting
- Date-range operations report
- Booking status report
- Gross booking value
- Payments received
- Refund totals
- Customer counts
- Top services
- Admin report screen
- CSV-ready report generation


## Version 2.2.2 — PHP type compatibility and translation loading fix

- Fixed `?string` inheritance compatibility for all taxonomy and post type `rewriteSlug` properties.
- Deferred `wildtours-plugin` translation loading to the WordPress `init` hook.


## Version 2.2.3 — WordPress 6.7 translation lifecycle fix

- Removed the plugin's `load_plugin_textdomain()` call from `plugins_loaded`.
- Translation loading now occurs only from the main plugin bootstrap on `init`.
- Retained compatible nullable `rewriteSlug` declarations across post types and taxonomies.
