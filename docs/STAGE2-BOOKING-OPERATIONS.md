# Stage 2: Booking, Availability, Pricing and Operations

## Changes
- Version bumped to 2.5.27.
- Operational bookings now reject past start dates.
- Operational bookings reject services without a configured rate or trusted fallback price.
- Unknown inventory capacity is treated as unavailable for reservation decisions.
- Inventory checks now validate every date in a multi-day item.
- Public AJAX validation now validates ISO travel dates and rejects past dates.
- REST booking access no longer grants an editor-level bypass; operational access requires `pwt_manage_operations`, a valid REST nonce, or the configured API key.
- Booking numbers are generated with collision checks before insert.

## Important operational rule
A service must have both inventory capacity and a valid price before it can be converted into a normalized operational booking. Use the existing enquiry flow for price-on-request products.

## Test checklist
1. Create a package enquiry from the frontend.
2. Create an internal operational booking with configured inventory and rates.
3. Attempt a past date and confirm rejection.
4. Attempt a multi-day item where one middle date is unavailable and confirm rejection.
5. Attempt a service with no rate and no fallback price and confirm rejection.
6. Confirm a held booking and verify inventory is incremented exactly once.
