<?php
declare(strict_types=1);
namespace PWT\Availability;
defined('ABSPATH') || exit;

use PWT\Core\ServiceProvider;

final class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(AvailabilityRepository::class, AvailabilityRepository::class);
        $this->singleton(HoldRepository::class, HoldRepository::class);
        $this->singleton(HoldService::class, HoldService::class);
    }
    public function boot(): void
    {
        add_action('pwt_cleanup_inventory_holds', [$this->make(HoldRepository::class), 'expire']);
        if (!wp_next_scheduled('pwt_cleanup_inventory_holds')) {
            wp_schedule_event(time() + 300, 'hourly', 'pwt_cleanup_inventory_holds');
        }
    }
}
