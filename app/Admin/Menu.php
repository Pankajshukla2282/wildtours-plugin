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
}