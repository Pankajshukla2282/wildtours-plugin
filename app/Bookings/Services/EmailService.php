<?php

declare(strict_types=1);

namespace PWT\Bookings\Services;

defined('ABSPATH') || exit;

use PWT\Bookings\EmailTemplates;

final class EmailService
{
    public static function adminRecipients(): array
    {
        $settings = (array) get_option('pwt_settings', []);
        $notificationEmails = (string) ($settings['notification_emails'] ?? '');
        if ($notificationEmails === '') {
            $notificationEmails = (string) ($settings['booking_notification_emails'] ?? '');
        }

        $recipients = array_merge(
            [(string) ($settings['contact_email'] ?? '')],
            preg_split('/[\\s,;]+/', $notificationEmails, -1, PREG_SPLIT_NO_EMPTY)
        );
        $recipients = array_values(array_unique(array_filter(
            array_map('sanitize_email', $recipients),
            'is_email'
        )));

        if ($recipients === []) {
            $adminEmail = sanitize_email((string) get_option('admin_email'));
            if (is_email($adminEmail)) {
                $recipients[] = $adminEmail;
            }
        }

        return $recipients;
    }

    public function sendAdminNotification(
        int $bookingId,
        array $booking
    ): void {
        $sent = wp_mail(
            self::adminRecipients(),
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

        $lines = [
            sprintf(
                __('Thank you %s. Your booking request has been received.', 'wildtours-plugin'),
                $booking['name']
            ),
        ];

        if (!empty($booking['estimated_total'])) {
            $lines[] = sprintf(__('Estimated total: %s', 'wildtours-plugin'), $booking['estimated_total']);
        }
        if (!empty($booking['payment_advance_amount'])) {
            $lines[] = sprintf(
                __('Advance payment due: INR %s', 'wildtours-plugin'),
                number_format_i18n((float) $booking['payment_advance_amount'], 2)
            );
        }
        if (!empty($booking['payment_link'])) {
            $lines[] = '';
            $lines[] = __('Continue to payment:', 'wildtours-plugin');
            $lines[] = (string) $booking['payment_link'];
        } else {
            $lines[] = __('Our team will contact you with payment instructions after confirming availability.', 'wildtours-plugin');
        }

        $sent = wp_mail(
            $booking['email'],
            __('Booking request received', 'wildtours-plugin'),
            implode(PHP_EOL, $lines)
        );

        if (!$sent) {
            error_log('PWT customer booking email failed for booking #' . $bookingId);
            update_post_meta($bookingId, '_pwt_email_customer_failed', '1');
            update_post_meta($bookingId, '_pwt_email_customer_failed_at', current_time('mysql'));
        }
    }

    public function sendPaymentReferenceNotice(int $bookingId, array $booking): void
    {
        $subject = sprintf(
            __('Payment reference needs review for booking #%d', 'wildtours-plugin'),
            $bookingId
        );
        $body = implode(PHP_EOL, [
            __('A customer submitted a payment reference. Please verify the payment before confirming the booking.', 'wildtours-plugin'),
            '',
            'Name: ' . (string) ($booking['name'] ?? ''),
            'Email: ' . (string) ($booking['email'] ?? ''),
            'Amount: INR ' . number_format_i18n((float) ($booking['amount'] ?? 0), 2),
            'Method: ' . (string) ($booking['method'] ?? ''),
            'Reference: ' . (string) ($booking['reference'] ?? ''),
            admin_url('admin.php?page=pwt-payments'),
        ]);

        wp_mail(self::adminRecipients(), $subject, $body);
    }

    public function sendPaymentReviewResult(array $booking, bool $approved, float $amount, float $balanceDue = 0): void
    {
        $email = sanitize_email((string) ($booking['email'] ?? ''));
        if (!is_email($email)) {
            return;
        }

        if ($approved) {
            $subject = __('Payment verified for your booking', 'wildtours-plugin');
            if ($balanceDue > 0.005) {
                $body = sprintf(
                    __('Hello %1$s, we verified your payment of INR %2$s and confirmed your booking. The remaining balance is INR %3$s.', 'wildtours-plugin'),
                    (string) ($booking['name'] ?? ''),
                    number_format_i18n($amount, 2),
                    number_format_i18n($balanceDue, 2)
                );
                if (!empty($booking['payment_link'])) {
                    $body .= PHP_EOL . PHP_EOL . __('Pay the remaining balance:', 'wildtours-plugin') . ' ' . (string) $booking['payment_link'];
                }
            } else {
                $body = sprintf(
                    __('Hello %1$s, we verified your final payment of INR %2$s. Your booking is confirmed and paid in full.', 'wildtours-plugin'),
                    (string) ($booking['name'] ?? ''),
                    number_format_i18n($amount, 2)
                );
            }
        } else {
            $subject = __('Please resubmit your booking payment reference', 'wildtours-plugin');
            $body = sprintf(
                __('Hello %1$s, we could not verify the payment reference you submitted for your booking. Please use your payment link to submit a corrected reference, or contact our team for help.', 'wildtours-plugin'),
                (string) ($booking['name'] ?? '')
            );
            if (!empty($booking['payment_link'])) {
                $body .= PHP_EOL . PHP_EOL . __('Payment link:', 'wildtours-plugin') . ' ' . (string) $booking['payment_link'];
            }
        }

        wp_mail($email, $subject, $body);
    }
}
