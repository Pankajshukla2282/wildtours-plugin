<?php
declare(strict_types=1);

namespace PWT\Packages;

defined('ABSPATH') || exit;

use PWT\Core\ServiceProvider;

final class PackageServiceProvider
    extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(
            PackageComponentService::class,
            PackageComponentService::class
        );

        $this->singleton(
            ResourceCatalogService::class,
            ResourceCatalogService::class
        );
    }

    public function boot(): void
    {
    }
}