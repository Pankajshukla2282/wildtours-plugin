<?php
declare(strict_types=1);
namespace PWT\Availability;
defined('ABSPATH') || exit;

final class AvailabilityService
{
    public function __construct(private readonly AvailabilityRepository $repository) {}

    public function remaining(int $resourceId, string $resourceType, string $date): int
    {
        $row = $this->repository->get($resourceId, $resourceType, $date);
        if (!$row) {
            return 0; // Unknown capacity is not configured and must not be reservable.
        }
        if (($row['status'] ?? 'open') !== 'open') {
            return 0;
        }
        return max(0, (int)$row['capacity'] - (int)$row['reserved'] - (int)$row['blocked']);
    }

    public function isAvailable(int $resourceId, string $resourceType, string $date, int $quantity = 1): bool
    {
        $remaining = $this->remaining($resourceId, $resourceType, $date);
        return $remaining >= max(1, $quantity);
    }
}
