<?php

declare(strict_types=1);

namespace PWT\Settings;

defined('ABSPATH') || exit;

final class Settings
{
    public static function get(
        string $key,
        mixed $default = null
    ): mixed {

        return get_option(
            'pwt_' . $key,
            $default
        );
    }

    public static function update(
        string $key,
        mixed $value
    ): bool {

        return update_option(
            'pwt_' . $key,
            $value
        );
    }

    public static function advancePercent(): int
    {
        $settings = (array) get_option('pwt_settings', []);
        return max(1, min(100, absint($settings['payment_advance_percent'] ?? 30)));
    }
}