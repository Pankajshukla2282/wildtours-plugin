<?php
declare(strict_types=1);
namespace PWT\Performance;
defined('ABSPATH') || exit;

/**
 * Lightweight query telemetry for PWT admin screens.
 *
 * Requires SAVEQUERIES to be enabled. Slow queries and heavy page loads are
 * reported through the pwt/log action (persisted to the audit trail).
 */
final class PerformanceMonitor
{
    public function register(): void
    {
        add_action('shutdown', [$this, 'report'], 5);
    }

    public function report(): void
    {
        if (!is_admin() || !defined('SAVEQUERIES') || !SAVEQUERIES) {
            return;
        }

        $page = (string) ($_GET['page'] ?? '');
        if (strpos($page, 'pwt-') !== 0) {
            return;
        }

        global $wpdb;
        $queries = is_array($wpdb->queries ?? null) ? $wpdb->queries : [];
        if (!$queries) {
            return;
        }

        $totalTime = 0.0;
        $slowest = null;
        $slowestTime = 0.0;

        foreach ($queries as $q) {
            $time = (float) ($q[1] ?? 0);
            $totalTime += $time;
            if ($time > $slowestTime) {
                $slowestTime = $time;
                $slowest = (string) ($q[0] ?? '');
            }
        }

        $threshold = (float) (get_option('pwt_settings', [])['performance_slow_query_ms'] ?? 1000);

        if ($slowest && $slowestTime * 1000 > $threshold) {
            do_action('pwt/log', 'warning', 'Slow query on ' . $page, [
                'entity_type' => 'performance',
                'query_ms' => round($slowestTime * 1000, 1),
                'sql' => $slowest,
            ]);
        }

        if (count($queries) >= 100) {
            do_action('pwt/log', 'warning', 'High query count on ' . $page, [
                'entity_type' => 'performance',
                'query_count' => count($queries),
                'total_ms' => round($totalTime * 1000, 1),
            ]);
        }
    }
}