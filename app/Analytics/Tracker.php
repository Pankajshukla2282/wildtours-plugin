<?php

namespace PWT\Analytics;

defined('ABSPATH') || exit;

class Tracker
{
    public function register(): void
    {
        add_action('wp_footer', [$this, 'renderAnalyticsScript'], 100);
        add_action('save_post_pwt_booking', [$this, 'recordBookingEvent'], 10, 3);
    }

    public function renderAnalyticsScript(): void
    {
        if (is_admin()) {
            return;
        }

        $settings = get_option('pwt_settings', []);
        $gaId = sanitize_text_field((string) ($settings['google_analytics_id'] ?? ''));

        if (!$gaId) {
            return;
        }

        ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($gaId); ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);} // eslint-disable-line no-unused-vars
            gtag('js', new Date());
            gtag('config', '<?php echo esc_js($gaId); ?>');
        </script>
        <?php
    }

    public function recordBookingEvent(int $postId, \WP_Post $post, bool $isUpdate): void
    {
        if (wp_is_post_revision($postId)) {
            return;
        }

        $count = (int) get_option('pwt_analytics_booking_count', 0);

        if ($isUpdate) {
            return;
        }

        update_option('pwt_analytics_booking_count', $count + 1, false);
    }
}
