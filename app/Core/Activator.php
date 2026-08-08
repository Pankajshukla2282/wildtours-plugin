<?php

declare(strict_types=1);

namespace PWT\Core;

defined('ABSPATH') || exit;

use PWT\Core\Database\Schema;

/**
 * Plugin activation handler.
 */
final class Activator
{
    /**
     * Activate plugin.
     */
    public static function activate(): void
    {
        Schema::install();
        self::registerPostTypes();

        flush_rewrite_rules();

        update_option('pwt_plugin_version', PWT_VERSION);
    }

    /**
     * Register post types before flushing rewrite rules.
     */
    private static function registerPostTypes(): void
    {
        do_action('pwt/register_post_types');
    }
}