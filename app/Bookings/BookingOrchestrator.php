<?php
declare(strict_types=1);
namespace PWT\Bookings;
defined('ABSPATH') || exit;

use PWT\Bookings\Repositories\BookingRepository;
use PWT\Bookings\Repositories\BookingDataRepository;
use PWT\Availability\AvailabilityRepository;
use PWT\Availability\HoldService;
use PWT\Core\Database\Transaction;
use PWT\Customers\CustomerRepository;
use PWT\Customers\TravelerRepository;
use PWT\Pricing\PricingService;
use PWT\Settings\Settings;
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
        private readonly TravelerRepository $travelers
    ) {}

    public function create(array $request): int|WP_Error
    {
        $customerId = absint($request['customer_id'] ?? 0);
        $items = is_array($request['items'] ?? null) ? $request['items'] : [];

        if (!$customerId && (!empty($request['email']) || !empty($request['phone']))) {
            $customerId = $this->customers->findOrCreate($request);
        }

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

        $name = sanitize_text_field((string)($request['name'] ?? 'Booking'));
        $email = sanitize_email((string)($request['email'] ?? ''));
        $phone = sanitize_text_field((string)($request['phone'] ?? ''));
        $message = sanitize_textarea_field((string)($request['message'] ?? ''));
        $persons = max(1, (int)($request['persons'] ?? 1));
        $currency = strtoupper(sanitize_text_field((string)($request['currency'] ?? 'INR')));

        $legacyId = $this->bookings->create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'travel_date' => $travelStart,
            'persons' => $persons,
            'customer_id' => $customerId,
            'message' => $message,
        ]);

        if (is_wp_error($legacyId) || !$legacyId) {
            return $legacyId ?: new WP_Error('pwt_booking_create_failed', __('Unable to create booking.', 'wildtours-plugin'));
        }

        $bookingId = Transaction::run(function () use ($legacyId, $customerId, $request, $prepared, $total, $travelStart, $travelEnd, $persons, $currency, $message): int|WP_Error {
            $depositDue = round(($total * Settings::advancePercent()) / 100, 2);

            $bookingId = $this->bookingsData->create([
                'legacy_post_id' => $legacyId,
                'customer_id' => $customerId,
                'status' => 'pending',
                'travel_start' => $travelStart,
                'travel_end' => $travelEnd,
                'adults' => $persons,
                'children' => max(0, (int)($request['children'] ?? 0)),
                'currency' => $currency,
                'subtotal' => $total,
                'total' => $total,
                'deposit_due' => $depositDue,
                'notes' => $message,
                'source' => 'api',
            ]);

            if (!$bookingId) {
                return new WP_Error('pwt_booking_create_failed', __('Unable to create booking.', 'wildtours-plugin'));
            }

            foreach ($prepared as $item) {
                $itemId = $this->items->create([
                    'booking_id' => $bookingId,
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

                if (!$itemId) {
                    return new WP_Error('pwt_item_create_failed', __('Unable to create booking item.', 'wildtours-plugin'));
                }
            }

            if (!$this->holds->holdBooking($bookingId)) {
                return new WP_Error('pwt_hold_failed', __('Inventory could not be reserved.', 'wildtours-plugin'));
            }

            foreach ((array)($request['travelers'] ?? []) as $traveler) {
                if (empty($traveler['first_name'])) {
                    return new WP_Error('pwt_traveler_invalid', __('Each traveler requires a first name.', 'wildtours-plugin'));
                }
                $this->travelers->add($bookingId, $traveler);
            }

            return $bookingId;
        });

        if (is_wp_error($bookingId)) {
            wp_delete_post($legacyId, true);
            return $bookingId;
        }

        update_post_meta($legacyId, '_pwt_normalized_booking_id', $bookingId);

        return (int)$bookingId;
    }

    public function confirm(int $bookingId): bool|WP_Error
    {
        $booking = $this->bookingsData->find($bookingId);
        if (!$booking) {
            return new WP_Error('pwt_booking_not_found', __('Booking not found.', 'wildtours-plugin'));
        }

        $current = (string)($booking['status'] ?? 'pending');
        if ($current === BookingStatus::CANCELLED || $current === BookingStatus::REFUNDED) {
            return new WP_Error('pwt_booking_not_confirmable', __('This booking cannot be confirmed.', 'wildtours-plugin'));
        }

        $finalStatus = $current === BookingStatus::PAID ? BookingStatus::PAID : BookingStatus::CONFIRMED;
        if ($current !== $finalStatus && !BookingStatus::canTransition($current, $finalStatus)) {
            return new WP_Error('pwt_booking_not_confirmable', __('This booking cannot be confirmed.', 'wildtours-plugin'));
        }

        $items = $this->items->byBooking($bookingId);
        if (!$items) {
            return new WP_Error('pwt_booking_not_found', __('Booking not found.', 'wildtours-plugin'));
        }

        return Transaction::run(function () use ($booking, $bookingId, $items, $current, $finalStatus): bool|WP_Error {
            $this->holds->releaseBooking($bookingId);

            foreach ($items as $item) {
                $resourceId = absint($item['object_id'] ?? 0);
                if (!$resourceId) {
                    continue;
                }
                $date = (string)($item['start_date'] ?? '');
                if (!$this->availability->reserve($resourceId, (string)$item['item_type'], $date, (int)$item['quantity'])) {
                    return new WP_Error('pwt_inventory_unavailable', __('Inventory is no longer available.', 'wildtours-plugin'));
                }
            }

            if ($current !== $finalStatus && !$this->bookingsData->updateStatus($bookingId, $finalStatus)) {
                return new WP_Error('pwt_booking_status_failed', __('Unable to confirm booking.', 'wildtours-plugin'));
            }

            $this->syncLegacyStatus((int)($booking['legacy_post_id'] ?? 0), $finalStatus);

            if ($finalStatus === BookingStatus::CONFIRMED) {
                do_action('pwt/booking/confirmed', $bookingId);
            }

            return true;
        });
    }

    public function cancel(int $bookingId): bool|WP_Error
    {
        $booking = $this->bookingsData->find($bookingId);
        if (!$booking) {
            return new WP_Error('pwt_booking_not_found', __('Booking not found.', 'wildtours-plugin'));
        }

        $current = (string)($booking['status'] ?? '');
        if ($current === BookingStatus::CANCELLED) {
            return true;
        }
        if ($current === BookingStatus::REFUNDED) {
            return new WP_Error('pwt_booking_not_cancellable', __('Refunded bookings cannot be cancelled.', 'wildtours-plugin'));
        }
        if (!BookingStatus::canTransition($current, BookingStatus::CANCELLED)) {
            return new WP_Error('pwt_booking_not_cancellable', __('This booking cannot be cancelled.', 'wildtours-plugin'));
        }

        $items = $this->items->byBooking($bookingId);

        return Transaction::run(function () use ($booking, $bookingId, $items, $current): bool|WP_Error {
            $this->holds->releaseBooking($bookingId);

            if (in_array($current, [BookingStatus::CONFIRMED, BookingStatus::PAID], true)) {
                foreach ($items as $item) {
                    $resourceId = absint($item['object_id'] ?? 0);
                    if ($resourceId) {
                        $this->availability->release(
                            $resourceId,
                            (string)$item['item_type'],
                            (string)$item['start_date'],
                            (int)$item['quantity']
                        );
                    }
                }
            }

            if (!$this->bookingsData->updateStatus($bookingId, BookingStatus::CANCELLED)) {
                return new WP_Error('pwt_booking_status_failed', __('Unable to cancel booking.', 'wildtours-plugin'));
            }

            $this->syncLegacyStatus((int)($booking['legacy_post_id'] ?? 0), BookingStatus::CANCELLED);

            do_action('pwt/booking/cancelled', $bookingId);

            return true;
        });
    }

    public function refund(int $bookingId): bool|WP_Error
    {
        $booking = $this->bookingsData->find($bookingId);
        if (!$booking) {
            return new WP_Error('pwt_booking_not_found', __('Booking not found.', 'wildtours-plugin'));
        }

        $current = (string)($booking['status'] ?? '');
        if ($current === BookingStatus::REFUNDED) {
            return true;
        }
        if (!BookingStatus::canTransition($current, BookingStatus::REFUNDED)) {
            return new WP_Error('pwt_booking_not_refundable', __('This booking cannot be refunded.', 'wildtours-plugin'));
        }

        $items = $this->items->byBooking($bookingId);

        return Transaction::run(function () use ($booking, $bookingId, $items): bool|WP_Error {
            $this->holds->releaseBooking($bookingId);

            foreach ($items as $item) {
                $resourceId = absint($item['object_id'] ?? 0);
                if ($resourceId) {
                    $this->availability->release(
                        $resourceId,
                        (string)$item['item_type'],
                        (string)$item['start_date'],
                        (int)$item['quantity']
                    );
                }
            }

            if (!$this->bookingsData->updateStatus($bookingId, BookingStatus::REFUNDED)) {
                return new WP_Error('pwt_booking_status_failed', __('Unable to refund booking.', 'wildtours-plugin'));
            }

            $this->syncLegacyStatus((int)($booking['legacy_post_id'] ?? 0), BookingStatus::REFUNDED);

            return true;
        });
    }

    private function syncLegacyStatus(int $legacyPostId, string $status): void
    {
        if ($legacyPostId) {
            update_post_meta($legacyPostId, '_pwt_status', $status);
        }
    }
}