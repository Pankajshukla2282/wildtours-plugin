<?php

declare(strict_types=1);

namespace PWT\Core;

defined('ABSPATH') || exit;

/**
 * Plugin path helper.
 */
final class Paths
{
    /**
     * Base filesystem path.
     */
    public static function path(string $path = ''): string
    {
        $path = ltrim($path, '/');

        if ($path === '') {
            return trailingslashit(plugin_dir_path(PWT_PLUGIN_FILE));
        }

        return trailingslashit(plugin_dir_path(PWT_PLUGIN_FILE)) . $path;
    }

    /**
     * Base URL.
     */
    public static function uri(string $path = ''): string
    {
        $path = ltrim($path, '/');

        if ($path === '') {
            return trailingslashit(plugin_dir_url(PWT_PLUGIN_FILE));
        }

        return trailingslashit(plugin_dir_url(PWT_PLUGIN_FILE)) . $path;
    }
}