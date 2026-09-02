<?php
declare(strict_types=1);
namespace PWT\Pricing;
defined('ABSPATH') || exit;
use PWT\Core\Database\Schema;

final class RateRepository
{
    public function __construct(private readonly SeasonResolver $seasons)
    {
    }

    public function find(int $resourceId, string $resourceType, string $date): array
    {
        global $wpdb;
        $table = Schema::tables()['rates'];
        $season = $this->seasons->resolve($date);
        $seasonId = (int)($season['id'] ?? 0);

        $sql = "SELECT * FROM {$table}
                WHERE resource_type=%s AND resource_id=%d AND status='active'
                  AND (start_date IS NULL OR start_date <= %s)
                  AND (end_date IS NULL OR end_date >= %s)";
        $args = [sanitize_key($resourceType), $resourceId, sanitize_text_field($date), sanitize_text_field($date)];

        if ($seasonId) {
            $sql .= " AND (season_id IS NULL OR season_id = %d)";
            $args[] = $seasonId;
        } else {
            $sql .= " AND season_id IS NULL";
        }

        $sql .= " ORDER BY priority ASC, id DESC";

        return $wpdb->get_results($wpdb->prepare($sql, $args), ARRAY_A) ?: [];
    }
}