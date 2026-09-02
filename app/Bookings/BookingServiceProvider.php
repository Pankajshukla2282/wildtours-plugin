<?php
declare(strict_types=1);
namespace PWT\Bookings;
defined('ABSPATH') || exit;

use PWT\Core\ServiceProvider;
use PWT\Bookings\Controllers\BookingController;
use PWT\Bookings\Repositories\BookingRepository;
use PWT\Bookings\Services\NotificationService;
use PWT\Availability\AvailabilityRepository;
use PWT\Pricing\PricingService;

final class BookingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(BookingRepository::class, BookingRepository::class);
        $this->singleton(BookingItemRepository::class, BookingItemRepository::class);
        $this->singleton(BookingOrchestrator::class, BookingOrchestrator::class);
    }
    public function boot(): void
    {
        $this->make(BookingController::class)->register();

        $notifications = $this->make(NotificationService::class);
        add_action('pwt/booking/confirmed', [$notifications, 'confirmation'], 10, 1);
        add_action('pwt/booking/paid', [$notifications, 'confirmation'], 10, 1);
        add_action('pwt/booking/cancelled', [$notifications, 'cancellation'], 10, 1);
    }
}
