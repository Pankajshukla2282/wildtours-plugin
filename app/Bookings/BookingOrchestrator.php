<?php
declare(strict_types=1);
namespace PWT\Bookings;
defined('ABSPATH') || exit;

use PWT\Bookings\Repositories\BookingRepository;
use PWT\Availability\AvailabilityRepository;
use PWT\Pricing\PricingService;
use WP_Error;

final class BookingOrchestrator
{
    public function __construct(
        private readonly BookingRepository $bookings,
        private readonly BookingItemRepository $items,
        private readonly AvailabilityRepository $availability,
        private readonly PricingService $pricing
    ) {}

    public function create(array $request): int|WP_Error
    {
        $customerId = absint($request['customer_id'] ?? 0);
        $items = is_array($request['items'] ?? null) ? $request['items'] : [];

        if (!$customerId) {
            return new WP_Error('pwt_customer_required', __('Customer is required.', 'wildtours-plugin'));
        }
        if (!$items) {
            return new WP_Error('pwt_items_required', __('At least one booking item is required.', 'wildtours-plugin'));
        }

        $prepared = [];
        $total = 0.0;
        $travelStart = null;
        $travelEnd = null;

        foreach ($items as $item) {
            $itemType = sanitize_key((string)($item['item_type'] ?? ''));
            $itemId = absint($item['item_id'] ?? $item['object_id'] ?? 0);
            $date = sanitize_text_field((string)($item['service_date'] ?? $item['start_date'] ?? ''));
            $start = sanitize_text_field((string)($item['start_date'] ?? $date));
            $end = sanitize_text_field((string)($item['end_date'] ?? $date));
            $quantity = max(1, (int)($item['quantity'] ?? 1));

            if (!$itemType || !$itemId || !$date) {
                return new WP_Error('pwt_invalid_booking_item', __('Invalid booking item.', 'wildtours-plugin'));
            }

            $availability = $this->availability->check($itemId, $itemType, $date, $quantity);
            if (!$availability['available']) {
                return new WP_Error('pwt_inventory_unavailable', __('Requested inventory is not available.', 'wildtours-plugin'));
            }

            $quote = $this->pricing->quote($itemId, $itemType, $date, $quantity, (float)($item['unit_price'] ?? 0));
            $unit = (float)$quote['unit_price'];
            $subtotal = (float)$quote['total'];

            $prepared[] = compact('itemType','itemId','date','start','end','quantity','unit','subtotal','item');
            $total += $subtotal;

            $travelStart = $travelStart === null || $start < $travelStart ? $start : $travelStart;
            $travelEnd = $travelEnd === null || $end > $travelEnd ? $end : $travelEnd;
        }

        $bookingId = $this->bookings->create([
            'name' => sanitize_text_field((string)($request['name'] ?? 'Booking')),
            'email' => sanitize_email((string)($request['email'] ?? '')),
            'phone' => sanitize_text_field((string)($request['phone'] ?? '')),
            'travel_date' => $travelStart,
            'persons' => max(1, (int)($request['persons'] ?? 1)),
            'customer_id' => $customerId,
            'travel_start' => $travelStart,
            'travel_end' => $travelEnd,
            'total' => $total,
            'currency' => strtoupper(sanitize_text_field((string)($request['currency'] ?? 'INR'))),
        ]);

        if (is_wp_error($bookingId) || !$bookingId) {
            return $bookingId ?: new WP_Error('pwt_booking_create_failed', __('Unable to create booking.', 'wildtours-plugin'));
        }

        foreach ($prepared as $item) {
            $this->items->create([
                'booking_id' => (int)$bookingId,
                'item_type' => $item['itemType'],
                'item_id' => $item['itemId'],
                'service_date' => $item['date'],
                'start_date' => $item['start'],
                'end_date' => $item['end'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit'],
                'subtotal' => $item['subtotal'],
                'meta' => $item['item']['meta'] ?? [],
            ]);
        }

        return (int)$bookingId;
    }

    public function confirm(int $bookingId): bool|WP_Error
    {
        $booking = $this->bookings->find($bookingId);
        if (!$booking) {
            return new WP_Error('pwt_booking_not_found', __('Booking not found.', 'wildtours-plugin'));
        }

        $items = $this->items->byBooking($bookingId);
        foreach ($items as $item) {
            $resourceId = absint($item['object_id'] ?? 0);
            if (!$resourceId) continue;
            $date = (string)($item['start_date'] ?? '');
            if (!$this->availability->reserve($resourceId, (string)$item['item_type'], $date, (int)$item['quantity'])) {
                return new WP_Error('pwt_inventory_unavailable', __('Inventory is no longer available.', 'wildtours-plugin'));
            }
        }

        return $this->bookings->updateStatus($bookingId, 'confirmed');
    }

    public function cancel(int $bookingId): bool|WP_Error
    {
        $booking = $this->bookings->find($bookingId);
        if (!$booking) {
            return new WP_Error('pwt_booking_not_found', __('Booking not found.', 'wildtours-plugin'));
        }

        if (in_array((string)($booking['status'] ?? ''), ['confirmed','paid'], true)) {
            foreach ($this->items->byBooking($bookingId) as $item) {
                $resourceId = absint($item['object_id'] ?? 0);
                if ($resourceId) {
                    $this->availability->release($resourceId, (string)$item['item_type'], (string)$item['start_date'], (int)$item['quantity']);
                }
            }
        }

        return $this->bookings->updateStatus($bookingId, 'cancelled');
    }
}
