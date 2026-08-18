<?php
declare(strict_types=1);
namespace PWT\Services;
defined('ABSPATH') || exit;
use PWT\Core\ServiceProvider;
use PWT\Availability\AvailabilityRepository;

final class ServiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(ResourceProvisioner::class, ResourceProvisioner::class);
    }
    public function boot(): void
    {
        add_action('pwt_sync_resources', [$this->make(ResourceProvisioner::class), 'syncAll']);
        if (!wp_next_scheduled('pwt_sync_resources')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'pwt_sync_resources');
        }
    }
}
