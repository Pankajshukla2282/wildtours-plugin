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
    public function boot(): void {}
}
