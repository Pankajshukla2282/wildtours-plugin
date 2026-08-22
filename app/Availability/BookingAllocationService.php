<?php

declare(strict_types=1);

namespace PWT\Availability;

defined('ABSPATH') || exit;

use PWT\Bookings\BookingItemRepository;
use PWT\Core\Database\Transaction;
use WP_Error;

final class BookingAllocationService
{
    public function __construct(
        private readonly AvailabilityRepository $availability,
        private readonly BookingItemRepository $items,
        private readonly HoldService $holdService,
        private readonly HoldRepository $holds
    ) {
    }

    public function confirm(
        int $bookingId
    ): bool|WP_Error {
        $items = $this->items->byBooking(
            $bookingId
        );

        if (!$items) {
            return new WP_Error(
                'pwt_booking_items_missing',
                __(
                    'No allocatable booking items were found.',
                    'wildtours-plugin'
                )
            );
        }

        return Transaction::run(
            function () use (
                $bookingId,
                $items
            ): bool|WP_Error {
                $allocated = [];

                /*
                 * Remove this booking's active holds first.
                 * The reservation query will still account for
                 * holds belonging to other bookings.
                 */
                $this->holdService->releaseBooking(
                    $bookingId,
                    'confirming'
                );

                foreach ($items as $item) {
                    $resourceId = absint(
                        $item['object_id'] ?? 0
                    );

                    $resourceType = sanitize_key(
                        (string) (
                            $item['item_type'] ?? ''
                        )
                    );

                    $quantity = max(
                        1,
                        (int) (
                            $item['quantity'] ?? 1
                        )
                    );

                    if (
                        !$resourceId
                        || $resourceType === ''
                    ) {
                        continue;
                    }

                    foreach (
                        $this->datesForItem($item)
                        as $date
                    ) {
                        if (
                            !$this->availability->reserve(
                                $resourceId,
                                $resourceType,
                                $date,
                                $quantity
                            )
                        ) {
                            $this->rollback(
                                $allocated
                            );

                            return new WP_Error(
                                'pwt_inventory_unavailable',
                                __(
                                    'Inventory became unavailable while confirming the booking.',
                                    'wildtours-plugin'
                                )
                            );
                        }

                        $allocated[] = [
                            'resource_id' => $resourceId,
                            'resource_type' => $resourceType,
                            'service_date' => $date,
                            'quantity' => $quantity,
                        ];
                    }
                }

                /*
                 * Normally there are no active holds left after
                 * releaseBooking(). This is retained as a safe
                 * state cleanup.
                 */
                $this->holds->confirmBooking(
                    $bookingId
                );

                return true;
            }
        );
    }

    public function release(
        int $bookingId
    ): bool {
        $items = $this->items->byBooking(
            $bookingId
        );

        return Transaction::run(
            function () use ($items): bool {
                foreach ($items as $item) {
                    $resourceId = absint(
                        $item['object_id'] ?? 0
                    );

                    $resourceType = sanitize_key(
                        (string) (
                            $item['item_type'] ?? ''
                        )
                    );

                    if (
                        !$resourceId
                        || $resourceType === ''
                    ) {
                        continue;
                    }

                    $quantity = max(
                        1,
                        (int) (
                            $item['quantity'] ?? 1
                        )
                    );

                    foreach (
                        $this->datesForItem($item)
                        as $date
                    ) {
                        if (
                            !$this->availability->release(
                                $resourceId,
                                $resourceType,
                                $date,
                                $quantity
                            )
                        ) {
                            return false;
                        }
                    }
                }

                return true;
            }
        );
    }

    /**
     * Booking schema:
     *
     * start_date
     * end_date
     *
     * Same-day services allocate one date.
     * Multi-day services use an exclusive end date.
     *
     * @return string[]
     */
    private function datesForItem(
        array $item
    ): array {
        $start = sanitize_text_field(
            (string) (
                $item['start_date'] ?? ''
            )
        );

        $end = sanitize_text_field(
            (string) (
                $item['end_date']
                ?? $start
            )
        );

        if ($start === '') {
            return [];
        }

        try {
            $from = new \DateTimeImmutable(
                $start
            );

            $to = new \DateTimeImmutable(
                $end !== ''
                    ? $end
                    : $start
            );
        } catch (\Throwable) {
            return [];
        }

        if ($to < $from) {
            return [];
        }

        /*
         * Same-day item.
         */
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

    private function rollback(
        array $allocated
    ): void {
        foreach (
            array_reverse($allocated)
            as $allocation
        ) {
            $this->availability->release(
                (int) $allocation['resource_id'],
                (string) $allocation['resource_type'],
                (string) $allocation['service_date'],
                (int) $allocation['quantity']
            );
        }
    }
}