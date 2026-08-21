<?php
declare(strict_types=1);
namespace PWT\Pricing;
defined('ABSPATH') || exit;
define('HOUR_IN_SECONDS', 60 * 60);

final class PricingService
{
    public function __construct(private readonly RateRepository $rates,
        private readonly SeasonResolver $seasons) {}

    const CACHE_KEY_PREFIX = 'pwt_pricing';

    public function quote(int $resourceId, string $resourceType, string $date, int $quantity = 1, float $fallback = 0.0): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX . '_' . $resourceId . '_' . $resourceType . '_' . $date;
        $unit = wp_cache_get($cacheKey, 'pwt_pricing');
        if (false !== $unit) {
            $unit = (float)$unit;
        } else {
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

            wp_cache_set($cacheKey, (string)$unit, 'pwt_pricing', HOUR_IN_SECONDS);
        }

        $quantity = max(1, $quantity);
        $total = round($unit * $quantity, 2);

        return [
            'resource_id' => $resourceId,
            'resource_type' => $resourceType,
            'date' => $date,
            'quantity' => $quantity,
            'unit_price' => $unit,
            'total' => $total,
            'rate_id' => $matchedRate ? (int)$matchedRate['id'] : 0,
            'currency' => $matchedRate['currency'] ?? 'INR',
            'season' => $this->seasons->resolve($date),
        ];
    }
}