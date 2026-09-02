<?php

declare(strict_types=1);

namespace PWT\SCF\Groups;

defined('ABSPATH') || exit;

use PWT\SCF\FieldGroup;

/**
 * Review field group.
 */
final class ReviewFields extends FieldGroup
{
    /**
     * Register review fields.
     */
    public function register(): void
    {
        $this->addGroup([
            'key' => 'group_pwt_review_details',
            'title' => __('Review Details', 'wildtours-plugin'),

            'location' => [[[
                'param' => 'post_type',
                'operator' => '=',
                'value' => 'pwt_review',
            ]]],

            'fields' => [
                [
                    'key' => 'field_pwt_review_rating',
                    'label' => 'Rating (1-5)',
                    'name' => 'rating',
                    'type' => 'number',
                ],
                [
                    'key' => 'field_pwt_review_guest_city',
                    'label' => 'Guest City',
                    'name' => 'guest_city',
                    'type' => 'text',
                ],
                [
                    'key' => 'field_pwt_review_verified',
                    'label' => 'Verified Customer',
                    'name' => 'verified',
                    'type' => 'true_false',
                ],
            ],
        ]);
    }
}
