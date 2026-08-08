<?php
declare(strict_types=1);
namespace PWT\Pricing;
defined('ABSPATH') || exit;
use PWT\Core\ServiceProvider;

final class PricingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(SeasonResolver::class, SeasonResolver::class);
        $this->singleton(RateManager::class, RateManager::class);
        $this->singleton(PricingService::class, PricingService::class);
        $this->singleton(RateRepository::class, RateRepository::class);
    }
    public function boot(): void {}
}
