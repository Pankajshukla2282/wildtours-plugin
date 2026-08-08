<?php

declare(strict_types=1);

namespace PWT\Bookings\Services;

defined('ABSPATH') || exit;

use PWT\Bookings\Repositories\BookingRepository;
use PWT\Bookings\Validators\BookingValidator;
use PWT\Frontend\Pricing;
use PWT\Payments\PaymentManager;
use PWT\Bookings\Services\BookingDataService;

final class BookingService
{
    public function __construct(
        private readonly BookingRepository $repository,
        private readonly BookingValidator $validator,
        private readonly EmailService $emailService,
        private readonly BookingDataService $bookingDataService
    ) {
    }

    /**
     * Create booking.
     */
    public function create(array $data): array
    {
        $data = wp_unslash($data);

        $validation = $this->validator->validate($data);

        if (!$validation['success']) {
            return $validation;
        }

        $bookingId = $this->repository->create($validation['data']);

        if (is_wp_error($bookingId)) {
            return [
                'success' => false,
                'message' => $bookingId->get_error_message(),
            ];
        }

        $booking = $validation['data'];

        // New normalized booking model; legacy post remains for backward compatibility.
        try {
            $normalizedBookingId = $this->bookingDataService->syncLegacyBooking((int) $bookingId, $booking);
            update_post_meta($bookingId, '_pwt_normalized_booking_id', $normalizedBookingId);
        } catch (\Throwable $e) {
            do_action('pwt/booking/normalization_failed', $bookingId, $e);
        }
        $estimate = [];
        $payment = [];

        if (!empty($booking['package_id'])) {
            $estimate = Pricing::calculateEstimate(
                (int) $booking['package_id'],
                (int) $booking['persons'],
                (string) $booking['travel_date']
            );

            if (!empty($estimate['estimated_total'])) {
                update_post_meta($bookingId, '_pwt_estimated_total', $estimate['estimated_total']);
                update_post_meta($bookingId, '_pwt_estimate_season', $estimate['season_label']);

                try {
                    $payment = PaymentManager::createIntent(
                        $bookingId,
                        (float) $estimate['estimated_total']
                    );
                } catch (\Throwable $e) {
                    $payment = [];
                }
            }
        }

        $emailData = $booking;

        if (isset($estimate['formatted_total'])) {
            $emailData['estimated_total'] = $estimate['formatted_total'];
        }

        if (!empty($payment['payment_url'])) {
            $emailData['payment_link'] = $payment['payment_url'];
        }

        $this->emailService->sendAdminNotification(
            $bookingId,
            $emailData
        );

        $this->emailService->sendCustomerConfirmation(
            $bookingId,
            $emailData
        );

        do_action(
            'pwt/booking/created',
            $bookingId,
            $emailData
        );

        $response = [
            'success' => true,
            'booking_id' => $bookingId,
            'message' => __('Booking submitted successfully.', 'wildtours-plugin'),
        ];

        if (!empty($payment['payment_url'])) {
            $response['payment_url'] = $payment['payment_url'];
            $response['payment_advance_amount'] = $payment['advance_amount'] ?? 0;
        }

        return $response;
    }
}