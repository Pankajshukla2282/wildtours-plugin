<?php

namespace PWT\Integrations;

use PWT\Core\ServiceProvider;

defined('ABSPATH') || exit;

class IntegrationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        (new FluentContentIntake())->register();
    }
}
