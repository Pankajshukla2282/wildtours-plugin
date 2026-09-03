<?php
declare(strict_types=1);
namespace PWT\Reporting;
defined('ABSPATH') || exit;

use PWT\Core\Database\Schema;

final class ReportService
{
    public function summary(string $from, string $to): array
    {
        global $wpdb;
        $b = Schema::tables()['bookings'];
        $p = Schema::tables()['payments'];
        $c = Schema::tables()['customers'];

        $from = sanitize_text_field($from);
        $to = sanitize_text_field($to);

        return [
            'bookings' => (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$b} WHERE created_at BETWEEN %s AND %s", $from.' 00:00:00', $to.' 23:59:59'
            )),
            'confirmed_bookings' => (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$b} WHERE status IN ('confirmed','paid') AND created_at BETWEEN %s AND %s", $from.' 00:00:00', $to.' 23:59:59'
            )),
            'cancelled_bookings' => (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$b} WHERE status='cancelled' AND created_at BETWEEN %s AND %s", $from.' 00:00:00', $to.' 23:59:59'
            )),
            'gross_booking_value' => (float)$wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(total),0) FROM {$b} WHERE created_at BETWEEN %s AND %s", $from.' 00:00:00', $to.' 23:59:59'
            )),
            'payments_received' => (float)$wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(amount),0) FROM {$p} WHERE status='paid' AND transaction_type='payment' AND created_at BETWEEN %s AND %s", $from.' 00:00:00', $to.' 23:59:59'
            )),
            'refunds' => (float)$wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(amount),0) FROM {$p} WHERE status='paid' AND transaction_type='refund' AND created_at BETWEEN %s AND %s", $from.' 00:00:00', $to.' 23:59:59'
            )),
            'customers' => (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$c} WHERE created_at BETWEEN %s AND %s", $from.' 00:00:00', $to.' 23:59:59'
            )),
        ];
    }

    public function bookingsByStatus(string $from, string $to): array
    {
        global $wpdb;
        $table = Schema::tables()['bookings'];
        return $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) AS bookings, COALESCE(SUM(total),0) AS value
             FROM {$table}
             WHERE created_at BETWEEN %s AND %s
             GROUP BY status ORDER BY bookings DESC",
            sanitize_text_field($from).' 00:00:00',
            sanitize_text_field($to).' 23:59:59'
        ), ARRAY_A) ?: [];
    }

    public function topServices(string $from, string $to): array
    {
        global $wpdb;
        $items = Schema::tables()['items'];
        $bookings = Schema::tables()['bookings'];
        return $wpdb->get_results($wpdb->prepare(
            "SELECT bi.item_type, bi.item_id, SUM(bi.quantity) AS quantity, COALESCE(SUM(bi.total),0) AS value
             FROM {$items} bi INNER JOIN {$bookings} b ON b.id=bi.booking_id
             WHERE b.created_at BETWEEN %s AND %s
             GROUP BY bi.item_type, bi.item_id ORDER BY value DESC LIMIT 20",
            sanitize_text_field($from).' 00:00:00',
            sanitize_text_field($to).' 23:59:59'
        ), ARRAY_A) ?: [];
    }

    public function exportCsv(string $from, string $to): string
    {
        $summary = $this->summary($from, $to);
        $rows = [['Metric','Value']];
        foreach ($summary as $metric => $value) {
            $rows[] = [$metric, (string)$value];
        }

        $handle = fopen('php://temp', 'w+');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        return (string)stream_get_contents($handle);
    }
}
