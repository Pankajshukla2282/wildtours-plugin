<?php

declare(strict_types=1);

namespace PWT\Frontend;

defined('ABSPATH') || exit;

use PWT\Core\Paths;

final class Assets
{
    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    }

    /**
     * Load plugin frontend assets only where plugin UI can actually render.
     */
    public function enqueue(): void
    {
        if (!$this->shouldEnqueue()) {
            return;
        }

        $cssPath = Paths::path('assets/css/frontend.css');
        $jsPath  = Paths::path('assets/js/frontend.js');

        wp_enqueue_style(
            'pwt-plugin',
            Paths::uri('assets/css/frontend.css'),
            [],
            file_exists($cssPath) ? (string) filemtime($cssPath) : PWT_VERSION
        );

        wp_enqueue_script(
            'pwt-plugin',
            Paths::uri('assets/js/frontend.js'),
            [],
            file_exists($jsPath) ? (string) filemtime($jsPath) : PWT_VERSION,
            true
        );

        wp_script_add_data('pwt-plugin', 'strategy', 'defer');

        wp_localize_script(
            'pwt-plugin',
            'pwtFrontend',
            [
                'ajaxUrl'  => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('pwt_booking'),
                'messages' => [
                    'submitting' => __('Sending your booking request...', 'wildtours-plugin'),
                    'success'    => __('Booking submitted successfully.', 'wildtours-plugin'),
                    'error'      => __('Something went wrong. Please try again.', 'wildtours-plugin'),
                ],
            ]
        );
    }

    private function shouldEnqueue(): bool
    {
        if (is_admin()) {
            return false;
        }

        if (
            is_singular(['pwt_package', 'pwt_safari']) ||
            is_post_type_archive(['pwt_package', 'pwt_safari']) ||
            is_tax([
                'pwt_package_category',
                'pwt_safari_zone',
                'pwt_destination_category',
                'pwt_season',
                'pwt_activity',
                'pwt_trip_type',
                'pwt_vehicle_type',
                'pwt_resort_category',
                'pwt_cuisine',
            ])
        ) {
            return true;
        }

        if (is_active_widget(false, false, 'pwt_contact_widget', true)
            || is_active_widget(false, false, 'pwt_package_filter_widget', true)) {
            return true;
        }

        if (!is_singular()) {
            return false;
        }

        $post = get_post();
        if (!$post instanceof \WP_Post) {
            return false;
        }

        $shortcodes = [
            'pwt_homepage',
            'pwt_packages',
            'pwt_safaris',
            'pwt_destinations',
            'pwt_testimonials',
            'pwt_faq',
            'pwt_reviews',
            'pwt_contact_card',
            'pwt_booking_form',
            'pwt_payment_page',
            'pwt_customer_portal',
            'pwt_availability_calendar',
        ];

        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }
}
