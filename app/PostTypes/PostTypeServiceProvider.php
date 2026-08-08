<?php

declare(strict_types=1);

namespace PWT\PostTypes;

defined('ABSPATH') || exit;

use PWT\Core\ServiceProvider;

/**
 * Registers all plugin custom post types.
 */
final class PostTypeServiceProvider extends ServiceProvider
{
    /**
     * Registered post type classes.
     *
     * @var array<class-string>
     */
    private const POST_TYPES = [
        Safari::class,
        Package::class,
        Resort::class,
        Vehicle::class,
        Restaurant::class,
        RoomType::class,
        RoomUnit::class,
        SafariSchedule::class,
        LocalTrip::class,
        Destination::class,
        Testimonial::class,
        Review::class,
        FAQ::class,
        Gallery::class,
        Booking::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Reserved for future bindings.
    }

    /**
     * Boot all post types.
     */
    public function boot(): void
    {
        $postTypes = apply_filters(
            'pwt/post_types',
            self::POST_TYPES
        );

        foreach ($postTypes as $postType) {
            $instance = $this->make($postType);

            if (method_exists($instance, 'register')) {
                $instance->register();
            }
        }
    }
}