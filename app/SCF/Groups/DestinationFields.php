<?php

declare(strict_types=1);

namespace PWT\SCF\Groups;

defined('ABSPATH') || exit;

use PWT\SCF\FieldGroup;

/**
 * Destination field group.
 */
final class DestinationFields extends FieldGroup
{
    /**
     * Register destination fields.
     */
    public function register(): void
    {
        $this->addGroup([
            'key' => 'group_pwt_destination_details',

            'title' => __('Destination Details', 'wildtours-plugin'),

            'location' => [[[
                'param' => 'post_type',
                'operator' => '=',
                'value' => 'pwt_destination',
            ]]],

            'fields' => [

                [
                    'key' => 'field_pwt_destination_code',
                    'label' => __('Destination Code', 'wildtours-plugin'),
                    'name' => 'destination_code',
                    'type' => 'text',
                ],

                [
                    'key' => 'field_pwt_destination_state',
                    'label' => __('State', 'wildtours-plugin'),
                    'name' => 'state',
                    'type' => 'text',
                ],

                [
                    'key' => 'field_pwt_destination_country',
                    'label' => __('Country', 'wildtours-plugin'),
                    'name' => 'country',
                    'type' => 'text',
                ],

                [
                    'key' => 'field_pwt_destination_latitude',
                    'label' => __('Latitude', 'wildtours-plugin'),
                    'name' => 'latitude',
                    'type' => 'text',
                ],

                [
                    'key' => 'field_pwt_destination_longitude',
                    'label' => __('Longitude', 'wildtours-plugin'),
                    'name' => 'longitude',
                    'type' => 'text',
                ],

                [
                    'key' => 'field_pwt_destination_map',
                    'label' => __('Google Map URL', 'wildtours-plugin'),
                    'name' => 'google_map',
                    'type' => 'url',
                ],

                [
                    'key' => 'field_pwt_destination_best_time',
                    'label' => __('Best Time To Visit', 'wildtours-plugin'),
                    'name' => 'best_time',
                    'type' => 'text',
                ],

                [
                    'key' => 'field_pwt_destination_gallery',
                    'label' => __('Gallery', 'wildtours-plugin'),
                    'name' => 'gallery',
                    'type' => 'gallery',
                    'return_format' => 'array',
                ],

                [
                    'key' => 'field_pwt_destination_cover',
                    'label' => __('Cover Image', 'wildtours-plugin'),
                    'name' => 'cover_image',
                    'type' => 'image',
                    'return_format' => 'array',
                ],

                [
                    'key' => 'field_pwt_destination_video',
                    'label' => __('Video URL', 'wildtours-plugin'),
                    'name' => 'video_url',
                    'type' => 'url',
                ],

            ],
        ]);
    }
}