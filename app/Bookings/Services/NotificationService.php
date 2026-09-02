<?php
declare(strict_types=1);
namespace PWT\Bookings\Services;
defined('ABSPATH') || exit;

use PWT\Bookings\EmailTemplates;
use PWT\Bookings\Repositories\BookingDataRepository;
use PWT\Bookings\BookingItemRepository;
use PWT\Customers\CustomerRepository;
use PWT\Customers\TravelerRepository;
use PWT\Payments\PaymentService;

final class NotificationService
{
    public function __construct(
        private readonly BookingDataRepository $bookings,
        private readonly BookingItemRepository $items,
        private readonly CustomerRepository $customers,
        private readonly TravelerRepository $travelers,
        private readonly PaymentService $payments,
        private readonly EmailService $mailer
    ) {
    }

    public function enquiry(int $bookingId, array $extra = []): bool
    {
        $ctx = $this->context($bookingId, $extra);
        return $this->mailer->sendHtml(
            (string) apply_filters('pwt/notification/admin_recipients', get_option('admin_email'), $ctx),
            EmailTemplates::adminEnquirySubject($ctx),
            EmailTemplates::adminEnquiryBody($ctx),
            ['context' => ['booking_id' => $bookingId]]
        );
    }

    public function acknowledgement(int $bookingId, array $extra = []): bool
    {
        $ctx = $this->context($bookingId, $extra);
        $email = (string) ($ctx['customer']['email'] ?? '');
        if ($email === '') {
            return false;
        }

        return $this->mailer->sendHtml(
            $email,
            EmailTemplates::acknowledgementSubject($ctx),
            EmailTemplates::acknowledgementBody($ctx),
            ['context' => ['booking_id' => $bookingId]]
        );
    }

    public function confirmation(int $bookingId): bool
    {
        $ctx = $this->context($bookingId);
        $email = (string) ($ctx['customer']['email'] ?? '');
        if ($email === '') {
            return false;
        }

        return $this->mailer->sendHtml(
            $email,
            EmailTemplates::confirmationSubject($ctx),
            EmailTemplates::confirmationBody($ctx),
            ['context' => ['booking_id' => $bookingId]]
        );
    }

    public function paymentReminder(int $bookingId, array $extra = []): bool
    {
        $ctx = $this->context($bookingId, $extra);
        $email = (string) ($ctx['customer']['email'] ?? '');
        if ($email === '' || (float) ($ctx['balance_due'] ?? 0) <= 0) {
            return false;
        }

        return $this->mailer->sendHtml(
            $email,
            EmailTemplates::paymentReminderSubject($ctx),
            EmailTemplates::paymentReminderBody($ctx),
            ['context' => ['booking_id' => $bookingId]]
        );
    }

    public function cancellation(int $bookingId): bool
    {
        $ctx = $this->context($bookingId);
        $email = (string) ($ctx['customer']['email'] ?? '');
        if ($email === '') {
            return false;
        }

        return $this->mailer->sendHtml(
            $email,
            EmailTemplates::cancellationSubject($ctx),
            EmailTemplates::cancellationBody($ctx),
            ['context' => ['booking_id' => $bookingId]]
        );
    }

    public function voucher(int $bookingId, array $extra = []): bool
    {
        $ctx = $this->context($bookingId, $extra);
        $email = (string) ($ctx['customer']['email'] ?? '');
        if ($email === '') {
            return false;
        }

        return $this->mailer->sendHtml(
            $email,
            EmailTemplates::voucherSubject($ctx),
            EmailTemplates::voucherBody($ctx),
            ['context' => ['booking_id' => $bookingId]]
        );
    }

    public function context(int $bookingId, array $extra = []): array
    {
        $booking = $this->bookings->find($bookingId);
        $legacyId = (int) ($booking['legacy_post_id'] ?? 0);

        $customer = [];
        $customerId = (int) ($booking['customer_id'] ?? 0);
        if ($customerId) {
            $customer = $this->customers->find($customerId);
        }
        if (empty($customer['email']) && $legacyId) {
            $customer['email'] = (string) get_post_meta($legacyId, '_pwt_email', true);
        }
        if (empty($customer['first_name']) && $legacyId) {
            $customer['first_name'] = (string) get_post_meta($legacyId, '_pwt_name', true);
        }
        $customer['name'] = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
        if ($customer['name'] === '') {
            $customer['name'] = (string) ($customer['email'] ?? '');
        }

        $balance = $this->payments->balance($bookingId);
        $advance = (string) get_post_meta($legacyId, '_pwt_payment_due_amount', true);

        return array_merge([
            'booking_id' => $bookingId,
            'booking_number' => (string) ($booking['booking_number'] ?? ''),
            'legacy_post_id' => $legacyId,
            'status' => (string) ($booking['status'] ?? 'pending'),
            'travel_start' => (string) ($booking['travel_start'] ?? ''),
            'travel_end' => (string) ($booking['travel_end'] ?? ''),
            'adults' => (int) ($booking['adults'] ?? 0),
            'children' => (int) ($booking['children'] ?? 0),
            'currency' => (string) ($booking['currency'] ?? 'INR'),
            'total' => (float) ($booking['total'] ?? 0),
            'customer' => $customer,
            'items' => $this->items->byBooking($bookingId),
            'travelers' => $this->travelers->byBooking($bookingId),
            'balance_due' => (float) ($balance['balance_due'] ?? 0),
            'paid' => (float) ($balance['paid'] ?? 0),
            'payment' => [
                'advance_amount' => $advance,
                'payment_link' => $extra['payment_link'] ?? '',
            ],
        ], $extra);
    }
}