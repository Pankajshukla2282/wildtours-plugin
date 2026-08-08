<?php
declare(strict_types=1);
namespace PWT\Architecture;
defined('ABSPATH') || exit;
use PWT\Core\ServiceProvider;
use PWT\Availability\AvailabilityRepository;
use PWT\Availability\AvailabilityService;
use PWT\Bookings\Repositories\BookingDataRepository;
use PWT\Bookings\Repositories\BookingItemRepository;
use PWT\Customers\CustomerRepository;
use PWT\Inventory\InventoryService;
use PWT\Payments\PaymentRepository;
use PWT\Pricing\PricingService;
use PWT\Pricing\RateRepository;

final class ArchitectureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(AvailabilityRepository::class, AvailabilityRepository::class);
        $this->singleton(AvailabilityService::class, AvailabilityService::class);
        $this->singleton(BookingDataRepository::class, BookingDataRepository::class);
        $this->singleton(BookingItemRepository::class, BookingItemRepository::class);
        $this->singleton(CustomerRepository::class, CustomerRepository::class);
        $this->singleton(InventoryService::class, InventoryService::class);
        $this->singleton(PaymentRepository::class, PaymentRepository::class);
        $this->singleton(RateRepository::class, RateRepository::class);
        $this->singleton(PricingService::class, PricingService::class);
    }

    public function boot(): void
    {
        // Domain services are intentionally hook-light. Controllers/API consume them.
    }
}
