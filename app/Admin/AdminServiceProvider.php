<?php

namespace PWT\Admin;

use PWT\Core\ServiceProvider;

defined('ABSPATH') || exit;

class AdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (!is_admin()) {
            return;
        }

        (new Menu())->register();

        (new Assets())->register();

        (new Settings())->register();

        (new ContentSeeder())->register();
    }
}