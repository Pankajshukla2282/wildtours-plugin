<?php
declare(strict_types=1);
namespace PWT\Admin;
defined('ABSPATH') || exit;

use PWT\Bookings\BookingItemRepository;
use PWT\Bookings\Repositories\BookingDataRepository;
use PWT\Core\Database\Schema;
use PWT\Payments\PaymentService;

final class BookingDetailPage
{
    public function __construct(
        private readonly BookingDataRepository $bookings,
        private readonly BookingItemRepository $items,
        private readonly PaymentService $payments
    ) {}

    public function register(): void
    {
        add_submenu_page(null, __('Booking Details','wildtours-plugin'), __('Booking Details','wildtours-plugin'), 'pwt_manage_operations', 'pwt-booking', [$this,'render']);
    }

    public static function url(int $id): string
    {
        return admin_url('admin.php?page=pwt-booking&booking_id=' . absint($id));
    }

    public function render(): void
    {
        if (!current_user_can('pwt_manage_operations')) { wp_die(__('You do not have permission to view this booking.','wildtours-plugin')); }
        $id = absint($_GET['booking_id'] ?? 0);
        $booking = $this->bookings->find($id);
        if (!$booking) { wp_die(__('Booking not found.','wildtours-plugin')); }
        global $wpdb; $t = Schema::tables();
        $customer = !empty($booking['customer_id']) ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['customers']} WHERE id=%d", $booking['customer_id']), ARRAY_A) : [];
        $items = $this->items->byBooking($id);
        $payments = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$t['payments']} WHERE booking_id=%d ORDER BY id DESC", $id), ARRAY_A) ?: [];
        $balance = $this->payments->balance($id);
        $audit = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$t['audit']} WHERE entity_type='booking' AND entity_id=%d ORDER BY created_at DESC LIMIT 50", $id), ARRAY_A) ?: [];
        ?>
        <div class="wrap pwt-booking-detail">
          <h1><?php echo esc_html($booking['booking_number']); ?> <span class="pwt-status-badge"><?php echo esc_html($booking['status']); ?></span></h1>
          <p><a href="<?php echo esc_url(admin_url('admin.php?page=pwt-operations')); ?>">← <?php esc_html_e('Back to Operations','wildtours-plugin'); ?></a></p>
          <div class="pwt-admin-cards">
            <div class="pwt-admin-card"><strong><?php echo esc_html($booking['currency'].' '.number_format_i18n((float)$booking['total'],2)); ?></strong><span><?php esc_html_e('Booking Total','wildtours-plugin'); ?></span></div>
            <div class="pwt-admin-card"><strong><?php echo esc_html($booking['currency'].' '.number_format_i18n((float)($balance['net_paid'] ?? 0),2)); ?></strong><span><?php esc_html_e('Net Paid','wildtours-plugin'); ?></span></div>
            <div class="pwt-admin-card"><strong><?php echo esc_html($booking['currency'].' '.number_format_i18n((float)($balance['outstanding'] ?? 0),2)); ?></strong><span><?php esc_html_e('Outstanding','wildtours-plugin'); ?></span></div>
          </div>
          <h2><?php esc_html_e('Customer','wildtours-plugin'); ?></h2>
          <?php if ($customer): ?>
            <p><strong><a href="<?php echo esc_url(admin_url('admin.php?page=pwt-customers&customer_id='.absint($customer['id']))); ?>"><?php echo esc_html(trim(($customer['first_name'] ?? '').' '.($customer['last_name'] ?? '')) ?: __('Customer','wildtours-plugin')); ?></a></strong><br><?php echo esc_html($customer['email'] ?? ''); ?><br><?php echo esc_html($customer['phone'] ?? ''); ?></p>
          <?php else: ?><p><?php esc_html_e('No linked customer record.','wildtours-plugin'); ?></p><?php endif; ?>
          <h2><?php esc_html_e('Travel','wildtours-plugin'); ?></h2>
          <p><?php echo esc_html(($booking['travel_start'] ?: '—').' → '.($booking['travel_end'] ?: '—')); ?> · <?php echo esc_html((int)$booking['adults']); ?> <?php esc_html_e('Adults','wildtours-plugin'); ?> · <?php echo esc_html((int)$booking['children']); ?> <?php esc_html_e('Children','wildtours-plugin'); ?></p>
          <h2><?php esc_html_e('Booking Items','wildtours-plugin'); ?></h2>
          <table class="widefat striped"><thead><tr><th><?php esc_html_e('Item','wildtours-plugin'); ?></th><th><?php esc_html_e('Dates','wildtours-plugin'); ?></th><th><?php esc_html_e('Qty','wildtours-plugin'); ?></th><th><?php esc_html_e('Vendor','wildtours-plugin'); ?></th><th><?php esc_html_e('Total','wildtours-plugin'); ?></th></tr></thead><tbody>
          <?php foreach ($items as $item): $objectUrl = !empty($item['object_id']) ? get_edit_post_link((int)$item['object_id']) : ''; $vendorUrl = !empty($item['vendor_id']) ? admin_url('admin.php?page=pwt-vendors&vendor_id='.absint($item['vendor_id'])) : ''; ?>
            <tr><td><?php if ($objectUrl): ?><a href="<?php echo esc_url($objectUrl); ?>"><?php echo esc_html($item['name']); ?></a><?php else: echo esc_html($item['name']); endif; ?></td><td><?php echo esc_html(($item['start_date'] ?: '—').' → '.($item['end_date'] ?: '—')); ?></td><td><?php echo esc_html((int)$item['quantity']); ?></td><td><?php if ($vendorUrl): ?><a href="<?php echo esc_url($vendorUrl); ?>"><?php echo esc_html($item['vendor_name'] ?: __('View Vendor','wildtours-plugin')); ?></a><?php else: echo esc_html($item['vendor_name'] ?: '—'); endif; ?></td><td><?php echo esc_html($booking['currency'].' '.number_format_i18n((float)$item['total'],2)); ?></td></tr>
          <?php endforeach; ?></tbody></table>
          <h2><?php esc_html_e('Payment Ledger','wildtours-plugin'); ?></h2>
          <table class="widefat striped"><thead><tr><th><?php esc_html_e('ID','wildtours-plugin'); ?></th><th><?php esc_html_e('Type','wildtours-plugin'); ?></th><th><?php esc_html_e('Gateway','wildtours-plugin'); ?></th><th><?php esc_html_e('Amount','wildtours-plugin'); ?></th><th><?php esc_html_e('Status','wildtours-plugin'); ?></th><th><?php esc_html_e('Reference','wildtours-plugin'); ?></th></tr></thead><tbody>
          <?php foreach ($payments as $p): ?><tr><td>#<?php echo esc_html($p['id']); ?></td><td><?php echo esc_html($p['transaction_type']); ?></td><td><?php echo esc_html($p['gateway']); ?></td><td><?php echo esc_html($p['currency'].' '.number_format_i18n((float)$p['amount'],2)); ?></td><td><?php echo esc_html($p['status']); ?></td><td><?php echo esc_html($p['reference'] ?: $p['transaction_id']); ?></td></tr><?php endforeach; ?></tbody></table>
          <h2><?php esc_html_e('Booking History','wildtours-plugin'); ?></h2>
          <table class="widefat striped"><thead><tr><th><?php esc_html_e('When','wildtours-plugin'); ?></th><th><?php esc_html_e('Event','wildtours-plugin'); ?></th><th><?php esc_html_e('Details','wildtours-plugin'); ?></th></tr></thead><tbody>
          <?php foreach ($audit as $event): ?><tr><td><?php echo esc_html($event['created_at']); ?></td><td><?php echo esc_html($event['event_type']); ?></td><td><code><?php echo esc_html((string)$event['payload']); ?></code></td></tr><?php endforeach; ?></tbody></table>
        </div><?php
    }
}
