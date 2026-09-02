<?php
declare(strict_types=1);
namespace PWT\Bookings\Repositories;
defined('ABSPATH') || exit;
use PWT\Core\Database\Schema;

final class BookingItemRepository
{
    public function add(int $bookingId, array $item): int
    {
        global $wpdb;
        $qty = max(1, (int)($item['quantity'] ?? 1));
        $unit = (float)($item['unit_price'] ?? 0);
        $total = (float)($item['total'] ?? ($qty * $unit));
        $wpdb->insert(Schema::tables()['items'], [
            'booking_id' => $bookingId,
            'item_type' => sanitize_key((string)($item['item_type'] ?? 'service')),
            'object_id' => (int)($item['object_id'] ?? 0) ?: null,
            'name' => sanitize_text_field((string)($item['name'] ?? 'Service')),
            'quantity' => $qty,
            'start_date' => $item['start_date'] ?? null,
            'end_date' => $item['end_date'] ?? null,
            'unit_price' => $unit,
            'total' => $total,
            'meta' => wp_json_encode($item['meta'] ?? []),
            'created_at' => current_time('mysql'),
        ]);
        return (int)$wpdb->insert_id;
    }

    public function forBooking(int $bookingId): array
    {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM " . Schema::tables()['items'] . " WHERE booking_id=%d ORDER BY id ASC", $bookingId),
            ARRAY_A
        ) ?: [];
    }
}
