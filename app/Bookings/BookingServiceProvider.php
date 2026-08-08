<?php
declare(strict_types=1);
namespace PWT\Bookings;
defined('ABSPATH') || exit;

use PWT\Core\ServiceProvider;
use PWT\Bookings\Repositories\BookingRepository;
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
    public function boot(): void {}
}
