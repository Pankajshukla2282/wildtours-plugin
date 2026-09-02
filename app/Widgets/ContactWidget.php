<?php

namespace PWT\Widgets;

defined('ABSPATH') || exit;

class ContactWidget extends \WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            'pwt_contact_widget',
            __('PWT Contact Card', 'wildtours-plugin'),
            ['description' => __('Shows contact details from plugin settings.', 'wildtours-plugin')]
        );
    }

    public function widget($args, $instance): void
    {
        $settings = get_option('pwt_settings', []);
        $company = $settings['company_name'] ?? get_bloginfo('name');
        $phone = $settings['contact_phone'] ?? '';
        $email = $settings['contact_email'] ?? get_bloginfo('admin_email');

        echo wp_kses_post($args['before_widget']);
        echo wp_kses_post($args['before_title'] . esc_html__('Contact Us', 'wildtours-plugin') . $args['after_title']);
        echo '<p><strong>' . esc_html($company) . '</strong></p>';

        if ($phone) {
            echo '<p><a href="tel:' . esc_attr(preg_replace('/\s+/', '', $phone)) . '">' . esc_html($phone) . '</a></p>';
        }

        if ($email) {
            echo '<p><a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a></p>';
        }

        echo wp_kses_post($args['after_widget']);
    }
}
