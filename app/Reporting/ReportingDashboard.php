<?php
declare(strict_types=1);
namespace PWT\Reporting;
defined('ABSPATH') || exit;

final class ReportingDashboard
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'menus'], 30);
    }

    public function menus(): void
    {
        add_submenu_page(PWT_ADMIN_MENU_SLUG, __('Reports','wildtours-plugin'), __('Reports','wildtours-plugin'), 'manage_options', 'pwt-reports', [$this,'render']);
    }

    public function render(): void
    {
        $to = isset($_GET['to']) ? sanitize_text_field(wp_unslash($_GET['to'])) : current_time('Y-m-d');
        $from = isset($_GET['from']) ? sanitize_text_field(wp_unslash($_GET['from'])) : gmdate('Y-m-d', strtotime('-30 days'));
        $report = new ReportService();
        $summary = $report->summaryWithMargin($from, $to);
        $statuses = $report->bookingsByStatus($from, $to);
        $services = $report->topServices($from, $to);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Panna Wild Tour Reports','wildtours-plugin'); ?></h1>
            <form method="get">
                <input type="hidden" name="page" value="pwt-reports">
                <label><?php esc_html_e('From','wildtours-plugin'); ?> <input type="date" name="from" value="<?php echo esc_attr($from); ?>"></label>
                <label><?php esc_html_e('To','wildtours-plugin'); ?> <input type="date" name="to" value="<?php echo esc_attr($to); ?>"></label>
                <button class="button button-primary"><?php esc_html_e('Run Report','wildtours-plugin'); ?></button>
            </form>
            <div class="pwt-admin-cards">
            <?php foreach ($summary as $key=>$value): ?>
                <div class="pwt-admin-card"><strong><?php echo esc_html(is_float($value) ? number_format_i18n($value,2) : number_format_i18n((int)$value)); ?></strong><span><?php echo esc_html(ucwords(str_replace('_',' ',$key))); ?></span></div>
            <?php endforeach; ?>
            </div>
            <h2><?php esc_html_e('Bookings by Status','wildtours-plugin'); ?></h2>
            <table class="widefat striped"><thead><tr><th>Status</th><th>Bookings</th><th>Value</th></tr></thead><tbody>
            <?php foreach ($statuses as $row): ?><tr><td><?php echo esc_html($row['status']); ?></td><td><?php echo esc_html($row['bookings']); ?></td><td><?php echo esc_html(number_format_i18n((float)$row['value'],2)); ?></td></tr><?php endforeach; ?>
            </tbody></table>
            <h2><?php esc_html_e('Top Services','wildtours-plugin'); ?></h2>
            <table class="widefat striped"><thead><tr><th>Type</th><th>ID</th><th>Quantity</th><th>Value</th></tr></thead><tbody>
            <?php foreach ($services as $row): ?><tr><td><?php echo esc_html($row['item_type']); ?></td><td><?php echo esc_html($row['item_id']); ?></td><td><?php echo esc_html($row['quantity']); ?></td><td><?php echo esc_html(number_format_i18n((float)$row['value'],2)); ?></td></tr><?php endforeach; ?>
            </tbody></table>
        </div>
        <?php
    }
}
