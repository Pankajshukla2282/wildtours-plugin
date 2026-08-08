# Panna Wild Tour Core 2.0 Architecture

## Layers

- **Content**: Packages, Safaris, Destinations, Resorts, Vehicles, Restaurants, Local Trips, Reviews, FAQs.
- **Customers**: normalized customer records in `pwt_customers`.
- **Bookings**: normalized bookings and booking items in custom tables; the existing `pwt_booking` CPT remains as a compatibility/admin record.
- **Availability**: date/resource capacity in `pwt_availability`.
- **Pricing**: date/season/quantity-aware rates in `pwt_rates`.
- **Inventory**: reservation checks through AvailabilityService.
- **Payments**: normalized transactions in `pwt_payments`; existing gateway classes remain usable.
- **Integrations**: REST, SCF, Fluent integrations and future gateway adapters.
- **Presentation**: remains in the WildTours base/child themes.

## Resource model

Resources use stable identifiers:

- `package`
- `safari`
- `resort`
- `room` (future inventory subtype)
- `restaurant`
- `local_trip`
- `vehicle`

The availability and pricing tables deliberately use `resource_type + resource_id` so new resource types can be introduced without a schema migration.

## Booking model

A booking has:

- one customer
- many booking items
- totals
- travel dates
- lifecycle status
- payments

The existing booking CPT is not removed. New normalized records are linked by `_pwt_normalized_booking_id`.

## Next implementation phases

1. Room/unit inventory and hotel room types.
2. Safari seat/vehicle capacity.
3. Seasonal pricing and package component pricing.
4. Booking hold/expiry and atomic inventory reservation.
5. Payment webhook verification and refunds.
6. Customer portal and authenticated booking access.
7. Notifications and operational dashboards.
8. Reporting/analytics and exports.

## API

Public read-only endpoints:

- `GET /wp-json/pwt/v1/availability`
- `GET /wp-json/pwt/v1/quote`

Booking creation and payment mutation endpoints should require nonce/authentication and idempotency keys before being exposed publicly.


## Implemented phases in 2.1.0

### Phase 1 — Accommodation inventory
- Room Type CPT
- Room Unit CPT
- Resort association
- Occupancy and meal-plan data
- Room operational status
- Availability provisioning service

### Phase 2 — Safari and vehicle operations
- Safari Schedule CPT
- Safari date and shift
- Safari zone
- Assigned vehicle
- Capacity
- Gate and schedule status
- Availability provisioning service

### Phase 3 — Pricing
- Date-range rates
- Season-aware pricing
- Quantity bands
- Rate priority
- Resource-specific pricing
- Pricing administration view

### Phase 7 — Customer portal
Use `[pwt_customer_portal]` on a page. Logged-in customers matched by account email can see normalized bookings.

### Phase 8 — Operations dashboard
Admin pages now expose:
- Operations overview
- Availability
- Pricing rules
- Customers
- Quick links to room, safari schedule and booking management

These modules are additive. Existing content CPTs and legacy booking records remain intact.


## Version 2.2.0 — Transactional operations

### Phase 4 — Booking orchestration
The booking orchestrator validates all requested resources and pricing before creating a booking and its booking items. A booking can contain multiple service types.

### Phase 5 — Inventory holds
Inventory can be temporarily held before payment. Holds expire automatically through WP-Cron. Final confirmation reserves inventory.

### Phase 6 — Payments
Payment records are normalized and idempotent. The generic webhook endpoint rejects unsigned/unverified payloads. Payment-provider adapters should implement the `pwt_verify_payment_webhook` and `pwt_process_payment_webhook` filters.

### Reporting
Reporting queries operate against normalized transactional tables and are deliberately independent of the theme.
