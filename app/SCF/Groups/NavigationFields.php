<?php

declare(strict_types=1);

namespace PWT\SCF\Groups;

use PWT\SCF\FieldGroup;

defined('ABSPATH') || exit;

/**
 * Navigation field group.
 *
 * Holds the primary navigation tree as a nested repeater plus the header
 * CTA and top bar text. Stored on the "PWT Navigation" options page so the
 * theme renders navigation straight from SCF values instead of WP Menus.
 */
final class NavigationFields extends FieldGroup
{
    public function register(): void
    {
        $this->registerOptionsPage();

        $this->addGroup([
            'key' => 'group_pwt_navigation',

            'title' => __('Navigation', 'wildtours-plugin'),

            'location' => [[[
                'param' => 'options_page',
                'operator' => '=',
                'value' => 'pwt-navigation',
            ]]],

            'fields' => [

                [
                    'key' => 'field_pwt_nav_items',
                    'label' => __('Navigation Items', 'wildtours-plugin'),
                    'name' => 'nav_items',
                    'type' => 'repeater',
                    'layout' => 'block',
                    'button_label' => __('Add Menu Item', 'wildtours-plugin'),
                    'sub_fields' => [

                        [
                            'key' => 'field_pwt_nav_label',
                            'label' => __('Label', 'wildtours-plugin'),
                            'name' => 'label',
                            'type' => 'text',
                            'required' => 1,
                        ],

                        [
                            'key' => 'field_pwt_nav_type',
                            'label' => __('Link Type', 'wildtours-plugin'),
                            'name' => 'type',
                            'type' => 'select',
                            'choices' => [
                                'custom' => __('Custom URL', 'wildtours-plugin'),
                                'page' => __('Page', 'wildtours-plugin'),
                                'post' => __('Post', 'wildtours-plugin'),
                            ],
                        ],

                        [
                            'key' => 'field_pwt_nav_slug',
                            'label' => __('Page / Post Slug (auto URL)', 'wildtours-plugin'),
                            'name' => 'slug',
                            'type' => 'text',
                            'instructions' => __('Use "home" for the front page. Leave blank when Link Type is Custom URL.', 'wildtours-plugin'),
                        ],

                        [
                            'key' => 'field_pwt_nav_url',
                            'label' => __('Custom URL (optional)', 'wildtours-plugin'),
                            'name' => 'url',
                            'type' => 'text',
                            'instructions' => __('Used when Link Type is Custom URL, or to override the auto-resolved URL.', 'wildtours-plugin'),
                        ],

                        [
                            'key' => 'field_pwt_nav_mega',
                            'label' => __('Mega Dropdown', 'wildtours-plugin'),
                            'name' => 'mega',
                            'type' => 'true_false',
                            'message' => __('Render children as a two-column mega dropdown', 'wildtours-plugin'),
                        ],

                        [
                            'key' => 'field_pwt_nav_children',
                            'label' => __('Child Items', 'wildtours-plugin'),
                            'name' => 'children',
                            'type' => 'repeater',
                            'layout' => 'block',
                            'button_label' => __('Add Child Item', 'wildtours-plugin'),
                            'sub_fields' => [

                                [
                                    'key' => 'field_pwt_nav_child_label',
                                    'label' => __('Label', 'wildtours-plugin'),
                                    'name' => 'label',
                                    'type' => 'text',
                                    'required' => 1,
                                ],

                                [
                                    'key' => 'field_pwt_nav_child_type',
                                    'label' => __('Link Type', 'wildtours-plugin'),
                                    'name' => 'type',
                                    'type' => 'select',
                                    'choices' => [
                                        'custom' => __('Custom URL', 'wildtours-plugin'),
                                        'page' => __('Page', 'wildtours-plugin'),
                                        'post' => __('Post', 'wildtours-plugin'),
                                    ],
                                ],

                                [
                                    'key' => 'field_pwt_nav_child_slug',
                                    'label' => __('Page / Post Slug (auto URL)', 'wildtours-plugin'),
                                    'name' => 'slug',
                                    'type' => 'text',
                                ],

                                [
                                    'key' => 'field_pwt_nav_child_url',
                                    'label' => __('Custom URL (optional)', 'wildtours-plugin'),
                                    'name' => 'url',
                                    'type' => 'text',
                                ],
                            ],
                        ],
                    ],
                ],

                [
                    'key' => 'field_pwt_header_cta_label',
                    'label' => __('Header CTA Label', 'wildtours-plugin'),
                    'name' => 'header_cta_label',
                    'type' => 'text',
                ],

                [
                    'key' => 'field_pwt_header_cta_url',
                    'label' => __('Header CTA URL', 'wildtours-plugin'),
                    'name' => 'header_cta_url',
                    'type' => 'text',
                ],

                [
                    'key' => 'field_pwt_topbar_text',
                    'label' => __('Top Bar Text', 'wildtours-plugin'),
                    'name' => 'topbar_text',
                    'type' => 'text',
                ],
            ],
        ]);
    }

    /**
     * Register the "PWT Navigation" options page (ACF or SCF flavour).
     */
    private function registerOptionsPage(): void
    {
        $args = [
            'page_title' => __('PWT Navigation', 'wildtours-plugin'),
            'menu_title' => __('Navigation', 'wildtours-plugin'),
            'menu_slug' => 'pwt-navigation',
            'capability' => 'manage_options',
            'parent_slug' => 'pwt-dashboard',
            'redirect' => false,
        ];

        if (function_exists('acf_add_options_page')) {
            acf_add_options_page($args);
            return;
        }

        if (function_exists('scf_add_options_page')) {
            scf_add_options_page($args);
        }
    }
}