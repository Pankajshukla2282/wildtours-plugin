<?php

namespace PWT\Payments\Gateways;

defined('ABSPATH') || exit;

class HostedRedirectGateway implements GatewayInterface
{
    private string $provider;

    private string $checkoutUrl;

    public function __construct(string $provider, string $checkoutUrl)
    {
        $this->provider = $provider;
        $this->checkoutUrl = $checkoutUrl;
    }

    public function createIntent(int $bookingId, float $estimatedTotal, int $advancePercent): array
    {
        $advanceAmount = round(($estimatedTotal * $advancePercent) / 100, 2);
        $token = wp_generate_password(24, false, false);
        $paymentUrl = '';

        if ($this->checkoutUrl !== '') {
            $paymentUrl = add_query_arg([
                'provider' => $this->provider,
                'booking_id' => $bookingId,
                'token' => $token,
                'amount' => (string) $advanceAmount,
            ], $this->checkoutUrl);
        }

        return [
            'token' => $token,
            'advance_amount' => $advanceAmount,
            'payment_url' => $paymentUrl,
        ];
    }

    public function slug(): string
    {
        return $this->provider;
    }
}
