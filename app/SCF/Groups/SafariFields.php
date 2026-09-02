<?php

declare(strict_types=1);

namespace PWT\SCF\Groups;

use PWT\SCF\FieldGroup;

defined('ABSPATH') || exit;

/**
 * Safari field group.
 */
final class SafariFields extends FieldGroup
{
    /**
     * Register field group.
     */
    public function register(): void
    {
        $this->addGroup([
            'key' => 'group_pwt_safari_details',

            'title' => __('Safari Details', 'wildtours-plugin'),

            'location' => [[[
                'param' => 'post_type',
                'operator' => '=',
                'value' => 'pwt_safari',
            ]]],

            'fields' => [

                [
                    'key' => 'field_pwt_safari_code',
                    'label' => __('Safari Code', 'wildtours-plugin'),
                    'name' => 'safari_code',
                    'type' => 'text',
                ],

                [
                    'key' => 'field_pwt_safari_type',
                    'label' => __('Safari Type', 'wildtours-plugin'),
                    'name' => 'safari_type',
                    'type' => 'select',
                    'choices' => [
                        'jeep'    => __('Jeep Safari', 'wildtours-plugin'),
                        'canter'  => __('Canter Safari', 'wildtours-plugin'),
                        'private' => __('Private Safari', 'wildtours-plugin'),
                    ],
                ],

                [
                    'key' => 'field_pwt_safari_shift',
                    'label' => __('Shift', 'wildtours-plugin'),
                    'name' => 'shift',
                    'type' => 'select',
                    'choices' => [
                        'morning' => __('Morning', 'wildtours-plugin'),
                        'evening' => __('Evening', 'wildtours-plugin'),
                        'full_day' => __('Full Day', 'wildtours-plugin'),
                    ],
                ],

                [
                    'key' => 'field_pwt_safari_duration',
                    'label' => __('Duration', 'wildtours-plugin'),
                    'name' => 'duration',
                    'type' => 'text',
                ],

                [
                    'key' => 'field_pwt_safari_price',
                    'label' => __('Base Price (INR)', 'wildtours-plugin'),
                    'name' => 'regular_price',
                    'type' => 'number',
                ],

                [
                    'key' => 'field_pwt_safari_offer_price',
                    'label' => __('Offer Price (INR)', 'wildtours-plugin'),
                    'name' => 'offer_price',
                    'type' => 'number',
                ],

                [
                    'key' => 'field_pwt_safari_meeting_point',
                    'label' => __('Meeting Point', 'wildtours-plugin'),
                    'name' => 'meeting_point',
                    'type' => 'text',
                ],

                [
                    'key' => 'field_pwt_safari_notes',
                    'label' => __('Inclusions', 'wildtours-plugin'),
                    'name' => 'inclusions',
                    'type' => 'textarea',
                ],
                [
                    'key' => 'field_pwt_safari_vendor',
                    'label' => __('Preferred Vendor', 'wildtours-plugin'),
                    'name' => 'vendor_id',
                    'type' => 'post_object',
                    'post_type' => ['pwt_vendor'],
                    'return_format' => 'id',
                ],

                [
                    'key' => 'field_pwt_safari_bookable',
                    'label' => __('Available for Package Booking', 'wildtours-plugin'),
                    'name' => 'bookable',
                    'type' => 'true_false',
                    'default_value' => 1,
                ],

            ],
        ]);
    }
}