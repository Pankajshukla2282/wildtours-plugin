<?php

declare(strict_types=1);

namespace PWT\Core;

defined('ABSPATH') || exit;

use PWT\Core\Database\Schema;
use PWT\PostTypes\PostTypeServiceProvider;
use PWT\Taxonomies\TaxonomyServiceProvider;

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
        self::registerTaxonomies();
        self::registerRoles();

        flush_rewrite_rules();

        update_option('pwt_plugin_version', PWT_VERSION);
    }

    private static function registerRoles(): void
    {
        $admin = get_role('administrator');
        if ($admin && !$admin->has_cap('pwt_manage_operations')) {
            $admin->add_cap('pwt_manage_operations');
        }

        if (!get_role('pwt_staff')) {
            add_role('pwt_staff', __('PWT Staff', 'wildtours-plugin'), [
                'read' => true,
                'pwt_manage_operations' => true,
            ]);
        }
    }

    /**
     * Register post types synchronously so rewrite rules exist before flushing.
     */
    private static function registerPostTypes(): void
    {
        $postTypes = apply_filters('pwt/post_types', PostTypeServiceProvider::POST_TYPES);

        foreach ($postTypes as $postTypeClass) {
            $instance = new $postTypeClass();
            $instance->create();
        }
    }

    /**
     * Register taxonomies synchronously so rewrite rules exist before flushing.
     */
    private static function registerTaxonomies(): void
    {
        $taxonomies = apply_filters('pwt/taxonomies', TaxonomyServiceProvider::TAXONOMIES);

        foreach ($taxonomies as $taxonomyClass) {
            $instance = new $taxonomyClass();
            $instance->create();
        }
    }
}