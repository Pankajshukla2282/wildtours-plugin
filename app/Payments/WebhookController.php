<?php
declare(strict_types=1);
namespace PWT\Payments;
defined('ABSPATH') || exit;

final class WebhookController
{
    public function register(): void
    {
        add_action('rest_api_init', function (): void {
            register_rest_route('pwt/v1', '/payments/webhook/(?P<provider>[a-z0-9_-]+)', [
                'methods' => 'POST',
                'callback' => [$this, 'handle'],
                'permission_callback' => '__return_true',
            ]);
        });
    }

    public function handle(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $provider = sanitize_key((string)$request['provider']);
        $raw = $request->get_body();

        /**
         * Provider-specific signature verification belongs in the provider adapter.
         * This endpoint intentionally does not trust an unsigned gateway payload.
         */
        $verified = apply_filters('pwt_verify_payment_webhook', false, $provider, $raw, $request);
        if (!$verified) {
            return new \WP_Error('pwt_webhook_unverified', __('Webhook signature could not be verified.', 'wildtours-plugin'), ['status'=>401]);
        }

        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            return new \WP_Error('pwt_webhook_invalid', __('Invalid webhook payload.', 'wildtours-plugin'), ['status'=>400]);
        }

        $result = apply_filters('pwt_process_payment_webhook', null, $provider, $payload, $request);
        if (is_wp_error($result)) {
            return $result;
        }

        return new \WP_REST_Response(['success'=>true], 200);
    }
}
