<?php

declare(strict_types=1);

namespace PWT\Bookings;

defined('ABSPATH') || exit;

/**
 * Booking email templates.
 */
final class EmailTemplates
{
    /**
     * Admin notification subject.
     */
    public static function bookingAdminSubject(string $name): string
    {
        return (string) apply_filters(
            'pwt/booking/admin_subject',
            sprintf(
                /* translators: %s: customer name */
                __('New Booking Request from %s', 'wildtours-plugin'),
                $name
            ),
            $name
        );
    }

    /**
     * Admin notification body.
     */
    public static function bookingAdminBody(array $data): string
    {
        $lines = [
            __('A new booking request has been submitted.', 'wildtours-plugin'),
            '',
            self::line(__('Name', 'wildtours-plugin'), $data['name'] ?? ''),
            self::line(__('Phone', 'wildtours-plugin'), $data['phone'] ?? ''),
            self::line(__('Email', 'wildtours-plugin'), $data['email'] ?? ''),
            self::line(__('Travel Date', 'wildtours-plugin'), $data['travel_date'] ?? ''),
            self::line(__('Persons', 'wildtours-plugin'), $data['persons'] ?? ''),
            self::line(__('Package', 'wildtours-plugin'), $data['package_name'] ?? ''),
            self::line(__('Message', 'wildtours-plugin'), $data['message'] ?? ''),
        ];

        if (!empty($data['estimated_total'])) {
            $lines[] = self::line(
                __('Estimated Total', 'wildtours-plugin'),
                (string) $data['estimated_total']
            );
        }

        if (!empty($data['payment_link'])) {
            $lines[] = self::line(
                __('Payment Link', 'wildtours-plugin'),
                (string) $data['payment_link']
            );
        }

        $body = implode(PHP_EOL, $lines);

        return (string) apply_filters(
            'pwt/booking/admin_body',
            $body,
            $data
        );
    }

    /**
     * Format a label/value pair.
     */
    private static function line(string $label, string|int|float $value): string
    {
        return sprintf('%s: %s', $label, (string) $value);
    }
}