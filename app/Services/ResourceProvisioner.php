<?php
declare(strict_types=1);
namespace PWT\Services;
defined('ABSPATH') || exit;

use PWT\Availability\AvailabilityRepository;

final class ResourceProvisioner
{
    public function __construct(private readonly AvailabilityRepository $availability) {}

    public function provisionRoomUnit(int $unitId, string $startDate, string $endDate, int $capacity = 1): int
    {
        return $this->provision($unitId, 'room_unit', $startDate, $endDate, max(1, $capacity));
    }

    public function provisionSafariSchedule(int $scheduleId, string $date, int $capacity): int
    {
        return $this->provision($scheduleId, 'safari_schedule', $date, $date, max(1, $capacity));
    }

    private function provision(int $resourceId, string $type, string $start, string $end, int $capacity): int
    {
        $startTs = strtotime($start);
        $endTs = strtotime($end);
        if (!$startTs || !$endTs || $endTs < $startTs) {
            return 0;
        }

        $count = 0;
        for ($ts = $startTs; $ts <= $endTs; $ts += DAY_IN_SECONDS) {
            $this->availability->set($resourceId, $type, gmdate('Y-m-d', $ts), $capacity);
            $count++;
        }
        return $count;
    }
}
