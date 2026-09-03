<?php

declare(strict_types=1);

namespace PWT\Bookings\Repositories;

defined('ABSPATH') || exit;

final class BookingRepository
{
    public function create(array $booking)
    {
        $meta = [
            'name' => $booking['name'] ?? '',
            'email' => $booking['email'] ?? '',
            'phone' => $booking['phone'] ?? '',
            'travel_date' => $booking['travel_date'] ?? '',
            'persons' => $booking['persons'] ?? 1,
            'package_id' => $booking['package_id'] ?? 0,
            'message' => $booking['message'] ?? '',
        ];

        $postId = wp_insert_post([
            'post_type' => 'pwt_booking',
            'post_status' => 'publish',
            'post_title' => sprintf(
                '%s - %s',
                $booking['name'] ?? 'Booking',
                current_time('mysql')
            ),
            'meta_input' => $meta,
        ]);

        if (is_wp_error($postId)) {
            return $postId;
        }

        update_post_meta($postId, '_pwt_status', 'pending_payment');
        update_post_meta($postId, '_pwt_payment_status', 'pending_payment');

        return (int)$postId;
    }
}