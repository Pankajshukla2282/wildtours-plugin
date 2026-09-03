<?php

declare(strict_types=1);

namespace PWT\Bookings\Repositories;

defined('ABSPATH') || exit;

final class BookingRepository
{
    public function create(array $booking)
    {
        $postId = wp_insert_post([

            'post_type' => 'pwt_booking',

            'post_status' => 'publish',

            'post_title' => sprintf(
                '%s - %s',
                $booking['name'],
                current_time('mysql')
            ),
        ]);

        if (is_wp_error($postId)) {
            return $postId;
        }

        $metaMap = [
            'name' => '_pwt_name',
            'email' => '_pwt_email',
            'phone' => '_pwt_phone',
            'travel_date' => '_pwt_travel_date',
            'persons' => '_pwt_persons',
            'package_id' => '_pwt_package_id',
            'message' => '_pwt_message',
        ];

        foreach ($booking as $key => $value) {
            $metaKey = $metaMap[$key] ?? ('_pwt_' . $key);

            update_post_meta(
                $postId,
                $metaKey,
                $value
            );
        }

        update_post_meta($postId, '_pwt_status', 'pending_payment');
        update_post_meta($postId, '_pwt_payment_status', 'pending_payment');

        return $postId;
    }
}