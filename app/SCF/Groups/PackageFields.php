<?php

declare(strict_types=1);

namespace PWT\SCF\Groups;

defined('ABSPATH') || exit;

use PWT\SCF\FieldGroup;

/**
 * Package field group.
 */
final class PackageFields extends FieldGroup
{
    /**
     * Register package field group.
     */
    public function register(): void
    {
        $this->addGroup([
            'key' => 'group_pwt_package_details',

            'title' => __('Package Details', 'wildtours-plugin'),

            'location' => [[[
                'param' => 'post_type',
                'operator' => '=',
                'value' => 'pwt_package',
            ]]],

            'fields' => [

                [
                    'key' => 'field_pwt_package_subtitle',
                    'label' => __('Subtitle', 'wildtours-plugin'),
                    'name' => 'subtitle',
                    'type' => 'text',
                ],

                [
                    'key' => 'field_pwt_package_code',
                    'label' => __('Package Code', 'wildtours-plugin'),
                    'name' => 'package_code',
                    'type' => 'text',
                ],

                [
                    'key' => 'field_pwt_package_duration',
                    'label' => __('Duration', 'wildtours-plugin'),
                    'name' => 'duration',
                    'type' => 'text',
                ],

                [
                    'key' => 'field_pwt_package_days',
                    'label' => __('Days', 'wildtours-plugin'),
                    'name' => 'days',
                    'type' => 'number',
                ],

                [
                    'key' => 'field_pwt_package_nights',
                    'label' => __('Nights', 'wildtours-plugin'),
                    'name' => 'nights',
                    'type' => 'number',
                ],

                [
                    'key' => 'field_pwt_package_regular_price',
                    'label' => __('Regular Price (INR)', 'wildtours-plugin'),
                    'name' => 'regular_price',
                    'type' => 'number',
                ],

                [
                    'key' => 'field_pwt_package_offer_price',
                    'label' => __('Offer Price (INR)', 'wildtours-plugin'),
                    'name' => 'offer_price',
                    'type' => 'number',
                ],

                [
                    'key' => 'field_pwt_package_child_price',
                    'label' => __('Child Price (INR)', 'wildtours-plugin'),
                    'name' => 'child_price',
                    'type' => 'number',
                ],

                [
                    'key' => 'field_pwt_package_peak_multiplier',
                    'label' => __('Peak Season Multiplier', 'wildtours-plugin'),
                    'name' => 'peak_multiplier',
                    'type' => 'number',
                    'default_value' => 1.2,
                ],

                [
                    'key' => 'field_pwt_package_shoulder_multiplier',
                    'label' => __('Shoulder Season Multiplier', 'wildtours-plugin'),
                    'name' => 'shoulder_multiplier',
                    'type' => 'number',
                    'default_value' => 1,
                ],

                [
                    'key' => 'field_pwt_package_monsoon_multiplier',
                    'label' => __('Monsoon Season Multiplier', 'wildtours-plugin'),
                    'name' => 'monsoon_multiplier',
                    'type' => 'number',
                    'default_value' => 0.85,
                ],

                [
                    'key' => 'field_pwt_package_resorts',
                    'label' => __('Related Resorts', 'wildtours-plugin'),
                    'name' => 'resorts',
                    'type' => 'relationship',
                    'post_type' => ['pwt_resort'],
                    'return_format' => 'id',
                ],

                [
                    'key' => 'field_pwt_package_cover_image',
                    'label' => __('Cover Image', 'wildtours-plugin'),
                    'name' => 'cover_image',
                    'type' => 'image',
                    'return_format' => 'array',
                ],

                [
                    'key' => 'field_pwt_package_gallery',
                    'label' => __('Gallery', 'wildtours-plugin'),
                    'name' => 'gallery',
                    'type' => 'gallery',
                    'return_format' => 'array',
                ],

                [
                    'key' => 'field_pwt_package_brochure',
                    'label' => __('Brochure', 'wildtours-plugin'),
                    'name' => 'brochure',
                    'type' => 'file',
                    'return_format' => 'array',
                ],

                [
                    'key' => 'field_pwt_package_booking_enabled',
                    'label' => __('Enable Booking', 'wildtours-plugin'),
                    'name' => 'booking_enabled',
                    'type' => 'true_false',
                ],

                [
                    'key' => 'field_pwt_package_min_person',
                    'label' => __('Minimum Persons', 'wildtours-plugin'),
                    'name' => 'minimum_person',
                    'type' => 'number',
                    'default_value' => 1,
                ],

                [
                    'key' => 'field_pwt_package_max_person',
                    'label' => __('Maximum Persons', 'wildtours-plugin'),
                    'name' => 'maximum_person',
                    'type' => 'number',
                    'default_value' => 12,
                ],

                [
                    'key' => 'field_pwt_package_inclusions',
                    'label' => __('Inclusions', 'wildtours-plugin'),
                    'name' => 'inclusions',
                    'type' => 'textarea',
                ],

                [
                    'key' => 'field_pwt_package_exclusions',
                    'label' => __('Exclusions', 'wildtours-plugin'),
                    'name' => 'exclusions',
                    'type' => 'textarea',
                ],

                [
                    'key' => 'field_pwt_package_itinerary',
                    'label' => __('Itinerary Days', 'wildtours-plugin'),
                    'name' => 'days_itinerary',
                    'type' => 'repeater',
                    'layout' => 'block',
                    'button_label' => __('Add Day', 'wildtours-plugin'),

                    'sub_fields' => [

                        [
                            'key' => 'field_pwt_package_itinerary_title',
                            'label' => __('Day Title', 'wildtours-plugin'),
                            'name' => 'title',
                            'type' => 'text',
                        ],

                        [
                            'key' => 'field_pwt_package_itinerary_description',
                            'label' => __('Description', 'wildtours-plugin'),
                            'name' => 'description',
                            'type' => 'textarea',
                        ],

                        [
                            'key' => 'field_pwt_package_itinerary_photo',
                            'label' => __('Photo', 'wildtours-plugin'),
                            'name' => 'photo',
                            'type' => 'image',
                            'return_format' => 'array',
                        ],
                    ],
                ],

            ],
        ]);
    }
}