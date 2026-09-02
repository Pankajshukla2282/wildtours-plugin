<?php
declare(strict_types=1);
namespace PWT\Vendors;
defined('ABSPATH') || exit;

use PWT\Core\Database\Schema;
use PWT\Logging\AuditLog;

final class VendorRepository
{
    public function __construct(private readonly AuditLog $audit)
    {
    }

    public function create(array $data): int
    {
        global $wpdb;
        $wpdb->insert(Schema::tables()['vendors'], [
            'name' => sanitize_text_field((string)($data['name'] ?? '')),
            'vendor_type' => sanitize_key((string)($data['vendor_type'] ?? 'other')),
            'contact_person' => sanitize_text_field((string)($data['contact_person'] ?? '')) ?: null,
            'email' => sanitize_email((string)($data['email'] ?? '')) ?: null,
            'phone' => sanitize_text_field((string)($data['phone'] ?? '')) ?: null,
            'pan' => strtoupper(sanitize_text_field((string)($data['pan'] ?? ''))) ?: null,
            'gstin' => strtoupper(sanitize_text_field((string)($data['gstin'] ?? ''))) ?: null,
            'bank_details' => sanitize_textarea_field((string)($data['bank_details'] ?? '')) ?: null,
            'notes' => sanitize_textarea_field((string)($data['notes'] ?? '')) ?: null,
            'status' => sanitize_key((string)($data['status'] ?? 'active')),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ]);
        $id = (int)$wpdb->insert_id;

        if ($id) {
            $this->audit->record('vendor', $id, 'vendor.created', [
                'to' => [
                    'name' => sanitize_text_field((string)($data['name'] ?? '')),
                    'vendor_type' => sanitize_key((string)($data['vendor_type'] ?? 'other')),
                ],
            ]);
        }

        return $id;
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;
        $fields = [
            'name' => sanitize_text_field((string)($data['name'] ?? '')),
            'vendor_type' => sanitize_key((string)($data['vendor_type'] ?? 'other')),
            'contact_person' => sanitize_text_field((string)($data['contact_person'] ?? '')) ?: null,
            'email' => sanitize_email((string)($data['email'] ?? '')) ?: null,
            'phone' => sanitize_text_field((string)($data['phone'] ?? '')) ?: null,
            'pan' => strtoupper(sanitize_text_field((string)($data['pan'] ?? ''))) ?: null,
            'gstin' => strtoupper(sanitize_text_field((string)($data['gstin'] ?? ''))) ?: null,
            'bank_details' => sanitize_textarea_field((string)($data['bank_details'] ?? '')) ?: null,
            'notes' => sanitize_textarea_field((string)($data['notes'] ?? '')) ?: null,
            'status' => sanitize_key((string)($data['status'] ?? 'active')),
            'updated_at' => current_time('mysql'),
        ];

        $updated = false !== $wpdb->update(
            Schema::tables()['vendors'],
            $fields,
            ['id' => $id],
            ['%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s'],
            ['%d']
        );

        if ($updated) {
            $this->audit->record('vendor', $id, 'vendor.updated', [
                'to' => ['name' => $fields['name'], 'status' => $fields['status']],
            ]);
        }

        return $updated;
    }

    public function find(int $id): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . Schema::tables()['vendors'] . " WHERE id=%d", $id
        ), ARRAY_A);
        return $row ?: null;
    }

    public function all(string $status = ''): array
    {
        global $wpdb;
        $table = Schema::tables()['vendors'];
        if ($status !== '') {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table} WHERE status=%s ORDER BY name ASC", sanitize_key($status)
            ), ARRAY_A) ?: [];
        }
        return $wpdb->get_results("SELECT * FROM {$table} ORDER BY name ASC", ARRAY_A) ?: [];
    }
}