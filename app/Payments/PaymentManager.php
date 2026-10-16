<?php

namespace PWT\Payments;

use PWT\Bookings\Services\EmailService;
use PWT\Payments\Gateways\GatewayFactory;

defined('ABSPATH') || exit;

class PaymentManager
{
    public function register(): void
    {
        add_action('init', [$this, 'handlePortalSubmission']);
    }

    public static function createIntent(int $bookingId, float $estimatedTotal): array
    {
        $settings = get_option('pwt_settings', []);
        $advancePercent = max(1, min(100, (int) ($settings['payment_advance_percent'] ?? 30)));
        $gateway = GatewayFactory::fromSettings($settings);
        $intent = $gateway->createIntent($bookingId, $estimatedTotal, $advancePercent);

        $token = (string) ($intent['token'] ?? wp_generate_password(24, false, false));
        $advanceAmount = (float) ($intent['advance_amount'] ?? round(($estimatedTotal * $advancePercent) / 100, 2));
        $paymentUrl = (string) ($intent['payment_url'] ?? '');

        update_post_meta($bookingId, '_pwt_payment_token', $token);
        update_post_meta($bookingId, '_pwt_payment_token_expires', time() + (30 * DAY_IN_SECONDS));
        update_post_meta($bookingId, '_pwt_payment_status', 'pending_payment');
        update_post_meta($bookingId, '_pwt_payment_advance_percent', $advancePercent);
        update_post_meta($bookingId, '_pwt_payment_due_amount', $advanceAmount);
        update_post_meta($bookingId, '_pwt_payment_total_amount', $estimatedTotal);
        update_post_meta($bookingId, '_pwt_payment_gateway', $gateway->slug());

        return [
            'token' => $token,
            'advance_amount' => $advanceAmount,
            'payment_url' => $paymentUrl !== '' ? $paymentUrl : self::paymentUrl($token),
        ];
    }

    public static function paymentUrl(string $token): string
    {
        $settings = get_option('pwt_settings', []);
        $baseUrl = trim((string) ($settings['payment_page_url'] ?? ''));

        if ($baseUrl === '') {
            return '';
        }

        return add_query_arg('pwt_payment', rawurlencode($token), $baseUrl);
    }

    public static function getBookingByToken(string $token): int
    {
        $posts = get_posts([
            'post_type' => 'pwt_booking',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => [[
                'key' => '_pwt_payment_token',
                'value' => $token,
            ]],
        ]);

        $bookingId = (int) ($posts[0] ?? 0);

        if ($bookingId <= 0) {
            return 0;
        }

        $expiresAt = (int) get_post_meta($bookingId, '_pwt_payment_token_expires', true);
        if ($expiresAt > 0 && time() > $expiresAt) {
            return 0;
        }

        return $bookingId;
    }

    public static function portalContext(string $token): array
    {
        $bookingId = self::getBookingByToken($token);

        if (!$bookingId) {
            return [];
        }

        $settings = get_option('pwt_settings', []);
        $totalAmount = (float) get_post_meta($bookingId, '_pwt_payment_total_amount', true);
        $advanceAmount = (float) get_post_meta($bookingId, '_pwt_payment_due_amount', true);
        $normalizedBookingId = absint(get_post_meta($bookingId, '_pwt_normalized_booking_id', true));
        $paidAmount = $normalizedBookingId > 0
            ? (new PaymentRepository())->totalPaidForBooking($normalizedBookingId)
            : 0.0;
        $amountDue = $paidAmount > 0
            ? max(0, $totalAmount - $paidAmount)
            : min($advanceAmount, $totalAmount);

        return [
            'booking_id' => $bookingId,
            'name' => (string) get_post_meta($bookingId, '_pwt_name', true),
            'phone' => (string) get_post_meta($bookingId, '_pwt_phone', true),
            'package_name' => get_the_title((int) get_post_meta($bookingId, '_pwt_package_id', true)),
            'travel_date' => (string) get_post_meta($bookingId, '_pwt_travel_date', true),
            'status' => (string) get_post_meta($bookingId, '_pwt_payment_status', true),
            'booking_status' => (string) get_post_meta($bookingId, '_pwt_status', true),
            'payment_review_status' => (string) get_post_meta($bookingId, '_pwt_payment_review_status', true),
            'payment_submitted_at' => (string) get_post_meta($bookingId, '_pwt_payment_submitted_at', true),
            'payment_reviewed_at' => (string) get_post_meta($bookingId, '_pwt_payment_reviewed_at', true),
            'advance_amount' => $amountDue,
            'amount_due' => $amountDue,
            'paid_amount' => $paidAmount,
            'total_amount' => $totalAmount,
            'payment_reference' => (string) get_post_meta($bookingId, '_pwt_payment_reference', true),
            'payment_method' => (string) get_post_meta($bookingId, '_pwt_payment_method', true),
            'gateway' => (string) get_post_meta($bookingId, '_pwt_payment_gateway', true),
            'upi_id' => (string) ($settings['payment_upi_id'] ?? ''),
            'instructions' => (string) ($settings['payment_instructions'] ?? ''),
            'allowed_methods' => self::allowedPaymentMethods($settings),
        ];
    }

    public function handlePortalSubmission(): void
    {
        if (($_POST['action'] ?? '') !== 'pwt_submit_payment_reference') {
            return;
        }

        $token = sanitize_text_field($_POST['payment_token'] ?? '');
        $bookingId = self::getBookingByToken($token);

        if (!$bookingId) {
            return;
        }

        check_admin_referer('pwt_payment_portal_' . $bookingId);

        $currentStatus = (string) get_post_meta($bookingId, '_pwt_payment_status', true);
        if (!self::canTransitionStatus($currentStatus, 'verification_pending')) {
            wp_safe_redirect(add_query_arg('payment_error', '1', wp_get_referer() ?: home_url('/')));
            exit;
        }

        $reference = sanitize_text_field($_POST['payment_reference'] ?? '');
        $method = sanitize_text_field($_POST['payment_method'] ?? 'upi');
        $settings = get_option('pwt_settings', []);
        $allowedMethods = self::allowedPaymentMethods($settings);
        $context = self::portalContext($token);
        $paymentAmount = round((float) sanitize_text_field($_POST['payment_amount'] ?? 0), 2);
        $amountDue = (float) ($context['amount_due'] ?? 0);

        if (
            $reference === ''
            || !in_array($method, $allowedMethods, true)
            || $paymentAmount <= 0
            || $amountDue <= 0
            || $paymentAmount > $amountDue + 0.005
        ) {
            wp_safe_redirect(add_query_arg('payment_error', '1', wp_get_referer() ?: home_url('/')));
            exit;
        }

        update_post_meta($bookingId, '_pwt_payment_reference', $reference);
        update_post_meta($bookingId, '_pwt_payment_method', $method);
        update_post_meta($bookingId, '_pwt_payment_submitted_amount', $paymentAmount);
        update_post_meta($bookingId, '_pwt_payment_status', 'verification_pending');
        update_post_meta($bookingId, '_pwt_payment_review_status', 'pending');
        update_post_meta($bookingId, '_pwt_payment_submitted_at', current_time('mysql'));
        update_post_meta($bookingId, '_pwt_payment_token_expires', time() + (30 * DAY_IN_SECONDS));

        (new EmailService())->sendPaymentReferenceNotice($bookingId, [
            'name' => (string) get_post_meta($bookingId, '_pwt_name', true),
            'email' => (string) get_post_meta($bookingId, '_pwt_email', true),
            'amount' => $paymentAmount,
            'method' => $method,
            'reference' => $reference,
        ]);

        wp_safe_redirect(add_query_arg('payment_success', '1', wp_get_referer() ?: home_url('/')));
        exit;
    }

    public static function statusLabel(string $status): string
    {
        $labels = [
            'pending_payment' => __('Pending Payment', 'wildtours-plugin'),
            'verification_pending' => __('Verification Pending', 'wildtours-plugin'),
            'partial_paid' => __('Advance Received', 'wildtours-plugin'),
            'paid' => __('Paid in Full', 'wildtours-plugin'),
            'failed' => __('Failed', 'wildtours-plugin'),
            'cancelled' => __('Cancelled', 'wildtours-plugin'),
        ];

        return $labels[$status] ?? __('Pending Payment', 'wildtours-plugin');
    }

    public static function canTransitionStatus(string $from, string $to): bool
    {
        $map = [
            'pending_payment' => ['verification_pending', 'failed', 'cancelled'],
            'verification_pending' => ['partial_paid', 'paid', 'failed', 'cancelled'],
            'partial_paid' => ['verification_pending', 'paid', 'failed', 'cancelled'],
            'paid' => [],
            'failed' => ['pending_payment', 'verification_pending', 'cancelled'],
            'cancelled' => [],
        ];

        if ($from === '') {
            return true;
        }

        return in_array($to, $map[$from] ?? [], true) || $from === $to;
    }

    public static function allowedPaymentMethods(array $settings = []): array
    {
        if (empty($settings)) {
            $settings = get_option('pwt_settings', []);
        }

        $raw = (string) ($settings['payment_methods'] ?? 'upi,bank_transfer,cash');
        $methods = array_filter(array_map('sanitize_key', array_map('trim', explode(',', $raw))));

        if (empty($methods)) {
            return ['upi', 'bank_transfer', 'cash'];
        }

        return array_values(array_unique($methods));
    }
}
