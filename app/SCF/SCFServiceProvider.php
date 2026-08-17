<?php

declare(strict_types=1);

namespace PWT\SCF;

defined('ABSPATH') || exit;

use PWT\Core\ServiceProvider;
use PWT\SCF\Contracts\FieldGroupInterface;
use PWT\SCF\Groups\DestinationFields;
use PWT\SCF\Groups\FAQFields;
use PWT\SCF\Groups\PackageFields;
use PWT\SCF\Groups\ResortFields;
use PWT\SCF\Groups\ReviewFields;
use PWT\SCF\Groups\SafariFields;
use PWT\SCF\Groups\TestimonialFields;
use PWT\SCF\Groups\VehicleFields;
use PWT\SCF\Groups\RestaurantFields;
use PWT\SCF\Groups\LocalTripFields;
use PWT\SCF\Groups\RoomTypeFields;
use PWT\SCF\Groups\RoomUnitFields;
use PWT\SCF\Groups\SafariScheduleFields;
use PWT\SCF\Groups\NavigationFields;

/**
 * Registers all SCF/ACF field groups.
 */
final class SCFServiceProvider extends ServiceProvider
{
    /**
     * Registered field groups.
     *
     * @var array<class-string<FieldGroupInterface>>
     */
    private const FIELD_GROUPS = [
        SafariFields::class,
        PackageFields::class,
        ResortFields::class,
        VehicleFields::class,
        RestaurantFields::class,
        LocalTripFields::class,
        RoomTypeFields::class,
        RoomUnitFields::class,
        SafariScheduleFields::class,
        DestinationFields::class,
        TestimonialFields::class,
        ReviewFields::class,
        FAQFields::class,
        NavigationFields::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Reserved for future bindings.
    }

    /**
     * Boot provider.
     */
    public function boot(): void
    {
        add_action('init', [$this, 'registerGroups'], 20);
    }

    /**
     * Register all field groups.
     */
    public function registerGroups(): void
    {
        if (
            !function_exists('scf_register_field_group')
            && !function_exists('acf_add_local_field_group')
        ) {
            return;
        }

        $groups = apply_filters(
            'pwt/scf_field_groups',
            self::FIELD_GROUPS
        );

        foreach ($groups as $groupClass) {

            $group = $this->make($groupClass);

            if (!$group instanceof FieldGroupInterface) {
                continue;
            }

            $group->register();
        }
    }
}