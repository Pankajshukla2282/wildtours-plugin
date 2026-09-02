<?php

namespace PWT\Analytics;

use PWT\Core\ServiceProvider;

defined('ABSPATH') || exit;

class AnalyticsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        (new Tracker())->register();
    }
}
