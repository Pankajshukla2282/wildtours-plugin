<?php

declare(strict_types=1);

namespace PWT\Integrations;

use PWT\Bookings\Services\BookingService;

defined('ABSPATH') || exit;

/**
 * Bridges the public "PWT Safari Booking" Fluent Form into the existing
 * booking pipeline (pwt_booking CPT, estimates, payment intent, emails).
 *
 * When a payment link is generated the visitor is redirected to the payment
 * portal after a successful submission.
 */
final class FluentBookingHandler
{
    public const FORM_TITLE = 'PWT Safari Booking';

    public function __construct(private readonly BookingService $bookingService)
    {
    }

    public function register(): void
    {
        add_action('fluentform_submission_inserted', [$this, 'handleSubmission'], 20, 3);
        add_filter('fluentform_submission_confirmation', [$this, 'applyConfirmation'], 20, 3);
    }

    /**
     * @param mixed $entryId
     * @param mixed $formData
     * @param mixed $form
     */
    public function handleSubmission($entryId, $formData, $form): void
    {
        if (FluentForms::formTitle($form) !== self::FORM_TITLE) {
            return;
        }

        $entryId = absint((string) $entryId);
        $data = FluentForms::normalizeData($formData);

        if ($entryId <= 0 || $data === []) {
            return;
        }

        $bookingData = $this->mapBookingData($data);

        if ($bookingData['name'] === '') {
            return;
        }

        $response = $this->bookingService->create($bookingData);

        if (!empty($response['success']) && !empty($response['payment_url'])) {
            set_transient(
                $this->paymentKey($data),
                $response['payment_url'],
                MINUTE_IN_SECONDS * 10
            );
        }
    }

    /**
     * Redirect to the payment portal when the submission created a booking
     * with an advance payment due.
     *
     * @param mixed $confirmation
     * @param mixed $data
     * @param mixed $form
     *
     * @return mixed
     */
    public function applyConfirmation($confirmation, $data, $form)
    {
        if (FluentForms::formTitle($form) !== self::FORM_TITLE) {
            return $confirmation;
        }

        $data = FluentForms::normalizeData($data);
        $key = $this->paymentKey($data);

        $paymentUrl = get_transient($key);

        if (!$paymentUrl) {
            return $confirmation;
        }

        delete_transient($key);

        if (!is_array($confirmation)) {
            $confirmation = [];
        }

        $confirmation['redirectTo'] = 'customUrl';
        $confirmation['customUrl'] = $paymentUrl;

        return $confirmation;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array{name:string, email:string, phone:string, travel_date:string, persons:int, package_id:int, message:string}
     */
    private function mapBookingData(array $data): array
    {
        $name = $this->resolveName($data);
        $packageId = $this->resolvePackageId(FluentForms::field($data, 'package_id', ['package', 'preferred_package']));

        return [
            'name' => sanitize_text_field($name),
            'email' => sanitize_email(FluentForms::field($data, 'email', ['email_address'])),
            'phone' => sanitize_text_field(FluentForms::field($data, 'phone', ['phone_number', 'mobile', 'mobile_number'])),
            'travel_date' => sanitize_text_field(FluentForms::field($data, 'travel_date', ['booking_date', 'date', 'preferred_date'])),
            'persons' => max(1, absint(FluentForms::field($data, 'persons', ['number_of_persons', 'adults', 'guests', 'guests_count']) ?: 1)),
            'package_id' => $packageId,
            'message' => sanitize_textarea_field(FluentForms::field($data, 'message', ['special_requests', 'comments', 'details', 'textarea'])),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveName(array $data): string
    {
        $name = FluentForms::field($data, 'name', ['full_name', 'your_name', 'guest_name']);

        if ($name !== '') {
            return $name;
        }

        if (isset($data['names']) && is_array($data['names'])) {
            $first = (string) ($data['names']['first_name'] ?? '');
            $last = (string) ($data['names']['last_name'] ?? '');
            $name = trim($first . ' ' . $last);
        }

        if (isset($data['first_name']) && !is_array($data['first_name'])) {
            $name = trim((string) $data['first_name'] . ' ' . FluentForms::field($data, 'last_name', []));
        }

        return $name;
    }

    private function resolvePackageId(string $value): int
    {
        if ($value === '') {
            return 0;
        }

        if (ctype_digit($value)) {
            return (int) $value;
        }

        $package = get_page_by_title($value, OBJECT, 'pwt_package');

        return $package instanceof \WP_Post ? (int) $package->ID : 0;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function paymentKey(array $data): string
    {
        return 'pwt_fluent_payment_' . md5(wp_json_encode($data) ?: uniqid('', true));
    }
}