<?php

declare(strict_types=1);

namespace PWT\Taxonomies;

defined('ABSPATH') || exit;

use PWT\Core\ServiceProvider;
use PWT\Taxonomies\Contracts\TaxonomyInterface;

/**
 * Registers all plugin taxonomies.
 */
final class TaxonomyServiceProvider extends ServiceProvider
{
    /**
     * Registered taxonomy classes.
     *
     * @var array<class-string<TaxonomyInterface>>
     */
    private const TAXONOMIES = [
        SafariZone::class,
        DestinationCategory::class,
        PackageCategory::class,
        ResortCategory::class,
        VehicleType::class,
        Season::class,
        Activity::class,
        Cuisine::class,
        TripType::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Reserved for future bindings.
    }

    /**
     * Boot taxonomies.
     */
    public function boot(): void
    {
        $taxonomies = apply_filters(
            'pwt/taxonomies',
            self::TAXONOMIES
        );

        foreach ($taxonomies as $taxonomyClass) {

            $taxonomy = $this->make($taxonomyClass);

            if (!$taxonomy instanceof TaxonomyInterface) {
                continue;
            }

            $taxonomy->register();
        }
    }
}