<?php
declare(strict_types=1);
namespace PWT\Inventory;
defined('ABSPATH') || exit;
use PWT\Availability\AvailabilityService;

final class InventoryService
{
    public function __construct(private readonly AvailabilityService $availability) {}

    public function canReserve(array $items): bool
    {
        foreach ($items as $item) {
            if (!$this->availability->isAvailable(
                (int)($item['object_id'] ?? 0),
                (string)($item['item_type'] ?? 'service'),
                (string)($item['start_date'] ?? ''),
                (int)($item['quantity'] ?? 1)
            )) {
                return false;
            }
        }
        return true;
    }
}
