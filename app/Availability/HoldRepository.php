<?php

declare(strict_types=1);

namespace PWT\Availability;

defined('ABSPATH') || exit;

use PWT\Core\Database\Schema;

final class HoldRepository
{
    private string $table;

    public function __construct()
    {
        $this->table = Schema::tables()['holds'];
    }

    public function create(
        int $bookingId,
        int $resourceId,
        string $resourceType,
        string $serviceDate,
        int $quantity,
        int $ttlSeconds = 900
    ): int {
        global $wpdb;

        $now = current_time(
            'mysql',
            true
        );

        $expires = gmdate(
            'Y-m-d H:i:s',
            time() + max(60, $ttlSeconds)
        );

        $result = $wpdb->insert(
            $this->table,
            [
                'booking_id' => $bookingId,
                'resource_id' => $resourceId,
                'resource_type' => sanitize_key(
                    $resourceType
                ),
                'service_date' => sanitize_text_field(
                    $serviceDate
                ),
                'quantity' => max(
                    1,
                    $quantity
                ),
                'status' => 'active',
                'expires_at' => $expires,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        if ($result === false) {
            return 0;
        }

        return (int) $wpdb->insert_id;
    }

    public function activeForBooking(
        int $bookingId
    ): array {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                FROM {$this->table}
                WHERE booking_id = %d
                  AND status = 'active'
                  AND expires_at > %s",
                $bookingId,
                current_time('mysql', true)
            ),
            ARRAY_A
        ) ?: [];
    }

    public function activeExpired(): array
    {
        global $wpdb;

        $now = current_time(
            'mysql',
            true
        );

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                FROM {$this->table}
                WHERE status = 'active'
                  AND expires_at <= %s",
                $now
            ),
            ARRAY_A
        ) ?: [];
    }

    public function expire(): int
    {
        global $wpdb;

        $now = current_time(
            'mysql',
            true
        );

        return (int) $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->table}
                SET
                    status = 'expired',
                    updated_at = %s
                WHERE status = 'active'
                  AND expires_at <= %s",
                $now,
                $now
            )
        );
    }

    public function expireIds(
        array $ids
    ): int {
        $ids = array_values(
            array_filter(
                array_map(
                    'absint',
                    $ids
                )
            )
        );

        if (!$ids) {
            return 0;
        }

        global $wpdb;

        $placeholders = implode(
            ',',
            array_fill(
                0,
                count($ids),
                '%d'
            )
        );

        $arguments = array_merge(
            [
                current_time(
                    'mysql',
                    true
                ),
            ],
            $ids
        );

        return (int) $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->table}
                SET
                    status = 'expired',
                    updated_at = %s
                WHERE id IN ({$placeholders})",
                $arguments
            )
        );
    }

    public function releaseBooking(
        int $bookingId,
        string $status = 'released'
    ): int {
        global $wpdb;

        return (int) $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->table}
                SET
                    status = %s,
                    updated_at = %s
                WHERE booking_id = %d
                  AND status = 'active'",
                sanitize_key($status),
                current_time(
                    'mysql',
                    true
                ),
                $bookingId
            )
        );
    }

    public function confirmBooking(
        int $bookingId
    ): int {
        return $this->releaseBooking(
            $bookingId,
            'confirmed'
        );
    }
}