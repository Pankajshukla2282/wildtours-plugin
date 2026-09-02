<?php

declare(strict_types=1);

namespace PWT\Pricing;

defined('ABSPATH') || exit;

use DateTimeImmutable;
use PWT\Packages\ResourceCatalogService;

/**
 * Availability-aware package quote builder.
 * Components are supplied by the package layer; pricing remains server-side.
 */
final class PackageQuoteService
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly MoneyCalculator $money,
        private readonly ResourceCatalogService $resources
    ) {}

    public function quote(array $components, string $travelDate, int $adults = 1, int $children = 0, array $rules = []): array
    {
        $date = $this->normalizeDate($travelDate);
        $adults = max(1, $adults);
        $children = max(0, $children);
        $minimum = max(1, (int) ($rules['min_persons'] ?? 1));
        $maximum = max($minimum, (int) ($rules['max_persons'] ?? 99));
        $persons = $adults + $children;

        if ($date === '' || $persons < $minimum || $persons > $maximum) {
            return ['valid' => false, 'items' => [], 'message' => __('Traveller count or travel date is invalid.', 'wildtours-plugin')];
        }

        $items = [];
        foreach ($components as $component) {
            if (!is_array($component)) continue;
            $resourceId = absint($component['resource_id'] ?? 0);
            $type = sanitize_key((string) ($component['resource_type'] ?? ''));
            $quantity = max(1, (int) ($component['quantity'] ?? 1));
            if (!$resourceId || $type === '') continue;

            $resource = $this->resources->get($resourceId, $type);
            if (!is_array($resource) || empty($resource['bookable'])) {
                return ['valid' => false, 'items' => $items, 'message' => __('A selected package resource is not currently bookable.', 'wildtours-plugin')];
            }

            $pricingQuantity = !empty($component['per_person']) ? $persons * $quantity : $quantity;
            $quote = $this->pricing->quote($resourceId, $type, $date, $pricingQuantity, (float) ($resource['base_price'] ?? 0));
            if (empty($quote['valid']) || (float) ($quote['total'] ?? 0) <= 0) {
                return ['valid' => false, 'items' => $items, 'message' => __('A valid rate is not configured for one or more package resources.', 'wildtours-plugin')];
            }
            $items[] = [
                'resource_id' => $resourceId,
                'resource_type' => $type,
                'quantity' => $pricingQuantity,
                'total' => (float) $quote['total'],
                'unit_price' => (float) ($quote['unit_price'] ?? 0),
            ];
        }

        if ($items === []) return ['valid' => false, 'items' => [], 'message' => __('This package has no priced bookable resources.', 'wildtours-plugin')];

        $result = $this->money->quote(
            $items,
            (float) ($rules['discount'] ?? 0),
            (float) ($rules['tax_rate'] ?? 0),
            (float) ($rules['deposit_percent'] ?? 0)
        );
        $result['valid'] = true;
        $result['items'] = $items;
        $result['travel_date'] = $date;
        $result['adults'] = $adults;
        $result['children'] = $children;
        $result['persons'] = $persons;
        return $result;
    }

    private function normalizeDate(string $date): string
    {
        try {
            return (new DateTimeImmutable($date))->format('Y-m-d');
        } catch (\Throwable) {
            return '';
        }
    }
}
