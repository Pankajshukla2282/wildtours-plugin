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
}