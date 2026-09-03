<?php
declare(strict_types=1);
namespace PWT\Staff\Admin;
defined('ABSPATH') || exit;
use PWT\Staff\Operations\OperationsRepository;
use PWT\Staff\Roles\Capabilities;

final class OperationsDashboard {
 public function register(): void { add_action('admin_menu',[$this,'menu']); }
 public function menu(): void { }
 public function render(): void { if(!current_user_can(Capabilities::ACCESS_DASHBOARD)) wp_die(esc_html__('You do not have permission to access this page.','wildtours-plugin')); $bookings=(new OperationsRepository())->assignedBookings(get_current_user_id()); echo '<div class="wrap"><h1>'.esc_html__('My PWT Work','wildtours-plugin').'</h1><p>'.esc_html__('Bookings currently assigned to you for operational follow-up.','wildtours-plugin').'</p><table class="widefat striped"><thead><tr><th>'.esc_html__('Booking','wildtours-plugin').'</th><th>'.esc_html__('Status','wildtours-plugin').'</th><th>'.esc_html__('Due','wildtours-plugin').'</th><th>'.esc_html__('Last Updated','wildtours-plugin').'</th></tr></thead><tbody>'; if(!$bookings) echo '<tr><td colspan="4">'.esc_html__('No bookings are currently assigned to you.','wildtours-plugin').'</td></tr>'; foreach($bookings as $b){$d=(new OperationsRepository())->get($b->ID); echo '<tr><td><a href="'.esc_url(get_edit_post_link($b->ID)).'">'.esc_html(get_the_title($b)).'</a></td><td>'.esc_html(ucwords(str_replace('_',' ',$d['status']))).'</td><td>'.esc_html($d['due_date']?:'—').'</td><td>'.esc_html(get_the_modified_date('', $b)).'</td></tr>'; } echo '</tbody></table><h2>'.esc_html__('Daily checklist','wildtours-plugin').'</h2><ul><li>Review assigned bookings.</li><li>Confirm safari, accommodation and transport dependencies.</li><li>Update operational status and handover notes.</li><li>Escalate blocked items to the Operations Manager.</li></ul></div>'; }
}
