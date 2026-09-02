<?php

declare(strict_types=1);

namespace PWT\Core;

defined('ABSPATH') || exit;

/**
 * Main plugin bootstrap.
 */
final class Plugin
{
    /**
     * Boot the plugin.
     */
    public static function boot(): void
    {
        (new Application())->boot();
    }

    /**
     * Activate the plugin.
     */
    public static function activate(): void
    {
        Activator::activate();
    }

    /**
     * Deactivate the plugin.
     */
    public static function deactivate(): void
    {
        Deactivator::deactivate();
    }
}