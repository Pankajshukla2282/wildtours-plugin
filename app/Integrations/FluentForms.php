<?php

declare(strict_types=1);

namespace PWT\Integrations;

defined('ABSPATH') || exit;

/**
 * Lightweight helpers for talking to the Fluent Forms plugin.
 */
final class FluentForms
{
    public static function isActive(): bool
    {
        return defined('FLUENTFORM_VERSION')
            || class_exists('FluentForm\App\Modules\Form\Form');
    }

    /**
     * Resolve a form id by its exact title. Returns 0 when unavailable.
     */
    public static function formIdByTitle(string $title): int
    {
        $title = trim($title);

        if ($title === '' || !self::isActive()) {
            return 0;
        }

        global $wpdb;

        $table = $wpdb->prefix . 'fluentform_forms';

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
            return 0;
        }

        $form = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE title = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $title
            )
        );

        return $form && isset($form->id) ? (int) $form->id : 0;
    }

    /**
     * Shortcode for a form by title, or an empty string when it does not exist.
     */
    public static function shortcode(string $title): string
    {
        $id = self::formIdByTitle($title);

        return $id > 0 ? '[fluentform id="' . $id . '"]' : '';
    }

    /**
     * Render a form by title via do_shortcode, or an empty string.
     */
    public static function render(string $title): string
    {
        $shortcode = self::shortcode($title);

        return $shortcode !== '' ? (string) do_shortcode($shortcode) : '';
    }

    /**
     * Form title from the $form argument passed by Fluent Forms hooks.
     *
     * @param mixed $form
     */
    public static function formTitle($form): string
    {
        if (is_array($form)) {
            return (string) ($form['title'] ?? '');
        }

        if (is_object($form) && isset($form->title)) {
            return (string) $form->title;
        }

        return '';
    }

    /**
     * Normalize the $formData argument passed by Fluent Forms hooks.
     *
     * @param mixed $formData
     *
     * @return array<string, mixed>
     */
    public static function normalizeData($formData): array
    {
        if (is_array($formData)) {
            return $formData;
        }

        if (is_object($formData)) {
            $decoded = json_decode(wp_json_encode($formData), true);

            return is_array($decoded) ? $decoded : [];
        }

        if (is_string($formData)) {
            $decoded = json_decode($formData, true);

            if (is_array($decoded)) {
                return $decoded;
            }

            parse_str($formData, $parsed);

            return is_array($parsed) ? $parsed : [];
        }

        return [];
    }

    /**
     * Pull a field value from form data, trying aliases in order.
     *
     * @param array<string, mixed> $data
     * @param string[]             $aliases
     */
    public static function field(array $data, string $key, array $aliases = []): string
    {
        $candidates = array_merge([$key], $aliases);

        foreach ($candidates as $candidate) {
            if (isset($data[$candidate]) && !is_array($data[$candidate])) {
                return trim((string) $data[$candidate]);
            }
        }

        return '';
    }

    /**
     * Route a Fluent Form submission to PWT customers or booking system.
     *
     * @param array $formData Full form submission data from Fluent Forms
     * @return array 'routed' => bool, 'destination' => string
     */
    public static function routeSubmission(array $formData): array
    {
        $result = ['routed' => false, 'destination' => ''];

        if (!self::isActive()) {
            return $result;
        }

        $email = self::field($formData, 'email');
        $firstName = self::field($formData, 'first_name');
        $lastName = self::field($formData, 'last_name');
        $phone = self::field($formData, 'phone');

        if ($email !== '') {
            // Route to customer repository for lead tracking
            $customer = self::customers->findOrCreate([
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
            ]);

            $result['routed'] = true;
            $result['destination'] = 'customer_' . $customer['id'];
        }

        // Could also route to FluentCRM here

        return $result;
    }
}