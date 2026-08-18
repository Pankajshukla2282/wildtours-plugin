<?php
declare(strict_types=1);
namespace PWT\Customers;
defined('ABSPATH') || exit;
use PWT\Core\Database\Schema;

final class TravelerRepository
{
    public function add(int $bookingId, array $data): int
    {
        global $wpdb;
        $now = current_time('mysql');
        $wpdb->insert(Schema::tables()['travelers'], [
            'booking_id' => absint($bookingId),
            'first_name' => sanitize_text_field((string)($data['first_name'] ?? '')),
            'last_name' => sanitize_text_field((string)($data['last_name'] ?? '')),
            'date_of_birth' => sanitize_text_field((string)($data['date_of_birth'] ?? '')) ?: null,
            'nationality' => sanitize_text_field((string)($data['nationality'] ?? '')),
            'passport_number' => sanitize_text_field((string)($data['passport_number'] ?? '')),
            'email' => sanitize_email((string)($data['email'] ?? '')),
            'phone' => sanitize_text_field((string)($data['phone'] ?? '')),
            'meta' => isset($data['meta']) && is_array($data['meta']) ? wp_json_encode($data['meta']) : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return (int)$wpdb->insert_id;
    }

    public function byBooking(int $bookingId): array
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . Schema::tables()['travelers'] . " WHERE booking_id=%d ORDER BY id ASC",
            absint($bookingId)
        ), ARRAY_A) ?: [];
    }

    public function count(int $bookingId): int
    {
        global $wpdb;
        return (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . Schema::tables()['travelers'] . " WHERE booking_id=%d",
            absint($bookingId)
        ));
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;
        return false !== $wpdb->update(
            Schema::tables()['travelers'],
            [
                'first_name' => sanitize_text_field((string)($data['first_name'] ?? '')),
                'last_name' => sanitize_text_field((string)($data['last_name'] ?? '')),
                'date_of_birth' => sanitize_text_field((string)($data['date_of_birth'] ?? '')) ?: null,
                'nationality' => sanitize_text_field((string)($data['nationality'] ?? '')),
                'passport_number' => sanitize_text_field((string)($data['passport_number'] ?? '')),
                'email' => sanitize_email((string)($data['email'] ?? '')),
                'phone' => sanitize_text_field((string)($data['phone'] ?? '')),
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id],
            ['%s','%s','%s','%s','%s','%s','%s','%s'],
            ['%d']
        );
    }

    public function delete(int $id): bool
    {
        global $wpdb;
        return false !== $wpdb->delete(Schema::tables()['travelers'], ['id' => $id], ['%d']);
    }
}