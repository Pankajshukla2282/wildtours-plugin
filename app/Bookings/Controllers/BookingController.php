<?php

declare(strict_types=1);

namespace PWT\Bookings\Controllers;

defined('ABSPATH') || exit;

use PWT\Bookings\Services\BookingService;
use PWT\Frontend\Pricing;

/**
 * Handles booking requests.
 */
final class BookingController
{
    public function __construct(
        private readonly BookingService $bookingService
    ) {
    }

    /**
     * Register AJAX hooks.
     */
    public function register(): void
    {
        add_action('wp_ajax_pwt_booking', [$this, 'handle']);
        add_action('wp_ajax_nopriv_pwt_booking', [$this, 'handle']);
        add_action('wp_ajax_pwt_quote_booking', [$this, 'quote']);
        add_action('wp_ajax_nopriv_pwt_quote_booking', [$this, 'quote']);
    }

    /**
     * Process booking.
     */
    public function handle(): void
    {
        check_ajax_referer('pwt_booking', 'nonce');

        $throttle = $this->enforceRateLimit('booking');
        if (is_wp_error($throttle)) {
            wp_send_json_error(['message' => $throttle->get_error_message()], 429);
        }

        $result = $this->bookingService->create($_POST);

        if ($result['success']) {
            wp_send_json_success($result);
        }

        wp_send_json_error($result, 400);
    }

    /**
     * Return a live price estimate for the booking form.
     */
    public function quote(): void
    {
        check_ajax_referer('pwt_booking', 'nonce');

        $packageId = absint($_POST['package_id'] ?? 0);
        $persons = max(1, absint($_POST['persons'] ?? 1));
        $travelDate = sanitize_text_field($_POST['travel_date'] ?? '');

        if (!$packageId || $travelDate === '') {
            wp_send_json_error(
                ['message' => __('Invalid estimate request.', 'wildtours-plugin')],
                400
            );
        }

        $estimate = Pricing::calculateEstimate($packageId, $persons, $travelDate);

        if (empty($estimate['estimated_total'])) {
            wp_send_json_error(
                ['message' => __('Price on request.', 'wildtours-plugin')],
                200
            );
        }

        wp_send_json_success([
            'formatted_total' => $estimate['formatted_total'],
            'season_label' => $estimate['season_label'],
            'season_multiplier' => $estimate['season_multiplier'],
        ]);
    }

    /**
     * Throttle anonymous AJAX submissions per IP and window.
     */
    private function enforceRateLimit(string $bucket): bool|\WP_Error
    {
        $settings = get_option('pwt_settings', []);
        $limit = max(1, absint($settings['rest_rate_limit_per_minute'] ?? 20));
        $ip = sanitize_text_field((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $window = gmdate('YmdHi');
        $key = 'pwt_rl_' . md5($bucket . '|' . $ip . '|' . $window);
        $count = (int) get_transient($key);

        if ($count >= $limit) {
            return new \WP_Error(
                'pwt_rate_limited',
                __('Rate limit exceeded. Please retry after a minute.', 'wildtours-plugin')
            );
        }

        set_transient($key, $count + 1, MINUTE_IN_SECONDS + 5);

        return true;
    }
}