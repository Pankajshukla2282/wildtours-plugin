<?php

declare(strict_types=1);

namespace PWT\Frontend;

defined('ABSPATH') || exit;

use PWT\Core\ServiceProvider;

final class FrontendServiceProvider extends ServiceProvider
{
    /**
     * Frontend modules that should be booted when available.
     *
     * @var array<class-string>
     */
    private const MODULES = [
        Assets::class,
        TemplateLoader::class,
        ArchiveFilters::class,
        AvailabilityCalendar::class,
        Seo::class,
        Shortcodes::class,
    ];

    public function register(): void
    {
        // Reserved for future bindings.
    }

    public function boot(): void
    {
        foreach (self::MODULES as $module) {
            if (!class_exists($module)) {
                continue;
            }

            $instance = $this->make($module);

            if (method_exists($instance, 'register')) {
                $instance->register();
            }
        }
    }
}