<?php

declare(strict_types=1);

namespace PWT\Availability;

defined('ABSPATH') || exit;

use PWT\Core\Database\Schema;

final class AvailabilityRepository
{
    public function set(
        int $resourceId,
        string $resourceType,
        string $date,
        int $capacity,
        int $reserved = 0,
        int $blocked = 0,
        string $status = 'open'
    ): bool {
        global $wpdb;

        $table = Schema::tables()['availability'];

        $result = $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$table}
                (
                    resource_type,
                    resource_id,
                    service_date,
                    capacity,
                    reserved,
                    blocked,
                    status
                )
                VALUES (%s, %d, %s, %d, %d, %d, %s)
                ON DUPLICATE KEY UPDATE
                    capacity = VALUES(capacity),
                    reserved = VALUES(reserved),
                    blocked = VALUES(blocked),
                    status = VALUES(status)",
                sanitize_key($resourceType),
                $resourceId,
                sanitize_text_field($date),
                max(0, $capacity),
                max(0, $reserved),
                max(0, $blocked),
                sanitize_key($status)
            )
        );

        if ($result !== false) {
            $this->flush(
                $resourceId,
                $resourceType,
                $date
            );
        }

        return $result !== false;
    }

    public function get(
        int $resourceId,
        string $resourceType,
        string $date
    ): array {
        $key = $this->cacheKey(
            $resourceId,
            $resourceType,
            $date
        );

        $cached = wp_cache_get(
            $key,
            'pwt_availability'
        );

        if (is_array($cached)) {
            return $cached;
        }

        global $wpdb;

        $table = Schema::tables()['availability'];

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                FROM {$table}
                WHERE resource_type = %s
                  AND resource_id = %d
                  AND service_date = %s
                LIMIT 1",
                sanitize_key($resourceType),
                $resourceId,
                sanitize_text_field($date)
            ),
            ARRAY_A
        );

        $row = is_array($row)
            ? $row
            : [];

        wp_cache_set(
            $key,
            $row,
            'pwt_availability',
            60
        );

        return $row;
    }

    public function check(
        int $resourceId,
        string $resourceType,
        string $date,
        int $quantity = 1
    ): array {
        $row = $this->get(
            $resourceId,
            $resourceType,
            $date
        );

        if (!$row) {
            return [
                'available' => false,
                'remaining' => 0,
                'capacity' => 0,
                'reserved' => 0,
                'blocked' => 0,
                'held' => 0,
                'reason' => 'inventory_not_configured',
            ];
        }

        if (($row['status'] ?? 'open') !== 'open') {
            return [
                'available' => false,
                'remaining' => 0,
                'capacity' => (int) ($row['capacity'] ?? 0),
                'reserved' => (int) ($row['reserved'] ?? 0),
                'blocked' => (int) ($row['blocked'] ?? 0),
                'held' => 0,
                'reason' => 'inventory_closed',
            ];
        }

        $held = $this->activeHeld(
            $resourceId,
            $resourceType,
            $date
        );

        $capacity = max(
            0,
            (int) $row['capacity']
        );

        $reserved = max(
            0,
            (int) $row['reserved']
        );

        $blocked = max(
            0,
            (int) $row['blocked']
        );

        $remaining = max(
            0,
            $capacity
            - $reserved
            - $blocked
            - $held
        );

        return [
            'available' => $remaining >= max(1, $quantity),
            'remaining' => $remaining,
            'capacity' => $capacity,
            'reserved' => $reserved,
            'blocked' => $blocked,
            'held' => $held,
        ];
    }

    public function reserve(
        int $resourceId,
        string $resourceType,
        string $date,
        int $quantity = 1
    ): bool {
        global $wpdb;

        $table = Schema::tables()['availability'];

        $quantity = max(1, $quantity);
        $type = sanitize_key($resourceType);
        $date = sanitize_text_field($date);

        /*
         * During confirmation the booking's own active holds are
         * released first by BookingAllocationService.
         *
         * Other active holds must still count against capacity.
         */
        $holdsTable = Schema::tables()['holds'];

        $now = current_time(
            'mysql',
            true
        );

        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table}
                SET reserved = reserved + %d
                WHERE resource_type = %s
                  AND resource_id = %d
                  AND service_date = %s
                  AND status = 'open'
                  AND (
                      capacity
                      - reserved
                      - blocked
                      - (
                          SELECT COALESCE(
                              SUM(quantity),
                              0
                          )
                          FROM {$holdsTable}
                          WHERE resource_type = %s
                            AND resource_id = %d
                            AND service_date = %s
                            AND status = 'active'
                            AND expires_at > %s
                      )
                  ) >= %d",
                $quantity,
                $type,
                $resourceId,
                $date,
                $type,
                $resourceId,
                $date,
                $now,
                $quantity
            )
        );

        if ($result === 1) {
            $this->flush(
                $resourceId,
                $resourceType,
                $date
            );

            return true;
        }

        return false;
    }

    public function release(
        int $resourceId,
        string $resourceType,
        string $date,
        int $quantity = 1
    ): bool {
        global $wpdb;

        $table = Schema::tables()['availability'];

        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table}
                SET reserved = GREATEST(
                    0,
                    reserved - %d
                )
                WHERE resource_type = %s
                  AND resource_id = %d
                  AND service_date = %s",
                max(1, $quantity),
                sanitize_key($resourceType),
                $resourceId,
                sanitize_text_field($date)
            )
        );

        if ($result !== false) {
            $this->flush(
                $resourceId,
                $resourceType,
                $date
            );
        }

        return $result !== false;
    }

    public function flush(
        int $resourceId,
        string $resourceType,
        string $date
    ): void {
        wp_cache_delete(
            $this->cacheKey(
                $resourceId,
                $resourceType,
                $date
            ),
            'pwt_availability'
        );
    }

    private function activeHeld(
        int $resourceId,
        string $resourceType,
        string $date
    ): int {
        global $wpdb;

        $table = Schema::tables()['holds'];

        $now = current_time(
            'mysql',
            true
        );

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(
                    SUM(quantity),
                    0
                )
                FROM {$table}
                WHERE resource_type = %s
                  AND resource_id = %d
                  AND service_date = %s
                  AND status = 'active'
                  AND expires_at > %s",
                sanitize_key($resourceType),
                $resourceId,
                sanitize_text_field($date),
                $now
            )
        );
    }

    private function cacheKey(
        int $resourceId,
        string $resourceType,
        string $date
    ): string {
        return sprintf(
            'avail_%s_%d_%s',
            sanitize_key($resourceType),
            $resourceId,
            sanitize_text_field($date)
        );
    }
}