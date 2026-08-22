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
                $start = (string)($item['start_date'] ?? '');
                $end = (string)($item['end_date'] ?? $start);
                $quantity = max(1, (int)($item['quantity'] ?? 1));

                if (!$resourceId || !$resourceType || $start === '') {
                    continue;
                }

                foreach ($this->serviceDates($start, $end) as $date) {
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
    /** @return array<int,string> */
    private function serviceDates(string $start, string $end): array
    {
        try { $from = new \DateTimeImmutable($start); $to = new \DateTimeImmutable($end ?: $start); } catch (\Throwable) { return [$start]; }
        if ($to < $from) { $to = $from; }
        // End date is exclusive for multi-day stays, but single-day services reserve once.
        $exclusive = $to > $from;
        $last = $exclusive ? $to->modify('-1 day') : $to;
        $dates = [];
        for ($d=$from; $d <= $last; $d=$d->modify('+1 day')) { $dates[] = $d->format('Y-m-d'); }
        return $dates ?: [$from->format('Y-m-d')];
    }

    private function releaseHeld(array $held): void
    {
        foreach ($held as [$resourceId, $resourceType, $date, $quantity]) {
            $this->availability->release($resourceId, $resourceType, $date, $quantity);
        }
    }
}