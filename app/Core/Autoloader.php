<?php

declare(strict_types=1);

namespace PWT\Core;

defined('ABSPATH') || exit;

use PWT\Core\Paths;

/**
 * PSR-4 style autoloader for the PWT plugin.
 *
 * Maps:
 *
 * PWT\Booking\Service
 *
 * to
 *
 * app/Booking/Service.php
 */
final class Autoloader
{
    /**
     * Plugin namespace.
     */
    private const PREFIX = 'PWT\\';

    /**
     * Register the autoloader.
     */
    public static function register(): void
    {
        spl_autoload_register(
            [self::class, 'autoload'],
            true,
            true
        );
    }

    /**
     * Autoload a class.
     */
    private static function autoload(string $class): void
    {
        if (
            !defined('PWT_PLUGIN_FILE')
            || strncmp($class, self::PREFIX, strlen(self::PREFIX)) !== 0
        ) {
            return;
        }

        $relativeClass = substr($class, strlen(self::PREFIX));

        $file = trailingslashit(plugin_dir_path(PWT_PLUGIN_FILE))
            . 'app/'
            . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass)
            . '.php';

        if (is_readable($file)) {
            require_once $file;
        }
    }
}