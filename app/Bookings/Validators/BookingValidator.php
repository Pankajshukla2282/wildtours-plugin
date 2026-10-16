<?php

declare(strict_types=1);

namespace PWT\Bookings\Validators;

defined('ABSPATH') || exit;

final class BookingValidator
{
    public function validate(array $data): array
    {
        $clean = [

            'name' => sanitize_text_field($data['name'] ?? ''),

            'email' => sanitize_email($data['email'] ?? ''),

            'phone' => sanitize_text_field($data['phone'] ?? ''),

            'travel_date' => sanitize_text_field($data['travel_date'] ?? ''),

            'persons' => absint($data['persons'] ?? 0),

            'package_id' => absint($data['package_id'] ?? 0),

            'message' => sanitize_textarea_field($data['message'] ?? ''),
        ];

        if ($clean['name'] === '') {

            return [
                'success' => false,
                'message' => __('Please enter your name.', 'wildtours-plugin'),
            ];
        }

        if ($clean['email'] !== '' && !is_email($clean['email'])) {

            return [
                'success' => false,
                'message' => __('Please enter a valid email address.', 'wildtours-plugin'),
            ];
        }

        if ($clean['phone'] === '') {

            return [
                'success' => false,
                'message' => __('Phone number is required.', 'wildtours-plugin'),
            ];
        }

        $travelDate = \DateTime::createFromFormat('!Y-m-d', $clean['travel_date']);
        if (!$travelDate || $travelDate->format('Y-m-d') !== $clean['travel_date']) {
            return [
                'success' => false,
                'message' => __('Please select a valid travel date.', 'wildtours-plugin'),
            ];
        }

        if ($clean['travel_date'] < current_time('Y-m-d')) {
            return [
                'success' => false,
                'message' => __('Travel date must be today or later.', 'wildtours-plugin'),
            ];
        }

        if ($clean['persons'] < 1 || $clean['persons'] > 50) {
            return [
                'success' => false,
                'message' => __('Please select between 1 and 50 persons.', 'wildtours-plugin'),
            ];
        }

        if (
            $clean['package_id'] > 0
            && (get_post_type($clean['package_id']) !== 'pwt_package' || get_post_status($clean['package_id']) !== 'publish')
        ) {
            return [
                'success' => false,
                'message' => __('Please select a valid package.', 'wildtours-plugin'),
            ];
        }

        if ($clean['package_id'] > 0 && !\PWT\Frontend\AvailabilityCalendar::isDateAvailable($clean['package_id'], $clean['travel_date'])) {
            return [
                'success' => false,
                'message' => __('Selected date is unavailable. Please choose another date.', 'wildtours-plugin'),
            ];
        }

        return [
            'success' => true,
            'data' => $clean,
        ];
    }
}