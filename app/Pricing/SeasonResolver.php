<?php
declare(strict_types=1);
namespace PWT\Pricing;
defined('ABSPATH') || exit;

final class SeasonResolver
{
    public function resolve(string $date): array
    {
        $transientKey = 'pwt_season_resolver_' . md5($date);
        $cached = get_transient($transientKey);
        if ($cached !== false) {
            return $cached;
        }

        $terms = get_terms([
            'taxonomy' => 'pwt_season',
            'hide_empty' => false,
        ]);
        if (is_wp_error($terms)) {
            return [];
        }

        $timestamp = strtotime($date);
        if (!$timestamp) {
            set_transient($transientKey, [], HOUR_IN_SECONDS);
            return [];
        }

        foreach ($terms as $term) {
            $start = get_term_meta($term->term_id, '_pwt_season_start', true);
            $end = get_term_meta($term->term_id, '_pwt_season_end', true);
            if ($start && $end) {
                $startTs = strtotime($start . '-01');
                $endTs = strtotime($end . '-01 +1 month -1 day');
                if ($startTs && $endTs && $timestamp >= $startTs && $timestamp <= $endTs) {
                    $result = ['id'=>(int)$term->term_id,'name'=>$term->name,'slug'=>$term->slug];
                    set_transient($transientKey, $result, HOUR_IN_SECONDS);
                    return $result;
                }
            }
        }
        set_transient($transientKey, [], HOUR_IN_SECONDS);
        return [];
    }
}
