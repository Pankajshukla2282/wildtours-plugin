<?php

declare(strict_types=1);

namespace PWT\SCF\Groups;

defined('ABSPATH') || exit;

use PWT\SCF\FieldGroup;

/**
 * Vehicle field group.
 */
final class VehicleFields extends FieldGroup
{
    /**
     * Register vehicle field group.
     */
    public function register(): void
    {
        $this->addGroup([
            'key' => 'group_pwt_vehicle_details',

            'title' => __('Vehicle Details', 'wildtours-plugin'),

            'location' => [[[
                'param'    => 'post_type',
                'operator' => '=',
                'value'    => 'pwt_vehicle',
            ]]],

            'fields' => [

                [
                    'key'     => 'field_pwt_vehicle_type',
                    'label'   => __('Vehicle Type', 'wildtours-plugin'),
                    'name'    => 'vehicle_type',
                    'type'    => 'select',

                    'choices' => [
                        'jeep'    => __('Jeep', 'wildtours-plugin'),
                        'canter'  => __('Canter', 'wildtours-plugin'),
                        'tempo'   => __('Tempo Traveller', 'wildtours-plugin'),
                        'car'     => __('Car', 'wildtours-plugin'),
                    ],
                ],

                [
                    'key'   => 'field_pwt_vehicle_capacity',
                    'label' => __('Passenger Capacity', 'wildtours-plugin'),
                    'name'  => 'capacity',
                    'type'  => 'number',
                ],

                [
                    'key'   => 'field_pwt_vehicle_price_day',
                    'label' => __('Price per Day (INR)', 'wildtours-plugin'),
                    'name'  => 'price_per_day',
                    'type'  => 'number',
                ],

                [
                    'key'   => 'field_pwt_vehicle_ac',
                    'label' => __('Air Conditioned', 'wildtours-plugin'),
                    'name'  => 'is_ac',
                    'type'  => 'true_false',
                ],

                [
                    'key'   => 'field_pwt_vehicle_notes',
                    'label' => __('Vehicle Notes', 'wildtours-plugin'),
                    'name'  => 'notes',
                    'type'  => 'textarea',
                ],

                [
                    'key' => 'field_pwt_vehicle_vendor',
                    'label' => __('Preferred Vendor', 'wildtours-plugin'),
                    'name' => 'vendor_id',
                    'type' => 'post_object',
                    'post_type' => ['pwt_vendor'],
                    'return_format' => 'id',
                ],

                [
                    'key' => 'field_pwt_vehicle_bookable',
                    'label' => __('Available for Package Booking', 'wildtours-plugin'),
                    'name' => 'bookable',
                    'type' => 'true_false',
                    'default_value' => 1,
                ],

            ],
        ]);
    }
}