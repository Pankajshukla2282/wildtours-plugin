<?php

declare(strict_types=1);

namespace PWT\Availability;

defined('ABSPATH') || exit;

use DateTimeImmutable;
use PWT\Bookings\BookingItemRepository;
use PWT\Core\Database\Transaction;

final class HoldService
{
    public function __construct(
        private readonly HoldRepository $holds,
        private readonly AvailabilityRepository $availability,
        private readonly BookingItemRepository $items
    ) {
    }

    /**
     * Create inventory holds for every allocatable date of every
     * booking item.
     */
    public function holdBooking(
        int $bookingId,
        int $ttlSeconds = 900
    ): bool {
        $items = $this->items->byBooking($bookingId);

        if (!$items) {
            return false;
        }

        return Transaction::run(
            function () use (
                $bookingId,
                $items,
                $ttlSeconds
            ): bool {
                $createdHoldIds = [];

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
                        $this->rollbackHolds($createdHoldIds);

                        return false;
                    }

                    $dates = $this->allocationDates(
                        (string) (
                            $item['start_date'] ?? ''
                        ),
                        (string) (
                            $item['end_date'] ?? ''
                        )
                    );

                    if (!$dates) {
                        $this->rollbackHolds($createdHoldIds);

                        return false;
                    }

                    foreach ($dates as $date) {
                        /*
                         * Check availability before creating the hold.
                         *
                         * AvailabilityRepository::check() includes:
                         * - reserved inventory
                         * - blocked inventory
                         * - active holds from other bookings
                         */
                        $availability = $this->availability->check(
                            $resourceId,
                            $resourceType,
                            $date,
                            $quantity
                        );

                        if (
                            empty($availability['available'])
                        ) {
                            $this->rollbackHolds(
                                $createdHoldIds
                            );

                            return false;
                        }

                        $holdId = $this->holds->create(
                            $bookingId,
                            $resourceId,
                            $resourceType,
                            $date,
                            $quantity,
                            $ttlSeconds
                        );

                        if (!$holdId) {
                            $this->rollbackHolds(
                                $createdHoldIds
                            );

                            return false;
                        }

                        $createdHoldIds[] = $holdId;

                        /*
                         * Clear the cached availability state so
                         * subsequent checks in this request do not
                         * use stale inventory data.
                         */
                        $this->availability->flush(
                            $resourceId,
                            $resourceType,
                            $date
                        );
                    }
                }

                return true;
            }
        );
    }

    /**
     * Release all active holds belonging to a booking.
     *
     * This is used when:
     * - a booking is cancelled before confirmation
     * - a booking expires
     * - confirmation converts holds into reservations
     * - booking creation needs cleanup
     */
    public function releaseBooking(
        int $bookingId,
        string $status = 'released'
    ): bool {
        $activeHolds = $this->holds->activeForBooking(
            $bookingId
        );

        if (!$activeHolds) {
            return true;
        }

        return Transaction::run(
            function () use (
                $bookingId,
                $status,
                $activeHolds
            ): bool {
                $result = $this->holds->releaseBooking(
                    $bookingId,
                    $status
                );

                if ($result === false) {
                    return false;
                }

                foreach ($activeHolds as $hold) {
                    $this->availability->flush(
                        absint(
                            $hold['resource_id'] ?? 0
                        ),
                        sanitize_key(
                            (string) (
                                $hold['resource_type'] ?? ''
                            )
                        ),
                        sanitize_text_field(
                            (string) (
                                $hold['service_date'] ?? ''
                            )
                        )
                    );
                }

                return true;
            }
        );
    }

    /**
     * Expire all holds whose expiration time has passed.
     */
    public function expireExpired(): int
    {
        $expired = $this->holds->activeExpired();

        if (!$expired) {
            return 0;
        }

        $ids = [];
        $resources = [];

        foreach ($expired as $hold) {
            $holdId = absint(
                $hold['id'] ?? 0
            );

            if ($holdId) {
                $ids[] = $holdId;
            }

            $resources[] = [
                'resource_id' => absint(
                    $hold['resource_id'] ?? 0
                ),
                'resource_type' => sanitize_key(
                    (string) (
                        $hold['resource_type'] ?? ''
                    )
                ),
                'service_date' => sanitize_text_field(
                    (string) (
                        $hold['service_date'] ?? ''
                    )
                ),
            ];
        }

        if (!$ids) {
            return 0;
        }

        $updated = $this->holds->expireIds($ids);

        if ($updated > 0) {
            foreach ($resources as $resource) {
                if (
                    !$resource['resource_id']
                    || $resource['resource_type'] === ''
                    || $resource['service_date'] === ''
                ) {
                    continue;
                }

                $this->availability->flush(
                    $resource['resource_id'],
                    $resource['resource_type'],
                    $resource['service_date']
                );
            }
        }

        return $updated;
    }

    /**
     * @return string[]
     */
    private function allocationDates(
        string $start,
        string $end
    ): array {
        $start = sanitize_text_field($start);
        $end = sanitize_text_field($end);

        if ($start === '') {
            return [];
        }

        if ($end === '') {
            $end = $start;
        }

        try {
            $from = new DateTimeImmutable($start);
            $to = new DateTimeImmutable($end);
        } catch (\Throwable) {
            return [];
        }

        if ($to < $from) {
            return [];
        }

        /*
         * Same-day booking/service.
         */
        if ($to == $from) {
            return [
                $from->format('Y-m-d'),
            ];
        }

        /*
         * Multi-day booking.
         *
         * End date is exclusive:
         *
         * 10 Aug -> 13 Aug
         * consumes 10, 11 and 12.
         */
        $dates = [];

        for (
            $date = $from;
            $date < $to;
            $date = $date->modify('+1 day')
        ) {
            $dates[] = $date->format('Y-m-d');
        }

        return $dates;
    }

    /**
     * Roll back holds created during a failed hold operation.
     *
     * @param int[] $holdIds
     */
    private function rollbackHolds(
        array $holdIds
    ): void {
        if (!$holdIds) {
            return;
        }

        $this->holds->expireIds($holdIds);
    }
}