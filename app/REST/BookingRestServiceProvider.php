<?php
declare(strict_types=1);
namespace PWT\REST;
defined('ABSPATH') || exit;

use PWT\Core\ServiceProvider;

final class BookingRestServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        add_action('rest_api_init', function (): void {
            register_rest_route('pwt/v1', '/bookings', [
                'methods'=>'POST',
                'callback'=>[$this,'create'],
                'permission_callback'=>function(): bool {
                    return is_user_logged_in() && current_user_can('pwt_manage_operations');
                },
            ]);
        });
    }

    public function create(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $orchestrator = $this->make(\PWT\Bookings\BookingOrchestrator::class);
        $id = $orchestrator->create($request->get_json_params() ?: []);
        if (is_wp_error($id)) return $id;
        return new \WP_REST_Response(['success'=>true,'booking_id'=>$id], 201);
    }
}
