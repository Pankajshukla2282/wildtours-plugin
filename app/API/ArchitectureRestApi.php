<?php
declare(strict_types=1);
namespace PWT\API;
defined('ABSPATH') || exit;
use PWT\Availability\AvailabilityService;
use PWT\Pricing\PricingService;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

final class ArchitectureRestApi
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly PricingService $pricing
    ) {}

    public function register(): void
    {
        add_action('rest_api_init', function (): void {
            register_rest_route('pwt/v1', '/availability', [
            'methods' => 'GET',
            'callback' => [$this, 'availability'],
            'permission_callback' => '__return_true',
            'args' => [
                'resource_type' => ['required'=>true,'sanitize_callback'=>'sanitize_key'],
                'resource_id' => ['required'=>true,'sanitize_callback'=>'absint'],
                'date' => ['required'=>true,'sanitize_callback'=>'sanitize_text_field'],
                'quantity' => ['default'=>1,'sanitize_callback'=>'absint'],
            ],
        ]);

        register_rest_route('pwt/v1', '/quote', [
            'methods' => 'GET',
            'callback' => [$this, 'quote'],
            'permission_callback' => '__return_true',
            'args' => [
                'resource_type' => ['required'=>true,'sanitize_callback'=>'sanitize_key'],
                'resource_id' => ['required'=>true,'sanitize_callback'=>'absint'],
                'date' => ['required'=>true,'sanitize_callback'=>'sanitize_text_field'],
                'quantity' => ['default'=>1,'sanitize_callback'=>'absint'],
                'fallback' => ['default'=>0,'sanitize_callback'=>'floatval'],
            ],
            ]);
        });
    }

    public function availability(WP_REST_Request $request): WP_REST_Response|\WP_Error
    {
        $throttle = $this->enforceRateLimit('availability');
        if (is_wp_error($throttle)) {
            return $throttle;
        }

        $type = (string)$request['resource_type'];
        $id = (int)$request['resource_id'];
        $date = (string)$request['date'];
        if (!$this->isValidDate($date)) {
            return new WP_Error(
                'pwt_invalid_date',
                __('Date must be in YYYY-MM-DD format.', 'wildtours-plugin'),
                ['status' => 422]
            );
        }
        $quantity = max(1, (int)$request['quantity']);

        return new WP_REST_Response([
            'resource_type'=>$type,
            'resource_id'=>$id,
            'date'=>$date,
            'quantity'=>$quantity,
            'available'=>$this->availability->isAvailable($id,$type,$date,$quantity),
            'remaining'=>$this->availability->remaining($id,$type,$date),
        ]);
    }

    public function quote(WP_REST_Request $request): WP_REST_Response|\WP_Error
    {
        $throttle = $this->enforceRateLimit('quote');
        if (is_wp_error($throttle)) {
            return $throttle;
        }

        $date = (string) $request['date'];
        if (!$this->isValidDate($date)) {
            return new WP_Error(
                'pwt_invalid_date',
                __('Date must be in YYYY-MM-DD format.', 'wildtours-plugin'),
                ['status' => 422]
            );
        }

        return new WP_REST_Response($this->pricing->quote(
            (int)$request['resource_id'],
            (string)$request['resource_type'],
            $date,
            max(1,(int)$request['quantity']),
            (float)$request['fallback']
        ));
    }

    private function isValidDate(string $date): bool
    {
        $parsed = \DateTime::createFromFormat('Y-m-d', $date);

        return $parsed instanceof \DateTime
            && $parsed->format('Y-m-d') === $date;
    }

    private function enforceRateLimit(string $bucket): bool|\WP_Error
    {
        $settings = get_option('pwt_settings', []);
        $limit = max(1, absint($settings['rest_rate_limit_per_minute'] ?? 20));
        $ip = sanitize_text_field((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $window = gmdate('YmdHi');
        $key = 'pwt_rl_' . md5($bucket . '|' . $ip . '|' . $window);
        $count = (int) get_transient($key);

        if ($count >= $limit) {
            return new WP_Error(
                'pwt_rest_rate_limited',
                __('Rate limit exceeded. Please retry after a minute.', 'wildtours-plugin'),
                ['status' => 429]
            );
        }

        set_transient($key, $count + 1, MINUTE_IN_SECONDS + 5);

        return true;
    }
}
