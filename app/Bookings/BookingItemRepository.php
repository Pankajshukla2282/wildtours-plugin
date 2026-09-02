<?php

declare(strict_types=1);

namespace PWT\Bookings;

defined('ABSPATH') || exit;

use PWT\Core\Database\Schema;

final class BookingItemRepository
{
    private string $table;

    public function __construct()
    {
        $this->table = Schema::tables()['items'];
    }

    public function create(
        array $data
    ): int {
        global $wpdb;

        $bookingId = absint(
            $data['booking_id'] ?? 0
        );

        $itemType = sanitize_key(
            (string) (
                $data['item_type'] ?? ''
            )
        );

        $objectId = absint(
            $data['object_id']
            ?? $data['item_id']
            ?? 0
        );

        $quantity = max(
            1,
            (int) (
                $data['quantity'] ?? 1
            )
        );

        $startDate = $this->normalizeDate(
            (string) (
                $data['start_date']
                ?? $data['service_date']
                ?? ''
            )
        );

        $endDate = $this->normalizeDate(
            (string) (
                $data['end_date']
                ?? $data['service_date']
                ?? $startDate
            )
        );

        if (
            !$bookingId
            || $itemType === ''
            || !$objectId
            || $startDate === ''
        ) {
            return 0;
        }

        if ($endDate === '') {
            $endDate = $startDate;
        }

        if ($endDate < $startDate) {
            return 0;
        }

        $unitPrice = max(
            0,
            round(
                (float) (
                    $data['unit_price'] ?? 0
                ),
                2
            )
        );

        /*
         * Prefer an explicitly supplied total/subtotal.
         *
         * This is important because package pricing or discounts may
         * produce a line total that is not simply unit_price × quantity.
         */
        $total = isset($data['total'])
            ? (float) $data['total']
            : (
                isset($data['subtotal'])
                    ? (float) $data['subtotal']
                    : $unitPrice * $quantity
            );

        $total = max(
            0,
            round($total, 2)
        );

        $cost = max(
            0,
            round(
                (float) (
                    $data['cost'] ?? 0
                ),
                2
            )
        );

        $vendorId = absint(
            $data['vendor_id'] ?? 0
        );

        $vendorName = sanitize_text_field(
            (string) (
                $data['vendor_name'] ?? ''
            )
        );

        $name = sanitize_text_field(
            (string) (
                $data['name']
                ?? $this->resourceName(
                    $objectId,
                    $itemType
                )
            )
        );

        if ($name === '') {
            $name = __('Service', 'wildtours-plugin');
        }

        $meta = $this->sanitizeMeta(
            $data['meta'] ?? []
        );

        $result = $wpdb->insert(
            $this->table,
            [
                'booking_id' => $bookingId,
                'item_type' => $itemType,
                'object_id' => $objectId,
                'name' => $name,
                'quantity' => $quantity,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'unit_price' => $unitPrice,
                'total' => $total,
                'cost' => $cost,
                'vendor_id' => $vendorId ?: null,
                'vendor_name' => $vendorName ?: null,
                'meta' => wp_json_encode($meta),
                'created_at' => current_time(
                    'mysql',
                    true
                ),
            ]
        );

        if ($result === false) {
            return 0;
        }

        return (int) $wpdb->insert_id;
    }

    public function find(
        int $itemId
    ): ?array {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                FROM {$this->table}
                WHERE id = %d
                LIMIT 1",
                $itemId
            ),
            ARRAY_A
        );

        return is_array($row)
            ? $this->hydrate($row)
            : null;
    }

    public function byBooking(
        int $bookingId
    ): array {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                FROM {$this->table}
                WHERE booking_id = %d
                ORDER BY id ASC",
                $bookingId
            ),
            ARRAY_A
        );

        if (!is_array($rows)) {
            return [];
        }

        return array_map(
            fn(array $row): array => $this->hydrate($row),
            $rows
        );
    }

    public function update(
        int $itemId,
        array $data
    ): bool {
        $existing = $this->find($itemId);

        if (!$existing) {
            return false;
        }

        $payload = [];

        if (array_key_exists('item_type', $data)) {
            $payload['item_type'] = sanitize_key(
                (string) $data['item_type']
            );
        }

        if (
            array_key_exists('object_id', $data)
            || array_key_exists('item_id', $data)
        ) {
            $payload['object_id'] = absint(
                $data['object_id']
                ?? $data['item_id']
                ?? 0
            );
        }

        if (array_key_exists('name', $data)) {
            $payload['name'] = sanitize_text_field(
                (string) $data['name']
            );
        }

        if (array_key_exists('quantity', $data)) {
            $payload['quantity'] = max(
                1,
                (int) $data['quantity']
            );
        }

        if (
            array_key_exists('start_date', $data)
            || array_key_exists('service_date', $data)
        ) {
            $payload['start_date'] = $this->normalizeDate(
                (string) (
                    $data['start_date']
                    ?? $data['service_date']
                    ?? ''
                )
            );
        }

        if (
            array_key_exists('end_date', $data)
            || array_key_exists('service_date', $data)
        ) {
            $payload['end_date'] = $this->normalizeDate(
                (string) (
                    $data['end_date']
                    ?? $data['service_date']
                    ?? ''
                )
            );
        }

        $finalStart = (string) (
            $payload['start_date']
            ?? $existing['start_date']
            ?? ''
        );

        $finalEnd = (string) (
            $payload['end_date']
            ?? $existing['end_date']
            ?? $finalStart
        );

        if (
            $finalStart === ''
            || $finalEnd === ''
            || $finalEnd < $finalStart
        ) {
            return false;
        }

        if (array_key_exists('unit_price', $data)) {
            $payload['unit_price'] = max(
                0,
                round(
                    (float) $data['unit_price'],
                    2
                )
            );
        }

        if (
            array_key_exists('total', $data)
            || array_key_exists('subtotal', $data)
        ) {
            $payload['total'] = max(
                0,
                round(
                    (float) (
                        $data['total']
                        ?? $data['subtotal']
                    ),
                    2
                )
            );
        }

        if (array_key_exists('cost', $data)) {
            $payload['cost'] = max(
                0,
                round(
                    (float) $data['cost'],
                    2
                )
            );
        }

        if (array_key_exists('vendor_id', $data)) {
            $vendorId = absint(
                $data['vendor_id']
            );

            $payload['vendor_id'] = $vendorId ?: null;
        }

        if (array_key_exists('vendor_name', $data)) {
            $vendorName = sanitize_text_field(
                (string) $data['vendor_name']
            );

            $payload['vendor_name'] = $vendorName ?: null;
        }

        if (array_key_exists('meta', $data)) {
            $payload['meta'] = wp_json_encode(
                $this->sanitizeMeta(
                    $data['meta']
                )
            );
        }

        if (!$payload) {
            return true;
        }

        global $wpdb;

        $result = $wpdb->update(
            $this->table,
            $payload,
            [
                'id' => $itemId,
            ]
        );

        return $result !== false;
    }

    public function delete(
        int $itemId
    ): bool {
        global $wpdb;

        $result = $wpdb->delete(
            $this->table,
            [
                'id' => $itemId,
            ]
        );

        return $result !== false;
    }

    public function deleteByBooking(
        int $bookingId
    ): int {
        global $wpdb;

        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table}
                WHERE booking_id = %d",
                $bookingId
            )
        );

        return $result === false
            ? 0
            : (int) $result;
    }

    public function totalsForBooking(
        int $bookingId
    ): array {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    COALESCE(SUM(total), 0) AS total,
                    COALESCE(SUM(cost), 0) AS cost,
                    COALESCE(SUM(total - cost), 0) AS gross_profit
                FROM {$this->table}
                WHERE booking_id = %d",
                $bookingId
            ),
            ARRAY_A
        );

        return [
            'total' => round(
                (float) (
                    $row['total'] ?? 0
                ),
                2
            ),
            'cost' => round(
                (float) (
                    $row['cost'] ?? 0
                ),
                2
            ),
            'gross_profit' => round(
                (float) (
                    $row['gross_profit'] ?? 0
                ),
                2
            ),
        ];
    }

    public function byVendor(
        int $vendorId,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        global $wpdb;

        $where = [
            'vendor_id = %d',
        ];

        $values = [
            $vendorId,
        ];

        if ($startDate !== null) {
            $where[] = 'start_date >= %s';
            $values[] = $this->normalizeDate(
                $startDate
            );
        }

        if ($endDate !== null) {
            $where[] = 'start_date <= %s';
            $values[] = $this->normalizeDate(
                $endDate
            );
        }

        $sql = sprintf(
            "SELECT *
            FROM {$this->table}
            WHERE %s
            ORDER BY start_date ASC, id ASC",
            implode(
                ' AND ',
                $where
            )
        );

        $prepared = $wpdb->prepare(
            $sql,
            $values
        );

        $rows = $wpdb->get_results(
            $prepared,
            ARRAY_A
        );

        if (!is_array($rows)) {
            return [];
        }

        return array_map(
            fn(array $row): array => $this->hydrate($row),
            $rows
        );
    }

    private function hydrate(
        array $row
    ): array {
        $meta = [];

        if (!empty($row['meta'])) {
            $decoded = json_decode(
                (string) $row['meta'],
                true
            );

            if (is_array($decoded)) {
                $meta = $decoded;
            }
        }

        $row['id'] = absint(
            $row['id'] ?? 0
        );

        $row['booking_id'] = absint(
            $row['booking_id'] ?? 0
        );

        $row['object_id'] = absint(
            $row['object_id'] ?? 0
        );

        /*
         * Compatibility alias.
         *
         * Existing booking and package code may still reference item_id.
         */
        $row['item_id'] = $row['object_id'];

        $row['quantity'] = max(
            1,
            (int) (
                $row['quantity'] ?? 1
            )
        );

        $row['unit_price'] = round(
            (float) (
                $row['unit_price'] ?? 0
            ),
            2
        );

        $row['total'] = round(
            (float) (
                $row['total'] ?? 0
            ),
            2
        );

        /*
         * Compatibility alias for older code.
         */
        $row['subtotal'] = $row['total'];

        $row['cost'] = round(
            (float) (
                $row['cost'] ?? 0
            ),
            2
        );

        $row['vendor_id'] = absint(
            $row['vendor_id'] ?? 0
        );

        $row['gross_profit'] = round(
            $row['total'] - $row['cost'],
            2
        );

        $row['meta'] = $meta;

        return $row;
    }

    private function normalizeDate(
        string $date
    ): string {
        $date = sanitize_text_field($date);

        if ($date === '') {
            return '';
        }

        $timestamp = strtotime($date);

        if ($timestamp === false) {
            return '';
        }

        return wp_date(
            'Y-m-d',
            $timestamp
        );
    }

    private function sanitizeMeta(
        mixed $meta
    ): array {
        if (!is_array($meta)) {
            return [];
        }

        return $this->sanitizeMetaValue(
            $meta
        );
    }

    private function sanitizeMetaValue(
        mixed $value
    ): mixed {
        if (is_array($value)) {
            $clean = [];

            foreach ($value as $key => $item) {
                $clean[
                    sanitize_key(
                        (string) $key
                    )
                ] = $this->sanitizeMetaValue(
                    $item
                );
            }

            return $clean;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return $value;
        }

        if ($value === null) {
            return null;
        }

        return sanitize_text_field(
            (string) $value
        );
    }

    private function resourceName(
        int $objectId,
        string $itemType
    ): string {
        $title = get_the_title($objectId);

        if (is_string($title) && $title !== '') {
            return $title;
        }

        return ucwords(
            str_replace(
                [
                    '-',
                    '_',
                ],
                ' ',
                $itemType
            )
        );
    }
}