<?php

declare(strict_types=1);

namespace PWT\Pricing;

defined('ABSPATH') || exit;

/**
 * Deterministic money calculations for operational and public quotes.
 * All arithmetic is performed in minor units to avoid floating-point drift.
 */
final class MoneyCalculator
{
    public function quote(array $items, float $discount = 0.0, float $taxRate = 0.0, float $depositPercent = 0.0): array
    {
        $subtotal = 0;
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $subtotal += $this->toMinor((float) ($item['total'] ?? 0));
        }

        $discountMinor = min($subtotal, max(0, $this->toMinor($discount)));
        $taxable = max(0, $subtotal - $discountMinor);
        $taxMinor = max(0, (int) round($taxable * max(0, $taxRate) / 100));
        $total = $taxable + $taxMinor;
        $depositPercent = min(100, max(0, $depositPercent));
        $deposit = max(0, (int) round($total * $depositPercent / 100));

        return [
            'subtotal' => $this->fromMinor($subtotal),
            'discount' => $this->fromMinor($discountMinor),
            'tax' => $this->fromMinor($taxMinor),
            'total' => $this->fromMinor($total),
            'deposit_due' => $this->fromMinor($deposit),
            'balance_due' => $this->fromMinor(max(0, $total - $deposit)),
            'tax_rate' => $taxRate,
            'deposit_percent' => $depositPercent,
            'calculation_version' => '2.0',
        ];
    }

    /** Calculate a per-person line with adult/child rates and quantities. */
    public function travellerLine(float $adultRate, int $adults, float $childRate = 0.0, int $children = 0): array
    {
        $adults = max(0, $adults);
        $children = max(0, $children);
        $adultTotal = $this->toMinor(max(0, $adultRate)) * $adults;
        $childTotal = $this->toMinor(max(0, $childRate)) * $children;
        return [
            'adult_rate' => $this->fromMinor($this->toMinor(max(0, $adultRate))),
            'child_rate' => $this->fromMinor($this->toMinor(max(0, $childRate))),
            'adults' => $adults,
            'children' => $children,
            'total' => $this->fromMinor($adultTotal + $childTotal),
        ];
    }

    private function toMinor(float $amount): int
    {
        return (int) round(max(0, $amount) * 100);
    }

    private function fromMinor(int $amount): float
    {
        return round($amount / 100, 2);
    }
}
