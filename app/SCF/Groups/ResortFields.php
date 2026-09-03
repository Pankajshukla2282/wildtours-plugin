<?php

declare(strict_types=1);

namespace PWT\SCF\Groups;

defined('ABSPATH') || exit;

use PWT\SCF\FieldGroup;

/**
 * Resort field group.
 */
final class ResortFields extends FieldGroup
{
    /**
     * Register resort field group.
     */
    public function register(): void
    {
        $this->addGroup([
            'key'   => 'group_pwt_resort_details',

            'title' => __('Resort Details', 'wildtours-plugin'),

            'location' => [[[
                'param'    => 'post_type',
                'operator' => '=',
                'value'    => 'pwt_resort',
            ]]],

            'fields' => [

                [
                    'key'     => 'field_pwt_resort_type',
                    'label'   => __('Resort Type', 'wildtours-plugin'),
                    'name'    => 'resort_type',
                    'type'    => 'select',
                    'choices' => [
                        'budget'  => __('Budget', 'wildtours-plugin'),
                        'premium' => __('Premium', 'wildtours-plugin'),
                        'luxury'  => __('Luxury', 'wildtours-plugin'),
                    ],
                ],

                [
                    'key'   => 'field_pwt_resort_price',
                    'label' => __('Price per Night (INR)', 'wildtours-plugin'),
                    'name'  => 'price_per_night',
                    'type'  => 'number',
                ],

                [
                    'key'   => 'field_pwt_resort_distance_gate',
                    'label' => __('Distance from Safari Gate (KM)', 'wildtours-plugin'),
                    'name'  => 'distance_from_gate',
                    'type'  => 'number',
                ],

                [
                    'key'     => 'field_pwt_resort_amenities',
                    'label'   => __('Amenities', 'wildtours-plugin'),
                    'name'    => 'amenities',
                    'type'    => 'checkbox',
                    'choices' => [
                        'pool'       => __('Swimming Pool', 'wildtours-plugin'),
                        'wifi'       => __('WiFi', 'wildtours-plugin'),
                        'parking'    => __('Parking', 'wildtours-plugin'),
                        'restaurant' => __('Restaurant', 'wildtours-plugin'),
                        'pickup'     => __('Pickup & Drop', 'wildtours-plugin'),
                    ],
                ],

                [
                    'key'   => 'field_pwt_resort_contact_phone',
                    'label' => __('Contact Phone', 'wildtours-plugin'),
                    'name'  => 'contact_phone',
                    'type'  => 'text',
                ],

                [
                    'key'   => 'field_pwt_resort_contact_email',
                    'label' => __('Contact Email', 'wildtours-plugin'),
                    'name'  => 'contact_email',
                    'type'  => 'email',
                ],

            ],
        ]);
    }
}