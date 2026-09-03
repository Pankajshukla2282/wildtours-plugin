<?php
declare(strict_types=1);
namespace PWT\Staff\CRM;
defined('ABSPATH') || exit;

final class FeedbackRepository {
    const POST_TYPE = 'pwt_customer_feedback';

    public function register(): void {
        register_post_type(self::POST_TYPE, ['label' => 'Customer Feedback', 'public' => false, 'show_ui' => false, 'supports' => ['title']]);
    }

    public function create(array $d): int {
        $id = wp_insert_post([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'post_title' => sanitize_text_field($d['title'] ?? 'Tour Feedback')
        ]);
        if (is_wp_error($id)) return 0;
        $meta = [
            'booking_id' => $d['booking_id'] ?? '',
            'customer_id' => $d['customer_id'] ?? '',
            'overall_rating' => $d['overall_rating'] ?? '',
            'safari_rating' => $d['safari_rating'] ?? '',
            'accommodation_rating' => $d['accommodation_rating'] ?? '',
            'transport_rating' => $d['transport_rating'] ?? '',
            'service_rating' => $d['service_rating'] ?? '',
            'feedback' => $d['feedback'] ?? '',
            'review_requested_at' => $d['review_requested_at'] ?? '',
            'created_at' => current_time('mysql')
        ];
        foreach ($meta as $key => $value) {
            update_post_meta($id, '_pwt_feedback_' . $key, $value);
        }
        return (int)$id;
    }

    public function forCustomer(int $customer): array {
        $transientKey = 'pwt_feedback_customer_' . $customer;
        $cached = get_transient($transientKey);
        if ($cached !== false) {
            return $cached;
        }

        $result = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_key' => '_pwt_feedback_customer_id',
            'meta_value' => $customer
        ]);

        set_transient($transientKey, $result, HOUR_IN_SECONDS);
        return $result;
    }
}
