<?php
declare(strict_types=1);
namespace PWT\Availability;
defined('ABSPATH') || exit;
use PWT\Core\Database\Schema;

final class AvailabilityRepository
{
    public function set(int $resourceId, string $resourceType, string $date, int $capacity, int $reserved = 0, int $blocked = 0, string $status = 'open'): bool
    {
        global $wpdb;
        $table = Schema::tables()['availability'];
        return false !== $wpdb->replace($table, [
            'resource_type' => sanitize_key($resourceType),
            'resource_id' => $resourceId,
            'service_date' => sanitize_text_field($date),
            'capacity' => max(0, $capacity),
            'reserved' => max(0, $reserved),
            'blocked' => max(0, $blocked),
            'status' => sanitize_key($status),
        ]);
    }

    public function get(int $resourceId, string $resourceType, string $date): array
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . Schema::tables()['availability'] . " WHERE resource_type=%s AND resource_id=%d AND service_date=%s LIMIT 1",
            sanitize_key($resourceType), $resourceId, $date
        ), ARRAY_A);
        return is_array($row) ? $row : [];
    }
    public function check(int $resourceId, string $resourceType, string $date, int $quantity = 1): array
    {
        $row = $this->get($resourceId, $resourceType, $date);
        if (!$row) {
            return ['available' => true, 'remaining' => PHP_INT_MAX];
        }

        $remaining = max(0, (int)$row['capacity'] - (int)$row['reserved'] - (int)$row['blocked']);
        if (($row['status'] ?? 'open') !== 'open') {
            $remaining = 0;
        }

        return ['available' => $remaining >= max(1, $quantity), 'remaining' => $remaining];
    }

    public function reserve(int $resourceId, string $resourceType, string $date, int $quantity = 1): bool
    {
        global $wpdb;
        $table = Schema::tables()['availability'];
        $quantity = max(1, $quantity);
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE {$table}
             SET reserved = reserved + %d
             WHERE resource_type=%s AND resource_id=%d AND service_date=%s
             AND status='open' AND (capacity - reserved - blocked) >= %d",
            $quantity, sanitize_key($resourceType), $resourceId, sanitize_text_field($date), $quantity
        ));
        return $result === 1;
    }

    public function release(int $resourceId, string $resourceType, string $date, int $quantity = 1): bool
    {
        global $wpdb;
        $table = Schema::tables()['availability'];
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET reserved = GREATEST(0, reserved - %d)
             WHERE resource_type=%s AND resource_id=%d AND service_date=%s",
            max(1, $quantity), sanitize_key($resourceType), $resourceId, sanitize_text_field($date)
        ));
        return $result !== false;
    }
}
