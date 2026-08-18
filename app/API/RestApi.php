<?php

namespace PWT\API;

use PWT\Bookings\Services\BookingService;
use PWT\Documents\DocumentService;

defined('ABSPATH') || exit;

class RestApi
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly DocumentService $documents
    ) {
    }

    public function register(): void
    {
        add_action('rest_api_init', [$this, 'routes']);
    }

    public function routes(): void
    {
        register_rest_route('pwt/v1', '/packages', [
            'methods' => 'GET',
            'callback' => [$this, 'packages'],
            'permission_callback' => [$this, 'canReadPublicData'],
        ]);

        // Availability is owned by ArchitectureRestApi to keep one canonical public route.
        register_rest_route('pwt/v1', '/booking', [
            'methods' => 'POST',
            'callback' => [$this, 'booking'],
            'permission_callback' => [$this, 'canCreateBooking'],
            'args' => [
                'name' => ['required' => true],
                'phone' => ['required' => true],
                'travel_date' => ['required' => true],
                'persons' => ['required' => true],
            ],
        ]);

        foreach (['voucher', 'invoice'] as $document) {
            register_rest_route('pwt/v1', '/bookings/(?P<id>\d+)/' . $document, [
                'methods' => 'GET',
                'callback' => [$this, 'document'],
                'permission_callback' => [$this, 'canManageBookings'],
                'args' => ['id' => ['required' => true]],
            ]);
        }
    }

    public function canReadPublicData(
        \WP_REST_Request $request
    ): bool|\WP_Error {
        $throttle = $this->enforceRateLimit($request, 'public_read');
        if (is_wp_error($throttle)) {
            return $throttle;
        }

        return true;
    }

    public function canCreateBooking(
        \WP_REST_Request $request
    ): bool|\WP_Error {
        $throttle = $this->enforceRateLimit($request, 'booking');
        if (is_wp_error($throttle)) {
            return $throttle;
        }

        if (is_user_logged_in() && current_user_can('edit_posts')) {
            return true;
        }

        $nonce = (string) $request->get_header('x_wp_nonce');
        if ($nonce !== '' && wp_verify_nonce($nonce, 'wp_rest')) {
            return true;
        }

        $settings = get_option('pwt_settings', []);
        $configuredKey = (string) ($settings['rest_api_key'] ?? '');
        $providedKey = (string) $request->get_header('x_pwt_api_key');

        if ($configuredKey !== '' && hash_equals($configuredKey, $providedKey)) {
            return true;
        }

        return new \WP_Error(
            'pwt_rest_forbidden',
            __('Unauthorized booking API request.', 'wildtours-plugin'),
            ['status' => 403]
        );
    }

    public function canManageBookings(\WP_REST_Request $request): bool|\WP_Error
    {
        return is_user_logged_in() && current_user_can('edit_posts')
            ? true
            : new \WP_Error(
                'pwt_rest_forbidden',
                __('Unauthorized document request.', 'wildtours-plugin'),
                ['status' => 403]
            );
    }

    public function document(\WP_REST_Request $request): \WP_REST_Response
    {
        $id = absint($request['id']);
        $type = strpos($request->get_route(), 'voucher') !== false ? 'voucher' : 'invoice';

        $document = $type === 'invoice'
            ? $this->documents->invoice($id)
            : $this->documents->voucher($id);

        if (empty($document['booking_number'])) {
            return new \WP_REST_Response(['message' => __('Booking not found.', 'wildtours-plugin')], 404);
        }

        return new \WP_REST_Response([
            'type' => $type,
            'booking_id' => $id,
            'booking_number' => $document['booking_number'],
            'html' => $document['html'],
        ], 200);
    }

    public function packages(\WP_REST_Request $request): \WP_REST_Response
    {
        $posts = get_posts([
            'post_type' => 'pwt_package',
            'post_status' => 'publish',
            'posts_per_page' => 30,
        ]);

        $items = array_map(static function (\WP_Post $post): array {
            return [
                'id' => $post->ID,
                'title' => $post->post_title,
                'link' => get_permalink($post),
                'regular_price' => (float) get_post_meta($post->ID, 'regular_price', true),
                'offer_price' => (float) get_post_meta($post->ID, 'offer_price', true),
                'duration' => (string) get_post_meta($post->ID, 'duration', true),
            ];
        }, $posts);

        return new \WP_REST_Response(['data' => $items], 200);
    }

    public function booking(\WP_REST_Request $request): \WP_REST_Response
    {
        $name = sanitize_text_field((string) $request->get_param('name'));
        $phone = sanitize_text_field((string) $request->get_param('phone'));
        $email = sanitize_email((string) $request->get_param('email'));
        $travelDate = sanitize_text_field((string) $request->get_param('travel_date'));
        $persons = absint((string) $request->get_param('persons'));
        $packageId = absint((string) $request->get_param('package_id'));
        $message = sanitize_textarea_field((string) $request->get_param('message'));

        if (!$name || !$phone || !$travelDate || $persons < 1 || $persons > 50) {
            return new \WP_REST_Response(['message' => __('Invalid booking request.', 'wildtours-plugin')], 422);
        }

        if (!$this->isValidDate($travelDate)) {
            return new \WP_REST_Response(['message' => __('Travel date must be in YYYY-MM-DD format.', 'wildtours-plugin')], 422);
        }

        if ($travelDate < current_time('Y-m-d')) {
            return new \WP_REST_Response(['message' => __('Travel date must be in the future.', 'wildtours-plugin')], 422);
        }

        if (!preg_match('/^\+?[0-9\s\-]{8,20}$/', $phone)) {
            return new \WP_REST_Response(['message' => __('Phone number format is invalid.', 'wildtours-plugin')], 422);
        }

        if ($email !== '' && !is_email($email)) {
            return new \WP_REST_Response(['message' => __('Email format is invalid.', 'wildtours-plugin')], 422);
        }

        if ($packageId && !\PWT\Frontend\AvailabilityCalendar::isDateAvailable($packageId, $travelDate)) {
            return new \WP_REST_Response(['message' => __('Selected date is not available.', 'wildtours-plugin')], 409);
        }

        $result = $this->bookingService->create([
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'travel_date' => $travelDate,
            'persons' => $persons,
            'package_id' => $packageId,
            'message' => $message,
        ]);

        if (!$result['success']) {
            return new \WP_REST_Response(['message' => $result['message'] ?? __('Unable to create booking.', 'wildtours-plugin')], 422);
        }

        return new \WP_REST_Response([
            'booking_id' => $result['booking_id'],
            'payment_url' => $result['payment_url'] ?? '',
            'payment_advance_amount' => $result['payment_advance_amount'] ?? 0,
        ], 201);
    }

    private function isValidDate(string $date): bool
    {
        $parsed = \DateTime::createFromFormat('Y-m-d', $date);

        return $parsed instanceof \DateTime && $parsed->format('Y-m-d') === $date;
    }

    private function enforceRateLimit(\WP_REST_Request $request, string $bucket): bool|\WP_Error
    {
        $settings = get_option('pwt_settings', []);
        $limit = max(1, absint($settings['rest_rate_limit_per_minute'] ?? 20));
        $ip = sanitize_text_field((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $window = gmdate('YmdHi');
        $key = 'pwt_rl_' . md5($bucket . '|' . $ip . '|' . $window);
        $count = (int) get_transient($key);

        if ($count >= $limit) {
            return new \WP_Error(
                'pwt_rest_rate_limited',
                __('Rate limit exceeded. Please retry after a minute.', 'wildtours-plugin'),
                ['status' => 429]
            );
        }

        set_transient($key, $count + 1, MINUTE_IN_SECONDS + 5);

        return true;
    }
}
