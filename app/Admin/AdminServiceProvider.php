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

        // Register three separate settings submenu pages
        add_submenu_page(
            'pwt-dashboard',
            __('General', 'wildtours-plugin'),
            __('General', 'wildtours-plugin'),
            'manage_options',
            'pwt-settings-general',
            [$this->make(Settings::class), 'renderGeneralPage']
        );

        add_submenu_page(
            'pwt-dashboard',
            __('Payments', 'wildtours-plugin'),
            __('Payments', 'wildtours-plugin'),
            'manage_options',
            'pwt-settings-payments',
            [$this->make(Settings::class), 'renderPaymentsPage']
        );

        add_submenu_page(
            'pwt-dashboard',
            __('Availability', 'wildtours-plugin'),
            __('Availability', 'wildtours-plugin'),
            'manage_options',
            'pwt-settings-availability',
            [$this->make(Settings::class), 'renderAvailabilityPage']
        );

        (new ContentSeeder())->register();
    }
}