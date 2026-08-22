<?php

declare(strict_types=1);

namespace PWT\Admin;

defined('ABSPATH') || exit;

use PWT\Core\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(
            AvailabilityCalendarPage::class,
            AvailabilityCalendarPage::class
        );
    }

    public function boot(): void
    {
        if (!is_admin()) {
            return;
        }

        (new Menu())->register();

        (new Assets())->register();

        (new Settings())->register();

        (new ContentSeeder())->register();

        $this->make(
            AvailabilityCalendarPage::class
        )->register();
    }
}