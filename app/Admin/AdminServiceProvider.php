<?php

declare(strict_types=1);

namespace PWT\Admin;

defined('ABSPATH') || exit;

use PWT\Core\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        if (!is_admin()) {
            return;
        }

        (new Menu())->register();

        (new Assets())->register();

        (new Settings())->register();

        // Register single Settings top-level menu with three tabs
        add_menu_page(
            __('Settings', 'wildtours-plugin'),
            __('Settings', 'wildtours-plugin'),
            'manage_options',
            'pwt-settings',
            [$this->make(Settings::class), 'renderSettingsPage'],
            'dashicons-safari',
            30
        );

        (new ContentSeeder())->register();
    }
}