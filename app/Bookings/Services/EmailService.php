<?php

declare(strict_types=1);

namespace PWT\Bookings\Services;

defined('ABSPATH') || exit;

use PWT\Bookings\EmailTemplates;

final class EmailService
{
    public function sendAdminNotification(
        int $bookingId,
        array $booking
    ): void {
        $sent = wp_mail(
            get_option('admin_email'),
            EmailTemplates::bookingAdminSubject($booking['name']),
            EmailTemplates::bookingAdminBody($booking)
        );

        if (!$sent) {
            error_log('PWT admin booking email failed for booking #' . $bookingId);
            update_post_meta($bookingId, '_pwt_email_admin_failed', '1');
            update_post_meta($bookingId, '_pwt_email_admin_failed_at', current_time('mysql'));
        }
    }

    public function sendCustomerConfirmation(
        int $bookingId,
        array $booking
    ): void {

        if (empty($booking['email'])) {
            return;
        }

        $sent = wp_mail(
            $booking['email'],
            __('Booking Confirmation', 'wildtours-plugin'),
            sprintf(
                __('Thank you %s. Your booking request has been received.', 'wildtours-plugin'),
                $booking['name']
            )
        );

        if (!$sent) {
            error_log('PWT customer booking email failed for booking #' . $bookingId);
            update_post_meta($bookingId, '_pwt_email_customer_failed', '1');
            update_post_meta($bookingId, '_pwt_email_customer_failed_at', current_time('mysql'));
        }
    }
}
