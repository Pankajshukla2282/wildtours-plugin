<?php

namespace PWT\Integrations;

use PWT\Bookings\Services\BookingService;
use PWT\Core\ServiceProvider;

defined('ABSPATH') || exit;

class IntegrationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        (new FluentContentIntake())->register();
        (new FluentBookingHandler($this->make(BookingService::class)))->register();
        (new FluentContactHandler())->register();
    }
}
