<?php

namespace PWT\Admin;

defined('ABSPATH') || exit;

use PWT\Core\Paths;

class Menu
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'menu']);
    }

    public function menu(): void
    {
        add_menu_page(
            __('Panna Wild Tour', 'wildtours-plugin'),
            __('Panna Wild Tour', 'wildtours-plugin'),
            'manage_options',
            'pwt-dashboard',
            [$this, 'dashboard'],
            'dashicons-palmtree',
            30
        );

        // Essential submenu items
        add_submenu_page(
            'pwt-dashboard',
            __('Dashboard', 'wildtours-plugin'),
            __('Dashboard', 'wildtours-plugin'),
            'manage_options',
            'pwt-dashboard',
            [$this, 'dashboard']
        );

        add_submenu_page(
            'pwt-dashboard',
            __('Settings', 'wildtours-plugin'),
            __('Settings', 'wildtours-plugin'),
            'manage_options',
            'pwt-settings',
            [$this, 'settings']
        );

        add_submenu_page(
            'pwt-dashboard',
            __('Content Forms', 'wildtours-plugin'),
            __('Content Forms', 'wildtours-plugin'),
            'manage_options',
            'pwt-content-forms',
            [$this, 'contentForms']
        );

        add_submenu_page(
            'pwt-dashboard',
            __('Reports', 'wildtours-plugin'),
            __('Reports', 'wildtours-plugin'),
            'manage_options',
            'pwt-reports',
            [$this, 'reports']
        );

        add_submenu_page(
            'pwt-dashboard',
            __('My Dashboard', 'wildtours-plugin'),
            __('My Dashboard', 'wildtours-plugin'),
            'manage_options',
            'pwt-my-dashboard',
            [$this, 'myDashboard']
        );

        add_submenu_page(
            'pwt-dashboard',
            __('Staff', 'wildtours-plugin'),
            __('Staff', 'wildtours-plugin'),
            'manage_options',
            'pwt-staff',
            [$this, 'staff']
        );

        // Operations and utility submenu items
        add_submenu_page(
            'pwt-dashboard',
            __('Operations', 'wildtours-plugin'),
            __('Operations', 'wildtours-plugin'),
            'manage_options',
            'pwt-operations',
            [$this, 'operations']
        );

        // Separator - WordPress adds this automatically between groups
        // Availability and Pricing are accessible via the Operations page quick links
}

    public function dashboard(): void
    {
        include Paths::path('app/Admin/Views/dashboard.php');
    }

    public function settings(): void
    {
        include Paths::path('app/Admin/Views/settings.php');
    }

    public function contentForms(): void
    {
        include Paths::path('app/Admin/Views/content-forms.php');
    }

    public function reports(): void
    {
        include Paths::path('app/Admin/Views/reports.php');
    }

    public function operations(): void
    {
        include Paths::path('app/Admin/Views/operations.php');
    }

    public function myDashboard(): void
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
        $users = get_users(['role__in'=>array_keys(\PWT\Staff\Roles\RoleRegistrar::ROLES),'orderby'=>'registered','order'=>'DESC']);
        ?> <div class="wrap"><h1><?php esc_html_e('PWT Staff','wildtours-plugin'); ?></h1><?php if(isset($_GET['pwt_staff_created'])): ?><div class="notice <?php echo ((int)$_GET['pwt_staff_created']===1)?'notice-success':'notice-error'; ?>"><p><?php echo ((int)$_GET['pwt_staff_created']===1)?esc_html__('Staff account created. WordPress has sent the new-user password setup email.','wildtours-plugin'):esc_html(rawurldecode((string)($_GET['pwt_staff_error']??__('Unable to create staff user.','wildtours-plugin')))); ?></p></div><?php endif; ?><h2><?php esc_html_e('Create Staff User','wildtours-plugin'); ?></h2><form method="post"><input type="hidden" name="pwt_staff_action" value="create"><?php wp_nonce_field('pwt_create_staff'); ?><table class="form-table"><tr><th><label for="pwt-name"><?php esc_html_e('Full name','wildtours-plugin'); ?></label></th><td><input id="pwt-name" class="regular-text" name="name" required></td></tr><tr><th><label for="pwt-email"><?php esc_html_e('Email','wildtours-plugin'); ?></label></th><td><input id="pwt-email" class="regular-text" type="email" name="email" required></td></tr><tr><th><label for="pwt-role"><?php esc_html_e('Role','wildtours-plugin'); ?></label></th><td><select id="pwt-role" name="role" required><?php foreach(\PWT\Staff\Roles\RoleRegistrar::ROLES as $slug=>$label) echo '<option value="'.esc_attr($slug).'">'.esc_html__($label,'wildtours-plugin').'</option>'; ?></select></td></tr></table><?php submit_button(__('Create Staff User','wildtours-plugin')); ?></form><h2><?php esc_html_e('Current Staff','wildtours-plugin'); ?></h2><table class="widefat striped"><thead><tr><th><?php esc_html_e('Name','wildtours-plugin'); ?></th><th><?php esc_html_e('Email','wildtours-plugin'); ?></th><th><?php esc_html_e('Role','wildtours-plugin'); ?></th><th><?php esc_html_e('Created','wildtours-plugin'); ?></th></tr></thead><tbody><?php foreach($users as $user){$role=(string)array_values(array_intersect(array_keys(\PWT\Staff\Roles\RoleRegistrar::ROLES),(array)$user->roles))[0]??'';... (line truncated to 2000 chars)