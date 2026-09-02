<?php

namespace PWT\Admin;

defined('ABSPATH') || exit;

use PWT\Core\Paths;

class Assets
{
    public function register(): void
    {
        add_action(
            'admin_enqueue_scripts',
            [$this, 'enqueue']
        );
    }

    public function enqueue(string $hook): void
    {
        if (strpos($hook, 'pwt') === false) {
            return;
        }

        wp_enqueue_style(
            'pwt-admin',
            Paths::uri('resources/css/admin.css'),
            [],
            PWT_VERSION
        );

        wp_enqueue_script(
            'pwt-admin',
            Paths::uri('resources/js/admin.js'),
            ['jquery'],
            PWT_VERSION,
            true
        );
    }
}