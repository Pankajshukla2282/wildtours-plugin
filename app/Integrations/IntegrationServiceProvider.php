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

        $this->singleton(BookingCalendarSync::class, BookingCalendarSync::class);
        add_action('pwt_sync_booking_calendar', [$this->make(BookingCalendarSync::class), 'importRecent']);
        if (!wp_next_scheduled('pwt_sync_booking_calendar')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', 'pwt_sync_booking_calendar');
        }
    }
}