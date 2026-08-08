<?php
declare(strict_types=1);
namespace PWT\Pricing;
defined('ABSPATH') || exit;
use PWT\Core\Database\Schema;

final class RateManager
{
    public function __construct(private readonly SeasonResolver $seasons) {}

    public function save(array $data): int
    {
        global $wpdb;
        $table = Schema::tables()['rates'];
        $id = absint($data['id'] ?? 0);
        $row = [
            'resource_type' => sanitize_key((string)($data['resource_type'] ?? '')),
            'resource_id' => absint($data['resource_id'] ?? 0),
            'season_id' => absint($data['season_id'] ?? 0) ?: null,
            'start_date' => sanitize_text_field((string)($data['start_date'] ?? '')) ?: null,
            'end_date' => sanitize_text_field((string)($data['end_date'] ?? '')) ?: null,
            'rate_type' => sanitize_key((string)($data['rate_type'] ?? 'base')),
            'amount' => (float)($data['amount'] ?? 0),
            'currency' => strtoupper(sanitize_text_field((string)($data['currency'] ?? 'INR'))),
            'min_quantity' => max(1, absint($data['min_quantity'] ?? 1)),
            'max_quantity' => absint($data['max_quantity'] ?? 0) ?: null,
            'priority' => (int)($data['priority'] ?? 10),
            'status' => sanitize_key((string)($data['status'] ?? 'active')),
            'updated_at' => current_time('mysql'),
        ];
        if ($id) {
            $wpdb->update($table, $row, ['id'=>$id]);
            return $id;
        }
        $row['created_at'] = current_time('mysql');
        $wpdb->insert($table, $row);
        return (int)$wpdb->insert_id;
    }

    public function delete(int $id): bool
    {
        global $wpdb;
        return false !== $wpdb->delete(Schema::tables()['rates'], ['id'=>$id], ['%d']);
    }

    public function all(int $limit = 100): array
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . Schema::tables()['rates'] . " ORDER BY priority ASC, id DESC LIMIT %d", $limit
        ), ARRAY_A) ?: [];
    }
}
