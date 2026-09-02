<?php
declare(strict_types=1);
namespace PWT\Payments;
defined('ABSPATH') || exit;

use PWT\Core\Database\Schema;
use PWT\Logging\AuditLog;

final class PaymentRepository
{
    public function __construct(private readonly AuditLog $audit)
    {
    }

    public function create(array $data): int
    {
        global $wpdb;
        $wpdb->insert(Schema::tables()['payments'], [
            'booking_id' => absint($data['booking_id'] ?? 0),
            'gateway' => sanitize_key((string)($data['provider'] ?? $data['gateway'] ?? 'manual')),
            'transaction_type' => sanitize_key((string)($data['transaction_type'] ?? 'payment')),
            'transaction_id' => sanitize_text_field((string)($data['provider_payment_id'] ?? $data['transaction_id'] ?? '')) ?: null,
            'status' => sanitize_key((string)($data['status'] ?? 'pending')),
            'amount' => max(0, (float)($data['amount'] ?? 0)),
            'currency' => strtoupper(sanitize_text_field((string)($data['currency'] ?? 'INR'))),
            'reference' => sanitize_text_field((string)($data['idempotency_key'] ?? $data['reference'] ?? '')) ?: null,
            'paid_at' => !empty($data['paid_at']) ? sanitize_text_field((string)$data['paid_at']) : null,
            'meta' => wp_json_encode($data['gateway_response'] ?? []),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ]);
        $id = (int)$wpdb->insert_id;

        if ($id) {
            $this->audit->record('payment', $id, 'payment.created', [
                'to' => [
                    'booking_id' => absint($data['booking_id'] ?? 0),
                    'type' => sanitize_key((string)($data['transaction_type'] ?? 'payment')),
                    'amount' => (float)($data['amount'] ?? 0),
                    'status' => sanitize_key((string)($data['status'] ?? 'pending')),
                ],
            ]);
        }

        return $id;
    }

    public function byIdempotencyKey(string $key): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . Schema::tables()['payments'] . " WHERE reference=%s LIMIT 1", $key
        ), ARRAY_A);
        return $row ?: null;
    }

    public function markPaid(int $id, array $gatewayResponse = []): bool
    {
        global $wpdb;
        $row = $this->byId($id);
        $updated = false !== $wpdb->update(
            Schema::tables()['payments'],
            ['status'=>'paid','meta'=>wp_json_encode($gatewayResponse),'paid_at'=>current_time('mysql'),'updated_at'=>current_time('mysql')],
            ['id'=>$id],
            ['%s','%s','%s','%s'],
            ['%d']
        );

        if ($updated && $row && ($row['status'] ?? '') !== 'paid') {
            $this->audit->record('payment', $id, 'payment.paid', [
                'from' => ['status' => $row['status']],
                'to' => ['status' => 'paid'],
                'actor' => $row['gateway'] ?? '',
            ]);
        }

        return $updated;
    }

    public function byId(int $id): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . Schema::tables()['payments'] . " WHERE id=%d", $id
        ), ARRAY_A);
        return $row ?: null;
    }

    public function forBooking(int $bookingId, string $type = 'payment'): array
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . Schema::tables()['payments'] . "
             WHERE booking_id=%d AND transaction_type=%s AND status='paid' ORDER BY id ASC",
            $bookingId,
            sanitize_key($type)
        ), ARRAY_A) ?: [];
    }
}
