<?php
declare(strict_types=1);
namespace PWT\Admin;
defined('ABSPATH') || exit;

use PWT\Bookings\BookingOrchestrator;
use PWT\Bookings\BookingStatus;
use PWT\Bookings\Repositories\BookingDataRepository;
use PWT\Bookings\Services\NotificationService;
use PWT\Core\Database\Schema;
use PWT\Documents\DocumentService;
use PWT\Integrations\BookingCalendarSync;
use PWT\Payments\PaymentService;
use PWT\Services\ResourceProvisioner;

final class OperationsDashboard
{
    public function __construct(
        private readonly BookingDataRepository $bookings,
        private readonly BookingOrchestrator $orchestrator,
        private readonly PaymentService $payments,
        private readonly NotificationService $notifications,
        private readonly DocumentService $documents,
        private readonly ResourceProvisioner $provisioner,
        private readonly BookingCalendarSync $bookingCalendar
    ) {
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'menus'], 35);
        add_action('admin_post_pwt_booking_action', [$this, 'handleBookingAction']);
        add_action('admin_post_pwt_availability_bulk', [$this, 'handleAvailabilityBulk']);
        add_action('admin_post_pwt_sync_booking_calendar_manual', [$this, 'handleBookingCalendarImport']);
    }

    public function menus(): void
    {
        add_submenu_page('pwt-dashboard', __('Operations','wildtours-plugin'), __('Operations','wildtours-plugin'), 'pwt_manage_operations', 'pwt-operations', [$this,'operations']);
        add_submenu_page('pwt-dashboard', __('Availability','wildtours-plugin'), __('Availability','wildtours-plugin'), 'pwt_manage_operations', 'pwt-availability', [$this,'availability']);
        add_submenu_page('pwt-dashboard', __('Pricing','wildtours-plugin'), __('Pricing','wildtours-plugin'), 'pwt_manage_operations', 'pwt-pricing', [$this,'pricing']);
        add_submenu_page('pwt-dashboard', __('Customers','wildtours-plugin'), __('Customers','wildtours-plugin'), 'pwt_manage_operations', 'pwt-customers', [$this,'customers']);
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

        if (isset($_GET['pwt_notice'])) {
            echo '<div class="notice notice-' . esc_attr(sanitize_key((string)$_GET['pwt_notice'])) . ' is-dismissible"><p>'
                . esc_html(stripslashes((string)($_GET['pwt_msg'] ?? ''))) . '</p></div>';
        }
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
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=pwt-availability')); ?>"><?php esc_html_e('Availability Calendar','wildtours-plugin'); ?></a>
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=pwt-pricing')); ?>"><?php esc_html_e('Pricing','wildtours-plugin'); ?></a>
                <a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type=pwt_room_unit')); ?>"><?php esc_html_e('Manage Rooms','wildtours-plugin'); ?></a>
                <a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type=pwt_safari_schedule')); ?>"><?php esc_html_e('Manage Safari Schedules','wildtours-plugin'); ?></a>
            </p>
            <?php $this->bookingTable(); ?>
        </div>
        <?php
    }

    private function bookingTable(): void
    {
        global $wpdb;
        $t = Schema::tables();
        $rows = $wpdb->get_results(
            "SELECT b.*, c.first_name, c.last_name, c.email
             FROM {$t['bookings']} b
             LEFT JOIN {$t['customers']} c ON c.id = b.customer_id
             ORDER BY b.created_at DESC LIMIT 25",
            ARRAY_A
        ) ?: [];
        ?>
        <h2><?php esc_html_e('Recent Bookings','wildtours-plugin'); ?></h2>
        <table class="widefat striped">
            <thead><tr>
                <th><?php esc_html_e('Booking','wildtours-plugin'); ?></th>
                <th><?php esc_html_e('Customer','wildtours-plugin'); ?></th>
                <th><?php esc_html_e('Dates','wildtours-plugin'); ?></th>
                <th><?php esc_html_e('Total','wildtours-plugin'); ?></th>
                <th><?php esc_html_e('Status','wildtours-plugin'); ?></th>
                <th><?php esc_html_e('Actions','wildtours-plugin'); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><a href="<?php echo esc_url(BookingDetailPage::url((int) $r['id'])); ?>"><?php echo esc_html($r['booking_number']); ?></a><br><small>#<?php echo esc_html($r['id']); ?></small></td>
                    <td><a href="<?php echo esc_url(BookingDetailPage::url((int) $r['id'])); ?>"><?php echo esc_html(trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')) ?: ($r['email'] ?? '—')); ?></a><br><small><?php echo esc_html($r['email'] ?? ''); ?></small></td>
                    <td><?php echo esc_html(($r['travel_start'] ?? '') . ' → ' . ($r['travel_end'] ?? '')); ?></td>
                    <td><?php echo esc_html($r['currency'] . ' ' . number_format_i18n((float) $r['total'], 2)); ?></td>
                    <td><?php echo esc_html(BookingStatus::label((string) $r['status'])); ?></td>
                    <td><?php $this->bookingActions((int) $r['id'], (string) $r['status']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    private function bookingActions(int $bookingId, string $status): void
    {
        $nonce = wp_create_nonce('pwt_booking_action');
        $link = static fn (string $action, string $label): string => sprintf(
            '<a class="button button-small" href="%s">%s</a> ',
            esc_url(wp_nonce_url(admin_url('admin-post.php?action=pwt_booking_action&pwt_action=' . $action . '&booking_id=' . $bookingId), 'pwt_booking_action', '_wpnonce')),
            esc_html($label)
        );

        echo '<a class="button button-small" href="' . esc_url(BookingDetailPage::url($bookingId)) . '">' . esc_html__('View', 'wildtours-plugin') . '</a> ';
        echo $link('voucher', __('Voucher', 'wildtours-plugin'));

        if (in_array($status, ['pending', 'held'], true)) {
            echo $link('confirm', __('Confirm', 'wildtours-plugin'));
        }
        if ($status === 'confirmed') {
            echo $link('remind', __('Remind', 'wildtours-plugin'));
        }
        if (in_array($status, ['confirmed', 'paid'], true)) {
            echo $link('refund', __('Refund', 'wildtours-plugin'));
        }
        if (in_array($status, ['pending', 'held', 'confirmed', 'paid'], true)) {
            echo $link('cancel', __('Cancel', 'wildtours-plugin'));
        }
    }

    public function handleBookingAction(): void
    {
        $nonce = (string) ($_REQUEST['_wpnonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'pwt_booking_action')) {
            wp_die(__('Security check failed.', 'wildtours-plugin'));
        }
        if (!current_user_can('pwt_manage_operations')) {
            wp_die(__('You do not have permission to do this.', 'wildtours-plugin'));
        }

        $bookingId = absint($_REQUEST['booking_id'] ?? 0);
        $action = sanitize_key((string) ($_REQUEST['pwt_action'] ?? ''));
        $redirect = admin_url('admin.php?page=pwt-operations');

        switch ($action) {
            case 'confirm':
                $result = $this->orchestrator->confirm($bookingId);
                $this->redirectResult($redirect, $result, __('Booking confirmed.', 'wildtours-plugin'));
                break;

            case 'cancel':
                $result = $this->orchestrator->cancel($bookingId);
                $this->redirectResult($redirect, $result, __('Booking cancelled.', 'wildtours-plugin'));
                break;

            case 'refund':
                $balance = $this->payments->balance($bookingId);
                if ((float) $balance['net_paid'] <= 0) {
                    $this->redirectError($redirect, __('No paid amount to refund.', 'wildtours-plugin'));
                }
                $result = $this->payments->recordRefund([
                    'booking_id' => $bookingId,
                    'amount' => (float) $balance['net_paid'],
                    'provider' => 'manual',
                ]);
                $this->redirectResult($redirect, $result, __('Refund recorded.', 'wildtours-plugin'));
                break;

            case 'remind':
                $this->notifications->paymentReminder($bookingId);
                $this->redirectResult($redirect, true, __('Payment reminder sent.', 'wildtours-plugin'));
                break;

            case 'voucher':
                $document = $this->documents->voucher($bookingId);
                if (empty($document['html'])) {
                    wp_die(__('Booking not found.', 'wildtours-plugin'));
                }
                echo '<p><a href="' . esc_url(BookingDetailPage::url($bookingId)) . '">' . esc_html__('← Back to Booking Details', 'wildtours-plugin') . '</a></p>';
                echo $document['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitized inside templates
                exit;
        }

        $this->redirectError($redirect, __('Unknown action.', 'wildtours-plugin'));
    }

    public function handleAvailabilityBulk(): void
    {
        if (!wp_verify_nonce((string) ($_REQUEST['_wpnonce'] ?? ''), 'pwt_availability_bulk')) {
            wp_die(__('Security check failed.', 'wildtours-plugin'));
        }
        if (!current_user_can('pwt_manage_operations')) {
            wp_die(__('You do not have permission to do this.', 'wildtours-plugin'));
        }

        $type = sanitize_key((string) ($_POST['resource_type'] ?? ''));
        $id = absint($_POST['resource_id'] ?? 0);
        $start = sanitize_text_field((string) ($_POST['start_date'] ?? ''));
        $end = sanitize_text_field((string) ($_POST['end_date'] ?? ''));
        $block = !empty($_POST['block']);
        $capacity = $block ? 0 : max(0, absint($_POST['capacity'] ?? 0));

        $redirect = admin_url('admin.php?page=pwt-availability');
        if (!$type || !$id || !$start || !$end) {
            $this->redirectError($redirect, __('All fields are required.', 'wildtours-plugin'));
        }

        $provisioned = 0;
        $ts = strtotime($start);
        $endTs = strtotime($end);
        while ($ts <= $endTs) {
            $date = gmdate('Y-m-d', $ts);
            if ($this->provisioner->provisionDay($id, $type, $date, $capacity, $block)) {
                $provisioned++;
            }
            $ts += DAY_IN_SECONDS;
        }

        $this->redirectResult(
            $redirect,
            $provisioned > 0,
            sprintf(/* translators: %d: number of days */ __('Updated %d day(s).', 'wildtours-plugin'), $provisioned)
        );
    }

    public function handleBookingCalendarImport(): void
    {
        if (!wp_verify_nonce((string) ($_REQUEST['_wpnonce'] ?? ''), 'pwt_sync_bc')) {
            wp_die(__('Security check failed.', 'wildtours-plugin'));
        }
        if (!current_user_can('pwt_manage_operations')) {
            wp_die(__('You do not have permission to do this.', 'wildtours-plugin'));
        }

        $imported = $this->bookingCalendar->importRecent(100);
        $this->redirectResult(
            admin_url('admin.php?page=pwt-availability'),
            true,
            sprintf(/* translators: %d: number of bookings */ __('Imported %d Booking Calendar booking(s).', 'wildtours-plugin'), $imported)
        );
    }

    private function redirectResult(string $url, mixed $result, string $successMessage): void
    {
        if (is_wp_error($result)) {
            $this->redirectError($url, $result->get_error_message());
        }
        wp_safe_redirect(add_query_arg(['pwt_notice' => 'success', 'pwt_msg' => urlencode($successMessage)], $url));
        exit;
    }

    private function redirectError(string $url, string $message): void
    {
        wp_safe_redirect(add_query_arg(['pwt_notice' => 'error', 'pwt_msg' => urlencode($message)], $url));
        exit;
    }

    public function availability(): void
    {
        $this->availabilityCalendar();
    }

    private function availabilityCalendar(): void
    {
        global $wpdb;
        $t = Schema::tables();
        $settings = get_option('pwt_settings', []);

        $month = max(1, min(12, absint($_GET['month'] ?? gmdate('n'))));
        $year = max(2000, min(2100, absint($_GET['year'] ?? gmdate('Y'))));
        $resourceType = sanitize_key((string) ($_GET['resource_type'] ?? 'room_unit'));
        $resourceId = absint($_GET['resource_id'] ?? 0);

        $resourceTypes = ['room_unit', 'safari_schedule', 'vehicle'];

        $first = sprintf('%04d-%02d-01', $year, $month);
        $startTs = strtotime($first);
        $prev = strtotime($first . ' -1 month');
        $next = strtotime($first . ' +1 month');

        $daysInMonth = (int) gmdate('t', $startTs);
        $rows = $resourceId ? ($wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$t['availability']} WHERE resource_type=%s AND resource_id=%d AND service_date BETWEEN %s AND %s",
            $resourceType, $resourceId, $first, sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth)
        ), ARRAY_A) ?: []) : [];

        $dayMap = [];
        foreach ($rows as $r) {
            $dayMap[(string) $r['service_date']] = $r;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Availability Calendar','wildtours-plugin'); ?></h1>

            <?php if (!empty($_GET['pwt_notice'])): ?>
                <div class="notice notice-<?php echo esc_attr(sanitize_key((string) $_GET['pwt_notice'])); ?> is-dismissible"><p><?php echo esc_html(stripslashes((string) ($_GET['pwt_msg'] ?? ''))); ?></p></div>
            <?php endif; ?>

            <form method="get" style="margin:12px 0;">
                <input type="hidden" name="page" value="pwt-availability">
                <label><?php esc_html_e('Resource type', 'wildtours-plugin'); ?>
                    <select name="resource_type">
                        <?php foreach ($resourceTypes as $type): ?>
                            <option value="<?php echo esc_attr($type); ?>" <?php selected($resourceType, $type); ?>><?php echo esc_html($type); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><?php esc_html_e('Resource ID', 'wildtours-plugin'); ?>
                    <input type="number" name="resource_id" value="<?php echo esc_attr($resourceId ?: ''); ?>" min="0" placeholder="All">
                </label>
                <button class="button"><?php esc_html_e('Show', 'wildtours-plugin'); ?></button>
            </form>

            <p>
                <a class="button" href="<?php echo esc_url(add_query_arg(['month' => (int) gmdate('n', $prev), 'year' => (int) gmdate('Y', $prev)])); ?>">&larr; <?php esc_html_e('Prev', 'wildtours-plugin'); ?></a>
                <strong style="margin:0 12px;"><?php echo esc_html(gmdate('F Y', $startTs)); ?></strong>
                <a class="button" href="<?php echo esc_url(add_query_arg(['month' => (int) gmdate('n', $next), 'year' => (int) gmdate('Y', $next)])); ?>"><?php esc_html_e('Next', 'wildtours-plugin'); ?> &rarr;</a>
            </p>

            <table class="widefat" style="table-layout:fixed;">
                <thead><tr>
                    <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d): ?>
                        <th style="text-align:center;"><?php echo esc_html($d); ?></th>
                    <?php endforeach; ?>
                </tr></thead>
                <tbody><tr>
                <?php
                $padding = (int) gmdate('N', $startTs) - 1;
                for ($i = 0; $i < $padding; $i++) {
                    echo '<td></td>';
                }
                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
                    $row = $dayMap[$date] ?? null;
                    $remaining = $row
                        ? max(0, (int) $row['capacity'] - (int) $row['reserved'] - (int) $row['blocked'])
                        : null;
                    $label = $remaining === null ? '—' : (string) $remaining;
                    $class = $remaining === null ? 'pwt-cal-unprovisioned' : ($remaining > 0 ? 'pwt-cal-open' : 'pwt-cal-full');
                    echo '<td class="' . esc_attr($class) . '" style="height:64px;vertical-align:top;text-align:center;padding:6px;border:1px solid #e2e6ea;">';
                    echo '<strong>' . esc_html((string) $d) . '</strong><br><span>' . esc_html($label) . '</span>';
                    echo '</td>';
                    if (((int) gmdate('N', strtotime($date)) % 7) === 0) {
                        echo '</tr><tr>';
                    }
                }
                ?>
                </tr></tbody>
            </table>

            <style>
                .pwt-cal-open { background: #e8f7ee; }
                .pwt-cal-full { background: #fdeaea; }
                .pwt-cal-unprovisioned { background: #f7f8f9; color: #a0a9b2; }
            </style>

            <h2><?php esc_html_e('Bulk Provision / Block','wildtours-plugin'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="pwt_availability_bulk">
                <?php wp_nonce_field('pwt_availability_bulk', '_wpnonce'); ?>
                <input type="hidden" name="resource_type" value="<?php echo esc_attr($resourceType); ?>">
                <input type="number" name="resource_id" required min="1" placeholder="<?php esc_attr_e('Resource ID', 'wildtours-plugin'); ?>">
                <input type="date" name="start_date" required>
                <input type="date" name="end_date" required>
                <input type="number" name="capacity" min="0" placeholder="<?php esc_attr_e('Capacity', 'wildtours-plugin'); ?>">
                <label><input type="checkbox" name="block" value="1"> <?php esc_html_e('Block (capacity 0)', 'wildtours-plugin'); ?></label>
                <button class="button button-primary"><?php esc_html_e('Apply', 'wildtours-plugin'); ?></button>
            </form>

            <p>
                <strong><?php esc_html_e('Booking Calendar sync', 'wildtours-plugin'); ?>:</strong>
                <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=pwt_sync_booking_calendar_manual'), 'pwt_sync_bc')); ?>"><?php esc_html_e('Import now', 'wildtours-plugin'); ?></a>
            </p>
        </div>
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