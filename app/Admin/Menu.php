<?php

declare(strict_types=1);

namespace PWT\Admin;

defined('ABSPATH') || exit;

use PWT\Core\Paths;

/**
 * Registers the main Panna Wild Tour administration menu.
 */
final class Menu
{
    /**
     * Register WordPress admin menu hooks.
     */
    public function register(): void
    {
        add_action(
            'admin_menu',
            [$this, 'menu']
        );
    }

    /**
     * Register the main plugin menu and core submenu pages.
     */
    public function menu(): void
    {
        add_menu_page(
            __('Panna Wild Tour', 'wildtours-plugin'),
            __('Panna Wild Tour', 'wildtours-plugin'),
            'pwt_manage_operations',
            PWT_ADMIN_MENU_SLUG,
            [$this, 'dashboard'],
            'dashicons-palmtree',
            30
        );

        add_submenu_page(
            PWT_ADMIN_MENU_SLUG,
            __('Dashboard', 'wildtours-plugin'),
            __('Dashboard', 'wildtours-plugin'),
            'pwt_manage_operations',
            PWT_ADMIN_MENU_SLUG,
            [$this, 'dashboard']
        );

        add_submenu_page(
            PWT_ADMIN_MENU_SLUG,
            __('Settings', 'wildtours-plugin'),
            __('Settings', 'wildtours-plugin'),
            'manage_options',
            'pwt-settings',
            [$this, 'settings']
        );

        add_submenu_page(
            PWT_ADMIN_MENU_SLUG,
            __('Content Forms', 'wildtours-plugin'),
            __('Content Forms', 'wildtours-plugin'),
            'manage_options',
            'pwt-content-forms',
            [$this, 'contentForms']
        );
    }

    /**
     * Render the plugin dashboard.
     */
    public function dashboard(): void
    {
        include Paths::path(
            'app/Admin/Views/dashboard.php'
        );
    }

    /**
     * Render plugin settings.
     */
    public function settings(): void
    {
        include Paths::path(
            'app/Admin/Views/settings.php'
        );
    }

    /**
     * Render content forms.
     */
    public function contentForms(): void
    {
        include Paths::path(
            'app/Admin/Views/content-forms.php'
        );
    }
}