<?php

declare(strict_types=1);

namespace PWT\Bookings;

defined('ABSPATH') || exit;

use DateTimeImmutable;
use PWT\Availability\AvailabilityRepository;
use PWT\Availability\BookingAllocationService;
use PWT\Availability\HoldService;
use PWT\Bookings\Repositories\BookingDataRepository;
use PWT\Bookings\Repositories\BookingRepository;
use PWT\Core\Database\Transaction;
use PWT\Customers\CustomerRepository;
use PWT\Customers\TravelerRepository;
use PWT\Packages\PackageComponentService;
use PWT\Pricing\PricingService;
use PWT\Settings\Settings;
use PWT\Vendors\CostService;
use WP_Error;

final class BookingOrchestrator
{
    public function __construct(
        private readonly BookingRepository $bookings,
        private readonly BookingDataRepository $bookingsData,
        private readonly BookingItemRepository $items,
        private readonly AvailabilityRepository $availability,
        private readonly PricingService $pricing,
        private readonly HoldService $holds,
        private readonly CustomerRepository $customers,
        private readonly TravelerRepository $travelers,
        private readonly CostService $costs,
        private readonly PackageComponentService $packages,
        private readonly BookingAllocationService $allocation
    ) {
    }

    public function create(array $request): int|WP_Error
    {
        $request = $this->packages->expandRequest($request);

        $customerId = absint(
            $request['customer_id'] ?? 0
        );

        $items = is_array(
            $request['items'] ?? null
        )
            ? $request['items']
            : [];

        if (
            !$customerId
            && (
                !empty($request['email'])
                || !empty($request['phone'])
            )
        ) {
            $customerId = $this->customers->findOrCreate(
                $request
            );
        }

        if (!$customerId) {
            return new WP_Error(
                'pwt_customer_required',
                __(
                    'Customer is required.',
                    'wildtours-plugin'
                )
            );
        }

        if (!$items) {
            return new WP_Error(
                'pwt_items_required',
                __(
                    'At least one booking item is required.',
                    'wildtours-plugin'
                )
            );
        }

        $prepared = [];
        $total = 0.0;
        $travelStart = null;
        $travelEnd = null;

        foreach ($items as $item) {
            if (!is_array($item)) {
                return new WP_Error(
                    'pwt_invalid_booking_item',
                    __(
                        'Invalid booking item.',
                        'wildtours-plugin'
                    )
                );
            }

            $itemType = sanitize_key(
                (string) (
                    $item['item_type'] ?? ''
                )
            );

            $itemId = absint(
                $item['item_id']
                ?? $item['object_id']
                ?? 0
            );

            $date = sanitize_text_field(
                (string) (
                    $item['service_date']
                    ?? $item['start_date']
                    ?? ''
                )
            );

            $start = sanitize_text_field(
                (string) (
                    $item['start_date']
                    ?? $date
                )
            );

            $end = sanitize_text_field(
                (string) (
                    $item['end_date']
                    ?? $date
                )
            );

            $quantity = max(
                1,
                (int) (
                    $item['quantity'] ?? 1
                )
            );

            if (
                $itemType === ''
                || !$itemId
                || $date === ''
                || $start === ''
            ) {
                return new WP_Error(
                    'pwt_invalid_booking_item',
                    __(
                        'Invalid booking item.',
                        'wildtours-plugin'
                    )
                );
            }

            /*
             * Check every inventory date represented by the item.
             *
             * Same-day item:
             *     start_date
             *
             * Multi-day item:
             *     start_date through the day before end_date.
             */
            $allocationDates = $this->allocationDates(
                $start,
                $end
            );

            if (!$allocationDates) {
                return new WP_Error(
                    'pwt_invalid_booking_dates',
                    __(
                        'Invalid booking dates.',
                        'wildtours-plugin'
                    )
                );
            }

            foreach ($allocationDates as $allocationDate) {
                $availability = $this->availability->check(
                    $itemId,
                    $itemType,
                    $allocationDate,
                    $quantity
                );

                if (
                    empty($availability['available'])
                ) {
                    return new WP_Error(
                        'pwt_inventory_unavailable',
                        sprintf(
                            __(
                                'Requested inventory is not available on %s.',
                                'wildtours-plugin'
                            ),
                            $allocationDate
                        ),
                        [
                            'resource_id' => $itemId,
                            'resource_type' => $itemType,
                            'service_date' => $allocationDate,
                        ]
                    );
                }
            }

            /*
             * Pricing currently uses the primary service date.
             *
             * This preserves the uploaded plugin's existing pricing
             * contract while inventory validation is performed across
             * the complete date range.
             */
            $quote = $this->pricing->quote(
                $itemId,
                $itemType,
                $date,
                $quantity,
                (float) (
                    $item['unit_price'] ?? 0
                )
            );

            $unit = (float) (
                $quote['unit_price'] ?? 0
            );

            $subtotal = (float) (
                $quote['total'] ?? 0
            );

            $costInfo = $this->costs->forResource(
                $itemId,
                $itemType,
                $date,
                $quantity
            );

            $prepared[] = [
                'itemType' => $itemType,
                'itemId' => $itemId,
                'date' => $date,
                'start' => $start,
                'end' => $end,
                'quantity' => $quantity,
                'unit' => $unit,
                'subtotal' => $subtotal,
                'costInfo' => is_array($costInfo)
                    ? $costInfo
                    : [],
                'item' => $item,
            ];

            $total += $subtotal;

            $travelStart = (
                $travelStart === null
                || $start < $travelStart
            )
                ? $start
                : $travelStart;

            $travelEnd = (
                $travelEnd === null
                || $end > $travelEnd
            )
                ? $end
                : $travelEnd;
        }

        $name = sanitize_text_field(
            (string) (
                $request['name']
                ?? 'Booking'
            )
        );

        $email = sanitize_email(
            (string) (
                $request['email'] ?? ''
            )
        );

        $phone = sanitize_text_field(
            (string) (
                $request['phone'] ?? ''
            )
        );

        $message = sanitize_textarea_field(
            (string) (
                $request['message'] ?? ''
            )
        );

        $persons = max(
            1,
            (int) (
                $request['persons'] ?? 1
            )
        );

        $currency = strtoupper(
            sanitize_text_field(
                (string) (
                    $request['currency'] ?? 'INR'
                )
            )
        );

        $legacyId = $this->bookings->create(
            [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'travel_date' => $travelStart,
                'persons' => $persons,
                'customer_id' => $customerId,
                'message' => $message,
            ]
        );

        if (
            is_wp_error($legacyId)
            || !$legacyId
        ) {
            return is_wp_error($legacyId)
                ? $legacyId
                : new WP_Error(
                    'pwt_booking_create_failed',
                    __(
                        'Unable to create booking.',
                        'wildtours-plugin'
                    )
                );
        }

        $bookingId = Transaction::run(
            function () use (
                $legacyId,
                $customerId,
                $request,
                $prepared,
                $total,
                $travelStart,
                $travelEnd,
                $persons,
                $currency,
                $message
            ): int|WP_Error {
                $depositDue = round(
                    (
                        $total
                        * Settings::advancePercent()
                    ) / 100,
                    2
                );

                $bookingId = $this->bookingsData->create(
                    [
                        'legacy_post_id' => $legacyId,
                        'customer_id' => $customerId,
                        'status' => 'pending',
                        'travel_start' => $travelStart,
                        'travel_end' => $travelEnd,
                        'adults' => $persons,
                        'children' => max(
                            0,
                            (int) (
                                $request['children'] ?? 0
                            )
                        ),
                        'currency' => $currency,
                        'subtotal' => $total,
                        'total' => $total,
                        'deposit_due' => $depositDue,
                        'notes' => $message,
                        'source' => 'api',
                    ]
                );

                if (!$bookingId) {
                    return new WP_Error(
                        'pwt_booking_create_failed',
                        __(
                            'Unable to create booking.',
                            'wildtours-plugin'
                        )
                    );
                }

                foreach ($prepared as $item) {
                    $createdItemId = $this->items->create(
                        [
                            'booking_id' => $bookingId,
                            'item_type' => $item['itemType'],
                            'object_id' => $item['itemId'],
                            'item_id' => $item['itemId'],
                            'service_date' => $item['date'],
                            'start_date' => $item['start'],
                            'end_date' => $item['end'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unit'],
                            'total' => $item['subtotal'],
                            'subtotal' => $item['subtotal'],
                            'cost' => (float) (
                                $item['costInfo']['cost'] ?? 0
                            ),
                            'vendor_id' => absint(
                                $item['costInfo']['vendor_id'] ?? 0
                            ),
                            'vendor_name' => sanitize_text_field(
                                (string) (
                                    $item['costInfo']['vendor_name']
                                    ?? ''
                                )
                            ),
                            'meta' => $item['item']['meta'] ?? [],
                        ]
                    );

                    if (!$createdItemId) {
                        return new WP_Error(
                            'pwt_item_create_failed',
                            __(
                                'Unable to create booking item.',
                                'wildtours-plugin'
                            )
                        );
                    }
                }

                if (
                    !$this->holds->holdBooking(
                        $bookingId
                    )
                ) {
                    return new WP_Error(
                        'pwt_hold_failed',
                        __(
                            'Inventory could not be reserved.',
                            'wildtours-plugin'
                        )
                    );
                }

                foreach (
                    (array) (
                        $request['travelers'] ?? []
                    )
                    as $traveler
                ) {
                    if (
                        !is_array($traveler)
                        || empty(
                            $traveler['first_name']
                        )
                    ) {
                        return new WP_Error(
                            'pwt_traveler_invalid',
                            __(
                                'Each traveler requires a first name.',
                                'wildtours-plugin'
                            )
                        );
                    }

                    $this->travelers->add(
                        $bookingId,
                        $traveler
                    );
                }

                return (int) $bookingId;
            }
        );

        if (is_wp_error($bookingId)) {
            wp_delete_post(
                $legacyId,
                true
            );

            return $bookingId;
        }

        update_post_meta(
            $legacyId,
            '_pwt_normalized_booking_id',
            $bookingId
        );

        return (int) $bookingId;
    }

    public function confirm(
        int $bookingId
    ): bool|WP_Error {
        $booking = $this->bookingsData->find(
            $bookingId
        );

        if (!$booking) {
            return new WP_Error(
                'pwt_booking_not_found',
                __(
                    'Booking not found.',
                    'wildtours-plugin'
                )
            );
        }

        $current = (string) (
            $booking['status'] ?? 'pending'
        );

        if (
            $current === BookingStatus::CANCELLED
            || $current === BookingStatus::REFUNDED
        ) {
            return new WP_Error(
                'pwt_booking_not_confirmable',
                __(
                    'This booking cannot be confirmed.',
                    'wildtours-plugin'
                )
            );
        }

        /*
         * Confirmation must be idempotent.
         *
         * Without this check, confirming an already confirmed booking
         * could reserve the same inventory again.
         */
        if (
            $current === BookingStatus::CONFIRMED
            || $current === BookingStatus::PAID
        ) {
            return true;
        }

        $finalStatus = BookingStatus::CONFIRMED;

        if (
            !BookingStatus::canTransition(
                $current,
                $finalStatus
            )
        ) {
            return new WP_Error(
                'pwt_booking_not_confirmable',
                __(
                    'This booking cannot be confirmed.',
                    'wildtours-plugin'
                )
            );
        }

        $items = $this->items->byBooking(
            $bookingId
        );

        if (!$items) {
            return new WP_Error(
                'pwt_booking_items_missing',
                __(
                    'No booking items were found.',
                    'wildtours-plugin'
                )
            );
        }

        return Transaction::run(
            function () use (
                $booking,
                $bookingId,
                $finalStatus
            ): bool|WP_Error {
                $allocation = $this->allocation->confirm(
                    $bookingId
                );

                if (is_wp_error($allocation)) {
                    return $allocation;
                }

                if (
                    !$this->bookingsData->updateStatus(
                        $bookingId,
                        $finalStatus
                    )
                ) {
                    return new WP_Error(
                        'pwt_booking_status_failed',
                        __(
                            'Unable to confirm booking.',
                            'wildtours-plugin'
                        )
                    );
                }

                $this->syncLegacyStatus(
                    (int) (
                        $booking['legacy_post_id'] ?? 0
                    ),
                    $finalStatus
                );

                do_action(
                    'pwt/booking/confirmed',
                    $bookingId
                );

                return true;
            }
        );
    }

    public function cancel(
        int $bookingId
    ): bool|WP_Error {
        $booking = $this->bookingsData->find(
            $bookingId
        );

        if (!$booking) {
            return new WP_Error(
                'pwt_booking_not_found',
                __(
                    'Booking not found.',
                    'wildtours-plugin'
                )
            );
        }

        $current = (string) (
            $booking['status'] ?? ''
        );

        if ($current === BookingStatus::CANCELLED) {
            return true;
        }

        if ($current === BookingStatus::REFUNDED) {
            return new WP_Error(
                'pwt_booking_not_cancellable',
                __(
                    'Refunded bookings cannot be cancelled.',
                    'wildtours-plugin'
                )
            );
        }

        if (
            !BookingStatus::canTransition(
                $current,
                BookingStatus::CANCELLED
            )
        ) {
            return new WP_Error(
                'pwt_booking_not_cancellable',
                __(
                    'This booking cannot be cancelled.',
                    'wildtours-plugin'
                )
            );
        }

        return Transaction::run(
            function () use (
                $booking,
                $bookingId,
                $current
            ): bool|WP_Error {
                /*
                 * Pending bookings may only have holds.
                 * Confirmed and paid bookings also have allocated
                 * inventory that must be released.
                 */
                $this->holds->releaseBooking(
                    $bookingId
                );

                if (
                    in_array(
                        $current,
                        [
                            BookingStatus::CONFIRMED,
                            BookingStatus::PAID,
                        ],
                        true
                    )
                ) {
                    if (
                        !$this->allocation->release(
                            $bookingId
                        )
                    ) {
                        return new WP_Error(
                            'pwt_inventory_release_failed',
                            __(
                                'Unable to release booking inventory.',
                                'wildtours-plugin'
                            )
                        );
                    }
                }

                if (
                    !$this->bookingsData->updateStatus(
                        $bookingId,
                        BookingStatus::CANCELLED
                    )
                ) {
                    return new WP_Error(
                        'pwt_booking_status_failed',
                        __(
                            'Unable to cancel booking.',
                            'wildtours-plugin'
                        )
                    );
                }

                $this->syncLegacyStatus(
                    (int) (
                        $booking['legacy_post_id'] ?? 0
                    ),
                    BookingStatus::CANCELLED
                );

                do_action(
                    'pwt/booking/cancelled',
                    $bookingId
                );

                return true;
            }
        );
    }

    public function refund(
        int $bookingId
    ): bool|WP_Error {
        $booking = $this->bookingsData->find(
            $bookingId
        );

        if (!$booking) {
            return new WP_Error(
                'pwt_booking_not_found',
                __(
                    'Booking not found.',
                    'wildtours-plugin'
                )
            );
        }

        $current = (string) (
            $booking['status'] ?? ''
        );

        if ($current === BookingStatus::REFUNDED) {
            return true;
        }

        if (
            !BookingStatus::canTransition(
                $current,
                BookingStatus::REFUNDED
            )
        ) {
            return new WP_Error(
                'pwt_booking_not_refundable',
                __(
                    'This booking cannot be refunded.',
                    'wildtours-plugin'
                )
            );
        }

        return Transaction::run(
            function () use (
                $booking,
                $bookingId,
                $current
            ): bool|WP_Error {
                $this->holds->releaseBooking(
                    $bookingId
                );

                /*
                 * Use the centralized allocation service.
                 *
                 * This is critical for multi-day bookings because
                 * release() uses the same date-range model as
                 * confirmation.
                 */
                if (
                    in_array(
                        $current,
                        [
                            BookingStatus::CONFIRMED,
                            BookingStatus::PAID,
                        ],
                        true
                    )
                ) {
                    if (
                        !$this->allocation->release(
                            $bookingId
                        )
                    ) {
                        return new WP_Error(
                            'pwt_inventory_release_failed',
                            __(
                                'Unable to release booking inventory.',
                                'wildtours-plugin'
                            )
                        );
                    }
                }

                if (
                    !$this->bookingsData->updateStatus(
                        $bookingId,
                        BookingStatus::REFUNDED
                    )
                ) {
                    return new WP_Error(
                        'pwt_booking_status_failed',
                        __(
                            'Unable to refund booking.',
                            'wildtours-plugin'
                        )
                    );
                }

                $this->syncLegacyStatus(
                    (int) (
                        $booking['legacy_post_id'] ?? 0
                    ),
                    BookingStatus::REFUNDED
                );

                return true;
            }
        );
    }

    private function syncLegacyStatus(
        int $legacyPostId,
        string $status
    ): void {
        if (!$legacyPostId) {
            return;
        }

        update_post_meta(
            $legacyPostId,
            '_pwt_status',
            $status
        );
    }

    /**
     * Return all dates consuming inventory for a booking item.
     *
     * Same-day:
     *     2026-08-10 → 2026-08-10
     *     consumes 2026-08-10
     *
     * Multi-day:
     *     2026-08-10 → 2026-08-13
     *     consumes 10, 11 and 12
     *
     * The end date is exclusive for multi-day stays.
     *
     * @return string[]
     */
    private function allocationDates(
        string $start,
        string $end
    ): array {
        if ($start === '') {
            return [];
        }

        if ($end === '') {
            $end = $start;
        }

        try {
            $from = new DateTimeImmutable(
                $start
            );

            $to = new DateTimeImmutable(
                $end
            );
        } catch (\Throwable) {
            return [];
        }

        if ($to < $from) {
            return [];
        }

        if ($to == $from) {
            return [
                $from->format('Y-m-d'),
            ];
        }

        $dates = [];

        for (
            $date = $from;
            $date < $to;
            $date = $date->modify('+1 day')
        ) {
            $dates[] = $date->format(
                'Y-m-d'
            );
        }

        return $dates;
    }
}