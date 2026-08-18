<?php
declare(strict_types=1);
namespace PWT\Vendors;
defined('ABSPATH') || exit;

use PWT\Core\Database\Schema;

final class SettlementRepository
{
    public function create(array $data): int
    {
        global $wpdb;
        $wpdb->insert(Schema::tables()['settlements'], [
            'vendor_id' => absint($data['vendor_id'] ?? 0),
            'booking_id' => absint($data['booking_id'] ?? 0) ?: null,
            'amount' => max(0, (float)($data['amount'] ?? 0)),
            'currency' => strtoupper(sanitize_text_field((string)($data['currency'] ?? 'INR'))),
            'reference' => sanitize_text_field((string)($data['reference'] ?? '')) ?: null,
            'settled_at' => sanitize_text_field((string)($data['settled_at'] ?? '')) ?: null,
            'notes' => sanitize_textarea_field((string)($data['notes'] ?? '')) ?: null,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ]);
        return (int)$wpdb->insert_id;
    }

    public function forVendor(int $vendorId): array
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . Schema::tables()['settlements'] . "
             WHERE vendor_id=%d ORDER BY settled_at DESC, id DESC",
            $vendorId
        ), ARRAY_A) ?: [];
    }

    /**
     * Amount already settled (paid out) to the vendor.
     */
    public function settledTotal(int $vendorId): float
    {
        global $wpdb;
        return (float)$wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount),0) FROM " . Schema::tables()['settlements'] . " WHERE vendor_id=%d",
            $vendorId
        ));
    }
}