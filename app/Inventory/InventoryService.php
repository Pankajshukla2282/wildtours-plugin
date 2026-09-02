<?php

declare(strict_types=1);

namespace PWT\Inventory;

defined('ABSPATH') || exit;

use DateTimeImmutable;
use PWT\Availability\AvailabilityService;

final class InventoryService
{
    public function __construct(private readonly AvailabilityService $availability) {}

    public function canReserve(array $items): bool
    {
        foreach ($items as $item) {
            if (!is_array($item)) {
                return false;
            }

            $resourceId = absint($item['object_id'] ?? $item['item_id'] ?? 0);
            $resourceType = sanitize_key((string)($item['item_type'] ?? ''));
            $quantity = max(1, (int)($item['quantity'] ?? 1));

            if (!$resourceId || $resourceType === '') {
                return false;
            }

            foreach ($this->datesForItem($item) as $date) {
                if (!$this->availability->isAvailable($resourceId, $resourceType, $date, $quantity)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function datesForItem(array $item): array
    {
        $start = sanitize_text_field((string)($item['start_date'] ?? $item['service_date'] ?? ''));
        $end = sanitize_text_field((string)($item['end_date'] ?? $start));

        if ($start === '') {
            return [];
        }

        try {
            $from = new DateTimeImmutable($start);
            $to = new DateTimeImmutable($end !== '' ? $end : $start);
        } catch (\Throwable) {
            return [];
        }

        if ($to < $from) {
            return [];
        }

        if ($to == $from) {
            return [$from->format('Y-m-d')];
        }

        $dates = [];
        for ($date = $from; $date < $to; $date = $date->modify('+1 day')) {
            $dates[] = $date->format('Y-m-d');
        }

        return $dates;
    }
}
