<?php
declare(strict_types=1);
namespace PWT\Staff\Admin;
defined('ABSPATH') || exit;
use PWT\Staff\Roles\Capabilities;
use PWT\Staff\Roles\RoleRegistrar;
use PWT\Staff\Services\StaffService;

final class StaffAdmin
{
    private StaffService $service;
    public function __construct(StaffService $service) { $this->service = $service; }
    public function register(): void { add_action('admin_menu', [$this,'menus'], 36); add_action('admin_init', [$this,'handleCreate']); }
    public function menus(): void
    {
        add_menu_page(__('My PWT Work','wildtours-plugin'),__('My PWT Work','wildtours-plugin'),Capabilities::ACCESS_DASHBOARD,'pwt-staff-dashboard',[$this,'dashboard'],'dashicons-groups',31);
        add_submenu_page('pwt-staff-dashboard',__('My Dashboard','wildtours-plugin'),__('My Dashboard','wildtours-plugin'),Capabilities::ACCESS_DASHBOARD,'pwt-staff-dashboard',[$this,'dashboard']);
        add_submenu_page('pwt-staff-dashboard',__('Staff','wildtours-plugin'),__('Staff','wildtours-plugin'),Capabilities::MANAGE_STAFF,'pwt-staff',[$this,'staff']);
    }
    public function handleCreate(): void
    {
        if (!isset($_POST['pwt_staff_action']) || $_POST['pwt_staff_action'] !== 'create') return;
        if (!current_user_can(Capabilities::MANAGE_STAFF)) wp_die(esc_html__('You are not allowed to manage staff.','wildtours-plugin'));
        check_admin_referer('pwt_create_staff');
        $result = $this->service->create(['name'=>wp_unslash($_POST['name'] ?? ''),'email'=>wp_unslash($_POST['email'] ?? ''),'role'=>wp_unslash($_POST['role'] ?? '')]);
        $url = add_query_arg(['page'=>'pwt-staff','pwt_staff_created'=>is_wp_error($result)?0:1,'pwt_staff_error'=>is_wp_error($result)?rawurlencode($result->get_error_message()):''], admin_url('admin.php'));
        wp_safe_redirect($url); exit;
    }
    public function dashboard(): void
    {
        $u = wp_get_current_user(); $caps = array_keys(array_filter((array)$u->allcaps));
        ?> <div class="wrap"><h1><?php esc_html_e('My Panna Wild Tour Work','wildtours-plugin'); ?></h1><p><?php echo esc_html(sprintf(__('Welcome %s. This dashboard shows the work areas assigned to your role.','wildtours-plugin'), $u->display_name)); ?></p><div class="pwt-admin-cards"><?php
        $areas=[Capabilities::MANAGE_BOOKINGS=>['Bookings','edit.php?post_type=pwt_booking'],Capabilities::MANAGE_SAFARI=>['Safari Operations','edit.php?post_type=pwt_safari_schedule'],Capabilities::MANAGE_ACCOMMODATION=>['Accommodation','edit.php?post_type=pwt_resort'],Capabilities::MANAGE_TRANSPORT=>['Transport','edit.php?post_type=pwt_vehicle'],Capabilities::MANAGE_PAYMENTS=>['Payments','admin.php?page=pwt-payments'],Capabilities::VIEW_REPORTS=>['Reports','admin.php?page=pwt-reports'],Capabilities::MANAGE_CONTENT=>['Travel Content','edit.php']];
        foreach($areas as $cap=>$a) if(in_array($cap,$caps,true) || current_user_can('manage_options')) echo '<div class="pwt-admin-card"><strong>'.esc_html($a[0]).'</strong><span><a class="button" href="'.esc_url(admin_url($a[1])).'">'.esc_html__('Open','wildtours-plugin').'</a></span></div>';
        ?></div><h2><?php esc_html_e('Daily checklist','wildtours-plugin'); ?></h2><ul class="ul-disc"><li><?php esc_html_e('Review today’s confirmed bookings and customer requirements.','wildtours-plugin'); ?></li><li><?php esc_html_e('Confirm safari, accommodation and transport dependencies assigned to you.','wildtours-plugin'); ?></li><li><?php esc_html_e('Update operational status immediately when work is completed.','wildtours-plugin'); ?></li><li><?php esc_html_e('Escalate payment, availability or customer issues to the Operations Manager.','wildtours-plugin'); ?></li></ul></div><?php
    }
    public function staff(): void
    {
        if (!current_user_can(Capabilities::MANAGE_STAFF)) wp_die(esc_html__('You are not allowed to manage staff.','wildtours-plugin'));
        $users = get_users(['role__in'=>array_keys(RoleRegistrar::ROLES),'orderby'=>'registered','order'=>'DESC']);
        ?> <div class="wrap"><h1><?php esc_html_e('PWT Staff','wildtours-plugin'); ?></h1><?php if(isset($_GET['pwt_staff_created'])): ?><div class="notice <?php echo ((int)$_GET['pwt_staff_created']===1)?'notice-success':'notice-error'; ?>"><p><?php echo ((int)$_GET['pwt_staff_created']===1)?esc_html__('Staff account created. WordPress has sent the new-user password setup email.','wildtours-plugin'):esc_html(rawurldecode((string)($_GET['pwt_staff_error']??__('Unable to create staff user.','wildtours-plugin')))); ?></p></div><?php endif; ?><h2><?php esc_html_e('Create Staff User','wildtours-plugin'); ?></h2><form method="post"><input type="hidden" name="pwt_staff_action" value="create"><?php wp_nonce_field('pwt_create_staff'); ?><table class="form-table"><tr><th><label for="pwt-name"><?php esc_html_e('Full name','wildtours-plugin'); ?></label></th><td><input id="pwt-name" class="regular-text" name="name" required></td></tr><tr><th><label for="pwt-email"><?php esc_html_e('Email','wildtours-plugin'); ?></label></th><td><input id="pwt-email" class="regular-text" type="email" name="email" required></td></tr><tr><th><label for="pwt-role"><?php esc_html_e('Role','wildtours-plugin'); ?></label></th><td><select id="pwt-role" name="role" required><?php foreach(RoleRegistrar::ROLES as $slug=>$label) echo '<option value="'.esc_attr($slug).'">'.esc_html__($label,'wildtours-plugin').'</option>'; ?></select></td></tr></table><?php submit_button(__('Create Staff User','wildtours-plugin')); ?></form><h2><?php esc_html_e('Current Staff','wildtours-plugin'); ?></h2><table class="widefat striped"><thead><tr><th><?php esc_html_e('Name','wildtours-plugin'); ?></th><th><?php esc_html_e('Email','wildtours-plugin'); ?></th><th><?php esc_html_e('Role','wildtours-plugin'); ?></th><th><?php esc_html_e('Created','wildtours-plugin'); ?></th></tr></thead><tbody><?php foreach($users as $user){$role=(string)array_values(array_intersect(array_keys(RoleRegistrar::ROLES),(array)$user->roles))[0]??''; echo '<tr><td><a href="'.esc_url(get_edit_user_link($user->ID)).'">'.esc_html($user->display_name).'</a></td><td>'.esc_html($user->user_email).'</td><td>'.esc_html(RoleRegistrar::labelFor($role)).'</td><td>'.esc_html($user->user_registered).'</td></tr>'; } ?></tbody></table></div><?php
    }
}
