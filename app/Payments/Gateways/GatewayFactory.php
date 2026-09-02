<?php

namespace PWT\Payments\Gateways;

defined('ABSPATH') || exit;

class GatewayFactory
{
    public static function fromSettings(array $settings): GatewayInterface
    {
        $gateway = sanitize_key((string) ($settings['payment_gateway'] ?? 'manual'));

        if (in_array($gateway, ['razorpay', 'cashfree'], true)) {
            $checkoutUrl = esc_url_raw((string) ($settings['payment_gateway_checkout_url'] ?? ''));
            return new HostedRedirectGateway($gateway, $checkoutUrl);
        }

        return new ManualReferenceGateway();
    }
}
