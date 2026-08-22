<?php
declare(strict_types=1);
namespace PWT\Payments;
defined('ABSPATH') || exit;

final class WebhookController
{
    public function __construct(private readonly PaymentEventRepository $events) {}
    public function register(): void
    {
        add_action('rest_api_init', function (): void {
            register_rest_route('pwt/v1', '/payments/webhook/(?P<provider>[a-z0-9_-]+)', [
                'methods'=>'POST','callback'=>[$this,'handle'],'permission_callback'=>'__return_true',
            ]);
        });
    }
    public function handle(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $provider=sanitize_key((string)$request['provider']); $raw=$request->get_body();
        if (!apply_filters('pwt_verify_payment_webhook', false, $provider, $raw, $request)) return new \WP_Error('pwt_webhook_unverified',__('Webhook signature could not be verified.','wildtours-plugin'),['status'=>401]);
        $payload=json_decode($raw,true);
        if (!is_array($payload)) return new \WP_Error('pwt_webhook_invalid',__('Invalid webhook payload.','wildtours-plugin'),['status'=>400]);
        $eventId=sanitize_text_field((string)apply_filters('pwt_payment_webhook_event_id','',$provider,$payload,$request));
        if ($eventId==='') $eventId='hash_'.hash('sha256',$provider."\n".$raw);
        if (!$this->events->claim($provider,$eventId,hash('sha256',$raw))) return new \WP_REST_Response(['success'=>true,'duplicate'=>true],200);
        $result=apply_filters('pwt_process_payment_webhook',null,$provider,$payload,$request);
        if (is_wp_error($result)) { $this->events->failed($provider,$eventId,$result->get_error_message()); return $result; }
        $this->events->processed($provider,$eventId);
        return new \WP_REST_Response(['success'=>true],200);
    }
}
