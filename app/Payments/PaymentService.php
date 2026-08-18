<?php
declare(strict_types=1);
namespace PWT\Payments;
defined('ABSPATH') || exit;

use PWT\Bookings\BookingOrchestrator;
use PWT\Bookings\BookingStatus;
use PWT\Bookings\Repositories\BookingDataRepository;
use PWT\Core\Database\Transaction;
use WP_Error;

final class PaymentService
{
    public function __construct(
        private readonly PaymentRepository $payments,
        private readonly BookingDataRepository $bookings,
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

        $booking = $this->bookings->find($bookingId);
        $current = (string)($booking['status'] ?? '');
        if ($current === BookingStatus::CANCELLED || $current === BookingStatus::REFUNDED) {
            return new WP_Error('pwt_booking_not_payable', __('Payments cannot be recorded against a cancelled or refunded booking.', 'wildtours-plugin'));
        }
        if (!BookingStatus::canTransition($current, BookingStatus::PAID)) {
            return new WP_Error('pwt_booking_not_payable', __('This booking cannot be paid.', 'wildtours-plugin'));
        }

        return Transaction::run(function () use ($bookingId, $data, $key): int|WP_Error {
            $paymentId = $this->payments->create([
                'booking_id' => $bookingId,
                'provider' => sanitize_key((string)($data['provider'] ?? '')),
                'provider_payment_id' => sanitize_text_field((string)($data['provider_payment_id'] ?? '')),
                'amount' => (float)($data['amount'] ?? 0),
                'currency' => strtoupper(sanitize_text_field((string)($data['currency'] ?? 'INR'))),
                'status' => 'paid',
                'transaction_type' => 'payment',
                'idempotency_key' => $key,
                'gateway_response' => $data['gateway_response'] ?? [],
                'paid_at' => current_time('mysql'),
            ]);

            if (!$paymentId) {
                return new WP_Error('pwt_payment_create_failed', __('Unable to record payment.', 'wildtours-plugin'));
            }

            if (!$this->bookings->updateStatus($bookingId, BookingStatus::PAID)) {
                return new WP_Error('pwt_booking_status_failed', __('Unable to update booking status.', 'wildtours-plugin'));
            }

            $confirmed = $this->orchestrator->confirm($bookingId);
            if (is_wp_error($confirmed)) {
                return $confirmed;
            }

            do_action('pwt/booking/paid', $bookingId);

            $this->syncLegacyFinancials($bookingId);

            return $paymentId;
        });
    }

    public function balance(int $bookingId): array
    {
        $booking = $this->bookings->find($bookingId);
        $total = (float)($booking['total'] ?? 0);
        $depositDue = (float)($booking['deposit_due'] ?? 0);
        $paid = $this->sumPaid($bookingId, 'payment');
        $refunded = $this->sumPaid($bookingId, 'refund');
        $netPaid = $paid - $refunded;

        return [
            'booking_id' => $bookingId,
            'status' => (string)($booking['status'] ?? ''),
            'total' => $total,
            'deposit_due' => $depositDue,
            'paid' => round($paid, 2),
            'refunded' => round($refunded, 2),
            'net_paid' => round($netPaid, 2),
            'balance_due' => round(max(0, $total - $netPaid), 2),
        ];
    }

    public function recordRefund(array $data): int|WP_Error
    {
        $bookingId = absint($data['booking_id'] ?? 0);
        if (!$bookingId) {
            return new WP_Error('pwt_booking_required', __('Booking is required.', 'wildtours-plugin'));
        }

        $amount = (float)($data['amount'] ?? 0);
        if ($amount <= 0) {
            return new WP_Error('pwt_refund_amount_required', __('A positive refund amount is required.', 'wildtours-plugin'));
        }

        $booking = $this->bookings->find($bookingId);
        $current = (string)($booking['status'] ?? '');
        if (!in_array($current, [BookingStatus::CONFIRMED, BookingStatus::PAID], true)) {
            return new WP_Error('pwt_booking_not_refundable', __('Only confirmed or paid bookings can be refunded.', 'wildtours-plugin'));
        }

        $paid = $this->sumPaid($bookingId, 'payment');

        return Transaction::run(function () use ($bookingId, $amount, $paid, $data): int|WP_Error {
            $refundId = $this->payments->create([
                'booking_id' => $bookingId,
                'provider' => sanitize_key((string)($data['provider'] ?? 'manual')),
                'provider_payment_id' => sanitize_text_field((string)($data['provider_payment_id'] ?? '')),
                'amount' => $amount,
                'currency' => strtoupper(sanitize_text_field((string)($data['currency'] ?? 'INR'))),
                'status' => 'paid',
                'transaction_type' => 'refund',
                'idempotency_key' => sanitize_text_field((string)($data['idempotency_key'] ?? '')),
                'reference' => sanitize_text_field((string)($data['reference'] ?? '')),
                'gateway_response' => $data['gateway_response'] ?? [],
                'paid_at' => current_time('mysql'),
            ]);

            if (!$refundId) {
                return new WP_Error('pwt_refund_create_failed', __('Unable to record refund.', 'wildtours-plugin'));
            }

            $refunded = $this->sumPaid($bookingId, 'refund');
            if ($refunded >= $paid) {
                $result = $this->orchestrator->refund($bookingId);
                if (is_wp_error($result)) {
                    return $result;
                }
            }

            $this->syncLegacyFinancials($bookingId);

            return $refundId;
        });
    }

    public function reconcile(int $bookingId): array
    {
        $booking = $this->bookings->find($bookingId);
        $summary = $this->balance($bookingId);

        $total = (float)$summary['total'];
        $paid = (float)$summary['paid'];
        $refunded = (float)$summary['refunded'];
        $current = (string)$summary['status'];

        $target = $current;
        if ($refunded >= $paid && $paid > 0) {
            $target = BookingStatus::REFUNDED;
        } elseif ($paid >= $total) {
            $target = BookingStatus::PAID;
        } elseif ($paid > 0 && in_array($current, [BookingStatus::PENDING, BookingStatus::HELD], true)) {
            $target = BookingStatus::CONFIRMED;
        }

        if ($target !== $current && BookingStatus::canTransition($current, $target)) {
            $this->bookings->updateStatus($bookingId, $target);
        }

        $this->syncLegacyFinancials($bookingId);
        $summary['status'] = $target;

        return $summary;
    }

    private function sumPaid(int $bookingId, string $type): float
    {
        $rows = $this->payments->forBooking($bookingId, $type);
        return array_sum(array_map(static fn (array $row): float => (float)$row['amount'], $rows));
    }

    private function syncLegacyFinancials(int $bookingId): void
    {
        $booking = $this->bookings->find($bookingId);
        $legacyId = (int)($booking['legacy_post_id'] ?? 0);
        if (!$legacyId) {
            return;
        }

        $summary = $this->balance($bookingId);
        update_post_meta($legacyId, '_pwt_paid_amount', (string)$summary['paid']);
        update_post_meta($legacyId, '_pwt_refunded_amount', (string)$summary['refunded']);
        update_post_meta($legacyId, '_pwt_balance_due', (string)$summary['balance_due']);
    }
}