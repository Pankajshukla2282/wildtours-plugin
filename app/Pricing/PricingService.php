<?php
declare(strict_types=1);
namespace PWT\Pricing;
defined('ABSPATH') || exit;

final class PricingService
{
    public function __construct(private readonly RateRepository $rates,
        private readonly SeasonResolver $seasons) {}

    public function quote(int $resourceId, string $resourceType, string $date, int $quantity = 1, float $fallback = 0.0): array
    {
        $quantity = max(1, $quantity);
        $rows = $this->rates->find($resourceId, $resourceType, $date);
        $unit = $fallback;
        $matchedRate = null;

        foreach ($rows as $row) {
            $min = max(1, (int)$row['min_quantity']);
            $max = $row['max_quantity'] !== null ? (int)$row['max_quantity'] : PHP_INT_MAX;
            if ($quantity >= $min && $quantity <= $max) {
                $unit = (float)$row['amount'];
                $matchedRate = $row;
                break;
            }
        }

        return [
            'resource_id' => $resourceId,
            'resource_type' => $resourceType,
            'date' => $date,
            'quantity' => $quantity,
            'unit_price' => $unit,
            'total' => round($unit * $quantity, 2),
            'rate_id' => $matchedRate ? (int)$matchedRate['id'] : 0,
            'currency' => $matchedRate['currency'] ?? 'INR',
            'season' => $this->seasons->resolve($date),
        ];
    }
}
