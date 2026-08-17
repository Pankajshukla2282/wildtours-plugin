<?php

namespace PWT\Integrations;

use PWT\Core\ServiceProvider;
use PWT\Bookings\Services\BookingService;

defined('ABSPATH') || exit;

class IntegrationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        (new FluentContentIntake())->register();
        (new FluentNewsletterHandler())->register();
        (new FluentContactHandler())->register();
        (new FluentBookingHandler($this->make(BookingService::class)))->register();
    }
}