<?php
declare(strict_types=1);
namespace PWT\Customers;
defined('ABSPATH') || exit;
use PWT\Core\Database\Schema;

final class CustomerAdminPage
{
    public function __construct(private readonly CustomerRepository $customers, private readonly TravelerRepository $travelers) {}
    public function register(): void
    {
        add_action('admin_menu', [$this, 'menu'], 34);
        add_action('admin_post_pwt_customer_save', [$this, 'save']);
    }
    public function menu(): void
    {
        add_submenu_page(PWT_ADMIN_MENU_SLUG, __('Customers','wildtours-plugin'), __('Customers','wildtours-plugin'), 'pwt_manage_operations', 'pwt-customers', [$this,'render']);
    }
    public function render(): void
    {
        if (!current_user_can('pwt_manage_operations')) wp_die(__('You do not have permission to manage customers.','wildtours-plugin'));
        $id=absint($_GET['customer_id'] ?? 0);
        if ($id) { $this->detail($id); return; }
        $term=sanitize_text_field((string)($_GET['s'] ?? ''));
        $rows=$this->customers->search($term,100);
        ?>
        <div class="wrap"><h1><?php esc_html_e('Customers','wildtours-plugin'); ?></h1>
        <form method="get"><input type="hidden" name="page" value="pwt-customers"><p class="search-box"><label class="screen-reader-text" for="pwt-customer-search"><?php esc_html_e('Search Customers','wildtours-plugin'); ?></label><input id="pwt-customer-search" name="s" value="<?php echo esc_attr($term); ?>" type="search"><input class="button" type="submit" value="<?php esc_attr_e('Search','wildtours-plugin'); ?>"></p></form>
        <table class="widefat striped"><thead><tr><th><?php esc_html_e('Customer','wildtours-plugin'); ?></th><th>Email</th><th>Phone</th><th>Country</th><th>Bookings</th><th>Last Updated</th></tr></thead><tbody>
        <?php global $wpdb; $t=Schema::tables(); foreach($rows as $r): $count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['bookings']} WHERE customer_id=%d",$r['id'])); ?>
        <tr><td><a href="<?php echo esc_url(admin_url('admin.php?page=pwt-customers&customer_id='.(int)$r['id'])); ?>"><?php echo esc_html(trim(($r['first_name']??'').' '.($r['last_name']??'')) ?: __('Customer','wildtours-plugin')); ?></a></td><td><?php echo esc_html($r['email']??''); ?></td><td><?php echo esc_html($r['phone']??''); ?></td><td><?php echo esc_html($r['country']??''); ?></td><td><?php echo esc_html($count); ?></td><td><?php echo esc_html($r['updated_at']??''); ?></td></tr>
        <?php endforeach; ?></tbody></table></div><?php
    }
    private function detail(int $id): void
    {
        $c=$this->customers->find($id); if(!$c) wp_die(__('Customer not found.','wildtours-plugin'));
        global $wpdb; $t=Schema::tables(); $bookings=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$t['bookings']} WHERE customer_id=%d ORDER BY created_at DESC",$id),ARRAY_A)?:[];
        $travelerCount=0; foreach($bookings as $b) $travelerCount += $this->travelers->count((int)$b['id']);
        ?><div class="wrap"><h1><?php echo esc_html(trim(($c['first_name']??'').' '.($c['last_name']??''))); ?></h1><p><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=pwt-customers')); ?>">&larr; <?php esc_html_e('Back to Customers','wildtours-plugin'); ?></a></p>
        <div class="pwt-admin-cards"><div class="pwt-admin-card"><strong><?php echo esc_html(count($bookings)); ?></strong><span><?php esc_html_e('Bookings','wildtours-plugin'); ?></span></div><div class="pwt-admin-card"><strong><?php echo esc_html($travelerCount); ?></strong><span><?php esc_html_e('Traveller Records','wildtours-plugin'); ?></span></div></div>
        <h2><?php esc_html_e('Customer Details','wildtours-plugin'); ?></h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="pwt_customer_save"><input type="hidden" name="customer_id" value="<?php echo esc_attr($id); ?>"><?php wp_nonce_field('pwt_customer_save'); ?><table class="form-table">
        <?php foreach(['first_name'=>'First Name','last_name'=>'Last Name','email'=>'Email','phone'=>'Phone','country'=>'Country','city'=>'City'] as $key=>$label): ?><tr><th><label><?php echo esc_html__($label,'wildtours-plugin'); ?></label></th><td><input class="regular-text" type="<?php echo $key==='email'?'email':'text'; ?>" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr((string)($c[$key]??'')); ?>"></td></tr><?php endforeach; ?>
        <tr><th><label><?php esc_html_e('Notes','wildtours-plugin'); ?></label></th><td><textarea class="large-text" rows="4" name="notes"><?php echo esc_textarea((string)($c['notes']??'')); ?></textarea></td></tr></table><?php submit_button(__('Save Customer','wildtours-plugin')); ?></form>
        <h2><?php esc_html_e('Booking History','wildtours-plugin'); ?></h2><table class="widefat striped"><thead><tr><th>Booking</th><th>Travel</th><th>Total</th><th>Status</th></tr></thead><tbody><?php foreach($bookings as $b): ?><tr><td><a href="<?php echo esc_url(admin_url('admin.php?page=pwt-booking&booking_id='.(int)$b['id'])); ?>"><?php echo esc_html($b['booking_number']); ?></a></td><td><?php echo esc_html(($b['travel_start']?:'—').' → '.($b['travel_end']?:'—')); ?></td><td><?php echo esc_html(($b['currency']??'INR').' '.number_format_i18n((float)$b['total'],2)); ?></td><td><?php echo esc_html($b['status']); ?></td></tr><?php endforeach; ?></tbody></table>
        <h2><?php esc_html_e('Travellers','wildtours-plugin'); ?></h2><table class="widefat striped"><thead><tr><th>Name</th><th>Nationality</th><th>Date of Birth</th><th>Booking</th></tr></thead><tbody><?php foreach($bookings as $b): foreach($this->travelers->byBooking((int)$b['id']) as $tr): ?><tr><td><?php echo esc_html(trim(($tr['first_name']??'').' '.($tr['last_name']??''))); ?></td><td><?php echo esc_html($tr['nationality']??''); ?></td><td><?php echo esc_html($tr['date_of_birth']??''); ?></td><td><a href="<?php echo esc_url(admin_url('admin.php?page=pwt-booking&booking_id='.(int)$b['id'])); ?>"><?php echo esc_html($b['booking_number']); ?></a></td></tr><?php endforeach; endforeach; ?></tbody></table></div><?php
    }
    public function save(): void
    {
        if(!current_user_can('pwt_manage_operations')) wp_die(__('Permission denied.','wildtours-plugin'));
        check_admin_referer('pwt_customer_save'); $id=absint($_POST['customer_id']??0);
        if(!$id || !$this->customers->update($id,$_POST)) { wp_safe_redirect(add_query_arg(['page'=>'pwt-customers','customer_id'=>$id,'pwt_notice'=>'error'],admin_url('admin.php'))); exit; }
        wp_safe_redirect(add_query_arg(['page'=>'pwt-customers','customer_id'=>$id,'pwt_notice'=>'updated'],admin_url('admin.php'))); exit;
    }
}
