<?php
declare(strict_types=1);
namespace PWT\Bookings;
defined('ABSPATH') || exit;

use PWT\Core\Database\Schema;

final class BookingItemRepository
{
    public function create(array $data): int
    {
        global $wpdb;
        $qty = max(1, (int)($data['quantity'] ?? 1));
        $unit = max(0, (float)($data['unit_price'] ?? 0));
        $total = max(0, (float)($data['subtotal'] ?? ($unit * $qty)));

        $wpdb->insert(Schema::tables()['items'], [
            'booking_id' => absint($data['booking_id'] ?? 0),
            'item_type' => sanitize_key((string)($data['item_type'] ?? 'service')),
            'object_id' => absint($data['item_id'] ?? $data['object_id'] ?? 0) ?: null,
            'name' => sanitize_text_field((string)($data['name'] ?? 'Service')),
            'quantity' => $qty,
            'start_date' => sanitize_text_field((string)($data['start_date'] ?? $data['service_date'] ?? '')) ?: null,
            'end_date' => sanitize_text_field((string)($data['end_date'] ?? $data['service_date'] ?? '')) ?: null,
            'unit_price' => $unit,
            'total' => $total,
            'cost' => max(0, (float)($data['cost'] ?? 0)),
            'vendor_id' => absint($data['vendor_id'] ?? 0) ?: null,
            'vendor_name' => sanitize_text_field((string)($data['vendor_name'] ?? '')) ?: null,
            'meta' => wp_json_encode(is_array($data['meta'] ?? null) ? $data['meta'] : []),
            'created_at' => current_time('mysql'),
        ]);

        return (int) $wpdb->insert_id;
    }

    public function byBooking(int $bookingId): array
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . Schema::tables()['items'] . " WHERE booking_id=%d ORDER BY id ASC",
            $bookingId
        ), ARRAY_A) ?: [];
    }

    public function deleteByBooking(int $bookingId): int
    {
        global $wpdb;
        return (int) $wpdb->query($wpdb->prepare(
            "DELETE FROM " . Schema::tables()['items'] . " WHERE booking_id=%d",
            $bookingId
        ));
    }
}
