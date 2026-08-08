<?php
declare(strict_types=1);
namespace PWT\API;
defined('ABSPATH') || exit;
use PWT\Availability\AvailabilityService;
use PWT\Pricing\PricingService;
use WP_REST_Request;
use WP_REST_Response;

final class ArchitectureRestApi
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly PricingService $pricing
    ) {}

    public function register(): void
    {
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
    }

    public function availability(WP_REST_Request $request): WP_REST_Response
    {
        $type = (string)$request['resource_type'];
        $id = (int)$request['resource_id'];
        $date = (string)$request['date'];
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

    public function quote(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response($this->pricing->quote(
            (int)$request['resource_id'],
            (string)$request['resource_type'],
            (string)$request['date'],
            max(1,(int)$request['quantity']),
            (float)$request['fallback']
        ));
    }
}
