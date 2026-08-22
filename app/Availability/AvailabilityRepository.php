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
        $result = $wpdb->query($wpdb->prepare(
            "INSERT INTO {$table} (resource_type, resource_id, service_date, capacity, reserved, blocked, status)
             VALUES (%s, %d, %s, %d, %d, %d, %s)
             ON DUPLICATE KEY UPDATE capacity = VALUES(capacity), status = VALUES(status)",
            sanitize_key($resourceType),
            $resourceId,
            sanitize_text_field($date),
            max(0, $capacity),
            max(0, $reserved),
            max(0, $blocked),
            sanitize_key($status)
        ));
        if ($result !== false) {
            $this->flush($resourceId, $resourceType, $date);
        }
        return $result !== false;
    }

    public function get(int $resourceId, string $resourceType, string $date): array
    {
        $key = $this->cacheKey($resourceId, $resourceType, $date);
        $cached = wp_cache_get($key, 'pwt_availability');
        if (is_array($cached)) {
            return $cached;
        }

        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . Schema::tables()['availability'] . " WHERE resource_type=%s AND resource_id=%d AND service_date=%s LIMIT 1",
            sanitize_key($resourceType), $resourceId, $date
        ), ARRAY_A);

        $row = is_array($row) ? $row : [];
        wp_cache_set($key, $row, 'pwt_availability', 60);

        return $row;
    }
    public function check(int $resourceId, string $resourceType, string $date, int $quantity = 1): array
    {
        $row = $this->get($resourceId, $resourceType, $date);
        if (!$row) {
            // Unknown inventory must not silently behave as unlimited capacity.
            return ['available' => false, 'remaining' => 0, 'reason' => 'inventory_not_configured'];
        }

        $held = $this->activeHeld($resourceId, $resourceType, $date);
        $remaining = max(0, (int)$row['capacity'] - (int)$row['reserved'] - (int)$row['blocked'] - $held);
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
        $type = sanitize_key($resourceType);
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE {$table}
             SET reserved = reserved + %d
             WHERE resource_type=%s AND resource_id=%d AND service_date=%s
             AND status='open' AND (capacity - reserved - blocked) >= %d",
            $quantity, $type, $resourceId, sanitize_text_field($date), $quantity
        ));
        if ($result === 1) {
            $this->flush($resourceId, $resourceType, $date);
            return true;
        }

        // No row was updated. If the row exists, capacity is exhausted.
        if ($this->get($resourceId, $resourceType, $date)) {
            return false;
        }

        // Never create capacity implicitly during a booking confirmation.
        // Capacity must be configured explicitly by operations.
        return false;
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
        if ($result !== false) {
            $this->flush($resourceId, $resourceType, $date);
        }
        return $result !== false;
    }

    private function activeHeld(int $resourceId, string $resourceType, string $date): int
    {
        global $wpdb;
        $table = Schema::tables()['holds'];
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(quantity), 0) FROM {$table} WHERE resource_type=%s AND resource_id=%d AND service_date=%s AND status='active' AND expires_at > UTC_TIMESTAMP()",
            sanitize_key($resourceType), $resourceId, sanitize_text_field($date)
        ));
    }

    private function cacheKey(int $resourceId, string $resourceType, string $date): string
    {
        return 'avail_' . sanitize_key($resourceType) . '_' . $resourceId . '_' . sanitize_text_field($date);
    }

    private function flush(int $resourceId, string $resourceType, string $date): void
    {
        wp_cache_delete($this->cacheKey($resourceId, $resourceType, $date), 'pwt_availability');
    }
}
