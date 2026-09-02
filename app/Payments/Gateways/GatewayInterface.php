<?php

namespace PWT\Payments\Gateways;

defined('ABSPATH') || exit;

interface GatewayInterface
{
    public function createIntent(int $bookingId, float $estimatedTotal, int $advancePercent): array;

    public function slug(): string;
}
