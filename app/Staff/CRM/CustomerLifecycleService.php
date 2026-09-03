<?php
declare(strict_types=1);
namespace PWT\Staff\CRM;
defined('ABSPATH') || exit;

final class CustomerLifecycleService {
    public function bookingCount(int $customerId): int {
        $transientKey = 'pwt_customer_bookings_' . $customerId;
        $cached = get_transient($transientKey);
        if ($cached !== false) {
            return (int)$cached;
        }

        $count = count(get_posts([
            'post_type' => 'pwt_booking',
            'post_status' => 'any',
            'numberposts' => -1,
            'meta_key' => '_pwt_customer_id',
            'meta_value' => $customerId,
            'fields' => 'ids'
        ]));

        set_transient($transientKey, $count, HOUR_IN_SECONDS);
        return $count;
    }

    public function status(int $customerId): string {
        $count = $this->bookingCount($customerId);
        return $count > 1 ? 'Repeat Customer' : 'Customer';
    }
}
