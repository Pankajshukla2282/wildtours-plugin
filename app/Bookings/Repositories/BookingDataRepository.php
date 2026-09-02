<?php
declare(strict_types=1);
namespace PWT\Bookings\Repositories;
defined('ABSPATH') || exit;
use PWT\Core\Database\Schema;
use PWT\Logging\AuditLog;

final class BookingDataRepository
{
    public function __construct(private readonly AuditLog $audit)
    {
    }

    public function create(array $data): int
    {
        global $wpdb;
        $t = Schema::tables()['bookings'];
        $number = 'PWT-' . gmdate('Ymd') . '-' . strtoupper(wp_generate_password(6, false, false));
        $now = current_time('mysql');

        $wpdb->insert($t, [
            'legacy_post_id' => (int)($data['legacy_post_id'] ?? 0) ?: null,
            'customer_id' => (int)($data['customer_id'] ?? 0) ?: null,
            'booking_number' => $number,
            'status' => sanitize_key((string)($data['status'] ?? 'pending')),
            'travel_start' => $data['travel_start'] ?? null,
            'travel_end' => $data['travel_end'] ?? ($data['travel_start'] ?? null),
            'adults' => max(1, (int)($data['adults'] ?? $data['persons'] ?? 1)),
            'children' => max(0, (int)($data['children'] ?? 0)),
            'currency' => strtoupper(sanitize_text_field((string)($data['currency'] ?? 'INR'))),
            'subtotal' => (float)($data['subtotal'] ?? 0),
            'discount' => (float)($data['discount'] ?? 0),
            'tax' => (float)($data['tax'] ?? 0),
            'total' => (float)($data['total'] ?? 0),
            'deposit_due' => (float)($data['deposit_due'] ?? 0),
            'notes' => sanitize_textarea_field((string)($data['notes'] ?? '')),
            'source' => sanitize_key((string)($data['source'] ?? 'website')),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $id = (int)$wpdb->insert_id;

        if ($id) {
            $this->audit->record('booking', $id, 'booking.created', [
                'to' => ['status' => sanitize_key((string)($data['status'] ?? 'pending'))],
            ]);
        }

        return $id;
    }

    public function find(int $id): array
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . Schema::tables()['bookings'] . " WHERE id=%d", $id), ARRAY_A);
        return is_array($row) ? $row : [];
    }

    public function updateStatus(int $id, string $status): bool
    {
        global $wpdb;
        $status = sanitize_key($status);
        $current = (string)($this->find($id)['status'] ?? '');
        $updated = false !== $wpdb->update(
            Schema::tables()['bookings'],
            ['status' => $status, 'updated_at' => current_time('mysql')],
            ['id' => $id],
            ['%s','%s'],
            ['%d']
        );

        if ($updated && $current !== $status) {
            $this->audit->record('booking', $id, 'booking.status', [
                'from' => ['status' => $current],
                'to' => ['status' => $status],
            ]);
        }

        return $updated;
    }
}
