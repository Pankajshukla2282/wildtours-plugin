<?php
declare(strict_types=1);
namespace PWT\Admin;
defined('ABSPATH') || exit;
use PWT\Core\Database\Schema;

final class OperationsDashboard
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'menus'], 35);
    }

    public function menus(): void
    {
        add_submenu_page('pwt-dashboard', __('Operations','wildtours-plugin'), __('Operations','wildtours-plugin'), 'manage_options', 'pwt-operations', [$this,'operations']);
        add_submenu_page('pwt-dashboard', __('Availability','wildtours-plugin'), __('Availability','wildtours-plugin'), 'manage_options', 'pwt-availability', [$this,'availability']);
        add_submenu_page('pwt-dashboard', __('Pricing','wildtours-plugin'), __('Pricing','wildtours-plugin'), 'manage_options', 'pwt-pricing', [$this,'pricing']);
        add_submenu_page('pwt-dashboard', __('Customers','wildtours-plugin'), __('Customers','wildtours-plugin'), 'manage_options', 'pwt-customers', [$this,'customers']);
    }

    public function operations(): void
    {
        global $wpdb;
        $t = Schema::tables();
        $counts = [
            'bookings' => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$t['bookings']}"),
            'pending' => (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['bookings']} WHERE status=%s",'pending')),
            'confirmed' => (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['bookings']} WHERE status IN (%s,%s)",'confirmed','paid')),
            'customers' => (int)$wpdb->get_var("SELECT COUNT(*) FROM {$t['customers']}"),
            'payments' => (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['payments']} WHERE status=%s",'paid')),
        ];
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Panna Wild Tour Operations','wildtours-plugin'); ?></h1>
            <div class="pwt-admin-cards">
            <?php foreach ($counts as $label=>$value): ?>
                <div class="pwt-admin-card"><strong><?php echo esc_html(number_format_i18n($value)); ?></strong><span><?php echo esc_html(ucwords(str_replace('_',' ',$label))); ?></span></div>
            <?php endforeach; ?>
            </div>
            <h2><?php esc_html_e('Quick Actions','wildtours-plugin'); ?></h2>
            <p>
                <a class="button button-primary" href="<?php echo esc_url(admin_url('edit.php?post_type=pwt_booking')); ?>"><?php esc_html_e('Manage Bookings','wildtours-plugin'); ?></a>
                <a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type=pwt_room_unit')); ?>"><?php esc_html_e('Manage Rooms','wildtours-plugin'); ?></a>
                <a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type=pwt_safari_schedule')); ?>"><?php esc_html_e('Manage Safari Schedules','wildtours-plugin'); ?></a>
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=pwt-availability')); ?>"><?php esc_html_e('Availability','wildtours-plugin'); ?></a>
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=pwt-pricing')); ?>"><?php esc_html_e('Pricing','wildtours-plugin'); ?></a>
            </p>
        </div>
        <?php
    }

    public function availability(): void
    {
        global $wpdb;
        $t = Schema::tables();
        $rows = $wpdb->get_results("SELECT * FROM {$t['availability']} ORDER BY service_date ASC LIMIT 100", ARRAY_A) ?: [];
        ?>
        <div class="wrap"><h1><?php esc_html_e('Availability','wildtours-plugin'); ?></h1>
        <table class="widefat striped"><thead><tr><th>Resource</th><th>Date</th><th>Capacity</th><th>Reserved</th><th>Blocked</th><th>Remaining</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($rows as $r): ?><tr>
            <td><?php echo esc_html($r['resource_type'].' #'.$r['resource_id']); ?></td>
            <td><?php echo esc_html($r['service_date']); ?></td>
            <td><?php echo esc_html($r['capacity']); ?></td>
            <td><?php echo esc_html($r['reserved']); ?></td>
            <td><?php echo esc_html($r['blocked']); ?></td>
            <td><?php echo esc_html(max(0,(int)$r['capacity']-(int)$r['reserved']-(int)$r['blocked'])); ?></td>
            <td><?php echo esc_html($r['status']); ?></td>
        </tr><?php endforeach; ?>
        </tbody></table></div>
        <?php
    }

    public function pricing(): void
    {
        global $wpdb;
        $t = Schema::tables();
        $rows = $wpdb->get_results("SELECT * FROM {$t['rates']} ORDER BY priority ASC, id DESC LIMIT 100", ARRAY_A) ?: [];
        ?>
        <div class="wrap"><h1><?php esc_html_e('Pricing Rules','wildtours-plugin'); ?></h1>
        <p><?php esc_html_e('Rates are stored independently from content so seasonal and quantity-based pricing can change without changing package or safari content.','wildtours-plugin'); ?></p>
        <table class="widefat striped"><thead><tr><th>ID</th><th>Resource</th><th>Season</th><th>Date Range</th><th>Rate Type</th><th>Amount</th><th>Qty</th><th>Priority</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($rows as $r): ?><tr>
            <td><?php echo esc_html($r['id']); ?></td><td><?php echo esc_html($r['resource_type'].' #'.$r['resource_id']); ?></td><td><?php echo esc_html($r['season_id'] ?: '—'); ?></td>
            <td><?php echo esc_html(($r['start_date'] ?: 'Any').' → '.($r['end_date'] ?: 'Any')); ?></td><td><?php echo esc_html($r['rate_type']); ?></td>
            <td><?php echo esc_html(number_format_i18n((float)$r['amount'],2).' '.$r['currency']); ?></td>
            <td><?php echo esc_html($r['min_quantity'].'–'.($r['max_quantity'] ?: '∞')); ?></td><td><?php echo esc_html($r['priority']); ?></td><td><?php echo esc_html($r['status']); ?></td>
        </tr><?php endforeach; ?>
        </tbody></table></div>
        <?php
    }

    public function customers(): void
    {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT c.*, COUNT(b.id) AS booking_count FROM ".Schema::tables()['customers']." c LEFT JOIN ".Schema::tables()['bookings']." b ON b.customer_id=c.id GROUP BY c.id ORDER BY c.created_at DESC LIMIT 100", ARRAY_A) ?: [];
        ?>
        <div class="wrap"><h1><?php esc_html_e('Customers','wildtours-plugin'); ?></h1>
        <table class="widefat striped"><thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Country</th><th>Bookings</th><th>Created</th></tr></thead><tbody>
        <?php foreach ($rows as $r): ?><tr>
            <td><?php echo esc_html(trim($r['first_name'].' '.$r['last_name'])); ?></td><td><?php echo esc_html($r['email']); ?></td><td><?php echo esc_html($r['phone']); ?></td><td><?php echo esc_html($r['country']); ?></td><td><?php echo esc_html($r['booking_count']); ?></td><td><?php echo esc_html($r['created_at']); ?></td>
        </tr><?php endforeach; ?>
        </tbody></table></div>
        <?php
    }
}
