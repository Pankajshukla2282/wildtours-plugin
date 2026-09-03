<?php
declare(strict_types=1);
namespace PWT\Staff\CRM;
defined('ABSPATH') || exit;

final class CustomerSegmentationService {
    public function segment(int $customer): string {
        $transientKey = 'pwt_customer_segment_' . $customer;
        $cached = get_transient($transientKey);
        if ($cached !== false) {
            return $cached;
        }

        $count = count(get_posts([
            'post_type' => 'pwt_booking',
            'numberposts' => -1,
            'meta_key' => '_pwt_customer_id',
            'meta_value' => $customer
        ]));

        $result = $count >= 5 ? 'VIP Customer' : ($count >= 2 ? 'Repeat Customer' : 'First-Time Customer');
        set_transient($transientKey, $result, HOUR_IN_SECONDS);
        return $result;
    }
}
