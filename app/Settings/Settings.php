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

    public static function currency(): string
    {
        $currency = get_option(
            'pwt_currency',
            'INR'
        );

        $currency = strtoupper(
            sanitize_text_field(
                (string) $currency
            )
        );

        return $currency !== ''
            ? $currency
            : 'INR';
    }

    public static function upiVpa(): string
    {
        return sanitize_text_field(
            (string) get_option(
                'pwt_upi_vpa',
                ''
            )
        );
    }

    public static function upiPayeeName(): string
    {
        return sanitize_text_field(
            (string) get_option(
                'pwt_upi_payee_name',
                get_bloginfo('name')
            )
        );
    }

}