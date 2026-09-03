# Panna Wild Tour Core 2.5

Core WordPress plugin for the Panna Wild Tour travel, safari, accommodation, restaurant and local-trip business.

## Responsibilities

- Business/content post types and taxonomies
- SCF field groups
- Customers
- Bookings and booking items
- Availability/capacity with inventory holds
- Pricing/rates with seasonal pricing
- Inventory checks and holds
- Payments with idempotency and gateway adapters
- REST API endpoints for availability and quotes
- Integrations (Fluent, FluentForms, FluentBooking)
- Staff management with role-based access control
- Customer portal and operations dashboard

Themes remain presentation-only.

## Compatibility

Existing `pwt_booking` posts are retained. The new normalized booking tables are additive and linked to legacy booking records.

See `docs/ARCHITECTURE.md` for the target architecture and migration roadmap.

## Performance Optimizations

- **SeasonResolver**: Transient-cached season lookups (key: `pwt_season_resolver_{md5(date)}`, TTL: 1 hour)
- **RateRepository**: Transient-cached rate queries via `Cache` dependency (key: `pwt_rates_{resourceId}_{type}_{date}`, TTL: 1 hour)
- **BookingRepository**: Efficient post meta insertion using `wp_insert_post()` `meta_input` parameter instead of 7+ individual `update_post_meta()` calls
- **PaymentManager**: Cached `pwt_settings` option loaded once per request via static `$settings` property

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


## Staff Role Structure (Version 2.5)

The plugin now supports 8 operational roles with dedicated WordPress capabilities, **without Administrator access**:

| Role | Main Responsibility |
|------|---------------------|
| `pwt_operations_manager` | Overall daily operations - access to almost all operational modules |
| `pwt_booking_executive` | Customer enquiries and bookings - customers, bookings, quotations |
| `pwt_safari_coordinator` | Safari scheduling and permits - safaris, vehicles, schedules |
| `pwt_accommodation_coordinator` | Hotels, resorts and rooms - resorts, room inventory |
| `pwt_transport_coordinator` | Pickup/drop and local travel - vehicles and transfers |
| `pwt_vendor_manager` | Vendor, vehicle and supplier coordination - vendors and contracts |
| `pwt_accounts_executive` | Payments and refunds - payments and financial reports |
| `pwt_content_executive` | Packages, destinations and website content - selected content only |

### Access Control

- Roles are registered on plugin activation via `RoleRegistrar::register()`
- Each role receives only the capabilities needed for its function
- No role receives Administrator access
- Capabilities are defined in `Capabilities.php` with dedicated methods per role
- Role mapping is in `RoleRegistrar.php`

### Example Access

**Booking Executive**: Can view/create customers, create/edit bookings, create booking items, generate quotations, check availability, view packages and safaris. Cannot delete completed bookings, change payment records, change WordPress settings, install plugins, or modify themes.

**Safari Coordinator**: Can view bookings, manage safari schedules, assign vehicles, check availability, update operational status, manage safari-related inventory. Cannot manage payments, change pricing globally, or manage WordPress users.

**Accounts Executive**: Can view confirmed bookings, record payments/refunds, view financial reports, export reports. Cannot edit packages, edit safari schedules, install plugins, or change system configuration.

**Content Executive**: Can manage packages, destinations, and website content (has `edit_posts` and `upload_files` capabilities). Cannot manage operational settings.

Staff role structure is defined in `app/Staff/Roles/Capabilities.php` and `app/Staff/Roles/RoleRegistrar.php`. Customer lifecycle and segmentation services feature transient caching for improved performance.