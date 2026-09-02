<?php
declare(strict_types=1);
namespace PWT\Customers;
defined('ABSPATH') || exit;
use PWT\Core\Database\Schema;

final class CustomerPortal
{
    public function register(): void
    {
        add_shortcode('pwt_customer_portal', [$this, 'shortcode']);
    }

    public function shortcode(): string
    {
        if (!is_user_logged_in()) {
            return '<div class="pwt-customer-portal"><p>' .
                esc_html__('Please log in to view your bookings.', 'wildtours-plugin') .
                '</p><p><a class="button" href="' . esc_url(wp_login_url(get_permalink())) . '">' .
                esc_html__('Log in', 'wildtours-plugin') . '</a></p></div>';
        }

        global $wpdb;
        $user = wp_get_current_user();
        $customer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . Schema::tables()['customers'] . " WHERE email=%s ORDER BY id DESC LIMIT 1",
            sanitize_email($user->user_email)
        ), ARRAY_A);

        if (!$customer) {
            return '<div class="pwt-customer-portal"><p>' .
                esc_html__('No customer profile is linked to this account yet.', 'wildtours-plugin') .
                '</p></div>';
        }

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . Schema::tables()['bookings'] . " WHERE customer_id=%d ORDER BY created_at DESC LIMIT 50",
            (int)$customer['id']
        ), ARRAY_A) ?: [];

        ob_start();
        ?>
        <div class="pwt-customer-portal">
            <h2><?php esc_html_e('My Bookings', 'wildtours-plugin'); ?></h2>
            <?php if (!$rows): ?>
                <p><?php esc_html_e('No bookings found.', 'wildtours-plugin'); ?></p>
            <?php else: ?>
                <table class="pwt-customer-bookings">
                    <thead><tr><th><?php esc_html_e('Booking','wildtours-plugin'); ?></th><th><?php esc_html_e('Travel','wildtours-plugin'); ?></th><th><?php esc_html_e('Status','wildtours-plugin'); ?></th><th><?php esc_html_e('Total','wildtours-plugin'); ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?php echo esc_html($row['booking_number']); ?></td>
                            <td><?php echo esc_html(trim((string)$row['travel_start'] . ' – ' . (string)$row['travel_end'], ' –')); ?></td>
                            <td><?php echo esc_html(ucwords(str_replace('_',' ',(string)$row['status']))); ?></td>
                            <td><?php echo esc_html(number_format_i18n((float)$row['total'], 2) . ' ' . $row['currency']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
        return (string)ob_get_clean();
    }
}
