<?php
declare(strict_types=1);
namespace PWT\Pricing;
defined('ABSPATH') || exit;

final class MoneyCalculator
{
    public function quote(array $items, float $discount = 0.0, float $taxRate = 0.0, float $depositPercent = 0.0): array
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += round(((float)($item['total'] ?? 0)) * 100);
        }
        $discountMinor = min($subtotal, max(0, (int) round($discount * 100)));
        $taxable = $subtotal - $discountMinor;
        $taxMinor = max(0, (int) round($taxable * max(0, $taxRate) / 100));
        $total = $taxable + $taxMinor;
        $deposit = max(0, (int) round($total * min(100, max(0, $depositPercent)) / 100));
        return [
            'subtotal' => $subtotal / 100,
            'discount' => $discountMinor / 100,
            'tax' => $taxMinor / 100,
            'total' => $total / 100,
            'deposit_due' => $deposit / 100,
            'calculation_version' => '1.0',
        ];
    }
}
