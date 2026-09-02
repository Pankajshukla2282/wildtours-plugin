<?php

declare(strict_types=1);

namespace PWT\SCF\Groups;

defined('ABSPATH') || exit;

use PWT\SCF\FieldGroup;

/**
 * FAQ field group.
 */
final class FAQFields extends FieldGroup
{
    /**
     * Register FAQ fields.
     */
    public function register(): void
    {
        $this->addGroup([
            'key' => 'group_pwt_faq_details',
            'title' => __('FAQ Details', 'wildtours-plugin'),

            'location' => [[[
                'param' => 'post_type',
                'operator' => '=',
                'value' => 'pwt_faq',
            ]]],

            'fields' => [
                [
                    'key' => 'field_pwt_faq_category',
                    'label' => 'FAQ Category',
                    'name' => 'faq_category',
                    'type' => 'select',
                    'choices' => [
                        'booking' => 'Booking',
                        'safari' => 'Safari Rules',
                        'stay' => 'Accommodation',
                        'travel' => 'Travel Information',
                    ],
                ],
                [
                    'key' => 'field_pwt_faq_priority',
                    'label' => 'Display Priority',
                    'name' => 'priority',
                    'type' => 'number',
                ],
            ],
        ]);
    }
}
