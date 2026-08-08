<?php
declare(strict_types=1);
namespace PWT\Availability;
defined('ABSPATH') || exit;

use PWT\Bookings\BookingItemRepository;

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

        foreach ($items as $item) {
            if (!$this->availability->check(
                (int)$item['item_id'],
                (string)$item['item_type'],
                (string)$item['service_date'],
                (int)$item['quantity']
            )['available']) {
                return false;
            }
        }

        foreach ($items as $item) {
            $this->holds->create(
                $bookingId,
                (int)$item['item_id'],
                (string)$item['item_type'],
                (string)$item['service_date'],
                (int)$item['quantity'],
                $ttlSeconds
            );
        }
        return true;
    }

    public function releaseBooking(int $bookingId): int
    {
        return $this->holds->releaseBooking($bookingId);
    }
}
