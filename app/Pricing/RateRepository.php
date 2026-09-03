<?php
declare(strict_types=1);
namespace PWT\Pricing;
defined('ABSPATH') || exit;
use PWT\Cache\Cache;

final class RateRepository
{
    private Cache $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function find(int $resourceId, string $resourceType, string $date): array
    {
        $transientKey = 'pwt_rates_' . $resourceId . '_' . $resourceType . '_' . $date;
        $cached = $this->cache->remember($transientKey, HOUR_IN_SECONDS, function () use ($resourceId, $resourceType, $date) {
            global $wpdb;
            $today = sanitize_text_field($date);
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM " . Schema::tables()['rates'] .
                " WHERE resource_type=%s AND resource_id=%d AND status='active'
                  AND (start_date IS NULL OR start_date <= %s)
                  AND (end_date IS NULL OR end_date >= %s)
                  ORDER BY priority ASC, id DESC",
                sanitize_key($resourceType), $resourceId, $today, $today
            ), ARRAY_A) ?: [];
        });

        return $cached;
    }
}
