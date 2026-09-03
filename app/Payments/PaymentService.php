<?php
declare(strict_types=1);
namespace PWT\Payments;
defined('ABSPATH') || exit;

use PWT\Bookings\BookingOrchestrator;
use PWT\Bookings\Repositories\BookingRepository;
use WP_Error;

final class PaymentService
{
    public function __construct(
        private readonly PaymentRepository $payments,
        private readonly BookingRepository $bookings,
        private readonly BookingOrchestrator $orchestrator
    ) {}

    public function recordSuccessfulPayment(array $data): int|WP_Error
    {
        $key = sanitize_text_field((string)($data['idempotency_key'] ?? ''));
        if (!$key) {
            return new WP_Error('pwt_payment_idempotency_required', __('Payment idempotency key is required.', 'wildtours-plugin'));
        }

        if ($existing = $this->payments->byIdempotencyKey($key)) {
            return (int)$existing['id'];
        }

        $bookingId = absint($data['booking_id'] ?? 0);
        if (!$bookingId) {
            return new WP_Error('pwt_booking_required', __('Booking is required.', 'wildtours-plugin'));
        }

        $paymentId = $this->payments->create([
            'booking_id'=>$bookingId,
            'provider'=>sanitize_key((string)($data['provider'] ?? '')),
            'provider_payment_id'=>sanitize_text_field((string)($data['provider_payment_id'] ?? '')),
            'amount'=>(float)($data['amount'] ?? 0),
            'currency'=>strtoupper(sanitize_text_field((string)($data['currency'] ?? 'INR'))),
            'status'=>'paid',
            'transaction_type'=>'payment',
            'idempotency_key'=>$key,
            'gateway_response'=>$data['gateway_response'] ?? [],
            'paid_at'=>current_time('mysql'),
        ]);

        if (!$paymentId) {
            return new WP_Error('pwt_payment_create_failed', __('Unable to record payment.', 'wildtours-plugin'));
        }

        $this->bookings->updateStatus($bookingId, 'paid');
        $confirmed = $this->orchestrator->confirm($bookingId);
        if (is_wp_error($confirmed)) {
            return $confirmed;
        }

        return $paymentId;
    }
}
