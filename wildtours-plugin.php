<?php
/**
 * Plugin Name: Panna Wild Tour
 * Description: Core content, booking, availability, pricing, inventory, payments and integrations for Panna Wild Tour.
 * Version: 2.5.43
 * Author: Panna Wild Tour
 * Author URI: https://www.pannawildtour.com
 * Requires at least: 6.7
 * Requires PHP: 8.2
 * License: GPL-2.0-or-later
 * Text Domain: wildtours-plugin
 */

defined('ABSPATH') || exit;

define('PWT_VERSION', '2.2.6');
define('PWT_PLUGIN_FILE', __FILE__);
define('PWT_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once plugin_dir_path(__FILE__) . 'app/Core/Autoloader.php';

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
