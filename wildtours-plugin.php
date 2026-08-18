<?php
/**
 * Plugin Name: Panna Wild Tour
 * Description: Core content, booking, availability, pricing, inventory, payments and integrations for Panna Wild Tour.
 * Version: 2.4.1
 * Author: Panna Wild Tour
 * Author URI: https://www.pannawildtour.com
 * Requires at least: 6.7
 * Requires PHP: 8.2
 * License: GPL-2.0-or-later
 * Text Domain: wildtours-plugin
 */

defined('ABSPATH') || exit;

define('PWT_VERSION', '2.4.0');
define('PWT_PLUGIN_FILE', __FILE__);
define('PWT_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('PWT_PLUGIN_PATH', trailingslashit(plugin_dir_path(__FILE__)));
define('PWT_PLUGIN_URL', trailingslashit(plugin_dir_url(__FILE__)));

/** Fail safely instead of causing a fatal error on unsupported hosts. */
function pwt_environment_is_supported(): bool
{
    global $wp_version;

    return version_compare(PHP_VERSION, '8.2.0', '>=')
        && isset($wp_version)
        && version_compare((string) $wp_version, '6.7', '>=');
}

function pwt_render_environment_notice(): void
{
    if (!current_user_can('activate_plugins')) {
        return;
    }

    echo '<div class="notice notice-error"><p>'
        . esc_html__('Panna Wild Tour requires WordPress 6.7+ and PHP 8.2+. The plugin was not booted.', 'wildtours-plugin')
        . '</p></div>';
}

if (!pwt_environment_is_supported()) {
    add_action('admin_notices', 'pwt_render_environment_notice');
    return;
}

require_once PWT_PLUGIN_PATH . 'app/Core/Autoloader.php';

PWT\Core\Autoloader::register();
PWT\Core\Plugin::boot();

register_activation_hook(__FILE__, [PWT\Core\Plugin::class, 'activate']);
register_deactivation_hook(__FILE__, [PWT\Core\Plugin::class, 'deactivate']);

add_filter('plugin_action_links_' . PWT_PLUGIN_BASENAME, static function (array $links): array {
    $custom = [
        '<a href="' . esc_url(admin_url('admin.php?page=pwt-settings')) . '">' . esc_html__('Settings', 'wildtours-plugin') . '</a>',
        '<a href="' . esc_url(admin_url('edit.php?post_type=pwt_booking')) . '">' . esc_html__('Bookings', 'wildtours-plugin') . '</a>',
    ];
    return array_merge($custom, $links);
});
