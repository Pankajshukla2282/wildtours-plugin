<?php
declare(strict_types=1);
namespace PWT\Availability;
defined('ABSPATH') || exit;

use PWT\Bookings\BookingItemRepository;
use PWT\Core\Database\Transaction;

final class HoldService
{
    public function __construct(
        private readonly HoldRepository $holds,
        private readonly AvailabilityRepository $availability,
        private readonly BookingItemRepository $items
    ) {}

    public function holdBooking(int $bookingId, int $ttlSeconds = 900): bool
    {
        $items = $this->items->byBooking($bookingId);
        if (!$items) {
            return false;
        }

        return Transaction::run(function () use ($bookingId, $items, $ttlSeconds): bool {
            $held = [];
            foreach ($items as $item) {
                $resourceId = absint($item['object_id'] ?? 0);
                $resourceType = (string)($item['item_type'] ?? '');
                $date = (string)($item['start_date'] ?? '');
                $quantity = max(1, (int)($item['quantity'] ?? 1));

                if (!$resourceId || !$resourceType || $date === '') {
                    continue;
                }

                if (!$this->availability->check($resourceId, $resourceType, $date, $quantity)['available']) {
                    $this->releaseHeld($held);
                    return false;
                }

                if (!$this->availability->reserve($resourceId, $resourceType, $date, $quantity)) {
                    $this->releaseHeld($held);
                    return false;
                }

                $held[] = [$resourceId, $resourceType, $date, $quantity];
                $this->holds->create($bookingId, $resourceId, $resourceType, $date, $quantity, $ttlSeconds);
            }

            return true;
        });
    }

    public function confirmBooking(int $bookingId): int
    {
        return $this->holds->confirmBooking($bookingId);
    }

    public function releaseBooking(int $bookingId, string $status = 'released'): int
    {
        $active = $this->holds->activeForBooking($bookingId);
        if (!$active) {
            return 0;
        }

        return Transaction::run(function () use ($bookingId, $active, $status): int {
            foreach ($active as $hold) {
                $this->availability->release(
                    (int)$hold['resource_id'],
                    (string)$hold['resource_type'],
                    (string)$hold['service_date'],
                    (int)$hold['quantity']
                );
            }

            return $this->holds->releaseBooking($bookingId, $status);
        });
    }

    public function expireExpired(): int
    {
        $expired = $this->holds->activeExpired();
        if (!$expired) {
            return 0;
        }

        return Transaction::run(function () use ($expired): int {
            foreach ($expired as $hold) {
                $this->availability->release(
                    (int)$hold['resource_id'],
                    (string)$hold['resource_type'],
                    (string)$hold['service_date'],
                    (int)$hold['quantity']
                );
            }

            return $this->holds->expireIds(array_column($expired, 'id'));
        });
    }

    /**
     * @param array<int, array{0:int,1:string,2:string,3:int}> $held
     */
    private function releaseHeld(array $held): void
    {
        foreach ($held as [$resourceId, $resourceType, $date, $quantity]) {
            $this->availability->release($resourceId, $resourceType, $date, $quantity);
        }
    }
}