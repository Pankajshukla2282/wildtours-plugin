<?php

namespace PWT\Payments\Gateways;

defined('ABSPATH') || exit;

class ManualReferenceGateway implements GatewayInterface
{
    public function createIntent(int $bookingId, float $estimatedTotal, int $advancePercent): array
    {
        $advanceAmount = round(($estimatedTotal * $advancePercent) / 100, 2);
        $token = wp_generate_password(24, false, false);

        return [
            'token' => $token,
            'advance_amount' => $advanceAmount,
            'payment_url' => '',
        ];
    }

    public function slug(): string
    {
        return 'manual';
    }
}
