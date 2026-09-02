<?php
declare(strict_types=1);
namespace PWT\Vendors;
defined('ABSPATH') || exit;

use PWT\Core\Database\Schema;

final class VendorRateRepository
{
    public function create(array $data): int
    {
        global $wpdb;
        $wpdb->insert(Schema::tables()['vendor_rates'], [
            'vendor_id' => absint($data['vendor_id'] ?? 0),
            'resource_type' => sanitize_key((string)($data['resource_type'] ?? 'service')),
            'resource_id' => absint($data['resource_id'] ?? 0),
            'rate_name' => sanitize_text_field((string)($data['rate_name'] ?? '')) ?: null,
            'unit_price' => max(0, (float)($data['unit_price'] ?? 0)),
            'currency' => strtoupper(sanitize_text_field((string)($data['currency'] ?? 'INR'))),
            'start_date' => sanitize_text_field((string)($data['start_date'] ?? '')) ?: null,
            'end_date' => sanitize_text_field((string)($data['end_date'] ?? '')) ?: null,
            'priority' => (int)($data['priority'] ?? 10),
            'notes' => sanitize_textarea_field((string)($data['notes'] ?? '')) ?: null,
            'status' => sanitize_key((string)($data['status'] ?? 'active')),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ]);
        return (int)$wpdb->insert_id;
    }

    public function delete(int $id): bool
    {
        global $wpdb;
        return false !== $wpdb->delete(Schema::tables()['vendor_rates'], ['id' => $id], ['%d']);
    }

    public function forVendor(int $vendorId): array
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . Schema::tables()['vendor_rates'] . "
             WHERE vendor_id=%d
             ORDER BY resource_type ASC, resource_id ASC, priority ASC, id ASC",
            $vendorId
        ), ARRAY_A) ?: [];
    }

    /**
     * Best active rate card for a sellable resource on a given date.
     */
    public function matching(string $resourceType, int $resourceId, string $date): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . Schema::tables()['vendor_rates'] . "
             WHERE status='active'
               AND resource_type=%s AND resource_id=%d
               AND (start_date IS NULL OR start_date <= %s)
               AND (end_date IS NULL OR end_date >= %s)
             ORDER BY priority ASC, id ASC LIMIT 1",
            sanitize_key($resourceType),
            $resourceId,
            sanitize_text_field($date),
            sanitize_text_field($date)
        ), ARRAY_A);
        return $row ?: null;
    }
}